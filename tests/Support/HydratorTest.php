<?php

namespace Elqora\Dgp\Tests\Support;

use PHPUnit\Framework\TestCase;
use Elqora\Dgp\Money\Amount;
use Elqora\Dgp\Money\Currency;
use Elqora\Dgp\Money\Money;
use Elqora\Dgp\Errors\DgpError;
use Elqora\Dgp\Support\Hydrator;

class HydratorTest extends TestCase
{
    public function testMoneyRoundTrip(): void
    {
        $amount = new Amount('123.45');
        $currency = new Currency('USD');
        $money = new Money($amount, $currency);

        $serialized = Hydrator::serialize($money);

        $this->assertEquals([
            'amount' => '123.45',
            'currency' => 'USD'
        ], $serialized);

        $hydrated = Hydrator::hydrate(Money::class, $serialized);

        $this->assertEquals('123.45', $hydrated->amount->value);
        $this->assertEquals('USD', $hydrated->currency->code);
        $this->assertTrue(Hydrator::compare($money, $hydrated));
    }

    public function testDgpErrorRoundTrip(): void
    {
        $error = new DgpError(
            code: 'INVALID_INPUT',
            message: 'The input value is invalid.',
            providerCode: 'PROV-001',
            httpStatus: 400,
            details: ['field' => 'quantity'],
            metadata: ['debug_id' => '12345'],
            retryable: true
        );

        $serialized = Hydrator::serialize($error);

        $this->assertEquals([
            'code' => 'INVALID_INPUT',
            'message' => 'The input value is invalid.',
            'provider_code' => 'PROV-001',
            'http_status' => 400,
            'retry_delay' => null,
            'details' => ['field' => 'quantity'],
            'metadata' => ['debug_id' => '12345'],
            'retryable' => true
        ], $serialized);

        $hydrated = Hydrator::hydrate(DgpError::class, $serialized);

        $this->assertEquals('INVALID_INPUT', $hydrated->code);
        $this->assertEquals('PROV-001', $hydrated->providerCode);
        $this->assertEquals(400, $hydrated->httpStatus);
        $this->assertTrue($hydrated->retryable);
        $this->assertTrue(Hydrator::compare($error, $hydrated));
    }
}
