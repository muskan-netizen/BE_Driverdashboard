<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\BaseController;
use Validation;
use Carbon\Carbon;
use App\Model\Geo;
use App\Model\Task;
use App\Model\Order;
use App\Model\Agent;
use App\Model\Client;
use App\Model\Roster;
use App\Model\Timezone;
use App\Model\Customer;
use App\Model\Location;
use App\Model\TaskProof;
use App\Model\DriverGeo;
use App\Model\TaskReject;
use App\Model\SmtpDetail;
use App\Traits\AgentSlotTrait;
use App\Model\BatchAllocation;
use App\Model\AllocationRule;
use App\Model\ClientPreference;
use App\Model\NotificationType;
use App\Traits\agentEarningManager;
use App\Model\BatchAllocationDetail;
use App\Model\{
    PricingRule,
    TagsForAgent,
    AgentPayout,
    TagsForTeam,
    Team,
    PaymentOption,
    PayoutOption,
    AgentConnectedAccount,
    CustomerVerificationResource,
    SubscriptionInvoicesDriver,
    TaskType,
    AgentLogSlab,
    AgentFleet,
    OrderAdditionData,
    UserBidRideRequest,
    OrderFormAttribute,
    Warehouse
};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use PHPUnit\Framework\Constraint\Count;
use Illuminate\Support\Facades\Storage;
use Kawankoding\Fcm\Fcm;
use App\Jobs\RosterCreate;
use App\Jobs\RosterDelete;
use Illuminate\Support\Arr;
use App\Model\NotificationEvent;
use App\Jobs\scheduleNotification;
use App\Traits\GlobalFunction;
use App\Traits\sendCustomNotification;
use App\Traits\FormAttributeTrait;
use App;
use Log;
use Mail;
use Config;
use Closure;
use DB, Session;
use Illuminate\Support\Str;
use GuzzleHttp\Client as GClient;
use App\Http\Requests\GetDeliveryFee;
use Twilio\Rest\Client as TwilioClient;
use App\Http\Requests\CreateTaskRequest;
use App\Http\Controllers\StripeGatewayController;
use App\Traits\TollFee;
use App\Traits\DispatcherRouteAllocation;
use JWT\Token;
use App\Model\Users;
use App\Models\OrderPanel;
use App\Model\OrderPanelDetail;
use App\OrderWaitTimeLog;
// use Illuminate\Support\Facades\Log as FacadesLog;
// use PhpOffice\PhpSpreadsheet\Calculation\MathTrig\Exp;

class TaskController extends BaseController
{
    use AgentSlotTrait;
    use TollFee, DispatcherRouteAllocation;
    use GlobalFunction, FormAttributeTrait;
    use sendCustomNotification;
    public function smstest(Request $request)
    {
        $res = $this->sendSms2($request->phone_number, $request->sms_body);
    }

    public function updateTaskStatus(Request $request)
    {

        $header = $request->header();
        $tasks = null;
        $client_details = Client::where('database_name', $header['client'][0])->first();
        $proof_image = '';
        $proof_face = '';
        $multimedia= $request->multimedia ?? 0;
        $proof_signature = '';
        $note = '';
        if (isset($request->note)) {
            $note = $request->note;
        } else {
            $note = '';
        }
        $is_drop_off_task_completed = $request->is_drop_off_task_completed ?? 0 ;
        $preference = ClientPreference::where('id', 1)->first([
            'is_dispatcher_allocation'
        ]);
        if ($client_details->custom_domain && !empty($client_details->custom_domain)) {
            $client_url = "https://" . $client_details->custom_domain;
        } else {
            $client_url = "https://" . $client_details->sub_domain . \env('SUBDOMAIN');
        }
        // set dynamic smtp for email send
        $this->setMailDetail($client_details);
        $waiting_time = $request->waiting_time ?? 0;
        $orderId = Task::where('id', $request->task_id)->with([
            'tasktype'
        ])->first();




        $user = Auth::user();

        $orderAll = Task::where('order_id', $orderId->order_id)->get();
        $order_details = Order::where('id', $orderId->order_id)->with([
            'agent',
            'customer'
        ])->first();

        if ($order_details->status == '' || $order_details->driver_id != $user->id) :
            return response()->json([
                'data' => [],
                'status' => 403,
                'message' => "You can not complete this order."
            ]);
        endif;

        // dd($order_details->toArray());
        if (isset($request->qr_code) && ($order_details && $order_details->call_back_url)) {
            $qrcode_web_hook = $this->checkQrcodeStatusDataToOrderPanel($order_details, $request->qr_code, '1');
            if ($qrcode_web_hook == '0') {
                return $this->error('Wrong Qr Code.', 400);
            } else {
                $codeVendor = $qrcode_web_hook;
            }
        }


        $allCount = Count($orderAll);
        $inProgress = $orderAll->where('task_status', 2);
        $lasttask = count($orderAll->where('task_status', 4)->where('id', '!=', $request->task_id));
        $check = $allCount - $lasttask;
        $lastfailedtask = count($orderAll->where('task_status', 5));
        $checkfailed = $allCount - $lastfailedtask;
        $sms_body = '';
        $notification_type = NotificationType::with('notification_events.client_notification')->get();

        switch ($orderId->task_type_id) {
            case 1:

                $sms_settings = $notification_type[0];
                break;
            case 2:

                $sms_settings = $notification_type[1];
                break;
            case 3:

                $sms_settings = $notification_type[2];
                break;
        }

        $otpEnabled = 0;
        $otpRequired = 0;

        switch ($request->task_status) {
            case 2:
                $task_type = 'assigned';
                $sms_final_status = $sms_settings['notification_events'][0];
                $sms_body = $sms_settings['notification_events'][0]['message'];
                $link = $client_url . '/order/tracking/' . $client_details->code . '/' . $order_details->unique_id;
                break;
            case 3:
                $task_type = 'assigned';
                $sms_final_status = $sms_settings['notification_events'][1];
                $sms_body = $sms_settings['notification_events'][1]['message'];
                $link = '';

                break;
            case 4:
                $task_type = 'completed';
                $sms_final_status = $sms_settings['notification_events'][2];
                $sms_body = $sms_settings['notification_events'][2]['message'];
                $link = $client_url . '/order/feedback/' . $client_details->code . '/' . $order_details->unique_id;

                $taskProof = TaskProof::all();
                $completionOtp = Order::where('id', $orderId->order_id)->select('completion_otp')->first();
                $errorMsgOtp = '';
                if (!empty($orderId->tasktype->name) && $orderId->tasktype->name == 'Pickup' && $taskProof[0]->otp_requried == 1 && $taskProof[0]->otp == 1) {
                    if (!empty($request->otp) && $completionOtp->completion_otp != $request->otp) {
                        $errorMsgOtp = __('Otp Not Match');
                    } else if (empty($request->otp)) {
                        $errorMsgOtp = __('Otp is requried');
                    }
                } else if (!empty($orderId->tasktype->name) && $orderId->tasktype->name == 'Drop' && $taskProof[1]->otp_requried == 1 && $taskProof[1]->otp == 1) {
                    if (!empty($request->otp) && $completionOtp->completion_otp != $request->otp) {
                        $errorMsgOtp = __('Otp Not Match');
                    } else if (empty($request->otp)) {
                        $errorMsgOtp = __('Otp is requried');
                    }
                } else if (!empty($orderId->tasktype->name) && $orderId->tasktype->name == 'Appointment' && $taskProof[2]->otp_requried == 1 && $taskProof[2]->otp == 1) {
                    if (!empty($request->otp) && $completionOtp->completion_otp != $request->otp) {
                        $errorMsgOtp = __('Otp Not Match');
                    } else if (empty($request->otp)) {
                        $errorMsgOtp = __('Otp is requried');
                    }
                }

                if (isset($request->customer_phone_number)) {
                    $dispatch_traking_url = $client_url . '/order/tracking/' . $client_details->code . '/' . $order_details->unique_id;
                    $this->sendSms2($request->customer_phone_number, $dispatch_traking_url);
                }

                if (!empty($errorMsgOtp)) {
                    return response()->json([
                        'data' => [],
                        'status' => 200,
                        'message' => $errorMsgOtp
                    ]);
                }

                break;
            case 5:
                $task_type = 'failed';
                $sms_final_status = $sms_settings['notification_events'][3];
                $sms_body = $sms_settings['notification_events'][3]['message'];
                $link = '';
                break;

            default:
                $link = '';
        }

        $send_sms_status = isset($sms_final_status['client_notification']['request_recieved_sms']) ? $sms_final_status['client_notification']['request_recieved_sms'] : 0;
        $send_email_status = isset($sms_final_status['client_notification']['request_received_email']) ? $sms_final_status['client_notification']['request_received_email'] : 0;

        // for recipient email and sms
        $send_recipient_sms_status = isset($sms_final_status['client_notification']['recipient_request_recieved_sms']) ? $sms_final_status['client_notification']['recipient_request_recieved_sms'] : 0;
        $send_recipient_email_status = isset($sms_final_status['client_notification']['recipient_request_received_email']) ? $sms_final_status['client_notification']['recipient_request_received_email'] : 0;

        if ($waiting_time) {
            Task::where('id', $request->task_id)->update(['waiting_time' => $waiting_time]);
        }
        if ($request->task_status == 4) {


            if ($check == 1) {
                Order::where('id', $orderId->order_id)->update(['status' => $task_type]);

                //If waiting time came then all price will be updated
                if ($waiting_time) {
                    // Task::where('id',$request->task_id)->update(['waiting_time'=>$waiting_time]);
                    $waitingDetails =  (object)$this->setPricingRuleDynamic($orderId->order_id, $waiting_time);
                }


                if ($order_details && $order_details->call_back_url) {
                    // \Log::info(json_encode($waitingDetails));
                    // \Log::info(' total_waiting_time  - '.$waitingDetails->total_waiting_time??0);
                    // \Log::info(' total_waiting_price - '.$waitingDetails->total_waiting_price??0);
                    if($multimedia!=1){
                    $call_web_hook = $this->updateStatusDataToOrder($order_details, 5, $orderId->task_type_id, $waitingDetails->total_waiting_price ?? 0, $waitingDetails->total_waiting_time ?? 0);  # call web hook when order completed
                    }
                }
                if (isset($request->qr_code)) {
                    $codeVendor = $this->checkQrcodeStatusDataToOrderPanel($order_details, $request->qr_code, 5);
                }
                $orderdata = Order::select('id', 'order_time', 'status', 'driver_id')->with('agent')
                    ->where('id', $order_details->id)
                    ->first();
                // event(new \App\Events\loadDashboardData($orderdata));
            }

            //Send Next Dependent task details
            if(($preference->is_dispatcher_allocation == 1) && ($orderId->tasktype->id == 2))
            {

                    $tasks = Task::where('order_id', $order_details->id)->whereNull('dependent_task_id')->where('task_status', '!=', 4)->Where('task_status', '!=', 5)
                    ->with(['location', 'tasktype', 'order.customer', 'order.customer.resources', 'order.task.location', 'order.additionData'])
                    ->orderBy("id", "ASC")
                    ->first();

            }else{

                $tasks = Task::where('dependent_task_id', $orderId->id)->where('task_status', '!=', 4)->Where('task_status', '!=', 5)
                ->with(['location', 'tasktype', 'order.customer', 'order.customer.resources', 'order.task.location', 'order.additionData','order.waitingTimeLogs'])->orderBy("order_id", "DESC")
                ->orderBy("id", "ASC")
                ->get();


                if (count($tasks) > 0) {
                    // sort according to task_order
                    $tasks = $tasks->toArray();
                    if ($tasks[0]['task_order'] != 0) {
                        usort($tasks, function ($a, $b) {
                            return $a['task_order'] <=> $b['task_order'];
                        });
                    }
                }
            }



            if (@$request->attribute_data) {
                $this->saveOrderFormAttribute($request, $orderId);
            }
        } elseif ($request->task_status == 5) {

            //cancel complete order if driver cancel pickup task
            //if ($checkfailed == 1) {
                $Order  = Order::where('id', $orderId->order_id)->update(['status' => $task_type ]);
                $task = Task::where('order_id', $orderId->order_id)->update(['task_status' => $request->task_status,'note' => $note ]);
                ////
                if(checkColumnExists('orders','rejectable_order')){
                   // if($order_details && isset( $order_details->rejectable_order) && $order_details->rejectable_order ==1){
                        if ($order_details &&  $order_details->call_back_url) {
                            $call_web_hook = $this->updateStatusDataToOrder($order_details, 6,2);  # task rejected
                        }
                   // }
                }
            //}
        } else {
            if(isset($task_type)){
                $Order = Order::where('id', $orderId->order_id)->update([
                    'status' => $task_type,
                    'note' => $note
                ]);
            }

            if ($order_details && $order_details->call_back_url) {
                if ($request->task_status == 2 || $request->task_status == 3)
                    $stat = $request->task_status + 1;
                else
                    $stat = $request->task_status;

                    if($multimedia!=1){
                        $call_web_hook = $this->updateStatusDataToOrder($order_details, $stat, $orderId->task_type_id); # call web hook when order update
                    }

                # call web hook when order update
            }
        }

        if (isset($request->wait_time)) {

            $waiting_time = explode(":",$request->wait_time);

            OrderWaitTimeLog::create([
                'order_id' => $orderId->order_id,
                'wait_time' => $request->wait_time,
                'amount' => $orderId->order->duration_price * $waiting_time[0],
            ]);

            $time = (float)($orderId->order->actual_time ?? 0) + (float)$waiting_time[0] + ((float)$waiting_time[1]/100);
            $timeArray = explode('.',$time);
            if($timeArray[1] >= 60){
                $increaseMin = 1;
                $remainingSec = $timeArray[1] - 60;
                if($remainingSec < 10){
                    $remainingSec = (int)'0'.$remainingSec;
                }
            }else{
                $increaseMin = 0;
                $remainingSec = $timeArray[1] ;
            }

            $updateData = [
                'actual_time'   => (float)(($timeArray[0] + $increaseMin) .'.'.$remainingSec),
                // 'cash_to_be_collected' => ($orderId->order->cash_to_be_collected ?? 0)
            ];

            Order::find($orderId->order_id)->update($updateData);
        }

        if (isset($request->qr_code)) {
            $task = Task::where('id', $request->task_id)->update(['bag_qrcode' => $request->qr_code]);
        }

        if(isset($request->task_status)){
            $task = Task::where('id', $request->task_id)->update([
                'task_status' => $request->task_status,
                'note' => $note
            ]);
        }

        if (isset($request->image)) {
            if ($request->hasFile('image')) {
                $folder = str_pad($client_details->code, 8, '0', STR_PAD_LEFT);
                $folder = 'client_' . $folder;
                $file = $request->file('image');
                $file_name = uniqid() . '.' . $file->getClientOriginalExtension();
                $s3filePath = '/assets/' . $folder . '/orders' . $file_name;
                $path = Storage::disk('s3')->put($s3filePath, $file, 'public');
                $proof_image = $path;

                $task = Task::where('id', $request->task_id)->update([
                    'proof_image' => $proof_image
                ]);
            }
        } else {
            $proof_image = null;
        }

        if (isset($request->proof_face)) {
            if ($request->hasFile('proof_face')) {
                $folder = str_pad($client_details->code, 8, '0', STR_PAD_LEFT);
                $folder = 'client_' . $folder;
                $file = $request->file('proof_face');
                $file_name = uniqid() . '.' . $file->getClientOriginalExtension();
                $s3filePath = '/assets/' . $folder . '/orders' . $file_name;
                $path = Storage::disk('s3')->put($s3filePath, $file, 'public');
                $proof_face = $path;

                $task = Task::where('id', $request->task_id)->update([
                    'proof_face' => $proof_face
                ]);
            }
        } else {
            $proof_face = null;
        }

        if (isset($request->signature)) {
            if ($request->hasFile('signature')) {
                $folder = str_pad($client_details->code, 8, '0', STR_PAD_LEFT);
                $folder = 'client_' . $folder;
                $file = $request->file('signature');
                $file_name = uniqid() . '.' . $file->getClientOriginalExtension();
                $s3filePath = '/assets/' . $folder . '/orders' . $file_name;
                $path = Storage::disk('s3')->put($s3filePath, $file, 'public');
                $proof_signature = $path;

                $task = Task::where('id', $request->task_id)->update([
                    'proof_signature' => $proof_signature
                ]);
            }
        } else {
            $proof_signature = null;
        }
        // dd($request->toArray());

        $newDetails = Task::where('id', $request->task_id)->with(['location', 'tasktype', 'pricing', 'order.customer', 'order.additionData','order.waitingTimeLogs'])->first();

        $sms_body = str_replace('"order_number"', $order_details->unique_id, $sms_body);
        $sms_body = str_replace('"driver_name"', $order_details->agent->name, $sms_body);
        $sms_body = str_replace('"vehicle_model"', $order_details->agent->make_model, $sms_body);
        $sms_body = str_replace('"plate_number"', $order_details->agent->plate_number, $sms_body);
        $sms_body = str_replace('"tracking_link"', $link, $sms_body);
        $sms_body = str_replace('"feedback_url"', $link, $sms_body);

        // twilio sms send keys
        $client_prefrerence = ClientPreference::where('id', 1)->first();

        if ($send_sms_status == 1) {

            try {
                if (isset($order_details->customer->phone_number) && strlen($order_details->customer->phone_number) > 8) {
                    $this->sendSms2($order_details->customer->phone_number, $sms_body);
                }
                if (isset($order_details->type) && $order_details->type == 1 && strlen($order_details->friend_phone_number) > 8) {
                    $this->sendSms2($order_details->friend_phone_number, $sms_body);
                }
            } catch (\Exception $e) {
                Log::info($e->getMessage());
            }
        }

        if ($send_email_status == 1) {
            $sendto = isset($order_details->customer->email) ? $order_details->customer->email : '';
            $client_logo = Storage::disk('s3')->url($client_details->logo);
            $agent_profile = Storage::disk('s3')->url($order_details->agent->profile_picture ?? 'assets/client_00000051/agents605b6deb82d1b.png/XY5GF0B3rXvZlucZMiRQjGBQaWSFhcaIpIM5Jzlv.jpg');

            $mail = SmtpDetail::where('client_id', $client_details->id)->first();

            try {

                $res = \Mail::send('email.verify', [
                    'customer_name' => $order_details->customer->name,
                    'content' => $sms_body,
                    'agent_name' => $order_details->agent->name,
                    'agent_profile' => $agent_profile,
                    'number_plate' => $order_details->agent->plate_number,
                    'client_logo' => $client_logo,
                    'link' => $link
                ], function ($message) use ($sendto, $client_details, $mail) {
                    $message->from($mail->from_address, $client_details->name);
                    $message->to($sendto)->subject(__('Order Update | ') . $client_details->company_name);
                });
            } catch (\Exception $e) {
                Log::info($e->getMessage());
            }
        }
        $recipient_phone = isset($newDetails->location->phone_number) ? $newDetails->location->phone_number : '';
        $recipient_email = isset($newDetails->location->email) ? $newDetails->location->email : '';

        if ($send_recipient_sms_status == 1 && $recipient_phone != '') {
            try {
                if (isset($recipient_phone) && strlen($recipient_phone) > 8) {
                    $this->sendSms2($recipient_phone, $sms_body);
                }
            } catch (\Exception $e) {
                Log::info($e->getMessage());
            }
        }

        if ($send_recipient_email_status == 1 && $recipient_email != '') {
            $sendto = $recipient_email;
            $client_logo = Storage::disk('s3')->url($client_details->logo);
            $agent_profile = Storage::disk('s3')->url($order_details->agent->profile_picture ?? 'assets/client_00000051/agents605b6deb82d1b.png/XY5GF0B3rXvZlucZMiRQjGBQaWSFhcaIpIM5Jzlv.jpg');
            $mail = SmtpDetail::where('client_id', $client_details->id)->first();
            try {
                \Mail::send('email.verify', [
                    'customer_name' => $order_details->customer->name,
                    'content' => $sms_body,
                    'agent_name' => $order_details->agent->name,
                    'agent_profile' => $agent_profile,
                    'number_plate' => $order_details->agent->plate_number,
                    'client_logo' => $client_logo,
                    'link' => $link
                ], function ($message) use ($sendto, $client_details, $mail) {
                    $message->from($mail->from_address, $client_details->name);
                    $message->to($sendto)->subject(__('Order Update | ') . $client_details->company_name);
                });
            } catch (\Exception $e) {
                Log::info($e->getMessage());
            }
        }
        // -------------------------------------------code done by Surendra Singh--------------------------//
        $order_details_new = Order::where('id', $orderId->order_id)->where('status', '=', 'completed')
            ->where('is_comm_settled', '=', 0)
            ->with([
                'agent',
                'customer',
                'task'
            ])
            ->whereDoesntHave('task', function ($query) {
                $query->where('task_status', '!=', 4);
            })
            ->first();

        $agent_default_active_payment = AgentConnectedAccount::where('is_primary', 1)->where('agent_id', $user->id)
            ->where('status', 1)
            ->whereHas('payoutOption', function ($q) {
                $q->where('status', 1)
                    ->where('title', '!=', 'Off the Platform');
            })
            ->first();

        if (!empty($order_details_new) && $client_prefrerence->auto_payout == 1 && !empty($agent_default_active_payment)) :
            Order::where('id', $order_details_new->id)->update([
                'is_comm_settled' => 1
            ]);
            $payout_option_id = $agent_default_active_payment->payment_option_id;

            $amount_deduction = 0;
            if (isset($client_prefrerence->charge_percent_from_agent)) {
                $commission_deduct_percentage = $client_prefrerence->charge_percent_from_agent;
                $amount_deduction = $order_details_new->driver_cost * ($commission_deduct_percentage / 100);
            }

            $objetoRequest = new \Illuminate\Http\Request();
            $objetoRequest->setMethod('POST');
            $objetoRequest->request->add([
                'amount' => ($order_details_new->driver_cost != NULL) ? ($order_details_new->driver_cost - $amount_deduction) : 0,
                'agent_id' => $order_details_new->driver_id
            ]);

            if ($payout_option_id == 2) :
                $stripeController = new StripeGatewayController();
                $response = $stripeController->AgentPayoutViaStripe($objetoRequest)->getData();
                if ($response->status == 'Success') :
                    $payoutdata = [];
                    $payoutdata['agent_id'] = $user->id;
                    $payoutdata['payout_option_id'] = $payout_option_id;
                    $payoutdata['transaction_id'] = $response->data;
                    $payoutdata['amount'] = ($order_details_new->driver_cost != NULL) ? $order_details_new->driver_cost : 0;
                    $payoutdata['requested_by'] = $user->id;
                    $payoutdata['status'] = 1;
                    $payoutdata['currency'] = $client_prefrerence->currency_id;
                    $payoutdata['order_id'] = $order_details_new->id;
                    $payoutdata['created_at'] = date('Y-m-d H:i:s', time());
                    $payoutdata['updated_at'] = date('Y-m-d H:i:s', time());
                    $agentpauoutid = AgentPayout::insertGetId($payoutdata);
                    if ($agentpauoutid) :
                        Order::where('id', $order_details_new->id)->update([
                            'is_comm_settled' => 2
                        ]);
                    endif;
                endif;
            endif;
        endif;



        // ------------------------------------------------------------------------------------------------//
        $newDetails['otpEnabled'] = $otpEnabled;
        $newDetails['otpRequired'] = $otpRequired;
        $newDetails['qrCodeVendor'] = $codeVendor ?? null;

        if(is_object($tasks))
        {
            $newDetails['nextTask'] =  null;

        }else{
            $newDetails['nextTask'] = $tasks ?? null;

        }

         if($preference->is_dispatcher_allocation == 1 && (is_object($tasks)))
         {
            if(!empty($tasks)&& ($request->task_status == 4) &&($tasks->task_type_id == 1) )
            {

                 if(isset($request->is_drop_off_task_completed) && ($request->is_drop_off_task_completed == 1 ))
                 {
                  $geo = null;
                  $geo = $this->createRoster($tasks->location_id);
                  $agent_id = null;
                  $notification_time = Carbon::now()->toDateTimeString();
                  $customer = Customer::find($order_details->customer_id);

                  $finalLocation = Location::where('id', $tasks->location_id)->first();
                  $allocation = AllocationRule::where('id', 1)->first();
                  $is_one_push_booking = isset($order_details->is_one_push_booking) ? $order_details->is_one_push_booking : 0;
                 if(!empty($finalLocation))
                 {
                     $this->SendToAll($geo, $notification_time, $agent_id, $order_details->id, $customer, $finalLocation, 1, $header, $allocation,  $order_details->is_cab_pooling, null, 1, $is_one_push_booking);
                 }
                }

            }
        }
        return response()->json([
            'data' => $newDetails,
            'status' => 200,
            'message' => __('success')
        ]);
    }

    public function checkOTPRequried(Request $request)
    {
        $header = $request->header();
        $client_details = Client::where('database_name', $header['client'][0])->first();
        $otpEnabled     = 0;
        $otpRequired    = 0;
        $orderId        = Task::where('id', $request->task_id)->with(['tasktype'])->first();
        $orderAll       = Task::where('order_id', $orderId->order_id)->get();
        $order_details  = Order::where('id', $orderId->order_id)->with(['agent', 'customer'])->first();
        $otpCreate      = ''; //substr(str_shuffle("0123456789abcdefghijklmnopqrstvwxyz"), 0, 5);
        $taskProof      = TaskProof::all();

        if (!empty($orderId->tasktype->name) && $orderId->tasktype->name == 'Pickup' &&  $taskProof[0]->otp == 1) {
            $otpCreate = rand(10000, 99999);
            Order::where('id', $orderId->order_id)->update(['completion_otp' => $otpCreate]);
            $otpEnabled = 1;
            if ($taskProof[0]->otp_requried == 1) {
                $otpRequired = 1;
            }
        } else if (!empty($orderId->tasktype->name) && $orderId->tasktype->name == 'Drop' && $taskProof[1]->otp == 1) {
            $otpCreate = rand(10000, 99999);
            Order::where('id', $orderId->order_id)->update([
                'completion_otp' => $otpCreate
            ]);
            $otpEnabled = 1;
            if ($taskProof[1]->otp_requried == 1) {
                $otpRequired = 1;
            }
        } else if (!empty($orderId->tasktype->name) && $orderId->tasktype->name == 'Appointment' && $taskProof[2]->otp == 1) {
            $otpCreate = rand(10000, 99999);
            Order::where('id', $orderId->order_id)->update([
                'completion_otp' => $otpCreate
            ]);
            $otpEnabled = 1;
            if ($taskProof[2]->otp_requried == 1) {
                $otpRequired = 1;
            }
        }

        if (!empty($otpCreate) && !empty($otpEnabled)) {
            $client_prefrerence = ClientPreference::where('id', 1)->first();
            $token = $client_prefrerence->sms_provider_key_2;
            $twilio_sid = $client_prefrerence->sms_provider_key_1;
            $smsProviderNumber = $client_prefrerence->sms_provider_number;
            $customerPhoneNumber = $order_details->customer->phone_number;
            $customerEmail = $order_details->customer->email;
            // $sms_body = 'Your otp is '.$otpCreate;

            $notification_type = NotificationType::where('name', 'Customer Delivery OTP')->with('notification_events.client_notification')->first();
            if (isset($notification_type['notification_events']) && !empty($notification_type['notification_events'])) {
                $sms_body = $notification_type['notification_events'][0]['message'];
            } else {
                $sms_body = '';
            }

            $sms_body = str_replace('"order_number"', $order_details->unique_id, $sms_body);
            $sms_body = str_replace('"deliver_otp"', $otpCreate, $sms_body);

            // set dynamic smtp for email send
            $this->setMailDetail($client_details);

            try {

                // **Send OTP to customer phone text msg */
                if (!empty($smsProviderNumber)) {
                    if (!empty($customerPhoneNumber) && strlen($order_details->customer->phone_number) > 8) {
                        $this->sendSms2($order_details->customer->phone_number, $sms_body);
                    }
                    if (isset($order_details->type) && $order_details->type == 1 && $order_details->friend_phone_number > 8) {
                        $this->sendSms2($order_details->friend_phone_number, $sms_body);
                    }

                    // $twilio = new TwilioClient($twilio_sid, $token);

                    // $message = $twilio->messages
                    // ->create(
                    // $order_details->customer->phone_number, //to number
                    // [
                    // "body" => $sms_body,
                    // "from" => $smsProviderNumber //form_number
                    // ]
                    // );
                }

                $mail = SmtpDetail::where('client_id', $client_details->id)->first();
                $client_logo = Storage::disk('s3')->url($client_details->logo);

                // **Send OTP to customer email */
                if (!empty($customerEmail) && !empty($mail)) {
                    $sendto = $customerEmail;
                    $emailData = [
                        'customer_name' => $order_details->customer->name,
                        'content' => $sms_body,
                        'client_logo' => $client_logo,
                        'agent_profile' => '',
                        'agent_name' => '',
                        'number_plate' => '',
                        'link' => ''
                    ];

                    \Mail::send('email.verify', [
                        'customer_name' => $order_details->customer->name,
                        'content' => $sms_body,
                        'agent_name' => $order_details->agent->name,
                        'agent_profile' => '',
                        'number_plate' => $order_details->agent->plate_number,
                        'client_logo' => $client_logo,
                        'link' => ''
                    ], function ($message) use ($sendto, $client_details, $mail) {
                        $message->from($mail->from_address, $client_details->name);
                        $message->to($sendto)->subject(__('Order Update | ') . $client_details->company_name);
                    });
                }

                $newTaskDetails = Task::where('id', $request->task_id)->with([
                    'location'
                ])->first();
                $recipient_phone = isset($newTaskDetails->location->phone_number) ? $newTaskDetails->location->phone_number : '';
                $recipient_email = isset($newTaskDetails->location->email) ? $newTaskDetails->location->email : '';

                // **Send OTP to recipient phone text msg */
                if (!empty($smsProviderNumber) && !empty($recipient_phone) && strlen($recipient_phone) > 8) {
                    $this->sendSms2($recipient_phone, $sms_body);
                    // $twilio = new TwilioClient($twilio_sid, $token);
                    // $message = $twilio->messages
                    // ->create(
                    // $recipient_phone, //to number
                    // [
                    // "body" => $sms_body,
                    // "from" => $smsProviderNumber //form_number
                    // ]
                    // );
                }

                // **Send OTP to recipient email */
                if (!empty($recipient_email)) {
                    $sendto = $recipient_email;
                    \Mail::send('email.verify', [
                        'customer_name' => $order_details->customer->name,
                        'content' => $sms_body,
                        'agent_name' => $order_details->agent->name,
                        'agent_profile' => '',
                        'number_plate' => $order_details->agent->plate_number,
                        'client_logo' => $client_logo,
                        'link' => ''
                    ], function ($message) use ($sendto, $client_details, $mail) {
                        $message->from($mail->from_address, $client_details->name);
                        $message->to($sendto)->subject(__('Order Update | ') . $client_details->company_name);
                    });
                }
            } catch (\Exception $e) {
                Log::info($e->getMessage());
            }
        }

        $newDetails['otp'] = $otpCreate;
        $newDetails['otpEnabled'] = $otpEnabled;
        $newDetails['otpRequired'] = $otpRequired;

        return response()->json([
            'data' => $newDetails,
            'status' => 200,
            'message' => __('success')
        ]);
    }

    /////////////////// **********************   update status in order panel also **********************************  ///////////////////////
    public function updateStatusDataToOrder($order_details, $dispatcher_status_option_id, $task_type, $total_waiting_price = 0, $total_waiting_time = 0)
    {


        try {
            $auth = Client::with([
                'getAllocation',
                'getPreference'
            ])->first();

            if ($auth->custom_domain && !empty($auth->custom_domain)) {
                $client_url = "https://" . $auth->custom_domain;
            } else {
                $client_url = "https://" . $auth->sub_domain . \env('SUBDOMAIN');
            }

            $dispatch_traking_url = $client_url . '/order/tracking/' . $auth->code . '/' . $order_details->unique_id;
            $client = new GClient([
                'content-type' => 'application/json',
                'User-Agent: Mozilla/5.0'
            ]);
            $url = $order_details->call_back_url;
            $res = $client->get($url . '?dispatcher_status_option_id=' . $dispatcher_status_option_id . '&dispatch_traking_url=' . $dispatch_traking_url . '&task_type=' . $task_type);
            $response = json_decode($res->getBody(), true);
            if ($response) {
            }
            return $dispatch_traking_url;
        } catch (\Exception $e) {
            \Log::info($e->getMessage());
            return response()->json([
                'status' => __('error'),
                'message' => $e->getMessage()
            ]);
        }
    }

    // ///////////////// ********************** check qrcode exist in order panel ********************************** ///////////////////////
    public function checkQrcodeStatusDataToOrderPanel($order_details, $orderQrcode, $checkQr = '0')
    {
        try {
            $order_details = Order::where([
                'order_number' => $order_details->order_number,
                'request_type' => 'D'
            ])->with([
                'agent',
                'customer'
            ])->first();
            $auth = Client::with([
                'getAllocation',
                'getPreference'
            ])->first();
            if ($auth->custom_domain && !empty($auth->custom_domain)) {
                $client_url = "https://" . $auth->custom_domain;
            } else {
                $client_url = "https://" . $auth->sub_domain . \env('SUBDOMAIN');
            }
            $dispatch_traking_url = $client_url . '/order/tracking/' . $auth->code . '/' . $order_details->unique_id;

            $client = new GClient([
                'content-type' => 'application/json'
            ]);
            $url = $order_details->call_back_url;
            $res = $client->get($url . '?dispatcher_status_option_id=5&qr_code=' . $orderQrcode . '&order_number=' . $order_details->order_number . '&check_qr=' . $checkQr . '&dispatch_traking_url=' . $dispatch_traking_url);
            $response = json_decode($res->getBody(), true);
            if ($response['status'] == '0') {
                return 0;
            }
            return $response['data']['vendor_detail'] ?? 1;
        } catch (\Exception $e) {
            return response()->json([
                'status' => __('error'),
                'message' => $e->getMessage()
            ]);
        }
    }

    public function setMailDetail($client)
    {
        $mail = SmtpDetail::where('client_id', $client->id)->first();
        if (isset($mail)) {
            $config = array(
                'driver' => $mail->driver,
                'host' => $mail->host,
                'port' => $mail->port,
                'encryption' => $mail->encryption,
                'username' => $mail->username,
                'password' => $mail->password,
                'sendmail' => '/usr/sbin/sendmail -bs',
                'pretend' => false
            );

            Config::set('mail', $config);

            $app = App::getInstance();
            $app->register('Illuminate\Mail\MailServiceProvider');
        }

        return;
    }

    public function TaskUpdateReject(Request $request)
    {

        $header = $request->header();
        $client_details = Client::where('database_name', $header['client'][0])->first();
        $preference = ClientPreference::where('id', 1)->first([
            'is_dispatcher_allocation',
        ]);
        $percentage = 0;
        $agent_id =  $request->driver_id  ? $request->driver_id : null;
        $driver   = Agent::where('id', $agent_id)->first();
        $call_web_hook = '';
        $orderdata = Order::where('id', $request->order_id)->first();
        if($orderdata->status == 'failed' || $orderdata->status == 'cancelled'){
            return response()->json([
            'message' => __('This task has already been cancelled by user.'),
            'status'=>$orderdata->status
            ], 400);
        }


        if (@$driver && $driver->is_pooling_available == 1) {
            $assigned_orders  = Order::where('driver_id', $driver->id)->where('is_cab_pooling', 1)->where('status', 'assigned')->orderBy('id', 'asc')->first();
            $available_seats  = (!empty($assigned_orders)) ? $assigned_orders->available_seats : 0;
            if ($available_seats > 0) {
                $previous_seats   = Order::where('driver_id', $driver->id)->where('is_cab_pooling', 1)->where('status', 'assigned')->sum('no_seats_for_pooling');
                $this_order_seats = $orderdata->no_seats_for_pooling;
                $booked_seats     = $previous_seats + $this_order_seats;
                if ($available_seats < $booked_seats) {
                    return response()->json([
                        'message' => __('Available Seats are less than no of booked seats.'),
                    ], 404);
                }
            }
        }

        $assignedorder_data = Order::where('id', $request->order_id)->where('driver_id', '!=', $agent_id)->where('status', 'assigned')->first();
        $unassignedorder_data = Order::where('id', $request->order_id)->where('status', 'unassigned')->first();
        if (empty($unassignedorder_data) && !empty($assignedorder_data)) {
            return response()->json([
                'message' => __('This task has already been accepted.'),
            ], 404);
        }

        $proof_face = null;
        if (isset($request->proof_face)) {
            if ($request->hasFile('proof_face')) {
                $folder = str_pad($client_details->code, 8, '0', STR_PAD_LEFT);
                $folder = 'client_' . $folder;
                $file = $request->file('proof_face');
                $file_name = uniqid() . '.' .  $file->getClientOriginalExtension();
                $s3filePath = '/assets/' . $folder . '/orders' . $file_name;
                $path = Storage::disk('s3')->put($s3filePath, $file, 'public');
                $proof_face = $path;
            }
        }


        if (isset($orderdata) && $orderdata->driver_id != null) {
            if ($orderdata && $orderdata->call_back_url) {
                $call_web_hook = $this->updateStatusDataToOrder($orderdata, 2,1);  # task accepted
            }
            //Send SMS in case of friend's booking
            if(isset($orderdata->type) && $orderdata->type == 1 && strlen($orderdata->friend_phone_number) > 8)
            {
                $keyData = [
                    '{user-name}' => $orderdata->friend_name,
                    '{customer-name}' => $orderdata->customer->name ?? 'Our customer',
                    '{agent-name}' => $driver->name ?? '',
                    '{car-model}' => $driver->make_model ?? '',
                    '{plate-no}' => $driver->plate_number ?? '',
                    '{track-url}' => $call_web_hook??''
                ];
                $friend_sms_body = sendSmsTemplate('friend-sms', $keyData);
                $this->sendSmsNew($orderdata->friend_phone_number, $friend_sms_body);
            }
            return response()->json([
                'message' => __('Task Accecpted Successfully'),
            ], 200);
        }  // need to we change

        if ($request->status == 1) {

            if ($request->type == 'B') {
                //For Batch Order api
                $check = BatchAllocation::where('batch_no', $request->order_id)->first();
                if ($check->agent_id) {
                    return response()->json([
                        'message' => __('This Batch has already been accepted.'),
                    ], 404);
                }

                $batchNo = $request->order_id;
                $this->dispatchNow(new RosterDelete($request->order_id, 'B'));


                BatchAllocation::where(['batch_no' => $request->order_id])->update(['agent_id' => $agent_id]);
                BatchAllocationDetail::where(['batch_no' => $request->order_id])->update(['agent_id' => $agent_id]);
                $batchs = BatchAllocationDetail::where(['batch_no' => $request->order_id])->get();
                foreach ($batchs as $batch) {

                    $task_id = Order::where('id', $batch->order_id)->first();
                    $pricingRule = PricingRule::where('id', 1)->first();
                    // $agent_id =  $request->driver_id  ? $request->driver_id : null;
                    $agent_commission_fixed = $pricingRule->agent_commission_fixed;
                    $agent_commission_percentage = $pricingRule->agent_commission_percentage;
                    $freelancer_commission_fixed = $pricingRule->freelancer_commission_fixed;
                    $freelancer_commission_percentage = $pricingRule->freelancer_commission_percentage;

                    if ($task_id->driver_cost <= 0.00) {
                        $agent_details = Agent::where('id', $agent_id)->first();
                        if ($agent_details->type == 'Employee') {
                            $percentage = $agent_commission_fixed + (($task_id->order_cost / 100) * $agent_commission_percentage);
                        } else {
                            $percentage = $freelancer_commission_fixed + (($task_id->order_cost / 100) * $freelancer_commission_percentage);
                        }
                    } else {
                        $percentage = $task_id->driver_cost;
                    }

                    if ($agent_id) {
                        $now = Carbon::now()->toDateString();
                        $driver_subscription = SubscriptionInvoicesDriver::where('driver_id', $agent_id)->where('end_date', '>', $now)->orderBy('end_date', 'desc')->first();
                        if ($driver_subscription && ($driver_subscription->driver_type == $agent_details->type)) {
                            if ($driver_subscription->driver_type == 'Employee') {
                                $agent_commission_fixed = $driver_subscription->driver_commission_fixed;
                                $agent_commission_percentage = $driver_subscription->driver_commission_percentage;
                                $freelancer_commission_fixed = null;
                                $freelancer_commission_percentage = null;
                            } else {
                                $agent_commission_fixed = null;
                                $agent_commission_percentage = null;
                                $freelancer_commission_fixed = $driver_subscription->driver_commission_fixed;
                                $freelancer_commission_percentage = $driver_subscription->driver_commission_percentage;
                            }
                            $percentage = $driver_subscription->driver_commission_fixed + (($task_id->order_cost / 100) * $driver_subscription->driver_commission_percentage);
                        }
                    }
                    $agent_fleet = AgentFleet::where('agent_id', $agent_id)->value('fleet_id');
                    Order::where('id', $batch->order_id)->update([
                        'driver_id' => $agent_id,
                        'status' => 'assigned',
                        'fleet_id' => $agent_fleet,
                        'driver_cost' => $percentage,
                        'agent_commission_fixed' => $agent_commission_fixed,
                        'agent_commission_percentage' => $agent_commission_percentage,
                        'freelancer_commission_fixed' => $freelancer_commission_fixed,
                        'freelancer_commission_percentage' => $freelancer_commission_percentage
                    ]);


                        Task::where('order_id', $batch->order_id)->update(['task_status' => 1]);
                        $orderdata = Order::select('id', 'order_time', 'status', 'driver_id')->with('agent')->where('id', $batch->order_id)->first();


                    // event(new \App\Events\loadDashboardData($orderdata));
                }
                if ($check && $check->call_back_url) {
                    $call_web_hook = $this->updateStatusDataToOrder($check, 2, 1);  # task accepted
                }
            } else {
                $check = Order::where('id', $request->order_id)->with(['agent', 'customer'])->first();
                if (!isset($check)) {
                    return response()->json([
                        'message' => __('This order has already been accepted.'),
                    ], 404);
                }
                $order = Order::find($request->order_id);
                if($order->is_cab_pooling == 2){
                    $type = 'PD';
                }else{
                    $type = 'O';
                }
                //For order api
                $this->dispatchNow(new RosterDelete($request->order_id,$type));
                $task_id = Order::where('id', $request->order_id)->first();
                $pricingRule = PricingRule::where('id', 1)->first();
                $agent_commission_fixed = $pricingRule->agent_commission_fixed;
                $agent_commission_percentage = $pricingRule->agent_commission_percentage;
                $freelancer_commission_fixed = $pricingRule->freelancer_commission_fixed;
                $freelancer_commission_percentage = $pricingRule->freelancer_commission_percentage;
                // $agent_id =  isset($request->allocation_type) && $request->allocation_type == 'm' ? $request->driver_id : null;

                if ($task_id->driver_cost <= 0.00) {
                    $agent_details = Agent::where('id', $agent_id)->first();
                    if ($agent_details->type == 'Employee') {
                        $percentage = $agent_commission_fixed + (($task_id->order_cost / 100) * $agent_commission_percentage);
                    } else {
                        $percentage = $freelancer_commission_fixed + (($task_id->order_cost / 100) * $freelancer_commission_percentage);
                    }
                } else {
                    $percentage = $task_id->driver_cost;
                }

                if ($agent_id) {
                    $now = Carbon::now()->toDateString();
                    $driver_subscription = SubscriptionInvoicesDriver::where('driver_id', $agent_id)->where('end_date', '>', $now)->orderBy('end_date', 'desc')->first();
                    if ($driver_subscription && ($driver_subscription->driver_type == $agent_details->type)) {
                        if ($driver_subscription->driver_type == 'Employee') {
                            $agent_commission_fixed = $driver_subscription->driver_commission_fixed;
                            $agent_commission_percentage = $driver_subscription->driver_commission_percentage;
                            $freelancer_commission_fixed = null;
                            $freelancer_commission_percentage = null;
                        } else {
                            $agent_commission_fixed = null;
                            $agent_commission_percentage = null;
                            $freelancer_commission_fixed = $driver_subscription->driver_commission_fixed;
                            $freelancer_commission_percentage = $driver_subscription->driver_commission_percentage;
                        }
                        $percentage = $driver_subscription->driver_commission_fixed + (($task_id->order_cost / 100) * $driver_subscription->driver_commission_percentage);
                    }
                }
                $agent_fleet = AgentFleet::where('agent_id', $agent_id)->value('fleet_id');

                Order::where('id', $request->order_id)->update([
                    'driver_id' => $agent_id,
                    'status' => 'assigned',
                    'fleet_id' => $agent_fleet,
                    'driver_cost' => $percentage,
                    'agent_commission_fixed' => $agent_commission_fixed,
                    'agent_commission_percentage' => $agent_commission_percentage,
                    'freelancer_commission_fixed' => $freelancer_commission_fixed,
                    'freelancer_commission_percentage' => $freelancer_commission_percentage
                ]);
                if($order->auto_alloction == 'notify'){
                    $this->newNotifiedDriverSave($order->id,$agent_id);
                }
                if (checkColumnExists('orders', 'rejectable_order')) {

                    if ($orderdata  && $orderdata->rejectable_order == 1) {

                        $data['schedule_time'] = $orderdata->scheduled_date_time != '' ? $orderdata->scheduled_date_time : Carbon::now()->toDateTimeString();
                        $data['service_time'] = '60';
                        $data['order_id'] = $orderdata->id;
                        $data['order_number'] = $orderdata->order_number;
                        $data['booking_type'] = 'new_booking';
                        $data['memo']  = __("Booked for Order number:") . $orderdata->order_number;
                        $data['agent'] = $agent_id;

                        $bookingResponse =  $this->SlotBooking($data);
                    }
                }

                    if($preference->is_dispatcher_allocation == 1)
                    {
                        $task = Task::where(['order_id' => $request->order_id,'task_type_id' => 1,'task_status' => 0])
                        ->first();

                        if ($task) {
                            $task->task_status = 1;
                            $task->driver_id = $agent_id;
                            $task->assigned_time = date('Y-m-d H:i:s');
                            $task->save();

                        $dependent_task = Task::where(['dependent_task_id' => $task->id])->update([
                            'task_status' => 1,
                            'driver_id' => $agent_id,

                        ]);
                    }

                    }else{
                           Task::where('order_id', $request->order_id)->update(['task_status' => 1]);
                    }
                if ($check && $check->call_back_url) {
                    $call_web_hook = $this->updateStatusDataToOrder($check, 2, 1);  # task accepted
                }
            }


            //Send SMS in case of friend's booking
            if(isset($check->type) && $check->type == 1 && strlen($check->friend_phone_number) > 8)
            {
                $keyData = [
                    '{user-name}' => $check->friend_name,
                    '{customer-name}' => $check->customer->name ?? 'Our customer',
                    '{agent-name}' => $driver->name ?? '',
                    '{car-model}' => $driver->make_model ?? '',
                    '{plate-no}' => $driver->plate_number ?? '',
                    '{track-url}' => $call_web_hook??''
                ];
                $friend_sms_body = sendSmsTemplate('friend-sms', $keyData);
                $send = $this->sendSmsNew($check->friend_phone_number, $friend_sms_body);
            }
            return response()->json([
                'message' => __('Task Accecpted Successfully'),
            ], 200);
        } else {
            if (checkColumnExists('orders', 'rejectable_order') && ((isset($orderdata)  && $orderdata->rejectable_order == 1))) {
                $task_type         = 'failed';

                $Order  = Order::where('id', $orderdata->id)->update(['status' => $task_type, 'driver_id' => $agent_id]);
                $task  = Task::where('order_id', $orderdata->id)->update(['task_status' => '5', 'note' => '']);

                if ($orderdata &&  $orderdata->call_back_url) {
                    $call_web_hook = $this->updateStatusDataToOrder($orderdata, 6, 2);  # task rejected
                }
            } else {
                if($unassignedorder_data->assign_logic == 'one_by_one'){
                    //For order api
                    $this->dispatchNow(new RosterDelete($request->order_id,'O',$agent_id));
                }

                $data = [
                    'order_id'          => $request->order_id,
                    'driver_id'         => $request->driver_id,
                    'status'            => $request->status,
                    'created_at'        => Carbon::now()->toDateTimeString(),
                    'updated_at'        => Carbon::now()->toDateTimeString(),
                ];
                TaskReject::create($data);
            }
            Order::where('id', $orderdata->id)->update(['driver_id'=>null ,'status'=>'unassigned']);
            if($unassignedorder_data->notify_all){
                //For order api
                Order::where('id', $orderdata->id)->update(['notify_all'=>0]);
                $this->dispatchNow(new RosterDelete($request->order_id,'O'));
                $this->autoallocated($request);
            }
            return response()->json([
                'data' => __('Task Rejected Successfully'),
                'status' => 200,
                'message' => __('Task Rejected Successfully')
            ], 200);
        }
    }

    public function callNotification(Request $request)
    {
        try {

            $client = Client::first();
            if(!$client->is_lumen_enabled)
            {
                    $header = $request->header();
                    if (isset($header['client'][0])) {
                    } else {
                        $header['client'][0] = $client->database_name;
                    }
                    $ordersId = Order::where('sync_order_id',$request->order_id)->value('id');
                    \Log::info('Notification send successfully');
                    $this->addRosterNotification($ordersId,$header);
            }else{
                \Log::info('Notification send by Lumen');
            }

        }catch(\Execption $e)
        {
            \Log::info(' callNotification function call'.$e->getMessage());
        }

    }

    public function addRosterNotification($order_id,$header)
    {
        try{
            $orders = Order::findOrFail($order_id);
            $taskcount = $orders->task->count();
            $send_loc_id = $orders->pickup_task_first->location_id;
            $geo = null;
            $agent_id = null;
            $customer = Customer::where('id', $orders->customer_id)->first();

            $finalLocation = Location::where('id', $send_loc_id)->first();
            $pickup_location = $finalLocation;
            $agent_tags = $orders->agent_tags??'';
            $is_order_updated = 0;
            if ($orders->auto_alloction == 'a') {
                $geo = $this->createRoster($send_loc_id);
            }
            $notification_time = $orders->order_time;
            $allocation = AllocationRule::where('id', 1)->first();
            $is_one_push_booking = isset($orders->is_one_push_booking) ? $orders->is_one_push_booking : 0;


            switch ($allocation->auto_assign_logic) {
                case 'one_by_one':
                    //this is called when allocation type is one by one
                    $this->finalRoster($geo, $notification_time, $agent_id, $orders->id, $customer, $pickup_location, $taskcount, $header, $allocation, $orders->is_cab_pooling, $agent_tags, $is_order_updated, $is_one_push_booking);
                    break;
                case 'send_to_all':
                    //this is called when allocation type is send to all
                    $this->SendToAll($geo, $notification_time, $agent_id, $orders->id, $customer, $pickup_location, $taskcount, $header, $allocation, $orders->is_cab_pooling, $agent_tags, $is_order_updated, $is_one_push_booking);
                    break;
                case 'round_robin':
                    //this is called when allocation type is round robin
                    $this->roundRobin($geo, $notification_time, $agent_id, $orders->id, $customer, $pickup_location, $taskcount, $header, $allocation, $orders->is_cab_pooling, $agent_tags, $is_order_updated, $is_one_push_booking);
                    break;
                default:
                    //this is called when allocation type is batch wise
                    $this->batchWise($geo, $notification_time, $agent_id, $orders->id, $customer, $pickup_location, $taskcount, $header, $allocation, $orders->is_cab_pooling, $agent_tags, $is_order_updated, $is_one_push_booking);
            }


            }catch(\Exception $e)
            {
                \Log::info(' addRosterNotification Notification call'.$e->getMessage());
            }
        return true;

    }



    public function CreateTask(CreateTaskRequest $request)
    {
        try {
            $auth = $client = Client::with([
                'getAllocation',
                'getPreference'
            ])->first();
            $header = $request->header();
            if (isset($header['client'][0])) {
            } else {
                $header['client'][0] = $client->database_name;
            }
            $unique_agent_id = null;
            $inValidAgent = 0;
            if($request->driver_unique_id)
            {
                $agent = explode('_',base64_decode($request->driver_unique_id));
                $unique_agent_id = $agent[1]??0;
                $agent = Agent::find($agent[1]??0);
                if(!$agent){
                   return $inValidAgent = response()->json([
                        'status' => 201,
                        'message' => 'Agent not exist'
                    ], 201);
                }

                if($agent && $agent->is_available == 0){
                   return $inValidAgent = response()->json([
                        'status' => 201,
                        'message' => 'Agent is not available'
                    ], 201);
                }
            }

            $client = ClientPreference::where('id', 1)->first();
            $clientTimeZoneId = Client::where('id', 1)->value('timezone');

            if ($request->task_type == 'later')
                $request->task_type = 'schedule';


            DB::beginTransaction();
            $tz = new Timezone();
            if (isset($request->order_time_zone) && !empty($request->order_time_zone))
                $auth->timezone = $request->order_time_zone;
            else
                $auth->timezone = $tz->timezone_name($auth->timezone);

            $clienttimezone = $tz->timezone_name($clientTimeZoneId??251);

            $loc_id = $cus_id = $send_loc_id = $newlat = $newlong = 0;
            $images = [];
            $last = '';
            $customer = [];
            $finalLocation = [];
            $taskcount = 0;
            $latitude = [];
            $longitude = [];
            $percentage = 0;
            $pricingRule = '';
            $pool = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';

            $unique_order_id = substr(str_shuffle(str_repeat($pool, 5)), 0, 6);

            // save task images on s3 bucket
            if (isset($request->file) && count($request->file) > 0) {
                $folder = str_pad(Auth::user()->id, 8, '0', STR_PAD_LEFT);
                $folder = 'client_' . $folder;
                $files = $request->file('file');
                foreach ($files as $key => $value) {
                    $file = $value;
                    $file_name = uniqid() . '.' . $file->getClientOriginalExtension();

                    $s3filePath = '/assets/' . $folder . '/' . $file_name;
                    $path = Storage::disk('s3')->put($s3filePath, $file, 'public');
                    array_push($images, $path);
                }
                $last = implode(",", $images);
            }
            # string of image array
            if (isset($request->images_array) && count($request->images_array) > 0) {

                foreach ($request->images_array as $key => $path) {
                    array_push($images, $path);
                }

                $last = implode(",", $images);
            }

            // create new customer for task or get id of old customer
            if (isset($request->customer_email) || isset($request->customer_phone_number)) {
                $dialCode = $request->customer_dial_code ?? null;
                $customerNo = $dialCode . $request->customer_phone_number;
                $customer = Customer::where('email', $request->customer_email)->orWhere(function ($q) use ($customerNo) {
                    $q->whereRaw("CONCAT(dial_code, '', phone_number) = '" . $customerNo . "'")
                        ->orWhere('phone_number', '+' . $customerNo);
                })
                    ->first();
                if (isset($customer->id)) {
                    $cus_id = $customer->id;
                    // check is number is different then update custom phone number
                    if ($request->customer_phone_number != "") {
                        $customer_phone_number = [
                            'phone_number' => $request->customer_phone_number,
                            'dial_code' => $dialCode,
                            'sync_customer_id' => $request->customer_id,
                            'user_icon' => !empty($request->user_icon['proxy_url']) ? $request->user_icon['proxy_url'] . '512/512' . $request->user_icon['image_path'] : ''
                        ];

                        Customer::where('id', $cus_id)->update($customer_phone_number);
                    }
                } else {
                    $cus = [
                        'name' => $request->customer_name,
                        'email' => $request->customer_email,
                        'phone_number' => $request->customer_phone_number,
                        'dial_code' => $dialCode,
                        'sync_customer_id' => $request->customer_id,
                        'user_icon' => !empty($request->user_icon['proxy_url']) ? $request->user_icon['proxy_url'] . '512/512' . $request->user_icon['image_path'] : ''
                    ];

                    $customer = Customer::create($cus);
                    $cus_id = $customer->id;
                }
            } else {
            }
           $app_call = $request->app_call??0;

           $schedule_time = $request->schedule_time;
            if($request->task_type == "schedule"){
                $settime = $request->schedule_time;
                //Check Api call from Mobile side = 1 or website = 0
                if($app_call){
                    $settime = $request->schedule_time ;
                }

            }else{
                $settime = Carbon::now()->toDateTimeString();
            }
            //here order save code is started
            // $settime = ($request->task_type == "schedule") ? $request->schedule_time : Carbon::now()->toDateTimeString();
            $notification_time = ($request->task_type == "schedule") ? $settime : Carbon::now()->toDateTimeString();
            $agent_id           =  $request->allocation_type === 'm' ? $request->agent:null;
            $agent_id           =  isset($request->driver_id) ? $request->driver_id : $agent_id ;
            $rejectable_order   =  isset($request->rejectable_order) ? $request->rejectable_order : 0;
            $refer_driver_id = null;
            if ($rejectable_order == 1 && checkColumnExists('orders', 'rejectable_order')) {
                $agent_id         = null;
                $refer_driver_id  = $request->agent ?? null;
                $request->allocation_type = 'u';
            }

            if(isset($request->bid_task_type)){
                $request->allocation_type = 'u' ;
                 $agent_id = $request->agent ?? null ;
            }
            $order = [
                'notify_all' => isset($request->notify_all)?$request->notify_all:0,
                'order_number' => $request->order_number ?? null,
                'customer_id' => $cus_id,
                'scheduled_date_time' => ($request->task_type == "schedule") ? $schedule_time : null,
                'recipient_phone' => $request->recipient_phone,
                'Recipient_email' => $request->recipient_email,
                'task_description' => $request->task_description,
                'driver_id' => $agent_id,
                'auto_alloction' => $request->allocation_type,
                'images_array' => $last,
                'order_type' => $request->task_type,
                'order_time' => $notification_time,
                'status' => $agent_id != null? 'assigned' : 'unassigned',
                'cash_to_be_collected' => $request->cash_to_be_collected,
                'unique_id' => $unique_order_id,
                'call_back_url' => $request->call_back_url ?? null,
                'type' => $request->type ?? 0,
                'friend_name' => $request->friend_name,
                'friend_phone_number' => $request->friend_phone_number,
                'request_type' => $request->request_type ?? 'P',
                'is_restricted' => $request->is_restricted ?? 0,
                'vendor_id' => $request->vendor_id,
                'order_vendor_id' => $request->order_vendor_id,
                'dbname' => $request->dbname,
                'sync_order_id' => $request->order_id,
                'available_seats' => isset($request->available_seats) ? $request->available_seats : 0,
                'no_seats_for_pooling' => isset($request->no_seats_for_pooling) ? $request->no_seats_for_pooling : 0,
                'is_cab_pooling' => isset($request->is_cab_pooling) ? $request->is_cab_pooling : 0,
            ];
            \Log::info('data');
             \Log::info($order);
        

            if (checkColumnExists('orders', 'rejectable_order')) {
                $order['rejectable_order'] = isset($request->rejectable_order) ? $request->rejectable_order : 0;
                $order['refer_driver_id']  = $refer_driver_id;
            }
            if (checkColumnExists('orders', 'is_one_push_booking')) {
                $order['is_one_push_booking'] = isset($request->is_one_push_booking) ? $request->is_one_push_booking : 0;
            }
            if (checkColumnExists('orders', 'order_pre_time')) {
                $order['order_pre_time'] = isset($request->order_pre_time) ? $request->order_pre_time : 0;
            }

            $is_order_updated = 0;
            $orderexist = Order::where('call_back_url', '=', $request->call_back_url)->first();
            if (!empty($orderexist)) {
                $order['unique_id'] = ($orderexist->unique_id) ? $orderexist->unique_id : $unique_order_id;
                $order['status'] = $orderexist->status;
                $agent_id = (!empty($orderexist->driver_id)) ? $orderexist->driver_id : NULL;
                $updateorder = Order::where('id', $orderexist->id)->update($order);
                Task::where('order_id', $orderexist->id)->delete();
                $orders = Order::find($orderexist->id);
                $is_order_updated = 1;
            } else {
                $orders = Order::create($order);
            }
            if (checkColumnExists('order_addition_data', 'id')) {
                $this->updateOrderAdditional($request, $orders->id);
            }
            $agent_id =  $unique_agent_id ?? $agent_id;
            /**
             * booking for appointment
             * task_type_id =3= appointment type
             * is_driver_slot check slotting enabled or not
             */
            if (($request->has('task_type_id') && $request->task_type_id == 3) && $auth->getPreference->is_driver_slot == 1) {
                $data = $request->all();
                $data['order_id'] = $orders->id;
                $data['order_number'] = $orders->order_number;
                $data['booking_type'] = 'new_booking';
                $data['memo'] = __("Booked for Order number:") . $orders->order_number;

                $bookingResponse = $this->SlotBooking($data);
            }

            if ($request->is_restricted == 1) {
                $add_resource = CustomerVerificationResource::updateOrCreate([
                    'customer_id' => $cus_id
                ], [
                    'verification_type' => $request->user_verification_type,
                    'datapoints' => json_encode($request->user_datapoints)
                ]);
            }

            if ($auth->custom_domain && !empty($auth->custom_domain)) {
                $client_url = "https://" . $auth->custom_domain;
            } else {
                $client_url = "https://" . $auth->sub_domain . \env('SUBDOMAIN');
            }
            $dispatch_traking_url = $client_url . '/order/tracking/' . $auth->code . '/' . $orders->unique_id;

            $dep_id = null;
            $pickup_location = null;


            foreach ($request->task as $key => $value) {
                $taskcount++;
                $loc_id = null;
                if (isset($value)) {
                    $post_code = isset($value['post_code']) ? $value['post_code'] : '';
                    $loc = [
                        'latitude' => $value['latitude'] ?? 0.00,
                        'longitude' => $value['longitude'] ?? 0.00,
                        'address' => $value['address'] ?? null,
                        'customer_id' => $cus_id
                    ];
                    $loc_update = [
                        'short_name' => $value['short_name'] ?? null,
                        'post_code' => $post_code,
                        'flat_no' => $value['flat_no'] ?? null,
                        'email' => $value['email'] ?? null,
                        'phone_number' => $value['phone_number'] ?? null
                    ];

                    $Loction = Location::updateOrCreate($loc, $loc_update);
                    $loc_id = $Loction->id;
                }

                $finalLocation = Location::where('id', $loc_id)->first();
                if ($key == 0) {
                    $send_loc_id = $loc_id;
                    $pickup_location = $finalLocation;
                }

                if (isset($finalLocation)) {
                    array_push($latitude, $finalLocation->latitude);
                    array_push($longitude, $finalLocation->longitude);
                }

                $task_appointment_duration = isset($value->appointment_duration) ? $value->appointment_duration : null;

                if ($client->is_dispatcher_allocation == 1) {
                    if ($value['task_type_id'] == 2) {
                        $lastTask = Task::where('order_id', $orders->id)
                        ->where('task_type_id', 1)
                        ->orderBy('id', 'desc')

                        ->first();
                        $dep_id = $lastTask->id;
                 }
                 }
                $data = [
                    'order_id' => $orders->id,
                    'task_type_id' => $value['task_type_id'],
                    'location_id' => $loc_id,
                    'appointment_duration' => $task_appointment_duration,
                    'dependent_task_id' => $dep_id,
                    'task_status' => $agent_id != null ? 1 : 0,
                    'allocation_type' => $request->allocation_type,
                    'assigned_time' => $schedule_time,
                    'barcode' => $value['barcode'] ?? null,
                    'vendor_id' => isset($vendor_id) ? $vendor_id->id : '',
                    'warehouse_id' => isset($vendor_id) ? $vendor_id->id : null
                ];


                $task = Task::create($data);

                $dep_id = $task->id;

                if ($client->is_dispatcher_allocation == 1) {
                    if($value['task_type_id'] == 1){
                        $this->createWarehouseTasks($client,$value,$request,$orders,$dep_id,$Loction,$cus_id);
                    }
                 }
            }
            //accounting for task duration distanse

            $geoid = '';
            if (($pickup_location->latitude != '' || $pickup_location->latitude != '0.0000') && ($pickup_location->longitude != '' || $pickup_location->longitude != '0.0000')) :
                $geoid = $this->findLocalityByLatLng($pickup_location->latitude, $pickup_location->longitude);
            endif;

            // get duration and distance
            if ($auth->getPreference->toll_fee == 1) {
                $getdata = $this->toll_fee($latitude, $longitude, (isset($request->toll_passes) ? $request->toll_passes : ''), (isset($request->VehicleEmissionType) ? $request->VehicleEmissionType : ''), (isset($request->travelMode) ? $request->travelMode : ''));
                $toll_amount = (isset($getdata['toll_amount']) ? $getdata['toll_amount'] : 0);
            } else {
                $getdata = $this->GoogleDistanceMatrix($latitude, $longitude);
                $toll_amount = 0;
            }

            //get pricing rule  for save with every order based on geo fence and agent tags
            $client_notification_time = Carbon::parse($notification_time, 'UTC')->setTimezone($clienttimezone)->format('Y-m-d H:i:s');
            $agent_tags = (isset($request->order_agent_tag) && !empty($request->order_agent_tag)) ? $request->order_agent_tag : '';
            $pricingRule = $this->getPricingRuleData($geoid, $agent_tags, $client_notification_time);
            $pricingRuleDistance = $this->getPricingRuleDynamic($pricingRule, $getdata['distance']);


            $paid_duration = $getdata['duration'] - $pricingRule->base_duration;
            $paid_distance = $getdata['distance'] - $pricingRule->base_distance;
            $paid_duration = $paid_duration < 0 ? 0 : $paid_duration;
            $paid_distance = $paid_distance < 0 ? 0 : $paid_distance;
            //$total         = $pricingRule->base_price + ($paid_distance * $pricingRule->distance_fee) + ($paid_duration * $pricingRule->duration_price);
            if($pricingRuleDistance)
            {
                $total         = $pricingRule->base_price + ($pricingRuleDistance) + ($paid_duration * $pricingRule->duration_price);

            }else{
                $total         = $pricingRule->base_price + ($paid_distance * $pricingRule->distance_fee) + ($paid_duration * $pricingRule->duration_price);
            }
            if ($orders->is_cab_pooling == 1) {
                $total       = ($total / $orders->available_seats) * $orders->no_seats_for_pooling;
                $toll_amount = ($toll_amount / $orders->available_seats) * $orders->no_seats_for_pooling;
            }
            if (isset($agent_id)) {
                $agent_details = Agent::where('id', $agent_id)->first();
                if ($agent_details->type == 'Employee') {
                    $percentage = $pricingRule->agent_commission_fixed + (($total / 100) * $pricingRule->agent_commission_percentage);
                } else {
                    $percentage = $pricingRule->freelancer_commission_fixed + (($total / 100) * $pricingRule->freelancer_commission_percentage);
                }
            }

            // update order with order cost details

            $updateorder = [
                'base_price' => $pricingRule->base_price,
                'base_duration' => $pricingRule->base_duration,
                'base_distance' => $pricingRule->base_distance,
                // 'base_waiting' => $pricingRule->base_waiting,
                'duration_price' => $pricingRule->duration_price,
                // 'waiting_price' => $pricingRule->waiting_price,
                'distance_fee' => $pricingRule->distance_fee,
                'cancel_fee' => $pricingRule->cancel_fee,
                'agent_commission_percentage' => $pricingRule->agent_commission_percentage,
                'agent_commission_fixed' => $pricingRule->agent_commission_fixed,
                'freelancer_commission_percentage' => $pricingRule->freelancer_commission_percentage,
                'freelancer_commission_fixed' => $pricingRule->freelancer_commission_fixed,
                'actual_time' => $getdata['duration'],
                'actual_distance' => $getdata['distance'],
                'order_cost' => $total + $toll_amount,
                'toll_fee' => $toll_amount,
                'driver_cost' => $percentage,
                'agent_tags' => $agent_tags
            ];

            if ($request->has('driverCost') && ($request->driverCost > 0)) {
                $driver_cost = $request->driverCost;
                // need to commision
                $updateorder = [
                    'base_price'                      => 0,
                    'base_duration'                   => 0,
                    'base_distance'                   => 0,
                    'base_waiting'                    => 0,
                    'duration_price'                  => 0,
                    'waiting_price'                   => 0,
                    'distance_fee'                    => 0,
                    'cancel_fee'                      => 0,
                    'agent_commission_percentage'     => 0,
                    'agent_commission_fixed'          => 0,
                    'freelancer_commission_percentage' => 0,
                    'freelancer_commission_fixed'     => 0,
                    'actual_time'                     => $getdata['duration'],
                    'actual_distance'                 => $getdata['distance'],
                    'toll_fee'                        => $toll_amount,
                    'order_cost'                      => 0,
                    'driver_cost'                     => $driver_cost,   // freelencer  cost
                ];
            }

            Order::where('id', $orders->id)->update($updateorder);

            if (isset($request->order_team_tag)) {
                $value = $request->order_team_tag;
                $tag_id = [];
                if (!empty($value)) {
                    $check = TagsForTeam::firstOrCreate([
                        'name' => $value
                    ]);
                    array_push($tag_id, $check->id);
                }
                $orders->teamtags()->sync($tag_id);
            }

            if (isset($request->order_agent_tag)) {
                $value = $request->order_agent_tag;
                $tag_id = [];
                if (!empty($value)) {
                    $check = TagsForAgent::firstOrCreate([
                        'name' => $value
                    ]);
                    array_push($tag_id, $check->id);
                }
                $orders->drivertags()->sync($tag_id);
            }
            $geo = null;
            if ($request->allocation_type === 'a') {
                $geo = $this->createRoster($send_loc_id);
                $agent_id = null;
            }
            $allocation = AllocationRule::where('id', 1)->first();
            if($request->driver_unique_id){
                $decode_id = base64_decode($request->driver_unique_id);
                $agentId = explode('_',$decode_id)[1];
                $agent = Agent::find($agentId);
                $title = 'Scheduled New Order';
                $body  = 'The schedule timing of order number #'.$request->order_number.' by the customer.';
                // $this->sendPushNotificationtoDriver($title,$body,$auth,[$agent->device_token],$dispatch_traking_url);
                $this->OneByOneUniqueDriver($geo, $settime, $agentId, $orders, $customer, $pickup_location, $taskcount, $header, $allocation, $orders->is_cab_pooling, $agent_tags, $is_order_updated, '',$request->notify_hour,$request->reminder_hour);
            }
            // If batch allocation is on them return from there no job is created

            if (isset($client->getPreference)) {
                if (isset($client->getPreference->create_batch_hours)) {
                    if ($client->getPreference->create_batch_hours > 0) {
                        $dispatch_traking_url = $client_url . '/order/tracking/' . $auth->code . '/' . $orders->unique_id;

                        DB::commit();
                        $orderdata = Order::select('id', 'order_time', 'status', 'driver_id')->with('agent')
                            ->where('id', $orders->id)
                            ->first();
                        // event(new \App\Events\loadDashboardData($orderdata));
                        return response()->json([
                            'message' => __('Task Added Successfully'),
                            'task_id' => $orders->id,
                            'status' => $orders->status,
                            'dispatch_traking_url' => $dispatch_traking_url ?? null,
                            'invalid_agent' => $inValidAgent
                        ], 200);
                    }
                }
            }
            if(isset($request->bid_task_type)){
                $this->sendBidRideNotification($agent_id,1,$orders->id,$header);
            }
            // task schdule code is hare

            $allocation = AllocationRule::where('id', 1)->first();
            Order::where('id', $orders->id)->update(['assign_logic' => $allocation->auto_assign_logic]);
            if (($request->task_type != 'now') || ($rejectable_order == 1)) {
                // if(isset($header['client'][0]))
                // $auth = Client::where('database_name', $header['client'][0])->with(['getAllocation', 'getPreference'])->first();
                // else
                // $auth = Client::with(['getAllocation', 'getPreference'])->first();
                // setting timezone from id

                $dispatch_traking_url = $client_url . '/order/tracking/' . $auth->code . '/' . $orders->unique_id;

                $tz = new Timezone();
                $auth->timezone = $tz->timezone_name($auth->timezone);

                $beforetime = (int) $auth->getAllocation->start_before_task_time;
                $to = new \DateTime("now", new \DateTimeZone('UTC'));
                $sendTime = Carbon::now();
                $to = Carbon::parse($to)->format('Y-m-d H:i:s');
                $from = Carbon::parse($notification_time)->format('Y-m-d H:i:s');
                $datecheck = 0;
                $to_time = strtotime($to);
                $from_time = strtotime($from);
                if ($to_time >= $from_time) {
                    DB::commit();
                    // $orderdata = Order::select('id', 'order_time', 'status', 'driver_id')->with('agent')->where('id', $orders->id)->first();
                    // event(new \App\Events\loadDashboardData($orderdata));
                    return response()->json([
                        'message' => __('Task Added Successfully'),
                        'task_id' => $orders->id,
                        'status' => $orders->status,
                        'dispatch_traking_url' => $dispatch_traking_url ?? null,
                        'invalid_agent' => $inValidAgent
                    ], 200);
                }

                $diff_in_minutes = round(abs($to_time - $from_time) / 60);
                $schduledata = [];
                if ($diff_in_minutes > $beforetime) {
                    $finaldelay = (int)$diff_in_minutes - $beforetime;
                    $time = Carbon::parse($sendTime)
                        ->addMinutes($finaldelay)
                        ->format('Y-m-d H:i:s');
                    $schduledata['geo']               = $geo;
                    $schduledata['notification_time'] = $settime??$time;
                    $schduledata['notification_befor_time'] = $settime??$notification_time;
                    $schduledata['agent_id']          = $agent_id;
                    $schduledata['orders_id']         = $orders->id;
                    $schduledata['customer']          = $customer;
                    $schduledata['finalLocation']     = $finalLocation;
                    $schduledata['taskcount']         = $taskcount;
                    $schduledata['allocation']        = $allocation;
                    $schduledata['database']          = $auth;
                    $schduledata['cash_to_be_collected']         = $orders->cash_to_be_collected;
                    //Order::where('id',$orders->id)->update(['order_time'=>$time]);
                    //Task::where('order_id',$orders->id)->update(['assigned_time'=>$time,'created_at' =>$time]);
                    // Log::info('scheduleNotifi time');
                    // Log::info($schduledata);
                    if ($rejectable_order == 1) {
                        //  Log::info('scheduleNotifi fire');
                        $schduledata['notification_time'] = Carbon::now()->format('Y-m-d H:i:s');
                        scheduleNotification::dispatch($schduledata)->delay(now());
                    }
                    $schduledata['notification_time'] = $notification_time;
                    scheduleNotification::dispatch($schduledata)->delay(now()->addMinutes($finaldelay));
                    DB::commit();

                    $orderdata = Order::select('id', 'order_time', 'status', 'driver_id')->with('agent')
                        ->where('id', $orders->id)
                        ->first();
                    // event(new \App\Events\loadDashboardData($orderdata));

                    return response()->json([
                        'message' => __('Task Added Successfully'),
                        'task_id' => $orders->id,
                        'status' => $orders->status,
                        'dispatch_traking_url' => $dispatch_traking_url ?? null,
                        'invalid_agent' => $inValidAgent
                    ], 200);
                }
            }
            //Commit Transaction befor send notification
            DB::commit();
 

            if(@$client->is_lumen_enabled)
            {

                lumenDispatchToQueue($geoid,$orders);

            }
            if ($request->allocation_type === 'a' || $request->allocation_type === 'm') {
                $allocation = AllocationRule::where('id', 1)->first();
                $is_one_push_booking = isset($orders->is_one_push_booking) ? $orders->is_one_push_booking : 0;
                switch ($allocation->auto_assign_logic) {
                    case 'one_by_one':
                        //this is called when allocation type is one by one
                        $this->OneByOne($geo, $notification_time, $agent_id, $orders->id, $customer, $pickup_location, $taskcount, $header, $allocation, $orders->is_cab_pooling, $agent_tags, $is_order_updated, $is_one_push_booking);
                        break;
                    case 'send_to_all':
                        //this is called when allocation type is send to all
                        $this->SendToAll($geo, $notification_time, $agent_id, $orders->id, $customer, $pickup_location, $taskcount, $header, $allocation, $orders->is_cab_pooling, $agent_tags, $is_order_updated, $is_one_push_booking);
                        break;
                    case 'round_robin':
                        //this is called when allocation type is round robin
                        $this->roundRobin($geo, $notification_time, $agent_id, $orders->id, $customer, $pickup_location, $taskcount, $header, $allocation, $orders->is_cab_pooling, $agent_tags, $is_order_updated, $is_one_push_booking);
                        break;
                    default:
                        //this is called when allocation type is batch wise
                        $this->batchWise($geo, $notification_time, $agent_id, $orders->id, $customer, $pickup_location, $taskcount, $header, $allocation, $orders->is_cab_pooling, $agent_tags, $is_order_updated, $is_one_push_booking);
                }
            }
          
            $dispatch_traking_url = $client_url . '/order/tracking/' . $auth->code . '/' . $orders->unique_id;
            return response()->json([
                'message' => __('Task Added Successfully'),
                'task_id' => $orders->id,
                'status' => $orders->status,
                'dispatch_traking_url' => $dispatch_traking_url ?? null,
                'invalid_agent' => $inValidAgent
            ], 200);
       } catch (\Exception $e) {
            DB::rollback();
            \Log::info("dispatch error ". $e->getMessage());
            return response()->json([
                'message' => $e->getMessage()
            ], 400);
        }
    }

    public function CreateLimsTask(CreateTaskRequest $request)
    {
        try {
            $header = $request->header();
            if (isset($header['client'][0])) {
            } else {
                $client = Client::with([
                    'getAllocation',
                    'getPreference'
                ])->first();
                $header['client'][0] = $client->database_name;
            }

            if ($request->task_type == 'later')
                $request->task_type = 'schedule';

            DB::beginTransaction();

            $auth = Client::with([
                'getAllocation',
                'getPreference'
            ])->first();
            $tz = new Timezone();

            if (isset($request->order_time_zone) && !empty($request->order_time_zone))
                $auth->timezone = $request->order_time_zone;
            else
                $auth->timezone = $tz->timezone_name($auth->timezone);

            $loc_id = $cus_id = $send_loc_id = $newlat = $newlong = 0;
            $images = [];
            $last = '';
            $customer = [];
            $finalLocation = [];
            $taskcount = 0;
            $latitude = [];
            $longitude = [];
            $percentage = 0;
            $pricingRule = '';

            $pool = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';

            $unique_order_id = substr(str_shuffle(str_repeat($pool, 5)), 0, 6);

            // save task images on s3 bucket
            // if (isset($request->file) && count($request->file) > 0) {
            // $folder = str_pad(Auth::user()->id, 8, '0', STR_PAD_LEFT);
            // $folder = 'client_' . $folder;
            // $files = $request->file('file');
            // foreach ($files as $key => $value) {
            // $file = $value;
            // $file_name = uniqid() . '.' . $file->getClientOriginalExtension();

            // $s3filePath = '/assets/' . $folder . '/' . $file_name;
            // $path = Storage::disk('s3')->put($s3filePath, $file, 'public');
            // array_push($images, $path);
            // }
            // $last = implode(",", $images);
            // }
            # string of image array
            // if (isset($request->images_array) && count($request->images_array) > 0){

            // foreach ($request->images_array as $key => $path) {
            // array_push($images, $path);
            // }

            // $last = implode(",", $images);

            // }

            // create new customer for task or get id of old customer

            if (isset($request->customer_email)) {
                $customer = Customer::where('email', '=', $request->customer_email)->first();
                if (isset($customer->id)) {
                    $cus_id = $customer->id;
                    // check is number is different then update custom phone number
                    if ($customer->phone_number != $request->customer_phone_number && $request->customer_phone_number != "") {
                        $customer_phone_number = [
                            'phone_number' => $request->customer_phone_number
                        ];
                        Customer::where('id', $cus_id)->update($customer_phone_number);
                    }
                } else {
                    $cus = [
                        'name' => $request->customer_name,
                        'email' => $request->customer_email,
                        'phone_number' => $request->customer_phone_number
                    ];
                    $customer = Customer::create($cus);
                    $cus_id = $customer->id;
                }
            }

            // get pricing rule for save with every order
            // if(isset($request->order_agent_tag) && !empty($request->order_agent_tag))
            // $pricingRule = PricingRule::orderBy('id', 'desc')->whereHas('tagsForAgent',function($q)use($request){
            // $q->where('name',$request->order_agent_tag);
            // })->first();

            // if(empty($pricingRule))
            $pricingRule = PricingRule::orderBy('id', 'desc')->first();

            // here order save code is started
            $settime = ($request->task_type == "schedule") ? $request->schedule_time : Carbon::now()->toDateTimeString();
            // $notification_time = ($request->task_type=="schedule") ? Carbon::parse($settime . $auth->timezone ?? 'UTC')->tz('UTC') : Carbon::now()->toDateTimeString();
            $notification_time = ($request->task_type == "schedule") ? $settime : Carbon::now()->toDateTimeString();
            // $notification_time = isset($request->schedule_time) ? $request->schedule_time : Carbon::now()->toDateTimeString();

            $agent_id = $request->allocation_type === 'm' ? $request->agent : null;
            $checkorder = Order::where('order_number', $request->order_number)->first();
            if ($checkorder) {
                $orders = $checkorder;
                // order already exist. skip the entry
            } else {

                $order = [
                    'order_number' => $request->order_number ?? null,
                    'customer_id' => $cus_id,
                    'scheduled_date_time' => ($request->task_type == "schedule") ? $notification_time : null,
                    'recipient_phone' => $request->recipient_phone,
                    'Recipient_email' => $request->recipient_email,
                    'task_description' => $request->task_description,
                    'driver_id' => $agent_id,
                    'auto_alloction' => $request->allocation_type,
                    'images_array' => $last,
                    'order_type' => $request->task_type,
                    'order_time' => $notification_time,
                    'status' => $agent_id != null ? 'assigned' : 'unassigned',
                    'cash_to_be_collected' => $request->cash_to_be_collected,
                    'base_price' => $pricingRule->base_price,
                    'base_duration' => $pricingRule->base_duration,
                    'base_distance' => $pricingRule->base_distance,
                    'base_waiting' => $pricingRule->base_waiting,
                    'duration_price' => $pricingRule->duration_price,
                    'waiting_price' => $pricingRule->waiting_price,
                    'distance_fee' => $pricingRule->distance_fee,
                    'cancel_fee' => $pricingRule->cancel_fee,
                    'agent_commission_percentage' => $pricingRule->agent_commission_percentage,
                    'agent_commission_fixed' => $pricingRule->agent_commission_fixed,
                    'freelancer_commission_percentage' => $pricingRule->freelancer_commission_percentage,
                    'freelancer_commission_fixed' => $pricingRule->freelancer_commission_fixed,
                    'unique_id' => $unique_order_id,
                    'call_back_url' => $request->call_back_url ?? null,
                    'type' => $request->type ?? 0,
                    'friend_name' => $request->friend_name,
                    'friend_phone_number' => $request->friend_phone_number
                ];
                $orders = Order::create($order);

                if ($auth->custom_domain && !empty($auth->custom_domain)) {
                    $client_url = "https://" . $auth->custom_domain;
                } else {
                    $client_url = "https://" . $auth->sub_domain . \env('SUBDOMAIN');
                }
                $dispatch_traking_url = $client_url . '/order/tracking/' . $auth->code . '/' . $orders->unique_id;

                $dep_id = null;
                $pickup_location = null;

                foreach ($request->task as $key => $value) {
                    $taskcount++;
                    $loc_id = null;
                    if (isset($value)) {
                        $post_code = isset($value['post_code']) ? $value['post_code'] : '';
                        $loc = [
                            'latitude' => $value['latitude'] ?? 0.00,
                            'longitude' => $value['longitude'] ?? 0.00,
                            'address' => $value['address'] ?? null,
                            'customer_id' => $cus_id
                        ];
                        $loc_update = [
                            'short_name' => $value['short_name'] ?? null,
                            'post_code' => $post_code,
                            'flat_no' => $value['flat_no'] ?? null,
                            'email' => $value['email'] ?? null,
                            'phone_number' => $value['phone_number'] ?? null
                        ];

                        // $Loction = Location::create($loc);
                        $Loction = Location::updateOrCreate($loc, $loc_update);
                        $loc_id = $Loction->id;
                    }

                    $finalLocation = Location::where('id', $loc_id)->first();
                    if ($key == 0) {
                        $send_loc_id = $loc_id;
                        $pickup_location = $finalLocation;
                    }

                    if (isset($finalLocation)) {
                        array_push($latitude, $finalLocation->latitude);
                        array_push($longitude, $finalLocation->longitude);
                    }

                    $task_appointment_duration = isset($value->appointment_duration) ? $value->appointment_duration : null;

                    $data = [
                        'order_id' => $orders->id,
                        'task_type_id' => $value['task_type_id'],
                        'location_id' => $loc_id,
                        'appointment_duration' => $task_appointment_duration,
                        'dependent_task_id' => $dep_id,
                        'task_status' => $agent_id != null ? 1 : 0,
                        'allocation_type' => $request->allocation_type,
                        'assigned_time' => $notification_time,
                        'barcode' => $value['barcode'] ?? null
                    ];

                    $task = Task::create($data);
                    $dep_id = $task->id;
                }

                // accounting for task duration distanse

                // $getdata = $this->GoogleDistanceMatrix($latitude, $longitude);

                // $paid_duration = $getdata['duration'] - $pricingRule->base_duration;
                // $paid_distance = $getdata['distance'] - $pricingRule->base_distance;
                // $paid_duration = $paid_duration < 0 ? 0 : $paid_duration;
                // $paid_distance = $paid_distance < 0 ? 0 : $paid_distance;
                // $total = $pricingRule->base_price + ($paid_distance * $pricingRule->distance_fee) + ($paid_duration * $pricingRule->duration_price);

                // if (isset($agent_id)) {
                // $agent_details = Agent::where('id', $agent_id)->first();
                // if ($agent_details->type == 'Employee') {
                // $percentage = $pricingRule->agent_commission_fixed + (($total / 100) * $pricingRule->agent_commission_percentage);
                // } else {
                // $percentage = $pricingRule->freelancer_commission_percentage + (($total / 100) * $pricingRule->freelancer_commission_fixed);
                // }
                // }

                // update order with order cost details

                // $updateorder = [
                // 'actual_time' => $getdata['duration'],
                // 'actual_distance' => $getdata['distance'],
                // 'order_cost' => $total,
                // 'driver_cost' => $percentage,
                // ];

                // Order::where('id', $orders->id)->update($updateorder);

                if (isset($request->allocation_type) && $request->allocation_type === 'a') {
                    // if (isset($request->team_tag)) {
                    // $orders->teamtags()->sync($request->team_tag);
                    // }
                    // if (isset($request->agent_tag)) {
                    // $orders->drivertags()->sync($request->agent_tag);
                    // }
                }
                // if (isset($request->order_team_tag)) {

                // $value = $request->order_team_tag;
                // $tag_id = [];
                // if (!empty($value)) {
                // $check = TagsForTeam::firstOrCreate(['name' => $value]);
                // array_push($tag_id, $check->id);
                // }
                // $orders->teamtags()->sync($tag_id);
                // }

                // if (isset($request->order_agent_tag)) {

                // $value = $request->order_agent_tag;
                // $tag_id = [];
                // if (!empty($value)) {
                // $check = TagsForAgent::firstOrCreate(['name' => $value]);
                // array_push($tag_id, $check->id);
                // }
                // $orders->drivertags()->sync($tag_id);
                // }

                $geo = null;
                if ($request->allocation_type === 'a') {
                    $geo = $this->createRoster($send_loc_id);

                    $agent_id = null;
                }

                // task schdule code is hare

                $allocation = AllocationRule::where('id', 1)->first();

                // if ($request->task_type != 'now') {
                // if(isset($header['client'][0]))
                // $auth = Client::where('database_name', $header['client'][0])->with(['getAllocation', 'getPreference'])->first();
                // else
                // $auth = Client::with(['getAllocation', 'getPreference'])->first();
                // //setting timezone from id

                // $dispatch_traking_url = $client_url.'/order/tracking/'.$auth->code.'/'.$orders->unique_id;

                // $tz = new Timezone();
                // $auth->timezone = $tz->timezone_name($auth->timezone);

                // $beforetime = (int)$auth->getAllocation->start_before_task_time;
                // // $to = new \DateTime("now", new \DateTimeZone(isset(Auth::user()->timezone)? Auth::user()->timezone : 'Asia/Kolkata') );
                // $to = new \DateTime("now", new \DateTimeZone('UTC'));
                // $sendTime = Carbon::now();
                // $to = Carbon::parse($to)->format('Y-m-d H:i:s');
                // $from = Carbon::parse($notification_time)->format('Y-m-d H:i:s');
                // $datecheck = 0;
                // $to_time = strtotime($to);
                // $from_time = strtotime($from);
                // if ($to_time >= $from_time) {
                // DB::commit();
                // return response()->json([
                // 'message' => __('Task Added Successfully'),
                // 'task_id' => $orders->id,
                // 'status' => $orders->status,
                // 'dispatch_traking_url' => $dispatch_traking_url??null
                // ], 200);
                // }

                // $diff_in_minutes = round(abs($to_time - $from_time) / 60);

                // $schduledata = [];

                // if ($diff_in_minutes > $beforetime) {
                // $finaldelay = (int)$diff_in_minutes - $beforetime;

                // $time = Carbon::parse($sendTime)
                // ->addMinutes($finaldelay)
                // ->format('Y-m-d H:i:s');

                // $schduledata['geo'] = $geo;
                // //$schduledata['notification_time'] = $time;
                // $schduledata['notification_time'] = $notification_time;
                // $schduledata['agent_id'] = $agent_id;
                // $schduledata['orders_id'] = $orders->id;
                // $schduledata['customer'] = $customer;
                // $schduledata['finalLocation'] = $finalLocation;
                // $schduledata['taskcount'] = $taskcount;
                // $schduledata['allocation'] = $allocation;
                // $schduledata['database'] = $auth;
                // $schduledata['cash_to_be_collected'] = $orders->cash_to_be_collected;

                // //Order::where('id',$orders->id)->update(['order_time'=>$time]);
                // //Task::where('order_id',$orders->id)->update(['assigned_time'=>$time,'created_at' =>$time]);

                // scheduleNotification::dispatch($schduledata)->delay(now()->addMinutes($finaldelay));
                // DB::commit();

                // return response()->json([
                // 'message' => __('Task Added Successfully'),
                // 'task_id' => $orders->id,
                // 'status' => $orders->status,
                // 'dispatch_traking_url' => $dispatch_traking_url??null
                // ], 200);
                // }
                // }

                // this is roster create accounding to the allocation methed

                // if ($request->allocation_type === 'a' || $request->allocation_type === 'm') {
                // $allocation = AllocationRule::where('id', 1)->first();
                // switch ($allocation->auto_assign_logic) {
                // case 'one_by_one':
                // //this is called when allocation type is one by one
                // $this->finalRoster($geo, $notification_time, $agent_id, $orders->id, $customer, $pickup_location, $taskcount, $header, $allocation);
                // break;
                // case 'send_to_all':
                // //this is called when allocation type is send to all
                // $this->SendToAll($geo, $notification_time, $agent_id, $orders->id, $customer, $pickup_location, $taskcount, $header, $allocation);
                // break;
                // case 'round_robin':
                // //this is called when allocation type is round robin
                // $this->roundRobin($geo, $notification_time, $agent_id, $orders->id, $customer, $pickup_location, $taskcount, $header, $allocation);
                // break;
                // default:
                // //this is called when allocation type is batch wise
                // $this->batchWise($geo, $notification_time, $agent_id, $orders->id, $customer, $pickup_location, $taskcount, $header, $allocation);
                // }
                // }
                $dispatch_traking_url = $client_url . '/order/tracking/' . $auth->code . '/' . $orders->unique_id;
            }
            DB::commit();
            return response()->json([
                'message' => __('Task Added Successfully'),
                'task_id' => $orders->id,
                'status' => $orders->status,
                'dispatch_traking_url' => $dispatch_traking_url ?? null
            ], 200);
        } catch (Exception $e) {
            DB::rollback();
            return response()->json([
                'message' => $e->getMessage()
            ], 400);
        }
    }

    public function createRoster($send_loc_id)
    {
        $getletlong = Location::where('id', $send_loc_id)->first();
        $lat = $getletlong->latitude;
        $long = $getletlong->longitude;

        return $check = $this->findLocalityByLatLng($lat, $long);
    }

    public function findLocalityByLatLng($lat, $lng)
    {
        // get the locality_id by the coordinate //
        $latitude_y = $lat;
        $longitude_x = $lng;
        $localities = Geo::all();

        if (empty($localities)) {
            return false;
        }

        foreach ($localities as $k => $locality) {

            if (!empty($locality->polygon)) {
                $geoLocalitie = Geo::where('id', $locality->id)->whereRaw("ST_Contains(POLYGON, ST_GEOMFROMTEXT('POINT(" . $lat . " " . $lng . ")'))")->first();
                if (!empty($geoLocalitie)) {
                    return $locality->id;
                }
            } else {
                $all_points = $locality->geo_array;
                $temp = $all_points;
                $temp = str_replace('(', '[', $temp);
                $temp = str_replace(')', ']', $temp);
                $temp = '[' . $temp . ']';
                $temp_array = json_decode($temp, true);

                foreach ($temp_array as $k => $v) {
                    $data[] = [
                        'lat' => $v[0],
                        'lng' => $v[1]
                    ];
                }

                // $all_points[]= $all_points[0]; // push the first point in end to complete
                $vertices_x = $vertices_y = array();

                foreach ($data as $key => $value) {
                    $vertices_y[] = $value['lat'];
                    $vertices_x[] = $value['lng'];
                }

                $points_polygon = count($vertices_x) - 1; // number vertices - zero-based array
                $points_polygon;

                if ($this->is_in_polygon($points_polygon, $vertices_x, $vertices_y, $longitude_x, $latitude_y)) {
                    return $locality->id;
                }
            }
        }

        return false;
    }

    public function is_in_polygon($points_polygon, $vertices_x, $vertices_y, $longitude_x, $latitude_y)
    {
        $i = $j = $c = 0;
        for ($i = 0, $j = $points_polygon; $i < $points_polygon; $j = $i++) {
            if ((($vertices_y[$i] > $latitude_y != ($vertices_y[$j] > $latitude_y)) && ($longitude_x < ($vertices_x[$j] - $vertices_x[$i]) * ($latitude_y - $vertices_y[$i]) / ($vertices_y[$j] - $vertices_y[$i]) + $vertices_x[$i]))) {
                $c = !$c;
            }
        }
        return $c;
    }

    public function finalRoster($geo, $notification_time, $agent_id, $orders_id, $customer, $finalLocation, $taskcount, $header, $allocation, $is_cab_pooling, $agent_tag = '', $is_order_updated = 0, $is_one_push_booking = 0,$particular_driver_id = 0)
    {
        $allcation_type = 'AR';
        $date = \Carbon\Carbon::today();
        $auth = Client::where('database_name', $header['client'][0])->with([
            'getAllocation',
            'getPreference'
        ])->first();

        $expriedate = (int) $auth->getAllocation->request_expiry;
        $beforetime = (int) $auth->getAllocation->start_before_task_time;
        $maxsize = (int) $auth->getAllocation->maximum_batch_size;
        $type = $auth->getPreference->acknowledgement_type;
        $try = $auth->getAllocation->number_of_retries;
        $time = $this->checkTimeDiffrence($notification_time, $beforetime); // this function is check the time diffrence and give the notification time
        $randem = rand(11111111, 99999999);

        if ($type == 'acceptreject') {
            $allcation_type = 'AR';
        } elseif ($type == 'acknowledge') {
            $allcation_type = 'ACK';
        } else {
            $allcation_type = 'N';
        }

        $extraData = [
            'customer_name' => $customer->name,
            'customer_phone_number' => $customer->phone_number,
            'short_name' => $finalLocation->short_name,
            'address' => $finalLocation->address,
            'lat' => $finalLocation->latitude,
            'long' => $finalLocation->longitude,
            'task_count' => $taskcount,
            'unique_id' => $randem,
            'created_at' => Carbon::now()->toDateTimeString(),
            'updated_at' => Carbon::now()->toDateTimeString()
        ];

        if (!isset($geo)) {
            $oneagent = Agent::where('id', $agent_id)->first();
            if (isset($oneagent) && !empty($oneagent->device_token) && $oneagent->is_available == 1) {
                $allcation_type = 'ACK';
                $data = [
                    'order_id' => $orders_id,
                    'driver_id' => $agent_id,
                    'notification_time' => $time,
                    'type' => $allcation_type,
                    'client_code' => $auth->code,
                    'created_at' => Carbon::now()->toDateTimeString(),
                    'updated_at' => Carbon::now()->toDateTimeString(),
                    'device_type' => $oneagent->device_type ?? '',
                    'device_token' => $oneagent->device_token,
                    'detail_id' => $randem
                ];
                $this->dispatch(new RosterCreate($data, $extraData)); // this job is for create roster in main database for send the notification in manual alloction
            }
        } else {
            $unit = $auth->getPreference->distance_unit;
            $try = $auth->getAllocation->number_of_retries;
            $cash_at_hand = $auth->getAllocation->maximum_cash_at_hand_per_person ?? 0;
            $max_redius = $auth->getAllocation->maximum_radius;
            $max_task = $auth->getAllocation->maximum_batch_size;

            $dummyentry = [];
            $all = [];
            $extra = [];
            $remening = [];

            $geoagents = $this->getGeoBasedAgentsData($geo, $is_cab_pooling, $agent_tag, $date, $cash_at_hand, $orders_id, $particular_driver_id);

            $totalcount = $geoagents->count();
            $orders = order::where('driver_id', '!=', null)->whereDate('created_at', $date)
                ->groupBy('driver_id')
                ->get('driver_id');

            $allreadytaken = [];
            foreach ($orders as $ids) {
                array_push($allreadytaken, $ids->driver_id);
            }
            // print_r($allreadytaken);
            // die;
            $counter = 0;
            $data = [];
            if ($totalcount > 0) {
                for ($i = 1; $i <= $try; $i++) {
                    foreach ($geoagents as $key => $geoitem) {
                        if (in_array($geoitem->id, $allreadytaken) && !empty($geoitem->device_token) && $geoitem->is_available == 1) {
                            $extra = [
                                'id' => $geoitem->id,
                                'device_type' => $geoitem->device_type ?? '',
                                'device_token' => $geoitem->device_token
                            ];
                            array_push($remening, $extra);
                        } else {
                            if (!empty($geoitem->device_token) && $geoitem->is_available == 1) {
                                $data = [
                                    'order_id' => $orders_id,
                                    'driver_id' => $geoitem->id,
                                    'notification_time' => $time,
                                    'type' => $allcation_type,
                                    'client_code' => $auth->code,
                                    'created_at' => Carbon::now()->toDateTimeString(),
                                    'updated_at' => Carbon::now()->toDateTimeString(),
                                    'device_type' => $geoitem->device_type ?? '',
                                    'device_token' => $geoitem->device_token,
                                    'detail_id' => $randem
                                ];
                                if (count($dummyentry) < 1) {
                                    array_push($dummyentry, $data);
                                }

                                // here i am seting the time diffrence for every notification

                                $time = Carbon::parse($time)->addSeconds($expriedate + 3)->format('Y-m-d H:i:s');
                                array_push($all, $data);
                            }
                            $counter++;
                        }

                        if ($allcation_type == 'N' && 'ACK' && count($all) > 0) {
                            Order::where('id', $orders_id)->update([
                                'driver_id' => $geoitem->id
                            ]);

                            break;
                        }
                    }

                    foreach ($remening as $key => $rem) {
                        $data = [
                            'order_id' => $orders_id,
                            'driver_id' => $rem['id'],
                            'notification_time' => $time,
                            'type' => $allcation_type,
                            'client_code' => $auth->code,
                            'created_at' => Carbon::now()->toDateTimeString(),
                            'updated_at' => Carbon::now()->toDateTimeString(),
                            'device_type' => $rem['device_type'] ?? '',
                            'device_token' => $rem['device_token'],
                            'detail_id' => $randem
                        ];

                        $time = Carbon::parse($time)->addSeconds($expriedate + 3)->format('Y-m-d H:i:s');

                        if (count($dummyentry) < 1) {
                            array_push($dummyentry, $data);
                        }
                        array_push($all, $data);
                        if ($allcation_type == 'N' && 'ACK' && count($all) > 0) {
                            Order::where('id', $orders_id)->update([
                                'driver_id' => $remening[$i]['id']
                            ]);

                            break;
                        }
                    }
                    $remening = [];
                    if ($allcation_type == 'N' && 'ACK' && count($all) > 0) {
                        break;
                    }
                }
                $this->dispatch(new RosterCreate($all, $extraData)); // //this job is for create roster in main database for send the notification in auto alloction
            }
        }
    }

    // this function for check time diffrence and give a time for notification send betwwen current time and task time
    public function checkTimeDiffrence($notification_time, $beforetime)
    {
        $to = Carbon::createFromFormat('Y-m-d H:s:i', Carbon::now()->toDateTimeString());

        $from = Carbon::createFromFormat('Y-m-d H:s:i', Carbon::parse($notification_time)->format('Y-m-d H:i:s'));

        $diff_in_minutes = $to->diffInMinutes($from);
        if ($diff_in_minutes < $beforetime) {
            return Carbon::now()->toDateTimeString();
        } else {
            return $notification_time;
        }
    }

    public function SendToAll($geo, $notification_time, $agent_id, $orders_id, $customer, $finalLocation, $taskcount, $header, $allocation, $is_cab_pooling, $agent_tag = '', $is_order_updated, $is_one_push_booking = 0,$particular_driver_id = 0)
    {
        \Log::info($geo);
        $allcation_type    = 'AR';
        $date              = \Carbon\Carbon::today();
        $auth              = Client::where('database_name', $header['client'][0])->with(['getAllocation', 'getPreference'])->first();
        $expriedate        = (int)$auth->getAllocation->request_expiry;
        $beforetime        = (int)$auth->getAllocation->start_before_task_time;
        $maxsize           = (int)$auth->getAllocation->maximum_batch_size;
        $type              = $auth->getPreference->acknowledgement_type;
        $unit              = $auth->getPreference->distance_unit;
        $try               = $auth->getAllocation->number_of_retries;
        $cash_at_hand      = $auth->getAllocation->maximum_cash_at_hand_per_person ?? 0;
        $max_redius        = $auth->getAllocation->maximum_radius;
        $max_task          = $auth->getAllocation->maximum_batch_size;
        $time              = $this->checkTimeDiffrence($notification_time, $beforetime);
        $randem            = rand(11111111, 99999999);
        $data = [];

        if ($type == 'acceptreject') {
            $allcation_type = 'AR';
        } elseif ($type == 'acknowledge') {
            $allcation_type = 'ACK';
        } else {
            $allcation_type = 'N';
        }

        if ($is_one_push_booking == 1) {
            $allcation_type = "Instant_Booking";
        }

        $extraData = [
            'customer_name' => $customer->name,
            'customer_phone_number' => $customer->phone_number,
            'short_name' => $finalLocation->short_name,
            'address' => $finalLocation->address,
            'lat' => $finalLocation->latitude,
            'long' => $finalLocation->longitude,
            'task_count' => $taskcount,
            'unique_id' => $randem,
            'created_at' => Carbon::now()->toDateTimeString(),
            'updated_at' => Carbon::now()->toDateTimeString()
        ];

        if (!isset($geo) && !empty($geo)) {
            $oneagent = Agent::where('id', $agent_id)->first();
            if (isset($oneagent) && !empty($oneagent->device_token) && $oneagent->is_available == 1) {
                $allcation_type = 'ACK';
                $data = [
                    'order_id'            => $orders_id,
                    'driver_id'           => $agent_id,
                    'notification_time'   => $time,
                    'type'                => $allcation_type,
                    'client_code'         => $auth->code,
                    'created_at'          => Carbon::now()->toDateTimeString(),
                    'updated_at'          => Carbon::now()->toDateTimeString(),
                    'device_type'         => $oneagent->device_type ?? '',
                    'device_token'        => $oneagent->device_token,
                    'detail_id'           => $randem,

                ];
                $this->dispatch(new RosterCreate($data, $extraData));
            }
        } else {
            $geoagents = $this->getGeoBasedAgentsData($geo, $is_cab_pooling, $agent_tag, $date, $cash_at_hand,$orders_id,$particular_driver_id);
            if(count($geoagents) > 0){
                for ($i = 1; $i <= $try; $i++) {
                    foreach ($geoagents as $key =>  $geoitem) {
                        if (!empty($geoitem->device_token) && !empty($geoitem->device_type)  && $geoitem->is_available == 1) {
                            $datas = [
                                'order_id'            => $orders_id,
                                'driver_id'           => $geoitem->id,
                                'notification_time'   => $time,
                                'type'                => $allcation_type,
                                'client_code'         => $auth->code,
                                'created_at'          => Carbon::now()->toDateTimeString(),
                                'updated_at'          => Carbon::now()->toDateTimeString(),
                                'device_type'         => $geoitem->device_type,
                                'device_token'        => $geoitem->device_token,
                                'detail_id'           => $randem,

                            ];
                            array_push($data, $datas);
                            if ($allcation_type == 'N' && 'ACK') {
                                Order::where('id', $orders_id)->update(['driver_id' => $geoitem->id]);
                                break;
                            }
                        }
                    }
                    $time = Carbon::parse($time)
                        ->addSeconds($expriedate + 10)
                        ->format('Y-m-d H:i:s');
                    if ($allcation_type == 'N' && 'ACK') {
                        break;
                    }
                }
            }
           if(!empty($data))
            $this->dispatch(new RosterCreate($data, $extraData));
        }
    }



    public function batchWise($geo, $notification_time, $agent_id, $orders_id, $customer, $finalLocation, $taskcount, $header, $allocation, $is_cab_pooling, $agent_tag = '', $is_order_updated = 0, $is_one_push_booking = 0, $particular_driver_id = 0)
    {
        $allcation_type = 'AR';
        $date = \Carbon\Carbon::today();
        $auth = Client::where('database_name', $header['client'][0])->with([
            'getAllocation',
            'getPreference'
        ])->first();
        $expriedate = (int) $auth->getAllocation->request_expiry;
        $beforetime = (int) $auth->getAllocation->start_before_task_time;
        $maxsize = (int) $auth->getAllocation->maximum_batch_size;
        $type = $auth->getPreference->acknowledgement_type;
        $unit = $auth->getPreference->distance_unit;
        $try = $auth->getAllocation->number_of_retries;
        $cash_at_hand = $auth->getAllocation->maximum_cash_at_hand_per_person ?? 0;
        $max_redius = $auth->getAllocation->maximum_radius;
        $max_task = $auth->getAllocation->maximum_batch_size;
        $time = $this->checkTimeDiffrence($notification_time, $beforetime);
        $randem = rand(11111111, 99999999);
        $data = [];

        if ($type == 'acceptreject') {
            $allcation_type = 'AR';
        } elseif ($type == 'acknowledge') {
            $allcation_type = 'ACK';
        } else {
            $allcation_type = 'N';
        }

        $extraData = [
            'customer_name' => $customer->name,
            'customer_phone_number' => $customer->phone_number,
            'short_name' => $finalLocation->short_name,
            'address' => $finalLocation->address,
            'lat' => $finalLocation->latitude,
            'long' => $finalLocation->longitude,
            'task_count' => $taskcount,
            'unique_id' => $randem,
            'created_at' => Carbon::now()->toDateTimeString(),
            'updated_at' => Carbon::now()->toDateTimeString()
        ];

        if (!isset($geo)) {
            $oneagent = Agent::where('id', $agent_id)->first();
            if (isset($oneagent) &&  !empty($oneagent->device_token) && $oneagent->is_available == 1) {
                $allcation_type = 'ACK';
                $data = [
                    'order_id' => $orders_id,
                    'driver_id' => $agent_id,
                    'notification_time' => $time,
                    'type' => $allcation_type,
                    'client_code' => $auth->code,
                    'created_at' => Carbon::now()->toDateTimeString(),
                    'updated_at' => Carbon::now()->toDateTimeString(),
                    'device_type' => $oneagent->device_type ?? '',
                    'device_token' => $oneagent->device_token,
                    'detail_id' => $randem
                ];
                $this->dispatch(new RosterCreate($data, $extraData));
            }
        } else {
            $geoagents = $this->getGeoBasedAgentsData($geo, $is_cab_pooling, $agent_tag, $date, $cash_at_hand, $orders_id, $particular_driver_id);
            $geoagents = $geoagents->toArray();
            // this function is give me nearest drivers list accourding to the the task location.
            $distenseResult = $this->haversineGreatCircleDistance($geoagents, $finalLocation, $unit, $max_redius, $max_task);

            if (count($distenseResult) > 0) {
                for ($i = 1; $i <= $try; $i++) {
                    $counter = 0;
                    foreach ($distenseResult as $key => $geoitem) {
                        if (!empty($geoitem['device_token'])) {
                            $datas = [
                                'order_id' => $orders_id,
                                'driver_id' => $geoitem['driver_id'],
                                'notification_time' => $time,
                                'type' => $allcation_type,
                                'client_code' => $auth->code,
                                'created_at' => Carbon::now()->toDateTimeString(),
                                'updated_at' => Carbon::now()->toDateTimeString(),
                                'device_type' => $geoitem['device_type'] ?? '',
                                'device_token' => $geoitem['device_token'],
                                'detail_id' => $randem
                            ];
                            array_push($data, $datas);
                            $counter++;
                            if ($counter == $maxsize) {
                                $time = Carbon::parse($time)->addSeconds($expriedate)->format('Y-m-d H:i:s');
                                $counter = 0;
                            }
                            if ($allcation_type == 'N' && 'ACK') {
                                break;
                            }
                        }
                    }
                    $time = Carbon::parse($time)->addSeconds($expriedate + 10)->format('Y-m-d H:i:s');
                    if ($allcation_type == 'N' && 'ACK') {
                        break;
                    }
                }
                $this->dispatch(new RosterCreate($data, $extraData)); // job for create roster
            }
        }
    }

    public function roundRobin($geo, $notification_time, $agent_id, $orders_id, $customer, $finalLocation, $taskcount, $header, $allocation, $is_cab_pooling, $agent_tag = '', $is_order_updated = 0, $is_one_push_booking = 0, $particular_driver_id = 0)
    {
        $allcation_type = 'AR';
        $date = \Carbon\Carbon::today();
        $auth = Client::where('database_name', $header['client'][0])->with([
            'getAllocation',
            'getPreference'
        ])->first();
        $expriedate = (int) $auth->getAllocation->request_expiry;
        $beforetime = (int) $auth->getAllocation->start_before_task_time;
        $maxsize = (int) $auth->getAllocation->maximum_batch_size;
        $type = $auth->getPreference->acknowledgement_type;
        $unit = $auth->getPreference->distance_unit;
        $try = $auth->getAllocation->number_of_retries;
        $cash_at_hand = $auth->getAllocation->maximum_cash_at_hand_per_person ?? 0;
        $max_redius = $auth->getAllocation->maximum_radius;
        $max_task = $auth->getAllocation->maximum_batch_size;
        $time = $this->checkTimeDiffrence($notification_time, $beforetime);
        $randem = rand(11111111, 99999999);
        $data = [];

        if ($type == 'acceptreject') {
            $allcation_type = 'AR';
        } elseif ($type == 'acknowledge') {
            $allcation_type = 'ACK';
        } else {
            $allcation_type = 'N';
        }

        $extraData = [
            'customer_name' => $customer->name,
            'customer_phone_number' => $customer->phone_number,
            'short_name' => $finalLocation->short_name,
            'address' => $finalLocation->address,
            'lat' => $finalLocation->latitude,
            'long' => $finalLocation->longitude,
            'task_count' => $taskcount,
            'unique_id' => $randem,
            'created_at' => Carbon::now()->toDateTimeString(),
            'updated_at' => Carbon::now()->toDateTimeString()
        ];

        if (!isset($geo)) {
            $oneagent = Agent::where('id', $agent_id)->first();
            if (isset($oneagent) && !empty($oneagent->device_token) && $oneagent->is_available == 1) {
                $allcation_type = 'ACK';
                $data = [
                    'order_id' => $orders_id,
                    'driver_id' => $agent_id,
                    'notification_time' => $time,
                    'type' => $allcation_type,
                    'client_code' => $auth->code,
                    'created_at' => Carbon::now()->toDateTimeString(),
                    'updated_at' => Carbon::now()->toDateTimeString(),
                    'device_type' => $oneagent->device_type ?? '',
                    'device_token' => $oneagent->device_token,
                    'detail_id' => $randem
                ];
                $this->dispatch(new RosterCreate($data, $extraData));
            }
        } else {
            $geoagents = $this->getGeoBasedAgentsData($geo, $is_cab_pooling, $agent_tag, $date, $cash_at_hand, $orders_id, $particular_driver_id);

            $geoagents = $geoagents->toArray();
            // this function give me the driver list accourding to who have liest task for the current date
            $distenseResult = $this->roundCalculation($geoagents, $finalLocation, $unit, $max_redius, $max_task);
            if (count($distenseResult) > 0) {
                for ($i = 1; $i <= $try; $i++) {
                    foreach ($distenseResult as $key => $geoitem) {
                        if (!empty($geoitem['device_token'])) {
                            $datas = [
                                'order_id' => $orders_id,
                                'driver_id' => $geoitem['driver_id'],
                                'notification_time' => $time,
                                'type' => $allcation_type,
                                'client_code' => $auth->code,
                                'created_at' => Carbon::now()->toDateTimeString(),
                                'updated_at' => Carbon::now()->toDateTimeString(),
                                'device_type' => $geoitem['device_type'] ?? '',
                                'device_token' => $geoitem['device_token'],
                                'detail_id' => $randem
                            ];

                            $time = Carbon::parse($time)->addSeconds($expriedate)->format('Y-m-d H:i:s');

                            array_push($data, $datas);
                        }

                        if ($allcation_type == 'N' && 'ACK') {
                            break;
                        }
                    }
                    $time = Carbon::parse($time)->addSeconds($expriedate + 10)->format('Y-m-d H:i:s');
                    if ($allcation_type == 'N' && 'ACK') {
                        break;
                    }
                }
                $this->dispatch(new RosterCreate($data, $extraData)); // job for insert data in roster table for send notification
            }
        }
    }

    public function roundCalculation($geoagents, $finalLocation, $unit, $max_redius, $max_task)
    {
        $extraarray = [];

        foreach ($geoagents as $item) {
            $count = isset($item['order']) ? count($item['order']) : 0;

            if ($max_task > $count) {
                $data = [
                    'driver_id' => $item['id'],
                    'device_type' => $item['device_type'] ?? '',
                    'device_token' => $item['device_token'] ?? '',
                    'task_count' => $count
                ];

                array_push($extraarray, $data);
            }
        }

        $allsort = array_values(Arr::sort($extraarray, function ($value) {
            return $value['task_count'];
        }));

        return $allsort;
    }

    public function haversineGreatCircleDistance($geoagents, $finalLocation, $unit, $max_redius, $max_task)
    {
        // $latitudeFrom, $longitudeFrom, $latitudeTo, $longitudeTo,
        // convert from degrees to radians
        $earthRadius = 6371; // earth radius in km
        $latitudeFrom = $finalLocation->latitude;
        $longitudeFrom = $finalLocation->longitude;
        $lastarray = [];
        $extraarray = [];
        foreach ($geoagents as $item) {
            $latitudeTo = $item['logs']['lat'] ?? null;
            $longitudeTo = $item['logs']['long'] ?? null;

            if (!empty($latitudeTo) && !empty($longitudeTo)) {
                if (isset($latitudeFrom) && isset($latitudeFrom) && isset($latitudeTo) && isset($longitudeTo)) {
                    $latFrom = deg2rad($latitudeFrom);
                    $lonFrom = deg2rad($longitudeFrom);
                    $latTo = deg2rad($latitudeTo);
                    $lonTo = deg2rad($longitudeTo);

                    $latDelta = $latTo - $latFrom;
                    $lonDelta = $lonTo - $lonFrom;

                    $angle = 2 * asin(sqrt(pow(sin($latDelta / 2), 2) + cos($latFrom) * cos($latTo) * pow(sin($lonDelta / 2), 2)));

                    $final = round($angle * $earthRadius);
                    $count = isset($item['order']) ? count($item['order']) : 0;
                    if ($unit == 'metric') {
                        if ($final <= $max_redius && $max_task > $count) {
                            $data = [
                                'driver_id' => $item['id'],
                                'device_type' => $item['device_type'] ?? '',
                                'device_token' => $item['device_token'] ?? '',
                                'distance' => $final
                            ];
                            array_push($extraarray, $data);
                        }
                    } else {
                        if ($final <= $max_redius && $max_task > $count) {
                            $data = [
                                'driver_id' => $item['id'],
                                'devide_type' => $item['device_type'] ?? '',
                                'device_token' => $item['device_token'] ?? '',
                                'distance' => round($final * 0.6214)
                            ];
                            array_push($extraarray, $data);
                        }
                    }
                }
            }
        }

        $allsort = array_values(Arr::sort($extraarray, function ($value) {
            return $value['distance'];
        }));

        return $allsort;
    }

    public function GoogleDistanceMatrix($latitude, $longitude)
    {
        $send = [];
        $client = ClientPreference::where('id', 1)->first();
        $lengths = count($latitude) - 1;
        $value = [];
        $count = 0;
        $count1 = 1;
        $totalDistance = 0;
        $totalDuration = 0;
        for ($i = 1; $i <= $lengths; $i++) {
            $ch = curl_init();
            $headers = array(
                'Accept: application/json',
                'Content-Type: application/json'
            );
            $url = 'https://maps.googleapis.com/maps/api/distancematrix/json?units=metric&origins=' . $latitude[$count] . ',' . $longitude[$count] . '&destinations=' . $latitude[$count1] . ',' . $longitude[$count1] . '&key=' . $client->map_key_1 . '';
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            $response = curl_exec($ch);
            $result = json_decode($response);
            curl_close($ch); // Close the connection
            $new = $result;
            if (count($result->rows) > 0) {
                // array_push($value, $result->rows[0]->elements);
                $value[] = $result->rows[0]->elements;
            }
            $count++;
            $count1++;
        }

        if (isset($value)) {

            foreach ($value as $item) {
                $totalDistance += (isset($item[0]->distance) ? $item[0]->distance->value : 0);
                $totalDuration += (isset($item[0]->duration) ? $item[0]->duration->value : 0);
            }

            if ($client->distance_unit == 'metric') {
                $send['distance'] = round($totalDistance / 1000, 2); // km
            } else {
                $send['distance'] = round($totalDistance / 1609.34, 2); // mile
            }
            //
            $newvalue = round($totalDuration / 60, 2);
            $whole = floor($newvalue);
            $fraction = $newvalue - $whole;

            if ($fraction >= 0.60) {
                $send['duration'] = $whole + 1;
            } else {
                $send['duration'] = $whole;
            }
        }

        return $send;
    }

    public function currentstatus(Request $request)
    {
        $status = Order::where('id', $request->task_id)->first();

        return response()->json([
            'task_id' => $status->id,
            'status' => $status->status
        ], 200);
    }

    /**
     * **************** ---- get delivery fees (Need to pass all latitude / longitude of pickup & drop ) ----- *****************
     */
    public function getDeliveryFee(GetDeliveryFee $request)
    {
        $latitude = [];
        $longitude = [];
        $pricingRule = '';
        $lat = $long = '';

        $auth = Client::with([
            'getAllocation',
            'getPreference',
            'getTimezone'
        ])->first();
        $client_timezone = $auth->getTimezone ? $auth->getTimezone->timezone : 251;
        $tz = new Timezone();

        $timezone = $tz->timezone_name($client_timezone);

        foreach ($request->locations as $key => $value) {
            if (empty($value['latitude']) || empty($value['longitude']))
                return response()->json([
                    'message' => 'Pickup and Dropoff location required.'
                ], 404);
            array_push($latitude, $value['latitude'] ?? 0.0000);
            array_push($longitude, $value['longitude'] ?? 0.0000);
            if ($lat == '' && $long == '') :
                $lat = $value['latitude'] ?? 0.0000;
                $long = $value['longitude'] ?? 0.0000;
            endif;
        }

        // get geoid based on customer location

        $geoid = '';
        if (($lat != '' || $lat != '0.0000') && ($long != '' || $long != '0.0000')) :
            $geoid = $this->findLocalityByLatLng($lat, $long);
        endif;

        // get pricing rule for save with every order based on geo fence and agent tags

        if (!empty($request->schedule_datetime_del)) :
            $order_datetime = Carbon::parse($request->schedule_datetime_del, 'UTC')->setTimezone($timezone)->toDateTimeString();
        else :
            $order_datetime = Carbon::now()->timezone($timezone)->toDateTimeString();
        endif;


        $agent_tags = (isset($request->agent_tag) && !empty($request->agent_tag)) ? $request->agent_tag : '';


        if ($auth->getPreference->toll_fee == 1) {
            $getdata = $this->toll_fee($latitude, $longitude, (isset($request->toll_passes) ? $request->toll_passes : ''), (isset($request->VehicleEmissionType) ? $request->VehicleEmissionType : ''), (isset($request->travelMode) ? $request->travelMode : ''));
        } else {
            $getdata = $this->GoogleDistanceMatrix($latitude, $longitude);
        }

        $pricingRule = $this->getPricingRuleData($geoid, $agent_tags, $order_datetime);
        $pricingRuleDistanceWise = $this->getPricingRuleDynamic($pricingRule, $getdata['distance']);


        if(empty($pricingRule)){
            $pricingRule = PricingRule::where('is_default', 1)->first();
        }

        $paid_duration = $getdata['duration'] - $pricingRule->base_duration;
        $paid_distance = $getdata['distance'] - $pricingRule->base_distance;
        $paid_duration = $paid_duration < 0 ? 0 : $paid_duration;
        $paid_distance = $paid_distance < 0 ? 0 : $paid_distance;


        if ($pricingRuleDistanceWise) {
            $total         = $pricingRule->base_price + ($pricingRuleDistanceWise) + ($paid_duration * $pricingRule->duration_price);
            //\Log::info($pricingRule->base_price.' +  '.$pricingRuleDistanceWise.' + '.($paid_duration * $pricingRule->duration_price));
        } else {
            $total         = $pricingRule->base_price + ($paid_distance * $pricingRule->distance_fee) + ($paid_duration * $pricingRule->duration_price);
        }



        //-------------------for bid and ride---------------------
        if (isset($pricingRule->base_price_minimum)) {
            $total_minimum = $pricingRule->base_price_minimum + ($paid_distance * $pricingRule->distance_fee_minimum) + ($paid_duration * $pricingRule->duration_price_minimum);
            $total_maximum = $pricingRule->base_price_maximum + ($paid_distance * $pricingRule->distance_fee_maximum) + ($paid_duration * $pricingRule->duration_price_maximum);
        } else {
            $total_minimum = 0;
            $total_maximum = 0;
        }

        $client = ClientPreference::take(1)->with('currency')->first();
        $currency = $client->currency ?? '';

        return response()->json([
            'total' => $total,
            'total_duration' => $getdata['duration'],
            'total_distance' => $getdata['distance'],
            'currency' => $currency,
            'paid_distance' => $paid_distance,
            'paid_duration' => $paid_duration,
            'total_minimum' => $total_minimum,
            'total_maximum' => $total_maximum,
            'toll_fee' => (isset($getdata['toll_amount']) ? $getdata['toll_amount'] : 0),
            'message' => __('success')
        ], 200);
    }
    public function getDeliveryFeeRental(Request $request)
    {
        $latitude = [];
        $longitude = [];
        $pricingRule = '';
        $lat = $long = '';

        $auth = Client::with([
            'getAllocation',
            'getPreference',
            'getTimezone'
        ])->first();
        $client_timezone = $auth->getTimezone ? $auth->getTimezone->timezone : 251;
        $tz = new Timezone();

        $timezone = $tz->timezone_name($client_timezone);

        
        // get geoid based on customer location

       
        $agent_tags = (isset($request->agent_tag) && !empty($request->agent_tag)) ? $request->agent_tag : '';


        $pricingRule = PricingRule::where('is_default', 1)->first();
        

        $paid_duration = $pricingRule->base_duration;
        $paid_distance = $pricingRule->base_distance;
        $paid_duration = $paid_duration < 0 ? 0 : $paid_duration;
        $paid_distance = $paid_distance < 0 ? 0 : $paid_distance;


         $total         = $pricingRule->base_price + ($paid_distance * $pricingRule->distance_fee) + ($paid_duration * $pricingRule->duration_price);
        



        //-------------------for bid and ride---------------------
        if (isset($pricingRule->base_price_minimum)) {
            $total_minimum = $pricingRule->base_price_minimum + ($paid_distance * $pricingRule->distance_fee_minimum) + ($paid_duration * $pricingRule->duration_price_minimum);
            $total_maximum = $pricingRule->base_price_maximum + ($paid_distance * $pricingRule->distance_fee_maximum) + ($paid_duration * $pricingRule->duration_price_maximum);
        } else {
            $total_minimum = 0;
            $total_maximum = 0;
        }

        $client = ClientPreference::take(1)->with('currency')->first();
        $currency = $client->currency ?? '';

        return response()->json([
            'total' => $total,
            'total_duration' => 0,
            'total_distance' => 0,
            'currency' => $currency,
            'paid_distance' => $paid_distance,
            'paid_duration' => $paid_duration,
            'total_minimum' => $total_minimum,
            'total_maximum' => $total_maximum,
            'toll_fee' => 0,
            'message' => __('success')
        ], 200);
    }

    /**
     * **************** ---- get agents tags ----- *****************
     */
    public function getAgentTags(Request $request)
    {
        $email_set = $request->email_set ?? '';
        if (!empty($email_set))
            $user = Client::where('email', $request->email_set)->first();

        $tags = TagsForAgent::OrderBy('id', 'desc');
        if (isset($user) && $user->is_superadmin == 0 && $user->all_team_access == 0) {
            $tags = $tags->whereHas('assignTags.agent.team.permissionToManager', function ($query) use ($user) {
                $query->where('sub_admin_id', $user->id);
            });
        }

        $tags = $tags->get();

        return response()->json([
            'tags' => $tags,
            'message' => __('success')
        ], 200);
    }

    /**
     * **************** ---- check Keys from Dispatcher keys ----- *****************
     */
    public function checkDispatcherKeys(Request $request)
    {
        return response()->json([
            'status' => 200,
            'message' => 'Valid Dispatcher API keys'
        ]);
    }

    public function checkDispatchPanelKeys(Request $request)
    {
        $user = Client::first();

        if (!empty($user)) {
            $token1 = new Token();
            $token = $token1->make([
                'key' => 'royodispatch-jwt',
                'issuer' => 'royodelivery.com',
                'expiry' => strtotime('+2 hour'),
                'issuedAt' => time(),
                'algorithm' => 'HS256'
            ])->get();
            $token1->setClaim('user_id', $user->id);

            // $device = UserDevice::updateOrCreate([
            // 'device_token' => 'dispather-login'
            // ], [
            // 'user_id' => $user->id,
            // 'device_type' => 'web',
            // 'access_token' => $token,
            // 'is_vendor_app' => 0
            // ]);
            return response()->json([
                'status' => 200,
                'token' => $token,
                'message' => 'Valid Dispatch Panel API keys'
            ]);
        }
        return response()->json([
            'status' => 401,
            'message' => 'Authentication failed'
        ]);
    }

    public function getDispatchPanelDetails(Request $request)
    {
        try {
            if ($request->inventory_url) {

                $inventory_url = $request->inventory_url;
                $inventory_code = $request->inventory_code;

                $client = Client::select('database_name')->where('id', '>', 0)->first();

                if ($client) {
                    $client_prefrence = ClientPreference::where('id', '>', 0)->first();

                    $client_prefrence->inventory_service_key_url = $inventory_url;
                    $client_prefrence->inventory_service_key_code = $inventory_code;
                    $client_prefrence->update();

                    $dispatch_panel = OrderPanelDetail::where('type', '1')->first();

                    if (@$dispatch_panel) {
                        $dispatch_panel->name = 'bharat';
                        $dispatch_panel->url = $inventory_url;
                        $dispatch_panel->key = $inventory_code;
                        $dispatch_panel->code = $inventory_code;
                        $dispatch_panel->type = 1;

                        $dispatch_panel->save();
                    } else {

                        OrderPanelDetail::create([
                            'code' => $inventory_code,
                            'name' => 'bharat',
                            'url' => $inventory_url,
                            'key' => $inventory_code,
                            'type' => 1,
                            'status' => 1
                        ]);
                    }
                    $data = [
                        'key' => $client->database_name
                    ];

                    return response()->json([
                        'status' => 200,
                        'data' => $data,
                        'message' => 'success'
                    ]);
                }

                return response()->json([
                    'status' => 400,
                    'message' => 'Order Panel Not found'
                ]);
            }
            return response()->json([
                'status' => 400,
                'message' => 'Invalid Code'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'data' => $e->getMessage()
            ]);
        }
    }

    /**
     * **************** ---- get all teams ----- *****************
     */
    public function getAllTeams(Request $request)
    {
        $teams = TagsForTeam::OrderBy('id', 'desc')->get();
        $all_teams = Team::OrderBy('id', 'desc')->get();

        return response()->json([
            'teams' => $teams,
            'all_teams' => $all_teams,
            'message' => __('success')
        ], 200);
    }

    /**
     * **************** ---- Save feedback on order ----- *****************
     */
    public function SaveFeedbackOnOrder(Request $request)
    {
        $order = Order::where('id', $request->order_id)->first();

        if (isset($order->id)) {
            $check_alredy = DB::table('order_ratings')->where('order_id', $order->id)->first();

            if (isset($check_alredy->id)) {
                return response()->json([
                    'status' => true,
                    'message' => __('Feedback has been already submitted')
                ]);
            } else {
                $data = [
                    'order_id' => $order->id,
                    'rating' => $request->rating,
                    'review' => $request->review
                ];

                DB::table('order_ratings')->insert($data);

                return response()->json([
                    'status' => true,
                    'message' => __('Your feedback is submitted')
                ]);
            }
        } else {
            return response()->json([
                'status' => true,
                'message' => __('Order Not Found')
            ]);
        }
    }

    /**
     * **************** ---- upload Image For Task ----- *****************
     */
    public function uploadImageForTask(Request $request)
    {
        if ($request->hasFile('upload_photo')) {
            $header = $request->header();
            if (array_key_exists("shortcode", $header)) {
                $shortcode = $header['shortcode'][0];
            }
            $folder = str_pad($shortcode, 8, '0', STR_PAD_LEFT);
            $folder = 'client_' . $folder;
            $file = $request->file('upload_photo');
            $file_name = uniqid() . '.' . $file->getClientOriginalExtension();
            $s3filePath = '/assets/' . $folder;
            $path = Storage::disk('s3')->put($s3filePath, $file, 'public');
            $getFileName = $path;
        }

        if (isset($getFileName)) {
            return response()->json([
                'status' => true,
                'message' => __('Image submitted'),
                'image' => $getFileName
            ]);
        } else {
            return response()->json([
                'status' => true,
                'message' => __('Error in upload image')
            ]);
        }
    }




    # notification data
    public function notificationTrackingDetail(Request $request, $id)
    {
        // sleep(2);
        $order = Order::with('additionData')->where('id', $id)->first();
        $user_id = Auth::id();
        $client_prefrence = ClientPreference::where('id', '>', 0)->first();
        if (isset($order->id)) {


            $customer = DB::table('customers')->where('id', $order->customer_id)->first();
            $order->order_cost = $order->cash_to_be_collected ?? $order->order_cost;

                if($client_prefrence->is_dispatcher_allocation == 1 )
                {
                    $tasks = DB::table('tasks')->where('order_id', $order->id)->where('task_status',0)->leftJoin('locations', 'tasks.location_id', '=', 'locations.id')
                    ->select('tasks.*', 'locations.latitude', 'locations.longitude', 'locations.short_name', 'locations.address')->orderBy('id')->limit(2)->get();

                }else{
                     $tasks = DB::table('tasks')->where('order_id', $order->id)->leftJoin('locations', 'tasks.location_id', '=', 'locations.id')
                    ->select('tasks.*', 'locations.latitude', 'locations.longitude', 'locations.short_name', 'locations.address')->orderBy('id')->get();


                }
                $db_name = client::select('database_name')->orderBy('id', 'asc')->first()->database_name;

                return response()->json([
                'message' => 'Successfully',
                'tasks' => $tasks,
                'order'  => $order,
                'customer'  => $customer,
                'agent_dbname'  => $db_name
            ], 200);
        } else {
            return response()->json([
                'message' => 'Error'
            ], 400);
        }
    }

    /**
     * notify driver for user response on edit order approval
     */
    public function editOrderNotification(Request $request)
    {
        try {
            if (($request->has('web_hook_code')) && ($request->has('status'))) {
                $web_hook_code = $request->web_hook_code;
                $status = $request->status;
                if ($status == 1 || $status == 2) {
                    $order = Order::where('call_back_url', 'LIKE', '%' . $web_hook_code . '%')->first();
                    if ($order) {
                        $driver_id = $order->driver_id;
                        $device_token = Agent::where('id', $driver_id)->value('device_token');

                        $new = [];
                        array_push($new, $device_token);

                        $item['title'] = 'Edit Order Status';
                        $item['body'] = 'Check Status of Edit Order Approval';
                        $item['status'] = $status;
                        $item['callback_url'] = $order->call_back_url;

                        $client_preferences = ClientPreference::where('id', 1)->first();
                        if (count($new)) {
                            $fcm_server_key = !empty($client_preferences->fcm_server_key) ? $client_preferences->fcm_server_key : config('laravel-fcm.server_key');

                            $fcmObj = new Fcm($fcm_server_key);
                            $fcm_store = $fcmObj->to($new)
                                ->
                                // $recipients must an array
                                priority('high')
                                ->timeToLive(0)
                                ->data($item)
                                ->notification([
                                    'title' => 'Edit Order Status',
                                    'body' => 'Check Status of Edit Order Approval',
                                    'sound' => 'notification',
                                    'android_channel_id' => 'Royo-Delivery',
                                    'soundPlay' => true,
                                    'show_in_foreground' => true
                                ])
                                ->send();

                            return $fcm_store;
                        }
                    }
                }
            } else {
                return response()->json([
                    'data' => [],
                    'status' => 422,
                    'message' => 'Invalid Data'
                ]);
            }
        } catch (Exception $e) {
            Log::info($e->getMessage());
            return response()->json([
                'data' => [],
                'status' => $e->getCode(),
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * notify driver for user response on edit order approval
     */
    public function cancelOrderRequestStatusNotification(Request $request)
    {
        try {
            if (($request->has('web_hook_code')) && ($request->has('status'))) {
                $web_hook_code = $request->web_hook_code;
                $status = $request->status;
                if ($status == 1 || $status == 2) {
                    $order = Order::where('call_back_url', 'LIKE', '%' . $web_hook_code . '%')->first();
                    if ($order) {
                        $driver_id = $order->driver_id;
                        $device_token = Agent::where('id', $driver_id)->value('device_token');

                        $new = [];
                        array_push($new, $device_token);

                        $item['title'] = 'Cancel Order Status';
                        $item['body'] = 'Check Status of Cancel Order Request';
                        $item['status'] = $status;
                        $item['callback_url'] = $order->call_back_url;

                        $client_preferences = ClientPreference::where('id', 1)->first();
                        if (count($new)) {
                            $fcm_server_key = !empty($client_preferences->fcm_server_key) ? $client_preferences->fcm_server_key : config('laravel-fcm.server_key');

                            $fcmObj = new Fcm($fcm_server_key);
                            $fcm_store = $fcmObj->to($new)
                                ->
                                // $recipients must an array
                                priority('high')
                                ->timeToLive(0)
                                ->data($item)
                                ->notification([
                                    'title' => 'Cancel Order Status',
                                    'body' => 'Check Status of Cancel Order Request',
                                    'sound' => 'notification',
                                    'android_channel_id' => 'Royo-Delivery',
                                    'soundPlay' => true,
                                    'show_in_foreground' => true
                                ])
                                ->send();

                            return $fcm_store;
                        }
                    }
                }
            } else {
                return response()->json([
                    'data' => [],
                    'status' => 422,
                    'message' => 'Invalid Data'
                ]);
            }
        } catch (Exception $e) {
            Log::info($e->getMessage());
            return response()->json([
                'data' => [],
                'status' => $e->getCode(),
                'message' => $e->getMessage()
            ]);
        }
    }

    public function returnToWarehouseTask(Request $request)
    {
        try {
            $header = $request->header();
            if (isset($header['client'][0])) {
            } else {
                $client = Client::with([
                    'getAllocation',
                    'getPreference'
                ])->first();
                $header['client'][0] = $client->database_name;
            }

            // Check agent task is pending
            $pending_task = Order::with('task')->whereHas('task', function ($query) {
                $query->whereDate('created_at', date('Y-m-d'));
                $query->where('is_return', 1);
                $query->where('task_status', '!=', 4);
            })
                ->where('driver_id', Auth::id())
                ->whereDate('created_at', date('Y-m-d'))
                ->get();

            // If task is pending then return message
            if ($pending_task->isNotEmpty()) {
                // return response()->json([
                // 'message' => 'Return to warehouse task is already created'
                // ], 400);
            }

            // Get order location

            $order = Order::with([
                'task' => function ($q) {
                    $q->where('task_type_id', 1); // 1 is treated as pickup
                    $q->whereDate('created_at', date('Y-m-d'));
                },
                'task.location'
            ])->whereDate('created_at', date('Y-m-d'))
                ->where('driver_id', Auth::id())
                ->latest()
                ->first();

            // If Anything is empty then return error message
            // if( ! (!empty($order) && !empty($order->task) && !empty($order->task[0]) && !empty($order->task[0]->location)) )
            // {
            // return response()->json([
            // 'message' => 'Location is not available'
            // ], 400);
            // }

            // $customer = $order->task[0]->location;

            // todo find customer on the basis of email
            // $customer = $this->cutomer->getUserByEmail('7018451175@bharatmove.in');

            // set these variables
            // $request->customer_name = $customer->short_name ?? '';
            // $request->customer_phone_number = $customer->phone_number ?? '';
            // $request->customer_email = $customer->email ?? '';
            // $request->recipient_phone = $customer->phone_number ?? '';
            // $request->recipient_email = $customer->email ?? '';
            // end

            $request->task_description = "Return to warehouse";
            $request->allocation_type = "m";
            $request->task_type = "now";
            $request->schedule_time = "NULL";
            $request->cash_to_be_collected = 0;
            $request->barcode = "";
            $request->order_team_tag = "NULL";

            $request->agent = Auth::id();

            if ($request->task_type == 'later')
                $request->task_type = 'schedule';

            DB::beginTransaction();

            $auth = Client::with([
                'getAllocation',
                'getPreference'
            ])->first();
            $tz = new Timezone();

            if (isset($request->order_time_zone) && !empty($request->order_time_zone))
                $auth->timezone = $request->order_time_zone;
            else
                $auth->timezone = $tz->timezone_name($auth->timezone);

            $loc_id = $cus_id = $send_loc_id = $newlat = $newlong = 0;
            $images = [];
            $last = '';
            $customer = [];
            $finalLocation = [];
            $taskcount = 0;
            $latitude = [];
            $longitude = [];
            $percentage = 0;
            $pricingRule = '';

            $pool = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';

            $unique_order_id = substr(str_shuffle(str_repeat($pool, 5)), 0, 6);

            // save task images on s3 bucket
            if (isset($request->file) && count($request->file) > 0) {
                $folder = str_pad(Auth::user()->id, 8, '0', STR_PAD_LEFT);
                $folder = 'client_' . $folder;
                $files = $request->file('file');
                foreach ($files as $key => $value) {
                    $file = $value;
                    $file_name = uniqid() . '.' . $file->getClientOriginalExtension();

                    $s3filePath = '/assets/' . $folder . '/' . $file_name;
                    $path = Storage::disk('s3')->put($s3filePath, $file, 'public');
                    array_push($images, $path);
                }
                $last = implode(",", $images);
            }
            # string of image array
            if (isset($request->images_array) && count($request->images_array) > 0) {

                foreach ($request->images_array as $key => $path) {
                    array_push($images, $path);
                }

                $last = implode(",", $images);
            }

            // create new customer for task or get id of old customer

            if (isset($request->customer_email) || isset($request->customer_phone_number)) {
                $customer = Customer::where('email', '=', $request->customer_email)->orWhere('phone_number', $request->customer_phone_number)->first();
                if (isset($customer->id)) {
                    $cus_id = $customer->id;
                } else {
                    $cus = [
                        'name' => $request->customer_name,
                        'email' => $request->customer_email,
                        'phone_number' => $request->customer_phone_number
                    ];
                    $customer = Customer::create($cus);
                    $cus_id = $customer->id;
                }
            } else {
                // $cus_id = $request->ids;
                // $customer = Customer::where('id',$request->ids)->first();
            }

            // get pricing rule for save with every order
            if (isset($request->order_agent_tag) && !empty($request->order_agent_tag))
                $pricingRule = PricingRule::orderBy('id', 'desc')->whereHas('tagsForAgent', function ($q) use ($request) {
                    $q->where('name', $request->order_agent_tag);
                })
                    ->first();

            $settime = ($request->task_type == "schedule") ? $request->schedule_time : Carbon::now()->toDateTimeString();
            $notification_time = ($request->task_type == "schedule") ? Carbon::parse($settime)->tz('UTC') : Carbon::now()->toDateTimeString();

            //get pricing rule  for save with every order
            $agent_tags = (isset($request->order_agent_tag) && !empty($request->order_agent_tag)) ? $request->order_agent_tag : '';
            $pricingRule = $this->getPricingRuleData($geoid, $agent_tags, $this->getConvertUTCToLocalTime($notification_time, $auth->timezone));





            //here order save code is started
            $agent_id          = $request->allocation_type === 'm' ? $request->agent : null;


            $order = [
                'order_number' => $request->order_number ?? null,
                'customer_id' => $cus_id,
                'recipient_phone' => $request->recipient_phone,
                'Recipient_email' => $request->recipient_email,
                'task_description' => $request->task_description,
                'driver_id' => $agent_id,
                'auto_alloction' => $request->allocation_type,
                'images_array' => $last,
                'order_type' => $request->task_type,
                'order_time' => $notification_time,
                'status' => $agent_id != null ? 'assigned' : 'unassigned',
                'cash_to_be_collected' => $request->cash_to_be_collected,
                'base_price' => $pricingRule->base_price,
                'base_duration' => $pricingRule->base_duration,
                'base_distance' => $pricingRule->base_distance,
                'base_waiting' => $pricingRule->base_waiting,
                'duration_price' => $pricingRule->duration_price,
                'waiting_price' => $pricingRule->waiting_price,
                'distance_fee' => $pricingRule->distance_fee,
                'cancel_fee' => $pricingRule->cancel_fee,
                'agent_commission_percentage' => $pricingRule->agent_commission_percentage,
                'agent_commission_fixed' => $pricingRule->agent_commission_fixed,
                'freelancer_commission_percentage' => $pricingRule->freelancer_commission_percentage,
                'freelancer_commission_fixed' => $pricingRule->freelancer_commission_fixed,
                'unique_id' => $unique_order_id,
                'call_back_url' => $request->call_back_url ?? null,
                'royo_order_number' => $request->royo_order_number
            ];

            $orders = Order::create($order);

            if ($auth->custom_domain && !empty($auth->custom_domain)) {
                $client_url = "https://" . $auth->custom_domain;
            } else {
                $client_url = "https://" . $auth->sub_domain . \env('SUBDOMAIN');
            }
            $dispatch_traking_url = $client_url . '/order/tracking/' . $auth->code . '/' . $orders->unique_id;

            $dep_id = null;

            foreach ($request->task as $key => $value) {
                $taskcount++;
                if (isset($value)) {
                    $post_code = isset($value['post_code']) ? $value['post_code'] : '';
                    $loc = [
                        'latitude' => $value['latitude'] ?? 0.00,
                        'longitude' => $value['longitude'] ?? 0.00,
                        'address' => $value['address'] ?? null,
                        'customer_id' => $cus_id
                    ];
                    $loc_update = [
                        'short_name' => $value['short_name'] ?? null,
                        'post_code' => (int) $post_code,
                        'flat_no' => $value['flat_no'] ?? null,
                        'email' => $value['email'] ?? null,
                        'phone_number' => $value['phone_number'] ?? null
                    ];

                    // $Loction = Location::create($loc);
                    $Loction = Location::updateOrCreate($loc, $loc_update);
                    $loc_id = $Loction->id;
                }

                // if ($key == 0) {
                $send_loc_id = $loc_id;
                $finalLocation = Location::where('id', $loc_id)->first();
                // }
                if (isset($finalLocation)) {
                    array_push($latitude, $finalLocation->latitude);
                    array_push($longitude, $finalLocation->longitude);
                }

                $task_appointment_duration = isset($value->appointment_duration) ? $value->appointment_duration : null;

                $data = [
                    'order_id' => $orders->id,
                    'task_type_id' => $value['task_type_id'],
                    'location_id' => $loc_id,
                    'appointment_duration' => $task_appointment_duration,
                    'dependent_task_id' => $dep_id,
                    'task_status' => $agent_id != null ? 1 : 0,
                    'allocation_type' => $request->allocation_type,
                    'assigned_time' => $notification_time,
                    'barcode' => $value['barcode'] ?? null,
                    'current_lat' => $request->current_lat ?? '',
                    'current_long' => $request->current_long ?? '',
                    'is_return' => 1
                ];

                $task = Task::create($data);
                $dep_id = $task->id;
            }

            // accounting for task duration distanse

            $getdata = $this->GoogleDistanceMatrix($latitude, $longitude);

            $paid_duration = $getdata['duration'] - $pricingRule->base_duration;
            $paid_distance = $getdata['distance'] - $pricingRule->base_distance;
            $paid_duration = $paid_duration < 0 ? 0 : $paid_duration;
            $paid_distance = $paid_distance < 0 ? 0 : $paid_distance;
            $total = $pricingRule->base_price + ($paid_distance * $pricingRule->distance_fee) + ($paid_duration * $pricingRule->duration_price);

            if (isset($agent_id)) {
                $agent_details = Agent::where('id', $agent_id)->first();
                if ($agent_details->type == 'Employee') {
                    $percentage = $pricingRule->agent_commission_fixed + (($total / 100) * $pricingRule->agent_commission_percentage);
                } else {
                    $percentage = $pricingRule->freelancer_commission_percentage + (($total / 100) * $pricingRule->freelancer_commission_fixed);
                }
            }

            // update order with order cost details

            $updateorder = [
                'actual_time' => $getdata['duration'],
                'actual_distance' => $getdata['distance'],
                'order_cost' => $total,
                'driver_cost' => $percentage
            ];

            Order::where('id', $orders->id)->update($updateorder);

            if (isset($request->allocation_type) && $request->allocation_type === 'a') {
                // if (isset($request->team_tag)) {
                // $orders->teamtags()->sync($request->team_tag);
                // }
                // if (isset($request->agent_tag)) {
                // $orders->drivertags()->sync($request->agent_tag);
                // }
            }
            if (isset($request->order_team_tag)) {

                $value = $request->order_team_tag;
                $tag_id = [];
                if (!empty($value)) {
                    $check = TagsForTeam::firstOrCreate([
                        'name' => $value
                    ]);
                    array_push($tag_id, $check->id);
                }
                $orders->teamtags()->sync($tag_id);
            }

            if (isset($request->order_agent_tag)) {

                $value = $request->order_agent_tag;
                $tag_id = [];
                if (!empty($value)) {
                    $check = TagsForAgent::firstOrCreate([
                        'name' => $value
                    ]);
                    array_push($tag_id, $check->id);
                }
                $orders->drivertags()->sync($tag_id);
            }

            $geo = null;
            if ($request->allocation_type === 'a') {
                $geo = $this->createRoster($send_loc_id);

                $agent_id = null;
            }

            // task schdule code is hare

            $allocation = AllocationRule::where('id', 1)->first();

            if ($request->task_type != 'now') {
                if (isset($header['client'][0]))
                    $auth = Client::where('database_name', $header['client'][0])->with([
                        'getAllocation',
                        'getPreference'
                    ])->first();
                else
                    $auth = Client::with([
                        'getAllocation',
                        'getPreference'
                    ])->first();
                // setting timezone from id

                $dispatch_traking_url = $client_url . '/order/tracking/' . $auth->code . '/' . $orders->unique_id;

                $tz = new Timezone();
                $auth->timezone = $tz->timezone_name($auth->timezone);

                $beforetime = (int) $auth->getAllocation->start_before_task_time;
                // $to = new \DateTime("now", new \DateTimeZone(isset(Auth::user()->timezone)? Auth::user()->timezone : 'Asia/Kolkata') );
                $to = new \DateTime("now", new \DateTimeZone('UTC'));
                $sendTime = Carbon::now();
                $to = Carbon::parse($to)->format('Y-m-d H:i:s');
                $from = Carbon::parse($notification_time)->format('Y-m-d H:i:s');
                $datecheck = 0;
                $to_time = strtotime($to);
                $from_time = strtotime($from);
                if ($to_time >= $from_time) {
                    DB::commit();
                    return response()->json([
                        'message' => __('Task Added Successfully'),
                        'task_id' => $orders->id,
                        'status' => $orders->status,
                        'dispatch_traking_url' => $dispatch_traking_url ?? null
                    ], 200);
                }

                $diff_in_minutes = round(abs($to_time - $from_time) / 60);

                $schduledata = [];

                if ($diff_in_minutes > $beforetime) {
                    $finaldelay = (int) $diff_in_minutes - $beforetime;

                    $time = Carbon::parse($sendTime)->addMinutes($finaldelay)->format('Y-m-d H:i:s');

                    $schduledata['geo'] = $geo;
                    // $schduledata['notification_time'] = $time;
                    $schduledata['notification_time'] = $notification_time;
                    $schduledata['agent_id'] = $agent_id;
                    $schduledata['orders_id'] = $orders->id;
                    $schduledata['customer'] = $customer;
                    $schduledata['finalLocation'] = $finalLocation;
                    $schduledata['taskcount'] = $taskcount;
                    $schduledata['allocation'] = $allocation;
                    $schduledata['database'] = $auth;
                    $schduledata['cash_to_be_collected'] = $orders->cash_to_be_collected;

                    // Order::where('id',$orders->id)->update(['order_time'=>$time]);
                    // Task::where('order_id',$orders->id)->update(['assigned_time'=>$time,'created_at' =>$time]);

                    scheduleNotification::dispatch($schduledata)->delay(now()->addMinutes($finaldelay));
                    DB::commit();

                    return response()->json([
                        'message' => __('Task Added Successfully'),
                        'task_id' => $orders->id,
                        'status' => $orders->status,
                        'dispatch_traking_url' => $dispatch_traking_url ?? null
                    ], 200);
                }
            }
            DB::commit();
            // this is roster create accounding to the allocation methed

            if ($request->allocation_type === 'a' || $request->allocation_type === 'm') {
                $allocation = AllocationRule::where('id', 1)->first();
                $is_cab_pooling = $is_order_updated = 0;
                $agent_tag = '';
                $is_one_push_booking = 0;
                switch ($allocation->auto_assign_logic) {
                    case 'one_by_one':
                        //this is called when allocation type is one by one
                        $this->OneByOne($geo, $notification_time, $agent_id, $orders->id, $customer, $finalLocation, $taskcount, $header, $allocation, $is_cab_pooling, $agent_tag, $is_order_updated, $is_one_push_booking);
                        break;
                    case 'send_to_all':
                        //this is called when allocation type is send to all
                        $this->SendToAll($geo, $notification_time, $agent_id, $orders->id, $customer, $finalLocation, $taskcount, $header, $allocation, $is_cab_pooling, $agent_tag, $is_order_updated, $is_one_push_booking);
                        break;
                    case 'round_robin':
                        //this is called when allocation type is round robin
                        $this->roundRobin($geo, $notification_time, $agent_id, $orders->id, $customer, $finalLocation, $taskcount, $header, $allocation, $is_cab_pooling, $agent_tag, $is_order_updated, $is_one_push_booking);
                        break;
                    default:
                        //this is called when allocation type is batch wise
                        $this->batchWise($geo, $notification_time, $agent_id, $orders->id, $customer, $finalLocation, $taskcount, $header, $allocation, $is_cab_pooling, $agent_tag, $is_order_updated, $is_one_push_booking);
                }
            }
            $dispatch_traking_url = $client_url . '/order/tracking/' . $auth->code . '/' . $orders->unique_id;

            return response()->json([
                'message' => __('Task Added Successfully'),
                'task_id' => $orders->id,
                'status' => $orders->status,
                'dispatch_traking_url' => $dispatch_traking_url ?? null
            ], 200);
        } catch (Exception $e) {
            DB::rollback();
            Log::info($e->getMessage());
            return response()->json([
                'message' => $e->getMessage()
            ], 400);
        }
    }

    public function roadsidePickup(CreateTaskRequest $request)
    {
        try {

            $auth = $client = Client::with([
                'getAllocation',
                'getPreference'
            ])->first();
            $header = $request->header();
            if (isset($header['client'][0])) {
            } else {
                // $client = Client::with(['getAllocation', 'getPreference'])->first();
                $header['client'][0] = $client->database_name;
            }

            if ($request->task_type == 'later')
                $request->task_type = 'schedule';

            DB::beginTransaction();

            // $auth = Client::with(['getAllocation', 'getPreference'])->first();
            $tz = new Timezone();

            if (isset($request->order_time_zone) && !empty($request->order_time_zone))
                $auth->timezone = $request->order_time_zone;
            else
                $auth->timezone = $tz->timezone_name($auth->timezone);

            $loc_id = $cus_id = $send_loc_id = $newlat = $newlong = 0;
            $last = '';
            $customer = [];
            $finalLocation = [];
            $taskcount = 0;
            $latitude = [];
            $longitude = [];
            $percentage = 0;
            $pricingRule = '';

            $pool = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';

            $unique_order_id = substr(str_shuffle(str_repeat($pool, 5)), 0, 6);

            // create new customer for task or get id of old customer
            if (isset($request->customer_email) || isset($request->customer_phone_number)) {
                $dialCode = $request->customer_dial_code ?? null;
                $customerNo = $dialCode . $request->customer_phone_number;
                $customer = Customer::where('email', $request->customer_email)->orWhere(function ($q) use ($customerNo) {
                    $q->whereRaw("CONCAT(dial_code, '', phone_number) = '" . $customerNo . "'")
                        ->orWhere('phone_number', '+' . $customerNo);
                })
                    ->first();

                if (isset($customer->id)) {
                    $cus_id = $customer->id;
                    // check is number is different then update custom phone number
                    if ($request->customer_phone_number != "") {
                        $customer_phone_number = [
                            'phone_number' => $request->customer_phone_number,
                            'dial_code' => $dialCode
                        ];
                        Customer::where('id', $cus_id)->update($customer_phone_number);
                    }
                } else {
                    $cus = [
                        'name' => $request->customer_name,
                        'email' => $request->customer_email,
                        'phone_number' => $request->customer_phone_number,
                        'dial_code' => $dialCode
                    ];

                    $customer = Customer::create($cus);
                    // dd($customer);
                    $cus_id = $customer->id;
                }
            } else {
            }

            // here order save code is started
            $settime = Carbon::now()->toDateTimeString();
            $agent_id = auth::user()->id;

            $order = [
                'order_number' => generateOrderNo(),
                'customer_id' => $cus_id,
                'driver_id' => $agent_id,
                'auto_alloction' => $request->allocation_type,
                'order_type' => $request->task_type,
                'order_time' => $settime,
                'status' => 'assigned',
                'unique_id' => $unique_order_id,
                'type' => $request->type ?? 0,
                'request_type' => $request->request_type ?? 'P',
                'is_restricted' => $request->is_restricted ?? 0,
                'vendor_id' => $request->vendor_id,
                'order_vendor_id' => $request->order_vendor_id,
                'dbname' => $request->dbname,
                'sync_order_id' => $request->order_id,
                'is_roadside_pickup' => 1
            ];
            $orders = Order::create($order);
            // dd($orders);
            /**
             * booking for appointment
             * task_type_id =3= appointment type
             * is_driver_slot check slotting enabled or not
             */
            if (($request->has('task_type_id') && $request->task_type_id == 3) && $auth->getPreference->is_driver_slot == 1) {
                $data = $request->all();
                $data['order_id'] = $orders->id;
                $data['order_number'] = $orders->order_number;
                $data['booking_type'] = 'new_booking';
                $data['memo'] = __("Booked for Order number:") . $orders->order_number;

                $bookingResponse = $this->SlotBooking($data);
            }
            if ($request->is_restricted == 1) {
                $add_resource = CustomerVerificationResource::updateOrCreate([
                    'customer_id' => $cus_id
                ], [
                    'verification_type' => $request->user_verification_type,
                    'datapoints' => json_encode($request->user_datapoints)
                ]);
            }

            if ($auth->custom_domain && !empty($auth->custom_domain)) {
                $client_url = "https://" . $auth->custom_domain;
            } else {
                $client_url = "https://" . $auth->sub_domain . \env('SUBDOMAIN');
            }
            $dispatch_traking_url = $client_url . '/order/tracking/' . $auth->code . '/' . $orders->unique_id;

            $dep_id = null;
            $pickup_location = null;

            foreach ($request->task as $key => $value) {
                $taskcount++;
                $loc_id = null;
                if (isset($value)) {
                    $post_code = isset($value['post_code']) ? $value['post_code'] : '';
                    $loc = [
                        'latitude' => $value['latitude'] ?? 0.00,
                        'longitude' => $value['longitude'] ?? 0.00,
                        'address' => $value['address'] ?? null,
                        'customer_id' => $cus_id
                    ];
                    $loc_update = [
                        'short_name' => $value['short_name'] ?? null,
                        'post_code' => $post_code,
                        'flat_no' => $value['flat_no'] ?? null,
                        'email' => $value['email'] ?? null,
                        'phone_number' => $value['phone_number'] ?? null
                    ];

                    $Loction = Location::updateOrCreate($loc, $loc_update);

                    $loc_id = $Loction->id;
                }

                $finalLocation = Location::where('id', $loc_id)->first();
                if ($key == 0) {
                    $send_loc_id = $loc_id;
                    $pickup_location = $finalLocation;
                }

                if (isset($finalLocation)) {
                    array_push($latitude, $finalLocation->latitude);
                    array_push($longitude, $finalLocation->longitude);
                }

                $task_appointment_duration = isset($value->appointment_duration) ? $value->appointment_duration : null;

                $data = [
                    'order_id' => $orders->id,
                    'task_type_id' => $value['task_type_id'],
                    'location_id' => $loc_id,
                    'appointment_duration' => $task_appointment_duration,
                    'dependent_task_id' => $dep_id,
                    'task_status' => $agent_id != null ? 1 : 0,
                    'allocation_type' => $request->allocation_type,
                    'assigned_time' => $settime,
                    'barcode' => $value['barcode'] ?? null,
                    'is_road_side' => 1
                ];
                $task = Task::create($data);
                $dep_id = $task->id;
            }

            // accounting for task duration distanse
            $geoid = '';
            if (($pickup_location->latitude != '' || $pickup_location->latitude != '0.0000') && ($pickup_location->longitude != '' || $pickup_location->longitude != '0.0000')) :
                $geoid = $this->findLocalityByLatLng($pickup_location->latitude, $pickup_location->longitude);
            endif;

            //get pricing rule  for save with every order based on geo fence and agent tags
            $agent_tags = (isset($request->order_agent_tag) && !empty($request->order_agent_tag)) ? $request->order_agent_tag : '';
            $pricingRule = $this->getPricingRuleData($geoid, $agent_tags, $this->getConvertUTCToLocalTime($settime, $auth->timezone));

            $getdata = $this->GoogleDistanceMatrix($latitude, $longitude);

            $paid_duration = $getdata['duration'] - $pricingRule->base_duration;
            $paid_distance = $getdata['distance'] - $pricingRule->base_distance;
            $paid_duration = $paid_duration < 0 ? 0 : $paid_duration;
            $paid_distance = $paid_distance < 0 ? 0 : $paid_distance;
            $total = $pricingRule->base_price + ($paid_distance * $pricingRule->distance_fee) + ($paid_duration * $pricingRule->duration_price);

            if (isset($agent_id)) {
                $agent_details = Agent::where('id', $agent_id)->first();
                if ($agent_details->type == 'Employee') {
                    $percentage = $pricingRule->agent_commission_fixed + (($total / 100) * $pricingRule->agent_commission_percentage);
                } else {
                    $percentage = $pricingRule->freelancer_commission_fixed + (($total / 100) * $pricingRule->freelancer_commission_percentage);
                }
            }

            // update order with order cost details

            $updateorder = [
                'base_price' => $pricingRule->base_price,
                'base_duration' => $paid_duration??$pricingRule->base_duration,
                'base_distance' => $pricingRule->base_distance,
                // 'base_waiting' => $pricingRule->base_waiting,
                'duration_price' => $pricingRule->duration_price,
                // 'waiting_price' => $pricingRule->waiting_price,
                'distance_fee' => $pricingRule->distance_fee,
                'cancel_fee' => $pricingRule->cancel_fee,
                'agent_commission_percentage' => $pricingRule->agent_commission_percentage,
                'agent_commission_fixed' => $pricingRule->agent_commission_fixed,
                'freelancer_commission_percentage' => $pricingRule->freelancer_commission_percentage,
                'freelancer_commission_fixed' => $pricingRule->freelancer_commission_fixed,
                'actual_time' => $getdata['duration'],
                'actual_distance' => $getdata['distance'],
                'order_cost' => $total,
                'driver_cost' => $percentage,
                'cash_to_be_collected' => $total
            ];

            Order::where('id', $orders->id)->update($updateorder);

            if (isset($request->customer_phone_number)) {
                // $this->sendSms2($request->customer_phone_number, $dispatch_traking_url);
            }

            DB::commit();
            return response()->json([
                'message' => __('Task Added Successfully'),
                'task_id' => $orders->id,
                'status' => $orders->status,
                'dispatch_traking_url' => $dispatch_traking_url ?? null
            ], 200);
        } catch (Exception $e) {
            DB::rollback();
            return response()->json([
                'message' => $e->getMessage()
            ], 400);
        }
    }

    // update dropoff task function
    public function updateTask(Request $request)
    {


        try {
            $auth = $client = Client::with([
                'getAllocation',
                'getPreference'
            ])->first();
            $header = $request->header();
            if (isset($header['client'][0])) {
            } else {
                $header['client'][0] = $client->database_name;
            }

            DB::beginTransaction();
            $loc_id = $cus_id = $send_loc_id = $newlat = $newlong = 0;
            $last = '';
            $customer = [];
            $finalLocation = [];
            $taskcount = 0;
            $latitude = [];
            $longitude = [];
            $percentage = 0;
            $pricingRule = '';
            $agent_id = null;
            $allocation_type = '';

            $orders = Order::where('call_back_url', '=', $request->call_back_url)->first();
            if (!empty($orders)) {
                Task::where('order_id', $orders->id)->whereIn('task_status', [
                    0,
                    1
                ])
                    ->where('task_type_id', 2)
                    ->delete();
                $is_order_updated = 1;
                $notification_time = $orders->order_time;
                $agent_id = $orders->driver_id;
            } else {
                return response()->json([
                    'message' => "Something went wrong. Please try again later"
                ], 400);
            }

            $dep_id = null;
            $pickup_location = null;
            $taskcount = 0;
            foreach ($request->task as $key => $value) {
                $taskcount++;
                $loc_id = null;
                if (isset($value)) {
                    $post_code = isset($value['post_code']) ? $value['post_code'] : '';
                    $loc = [
                        'latitude' => $value['latitude'] ?? 0.00,
                        'longitude' => $value['longitude'] ?? 0.00,
                        'address' => $value['address'] ?? null,
                        'customer_id' => $orders->customer_id
                    ];
                    $loc_update = [
                        'short_name' => $value['short_name'] ?? null,
                        'post_code' => $post_code,
                        'flat_no' => $value['flat_no'] ?? null,
                        'email' => $value['email'] ?? null,
                        'phone_number' => $value['phone_number'] ?? null
                    ];

                    $Loction = Location::updateOrCreate($loc, $loc_update);
                    $loc_id = $Loction->id;
                }

                $task_appointment_duration = isset($value->appointment_duration) ? $value->appointment_duration : null;

                $lasttaskid = Task::where('order_id', $orders->id)->orderBy("id", "DESC")->first();
                $data = [
                    'order_id' => $orders->id,
                    'task_type_id' => $value['task_type_id'],
                    'location_id' => $loc_id,
                    'appointment_duration' => $task_appointment_duration,
                    'dependent_task_id' => (!empty($lasttaskid)) ? $lasttaskid->id : 0,
                    'task_status' => (!empty($value['task_status'])) ? $value['task_status'] : (($agent_id != null) ? 1 : 0),
                    'allocation_type' => $request->allocation_type,
                    'assigned_time' => $notification_time,
                    'barcode' => $value['barcode'] ?? null
                ];

                $task = Task::create($data);
            }

            $alltasks = Task::where('order_id', $orders->id)->with([
                'location'
            ])
                ->orderBy('task_type_id', 'ASC')
                ->orderBy("id", "ASC")
                ->get();
            $first = 0;
            foreach ($alltasks as $alltask) {
                if ($alltask->task_type_id == 1 && $first == 0) {
                    $pickup_location = (!empty($alltask->location)) ? $alltask->location : null;
                    $first++;
                }
                array_push($latitude, (!empty($alltask->location)) ? $alltask->location->latitude : '');
                array_push($longitude, (!empty($alltask->location)) ? $alltask->location->longitude : '');
            }

            // accounting for task duration distanse
            $geoid = '';
            if (($pickup_location->latitude != '' || $pickup_location->latitude != '0.0000') && ($pickup_location->longitude != '' || $pickup_location->longitude != '0.0000')) :
                $geoid = $this->findLocalityByLatLng($pickup_location->latitude, $pickup_location->longitude);
            endif;

            //get pricing rule  for save with every order based on geo fence and agent tags
            $agent_tags = (isset($request->order_agent_tag) && !empty($request->order_agent_tag)) ? $request->order_agent_tag : '';
            $pricingRule = $this->getPricingRuleData($geoid, $agent_tags, $this->getConvertUTCToLocalTime($notification_time, $auth->timezone));


            if ($auth->getPreference->toll_fee == 1) {
                $getdata = $this->toll_fee($latitude, $longitude, (isset($request->toll_passes) ? $request->toll_passes : ''), (isset($request->VehicleEmissionType) ? $request->VehicleEmissionType : ''), (isset($request->travelMode) ? $request->travelMode : ''));
                $toll_amount = (isset($getdata['toll_amount']) ? $getdata['toll_amount'] : 0);
            } else {
                $getdata = $this->GoogleDistanceMatrix($latitude, $longitude);
                $toll_amount = 0;
            }

            $pricingRule = $this->getPricingRuleData($geoid, $agent_tags, $this->getConvertUTCToLocalTime($notification_time, $auth->timezone));
            $pricingRuleDistance = $this->getPricingRuleDynamic($pricingRule, $getdata['distance']);



            $paid_duration = $getdata['duration'] - $pricingRule->base_duration;
            $paid_distance = $getdata['distance'] - $pricingRule->base_distance;
            $paid_duration = $paid_duration < 0 ? 0 : $paid_duration;
            $paid_distance = $paid_distance < 0 ? 0 : $paid_distance;
            $total = $pricingRule->base_price + ($paid_distance * $pricingRule->distance_fee) + ($paid_duration * $pricingRule->duration_price);

            if ($orders->is_cab_pooling == 1 && $orders->available_seats != 0) {
                $total = ($total / $orders->available_seats) * $orders->no_seats_for_pooling;
                $toll_amount = ($toll_amount / $orders->available_seats) * $orders->no_seats_for_pooling;
            }
            if (isset($agent_id)) {
                $agent_details = Agent::where('id', $agent_id)->first();
                if ($agent_details->type == 'Employee') {
                    $percentage = $pricingRule->agent_commission_fixed + (($total / 100) * $pricingRule->agent_commission_percentage);
                } else {
                    $percentage = $pricingRule->freelancer_commission_fixed + (($total / 100) * $pricingRule->freelancer_commission_percentage);
                }
            }

            // update order with order cost details

            $updateorder = [
                'base_price' => $pricingRule->base_price,
                'base_duration' => $pricingRule->base_duration,
                'base_distance' => $pricingRule->base_distance,
                'base_waiting' => $pricingRule->base_waiting,
                'duration_price' => $pricingRule->duration_price,
                'waiting_price' => $pricingRule->waiting_price,
                'distance_fee' => $pricingRule->distance_fee,
                'cancel_fee' => $pricingRule->cancel_fee,
                'agent_commission_percentage' => $pricingRule->agent_commission_percentage,
                'agent_commission_fixed' => $pricingRule->agent_commission_fixed,
                'freelancer_commission_percentage' => $pricingRule->freelancer_commission_percentage,
                'freelancer_commission_fixed' => $pricingRule->freelancer_commission_fixed,
                'actual_time' => $getdata['duration'],
                'actual_distance' => $getdata['distance'],
                'order_cost' => $total + $toll_amount,
                'toll_fee' => $toll_amount,
                'driver_cost' => $percentage,
                'cash_to_be_collected' => $request->cash_to_be_collected
            ];

            Order::where('id', $orders->id)->update($updateorder);

            if (isset($request->order_agent_tag)) {
                $value = $request->order_agent_tag;
                $tag_id = [];
                if (!empty($value)) {
                    $check = TagsForAgent::firstOrCreate([
                        'name' => $value
                    ]);
                    array_push($tag_id, $check->id);
                }
                $orders->drivertags()->sync($tag_id);
            }

            if ($agent_id != null) {
                $client_prefrerence = ClientPreference::where('id', 1)->first();
                $oneagent = Agent::where('id', $agent_id)->first();
                if (isset($oneagent) && !empty($oneagent->device_token) && $oneagent->is_available == 1) {
                    $notificationdata = [
                        'order_id' => $orders->id,
                        'batch_no' => '',
                        'driver_id' => $agent_id,
                        'notification_time' => Carbon::now()->addSeconds(2)->format('Y-m-d H:i:s'),
                        'notificationType' => 'UPDATED',
                        'created_at' => Carbon::now()->toDateTimeString(),
                        'updated_at' => Carbon::now()->toDateTimeString(),
                        'device_type' => $oneagent->device_type ?? '',
                        'device_token' => $oneagent->device_token,
                        'detail_id' => rand(11111111, 99999999),
                        'title' => 'Drop Off Location Updated By Customer',
                        'body' => 'Check All Details For This Request In App'
                    ];
                    $this->sendnotification($notificationdata, $client_prefrerence);
                }
            }

            DB::commit();
            return response()->json([
                'message' => __('Drop Off Task updated Successfully'),
                'task_id' => $orders->id,
                'status' => $orders->status
            ], 200);
        } catch (Exception $e) {
            DB::rollback();
            return response()->json([
                'message' => $e->getMessage()
            ], 400);
        }
    }


    // update bid & ride / Instant Booking order function
    public function updateBidRideOrder(Request $request)
    {
        try {
            $auth =  $client =  Client::with(['getAllocation', 'getPreference'])->first();
            $header = $request->header();
            if (isset($header['client'][0])) {
            } else {
                $header['client'][0] = $client->database_name;
            }

            DB::beginTransaction();
            $loc_id = $cus_id = $send_loc_id = $newlat = $newlong = 0;
            $last = '';
            $customer = [];
            $finalLocation = [];
            $taskcount = 0;
            $latitude  = [];
            $longitude = [];
            $percentage = 0;
            $pricingRule = '';
            $agent_id = $request->agent_id ?? null;
            $allocation_type = '';
            $notification_time = Carbon::now()->toDateTimeString();

            $orders = Order::where('call_back_url', '=', $request->call_back_url)->first();
            if (!empty($orders)) {
                Task::where('order_id', $orders->id)->delete();
                $is_order_updated    = 1;
            } else {
                return response()->json([
                    'message' => "Something went wrong. Please try again later"
                ], 400);
            }

            $dep_id = null;
            $pickup_location = null;
            $taskcount = 0;
            foreach ($request->task as $key => $value) {
                $taskcount++;
                $loc_id = null;
                if (isset($value)) {
                    $post_code = isset($value['post_code']) ? $value['post_code'] : '';
                    $loc = [
                        'latitude'    => $value['latitude'] ?? 0.00,
                        'longitude'   => $value['longitude'] ?? 0.00,
                        'address'     => $value['address'] ?? null,
                        'customer_id' => $orders->customer_id,
                    ];
                    $loc_update = [
                        'short_name'  => $value['short_name'] ?? null,
                        'post_code'   => $post_code,
                        'flat_no'     => $value['flat_no'] ?? null,
                        'email'       => $value['email'] ?? null,
                        'phone_number' => $value['phone_number'] ?? null,
                    ];

                    $Loction = Location::updateOrCreate(
                        $loc,
                        $loc_update
                    );
                    $loc_id = $Loction->id;
                }

                $data = [
                    'order_id'                   => $orders->id,
                    'task_type_id'               => $value['task_type_id'],
                    'location_id'                => $loc_id,
                    'dependent_task_id'          => $dep_id,
                    'task_status'                => ($agent_id != null) ? 1 : 0,
                    'allocation_type'            => $request->allocation_type,
                    'assigned_time'              => $notification_time
                ];

                $task = Task::create($data);
                $dep_id = $task->id;
            }

            $total         = $request->cash_to_be_collected;

            $percentage = 0;
            if (isset($agent_id)) {
                $agent_details = Agent::where('id', $agent_id)->first();
                if ($agent_details->type == 'Employee') {
                    $percentage = $orders->agent_commission_fixed + (($total / 100) * $orders->agent_commission_percentage);
                } else {
                    $percentage = $orders->freelancer_commission_fixed + (($total / 100) * $orders->freelancer_commission_percentage);
                }
            }

            //update order with order cost details
            $updateorder = [
                'order_cost'                      => $total,
                'toll_fee'                        => 0,
                'driver_cost'                     => $percentage,
                'cash_to_be_collected'            => $total,
                'driver_id'                       => $agent_id,
                'status'                          => ($agent_id != null) ? 'assigned' : 'unassigned',
            ];

            Order::where('id', $orders->id)->update($updateorder);

            if ($request->task_type == 'Instant_Booking') {
                if ($agent_id != null) {
                    $client_prefrerence = ClientPreference::where('id', 1)->first();
                    $oneagent = Agent::where('id', $agent_id)->first();
                    if (isset($oneagent) && !empty($oneagent->device_token) && $oneagent->is_available == 1) {

                        $notificationdata = [
                            'order_id'            => $orders->id,
                            'batch_no'            => '',
                            'driver_id'           => $agent_id,
                            'notification_time'   => Carbon::now()->addSeconds(2)->format('Y-m-d H:i:s'),
                            'notificationType'    => 'ACK',
                            'created_at'          => Carbon::now()->toDateTimeString(),
                            'updated_at'          => Carbon::now()->toDateTimeString(),
                            'device_type'         => $oneagent->device_type ?? '',
                            'device_token'        => $oneagent->device_token,
                            'detail_id'           => rand(11111111, 99999999),
                            'title'               => 'Request accepted by customer and order assigned to you',
                            'body'                => 'Check All Details For This Request In App',
                        ];
                        $this->sendnotification($notificationdata, $client_prefrerence);
                    }
                }
            }


            DB::commit();
            return response()->json([
                'message' => __('Bid & Ride/Instant Booking Task updated Successfully'),
                'task_id' => $orders->id,
                'status'  => $orders->status,
            ], 200);
        } catch (Exception $e) {
            DB::rollback();
            return response()->json([
                'message' => $e->getMessage()
            ], 400);
        }
    }

    //-------Bid and ride notification to drivers
    public function bidRideRequestNotification(Request $request)
    {
        try {
            $auth =  $client =  Client::with(['getAllocation', 'getPreference'])->first();
            $header = $request->header();
            if (isset($header['client'][0])) {
            } else {
                $header['client'][0] = $client->database_name;
            }

            $latitude  = array();
            $longitude = array();
            $pickup_location = array('latitude' => '0.0000', 'longitude' => '0.00000');
            $key       = 0;

            foreach ($request->tasks as $key => $value) {

                if ($value['latitude'] != '' && $value['longitude']) {
                    array_push($latitude, $value['latitude']);
                    array_push($longitude, $value['longitude']);

                    if ($key == 0) {
                        $pickup_location = $value;
                    }
                }
                $key++;
            }
            DB::beginTransaction();
            //geoid based on first pickup geolocation
            $geoid = '';
            if (($pickup_location['latitude'] != '' || $pickup_location['latitude'] != '0.0000') && ($pickup_location['longitude'] != '' || $pickup_location['longitude'] != '0.0000')) :
                $geoid = $this->findLocalityByLatLng($pickup_location['latitude'], $pickup_location['longitude']);
            endif;

            $agent_tag = isset($request->agent_tag) ? $request->agent_tag : '';

            $UserBidRideRequest                          = new UserBidRideRequest();
            $UserBidRideRequest->geo_id                  = ($geoid) ? $geoid : 0;
            $UserBidRideRequest->bid_id                  = $request->bid_id;
            $UserBidRideRequest->db_name                 = $request->db_name;
            $UserBidRideRequest->client_code             = $request->client_code;
            $UserBidRideRequest->agent_tag               = $request->agent_tag;
            $UserBidRideRequest->tasks                   = json_encode($request->tasks);
            $UserBidRideRequest->requested_price         = $request->requested_price;
            $UserBidRideRequest->call_back_url           = $request->call_back_url;
            $UserBidRideRequest->expired_at              = $request->expired_at;
            $UserBidRideRequest->customer_name           = $request->customer_name;
            $UserBidRideRequest->customer_image          = $request->customer_image;
            $UserBidRideRequest->minimum_requested_price = $request->minimum_requested_price;
            $UserBidRideRequest->maximum_requested_price = $request->maximum_requested_price;
            $UserBidRideRequest->expire_seconds          = $request->expire_seconds;
            $UserBidRideRequest->save();

            $date = \Carbon\Carbon::today();

            $cash_at_hand      = $auth->getAllocation->maximum_cash_at_hand_per_person ?? 0;
            $geoagents = $this->getGeoBasedAgentsData($geoid, 0, $agent_tag, $date, $cash_at_hand);
            $geoagents = $this->getGeoBasedAgentsData($geoid, 0, $agent_tag, $date, $cash_at_hand);
            if (count($geoagents) > 0) {
                foreach ($geoagents as $key =>  $geoitem) {
                    if (!empty($geoitem->device_token)) {
                        $notificationdata = [
                            'driver_id'           => $geoitem->id,
                            'notification_time'   => Carbon::now()->addSeconds(2)->format('Y-m-d H:i:s'),
                            'notificationType'    => 'bid_ride_request',
                            'created_at'          => Carbon::now()->toDateTimeString(),
                            'updated_at'          => Carbon::now()->toDateTimeString(),
                            'device_type'         => $geoitem->device_type ?? '',
                            'device_token'        => $geoitem->device_token,
                            'detail_id'           => rand(11111111, 99999999),
                            'title'               => 'Bid & Ride Request',
                            'body'                => 'Check All Details For This Request In App',
                            'task_type'           => 'bid_ride_request'
                        ];
                        $this->sendnotification($notificationdata, $auth->getPreference);
                    }
                }
            }

            DB::commit();
            return response()->json([
                'message' => __('Bid Request Created Successfully.'),
                'status'  => "success",
            ], 200);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'message' => $e->getMessage()
            ], 400);
        }
    }

    public function addWaitingTime(Request $request)
    {
        try {
            $task = Task::where(['id' => $request->task_id])->update(['waiting_time' => $request->waiting_time]);
            return response()->json([
                'message' => __('Time Added SuccessFully.'),
                'status'  => "success",
            ], 200);
        } catch (\Exception $e) {

            return response()->json([
                'message' => $e->getMessage()
            ], 400);
        }
    }


    public function addBufferTime(Request $request)
    {
        try {
            $order = order::where(['unique_id' => $request->tracking_id])->update(['buffer_time' => $request->time]);
            $order_data = order::where(['unique_id' => $request->tracking_id])->first();
            if (!empty($order_data->driver_id)) {

                $user = Agent::where('id', $order_data->driver_id)->first();
                // Log::info("devic token is ".$user->device_token);
                $client_prefrerence = ClientPreference::first(['fcm_server_key']);
                if (isset($user) && !empty($user->device_token) && $user->is_available == 1) {
                    $data = [

                        'notification_time'   => Carbon::now()->addSeconds(2)->format('Y-m-d H:i:s'),
                        'notificationType'    => 'delay_time',
                        'created_at'          => Carbon::now()->toDateTimeString(),
                        'updated_at'          => Carbon::now()->toDateTimeString(),
                        'device_type'         => $user->device_type ?? '',
                        'device_token'        => $user->device_token,
                        'title'               => 'Order Delayed',
                        'body'                => 'Your order with Order Number #' . $order_data->order_number . ' has been delayed by ' . $order_data->buffer_time . ' minutes',
                    ];
                    $this->sendnotification($data, $client_prefrerence);
                }
            }
            return response()->json([
                'message' => __('Time Added SuccessFully.'),
                'status'  => "success",
            ], 200);
        } catch (\Exception $e) {

            return response()->json([
                'message' => $e->getMessage()
            ], 400);
        }
    }

    public function sendPushNotificationtoDriver($title, $body, $auth, $device_token, $call_back_url)
    {
        $this->seperate_connection('db_'.$auth->database_name);
        $client_preferences = DB::connection('db_'.$auth->database_name)->table('client_preferences')->where('client_id', $auth->code)->first();
        $fcm_server_key = !empty($client_preferences->fcm_server_key)? $client_preferences->fcm_server_key : config('laravel-fcm.server_key');
        $headers = [
                'Authorization: key=' . $fcm_server_key,
                'Content-Type: application/json',
        ];
        $data = [
            "registration_ids" => $device_token,
            "notification" => [
                'title' => $title,
                'body'  => $body,
                'sound' => "notification",
                "icon" => '',
                'click_action' => $call_back_url,
                "android_channel_id" => "sound-channel-id"
            ],
            "data" => [
                'title' => $title,
                'body'  => $body,
                'data'  => $body,
                'type' => "order_modified"
            ],
            "priority" => "high"
        ];
        $dataString = $data;
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://fcm.googleapis.com/fcm/send');
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($dataString));
        $result = curl_exec($ch);
        curl_close($ch);
        return true;
    }

    public function RejectOrder(Request $request)
    {
        try {
            DB::beginTransaction();
            $client = Client::with([
                'getAllocation',
                'getPreference'
            ])->first();
            $header = $request->header();
            if (isset($header['client'][0])) {} else {
                $header['client'][0] = $client->database_name;
            }

            $order = Order::find($request->order_id);
            $agent = Agent::find(auth()->id());
            $customer = Customer::find($order->customer_id);
            $agent_tags = (isset($order->order_agent_tag) && !empty($order->order_agent_tag)) ? $order->order_agent_tag : '';
            $allocation = AllocationRule::where('id', 1)->first();
            $taskcount = 0;

            $tasks = Task::where('order_id', $order->id)->get();
            foreach ($tasks as $key => $value) {
                $taskcount ++;
                $loc_id = null;
                if (isset($value)) {
                    $post_code = isset($value['post_code']) ? $value['post_code'] : '';
                    $loc = [
                        'latitude' => $value['latitude'] ?? 0.00,
                        'longitude' => $value['longitude'] ?? 0.00,
                        'address' => $value['address'] ?? null,
                        'customer_id' => $order->customer_id
                    ];
                    $loc_update = [
                        'short_name' => $value['short_name'] ?? null,
                        'post_code' => $post_code,
                        'flat_no' => $value['flat_no'] ?? null,
                        'email' => $value['email'] ?? null,
                        'phone_number' => $value['phone_number'] ?? null
                    ];

                    $Loction = Location::updateOrCreate($loc, $loc_update);
                    $loc_id = $Loction->id;
                }

                $finalLocation = Location::where('id', $loc_id)->first();
                if ($key == 0) {
                    $send_loc_id = $loc_id;
                    $pickup_location = $finalLocation;
                }

                if (isset($finalLocation)) {
                    array_push($latitude, $finalLocation->latitude);
                    array_push($longitude, $finalLocation->longitude);
                }

                $task_appointment_duration = isset($value->appointment_duration) ? $value->appointment_duration : null;

                $data = [
                    'order_id' => $order->id,
                    'task_type_id' => $value['task_type_id'],
                    'location_id' => $loc_id,
                    'appointment_duration' => $task_appointment_duration,
                    'dependent_task_id' => null,
                    'task_status' => $agent->id != null ? 1 : 0,
                    'allocation_type' => $request->allocation_type,
                    'assigned_time' => $order->scheduled_date_time,
                    'barcode' => $value['barcode'] ?? null
                ];

                Task::create($data);
            }

            $geo = $this->createRoster($send_loc_id);

            $this->SendToAll($geo, $order->scheduled_date_time, $agent->id, $request->order_id, $customer, $pickup_location, $taskcount, $header, $allocation, $order->is_cab_pooling, $agent_tags, '', '');
            DB::commit();

            return response()->json(['status' => 200, 'message' => 'Order Rejected Successfull'],200);
        } catch (\Throwable $th) {
            DB::rollback();
            return response()->json([
                'message' => $th->getMessage()
            ], 400);
        }
    }

    public function checkBeforeTimeDiffrence($notification_time, $beforetime)
    {
        $to = Carbon::createFromFormat('Y-m-d H:s:i', Carbon::now()->toDateTimeString());
        $from = Carbon::createFromFormat('Y-m-d H:s:i', Carbon::parse($notification_time)->format('Y-m-d H:i:s'));
        $diff_in_minutes = $to->diffInMinutes($from);
        if ($diff_in_minutes < $beforetime) {
            return Carbon::now()->toDateTimeString();
        } else {
            return Carbon::parse($notification_time)->subMinutes($beforetime);
        }
    }

    public function sendBidRideNotification($driver_id,$status,$order_id = '',$header = '')
    {
        $user = Agent::find($driver_id);
        if (!empty($user)) {
            $client_prefrerence = ClientPreference::first(['fcm_server_key']);
            if (isset($user) && !empty($user->device_token) && $user->is_available == 1) {
                if($status == 2){
                    $data = [
                        'notificationType'    => 'bid',
                        'created_at'          => Carbon::now()->toDateTimeString(),
                        'updated_at'          => Carbon::now()->toDateTimeString(),
                        'device_type'         => $user->device_type ?? '',
                        'device_token'        => $user->device_token,
                        'title'               => ($status == 1)?'Bid Accepted':'Bid Rejected',
                        'body'                => ($status == 1)?'Your bid for pickup request is accepted':'Your bid for pickup request is rejected',
                    ];
                    $this->sendBidNotification($data, $client_prefrerence);
                }else{
                    $order_details = Order::where('id', $order_id)->with([
                        'customer',
                        'agent',
                        'task.location'
                    ])->first();
                    $auth = Client::where('database_name', $header['client'][0])->with([
                        'getAllocation',
                        'getPreference'
                    ])->first();
                    $notification_time = $order_details->order_time;
                    $expriedate = (int) $auth->getAllocation->request_expiry;
                    $beforetime = (int) $auth->getAllocation->start_before_task_time;
                    $maxsize = (int) $auth->getAllocation->maximum_batch_size;
                    $type = $auth->getPreference->acknowledgement_type;
                    $try = $auth->getAllocation->number_of_retries;
                    $time = $this->checkTimeDiffrence($notification_time, $beforetime); // this function is check the time diffrence and give the notification time
                    $rostersbeforetime = $this->checkBeforeTimeDiffrence($notification_time, $beforetime);
                    $randem = rand(11111111, 99999999);
                    $allcation_type = 'ACK';

                    foreach ($order_details->task as $key => $value) {
                        $taskcount = count($order_details->task);
                        $extraData = [
                            'customer_name' => $order_details->customer->name,
                            'customer_phone_number' => $order_details->customer->phone_number,
                            'short_name' => $value->location->short_name ?? '',
                            'address' => $value->location->address ?? '',
                            'lat' => $value->location->latitude ?? '',
                            'long' => $value->location->longitude ?? '',
                            'task_count' => $taskcount,
                            'unique_id' => $randem,
                            'created_at' => Carbon::now()->toDateTimeString(),
                            'updated_at' => Carbon::now()->toDateTimeString()
                        ];
                        break;
                    }
                    $data = [
                        'order_id' => $order_id,
                        'driver_id' => $driver_id,
                        'notification_time' => $time,
                        'notification_befor_time' => $rostersbeforetime,
                        'type' => $allcation_type,
                        'client_code' => $auth->code,
                        'created_at' => Carbon::now()->toDateTimeString(),
                        'updated_at' => Carbon::now()->toDateTimeString(),
                        'device_type' => $user->device_type,
                        'device_token' => $user->device_token,
                        'detail_id' => $randem
                    ];

                    $this->dispatch(new RosterCreate($data, $extraData));
                }
            }
        }
    }

    public function OneByOneUniqueDriver($geo, $notification_time, $agent_id, $orders, $customer, $finalLocation, $taskcount, $header, $allocation,$is_cab_pooling, $agent_tags, $is_order_updated,$var,$notify_hour,$reminder_hour)
    {
        $allcation_type    = 'AR';
        $date              = \Carbon\Carbon::today();
        $auth              = Client::where('database_name', $header['client'][0])->with(['getAllocation', 'getPreference'])->first();
        $expriedate        = (int)$auth->getAllocation->request_expiry;
        $beforetime        = (int)$auth->getAllocation->start_before_task_time;
        $maxsize           = (int)$auth->getAllocation->maximum_batch_size;
        $type              = $auth->getPreference->acknowledgement_type;
        $unit              = $auth->getPreference->distance_unit;
        $try               = $auth->getAllocation->number_of_retries;
        $cash_at_hand      = $auth->getAllocation->maximum_cash_at_hand_per_person??0;
        $max_redius        = $auth->getAllocation->maximum_radius;
        $max_task          = $auth->getAllocation->maximum_batch_size;
        $time              = $this->checkTimeDiffrence($notification_time, $beforetime);
        $randem            = rand(11111111, 99999999);
        $data = [];

        if ($type == 'acceptreject') {
            $allcation_type = 'AR';
        } elseif ($type == 'acknowledge') {
            $allcation_type = 'ACK';
        } else {
            $allcation_type = 'N';
        }
        date_default_timezone_set('UTC');
        $date  =  Carbon::now()->toDateTimeString();

        $extraData = [
            'customer_name'            => $customer->name,
            'customer_phone_number'    => $customer->phone_number,
            'short_name'               => $finalLocation->short_name,
            'address'                  => $finalLocation->address,
            'lat'                      => $finalLocation->latitude,
            'long'                     => $finalLocation->longitude,
            'task_count'               => $taskcount,
            'unique_id'                => $randem,
            'created_at'               => Carbon::now()->toDateTimeString(),
            'updated_at'               => Carbon::now()->toDateTimeString(),
        ];

        $oneagent = Agent::where('id', $agent_id)->first();
        $rosterData = [];
        $data1 = [
            'order_id'            => $orders->id,
            'driver_id'           => $agent_id,
            'notification_time'   => $date,
            'type'                => $allcation_type,
            'client_code'         => $auth->code,
            'created_at'          => Carbon::now()->toDateTimeString(),
            'updated_at'          => Carbon::now()->toDateTimeString(),
            'device_type'         => $oneagent->device_type,
            'device_token'        => $oneagent->device_token,
            'detail_id'           => $randem,
            'is_particular_driver' => 0
        ];
        array_push($rosterData, $data1);

        if(@$notify_hour && $orders->order_type =='schedule')
        {
                $data2 = [
                    'order_id'            => $orders->id,
                    'driver_id'           => $agent_id,
                    'notification_time'   => Carbon::parse($notification_time)->subMinutes($notify_hour)->format('Y-m-d H:i:s'),
                    'type'                => $allcation_type,
                    'client_code'         => $auth->code,
                    'created_at'          => Carbon::now()->toDateTimeString(),
                    'updated_at'          => Carbon::now()->toDateTimeString(),
                    'device_type'         => $oneagent->device_type,
                    'device_token'        => $oneagent->device_token,
                    'detail_id'           => $randem,
                    'is_particular_driver' => 1
                ];

                array_push($rosterData, $data2);
        }

        if(@$reminder_hour  && $orders->order_type =='schedule')
        {
            $data3 = [
                'order_id'            => $orders->id,
                'driver_id'           => $agent_id,
                'notification_time'   => Carbon::parse($notification_time)->subMinutes($reminder_hour)->format('Y-m-d H:i:s'),
                'type'                => $allcation_type,
                'client_code'         => $auth->code,
                'created_at'          => Carbon::now()->toDateTimeString(),
                'updated_at'          => Carbon::now()->toDateTimeString(),
                'device_type'         => $oneagent->device_type,
                'device_token'        => $oneagent->device_token,
                'detail_id'           => $randem,
                'is_particular_driver' => 2
            ];
            array_push($rosterData, $data3);
        }

        $this->dispatch(new RosterCreate($rosterData, $extraData));

    }

    public function OneByOne($geo, $notification_time, $agent_id, $orders_id, $customer, $finalLocation, $taskcount, $header, $allocation, $is_cab_pooling, $agent_tag = '', $is_order_updated, $is_one_push_booking=0,$particular_driver_id = 0)
    {
        $allcation_type    = 'AR';
        $date              = \Carbon\Carbon::today();
        $auth              = Client::where('database_name', $header['client'][0])->with(['getAllocation', 'getPreference'])->first();
        $expriedate        = (int)$auth->getAllocation->request_expiry;
        $beforetime        = (int)$auth->getAllocation->start_before_task_time;
        $maxsize           = (int)$auth->getAllocation->maximum_batch_size;
        $type              = $auth->getPreference->acknowledgement_type;
        $unit              = $auth->getPreference->distance_unit;
        $try               = $auth->getAllocation->number_of_retries;
        $cash_at_hand      = $auth->getAllocation->maximum_cash_at_hand_per_person??0;
        $max_redius        = $auth->getAllocation->maximum_radius;
        $max_task          = $auth->getAllocation->maximum_batch_size;
        $time              = $this->checkTimeDiffrence($notification_time, $beforetime);
        $randem            = rand(11111111, 99999999);
        $data = [];

        if ($type == 'acceptreject') {
            $allcation_type = 'AR';
        } elseif ($type == 'acknowledge') {
            $allcation_type = 'ACK';
        } else {
            $allcation_type = 'N';
        }

        $extraData = [
            'customer_name'            => $customer->name,
            'customer_phone_number'    => $customer->phone_number,
            'short_name'               => $finalLocation->short_name,
            'address'                  => $finalLocation->address,
            'lat'                      => $finalLocation->latitude,
            'long'                     => $finalLocation->longitude,
            'task_count'               => $taskcount,
            'unique_id'                => $randem,
            'created_at'               => Carbon::now()->toDateTimeString(),
            'updated_at'               => Carbon::now()->toDateTimeString(),
        ];

        if (!isset($geo)) {
            $oneagent = Agent::where('id', $agent_id)->first();
            if(!empty($oneagent->device_token) && $oneagent->is_available == 1){
                $data = [
                    'order_id'            => $orders_id,
                    'driver_id'           => $agent_id,
                    'notification_time'   => $time,
                    'type'                => $allcation_type,
                    'client_code'         => $auth->code,
                    'created_at'          => Carbon::now()->toDateTimeString(),
                    'updated_at'          => Carbon::now()->toDateTimeString(),
                    'device_type'         => $oneagent->device_type,
                    'device_token'        => $oneagent->device_token,
                    'detail_id'           => $randem,
                ];
                $this->dispatch(new RosterCreate($data, $extraData));
            }
        } else {
            $geoagents = $this->getGeoBasedAgentsData($geo, $is_cab_pooling, $agent_tag, $date, $cash_at_hand,$orders_id,$particular_driver_id);
            // for ($i = 1; $i <= $try; $i++) {
                foreach ($geoagents as $key =>  $geoitem) {
                    if (!empty($geoitem->device_token) && $geoitem->is_available == 1) {
                        $datas = [
                            'order_id'            => $orders_id,
                            'driver_id'           => $geoitem->id,
                            'notification_time'   => $time,
                            'type'                => $allcation_type,
                            'client_code'         => $auth->code,
                            'created_at'          => Carbon::now()->toDateTimeString(),
                            'updated_at'          => Carbon::now()->toDateTimeString(),
                            'device_type'         => $geoitem->device_type,
                            'device_token'        => $geoitem->device_token,
                            'detail_id'           => $randem,

                        ];
                        array_push($data, $datas);
                        if ($allcation_type == 'N' && 'ACK') {
                            Order::where('id', $orders_id)->update(['driver_id'=>$geoitem->id]);
                            break;
                        }
                        $time = Carbon::parse($time)
                        ->addSeconds($expriedate + 10)
                        ->format('Y-m-d H:i:s');
                    }
                }
            // }
            $this->dispatch(new RosterCreate($data, $extraData));
        }
    }


    public function sendBidAcceptRejectNotification(Request $request)
    {
        try {
            $this->sendBidRideNotification($request->driver_id,$request->status);
            return response()->json([
                'message' => __('Notification sent SuccessFully.'),
                'status'  => "success",
            ], 200);
        } catch (\Exception $e) {

            return response()->json([
                'message' => $e->getMessage()
            ], 400);
        }
    }

    public function seperate_connection($schemaName){
        $default = [
            'driver' => env('DB_CONNECTION', 'mysql'),
            'host' => env('DB_HOST'),
            'port' => env('DB_PORT'),
            'database' => $schemaName,
            'username' => env('DB_USERNAME'),
            'password' => env('DB_PASSWORD'),
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => '',
            'prefix_indexes' => true,
            'strict' => false,
            'engine' => null
        ];

        Config::set("database.connections.$schemaName", $default);
    }

    public function newNotifiedDriverSave($order_id,$agent_id){
            $schemaName = 'royodelivery_db';
            $this->seperate_connection($schemaName);
            config(["database.connections.mysql.database" =>$schemaName]);
            \DB::connection($schemaName)->table('rosters')->where('order_id',$order_id)->whereIn('is_particular_driver',[1,2])->update(['driver_id' => $agent_id]);
            \DB::disconnect($schemaName);
    }

    public function autoallocated($request){
        $header = $request->header();
        $orders=Order::where('id',$request->order_id)->first();
        $task=Task::where('order_id',$request->order_id)->get();
        $taskcount=$task->count();
        $allocation = AllocationRule::where('id', 1)->first();
        $location=Location::where('customer_id',$orders->customer_id)->orderBy('id','desc')->first();
        $send_loc_id= $location->id;
        $geo = $this->createRoster($send_loc_id);
        $agent_id = null;
        $particular_driver_id = $request->driver_id;
        $customer=Customer::where('id',$orders->customer_id)->first();
        $notification_time=Carbon::now()->toDateTimeString();
      //  if ($orders->auto_alloction === 'a') {
        switch ($allocation->auto_assign_logic) {
            case 'one_by_one':
                //this is called when allocation type is one by one
                $this->OneByOne($geo, $notification_time, $agent_id, $orders->id, $customer, $location, $taskcount,$header, $allocation, $orders->is_cab_pooling, $orders->agent_tags, 0, $orders->is_one_push_booking,$particular_driver_id);
                break;
            case 'send_to_all':
                //this is called when allocation type is send to all
                $this->SendToAll($geo, $notification_time, $agent_id, $orders->id, $customer, $location, $taskcount,$header, $allocation, $orders->is_cab_pooling, $orders->agent_tags, 0, $orders->is_one_push_booking,$particular_driver_id);
                break;
            case 'round_robin':
                //this is called when allocation type is round robin
                $this->roundRobin($geo, $notification_time, $agent_id, $orders->id, $customer, $location, $taskcount,$header, $allocation, $orders->is_cab_pooling, $orders->agent_tags, 0, $orders->is_one_push_booking,$particular_driver_id);
                break;
            default:
                //this is called when allocation type is batch wise
                $this->batchWise($geo, $notification_time, $agent_id, $orders->id, $customer, $location, $taskcount,$header, $allocation, $orders->is_cab_pooling, $orders->agent_tags, 0, $orders->is_one_push_booking,$particular_driver_id);
        }
        return true;
       // }
    }
}
