<?php

namespace App\Helpers\Mastercard\Models;

class Order implements Model
{
    private object $object;

    /**
     * Constructs a new Order model
     *
     * @param string $reference_id refrence/order id
     * @param string $currency     currency used for creating payment
     * @param float  $amount       The total payable amount for the order
     */
    public function __construct(
        string $reference_id,
        ?string $currency = 'USD',
        ?float $amount = null
    ) {
        $this->object = (object)[
            'id'        => $reference_id,
            'currency'  => $currency,
            'reference' => time() . rand(00000, 99999),
        ];

        if ($amount) $this->object->amount = (string)$amount;
    }

    public function getOrderId(): string {
        return $this->object->id;
    }

    public function setOrderId(string $reference_id): self {
        $this->object->id = $reference_id;
        return $this;
    }

    /**
     * Sets the total payable amount for the order
     *
     * @param float $amount
     */
    public function setAmount(float $amount): self
    {
        $this->object->amount = (string)$amount;
        return $this;
    }

    /**
     * Sets the currency to be used for creating payment
     *
     * @param string $currency
     */
    public function setCurrency(string $currency): self
    {
        $this->object->currency = $currency;
        return $this;
    }

    /**
     * Sets the description of order to be displayed on
     * the payment page
     *
     * @param string $description
     */
    public function setDescription(string $description): self
    {
        $this->object->description = $description;
        return $this;
    }

    public function getReference(): int {
        return $this->object->reference;
    }

    public function toJson(): array
    {
        if (!isset($this->object->amount)) throw new \Error('amount must be set');

        if (!isset($this->object->description)) {
            $this->object->description = "Payment for Order ID#" . $this->object->id;
        }

        return (array)$this->object;
    }
};
