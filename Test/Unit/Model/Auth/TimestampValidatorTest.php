<?php
/**
 * @author    Run_As_Root <hello@run-as-root.sh>
 * @copyright 2025 Run_As_Root
 * @license   https://opensource.org/license/mit  MIT License
 * @link      https://run-as-root.sh/
 */

declare(strict_types=1);

namespace RunAsRoot\AgenticCommerceProtocol\Test\Unit\Model\Auth;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\AuthenticationException;
use Magento\Framework\Stdlib\DateTime\DateTime;
use PHPUnit\Framework\TestCase;
use RunAsRoot\AgenticCommerceProtocol\Logger\Logger;
use RunAsRoot\AgenticCommerceProtocol\Model\Auth\TimestampValidator;

final class TimestampValidatorTest extends TestCase
{
    private TimestampValidator $timestampValidator;
    private ScopeConfigInterface $scopeConfig;
    private DateTime $dateTime;
    private Logger $logger;

    protected function setUp(): void
    {
        $this->scopeConfig = $this->createMock(ScopeConfigInterface::class);
        $this->dateTime = $this->createMock(DateTime::class);
        $this->logger = $this->createMock(Logger::class);

        $this->timestampValidator = new TimestampValidator(
            $this->scopeConfig,
            $this->dateTime,
            $this->logger
        );
    }

    public function test_validate_accepts_current_timestamp(): void
    {
        $currentTime = 1696348800; // Oct 3, 2023
        $this->dateTime->method('gmtTimestamp')->willReturn($currentTime);
        $this->scopeConfig->method('getValue')->willReturn(300); // 5 min tolerance

        // Same timestamp - should pass
        $this->timestampValidator->validate($currentTime);
        $this->assertTrue(true); // No exception thrown
    }

    public function test_validate_accepts_timestamp_within_tolerance(): void
    {
        $currentTime = 1696348800;
        $this->dateTime->method('gmtTimestamp')->willReturn($currentTime);
        $this->scopeConfig->method('getValue')->willReturn(300); // 5 min

        // 4 minutes old - should pass
        $this->timestampValidator->validate($currentTime - 240);
        $this->assertTrue(true);

        // 4 minutes in future - should pass
        $this->timestampValidator->validate($currentTime + 240);
        $this->assertTrue(true);
    }

    public function test_validate_rejects_old_timestamp(): void
    {
        $currentTime = 1696348800;
        $this->dateTime->method('gmtTimestamp')->willReturn($currentTime);
        $this->scopeConfig->method('getValue')->willReturn(300); // 5 min

        $this->expectException(AuthenticationException::class);
        $this->expectExceptionMessageMatches('/too old or too far in the future/');

        // 10 minutes old - should fail
        $this->timestampValidator->validate($currentTime - 600);
    }

    public function test_validate_rejects_future_timestamp(): void
    {
        $currentTime = 1696348800;
        $this->dateTime->method('gmtTimestamp')->willReturn($currentTime);
        $this->scopeConfig->method('getValue')->willReturn(300);

        $this->expectException(AuthenticationException::class);

        // 10 minutes in future - should fail
        $this->timestampValidator->validate($currentTime + 600);
    }

    public function test_validate_uses_configurable_tolerance(): void
    {
        $currentTime = 1696348800;
        $this->dateTime->method('gmtTimestamp')->willReturn($currentTime);
        $this->scopeConfig->method('getValue')->willReturn(60); // Only 1 min tolerance

        $this->expectException(AuthenticationException::class);

        // 2 minutes old - would pass with 5min, fails with 1min
        $this->timestampValidator->validate($currentTime - 120);
    }

    public function test_validate_at_tolerance_boundary(): void
    {
        $currentTime = 1696348800;
        $this->dateTime->method('gmtTimestamp')->willReturn($currentTime);
        $this->scopeConfig->method('getValue')->willReturn(300);

        // Exactly 5 minutes old - should pass (boundary test)
        $this->timestampValidator->validate($currentTime - 300);
        $this->assertTrue(true);
    }
}
