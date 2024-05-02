<?php

namespace App\Http\Controllers\Api;

use App\Helpers\Mastercard\Mastercard;
use App\Helpers\Mastercard\Models\Order;
use App\Helpers\Mastercard\Models\Purchase;
use App\Helpers\Mastercard\Operation;
use App\Http\Controllers\Controller;
use App\Http\Middleware\DbChooserApi;
use App\Model\AgentPayment;
use App\Model\Client;
use App\Model\PaymentOption;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class MastercardPaymentController extends Controller
{
    private object $credentials;
    private string $gatewayUrl;
    private int    $payopt_id;

    private Mastercard $client;

    public function __construct()
    {
        $pay_option = PaymentOption::where('code', 'mastercard')->where('status', 1)->get(['credentials', 'test_mode', 'status', 'id'])->first();

        if ($pay_option) {
            $clientDatabase = Client::select(['database_name'])->pluck('database_name')->first();
            Cache::store('redis')->set('client-database', $clientDatabase);
        } else {
            $clientDatabase = Cache::store('redis')->get('client-database');
            $request = new Request();
            $request->headers->add([
                'client' => [$clientDatabase],
            ]);

            (new DbChooserApi())->handle($request, fn($_) => $_);
            $pay_option = PaymentOption::where('code', 'mastercard')->where('status', 1)->get(['credentials', 'test_mode', 'status', 'id'])->first();
        }

        $this->credentials = json_decode($pay_option->credentials);

        $this->gatewayUrl = mastercardGateway();
        $this->payopt_id  = $pay_option->id;

        $this->client = new Mastercard(
            (($pay_option->test_mode == 1) ? 'TEST' : '') . $this->credentials->mastercard_merchant_id,
            $this->credentials->mastercard_merchant_key,
            $this->gatewayUrl,
        );
    }

    public function createSession(Request $request) {
        $order_id = time();

        if ($request->input('action') == 'wallet') {
            $payment = AgentPayment::create([
                'dr'           => $request->amount,
                'driver_id'    => auth()->user()->id,
                'payment_from' => 0,
            ]);

            $order_id = $payment->id;
        }

        $order    = new Order($order_id, 'USD', (int)ceil($request->amount));
        $purchase = (new Purchase($this->credentials->mastercard_merchant_id))
            ->setOrder($order);

        $purchase->getInteraction()
            ->setReturnUrl(url(sprintf('/api/payment/mastercard/return/%s', $order_id)));

        $session_content = $this->client->request(Operation::INITIATE_CHECKOUT, $purchase);
        if (!$session_content) return response()->json([
            'status' => 'Error',
            'data'   => $this->client->error(),
        ], 500);

        Cache::store('redis')->set('dispatch-' . $order_id, [
            'session_data' => $session_content,
            'action'       => $request->action,
        ]);

        return response()->json([
            'status' => 'Success',
            'data'   => sprintf('https://%s/checkout/pay/%s?checkoutVersion=1.0.0', $this->gatewayUrl, $session_content->session->id)
        ]);
    }

    public function afterPayment(Request $request, string $order_id) {
        $session_content = Cache::store('redis')->get('dispatch-' . $order_id);
        [   'session_data' => $session_data,
            'action'       => $action
        ] = $session_content;

        if ($request->resultIndicator != $session_data->successIndicator) return redirect()
            ->to(url('/payment/gateway/returnResponse?status=500&gateway=mastercard?transaction_id=' . $order_id));

        if ($action != 'wallet') return redirect()
            ->to(url('/payment/gateway/returnResponse?status=500&gateway=mastercard?transaction_id=' . $order_id));

        try {
            $agentPayment     = AgentPayment::find($order_id);
            $walletController = new WalletController();

            $request->request->add([
                'wallet_amount'     => $agentPayment->dr,
                'transaction_id'    => $order_id,
                'payment_option_id' => $this->payopt_id,
            ]);

            $walletController->creditAgentWallet($request);

            return redirect()
                ->to(url('/payment/gateway/returnResponse?status=200&gateway=mastercard?transaction_id=' . $order_id));
        } catch (\Throwable $err) {
            return response()->json([
                'status' => 'error',
                'data'   => $err->getMessage(),
                'code'   => $err->getCode(),
            ], 500);
        }
    }
}
