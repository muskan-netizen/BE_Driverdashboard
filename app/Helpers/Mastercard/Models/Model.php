<?php

namespace App\Helpers\Mastercard\Models;

interface Model
{
    /**
     * Converts any mastercard operation model to json data
     *
     * @return array<string, mixed>
     */
    public function toJson(): array;
};
