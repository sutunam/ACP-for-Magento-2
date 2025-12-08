<?php
/**
 * @author    run_as_root GmbH <info@run-as-root.sh>
 * @copyright 2025 run_as_root GmbH
 * @license   https://opensource.org/license/mit  MIT License
 * @link      https://run-as-root.sh/
 */

declare(strict_types=1);

namespace RunAsRoot\AgenticCommerceProtocol\Model\Response;

use Magento\Quote\Model\Quote;

/**
 * Builds ACP total_details from Magento Quote totals
 */
class TotalDetailsBuilder
{
    /**
     * Build total details from quote
     *
     * @param Quote $quote
     * @return array
     */
    public function build(Quote $quote): array
    {
        $currencyCode = $quote->getQuoteCurrencyCode();

        // Collect totals if not already done
        if (!$quote->getTotalsCollectedFlag()) {
            $quote->collectTotals();
        }

        $shippingAddress = $quote->getShippingAddress();

        // Get totals
        $subtotal = (float)$quote->getSubtotal();
        $shippingAmount = $shippingAddress ? (float)$shippingAddress->getShippingAmount() : 0.0;
        $taxAmount = $shippingAddress ? (float)$shippingAddress->getTaxAmount() : 0.0;
        $discountAmount = abs((float)$quote->getSubtotal() - (float)$quote->getSubtotalWithDiscount());
        $grandTotal = (float)$quote->getGrandTotal();

        return [
            'subtotal_amount' => [
                'amount' => $this->convertToCents($subtotal),
                'currency' => $currencyCode
            ],
            'shipping_amount' => [
                'amount' => $this->convertToCents($shippingAmount),
                'currency' => $currencyCode
            ],
            'tax_amount' => [
                'amount' => $this->convertToCents($taxAmount),
                'currency' => $currencyCode
            ],
            'discount_amount' => [
                'amount' => $this->convertToCents($discountAmount),
                'currency' => $currencyCode
            ],
            'total_amount' => [
                'amount' => $this->convertToCents($grandTotal),
                'currency' => $currencyCode
            ]
        ];
    }

    /**
     * Convert dollar amount to cents (integer)
     * ACP spec requires monetary values as non-negative integers
     *
     * @param float $amount
     * @return int
     */
    private function convertToCents(float $amount): int
    {
        return (int)round($amount * 100);
    }
}
