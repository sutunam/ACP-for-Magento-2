<?php
/**
 * @author    Run_As_Root <hello@run-as-root.sh>
 * @copyright 2025 Run_As_Root
 * @license   https://opensource.org/license/mit  MIT License
 * @link      https://run-as-root.sh/
 */

declare(strict_types=1);

namespace RunAsRoot\AgenticCommerceProtocol\Api\Data;

/**
 * Interface for ACP checkout session data
 *
 * @api
 */
interface CheckoutSessionInterface
{
    public const CHECKOUT_SESSION_ID = 'checkout_session_id';
    public const STATUS = 'status';
    public const ITEMS = 'items';
    public const TOTAL = 'total';
    public const CURRENCY = 'currency';
    public const CREATED_AT = 'created_at';
    public const UPDATED_AT = 'updated_at';

    /**
     * Get checkout session ID
     *
     * @return string
     */
    public function getCheckoutSessionId(): string;

    /**
     * Set checkout session ID
     *
     * @param string $checkoutSessionId
     * @return $this
     */
    public function setCheckoutSessionId(string $checkoutSessionId): self;

    /**
     * Get status
     *
     * @return string
     */
    public function getStatus(): string;

    /**
     * Set status
     *
     * @param string $status
     * @return $this
     */
    public function setStatus(string $status): self;

    /**
     * Get items
     *
     * @return array
     */
    public function getItems(): array;

    /**
     * Set items
     *
     * @param array $items
     * @return $this
     */
    public function setItems(array $items): self;

    /**
     * Get total
     *
     * @return float
     */
    public function getTotal(): float;

    /**
     * Set total
     *
     * @param float $total
     * @return $this
     */
    public function setTotal(float $total): self;

    /**
     * Get currency
     *
     * @return string
     */
    public function getCurrency(): string;

    /**
     * Set currency
     *
     * @param string $currency
     * @return $this
     */
    public function setCurrency(string $currency): self;
}
