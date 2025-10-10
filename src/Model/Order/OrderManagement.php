<?php
/**
 * @author    Run_As_Root <hello@run-as-root.sh>
 * @copyright 2025 Run_As_Root
 * @license   https://opensource.org/license/mit  MIT License
 * @link      https://run-as-root.sh/
 */

declare(strict_types=1);

namespace RunAsRoot\AgenticCommerceProtocol\Model\Order;

use Magento\Quote\Api\CartManagementInterface;
use Magento\Quote\Model\Quote;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use RunAsRoot\AgenticCommerceProtocol\Exception\PaymentDeclinedException;
use RunAsRoot\AgenticCommerceProtocol\Model\Payment\DelegatedPaymentProcessor;

/**
 * Manages order creation from ACP sessions
 */
class OrderManagement
{
    public function __construct(
        private readonly CartManagementInterface $cartManagement,
        private readonly OrderRepositoryInterface $orderRepository,
        private readonly DelegatedPaymentProcessor $paymentProcessor
    ) {
    }

    /**
     * Create order from quote with payment processing
     *
     * @param Quote $quote
     * @param array $paymentData
     * @param string $checkoutSessionId
     * @return OrderInterface
     * @throws PaymentDeclinedException
     */
    public function createOrder(Quote $quote, array $paymentData, string $checkoutSessionId): OrderInterface
    {
        // Process payment first
        $paymentResult = $this->paymentProcessor->processPayment($quote, $paymentData);

        if (!$paymentResult['success']) {
            throw new PaymentDeclinedException(__('Payment authorization failed'));
        }

        // Set payment method
        $payment = $quote->getPayment();
        $payment->setMethod('acp_stripe');
        $payment->setAdditionalInformation('payment_token', $paymentData['token'] ?? null);
        $payment->setAdditionalInformation('transaction_id', $paymentResult['transaction_id']);
        $payment->setAdditionalInformation('acp_session_id', $checkoutSessionId);

        // Create order from quote
        $orderId = $this->cartManagement->placeOrder($quote->getId());
        $order = $this->orderRepository->get($orderId);

        // Store ACP session ID on order for webhook tracking
        $order->setData('acp_checkout_session_id', $checkoutSessionId);
        $this->orderRepository->save($order);

        return $order;
    }
}
