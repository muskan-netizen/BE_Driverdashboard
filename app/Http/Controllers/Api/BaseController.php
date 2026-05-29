<?php
namespace App\Http\Controllers\Api;

use Auth;
use Illuminate\Http\Request;
use App\Traits\ApiResponser;
use Illuminate\Support\Collection;
use Illuminate\Pagination\Paginator;
use Illuminate\Pagination\LengthAwarePaginator;
use App\Http\Controllers\Controller;
use Twilio\Rest\Client as TwilioClient;
use App\Traits\smsManager;
use App\Model\ {
    Agent,
    PaymentOption,
    Client,
    ClientPreference,
    AgentSavedPaymentMethod
};
use App\Model\Users;
use JWT\Token;
use App\Model\UserDevice;
use App\Model\OrderPanelDetail;

class BaseController extends Controller
{
    use smsManager;
    use ApiResponser;

    protected function sendSms($recipients, $message)
    {
        $sid = getenv("TWILIO_SID");
        $token = getenv("TWILIO_AUTH_TOKEN");
        $twilio_number = getenv("TWILIO_NUMBER");
        $client = new TwilioClient($account_sid, $auth_token);
        $client->messages->create('+91' . $recipients, [
            'from' => $twilio_number,
            'body' => $message
        ]);
    }

    public function paginate($items, $perPage = 15, $page = null, $options = [])
    {
        $page = $page ?: (Paginator::resolveCurrentPage() ?: 1);
        $items = $items instanceof Collection ? $items : Collection::make($items);
        return new LengthAwarePaginator($items->forPage($page, $perPage), $items->count(), $perPage, $page, $options);
    }

    protected function sendSms2($to, $body)
    {
        try {
            $client_preference = getClientPreferenceDetail();

            if ($client_preference->sms_provider == 1) {
                $credentials = json_decode($client_preference->sms_credentials);
                $sms_key = (isset($credentials->sms_key)) ? $credentials->sms_key : $client_preference->sms_provider_key_1;
                $sms_secret = (isset($credentials->sms_secret)) ? $credentials->sms_secret : $client_preference->sms_provider_key_2;
                $sms_from = (isset($credentials->sms_from)) ? $credentials->sms_from : $client_preference->sms_provider_number;

                $client = new TwilioClient($sms_key, $sms_secret);
                $client->messages->create($to, [
                    'from' => $sms_from,
                    'body' => $body
                ]);
            } elseif ($client_preference->sms_provider == 2) // for mtalkz gateway
            {
                $credentials = json_decode($client_preference->sms_credentials);
                $send = $this->mTalkz_sms($to, $body, $credentials);
            } elseif ($client_preference->sms_provider == 3) // for mazinhost gateway
            {
                $credentials = json_decode($client_preference->sms_credentials);
                $send = $this->mazinhost_sms($to, $body, $credentials);
            } elseif ($client_preference->sms_provider == 4) // for unifonic gateway
            {
                $crendentials = json_decode($client_preference->sms_credentials);
                $send = $this->unifonic($to, $body, $crendentials);
            } elseif ($client_preference->sms_provider == 5) // for arkesel_sms gateway
            {
                $crendentials = json_decode($client_preference->sms_credentials);
                $send = $this->arkesel_sms($to, $body, $crendentials);
                if (isset($send->code) && $send->code != 'ok') {
                    return $this->error($send->message, 404);
                }
            } elseif ($client_preference->sms_provider == 6) // for Vonage (nexmo)
            {
                $crendentials = json_decode($client_preference->sms_credentials);
                $send = $this->vonage_sms($to, $body, $crendentials);
            } elseif ($client_preference->sms_provider == 7) // for SMS Partner France
            {
                $crendentials = json_decode($client_preference->sms_credentials);
                $send = $this->sms_partner_gateway($to, $body, $crendentials);
                if (isset($send->code) && $send->code != 200) {
                    return $this->error("SMS could not be deliver. Please check sms gateway configurations", 404);
                }
            }elseif($client_preference->sms_provider == 8) //for ethiopia 
            {
            $crendentials = json_decode($client_preference->sms_credentials);
            $send = $this->ethiopia($to,$body,$crendentials);
            }else {
                $credentials = json_decode($client_preference->sms_credentials);
                $sms_key = (isset($credentials->sms_key)) ? $credentials->sms_key : $client_preference->sms_provider_key_1;
                $sms_secret = (isset($credentials->sms_secret)) ? $credentials->sms_secret : $client_preference->sms_provider_key_2;
                $sms_from = (isset($credentials->sms_from)) ? $credentials->sms_from : $client_preference->sms_provider_number;
                $client = new TwilioClient($sms_key, $sms_secret);
                $client->messages->create($to, [
                    'from' => $sms_from,
                    'body' => $body
                ]);
            }
        } catch (\Exception $e) {
            \Log::info($e->getMessage());
            // return $this->error(__('Provider service is not configured. Please contact administration.'), 404);
            return $this->error($e->getMessage(), $e->getCode());
        }
        return $this->success([], __('An otp has been sent to your phone. Please check.'), 200);
    }

    /* Save user payment method */
    public function saveUserPaymentMethod($request)
    {
        $payment_method = new AgentSavedPaymentMethod();
        $payment_method->agent_id = Auth::user()->id;
        $payment_method->payment_option_id = $request->payment_option_id;
        $payment_method->card_last_four_digit = $request->card_last_four_digit;
        $payment_method->card_expiry_month = $request->card_expiry_month;
        $payment_method->card_expiry_year = $request->card_expiry_year;
        $payment_method->customerReference = ($request->has('customerReference')) ? $request->customerReference : NULL;
        $payment_method->cardReference = ($request->has('cardReference')) ? $request->cardReference : NULL;
        $payment_method->save();
    }

    public function getPanelDetail(Request $request)
    {
        try {
            if ($request->inventory_code) {
                $inventory_url = $request->inventory_url;
                $inventory_code = $request->inventory_code;

                $client = Client::select('database_name')->first();
                if ($client) {
                    $client_prefrence = ClientPreference::first();
                    $client_prefrence->inventory_service_key_url = $inventory_url;
                    $client_prefrence->inventory_service_key_code = $inventory_code;
                    $client_prefrence->update();

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
                    'message' => 'Order/Dispatch Panel Not found'
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

    /* Get Saved user payment method */
    public function getSavedUserPaymentMethod($request)
    {
        $saved_payment_method = AgentSavedPaymentMethod::where('agent_id', Auth::user()->id)->where('payment_option_id', $request->payment_option_id)->first();
        return $saved_payment_method;
    }

    /**
     * **************** ---- check Keys from order Panel keys ----- *****************
     */
    public function checkOrderPanelKeys(Request $request)
    {
        if (checkTableExists('clients')) {
            $user = Client::first();
            
            $order_panel = OrderPanelDetail::first();
            if(empty($order_panel)){

                return response()->json([
                    'status' => 401,
                    'message' => 'Authentication failed'
                ]);
            }
            $token1 = new Token();
            $token = $token1->make([
                'key' => 'royoorders-jwt',
                'issuer' => 'royoorders.com',
                'expiry' => strtotime('+2 hour'),
                'issuedAt' => time(),
                'algorithm' => 'HS256'
            ])->get();
            $token1->setClaim('user_id', $user->id);

            $device = UserDevice::updateOrCreate([
                'device_token' => 'dispatcher-login'
            ], [
                'user_id' => $user->id,
                'device_type' => 'web',
                'access_token' => $token,
                'is_vendor_app' => 0
            ]);
            return response()->json([
                'status' => 200,
                'token' => $token,
                'message' => 'Valid Order Panel API keys'
            ]);
        }
        return response()->json([
            'status' => 401,
            'message' => 'Authentication failed'
        ]);
    }

    public function randomData($table){
        $random_string = 'D'.substr(md5(microtime()), 0, 6);
        // after creating, check if string is already used
        while(\DB::table($table)->where('refferal_code', $random_string)->exists()){
            $random_string = 'D'.substr(md5(microtime()), 0, 6);
        }
        return $random_string;
    }
}
