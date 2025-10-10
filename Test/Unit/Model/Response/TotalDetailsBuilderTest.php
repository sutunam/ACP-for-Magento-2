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
use Magento\Quote\Model\Quote\Address;
use PHPUnit\Framework\TestCase;
use RunAsRoot\AgenticCommerceProtocol\Model\Response\TotalDetailsBuilder;

final class TotalDetailsBuilderTest extends TestCase
{
    private TotalDetailsBuilder $totalDetailsBuilder;

    protected function setUp(): void
    {
        $this->totalDetailsBuilder = new TotalDetailsBuilder();
    }

    public function test_build_converts_all_amounts_to_cents(): void
    {
        $shippingAddress = $this->createMock(Address::class);
        $shippingAddress->method('getShippingAmount')->willReturn(10.00); // $10.00 shipping
        $shippingAddress->method('getTaxAmount')->willReturn(8.75); // $8.75 tax

        $quote = $this->createMock(Quote::class);
        $quote->method('getQuoteCurrencyCode')->willReturn('USD');
        $quote->method('getTotalsCollectedFlag')->willReturn(true);
        $quote->method('getSubtotal')->willReturn(100.00); // $100 subtotal
        $quote->method('getSubtotalWithDiscount')->willReturn(90.00); // $10 discount
        $quote->method('getGrandTotal')->willReturn(108.75); // $108.75 total
        $quote->method('getShippingAddress')->willReturn($shippingAddress);

        $result = $this->totalDetailsBuilder->build($quote);

        // Verify cents conversion
        $this->assertSame(10000, $result['subtotal_amount']['amount']); // $100
        $this->assertSame(1000, $result['shipping_amount']['amount']); // $10
        $this->assertSame(875, $result['tax_amount']['amount']); // $8.75
        $this->assertSame(1000, $result['discount_amount']['amount']); // $10 discount
        $this->assertSame(10875, $result['total_amount']['amount']); // $108.75

        // Verify all are integers
        $this->assertIsInt($result['subtotal_amount']['amount']);
        $this->assertIsInt($result['shipping_amount']['amount']);
        $this->assertIsInt($result['tax_amount']['amount']);
        $this->assertIsInt($result['discount_amount']['amount']);
        $this->assertIsInt($result['total_amount']['amount']);
    }

    public function test_build_includes_all_required_fields(): void
    {
        $shippingAddress = $this->createMock(Address::class);
        $shippingAddress->method('getShippingAmount')->willReturn(5.00);
        $shippingAddress->method('getTaxAmount')->willReturn(0.00);

        $quote = $this->createMock(Quote::class);
        $quote->method('getQuoteCurrencyCode')->willReturn('EUR');
        $quote->method('getTotalsCollectedFlag')->willReturn(true);
        $quote->method('getSubtotal')->willReturn(50.00);
        $quote->method('getSubtotalWithDiscount')->willReturn(50.00);
        $quote->method('getGrandTotal')->willReturn(55.00);
        $quote->method('getShippingAddress')->willReturn($shippingAddress);

        $result = $this->totalDetailsBuilder->build($quote);

        $this->assertArrayHasKey('subtotal_amount', $result);
        $this->assertArrayHasKey('shipping_amount', $result);
        $this->assertArrayHasKey('tax_amount', $result);
        $this->assertArrayHasKey('discount_amount', $result);
        $this->assertArrayHasKey('total_amount', $result);

        // Verify currency in all amounts
        $this->assertEquals('EUR', $result['subtotal_amount']['currency']);
        $this->assertEquals('EUR', $result['shipping_amount']['currency']);
        $this->assertEquals('EUR', $result['tax_amount']['currency']);
    }

    public function test_build_handles_zero_discount(): void
    {
        $shippingAddress = $this->createMock(Address::class);
        $shippingAddress->method('getShippingAmount')->willReturn(0.00);
        $shippingAddress->method('getTaxAmount')->willReturn(0.00);

        $quote = $this->createMock(Quote::class);
        $quote->method('getQuoteCurrencyCode')->willReturn('USD');
        $quote->method('getTotalsCollectedFlag')->willReturn(true);
        $quote->method('getSubtotal')->willReturn(100.00);
        $quote->method('getSubtotalWithDiscount')->willReturn(100.00); // No discount
        $quote->method('getGrandTotal')->willReturn(100.00);
        $quote->method('getShippingAddress')->willReturn($shippingAddress);

        $result = $this->totalDetailsBuilder->build($quote);

        $this->assertSame(0, $result['discount_amount']['amount']);
    }

    public function test_build_handles_no_shipping_address(): void
    {
        $quote = $this->createMock(Quote::class);
        $quote->method('getQuoteCurrencyCode')->willReturn('USD');
        $quote->method('getTotalsCollectedFlag')->willReturn(true);
        $quote->method('getSubtotal')->willReturn(50.00);
        $quote->method('getSubtotalWithDiscount')->willReturn(50.00);
        $quote->method('getGrandTotal')->willReturn(50.00);
        $quote->method('getShippingAddress')->willReturn(null);

        $result = $this->totalDetailsBuilder->build($quote);

        // Should default to 0 for shipping and tax
        $this->assertSame(0, $result['shipping_amount']['amount']);
        $this->assertSame(0, $result['tax_amount']['amount']);
        $this->assertSame(5000, $result['subtotal_amount']['amount']);
    }
}
