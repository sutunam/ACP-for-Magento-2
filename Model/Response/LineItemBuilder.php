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
use Magento\Quote\Model\Quote\Item as QuoteItem;

/**
 * Builds ACP line_items array from Magento Quote items
 */
class LineItemBuilder
{
    /**
     * Build line items from quote
     *
     * @param Quote $quote
     * @return array
     */
    public function build(Quote $quote): array
    {
        $lineItems = [];
        $itemIndex = 1;

        foreach ($quote->getAllVisibleItems() as $item) {
            $lineItems[] = $this->buildLineItem($item, $itemIndex, $quote->getQuoteCurrencyCode());
            $itemIndex++;
        }

        return $lineItems;
    }

    /**
     * Build single line item from quote item
     *
     * @param QuoteItem $item
     * @param int $index
     * @param string $currencyCode
     * @return array
     */
    private function buildLineItem(QuoteItem $item, int $index, string $currencyCode): array
    {
        $qty = (int)$item->getQty();

        // Calculate amounts - convert to cents (integers) per ACP spec
        $baseAmount = $item->getPrice() * $qty;
        $discountAmount = (float)$item->getDiscountAmount();
        $taxAmount = (float)$item->getTaxAmount();
        $totalAmount = $item->getRowTotalInclTax();

        return [
            'item_id' => 'item_' . $index,
            'product_title' => $item->getName(),
            'sku' => $item->getSku(),
            'quantity' => $qty,
            'base_amount' => [
                'amount' => $this->convertToCents($baseAmount),
                'currency' => $currencyCode
            ],
            'discount_amount' => [
                'amount' => $this->convertToCents($discountAmount),
                'currency' => $currencyCode
            ],
            'tax_amount' => [
                'amount' => $this->convertToCents($taxAmount),
                'currency' => $currencyCode
            ],
            'total_amount' => [
                'amount' => $this->convertToCents($totalAmount),
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
