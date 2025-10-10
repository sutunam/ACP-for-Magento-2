<?php
/**
 * @author    Run_As_Root <hello@run-as-root.sh>
 * @copyright 2025 Run_As_Root
 * @license   https://opensource.org/license/mit  MIT License
 * @link      https://run-as-root.sh/
 */

declare(strict_types=1);

namespace RunAsRoot\AgenticCommerceProtocol\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

/**
 * ACP Checkout Session Resource Model
 */
class CheckoutSession extends AbstractDb
{
    private const TABLE_NAME = 'acp_checkout_session';
    private const PRIMARY_KEY = 'entity_id';

    protected function _construct(): void
    {
        $this->_init(self::TABLE_NAME, self::PRIMARY_KEY);
    }

    /**
     * Load session by checkout_session_id
     */
    public function loadByCheckoutSessionId(
        \RunAsRoot\AgenticCommerceProtocol\Model\Checkout\Session $session,
        string $checkoutSessionId
    ): self {
        $connection = $this->getConnection();
        $select = $connection->select()
            ->from($this->getMainTable())
            ->where('checkout_session_id = ?', $checkoutSessionId);

        $data = $connection->fetchRow($select);
        if ($data) {
            $session->setData($data);
        }

        return $this;
    }
}
