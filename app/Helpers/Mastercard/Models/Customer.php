<?php

namespace App\Helpers\Mastercard\Models;

class Customer implements Model
{
    private object $object;

    public function __construct(string $customer_name)
    {
        $firstName = explode(' ', $customer_name, 1)[0];
        $lastName  = explode(' ', $customer_name, 2)[1] ?? ' ';

        $this->object = (object)compact('firstName', 'lastName');
    }

    public function setEmail(string $email): self
    {
        $this->object->email = $email;
        return $this;
    }

    public function setMobilePhone(string $mobile_no): self
    {
        $this->object->mobilePhone = $mobile_no;
        return $this;
    }

    public function toJson(): array
    {
        return (array)$this->object;
    }
};
