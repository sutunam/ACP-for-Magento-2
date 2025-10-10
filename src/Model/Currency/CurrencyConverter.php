<?php
/**
 * @author    Run_As_Root <hello@run-as-root.sh>
 * @copyright 2025 Run_As_Root
 * @license   https://opensource.org/license/mit  MIT License
 * @link      https://run-as-root.sh/
 */

declare(strict_types=1);

namespace RunAsRoot\AgenticCommerceProtocol\Model\Currency;

use Magento\Directory\Model\CurrencyFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Handles currency conversion and validation
 */
class CurrencyConverter
{
    public function __construct(
        private readonly StoreManagerInterface $storeManager,
        private readonly CurrencyFactory $currencyFactory
    ) {
    }

    /**
     * Validate if currency is supported by store
     */
    public function isCurrencySupported(string $currencyCode): bool
    {
        $allowedCurrencies = $this->storeManager->getStore()->getAvailableCurrencyCodes(true);
        return in_array($currencyCode, $allowedCurrencies, true);
    }

    /**
     * Convert amount from one currency to another
     */
    public function convert(float $amount, string $fromCurrency, string $toCurrency): float
    {
        if ($fromCurrency === $toCurrency) {
            return $amount;
        }

        $currency = $this->currencyFactory->create();
        $currency->load($fromCurrency);

        $rate = $currency->getRate($toCurrency);
        if (!$rate) {
            throw new LocalizedException(
                __('Unable to convert from %1 to %2. Exchange rate not configured.', $fromCurrency, $toCurrency)
            );
        }

        return $amount * $rate;
    }

    /**
     * Get store's default currency
     */
    public function getStoreCurrency(): string
    {
        return $this->storeManager->getStore()->getCurrentCurrency()->getCode();
    }

    /**
     * Get all supported currencies for current store
     */
    public function getSupportedCurrencies(): array
    {
        return $this->storeManager->getStore()->getAvailableCurrencyCodes(true);
    }

    /**
     * Format amount with currency symbol
     */
    public function formatAmount(float $amount, string $currencyCode): string
    {
        $currency = $this->currencyFactory->create();
        $currency->load($currencyCode);
        return $currency->format($amount, [], false);
    }
}
