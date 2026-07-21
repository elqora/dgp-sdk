<?php

namespace Elqora\Dgp\Tests\Fixtures\Handlers;

use Elqora\Dgp\Charges\ChargePaymentNotification;
use Elqora\Dgp\Charges\Contracts\ChargePaymentNotificationContract;
use Elqora\Dgp\Errors\Result;

class PaymentNotificationTestHandler extends ManualTestHandler implements ChargePaymentNotificationContract
{
    public ?ChargePaymentNotification $lastPaymentNotification = null;

    public function notifyPayment(ChargePaymentNotification $notification): Result
    {
        $this->lastPaymentNotification = $notification;

        return Result::success(null);
    }
}
