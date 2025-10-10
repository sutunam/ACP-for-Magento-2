<?php
/**
 * @author    run_as_root GmbH <info@run-as-root.sh>
 * @copyright 2025 run_as_root GmbH
 * @license   https://opensource.org/license/mit  MIT License
 * @link      https://run-as-root.sh/
 */

declare(strict_types=1);

namespace RunAsRoot\AgenticCommerceProtocol\Model;

use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use RunAsRoot\AgenticCommerceProtocol\Api\Data\CheckoutSessionInterface;
use RunAsRoot\AgenticCommerceProtocol\Api\Data\CheckoutSessionInterfaceFactory;
use RunAsRoot\AgenticCommerceProtocol\Model\ResourceModel\CheckoutSession as CheckoutSessionResource;

/**
 * Repository for ACP Checkout Sessions
 */
class CheckoutSessionRepository
{
    public function __construct(
        private readonly CheckoutSessionInterfaceFactory $sessionFactory,
        private readonly CheckoutSessionResource $resource
    ) {
    }

    /**
     * Save checkout session
     */
    public function save(CheckoutSessionInterface $session): CheckoutSessionInterface
    {
        try {
            $this->resource->save($session);
        } catch (\Exception $e) {
            throw new CouldNotSaveException(
                __('Could not save checkout session: %1', $e->getMessage()),
                $e
            );
        }

        return $session;
    }

    /**
     * Get by checkout session ID
     */
    public function getByCheckoutSessionId(string $checkoutSessionId): CheckoutSessionInterface
    {
        $session = $this->sessionFactory->create();
        $this->resource->loadByCheckoutSessionId($session, $checkoutSessionId);

        if (!$session->getId()) {
            throw new NoSuchEntityException(
                __('Checkout session with ID "%1" does not exist', $checkoutSessionId)
            );
        }

        return $session;
    }

    /**
     * Get by entity ID
     */
    public function getById(int $id): CheckoutSessionInterface
    {
        $session = $this->sessionFactory->create();
        $this->resource->load($session, $id);

        if (!$session->getId()) {
            throw new NoSuchEntityException(
                __('Checkout session with entity ID "%1" does not exist', $id)
            );
        }

        return $session;
    }

    /**
     * Delete checkout session
     */
    public function delete(CheckoutSessionInterface $session): bool
    {
        try {
            $this->resource->delete($session);
        } catch (\Exception $e) {
            throw new CouldNotSaveException(
                __('Could not delete checkout session: %1', $e->getMessage()),
                $e
            );
        }

        return true;
    }
}
