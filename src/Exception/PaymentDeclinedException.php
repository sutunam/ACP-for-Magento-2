<?php
/**
 * @author    Run_As_Root <hello@run-as-root.sh>
 * @copyright 2025 Run_As_Root
 * @license   https://opensource.org/license/mit  MIT License
 * @link      https://run-as-root.sh/
 */

declare(strict_types=1);

namespace RunAsRoot\AgenticCommerceProtocol\Exception;

use Magento\Framework\Exception\LocalizedException;

/**
 * Exception for payment declined (ACP error code: payment_declined)
 */
class PaymentDeclinedException extends LocalizedException
{
    public function getErrorCode(): string
    {
        return 'payment_declined';
    }
}
