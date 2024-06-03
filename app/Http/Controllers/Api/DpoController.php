<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Model\ClientPreference;
use App\Model\Currency;
use App\Model\Payment;
use App\Model\PaymentOption;
use App\Model\Users;
use App\Traits\ApiResponser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DpoController extends Controller
{
    use ApiResponser;

    private $companyToken;
    private $appUrl;
    private $serviceType;
    private $token;
    public $currency;

    public function __construct()
    {
        $payOpt = PaymentOption::select('credentials', 'test_mode', 'status')->where('code', 'dpo')->where('status', 1)->first();
        if(@$payOpt->status){
            $json = json_decode($payOpt->credentials);
            $this->companyToken = $json->company_token;
            $this->serviceType = $json->service_type;
            $this->token = base64_encode($this->companyToken.':'.$this->serviceType);
            // if ($payOpt->test_mode == '1') {
                $this->appUrl = 'https://secure.3gdirectpay.com/';
            // } else {
            //     // $this->appUrl = 'https://sec.windcave.com/api/v1/sessions';
            // }
            $clientPreference = ClientPreference::select('id','currency_id')->with('currency')->first();
            $this->currency =  $clientPreference->currency->iso_code ?? 'USD';
        }
    }

    public function createAppTocken(Request $request)
    {
        $request->from = $request->action;
        $order_number =  $this->orderNumber($request);
        $user = Auth::user();
        $redirectUrl = $request->serverUrl.'payment/dpo/redirect/?order_no='.$order_number.'&payment_via=app&status=200&auth_token='.$user->auth_token;
        $total_amount = round($request->amount);
        $name = explode(' ',$user->name);
        $customerFirstName = $name[0];
        $customerLastName = !empty($name[1])? $name[1] : '';
        $xml = "<API3G>
                    <CompanyToken>".$this->companyToken."</CompanyToken>
                    <Request>createToken</Request>
                    <Transaction>
                        <PaymentAmount>".$total_amount."</PaymentAmount>
                        <PaymentCurrency>".$this->currency."</PaymentCurrency>
                        <CompanyRef>tr1ss1212bnbv</CompanyRef>
                        <RedirectURL>".$redirectUrl."</RedirectURL>
                        <BackURL>".back()."</BackURL>
                        <CompanyRefUnique>0</CompanyRefUnique>
                        <PTL>100000</PTL>
                        <CompanyAccRef>www</CompanyAccRef>
                        <PTLtype>minutes</PTLtype>
                        <DefaultPayment>XP</DefaultPayment>
                        <AllowRecurrent></AllowRecurrent>
                        <customerFirstName>".$customerFirstName."</customerFirstName>
                        <customerLastName>".$customerLastName."</customerLastName>
                        <customerEmail>".$user->email."</customerEmail>
                        <customerPhone>".$user->phone_number."</customerPhone>
                    </Transaction>
                    <Services>
                        <Service>
                            <ServiceType>".$this->serviceType."</ServiceType>
                            <ServiceDescription>Airlines Service</ServiceDescription>
                            <ServiceTypeName>Airlines Service</ServiceTypeName>
                            <ServiceDate>2022/06/25 06:52</ServiceDate>
                        </Service>
                    </Services>
                </API3G>";


        $result = $this->postCurl($xml);
        $paymentTocken = $this->xml2array($result);
        if(!empty($paymentTocken['TransToken'])){
            return $this->successResponse($this->appUrl.'payv2.php?ID='.$paymentTocken['TransToken']);
        }
    }

    private function postCurl($xml){

        $curl = curl_init();
        curl_setopt_array($curl, array(
        CURLOPT_URL => $this->appUrl.'API/v6/',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS =>$xml,
        CURLOPT_HTTPHEADER => array(
            'Content-Type: application/xml'
            // 'Cookie: AFIDENT=1A5B897A-277F-42B3-8E52-E67B0434944A'
        ),
        ));

        $response = curl_exec($curl);
        curl_close($curl);
        return simplexml_load_string($response);
        // return $response;
    }

    function xml2array ( $xmlObject, $out = array () )
    {
        foreach ( (array) $xmlObject as $index => $node )
            $out[$index] = ( is_object ( $node ) ) ? xml2array ( $node ) : $node;

        return $out;
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
}
