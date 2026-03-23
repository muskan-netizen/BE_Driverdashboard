<?php

namespace App\Support;

use App\Model\Order as DispatchOrder;
use App\Model\Order\Order as RoyoPanelOrder;

/**
 * GST-inclusive payable (COD cash or panel payable): lead on accept; COD completion = % of ex-GST + GST;
 * prepaid completion = (1 − platform_rate) × ex-GST credited to wallet.
 */
final class OrderPayableSplit
{
    /** Royo panel `orders.payable_amount` (dispatch `sync_order_id` → panel `orders.id`). */
    public static function resolvePanelPayableAmount(int $panelOrderId): float
    {
        if ($panelOrderId <= 0) {
            return 0.0;
        }
        try {
            $row = RoyoPanelOrder::query()->whereKey($panelOrderId)->first(['payable_amount']);
            if (!$row || $row->payable_amount === null) {
                return 0.0;
            }

            return round(max(0, (float) $row->payable_amount), 2);
        } catch (\Throwable $e) {
            return 0.0;
        }
    }

    /**
     * Agent share for one dispatch order: COD cash → panel payable → fallback dispatch order_cost (GST-inclusive).
     */
    public static function agentEarningsFromDispatchOrder(DispatchOrder $order): float
    {
        $cash = (float) ($order->cash_to_be_collected ?? 0);
        if ($cash > 0) {
            $leadTakenOnAccept = empty($order->skip_accept_lead_fee);

            return self::codAgentAttributedShare($cash, $leadTakenOnAccept);
        }

        $syncId = (int) ($order->sync_order_id ?? 0);
        if ($syncId > 0) {
            $panel = self::resolvePanelPayableAmount($syncId);
            if ($panel > 0) {
                return self::prepaidAgentCreditOnComplete($panel);
            }
        }

        $orderCost = (float) ($order->order_cost ?? 0);
        if ($orderCost > 0) {
            return self::prepaidAgentCreditOnComplete($orderCost);
        }

        return 0.0;
    }

    /** Sum of agent earnings across completed orders (e.g. lifetime_earnings on driver app). */
    public static function lifetimeAgentEarningsForDriver(int $driverId): float
    {
        $total = 0.0;
        $cols = ['cash_to_be_collected', 'sync_order_id', 'order_cost'];
        if (\checkColumnExists('orders', 'skip_accept_lead_fee')) {
            $cols[] = 'skip_accept_lead_fee';
        }
        $orders = DispatchOrder::query()
            ->where('driver_id', $driverId)
            ->where('status', 'completed')
            ->get($cols);

        foreach ($orders as $order) {
            $total += self::agentEarningsFromDispatchOrder($order);
        }

        return round($total, 2);
    }

    /** Lead fee charged on order accept (COD and prepaid). Full config amount when payable unknown. */
    public static function leadFeeOnAccept(?float $payableInclusive = null): float
    {
        $fee = round(max(0, (float) config('dispatch_order_split.lead_fee', 30)), 2);
        if ($fee <= 0) {
            return 0.0;
        }
        if ($payableInclusive === null) {
            return $fee;
        }
        $payable = max(0, round($payableInclusive, 2));

        return $payable > 0 ? round(min($fee, $payable), 2) : $fee;
    }

    /** Ex-GST subtotal and GST portion from GST-inclusive payable. */
    public static function subtotalAndGst(float $payableInclusive): array
    {
        $payable = max(0, round($payableInclusive, 2));
        $gstRate = max(0, (float) config('dispatch_order_split.gst_rate', 0.18));
        if ($payable <= 0) {
            return ['subtotal_ex_gst' => 0.0, 'gst_amount' => 0.0, 'payable' => 0.0];
        }
        $divisor = 1 + $gstRate;
        $subtotalExGst = $divisor > 0 ? round($payable / $divisor, 2) : $payable;

        return [
            'payable' => $payable,
            'subtotal_ex_gst' => $subtotalExGst,
            'gst_amount' => round($payable - $subtotalExGst, 2),
        ];
    }

    /** COD on complete: 20% of ex-GST subtotal + GST amount. */
    public static function codWalletDebitOnComplete(float $payableInclusive): float
    {
        $platformRate = max(0, min(1, (float) config('dispatch_order_split.platform_rate', 0.20)));
        $parts = self::subtotalAndGst($payableInclusive);
        if ($parts['payable'] <= 0) {
            return 0.0;
        }
        $commissionOnSubtotal = round($parts['subtotal_ex_gst'] * $platformRate, 2);

        return round($commissionOnSubtotal + $parts['gst_amount'], 2);
    }

    /**
     * Auto-allocation / COD: wallet must cover lead (on accept) + commission on ex-GST + GST (on complete)
     * so funds exist when the order is completed. Set $includeLeadFeeOnAccept false when accept lead is skipped
     * (e.g. direct driver_unique_id booking): completion debit (20% + GST) is unchanged.
     */
    public static function minWalletBalanceRequiredForCodAllocation(float $payableInclusive, bool $includeLeadFeeOnAccept = true): float
    {
        $payable = max(0, round($payableInclusive, 2));
        if ($payable <= 0) {
            return 0.0;
        }
        $lead = $includeLeadFeeOnAccept ? self::leadFeeOnAccept($payable) : 0.0;

        return round($lead + self::codWalletDebitOnComplete($payable), 2);
    }

    /** Prepaid on complete: agent credit = (1 − platform_rate) × ex-GST subtotal (e.g. 80% of base at 20%). */
    public static function prepaidAgentCreditOnComplete(float $payableInclusive): float
    {
        $platformRate = max(0, min(1, (float) config('dispatch_order_split.platform_rate', 0.20)));
        $parts = self::subtotalAndGst($payableInclusive);
        if ($parts['payable'] <= 0) {
            return 0.0;
        }

        return round($parts['subtotal_ex_gst'] * (1 - $platformRate), 2);
    }

    /**
     * COD: net cash attributed to agent after platform wallet debits.
     * $leadTakenOnAccept: false when accept-time lead fee was skipped (driver_unique_id); completion (20% + GST) unchanged.
     */
    public static function codAgentAttributedShare(float $payableInclusive, bool $leadTakenOnAccept = true): float
    {
        $payable = max(0, round($payableInclusive, 2));
        if ($payable <= 0) {
            return 0.0;
        }
        $lead = $leadTakenOnAccept ? self::leadFeeOnAccept($payable) : 0.0;
        $complete = self::codWalletDebitOnComplete($payable);
        $net = round($payable - $lead - $complete, 2);

        return max(0.0, $net);
    }

    public static function fromInclusivePayable(float $payableInclusive, bool $leadTakenOnAccept = true): array
    {
        $payable = max(0, round($payableInclusive, 2));
        $platformRate = max(0, min(1, (float) config('dispatch_order_split.platform_rate', 0.20)));

        if ($payable <= 0) {
            return self::emptyResult();
        }

        $parts = self::subtotalAndGst($payable);
        $subtotalExGst = $parts['subtotal_ex_gst'];
        $gstAmount = $parts['gst_amount'];
        $platformShareOnSubtotal = round($subtotalExGst * $platformRate, 2);
        $leadApplied = $leadTakenOnAccept ? self::leadFeeOnAccept($payable) : 0.0;
        $codCompletionDebit = self::codWalletDebitOnComplete($payable);
        $prepaidCredit = self::prepaidAgentCreditOnComplete($payable);
        $codAgentShare = self::codAgentAttributedShare($payable, $leadTakenOnAccept);

        return [
            'payable_inclusive' => $payable,
            'subtotal_ex_gst' => $subtotalExGst,
            'gst_amount' => $gstAmount,
            'lead_fee' => $leadApplied,
            'pool_after_lead' => round(max(0, $subtotalExGst - $platformShareOnSubtotal), 2),
            'platform_share' => $platformShareOnSubtotal,
            'cod_completion_wallet_debit' => $codCompletionDebit,
            'prepaid_completion_credit' => $prepaidCredit,
            'agent_earnings' => $prepaidCredit,
            'cod_agent_attributed_share' => $codAgentShare,
            'platform_wallet_debit' => round($leadApplied + $codCompletionDebit, 2),
        ];
    }

    /** Minimum wallet at accept: lead fee only (per product spec). */
    public static function platformWalletDebit(float $payableInclusive): float
    {
        return self::leadFeeOnAccept($payableInclusive > 0 ? $payableInclusive : null);
    }

    /** Prepaid/panel lifetime & earnings display: 80% of ex-GST base (at 20% rate). COD use {@see codAgentAttributedShare}. */
    public static function agentEarnings(float $payableInclusive): float
    {
        return self::prepaidAgentCreditOnComplete($payableInclusive);
    }

    private static function emptyResult(): array
    {
        return [
            'payable_inclusive' => 0.0,
            'subtotal_ex_gst' => 0.0,
            'gst_amount' => 0.0,
            'lead_fee' => 0.0,
            'pool_after_lead' => 0.0,
            'platform_share' => 0.0,
            'cod_completion_wallet_debit' => 0.0,
            'prepaid_completion_credit' => 0.0,
            'agent_earnings' => 0.0,
            'cod_agent_attributed_share' => 0.0,
            'platform_wallet_debit' => 0.0,
        ];
    }
}
