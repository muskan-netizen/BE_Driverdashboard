<?php

namespace App\Http\Controllers\Accountancy;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Model\{Agent, Order, AgentPayout, PayoutOption, Team};
use Auth;
use DataTables;
use App\Traits\agentEarningManager;
use App\Http\Controllers\{BaseController, StripeGatewayController};
use App\Traits\ApiResponser;
use DB, Log;
use App\Traits\GlobalFunction;
use App\Traits\DriverExportTrait;
use Maatwebsite\Excel\Facades\Excel;

class DriverAccountingController extends BaseController
{
    use ApiResponser,GlobalFunction,DriverExportTrait;
   
    public function index(Request $request) {
        // $geoagents = $this->getGeoBasedAgentsData('6', '0', '', '30-03-2023', '100','113');
        // pr($geoagents);
        $user = Auth::user();
        if ( $user->is_superadmin == 0 &&  $user->all_team_access == 0) {
            $userid = $user->id;
            $agentList = Agent::whereHas('team.permissionToManager', function($q) use ($userid){
                $q->where('sub_admin_id', $userid);
            })->pluck('name', 'id')->toArray();
        }else{
            $agentList = Agent::pluck('name', 'id')->toArray();
        }
        if( $request->status ) {
            $status = $request->status;
        } else {
            $status = 'settlement';
        }
        return view('accountancy.driver.index')->with(['status' => $status, 'agentList' => $agentList]);
    }

    /**
     * @return JSON
     */
    public function driverList(Request $request) {
        
        $agents = Agent::select('id', 'name');
        if( (strlen($request->term) > 0)) {
            $agents = $agents->where('name', 'like', '%' .$request->term.'%')->select('id', 'name');
        } 
        $agents = $agents->get();
        return response()->json($agents);
    }

    /**
     * @return JSON
     */
    public function driverDatatable(Request $request) {
        
        $data = $request->all();
        $user = Auth::user();
        $userid = $user->id;
        $type = $request->routesListingType;
        $orders = Order::with(['agent', 'getAgentPayout']);
        if ( $user->is_superadmin == 0 &&  $user->all_team_access == 0) {
            $orders = $orders->whereHas('agent.team.permissionToManager', function($q) use ($userid){
            $q->where('sub_admin_id', $userid);
        });}
        
        if($type == 'statement') {
            $orders = $orders->whereHas('getAgentPayout' , function($query) use($type) {
                $query->where('status', 1);
            });
        }
        $orders = $orders->where('status', 'completed');

        
        if (!empty($request->date_filter)) {
            
            $date_filter = $request->date_filter;
            if($date_filter){
                
                $date_explode = explode('to', $date_filter);
                $from_date =  trim($date_explode[0]);
                $end_date = trim($date_explode[1]);
                
                $orders->whereBetween('order_time', [$from_date, $end_date]);
            }
        }

        if(isset($request->driver_id) ) {
            $orders->where('driver_id', $request->driver_id);
        }

        // Create Datatable
        $orders = $orders->orderBy('id', 'desc');
        $order_clone = clone $orders;
        $driver_cost = $order_clone->get()->sum('driver_cost');
        // Log::info($driver_cost);
        return Datatables::of($orders)
                ->editColumn('id', function ($orders) {
                    return '';    
                })
                ->editColumn('order_number', function ($orders) {
                    return $orders->order_number ?? null;
                })
                ->editColumn('delivery_boy_id', function ($orders) {
                    return $orders->driver_id ?? 'N/A';
                })
                ->editColumn('delivery_boy_name', function ($orders) {
                    return optional($orders->agent)->name ?? 'N/A';
                })
                ->editColumn('delivery_boy_number', function ($orders) {
                    return optional($orders->agent)->phone_number ?? 'N/A';
                })
                ->editColumn('vendor_name', function ($orders) {
                    return $orders->vendor_name ?? '' ?? 'N/A';
                })
                ->editColumn('distance', function ($orders) {
                    return $orders->actual_distance ?? 'N/A';
                })
                ->editColumn('duration', function ($orders) {
                    return 'N/A';
                })
                ->editColumn('cash', function ($orders) {
                    return 'N/A';
                })
                ->editColumn('driver_cost', function ($orders) {
                    return $orders->driver_cost ?? 'N/A';
                })
                ->editColumn('employee_commission_percentage', function ($orders) {
                    return $orders->agent_commission_percentage ?? 'N/A';
                })
                ->editColumn('employee_commission_fixed', function ($orders) {
                    return $orders->agent_commission_fixed ?? 'N/A';
                })
                ->editColumn('order_amount', function ($orders) {
                    return $orders->order_cost ?? 'N/A';
                })
                ->editColumn('pay_to_driver', function ($orders) {
                    return optional($orders->getAgentPayout)->amount ?? 'N/A';
                })
                ->editColumn('payment_type', function () {
                    return 'N/A';
                })
                ->editColumn('tip', function () {
                    return 'N/A';
                })
                ->addColumn('payout_option_id', function ($orders) {
                    return optional($orders->getAgentPayout)->payout_option_id ?? '';
                })
                ->addColumn('agent_payouts_id', function ($orders) {
                    return optional($orders->getAgentPayout)->id ?? '';
                })
                ->addColumn('agent_sum', function($orders) use($driver_cost) {
                    return $driver_cost ?? '0.00';
                })
                ->addColumn('order_date', function($orders) {
                    return ($orders->created_at ?? '')->format("d-m-Y h:i A");
                })
                ->filter(function ($instance) use ($request) {
                    if (!empty($request->get('search'))) {
                       
                        $search = $request->get('search');
                        $instance->where(function($query) use($search){
                            $query
                            
                            ->orWhereHas('agent', function($q) use($search){
                                $q->where('name', 'Like', '%'.$search.'%')
                                ->orWhere('phone_number', 'Like', '%'.$search.'%');
                            })
                            ->orWhere('vendor_name', 'Like', '%'.$search.'%');
                        });
                    }
                }, true)
                ->rawColumns(['payout_option_id', 'agent_payouts_id'])
                ->make(true)
                ;
    }


    public function export(Request $request){
        return  $this->exportDriver($request);
     }
    /**
     * Payout to agent
     */
    public function agentPayoutRequestComplete(Request $request, $domain = ''){
        try{
            
            $agent_payouts_ids  = explode(',', $request->agent_payouts_ids);
            
            if( !empty($agent_payouts_ids) ) {
                foreach($agent_payouts_ids as $key => $value) {
                    
                    if( !empty($value) ) {
                        
                        # Payout to agent
                        $user = Auth::user();
                        $id = $value;
                        
                    
                        $payout = AgentPayout::with(['payoutBankDetails'=> function($q){
                            $q->where('status', 1);
                        }])->where('id', $id)->first();

                        $payout_option_id = $payout->payout_option_id;

                        $request->request->add(['agent_id' => $payout->agent_id]);
                        $request->request->add(['amount' => $payout->amount]);
                        $request->request->add(['payout_id' => $id]);
                        $request->request->add(['payout_option_id' => $payout_option_id]);
                        
                        $agent = Agent::where('id', $payout->agent_id)->where('is_approved', 1)->first();
                        if(!$agent){
                            return Redirect()->back()->with('error', __('This '.getAgentNomenclature().' is not approved!'));
                        }
                        
                        $agent_account = $payout->payoutBankDetails->first() ? $payout->payoutBankDetails->first()->beneficiary_account_number : '';
                        $agent_id = $agent->id;
                        

                        $available_funds = agentEarningManager::getAgentEarning($payout->agent_id, 1);

                        if($request->amount > $available_funds){
                            return Redirect()->back()->with('error', __('Payout amount is greater than '.getAgentNomenclature().' available funds'));
                        }

                        $payout_option = '';
                        if($payout_option_id > 0){
                            $payout_option = PayoutOption::where('id', $payout_option_id)->value('title');
                        }

                        /////// Payout via stripe ///////
                        if($payout_option_id == 2){
                            $stripeController = new StripeGatewayController();
                            $response = $stripeController->AgentPayoutViaStripe($request)->getData();
                            if($response->status != 'Success'){
                                return Redirect()->back()->with('error', __($response->message));
                            }
                            $request->request->add(['transaction_id' => $response->data]);
                        }

                        // update payout request
                        $request->request->add(['status' => 1]);
                        $udpate_response = $this->updateAgentPayoutRequest($request, $payout)->getData();

                        if($udpate_response->status == 'Success'){
                            $debit_amount = $request->amount;
                            $wallet = $agent->wallet;
                            if ($debit_amount > 0) {
                                $meta = [
                                    'type' => 'payout',
                                    'transaction_type' => 'payout_success',
                                    'payment_option' => $payout_option,
                                    'payout_id' => $payout->id
                                ];
                                if(isset($request->transaction_id)){
                                    $meta['transaction_id'] = $request->transaction_id;
                                }
                                $custom_meta = 'Debited for payout request';
                                if($payout_option_id == 4){
                                    // $custom_meta = $custom_meta . '<b>XXXX'.substr($agent_account, -4).'</b>';
                                    $meta['bank_account'] = $agent_account;
                                }
                                $meta['description'] = $custom_meta;
                                $wallet->forceWithdrawFloat($debit_amount, $meta);
                            }
                        }
                        
                        
                    }
                }
                return Redirect()->back()->with('success', __('Payout has been completed successfully'));
            }
            else {
                return Redirect()->back()->with('error', __('Drivers not found'));
            }
        }
        catch(Exception $ex){
            Log::info('## Exception come here ##');
            Log::info($ex->getLine());
            Log::info('## Exception come here end ##');
            DB::rollback();
            return Redirect()->back()->with('error', $ex->getMessage());
        }
    }


    public function updateAgentPayoutRequest($request, $payout=''){
        try{
            DB::beginTransaction();
            $payout->transaction_id = $request->transaction_id ?? null;
            $payout->status = $request->status;
            $payout->update();
            DB::commit();
            return $this->success('', __('Payout has been completed successfully'));
        }
        catch(\Exception $ex){
            dump($ex->getMessage());
            dd($ex->getLine());
            DB::rollback();
            return $this->error($ex->getMessage(), $ex->getCode());
        }
    }


}
