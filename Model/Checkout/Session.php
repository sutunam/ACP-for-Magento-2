<?php
/**
 * @author    Run_As_Root <hello@run-as-root.sh>
 * @copyright 2025 Run_As_Root
 * @license   https://opensource.org/license/mit  MIT License
 * @link      https://run-as-root.sh/
 */

declare(strict_types=1);

namespace RunAsRoot\AgenticCommerceProtocol\Model\Checkout;

use Magento\Framework\Model\AbstractModel;
use Magento\Framework\Serialize\SerializerInterface;
use RunAsRoot\AgenticCommerceProtocol\Api\Data\CheckoutSessionInterface;

/**
 * ACP Checkout Session Model
 */
class Session extends AbstractModel implements CheckoutSessionInterface
{
    private SerializerInterface $serializer;

    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        SerializerInterface $serializer,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        $this->serializer = $serializer;
        parent::__construct($context, $registry, $resource, $resourceCollection, $data);
    }

    protected function _construct(): void
    {
        $this->_init(\RunAsRoot\AgenticCommerceProtocol\Model\ResourceModel\CheckoutSession::class);
    }
    public function getCheckoutSessionId(): string
    {
        return (string)$this->getData(self::CHECKOUT_SESSION_ID);
    }

    public function setCheckoutSessionId(string $checkoutSessionId): CheckoutSessionInterface
    {
        return $this->setData(self::CHECKOUT_SESSION_ID, $checkoutSessionId);
    }

    public function getStatus(): string
    {
        return (string)$this->getData(self::STATUS);
    }

    public function setStatus(string $status): CheckoutSessionInterface
    {
        return $this->setData(self::STATUS, $status);
    }

    public function getItems(): array
    {
        $items = $this->getData(self::ITEMS);
        if (is_string($items) && !empty($items)) {
            try {
                return $this->serializer->unserialize($items);
            } catch (\Exception $e) {
                return [];
            }
        }
        return is_array($items) ? $items : [];
    }

    public function setItems(array $items): CheckoutSessionInterface
    {
        return $this->setData(self::ITEMS, $this->serializer->serialize($items));
    }

    public function getTotal(): float
    {
        return (float)$this->getData(self::TOTAL);
    }

    public function setTotal(float $total): CheckoutSessionInterface
    {
        return $this->setData(self::TOTAL, $total);
    }

    public function getCurrency(): string
    {
        return (string)$this->getData(self::CURRENCY);
    }

    public function setCurrency(string $currency): CheckoutSessionInterface
    {
        return $this->setData(self::CURRENCY, $currency);
    }
}
