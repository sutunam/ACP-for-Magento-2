<?php
/**
 * @author    Run_As_Root <hello@run-as-root.sh>
 * @copyright 2025 Run_As_Root
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
                'amount' => round($subtotal, 2),
                'currency' => $currencyCode
            ],
            'shipping_amount' => [
                'amount' => round($shippingAmount, 2),
                'currency' => $currencyCode
            ],
            'tax_amount' => [
                'amount' => round($taxAmount, 2),
                'currency' => $currencyCode
            ],
            'discount_amount' => [
                'amount' => round($discountAmount, 2),
                'currency' => $currencyCode
            ],
            'total_amount' => [
                'amount' => round($grandTotal, 2),
                'currency' => $currencyCode
            ]
        ];
    }
}
