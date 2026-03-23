<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Order payable split (GST-inclusive payable_amount / COD total)
    |--------------------------------------------------------------------------
    |
    | Payable is GST-inclusive. On accept: lead_fee from wallet (COD and prepaid).
    | COD on complete: platform_rate × ex-GST subtotal + GST portion from payable.
    | Prepaid on complete: credit (1 − platform_rate) × ex-GST subtotal to wallet.
    |
    */
    'lead_fee' => (float) env('DISPATCH_ORDER_LEAD_FEE', 30),

    'gst_rate' => (float) env('DISPATCH_ORDER_GST_RATE', 0.18),

    'platform_rate' => (float) env('DISPATCH_ORDER_PLATFORM_RATE', 0.20),
];
