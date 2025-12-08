<?php
/**
 * @author    run_as_root GmbH <info@run-as-root.sh>
 * @copyright 2025 run_as_root GmbH
 * @license   https://opensource.org/license/mit  MIT License
 * @link      https://run-as-root.sh/
 */

declare(strict_types=1);

namespace RunAsRoot\AgenticCommerceProtocol\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Sales\Model\Order;
use RunAsRoot\AgenticCommerceProtocol\Model\Webhook\WebhookDispatcher;

/**
 * Observer for order update events
 */
class OrderUpdatedObserver implements ObserverInterface
{
    public function __construct(
        private readonly WebhookDispatcher $webhookDispatcher
    ) {
    }

    public function execute(Observer $observer): void
    {
        /** @var Order $order */
        $order = $observer->getEvent()->getOrder();

        if (!$order || !$order->getId()) {
            return;
        }

        // Check if this is an ACP order
        $acpSessionId = $order->getData('acp_checkout_session_id');
        if (empty($acpSessionId)) {
            return;
        }

        // Only send webhook if status actually changed
        if (!$order->dataHasChangedFor('status') && !$order->dataHasChangedFor('state')) {
            return;
        }

        $payload = [
            'checkout_session_id' => $acpSessionId,
            'order_id' => $order->getIncrementId(),
            'status' => $order->getStatus(),
            'state' => $order->getState(),
            'updated_at' => $order->getUpdatedAt()
        ];

        $this->webhookDispatcher->dispatch('order.updated', $payload);
    }
}
