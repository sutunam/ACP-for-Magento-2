<?php
/**
 * @author    Run_As_Root <hello@run-as-root.sh>
 * @copyright 2025 Run_As_Root
 * @license   https://opensource.org/license/mit  MIT License
 * @link      https://run-as-root.sh/
 */

declare(strict_types=1);

namespace RunAsRoot\AgenticCommerceProtocol\Model\ResourceModel\CheckoutSession;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use RunAsRoot\AgenticCommerceProtocol\Model\Checkout\Session as CheckoutSessionModel;
use RunAsRoot\AgenticCommerceProtocol\Model\ResourceModel\CheckoutSession as CheckoutSessionResource;

/**
 * ACP Checkout Session Collection
 */
class Collection extends AbstractCollection
{
    protected $_idFieldName = 'entity_id';

    protected function _construct(): void
    {
        $this->_init(CheckoutSessionModel::class, CheckoutSessionResource::class);
    }
}
