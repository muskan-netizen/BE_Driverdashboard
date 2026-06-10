<?php

namespace App\Http\Controllers;

use Config;
use Storage,PDF;
use Carbon\Carbon;
use App\Model\{Agent, Client, ClientPreference, Order,DriverRegistrationDocument, OrderFormAttribute};
use App\Support\OrderPayableSplit;
use Bavix\Wallet\Internal\Service\DatabaseServiceInterface;
use Bavix\Wallet\Models\Transaction as WalletTransaction;
use Illuminate\Support\Facades\Cache;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Traits\{sendCustomNotification,FormAttributeTrait,RatingTrait};

class TrackingController extends Controller
{
    use sendCustomNotification,FormAttributeTrait,RatingTrait;

    public function OrderTracking($domain = '', $user, $id)
    {
        $respnse = $this->connection($user);
        if ($respnse['status'] == 'connected') {
            $order   = DB::connection($respnse['database'])->table('orders')->where('orders.unique_id', $id)->leftJoin('agents', 'orders.driver_id', '=', 'agents.id')
                ->select('orders.*', 'agents.name','agents.vehicle_type_id', 'agents.profile_picture', 'agents.phone_number')->first();
            if (isset($order->id)) {
                $tasks = DB::connection($respnse['database'])->table('tasks')->where('order_id', $order->id)->leftJoin('locations', 'tasks.location_id', '=', 'locations.id')
                    ->select('tasks.*', 'locations.latitude', 'locations.longitude', 'locations.short_name', 'locations.address')->orderBy('task_order')->get();
                 $total_orders_ids = DB::connection($respnse['database'])->table('orders')->where('driver_id',$order->driver_id)->pluck('id');
                $orderc = DB::connection($respnse['database'])->table('orders')->where('id', $order->id)->where('status','completed')->count();
                $agent_ratings = DB::connection($respnse['database'])->table('order_ratings')->whereIn('order_id', $total_orders_ids)->get();

                if($orderc == 0)
                $agent_location = DB::connection($respnse['database'])->table('agent_logs')->where('agent_id', $order->driver_id)->latest()->first();
                else{
                    $agent_location = [];
                    $lastElement = $tasks->last();
                    $agent_location['lat']  = $lastElement->latitude;
                    $agent_location['lng']  = $lastElement->longitude;
                }
                $map_key = DB::connection($respnse['database'])->table('client_preferences')->select('map_key_1')->latest()->first();
                
                $mapkey = $map_key->map_key_1 ?? '';

                $client = ClientPreference::where('client_id', $user)->first();

                return view('tracking/tracking', compact('tasks', 'order', 'agent_location','mapkey','client'));
            } else {
                return view('tracking/order_not_found');
            }
        } else {
            return view('tracking/order_not_found');
        }
    }

    public function OrderFeedback($domain = '', $user, $id)
    {
        $respnse = $this->connection($user);

        if ($respnse['status'] == 'connected') {
            $order   = DB::connection($respnse['database'])->table('orders')->where('unique_id', $id)->first();

            if (isset($order->id)) {
                return view('tracking/feedback', compact('user', 'id'));
            } else {
                return view('tracking/order_not_found');
            }
        } else {
            return view('tracking/order_not_found');
        }
    }


    public function SaveFeedback(Request $request, $domain = '')
    {
        $respnse = $this->connection($request->client_code);

        if ($respnse['status'] == 'connected') {
            $order   = DB::connection($respnse['database'])->table('orders')->where('unique_id', $request->unique_id)->first();

            if (isset($order->id)) {
                $check_alredy  = DB::connection($respnse['database'])->table('order_ratings')->where('order_id', $order->id)->first();

                if (isset($check_alredy->id)) {
                    return response()->json(['status' => true, 'message' => 'Feedback has been already submitted.']);
                } else {
                    $data = [
                        'order_id'    => $order->id,
                        'rating'      => $request->rating,
                        'review'      => $request->review,
                    ];

                    DB::connection($respnse['database'])->table('order_ratings')->insert($data);

                    return response()->json(['status' => true, 'message' => 'Your feedback is submitted']);
                }
            } else {
                return response()->json(['status' => true, 'message' => 'Order Not Found']);
            }
        } else {
            return response()->json(['status' => true, 'message' => 'Sorry Wrong Url']);
        }
    }


    public function connection($user)
    {
        $client = Client::where('code', $user)->first();

        if (isset($client->database_name)) {
            $database_name = 'db_' . $client->database_name;

            $default = [
                'driver' => env('DB_CONNECTION', 'mysql'),
                'host' => config('database.connections.mysql.host'),
                'port' => config('database.connections.mysql.port'),
                'database' => $database_name,
                'username' => config('database.connections.mysql.username'),
                'password' => config('database.connections.mysql.password'),
                'charset' => 'utf8mb4',
                'collation' => 'utf8mb4_unicode_ci',
                'prefix' => '',
                'prefix_indexes' => true,
                'strict' => false,
                'engine' => null
            ];

            Config::set("database.connections.$database_name", $default);

            return  $respnse = ['status' => 'connected', 'database' => $database_name];
        } else {
            return  $respnse = ['status' => 'failed'];
        }
    }


    public function OrderTrackingDetail($domain = '', $user, $id, Request $request)
    {
        $respnse = $this->connection($user);
        $timezone = $request->header('timezone') ??'Asia/Kolkata';
        $total_order_by_agent = 0;
        $avgrating = 0;
        $agent_ratings = [];
        $noofCopassengers = 0;
        $agent = [];
        if ($respnse['status'] == 'connected') {
            $order = DB::connection($respnse['database'])->table('orders')->where('orders.unique_id', $id)->leftJoin('agents', 'orders.driver_id', '=', 'agents.id')
                ->select('orders.*', 'agents.name','agents.vehicle_type_id','agents.color','agents.plate_number', 'agents.profile_picture', 'agents.phone_number')->first();
            if (isset($order->id)) {
                $tasks = DB::connection($respnse['database'])->table('tasks')->where('order_id', $order->id)->leftJoin('locations', 'tasks.location_id', '=', 'locations.id')
                    ->select('tasks.*', 'locations.latitude', 'locations.longitude', 'locations.short_name', 'locations.address',DB::raw("CONVERT_TZ(tasks.assigned_time, 'UTC', '$timezone') as assigned_time"))->orderBy('task_order')->get();
                $orderc = DB::connection($respnse['database'])->table('orders')->where('id', $order->id)->where('status','completed')->count();
                
                $driver_id = '';
                if( checkColumnExists('orders', 'refer_driver_id') && $order->driver_id == null &&  $order->refer_driver_id > 0){
                $driver_id = $order->refer_driver_id ;
                }else if($order->driver_id > 0){
                $driver_id = $order->driver_id ;
                }

                if($orderc == 0){
                    $agent_location = DB::connection($respnse['database'])->table('agent_logs')->where('agent_id', $driver_id)->latest()->first();
                }
                else{
                    $agent_location = [];
                    $lastElement = $tasks->last();
                    $agent_location['lat']  = $lastElement->latitude;
                    $agent_location['lng']  = $lastElement->longitude;
                }
               
                

                if($driver_id > 0){
                    $total_orders = DB::connection($respnse['database'])->table('orders')->where('driver_id',$driver_id)->pluck('id');
                    $total_order_by_agent = count($total_orders);

                    $total_other_pending_orders = DB::connection($respnse['database'])->table('orders')->select(DB::raw("SUM(no_seats_for_pooling) as total_booked_seats"))->where('driver_id', $driver_id)->where('status', '=', 'unassigned')->where('id', '!=', $order->id)->where('is_cab_pooling', 1)->first();
                    $noofCopassengers = !empty($total_other_pending_orders) ? $total_other_pending_orders->total_booked_seats : 0;
                    $avgrating = DB::connection($respnse['database'])->table('order_ratings')->whereIn('order_id',$total_orders)->sum('rating');
                    if($avgrating != 0)
                    $avgrating = $avgrating/$avgrating;

                    $agent_ratings = DB::connection($respnse['database'])->table('order_ratings')->whereIn('order_id',$total_orders)->get();

                    $agent = DB::connection($respnse['database'])->table('agents')->where('id',$driver_id)->first();
                   
                    
                    $driver_document = DB::connection($respnse['database'])->table('driver_registration_documents')
                                            ->join('agent_docs','driver_registration_documents.name','=', 'agent_docs.label_name')
                                            ->select('agent_docs.*','driver_registration_documents.file_type','driver_registration_documents.name')
                                            ->where('agent_docs.agent_id',$driver_id)->get();
                    $order->driver_document =$driver_document;        
                }

                $img = 'https://imgproxy.royodispatch.com/insecure/fit/300/100/sm/0/plain/' . Storage::disk('s3')->url($order->profile_picture ?? 'assets/client_00000051/agents605b6deb82d1b.png/XY5GF0B3rXvZlucZMiRQjGBQaWSFhcaIpIM5Jzlv.jpg');
                $base_url = 'https://royodelivery-assets.s3.us-west-2.amazonaws.com';

                $db_name = DB::connection($respnse['database'])->table('clients')->select('database_name')->first()->database_name;
                return response()->json([
                    'message' => 'Successfully',
                    'tasks' => $tasks,
                    'order'  => $order,
                    'agent_image' => $img, 
                    'agent_location'  => $agent_location,
                    'total_order_by_agent'  => $total_order_by_agent,
                    'avgrating'  => $avgrating,
                    'agent'  => $agent,
                    'agent_ratings'  => $agent_ratings,
                    'base_url' => $base_url,
                    'noofCopassengers' => $noofCopassengers,
                    'agent_dbname'  => $db_name
                ], 200);

                return view('tracking/tracking', compact('tasks', 'order', 'agent_location'));
            } else {

                return response()->json([
                    'message' => 'Error'], 400);
                return view('tracking/order_not_found');
            }
        } else {
            return response()->json([
                'message' => 'Error'], 400);
            return view('tracking/order_not_found');
        }
    }

    public function DriverRating($domain = '', $user, $id, Request $request)
    {
       // dd('sdfsdf');
        // $domain = '', $user, $id
        $respnse = $this->connection($user);
        if ($respnse['status'] == 'connected') {
            $order = DB::connection($respnse['database'])->table('orders')->where('unique_id', $id)->first();            

            if (isset($order->id)) {
                $check_alredy  = DB::connection($respnse['database'])->table('driver_ratings')->where('order_id', $order->id)->first();

                $data = [
                    'order_id'    => $order->id,
                    'driver_id'   => $order->driver_id,
                    'rating'      => $request->rating ?? 0,
                    'review'      => $request->review,
                ];
                if (isset($check_alredy->id)) {
                    $data['updated_at'] = date('Y-m-d H:i:s');
                    DB::connection($respnse['database'])->table('driver_ratings')->where('id',$check_alredy->id)->update($data);
                    return response()->json(['status' => true, 'message' => __('Rating has been updated')]);
                } else {
                    $data['created_at'] = date('Y-m-d H:i:s');

                    DB::connection($respnse['database'])->table('driver_ratings')->insert($data);

                    return response()->json(['status' => true, 'message' => __('Your rating is submitted')]);
                }
            } else {
                return response()->json(['status' => true, 'message' => __('Order Not Found')]);
            }
        } else {
            return response()->json([
                'message' => 'Error'], 400);
            // return view('tracking/order_not_found');
        }

    }



    # order cancel section 

    public function orderCancelFromOrder(Request $request, $domain = '', $user, $id)
    {
        $respnse = $this->connection($user);
        if ($respnse['status'] == 'connected') {
            DB::connection($respnse['database'])->beginTransaction();
            try{
                $database_name = $respnse['database'];
                $order = DB::connection($respnse['database'])->table('orders')->where('unique_id', $id)->first();
                if (isset($order->id)) {
                    $orderc = DB::connection($respnse['database'])->table('orders')->where('id', $order->id)->update(['status' => 'cancelled', 'note'=>$request->reject_reason]);

                    $data = [
                        'order_id'          => $order->id,
                        'driver_id'         => $order->driver_id,
                        'status'            => 2,
                        'created_at'        => Carbon::now()->toDateTimeString(),
                        'updated_at'        => Carbon::now()->toDateTimeString(),
                    ];
                    $order_reject = DB::connection($respnse['database'])->table('task_rejects')->insert($data);

                    if(!empty($order->driver_id)){
                        $client_preferences = DB::connection($respnse['database'])->table('client_preferences')->first();
                        $oneagent = DB::connection($respnse['database'])->table('agents')->where('id', $order->driver_id)->first();
                        $notificationdata = [
                            'order_id'            => $order->id,
                            'batch_no'            => '',
                            'driver_id'           => $order->driver_id,
                            'notification_time'   => Carbon::now()->addSeconds(2)->format('Y-m-d H:i:s'),
                            'notificationType'    => 'CANCELLED',
                            'created_at'          => Carbon::now()->toDateTimeString(),
                            'updated_at'          => Carbon::now()->toDateTimeString(),
                            'device_type'         => $oneagent->device_type,
                            'device_token'        => $oneagent->device_token,
                            'detail_id'           => rand(11111111, 99999999),
                            'title'               => 'Pickup Request Cancelled',
                            'body'                => 'Check All Details For This Request In App',
                        ];
                        $this->sendnotification($notificationdata, $client_preferences);
                    }

                    DB::connection($respnse['database'])->commit();

                    // Lead-fee refund must run AFTER commit: (1) Bavix wallet uses database.default /
                    // wallet.database.connection — if still "mysql", deposits go to the wrong DB while
                    // withdraw lookups use the tenant connection. (2) Avoid nesting wallet ops inside
                    // the cancel transaction.
                    if (!empty($order->driver_id)) {
                        $this->refundDriverLeadFeeOnOrderCancel($database_name, $order);
                    }
                    return response()->json([
                        'status' => 'Success',
                        'message' => 'Order cancelled successfully',
                        // 'order'  => $order
                    ], 200);
                }
                else {
                    DB::connection($respnse['database'])->rollback();
                    return response()->json(['status' => 'Error', 'message' => 'Invalid Data'], 400);
                }
            }
            catch(\Exception $ex){
                DB::connection($respnse['database'])->rollback();
                return response()->json(['status' => 'Error', 'message' => 'Server Error'], 400);
            }
        }else {
            return response()->json(['status' => 'Error', 'message' => 'Connection Error'], 400);
        }
    }

    public function OrderFormAttribute($domain = '', $user, $id)
    {
        $respnse = $this->connection($user);

        if ($respnse['status'] == 'connected') {
            $order   = DB::connection($respnse['database'])->table('orders')->where('orders.unique_id', $id)->leftJoin('agents', 'orders.driver_id', '=', 'agents.id')
                ->select('orders.*', 'agents.name', 'agents.profile_picture', 'agents.phone_number')->first();
            if (isset($order->id)) {
                $tasks = DB::connection($respnse['database'])->table('tasks')->where('order_id', $order->id)->leftJoin('locations', 'tasks.location_id', '=', 'locations.id')
                    ->select('tasks.*', 'locations.latitude', 'locations.longitude', 'locations.short_name', 'locations.address')->orderBy('task_order')->get();
                $orderc = DB::connection($respnse['database'])->table('orders')->where('id', $order->id)->where('status','completed')->count();
                
                $formData = OrderFormAttribute::with(['attributeOption', 'attribute'])->where('order_id', $order->id)->get();
            //    dd($formData);

                return view('tracking/form-attribute', compact('tasks', 'order', 'formData'));
            } else {
                return view('tracking/order_not_found');
            }
        } else {
            return view('tracking/order_not_found');
        }
    }

    public function OrderRatingform(Request $request,$domain = '', $user, $id)
    {
        $respnse = $this->connection($user);
        $data['attribute'] = [];
        $data['ratingType'] = [];
        if ($respnse['status'] == 'connected') {
            $order   = DB::connection($respnse['database'])->table('orders')->where('orders.unique_id', $id)->leftJoin('agents', 'orders.driver_id', '=', 'agents.id')
                ->select('orders.*', 'agents.name', 'agents.profile_picture', 'agents.phone_number')->first();
            if (isset($order->id)) {
                
                    $attribute = $this->getRatingAttributeForm($request,'',$order->id);
                  
                    $ratingType = $this->getRatingType($request, $order->id);
                   
                    $data['attribute'] =  $attribute;
                    $data['ratingType'] =  $ratingType;
            } 
        } 
        return response()->json(['status' => 'success','data'=> json_encode($data)]);
    }
    public function OrderRatingSubmit(Request $request,$domain = '', $user, $id)
    {
        $respnse = $this->connection($user);
     
        if ($respnse['status'] == 'connected') {
            $order = DB::connection($respnse['database'])->table('orders')->where('unique_id', $id)->first();            

            if (isset($order->id)) {
                if(isset($request->Rating_types)){

                    foreach($request->Rating_types as $Rating_type) {
                       
                        $check_alredy  = DB::connection($respnse['database'])->table('driver_ratings')->where('order_id', $order->id)->where('rating_type_id',$Rating_type['rating_type_id'])->first();
                     
                        $data = [
                            'order_id'    => $order->id,
                            'driver_id'   => $order->driver_id,
                            'rating'      => $Rating_type['rating'] ?? 0,
                            'review'      => $request->review,
                            'rating_type_id' => $Rating_type['rating_type_id'],
                        ];
                        if (isset($check_alredy->id)) {
                            $data['updated_at'] = date('Y-m-d H:i:s');
                            DB::connection($respnse['database'])->table('driver_ratings')->where('id',$check_alredy->id)->update($data);
                        } else {
                            $data['created_at'] = date('Y-m-d H:i:s');
                            DB::connection($respnse['database'])->table('driver_ratings')->insert($data);
                           
                        }
                    }
                }
                if($request->Rating && isset($request->Rating['review']) && !empty($request->Rating['review']) ){
                    $check_alredy  = DB::connection($respnse['database'])->table('driver_ratings')->where('order_id', $order->id)->whereNull('rating_type_id')->first();
               
                    $data = [
                        'order_id'    => $order->id,
                        'driver_id'   => $order->driver_id,
                        'rating'      => 0,
                        'review'      => $request->Rating['review'],
                      
                    ];
                    if (isset($check_alredy->id)) {
                        $data['updated_at'] = date('Y-m-d H:i:s');
                        DB::connection($respnse['database'])->table('driver_ratings')->where('id',$check_alredy->id)->update($data);
                    } else {
                        $data['created_at'] = date('Y-m-d H:i:s');
                        DB::connection($respnse['database'])->table('driver_ratings')->insert($data);
                    }
                }
                if(isset($request->attribute) && !empty($request->attribute)){
                    foreach($request->attribute as $attribute) {
                     
                        $check_questions  = DB::connection($respnse['database'])->table('order_rating_questions')->where('order_id', $order->id)->where('question_id',$attribute['question_id'])->first();
                        $data = [
                            'order_id'    => $order->id,
                            'question_id' => $attribute['question_id'],
                            'option_id'   => $attribute['option_id']
                        ];
                        if (isset($check_questions->id)) {
                            $data['updated_at'] = date('Y-m-d H:i:s');
                            DB::connection($respnse['database'])->table('order_rating_questions')->where('id',$check_questions->id)->update($data);
                        } else {
                            $data['created_at'] = date('Y-m-d H:i:s');
                            DB::connection($respnse['database'])->table('order_rating_questions')->insert($data);
                        }
                    }
                }
              
                return response()->json(['status' => true, 'message' => __('Your rating is submitted')]);
            } else {
                return response()->json(['status' => true, 'message' => __('Order Not Found')]);
            }
        } else {
            return response()->json([
                'message' => 'Error'], 400);
            
        }
    }
    public function OrderInvoice(Request $request,$domain = '', $order_id)
    {
     
        // $short_code = $request->short_code ?? '24id662f266bcd09c8';
        $order = Order::with('additionData','agent','task')->where('id', $order_id)->first();
      
        if(!$order){
            return 0; 
        }
        $Client = Client::where('is_superadmin', 1)->first();
   
        $date =  Carbon::parse($order->created_at)->format('m-d-Y');
        if(isset($Client->logo)){
            $urlImg = Storage::disk('s3')->url($Client->logo);
        }
        $imgproxyurl = 'https://imgproxy.royodispatch.com/insecure/fit/300/100/sm/0/plain/';
        $logoimage = $imgproxyurl.$urlImg;
      // pr( $logoimage );
      $sheredArray = ['order' => $order,'client'=>$Client ,'NowDate'=> $date,'logoimage'=>$logoimage];
       // if($request->html == 1){
        //return view('Invoice.orderInvoicePdf')->with($sheredArray);
         //   return view('Invoice.orderInvoicePdf')->with(['payment' => $paymentLink,'NowDate'=> $date]);
        //}
         view()->share( $sheredArray);  
         $pdf = PDF::loadView('Invoice.orderInvoicePdf');
         $pdfName = ($order->order_number ??$order->id)."_invoice.pdf";
         return $pdf->download($pdfName);
    }

    /**
     * When an assigned order is cancelled from the order panel, refund the lead fee
     * previously withdrawn on driver accept ({@see \App\Http\Controllers\Api\TaskController::deductWalletCommissionOnOrderAccept}).
     */
    protected function refundDriverLeadFeeOnOrderCancel(string $dbConnection, object $order): void
    {
        $orderId = (int) ($order->id ?? 0);

        if (empty($order->driver_id)) {
            $this->logLeadFeeCancelRefund($dbConnection, $orderId, null, 'skip: no driver_id on order', [], 'info');

            return;
        }
        // Explicit 1/0 only — avoid empty() quirks on string "0" / int 0 from DB.
        if ((int) ($order->skip_accept_lead_fee ?? 0) === 1) {
            $this->logLeadFeeCancelRefund($dbConnection, $orderId, (int) $order->driver_id, 'skip: skip_accept_lead_fee=1 (no lead fee charged)', [], 'info');

            return;
        }

        $driverId = (int) $order->driver_id;

        // Bavix wallet resolves DB via config('wallet.database.connection') or database.default.
        // Tracking routes often leave default as "mysql" while tenant data is on $dbConnection,
        // so depositFloat would write to the wrong database. Point default at the tenant for this refund.
        $previousDefault = config('database.default');
        config(['database.default' => $dbConnection]);
        if (app()->bound(DatabaseServiceInterface::class)) {
            app()->forgetInstance(DatabaseServiceInterface::class);
        }

        try {
            if ($this->walletRefundAlreadyPosted($dbConnection, $driverId, $orderId)) {
                $this->logLeadFeeCancelRefund($dbConnection, $orderId, $driverId, 'skip: refund deposit already exists for this order (idempotent)', [], 'info');

                return;
            }

            $refundAmount = null;

            $singleWithdraw = $this->findLeadFeeWithdrawForOrder($dbConnection, $driverId, $orderId, 'order_accept_lead_fee');

            if ($singleWithdraw) {
                $meta = is_array($singleWithdraw->meta) ? $singleWithdraw->meta : [];
                if (isset($meta['lead_fee']) && (float) $meta['lead_fee'] > 0) {
                    $refundAmount = round((float) $meta['lead_fee'], 2);
                } else {
                    $hint = $this->resolveOrderPayableHintFromOrderRow($order);
                    $refundAmount = OrderPayableSplit::leadFeeOnAccept($hint > 0 ? $hint : null);
                }
                $this->logLeadFeeCancelRefund($dbConnection, $orderId, $driverId, 'resolved refund from order_accept_lead_fee withdraw', [
                    'withdraw_transaction_id' => $singleWithdraw->id ?? null,
                    'refund_amount' => $refundAmount,
                ], 'info');
            }

            if ($refundAmount === null || $refundAmount <= 0) {
                $batchWithdraws = WalletTransaction::on($dbConnection)
                    ->whereIn('payable_type', $this->agentPayableTypes())
                    ->where('payable_id', $driverId)
                    ->where('type', 'withdraw')
                    ->where('meta->type', 'batch_order_accept_lead_fee')
                    ->orderByDesc('id')
                    ->get();

                foreach ($batchWithdraws as $tw) {
                    $meta = is_array($tw->meta) ? $tw->meta : [];
                    $rawIds = $meta['order_ids'] ?? null;
                    if (is_string($rawIds)) {
                        $oids = json_decode($rawIds, true) ?: [];
                    } elseif (is_array($rawIds)) {
                        $oids = $rawIds;
                    } else {
                        $oids = [];
                    }
                    $oids = array_map('intval', $oids);
                    if (in_array($orderId, $oids, true)) {
                        $hint = $this->resolveOrderPayableHintFromOrderRow($order);
                        $refundAmount = OrderPayableSplit::leadFeeOnAccept($hint > 0 ? $hint : null);
                        $this->logLeadFeeCancelRefund($dbConnection, $orderId, $driverId, 'resolved refund from batch_order_accept_lead_fee withdraw', [
                            'batch_withdraw_transaction_id' => $tw->id ?? null,
                            'refund_amount' => $refundAmount,
                        ], 'info');
                        break;
                    }
                }
            }

            // If DB JSON path queries missed the row (meta.order_id stored as int vs string, etc.), refund the configured lead fee.
            if ($refundAmount === null || $refundAmount <= 0) {
                $hint = $this->resolveOrderPayableHintFromOrderRow($order);
                $refundAmount = OrderPayableSplit::leadFeeOnAccept($hint > 0 ? $hint : null);
                $this->logLeadFeeCancelRefund($dbConnection, $orderId, $driverId, 'resolved refund from fallback leadFeeOnAccept (no matching withdraw row)', [
                    'payable_hint' => $hint,
                    'refund_amount' => $refundAmount,
                ], $refundAmount > 0 ? 'info' : 'warning');
            }

            if ($refundAmount === null || $refundAmount <= 0) {
                $hint = $this->resolveOrderPayableHintFromOrderRow($order);
                $this->logLeadFeeCancelRefund($dbConnection, $orderId, $driverId, 'abort: refund amount is zero — no wallet transaction will be created', [
                    'payable_hint' => $hint,
                    'cash_to_be_collected' => $order->cash_to_be_collected ?? null,
                    'sync_order_id' => $order->sync_order_id ?? null,
                    'order_cost' => $order->order_cost ?? null,
                ], 'warning');

                return;
            }

            $agent = Agent::on($dbConnection)->find($driverId);
            if (!$agent) {
                $this->logLeadFeeCancelRefund($dbConnection, $orderId, $driverId, 'abort: agent row not found — cannot credit wallet', [
                    'refund_amount' => $refundAmount,
                ], 'warning');

                return;
            }
            $agent->load('wallet');
            if (!$agent->wallet) {
                $this->logLeadFeeCancelRefund($dbConnection, $orderId, $driverId, 'abort: agent has no wallet — cannot deposit refund', [
                    'refund_amount' => $refundAmount,
                    'agent_found' => true,
                ], 'warning');

                return;
            }

            $deposit = $agent->wallet->depositFloat($refundAmount, [
                'type' => 'order_cancel_lead_fee_refund',
                'order_id' => (string) $orderId,
                'description' => __('Refund of lead fee on order cancel'),
                'refunded_lead_fee' => (string) $refundAmount,
            ]);
            $this->logLeadFeeCancelRefund($dbConnection, $orderId, $driverId, 'success: lead fee refund deposited to wallet', [
                'refund_amount' => $refundAmount,
                'wallet_transaction_id' => $deposit->id ?? null,
                'wallet_id' => $deposit->wallet_id ?? ($agent->wallet->id ?? null),
            ], 'info');
        } catch (\Throwable $e) {
            Log::warning('Lead fee refund on order cancel: exception (no wallet transaction)', [
                'event' => 'lead_fee_cancel_refund',
                'db_connection' => $dbConnection,
                'order_id' => $orderId,
                'driver_id' => $driverId,
                'error' => $e->getMessage(),
                'exception_class' => get_class($e),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
        } finally {
            config(['database.default' => $previousDefault]);
            if (app()->bound(DatabaseServiceInterface::class)) {
                app()->forgetInstance(DatabaseServiceInterface::class);
            }
        }
    }

    /**
     * Structured logs for lead-fee refund on order-panel cancel (wallet / transactions table).
     *
     * @param  array<string, mixed>  $context
     */
    protected function logLeadFeeCancelRefund(string $dbConnection, int $orderId, ?int $driverId, string $message, array $context = [], string $level = 'info'): void
    {
        $payload = array_merge([
            'event' => 'lead_fee_cancel_refund',
            'db_connection' => $dbConnection,
            'order_id' => $orderId,
            'driver_id' => $driverId,
        ], $context);

        if ($level === 'warning') {
            Log::warning($message, $payload);
        } else {
            Log::info($message, $payload);
        }
    }

    /**
     * @return array<int, string>
     */
    protected function agentPayableTypes(): array
    {
        return array_values(array_unique([
            Agent::class,
            'App\Model\Agent',
            ltrim(Agent::class, '\\'),
        ]));
    }

    /**
     * Idempotency: prior refund deposit for this order.
     */
    protected function walletRefundAlreadyPosted(string $dbConnection, int $driverId, int $orderId): bool
    {
        $candidates = WalletTransaction::on($dbConnection)
            ->whereIn('payable_type', $this->agentPayableTypes())
            ->where('payable_id', $driverId)
            ->where('type', 'deposit')
            ->where('meta->type', 'order_cancel_lead_fee_refund')
            ->orderByDesc('id')
            ->limit(50)
            ->get();

        foreach ($candidates as $row) {
            $meta = is_array($row->meta) ? $row->meta : [];
            if ($this->metaOrderIdMatches($meta, $orderId)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Find withdraw row where meta.type matches and meta.order_id matches (string or int in JSON).
     */
    protected function findLeadFeeWithdrawForOrder(string $dbConnection, int $driverId, int $orderId, string $metaType): ?WalletTransaction
    {
        $rows = WalletTransaction::on($dbConnection)
            ->whereIn('payable_type', $this->agentPayableTypes())
            ->where('payable_id', $driverId)
            ->where('type', 'withdraw')
            ->where('meta->type', $metaType)
            ->orderByDesc('id')
            ->limit(200)
            ->get();

        foreach ($rows as $row) {
            $meta = is_array($row->meta) ? $row->meta : [];
            if ($this->metaOrderIdMatches($meta, $orderId)) {
                return $row;
            }
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $meta
     */
    protected function metaOrderIdMatches(array $meta, int $orderId): bool
    {
        if (!array_key_exists('order_id', $meta)) {
            return false;
        }
        $v = $meta['order_id'];

        return (string) $v === (string) $orderId || (int) $v === $orderId;
    }

    protected function resolveOrderPayableHintFromOrderRow(object $order): float
    {
        $c = (float) ($order->cash_to_be_collected ?? 0);
        if ($c > 0) {
            return round($c, 2);
        }
        $syncId = (int) ($order->sync_order_id ?? 0);
        if ($syncId > 0) {
            $p = OrderPayableSplit::resolvePanelPayableAmount($syncId);
            if ($p > 0) {
                return $p;
            }
        }

        return round(max(0, (float) ($order->order_cost ?? 0)), 2);
    }
}
