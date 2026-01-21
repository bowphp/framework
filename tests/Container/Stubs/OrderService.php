<?php

namespace Bow\Tests\Container\Stubs;

class OrderService
{
    /**
     * @var PaymentGatewayInterface
     */
    private PaymentGatewayInterface $paymentGateway;

    /**
     * @var LoggerInterface
     */
    private LoggerInterface $logger;

    /**
     * OrderService constructor
     *
     * @param PaymentGatewayInterface $paymentGateway
     * @param LoggerInterface $logger
     */
    public function __construct(PaymentGatewayInterface $paymentGateway, LoggerInterface $logger)
    {
        $this->paymentGateway = $paymentGateway;
        $this->logger = $logger;
    }

    /**
     * Get the payment gateway
     *
     * @return PaymentGatewayInterface
     */
    public function getPaymentGateway(): PaymentGatewayInterface
    {
        return $this->paymentGateway;
    }

    /**
     * Get the logger
     *
     * @return LoggerInterface
     */
    public function getLogger(): LoggerInterface
    {
        return $this->logger;
    }

    /**
     * Process an order
     *
     * @param float $amount
     * @return bool
     */
    public function processOrder(float $amount): bool
    {
        $this->logger->log("Processing order for amount: {$amount}");

        return $this->paymentGateway->process($amount);
    }
}
