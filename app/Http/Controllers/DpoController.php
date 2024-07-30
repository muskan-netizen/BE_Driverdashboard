<?php

namespace App\Http\Controllers;

use App\Http\Controllers\WalletController;
use App\Model\Agent;
use App\Model\Payment;
use Illuminate\Http\Request;

class DpoController extends Controller
{
    public function successPage(Request $request)
    {
        try {
            $returnUrl = route('payment.gateway.return.response') . '/?gateway=dpo' . '&status=200&transaction_id=' . $request->transactionid . '&action=wallet';
            $request->request->add(['transaction_id' => $request->transactionid, 'payment_option_id' => 20]);
            $walletController = new WalletController();
            $res = $walletController->creditAgentWallet($request);
            return redirect($returnUrl);
        } catch (\Exception $e) {
            \Log::info($e->getMessage());
            return $e->getMessage();
        }
    }


}
