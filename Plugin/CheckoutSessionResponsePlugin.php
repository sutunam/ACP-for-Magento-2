<?php
/**
 * @author    Run_As_Root <hello@run-as-root.sh>
 * @copyright 2025 Run_As_Root
 * @license   https://opensource.org/license/mit  MIT License
 * @link      https://run-as-root.sh/
 */

declare(strict_types=1);

namespace RunAsRoot\AgenticCommerceProtocol\Plugin;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Webapi\Rest\Response;
use RunAsRoot\AgenticCommerceProtocol\Api\CheckoutSessionManagementInterface;
use RunAsRoot\AgenticCommerceProtocol\Api\Data\CheckoutSessionInterface;
use RunAsRoot\AgenticCommerceProtocol\Model\Response\CheckoutSessionResponseBuilder;

/**
 * Plugin to transform API responses to ACP format
 */
class CheckoutSessionResponsePlugin
{
    public function __construct(
        private readonly CheckoutSessionResponseBuilder $responseBuilder
    ) {
    }

    /**
     * Transform create response
     *
     * @param CheckoutSessionManagementInterface $subject
     * @param CheckoutSessionInterface $result
     * @return array
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function afterCreate(
        CheckoutSessionManagementInterface $subject,
        CheckoutSessionInterface $result
    ): array {
        return $this->responseBuilder->build($result);
    }

    /**
     * Transform update response
     *
     * @param CheckoutSessionManagementInterface $subject
     * @param CheckoutSessionInterface $result
     * @return array
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function afterUpdate(
        CheckoutSessionManagementInterface $subject,
        CheckoutSessionInterface $result
    ): array {
        return $this->responseBuilder->build($result);
    }

    /**
     * Transform get response
     *
     * @param CheckoutSessionManagementInterface $subject
     * @param CheckoutSessionInterface $result
     * @return array
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function afterGet(
        CheckoutSessionManagementInterface $subject,
        CheckoutSessionInterface $result
    ): array {
        return $this->responseBuilder->build($result);
    }

    /**
     * Transform complete response
     *
     * @param CheckoutSessionManagementInterface $subject
     * @param CheckoutSessionInterface $result
     * @return array
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function afterComplete(
        CheckoutSessionManagementInterface $subject,
        CheckoutSessionInterface $result
    ): array {
        return $this->responseBuilder->build($result);
    }

    /**
     * Transform cancel response
     *
     * @param CheckoutSessionManagementInterface $subject
     * @param CheckoutSessionInterface $result
     * @return array
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function afterCancel(
        CheckoutSessionManagementInterface $subject,
        CheckoutSessionInterface $result
    ): array {
        return $this->responseBuilder->build($result);
    }
}
