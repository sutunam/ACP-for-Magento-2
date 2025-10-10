<?php
/**
 * @author    run_as_root GmbH <info@run-as-root.sh>
 * @copyright 2025 run_as_root GmbH
 * @license   https://opensource.org/license/mit  MIT License
 * @link      https://run-as-root.sh/
 */

declare(strict_types=1);

namespace RunAsRoot\AgenticCommerceProtocol\Test\Unit\Model\Validator;

use Magento\Framework\Exception\LocalizedException;
use PHPUnit\Framework\TestCase;
use RunAsRoot\AgenticCommerceProtocol\Model\Validator\SessionValidator;

final class SessionValidatorTest extends TestCase
{
    private SessionValidator $validator;

    protected function setUp(): void
    {
        $this->validator = new SessionValidator();
    }

    public function test_validate_items_throws_exception_for_empty_array(): void
    {
        $this->expectException(LocalizedException::class);
        $this->expectExceptionMessage('At least one item is required');

        $this->validator->validateItems([]);
    }

    public function test_validate_items_throws_exception_for_missing_sku(): void
    {
        $this->expectException(LocalizedException::class);
        $this->expectExceptionMessage('Item at index 0 is missing required field: sku');

        $this->validator->validateItems([['quantity' => 1]]);
    }

    public function test_validate_items_throws_exception_for_zero_quantity(): void
    {
        $this->expectException(LocalizedException::class);
        $this->expectExceptionMessage('Item quantity must be greater than 0');

        $this->validator->validateItems([['sku' => 'TEST-001', 'quantity' => 0]]);
    }

    public function test_validate_items_passes_for_valid_items(): void
    {
        $items = [
            ['sku' => 'TEST-001', 'quantity' => 2],
            ['sku' => 'TEST-002']
        ];

        $this->validator->validateItems($items);
        $this->assertTrue(true);
    }

    public function test_validate_buyer_throws_exception_for_missing_email(): void
    {
        $this->expectException(LocalizedException::class);
        $this->expectExceptionMessage('Buyer email is required');

        $this->validator->validateBuyer(['name' => 'John Doe']);
    }

    public function test_validate_buyer_throws_exception_for_invalid_email(): void
    {
        $this->expectException(LocalizedException::class);
        $this->expectExceptionMessage('Buyer email is invalid');

        $this->validator->validateBuyer(['email' => 'not-an-email']);
    }

    public function test_validate_payment_data_throws_for_missing_token(): void
    {
        $this->expectException(LocalizedException::class);
        $this->expectExceptionMessage('Payment token is required');

        $this->validator->validatePaymentData([]);
    }

    public function test_validate_status_transition_throws_for_invalid_transition(): void
    {
        $this->expectException(LocalizedException::class);
        $this->expectExceptionMessage('Cannot transition from completed to open');

        $this->validator->validateStatusTransition('completed', 'open');
    }
}
