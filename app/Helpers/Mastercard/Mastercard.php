<?php

namespace App\Helpers\Mastercard;

use App\Helpers\Mastercard\Models\Model;
use Illuminate\Support\Str;
use GuzzleHttp\Client;

final class Mastercard
{
    private string $merchant_id;
    private string $merchant_key;

    private Client $client;

    private ?object $error = null;

    public function __construct(string $merchant_id, string $merchant_key, string $gateway_domain = 'test-gateway.mastercard.com')
    {
        $this->merchant_id  = $merchant_id;
        $this->merchant_key = $merchant_key;

        $this->client = new Client([
            'base_uri'    => 'https://' . $gateway_domain,
            'http_errors' => false,

            'auth' => [
                sprintf('merchant.%s', $this->merchant_id),
                $this->merchant_key
            ],
        ]);
    }

    public function request(string $api_operation, Model $model)
    {
        $model_object = $model->toJson();
        $model_object['apiOperation'] = $api_operation;

        $response = $this->client->post(sprintf('api/rest/version/79/merchant/%s/session', $this->merchant_id), [
            'headers' => ['Content-Type' => 'application/json'],
            'body'    => json_encode($model_object),
        ]);

        $rbody = $response->getBody()->getContents();
        $rbody = json_decode($rbody);

        if ($rbody->result == 'SUCCESS') {
            $this->error = null;
            return $rbody;
        }

        $this->error = $rbody;
        return null;
    }

    public function error()
    {
        return $this->error;
    }
};
