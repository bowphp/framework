<?php

namespace Bow\Tests\Container\Stubs;

class PaypalPaymentGateway implements PaymentGatewayInterface
{
    /**
     * @inheritDoc
     */
    public function process(float $amount): bool
    {
        return $amount >= 1.0;
    }

    /**
     * @inheritDoc
     */
    public function getName(): string
    {
        return 'paypal';
    }
}
