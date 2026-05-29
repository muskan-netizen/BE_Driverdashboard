<?php

namespace App\Model\Order;

use Illuminate\Database\Eloquent\Model;

/**
 * Order row on the order-panel DB (connection royoorder), keyed by dispatch {@see \App\Model\Order::$sync_order_id}.
 *
 * Driver payout share (when COD is zero) uses {@see $payable_amount} on this table.
 * Panel `driver_share_otp` is validated on drop completion using dispatch `sync_order_id` or `order_number`.
 */
class Order extends Model
{
    protected $connection = 'royoorder';

    protected $table = 'orders';
}
