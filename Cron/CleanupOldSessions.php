<?php
/**
 * @author    Run_As_Root <hello@run-as-root.sh>
 * @copyright 2025 Run_As_Root
 * @license   https://opensource.org/license/mit  MIT License
 * @link      https://run-as-root.sh/
 */

declare(strict_types=1);

namespace RunAsRoot\AgenticCommerceProtocol\Cron;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Psr\Log\LoggerInterface;
use RunAsRoot\AgenticCommerceProtocol\Model\ResourceModel\CheckoutSession\CollectionFactory;

/**
 * Cleanup old checkout sessions
 */
class CleanupOldSessions
{
    private const CONFIG_PATH_RETENTION_DAYS = 'acp/general/session_retention_days';
    private const DEFAULT_RETENTION_DAYS = 30;

    public function __construct(
        private readonly CollectionFactory $collectionFactory,
        private readonly ScopeConfigInterface $scopeConfig,
        private readonly DateTime $dateTime,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * Execute cleanup of old sessions
     */
    public function execute(): void
    {
        try {
            $retentionDays = (int)$this->scopeConfig->getValue(self::CONFIG_PATH_RETENTION_DAYS)
                ?: self::DEFAULT_RETENTION_DAYS;

            $cutoffDate = date(
                'Y-m-d H:i:s',
                $this->dateTime->gmtTimestamp() - ($retentionDays * 24 * 60 * 60)
            );

            $collection = $this->collectionFactory->create();
            $collection->addFieldToFilter('status', ['in' => ['completed', 'canceled']])
                ->addFieldToFilter('updated_at', ['lt' => $cutoffDate]);

            $deletedCount = 0;
            foreach ($collection as $session) {
                $session->delete();
                $deletedCount++;
            }

            if ($deletedCount > 0) {
                $this->logger->info("ACP: Cleaned up {$deletedCount} old checkout sessions older than {$retentionDays} days");
            }
        } catch (\Exception $e) {
            $this->logger->error("ACP: Error cleaning up old sessions: " . $e->getMessage());
        }
    }
}
