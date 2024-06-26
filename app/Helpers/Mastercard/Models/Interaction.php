<?php

namespace App\Helpers\Mastercard\Models;

final class Interaction implements Model
{
    private object $object;

    /**
     * Construct an interaction model for a given operation
     *
     * @param string $operation an item from `Mastercard\Interaction`
     */
    public function __construct(string $operation)
    {
        $this->object = (object)(compact('operation'));
    }

    /**
     * Sets the url to which the user will be redirected to
     * once the interaction is completed.
     *
     * @param string $return_url
     */
    public function setReturnUrl(string $return_url): self
    {
        $this->object->returnUrl = $return_url;
        return $this;
    }

    /**
     * Sets the cancel url for the interaction
     *
     * @param string $cancel_url
     */
    public function setCancelUrl(string $cancel_url): self {
        $this->object->cancelUrl = $cancel_url;
        return $this;
    }

    public function setTimeoutUrl(string $timeout_url): self {
        $this->object->timeoutUrl = $timeout_url;
        return $this;
    }

    public function setMerchantId(string $merchant_id): self
    {
        if (isset($this->object->merchant)) {
            $this->object->merchant['name'] = $merchant_id;
        } else {
            $this->object->merchant = ['name' => $merchant_id];
        };

        return $this;
    }

    public function toJson(): array
    {
        return (array)$this->object;
    }
};
