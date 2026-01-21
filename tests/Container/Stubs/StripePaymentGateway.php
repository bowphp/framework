<?php

namespace Bow\Tests\Container\Stubs;

class StripePaymentGateway implements PaymentGatewayInterface
{
    /**
     * @inheritDoc
     */
    public function process(float $amount): bool
    {
        return $amount > 0;
    }

    /**
     * @inheritDoc
     */
    public function getName(): string
    {
        return 'stripe';
    }
}
