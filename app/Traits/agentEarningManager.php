<?php
namespace App\Traits;
use DB;
use Illuminate\Support\Collection;
use App\Model\{Client, ClientPreference, User, Agent, Order, PaymentOption, PayoutOption, AgentPayout};

trait agentEarningManager{

    //------------------------------Function created by surendra singh--------------------------//
    public static function getAgentEarning($agentid, $include_wallet = 1)
    {
        $agent = Agent::where('id', $agentid)->first();
         
        $credit = $agent->agentPayment->sum('cr');
        $debit = $agent->agentPayment->sum('dr');
        
        $wallet_balance = 0;
        if($agent->wallet){
            $wallet_balance = $agent->balance;
        }

        $cash  = $agent->order->where('payment_mode','Cash On Delivery')->where('status', 'completed')->sum('cash_to_be_collected');
        $driver_cost  = $agent->order->where('status', 'completed')->sum('driver_cost'); //->where('is_comm_settled', '!=', 2)
        
        if($include_wallet == 1):
            $available_funds =  $debit + $driver_cost - ($credit + $cash);
        else:
            $available_funds = $debit + $driver_cost - ($credit + $cash);
        endif;
        return $available_funds;
    }
    //-------------------------------------------------------------------------------------------//
    

}
