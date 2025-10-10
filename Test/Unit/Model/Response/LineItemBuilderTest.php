<?php
/**
 * @author    Run_As_Root <hello@run-as-root.sh>
 * @copyright 2025 Run_As_Root
 * @license   https://opensource.org/license/mit  MIT License
 * @link      https://run-as-root.sh/
 */

declare(strict_types=1);

namespace RunAsRoot\AgenticCommerceProtocol\Test\Unit\Model\Response;

use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Item as QuoteItem;
use PHPUnit\Framework\TestCase;
use RunAsRoot\AgenticCommerceProtocol\Model\Response\LineItemBuilder;

final class LineItemBuilderTest extends TestCase
{
    private LineItemBuilder $lineItemBuilder;

    protected function setUp(): void
    {
        $this->lineItemBuilder = new LineItemBuilder();
    }

    public function test_build_converts_dollars_to_cents(): void
    {
        $quote = $this->createMock(Quote::class);
        $quote->method('getQuoteCurrencyCode')->willReturn('USD');

        $item = $this->createMock(QuoteItem::class);
        $item->method('getId')->willReturn(123);
        $item->method('getName')->willReturn('Test Product');
        $item->method('getSku')->willReturn('TEST-SKU');
        $item->method('getQty')->willReturn(2);
        $item->method('getPrice')->willReturn(50.00); // $50.00
        $item->method('getDiscountAmount')->willReturn(5.00); // $5.00 discount
        $item->method('getTaxAmount')->willReturn(8.50); // $8.50 tax
        $item->method('getRowTotalInclTax')->willReturn(103.50); // $103.50 total

        $quote->method('getAllVisibleItems')->willReturn([$item]);

        $result = $this->lineItemBuilder->build($quote);

        $this->assertCount(1, $result);
        $lineItem = $result[0];

        // Critical: Verify cents conversion (integers, not floats)
        $this->assertSame(10000, $lineItem['base_amount']['amount']); // $100.00 (2 * $50) = 10000 cents
        $this->assertSame(500, $lineItem['discount_amount']['amount']); // $5.00 = 500 cents
        $this->assertSame(850, $lineItem['tax_amount']['amount']); // $8.50 = 850 cents
        $this->assertSame(10350, $lineItem['total_amount']['amount']); // $103.50 = 10350 cents

        // Verify amounts are integers (not floats)
        $this->assertIsInt($lineItem['base_amount']['amount']);
        $this->assertIsInt($lineItem['discount_amount']['amount']);
        $this->assertIsInt($lineItem['tax_amount']['amount']);
        $this->assertIsInt($lineItem['total_amount']['amount']);
    }

    public function test_build_includes_all_required_fields(): void
    {
        $quote = $this->createMock(Quote::class);
        $quote->method('getQuoteCurrencyCode')->willReturn('EUR');

        $item = $this->createMock(QuoteItem::class);
        $item->method('getId')->willReturn(456);
        $item->method('getName')->willReturn('Another Product');
        $item->method('getSku')->willReturn('SKU-789');
        $item->method('getQty')->willReturn(1);
        $item->method('getPrice')->willReturn(25.99);
        $item->method('getDiscountAmount')->willReturn(0.00);
        $item->method('getTaxAmount')->willReturn(5.20);
        $item->method('getRowTotalInclTax')->willReturn(31.19);

        $quote->method('getAllVisibleItems')->willReturn([$item]);

        $result = $this->lineItemBuilder->build($quote);
        $lineItem = $result[0];

        // Verify all ACP-required fields present
        $this->assertArrayHasKey('item_id', $lineItem);
        $this->assertArrayHasKey('product_title', $lineItem);
        $this->assertArrayHasKey('sku', $lineItem);
        $this->assertArrayHasKey('quantity', $lineItem);
        $this->assertArrayHasKey('base_amount', $lineItem);
        $this->assertArrayHasKey('discount_amount', $lineItem);
        $this->assertArrayHasKey('tax_amount', $lineItem);
        $this->assertArrayHasKey('total_amount', $lineItem);

        // Verify amount structure
        $this->assertArrayHasKey('amount', $lineItem['base_amount']);
        $this->assertArrayHasKey('currency', $lineItem['base_amount']);
        $this->assertEquals('EUR', $lineItem['base_amount']['currency']);
    }

    public function test_build_generates_stable_item_ids(): void
    {
        $quote = $this->createMock(Quote::class);
        $quote->method('getQuoteCurrencyCode')->willReturn('USD');

        $item1 = $this->createMock(QuoteItem::class);
        $item1->method('getId')->willReturn(789);
        $item1->method('getName')->willReturn('Product 1');
        $item1->method('getSku')->willReturn('SKU-1');
        $item1->method('getQty')->willReturn(1);
        $item1->method('getPrice')->willReturn(10.00);
        $item1->method('getDiscountAmount')->willReturn(0.00);
        $item1->method('getTaxAmount')->willReturn(0.00);
        $item1->method('getRowTotalInclTax')->willReturn(10.00);

        $quote->method('getAllVisibleItems')->willReturn([$item1]);

        $result = $this->lineItemBuilder->build($quote);

        // Item ID should be based on quote item ID
        $this->assertEquals('item_789', $result[0]['item_id']);
    }

    public function test_build_handles_multiple_items(): void
    {
        $quote = $this->createMock(Quote::class);
        $quote->method('getQuoteCurrencyCode')->willReturn('USD');

        $item1 = $this->createMock(QuoteItem::class);
        $item1->method('getId')->willReturn(1);
        $item1->method('getName')->willReturn('Product 1');
        $item1->method('getSku')->willReturn('SKU-1');
        $item1->method('getQty')->willReturn(1);
        $item1->method('getPrice')->willReturn(10.00);
        $item1->method('getDiscountAmount')->willReturn(0.00);
        $item1->method('getTaxAmount')->willReturn(0.00);
        $item1->method('getRowTotalInclTax')->willReturn(10.00);

        $item2 = $this->createMock(QuoteItem::class);
        $item2->method('getId')->willReturn(2);
        $item2->method('getName')->willReturn('Product 2');
        $item2->method('getSku')->willReturn('SKU-2');
        $item2->method('getQty')->willReturn(3);
        $item2->method('getPrice')->willReturn(20.00);
        $item2->method('getDiscountAmount')->willReturn(5.00);
        $item2->method('getTaxAmount')->willReturn(3.50);
        $item2->method('getRowTotalInclTax')->willReturn(58.50);

        $quote->method('getAllVisibleItems')->willReturn([$item1, $item2]);

        $result = $this->lineItemBuilder->build($quote);

        $this->assertCount(2, $result);
        $this->assertEquals('SKU-1', $result[0]['sku']);
        $this->assertEquals('SKU-2', $result[1]['sku']);
        $this->assertSame(1000, $result[0]['total_amount']['amount']); // $10.00
        $this->assertSame(5850, $result[1]['total_amount']['amount']); // $58.50
    }

    public function test_build_rounds_correctly_to_nearest_cent(): void
    {
        $quote = $this->createMock(Quote::class);
        $quote->method('getQuoteCurrencyCode')->willReturn('USD');

        $item = $this->createMock(QuoteItem::class);
        $item->method('getId')->willReturn(1);
        $item->method('getName')->willReturn('Fractional Product');
        $item->method('getSku')->willReturn('FRAC-SKU');
        $item->method('getQty')->willReturn(3);
        $item->method('getPrice')->willReturn(33.333); // $33.333 each
        $item->method('getDiscountAmount')->willReturn(0.00);
        $item->method('getTaxAmount')->willReturn(2.225); // $2.225
        $item->method('getRowTotalInclTax')->willReturn(102.225);

        $quote->method('getAllVisibleItems')->willReturn([$item]);

        $result = $this->lineItemBuilder->build($quote);

        // Should round $99.999 -> 10000 cents, $2.225 -> 223 cents, $102.225 -> 10223 cents
        $this->assertSame(10000, $result[0]['base_amount']['amount']);
        $this->assertSame(223, $result[0]['tax_amount']['amount']);
        $this->assertSame(10223, $result[0]['total_amount']['amount']);
    }
}
