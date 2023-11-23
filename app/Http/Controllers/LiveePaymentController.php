<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\WalletController;
use App\Http\Controllers\Controller;
use App\Model\Agent;
use App\Model\ClientPreference;
use App\Model\Order;
use App\Model\PaymentOption;
use App\Model\Payment;
use App\Traits\ApiResponser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LiveePaymentController extends Controller
{
    use ApiResponser;
    public $currency;
    private $trade_key;
    private $resource_key;
    private $apiUrl;


    public function __construct()
    {
        $payOption = PaymentOption::select('credentials', 'test_mode', 'status')->where('code', 'livee')->where('status', 1)->first();

        if($payOption->status)
        $credentials = json_decode($payOption->credentials);

        $this->trade_key = $credentials->livee_merchant_key;
        $this->resource_key = $credentials->livee_resource_key;
        $this->apiUrl = "https://www.livees.net/Checkout/api4";

        $clientPreference = ClientPreference::select('id','currency_id')->first();
        $this->currency =  $clientPreference->currency->iso_code ?? 'USD';
    }


    public function index(Request $request, $amt)
    {
        try {
            // $orderNumber = $this->orderNumber($request);
            $orderNumber = time();
            // $users = $this->createUserToken();
            $user = Auth::user();
            $urlParams = '';
            $amount = $request->amt;
            $nameString = "name";
            $name = strtok(auth()->user()->name, " ");
            $lastname = substr(strstr(auth()->user()->name, " "), 1);
            $email = auth()->user()->email;
            $phone = auth()->user()->phone_number;
            // $users = $this->createUserToken();
            if ($request->payment_from == 'wallet') {
                $urlParams   = "transactionid=$orderNumber&paymentfrom=wallet&success=true";
            }
            $postURL = url('/livee/success' . '?' . $urlParams);

            $trade_key = $this->trade_key;
            $resource_key = $this->resource_key;
            $apiUrl = $this->apiUrl;
            return view('backend.payment.liveePay', compact('amount', 'postURL', 'user','trade_key','resource_key','apiUrl'));
        } catch (\Exception $e) {
            return $e->getMessage();
        }
    }

    public function token()
    {
        try {
            $apiUrl = $this->apiUrl;
            $input = json_encode([
                "trade_key" => $this->trade_key,
                "resource_key" => $this->resource_key
            ]);

            $header = [
                'Content-Type' => 'application/json'
            ];
            $response = Http::withBody($input, 'application/json')->withHeaders($header)->post($apiUrl);

            return $response;
        } catch (\Exception $e) {
            return $e->getMessage();
        }
    }
    
    public function afterPayment(Request $request)
    {
        try {
            if ($request->paymentfrom == 'wallet') {
                $returnUrl = route('payment.gateway.return.response') . '/?gateway=livees' . '&status=200&transaction_id=' . $request->transactionid . '&action=wallet';
                $request->request->add(['transaction_id' => $request->transactionid,'auth_token' => substr($request->auth_token,0,-8),'payment_option_id' => 19]);

                $walletController = new WalletController();
                $res= $walletController->creditAgentWallet($request);
                return redirect($returnUrl);
            }

        } catch (\Exception $e) {
            return $e->getMessage();
        }
    }

    public function payFormWeb(Request $request)
    {
        try {

            if (isset($request->user_id)) {
                $user = Agent::where('id', $request->user_id)->first();
                Auth::login($user);
            } else {
                $user = Auth::loginUsingId(1);
            }
            $request->request->add(['payment_from' => isset($request->paymentfrom) ? $request->paymentfrom : $request->payment_from]);
            $request->request->add(['amount' => isset($request->amt) ? $request->amt : $request->amount]);

            // $orderNumber = $this->orderNumber($request);
            $orderNumber = time();
            $urlParams = '';
            $amount = $request->amt;
            $nameString = "name";

            $name = strtok(auth()->user()->name, " ");
            $email = auth()->user()->email;
            $phone = auth()->user()->phone_number;

            if ($request->payment_from == 'wallet') {
                $urlParams   = "transactionid=$orderNumber&paymentfrom=wallet&success=true&come_from=app&amount=$request->amount&success=true&auth_token=$user->access_token";
            } 
            $postURL = url('/livee/success' . '?' . $urlParams);

            $trade_key = $this->trade_key;
            $resource_key = $this->resource_key;
            $apiUrl = $this->apiUrl;

            return view('payment_gateway.liveePay', compact('amount', 'postURL', 'user', 'trade_key','resource_key','apiUrl'));
        } catch (\Exception $e) {
            return $e->getMessage();
        }
    }

    public function orderNumber($request)
    {
        try {
            $time    = isset($request->transaction_id) ? $request->transaction_id : time();
            $user_id = auth()->id();
            $amount  = $request->amt;
            if ($request->payment_from == 'wallet') {
                // Payment::create([
                //     'amount' => $amount,
                //     'transaction_id' => $time,
                //     'balance_transaction' => $amount,
                //     'cr' => $amount,
                //     'type' => 'wallet',
                //     'date' => date('Y-m-d'),
                //     'driver_id' => $user_id,
                //     'payment_from' => $request->come_from ?? 'web',
                // ]);
            }
            return $time;
        } catch (\Exception $e) {
            return $e->getMessage();
        }
    }

    public function mobilePay(Request $request, $domain = '')
    {

        $message = '';
        $amount = $request->amount;

        $message = '';
        $amount = $request->amount;
        $user = auth()->user();
        $action = isset($request->action) ? $request->action : '';
        $params = '?amt=' . $amount . '&paymentfrom=' . $action . "&come_from=app&user_id=" . $user->id;
        if ($action == 'cart') {
            $params = $params . '&order_number=' . $request->order_number . '&app=1';
        } elseif ($action == 'wallet') {
            $params = $params . '&app=2&transaction_id=' . time();
        } 

        $url = url('payment/livees/api/' . $params);
        // \Log::info($url);
        return $this->successResponse(($url));
    }

    public function createUserToken()
    {
        $user = auth()->user();
        $token1 = new Token();
        $token = $token1->make([
            'key' => 'royoorders-jwt',
            'issuer' => 'royoorders.com',
            'expiry' => strtotime('+1 month'),
            'issuedAt' => time(),
            'algorithm' => 'HS256',
        ])->get();
        $token1->setClaim('user_id', $user->id);
        $this->token = $token;
        $user->auth_token = $token;
        $user->save();
        return $user;
    }

}
