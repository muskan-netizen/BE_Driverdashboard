<?php
namespace App\Traits;

use App\Model\Agent;
use App\Support\OrderPayableSplit;

trait agentEarningManager{

    //------------------------------Function created by surendra singh--------------------------//
    /**
     * Ledger-style balance for COD / driver_cost / payments (excludes prepaid payout pool — use agent.available_funds).
     */
    public static function getAgentEarning($agentid, $include_wallet = 1)
    {
        $agent = Agent::where('id', $agentid)->first();
        if (!$agent) {
            return 0;
        }

        $credit = $agent->agentPayment->sum('cr');
        $debit = $agent->agentPayment->sum('dr');

        $codSelect = ['cash_to_be_collected'];
        if (checkColumnExists('orders', 'skip_accept_lead_fee')) {
            $codSelect[] = 'skip_accept_lead_fee';
        }
        $codRows = $agent->order()
            ->where('payment_mode', 'Cash On Delivery')
            ->where('status', 'completed')
            ->get($codSelect);
        $cash = $codRows->sum('cash_to_be_collected');
        $codAgentShareFromPayable = 0.0;
        foreach ($codRows as $codRow) {
            $c = (float) ($codRow->cash_to_be_collected ?? 0);
            if ($c > 0) {
                $leadTaken = empty($codRow->skip_accept_lead_fee ?? false);
                $codAgentShareFromPayable += OrderPayableSplit::codAgentAttributedShare($c, $leadTaken);
            }
        }
        $codAgentShareFromPayable = round($codAgentShareFromPayable, 2);

        $driver_cost = $agent->order->where('status', 'completed')->sum('driver_cost'); //->where('is_comm_settled', '!=', 2)

        return $debit + $driver_cost - ($credit + $cash) + $codAgentShareFromPayable;
    }
    //-------------------------------------------------------------------------------------------//
}
