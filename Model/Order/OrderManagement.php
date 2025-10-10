<?php
/**
 * @author    Run_As_Root <hello@run-as-root.sh>
 * @copyright 2025 Run_As_Root
 * @license   https://opensource.org/license/mit  MIT License
 * @link      https://run-as-root.sh/
 */

declare(strict_types=1);

namespace RunAsRoot\AgenticCommerceProtocol\Model\Order;

use Magento\Framework\UrlInterface;
use Magento\Quote\Api\CartManagementInterface;
use Magento\Quote\Model\Quote;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Store\Model\StoreManagerInterface;
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
        private readonly DelegatedPaymentProcessor $paymentProcessor,
        private readonly StoreManagerInterface $storeManager
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

        // Track if confirmation email was sent
        $emailSent = (bool)$order->getEmailSent();
        $order->setData('acp_confirmation_email_sent', $emailSent);

        $this->orderRepository->save($order);

        return $order;
    }

    /**
     * Get order view URL for customer
     *
     * @param OrderInterface $order
     * @return string
     */
    public function getOrderUrl(OrderInterface $order): string
    {
        $store = $this->storeManager->getStore();
        $baseUrl = $store->getBaseUrl(UrlInterface::URL_TYPE_WEB);

        return $baseUrl . 'sales/order/view/order_id/' . $order->getEntityId();
    }

    /**
     * Check if confirmation email was sent
     *
     * @param OrderInterface $order
     * @return bool
     */
    public function wasConfirmationEmailSent(OrderInterface $order): bool
    {
        return (bool)$order->getData('acp_confirmation_email_sent');
    }
}
