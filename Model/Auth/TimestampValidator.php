<?php
/**
 * @author    Run_As_Root <hello@run-as-root.sh>
 * @copyright 2025 Run_As_Root
 * @license   https://opensource.org/license/mit  MIT License
 * @link      https://run-as-root.sh/
 */

declare(strict_types=1);

namespace RunAsRoot\AgenticCommerceProtocol\Model\Auth;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\AuthenticationException;
use Magento\Framework\Stdlib\DateTime\DateTime;
use RunAsRoot\AgenticCommerceProtocol\Logger\Logger;

/**
 * Validates request timestamps to prevent replay attacks
 */
class TimestampValidator
{
    private const CONFIG_PATH_TOLERANCE = 'agentic_commerce/security/timestamp_tolerance';
    private const DEFAULT_TOLERANCE = 300; // 5 minutes

    public function __construct(
        private readonly ScopeConfigInterface $scopeConfig,
        private readonly DateTime $dateTime,
        private readonly Logger $logger
    ) {
    }

    /**
     * Validate timestamp is within acceptable range
     *
     * @param int $timestamp Unix timestamp from request
     * @throws AuthenticationException
     */
    public function validate(int $timestamp): void
    {
        $currentTime = $this->dateTime->gmtTimestamp();
        $tolerance = $this->getTolerance();

        $timeDiff = abs($currentTime - $timestamp);

        if ($timeDiff > $tolerance) {
            $this->logger->error("Timestamp validation failed. Time diff: {$timeDiff}s, Tolerance: {$tolerance}s");
            throw new AuthenticationException(
                __('Request timestamp is too old or too far in the future. Time difference: %1 seconds', $timeDiff)
            );
        }

        $this->logger->info("Timestamp validated. Time diff: {$timeDiff}s");
    }

    /**
     * Get timestamp tolerance in seconds
     *
     * @return int
     */
    private function getTolerance(): int
    {
        $tolerance = $this->scopeConfig->getValue(
            self::CONFIG_PATH_TOLERANCE,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );

        return $tolerance ? (int)$tolerance : self::DEFAULT_TOLERANCE;
    }
}
