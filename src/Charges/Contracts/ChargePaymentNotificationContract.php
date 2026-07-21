<?php

namespace Elqora\Dgp\Charges\Contracts;

use Elqora\Dgp\Charges\ChargePaymentNotification;
use Elqora\Dgp\Errors\Result;

interface ChargePaymentNotificationContract
{
    /**
     * Notify the handler that the host has recorded a payment state change.
     *
     * @param ChargePaymentNotification $notification
     * @return Result<null>
     */
    public function notifyPayment(ChargePaymentNotification $notification): Result;
}
