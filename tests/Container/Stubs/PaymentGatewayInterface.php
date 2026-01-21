<?php

namespace Bow\Tests\Container\Stubs;

interface PaymentGatewayInterface
{
    /**
     * Process a payment
     *
     * @param float $amount
     * @return bool
     */
    public function process(float $amount): bool;

    /**
     * Get the gateway name
     *
     * @return string
     */
    public function getName(): string;
}
