<?php
/**
 * @author    run_as_root GmbH <info@run-as-root.sh>
 * @copyright 2025 run_as_root GmbH
 * @license   https://opensource.org/license/mit  MIT License
 * @link      https://run-as-root.sh/
 */

declare(strict_types=1);

namespace RunAsRoot\AgenticCommerceProtocol\Model\Validator;

use Magento\Framework\Exception\LocalizedException;

/**
 * Validates checkout session data
 */
class SessionValidator
{
    /**
     * Validate items array
     */
    public function validateItems(array $items): void
    {
        if (empty($items)) {
            throw new LocalizedException(__('At least one item is required'));
        }

        foreach ($items as $index => $item) {
            if (!isset($item['sku']) || empty($item['sku'])) {
                throw new LocalizedException(
                    __('Item at index %1 is missing required field: sku', $index)
                );
            }

            if (isset($item['quantity']) && $item['quantity'] <= 0) {
                throw new LocalizedException(
                    __('Item quantity must be greater than 0 for SKU: %1', $item['sku'])
                );
            }
        }
    }

    /**
     * Validate buyer information
     */
    public function validateBuyer(array $buyer): void
    {
        if (empty($buyer['email'])) {
            throw new LocalizedException(__('Buyer email is required'));
        }

        if (!filter_var($buyer['email'], FILTER_VALIDATE_EMAIL)) {
            throw new LocalizedException(__('Buyer email is invalid'));
        }
    }

    /**
     * Validate fulfillment address
     */
    public function validateFulfillmentAddress(array $address): void
    {
        $requiredFields = ['address_line1', 'city', 'postal_code', 'country'];

        foreach ($requiredFields as $field) {
            if (empty($address[$field])) {
                throw new LocalizedException(
                    __('Fulfillment address is missing required field: %1', $field)
                );
            }
        }

        // Validate country code (ISO 3166-1 alpha-2)
        if (!preg_match('/^[A-Z]{2}$/', $address['country'])) {
            throw new LocalizedException(__('Invalid country code format. Use ISO 3166-1 alpha-2'));
        }
    }

    /**
     * Validate payment data
     */
    public function validatePaymentData(array $paymentData): void
    {
        if (empty($paymentData['token'])) {
            throw new LocalizedException(__('Payment token is required'));
        }

        // Validate token format
        if (!preg_match('/^(tok_|src_|pm_|pi_)[a-zA-Z0-9_]+$/', $paymentData['token'])) {
            throw new LocalizedException(__('Invalid payment token format'));
        }
    }

    /**
     * Validate checkout session status transition
     */
    public function validateStatusTransition(string $currentStatus, string $newStatus): void
    {
        $allowedTransitions = [
            'open' => ['completed', 'canceled'],
            'completed' => [],
            'canceled' => []
        ];

        if (!isset($allowedTransitions[$currentStatus])) {
            throw new LocalizedException(__('Invalid current status: %1', $currentStatus));
        }

        if (!in_array($newStatus, $allowedTransitions[$currentStatus], true)) {
            throw new LocalizedException(
                __('Cannot transition from %1 to %2', $currentStatus, $newStatus)
            );
        }
    }
}
