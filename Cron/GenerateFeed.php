<?php
/**
 * @author    run_as_root GmbH <info@run-as-root.sh>
 * @copyright 2025 run_as_root GmbH
 * @license   https://opensource.org/license/mit  MIT License
 * @link      https://run-as-root.sh/
 */

declare(strict_types=1);

namespace RunAsRoot\AgenticCommerceProtocol\Cron;

use Psr\Log\LoggerInterface;
use RunAsRoot\AgenticCommerceProtocol\Model\Feed\Generator\StaticFeedGenerator;

/**
 * Scheduled task to regenerate product feed
 */
class GenerateFeed
{
    public function __construct(
        private readonly StaticFeedGenerator $feedGenerator,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * Execute cron job
     */
    public function execute(): void
    {
        $this->logger->info('ACP: Starting scheduled feed generation');

        try {
            $result = $this->feedGenerator->generate(0, 'feed.json');

            $this->logger->info('ACP: Feed generated successfully', [
                'products' => $result['product_count'],
                'size' => $result['file_size'],
                'path' => $result['relative_path']
            ]);
        } catch (\Exception $e) {
            $this->logger->error('ACP: Feed generation failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }
}
