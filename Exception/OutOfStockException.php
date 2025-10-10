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
 * Exception for out of stock products (ACP error code: out_of_stock)
 */
class OutOfStockException extends LocalizedException
{
    public function getErrorCode(): string
    {
        return 'out_of_stock';
    }
}
