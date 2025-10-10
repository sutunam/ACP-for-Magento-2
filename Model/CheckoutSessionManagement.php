<?php
/**
 * @author    Run_As_Root <hello@run-as-root.sh>
 * @copyright 2025 Run_As_Root
 * @license   https://opensource.org/license/mit  MIT License
 * @link      https://run-as-root.sh/
 */

declare(strict_types=1);

namespace RunAsRoot\AgenticCommerceProtocol\Model;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Store\Model\StoreManagerInterface;
use RunAsRoot\AgenticCommerceProtocol\Api\CheckoutSessionManagementInterface;
use RunAsRoot\AgenticCommerceProtocol\Api\Data\CheckoutSessionInterface;
use RunAsRoot\AgenticCommerceProtocol\Api\Data\CheckoutSessionInterfaceFactory;
use RunAsRoot\AgenticCommerceProtocol\Model\Address\AddressManagement;
use RunAsRoot\AgenticCommerceProtocol\Model\Order\OrderManagement;
use RunAsRoot\AgenticCommerceProtocol\Model\Quote\QuoteManagement;
use RunAsRoot\AgenticCommerceProtocol\Model\Response\CheckoutSessionResponseBuilder;

/**
 * ACP Checkout Session Management Implementation
 */
class CheckoutSessionManagement implements CheckoutSessionManagementInterface
{
    public function __construct(
        private readonly CheckoutSessionInterfaceFactory $checkoutSessionFactory,
        private readonly StoreManagerInterface $storeManager,
        private readonly CheckoutSessionRepository $sessionRepository,
        private readonly QuoteManagement $quoteManagement,
        private readonly CartRepositoryInterface $cartRepository,
        private readonly OrderManagement $orderManagement,
        private readonly AddressManagement $addressManagement,
        private readonly CheckoutSessionResponseBuilder $responseBuilder
    ) {
    }

    public function create($data): CheckoutSessionInterface
    {
        $requestData = is_string($data) ? json_decode($data, true) : $data;

        if (!isset($requestData['items']) || empty($requestData['items'])) {
            throw new LocalizedException(__('Items are required to create a checkout session'));
        }

        $checkoutSessionId = $this->generateSessionId();

        // Create Magento quote from items
        $quote = $this->quoteManagement->createFromItems($requestData['items']);

        $session = $this->checkoutSessionFactory->create();
        $session->setCheckoutSessionId($checkoutSessionId);
        $session->setStatus('open');
        $session->setItems($requestData['items']);
        $session->setCurrency($quote->getQuoteCurrencyCode());
        $session->setTotal($this->quoteManagement->getQuoteTotal($quote));
        $session->setData('quote_id', $quote->getId());

        $this->sessionRepository->save($session);

        return $session;
    }

    public function update(string $checkoutSessionId, $data): CheckoutSessionInterface
    {
        $session = $this->get($checkoutSessionId);

        $requestData = is_string($data) ? json_decode($data, true) : $data;

        $quoteId = $session->getData('quote_id');
        $quote = $quoteId ? $this->cartRepository->get($quoteId) : null;

        $needsSave = false;

        // Update items if provided
        if (isset($requestData['items'])) {
            $session->setItems($requestData['items']);

            if ($quote) {
                $quote = $this->quoteManagement->updateQuoteItems($quote, $requestData['items']);
            } else {
                $quote = $this->quoteManagement->createFromItems($requestData['items']);
                $session->setData('quote_id', $quote->getId());
            }
            $needsSave = true;
        }

        // Update shipping address if provided
        if (isset($requestData['fulfillment_address']) && $quote) {
            $this->addressManagement->setShippingAddress($quote, $requestData['fulfillment_address']);
            $quote->collectTotals();
            $this->cartRepository->save($quote);
            $session->setData('fulfillment_address', json_encode($requestData['fulfillment_address']));
            $needsSave = true;
        }

        // Update buyer info if provided
        if (isset($requestData['buyer']) && $quote) {
            $quote->setCustomerEmail($requestData['buyer']['email'] ?? null);
            $quote->setCustomerFirstname($requestData['buyer']['first_name'] ?? 'Guest');
            $quote->setCustomerLastname($requestData['buyer']['last_name'] ?? 'Customer');
            $this->cartRepository->save($quote);
            $session->setData('buyer_info', json_encode($requestData['buyer']));
            $needsSave = true;
        }

        // Recalculate total if quote was modified
        if ($quote && $needsSave) {
            $session->setTotal($this->quoteManagement->getQuoteTotal($quote));
        }

        if ($needsSave) {
            $this->sessionRepository->save($session);
        }

        return $session;
    }

    public function get(string $checkoutSessionId): CheckoutSessionInterface
    {
        return $this->sessionRepository->getByCheckoutSessionId($checkoutSessionId);
    }

    public function complete(string $checkoutSessionId, $data): CheckoutSessionInterface
    {
        $session = $this->get($checkoutSessionId);

        if ($session->getStatus() !== 'open') {
            throw new LocalizedException(__('Cannot complete a checkout session that is not open'));
        }

        $requestData = is_string($data) ? json_decode($data, true) : $data;
        $paymentData = $requestData['payment_data'] ?? [];

        if (empty($paymentData)) {
            throw new LocalizedException(__('Payment data is required to complete checkout'));
        }

        // Get quote
        $quoteId = $session->getData('quote_id');
        if (!$quoteId) {
            throw new LocalizedException(__('Quote not found for checkout session'));
        }

        $quote = $this->cartRepository->get($quoteId);

        // Create order with payment processing
        $order = $this->orderManagement->createOrder($quote, $paymentData, $checkoutSessionId);

        // Update session
        $session->setStatus('completed');
        $session->setData('completed_at', date('Y-m-d H:i:s'));
        $session->setData('order_id', $order->getEntityId());
        $session->setData('payment_data', json_encode($paymentData));

        $this->sessionRepository->save($session);

        return $session;
    }

    public function cancel(string $checkoutSessionId): CheckoutSessionInterface
    {
        $session = $this->get($checkoutSessionId);

        if ($session->getStatus() === 'completed') {
            throw new LocalizedException(__('Cannot cancel a completed checkout session'));
        }

        $session->setStatus('canceled');
        $session->setData('canceled_at', date('Y-m-d H:i:s'));

        $this->sessionRepository->save($session);

        return $session;
    }

    private function generateSessionId(): string
    {
        return 'cs_' . bin2hex(random_bytes(16));
    }
}
