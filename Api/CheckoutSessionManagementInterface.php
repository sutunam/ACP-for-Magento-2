<?php
/**
 * @author    Run_As_Root <hello@run-as-root.sh>
 * @copyright 2025 Run_As_Root
 * @license   https://opensource.org/license/mit  MIT License
 * @link      https://run-as-root.sh/
 */

declare(strict_types=1);

namespace RunAsRoot\AgenticCommerceProtocol\Api;

use RunAsRoot\AgenticCommerceProtocol\Api\Data\CheckoutSessionInterface;

/**
 * Interface for managing ACP checkout sessions
 *
 * @api
 */
interface CheckoutSessionManagementInterface
{
    /**
     * Create a new checkout session
     *
     * @param mixed $data
     * @return \RunAsRoot\AgenticCommerceProtocol\Api\Data\CheckoutSessionInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function create($data): CheckoutSessionInterface;

    /**
     * Update an existing checkout session
     *
     * @param string $checkoutSessionId
     * @param mixed $data
     * @return \RunAsRoot\AgenticCommerceProtocol\Api\Data\CheckoutSessionInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function update(string $checkoutSessionId, $data): CheckoutSessionInterface;

    /**
     * Get checkout session by ID
     *
     * @param string $checkoutSessionId
     * @return \RunAsRoot\AgenticCommerceProtocol\Api\Data\CheckoutSessionInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function get(string $checkoutSessionId): CheckoutSessionInterface;

    /**
     * Complete checkout session and create order
     *
     * @param string $checkoutSessionId
     * @param mixed $data
     * @return \RunAsRoot\AgenticCommerceProtocol\Api\Data\CheckoutSessionInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function complete(string $checkoutSessionId, $data): CheckoutSessionInterface;

    /**
     * Cancel checkout session
     *
     * @param string $checkoutSessionId
     * @return \RunAsRoot\AgenticCommerceProtocol\Api\Data\CheckoutSessionInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function cancel(string $checkoutSessionId): CheckoutSessionInterface;
}
