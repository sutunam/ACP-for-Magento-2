<?php
/**
 * @author    run_as_root GmbH <info@run-as-root.sh>
 * @copyright 2025 run_as_root GmbH
 * @license   https://opensource.org/license/mit  MIT License
 * @link      https://run-as-root.sh/
 */

declare(strict_types=1);

namespace RunAsRoot\AgenticCommerceProtocol\Controller\Feed;

use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use RunAsRoot\AgenticCommerceProtocol\Model\Feed\ProductFeedGenerator;

/**
 * Product feed endpoint for ChatGPT
 */
class Index implements HttpGetActionInterface
{
    private const CONFIG_PATH_FEED_ENABLED = 'acp/product_feed/enabled';

    public function __construct(
        private readonly JsonFactory $jsonFactory,
        private readonly ProductFeedGenerator $feedGenerator,
        private readonly ScopeConfigInterface $scopeConfig
    ) {
    }

    public function execute()
    {
        $result = $this->jsonFactory->create();

        // Check if feed is enabled
        if (!$this->scopeConfig->isSetFlag(self::CONFIG_PATH_FEED_ENABLED)) {
            return $result->setHttpResponseCode(404)
                ->setData(['error' => 'Product feed is disabled']);
        }

        try {
            $feed = $this->feedGenerator->generate();
            return $result->setData($feed);
        } catch (\Exception $e) {
            return $result->setHttpResponseCode(500)
                ->setData(['error' => 'Failed to generate product feed']);
        }
    }
}
