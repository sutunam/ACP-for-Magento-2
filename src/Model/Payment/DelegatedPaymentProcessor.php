<?php
/**
 * @author    Run_As_Root <hello@run-as-root.sh>
 * @copyright 2025 Run_As_Root
 * @license   https://opensource.org/license/mit  MIT License
 * @link      https://run-as-root.sh/
 */

declare(strict_types=1);

namespace RunAsRoot\AgenticCommerceProtocol\Model\Payment;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Quote\Model\Quote;
use RunAsRoot\AgenticCommerceProtocol\Exception\PaymentDeclinedException;
use Psr\Log\LoggerInterface;

/**
 * Processes payments via Stripe Delegated Payment Spec
 */
class DelegatedPaymentProcessor
{
    private const CONFIG_PATH_STRIPE_SECRET_KEY = 'payment/acp_stripe/secret_key';
    private const CONFIG_PATH_STRIPE_ENABLED = 'payment/acp_stripe/active';

    public function __construct(
        private readonly ScopeConfigInterface $scopeConfig,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * Process payment using Stripe Shared Payment Token
     *
     * @param Quote $quote
     * @param array $paymentData Contains 'token' from ACP complete request
     * @return array Payment result with transaction details
     * @throws PaymentDeclinedException
     */
    public function processPayment(Quote $quote, array $paymentData): array
    {
        if (!$this->isEnabled()) {
            throw new PaymentDeclinedException(__('Stripe payment processing is not enabled'));
        }

        $token = $paymentData['token'] ?? null;
        if (empty($token)) {
            throw new PaymentDeclinedException(__('Payment token is required'));
        }

        try {
            // TODO: Integrate actual Stripe SDK when available
            // For now, we'll validate the token format and return a mock response
            
            if (!$this->isValidTokenFormat($token)) {
                throw new PaymentDeclinedException(__('Invalid payment token format'));
            }

            // Simulate payment processing
            $transactionId = $this->generateTransactionId();

            $this->logger->info('ACP payment processed', [
                'quote_id' => $quote->getId(),
                'amount' => $quote->getGrandTotal(),
                'currency' => $quote->getQuoteCurrencyCode(),
                'transaction_id' => $transactionId
            ]);

            return [
                'success' => true,
                'transaction_id' => $transactionId,
                'status' => 'authorized',
                'amount' => $quote->getGrandTotal(),
                'currency' => $quote->getQuoteCurrencyCode()
            ];

        } catch (\Exception $e) {
            $this->logger->error('ACP payment failed', [
                'quote_id' => $quote->getId(),
                'error' => $e->getMessage()
            ]);

            throw new PaymentDeclinedException(
                __('Payment processing failed: %1', $e->getMessage())
            );
        }
    }

    /**
     * Validate Stripe token format
     */
    private function isValidTokenFormat(string $token): bool
    {
        // Stripe tokens typically start with specific prefixes
        // tok_ for test tokens, src_ for sources, pm_ for payment methods
        return preg_match('/^(tok_|src_|pm_)[a-zA-Z0-9]+$/', $token) === 1;
    }

    /**
     * Generate transaction ID
     */
    private function generateTransactionId(): string
    {
        return 'txn_' . bin2hex(random_bytes(16));
    }

    /**
     * Check if Stripe payment is enabled
     */
    private function isEnabled(): bool
    {
        return (bool)$this->scopeConfig->isSetFlag(self::CONFIG_PATH_STRIPE_ENABLED);
    }

    /**
     * Get Stripe secret key
     */
    private function getSecretKey(): ?string
    {
        return $this->scopeConfig->getValue(self::CONFIG_PATH_STRIPE_SECRET_KEY);
    }
}
