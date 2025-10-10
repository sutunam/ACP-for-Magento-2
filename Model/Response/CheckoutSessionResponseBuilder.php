<?php
/**
 * @author    run_as_root GmbH <info@run-as-root.sh>
 * @copyright 2025 run_as_root GmbH
 * @license   https://opensource.org/license/mit  MIT License
 * @link      https://run-as-root.sh/
 */

declare(strict_types=1);

namespace RunAsRoot\AgenticCommerceProtocol\Model\Response;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Model\Quote;
use RunAsRoot\AgenticCommerceProtocol\Api\Data\CheckoutSessionInterface;

/**
 * Builds ACP-compliant checkout session responses from Magento Quote data
 */
class CheckoutSessionResponseBuilder
{
    public function __construct(
        private readonly CartRepositoryInterface $cartRepository,
        private readonly LineItemBuilder $lineItemBuilder,
        private readonly TotalDetailsBuilder $totalDetailsBuilder,
        private readonly FulfillmentOptionsBuilder $fulfillmentOptionsBuilder
    ) {
    }

    /**
     * Build complete ACP response from session
     *
     * @param CheckoutSessionInterface $session
     * @return array
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function build(CheckoutSessionInterface $session): array
    {
        $response = [
            'checkout_session_id' => $session->getCheckoutSessionId(),
            'status' => $session->getStatus(),
            'payment_provider' => 'stripe',
        ];

        // Get quote if available
        $quoteId = $session->getData('quote_id');
        if ($quoteId) {
            $quote = $this->cartRepository->get($quoteId);
            $response = array_merge($response, $this->buildQuoteData($quote, $session));
        } else {
            // Fallback for sessions without quotes
            $response['currency'] = $session->getCurrency();
            $response['total_details'] = [
                'total_amount' => [
                    'amount' => $session->getTotal(),
                    'currency' => $session->getCurrency()
                ]
            ];
        }

        // Add buyer and fulfillment address if available
        $buyerInfo = $session->getData('buyer_info');
        if ($buyerInfo) {
            $response['buyer'] = json_decode($buyerInfo, true);
        }

        $fulfillmentAddress = $session->getData('fulfillment_address');
        if ($fulfillmentAddress) {
            $response['fulfillment_address'] = json_decode($fulfillmentAddress, true);
        }

        // Add selected fulfillment option if set
        $fulfillmentOptionId = $session->getData('fulfillment_option_id');
        if ($fulfillmentOptionId) {
            $response['selected_fulfillment_option_id'] = $fulfillmentOptionId;
        }

        // Add order details if completed
        if ($session->getStatus() === 'completed') {
            $orderId = $session->getData('order_id');
            $orderIncrementId = $session->getData('order_increment_id');
            $orderUrl = $session->getData('order_url');
            $confirmationEmailSent = $session->getData('confirmation_email_sent');

            if ($orderId) {
                $response['order_id'] = (string)$orderId;
            }

            if ($orderIncrementId) {
                $response['order_number'] = (string)$orderIncrementId;
            }

            if ($orderUrl) {
                $response['order_url'] = $orderUrl;
            }

            if ($confirmationEmailSent !== null) {
                $response['confirmation_email_sent'] = (bool)$confirmationEmailSent;
            }
        }

        return $response;
    }

    /**
     * Build response data from quote
     *
     * @param Quote $quote
     * @param CheckoutSessionInterface $session
     * @return array
     * @throws LocalizedException
     */
    private function buildQuoteData(Quote $quote, CheckoutSessionInterface $session): array
    {
        $data = [
            'currency' => $quote->getQuoteCurrencyCode(),
        ];

        // Build line items
        $data['line_items'] = $this->lineItemBuilder->build($quote);

        // Build total details
        $data['total_details'] = $this->totalDetailsBuilder->build($quote);

        // Build fulfillment options if shipping address is set
        $shippingAddress = $quote->getShippingAddress();
        if ($shippingAddress && $shippingAddress->getCountryId()) {
            $data['fulfillment_options'] = $this->fulfillmentOptionsBuilder->build($quote);
        }

        return $data;
    }
}
