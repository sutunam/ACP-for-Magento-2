<?php
/**
 * @author    Run_As_Root <hello@run-as-root.sh>
 * @copyright 2025 Run_As_Root
 * @license   https://opensource.org/license/mit  MIT License
 * @link      https://run-as-root.sh/
 */

declare(strict_types=1);

namespace RunAsRoot\AgenticCommerceProtocol\Model\Webhook;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\HTTP\Client\Curl;
use Magento\Framework\Serialize\SerializerInterface;
use Psr\Log\LoggerInterface;

/**
 * Dispatches webhook events to OpenAI
 */
class WebhookDispatcher
{
    private const CONFIG_PATH_ENABLED = 'acp/webhooks/enabled';
    private const CONFIG_PATH_ENDPOINT = 'acp/webhooks/endpoint_url';
    private const CONFIG_PATH_SECRET = 'acp/webhooks/signing_secret';

    public function __construct(
        private readonly Curl $curl,
        private readonly SerializerInterface $serializer,
        private readonly ScopeConfigInterface $scopeConfig,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * Dispatch webhook event
     */
    public function dispatch(string $eventType, array $payload): bool
    {
        if (!$this->isEnabled()) {
            return false;
        }

        $endpoint = $this->getEndpointUrl();
        if (empty($endpoint)) {
            $this->logger->warning('ACP webhook endpoint not configured');
            return false;
        }

        $webhookPayload = [
            'event_type' => $eventType,
            'timestamp' => time(),
            'data' => $payload
        ];

        $jsonPayload = $this->serializer->serialize($webhookPayload);
        $signature = $this->generateSignature($jsonPayload);

        try {
            $this->curl->setHeaders([
                'Content-Type' => 'application/json',
                'X-ACP-Signature' => $signature,
                'X-ACP-Timestamp' => (string)$webhookPayload['timestamp'],
                'User-Agent' => 'Magento-ACP/1.0'
            ]);

            $this->curl->post($endpoint, $jsonPayload);
            $statusCode = $this->curl->getStatus();

            if ($statusCode >= 200 && $statusCode < 300) {
                $this->logger->info("ACP webhook dispatched successfully", [
                    'event_type' => $eventType,
                    'status_code' => $statusCode
                ]);
                return true;
            }

            $this->logger->error("ACP webhook failed", [
                'event_type' => $eventType,
                'status_code' => $statusCode,
                'response' => $this->curl->getBody()
            ]);

            return false;
        } catch (\Exception $e) {
            $this->logger->error("ACP webhook exception", [
                'event_type' => $eventType,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Generate HMAC signature for webhook
     */
    private function generateSignature(string $payload): string
    {
        $secret = $this->scopeConfig->getValue(self::CONFIG_PATH_SECRET);
        if (empty($secret)) {
            return '';
        }

        return hash_hmac('sha256', $payload, $secret);
    }

    /**
     * Check if webhooks are enabled
     */
    private function isEnabled(): bool
    {
        return (bool)$this->scopeConfig->isSetFlag(self::CONFIG_PATH_ENABLED);
    }

    /**
     * Get webhook endpoint URL
     */
    private function getEndpointUrl(): ?string
    {
        return $this->scopeConfig->getValue(self::CONFIG_PATH_ENDPOINT);
    }
}
