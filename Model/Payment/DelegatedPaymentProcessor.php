<?php
/**
 * @author    run_as_root GmbH <info@run-as-root.sh>
 * @copyright 2025 run_as_root GmbH
 * @license   https://opensource.org/license/mit  MIT License
 * @link      https://run-as-root.sh/
 */

declare(strict_types=1);

namespace RunAsRoot\AgenticCommerceProtocol\Model\Payment;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Quote\Model\Quote;
use RunAsRoot\AgenticCommerceProtocol\Exception\PaymentDeclinedException;
use Psr\Log\LoggerInterface;
use Stripe\Exception\ApiErrorException;
use Stripe\PaymentIntent;
use Stripe\StripeClient;

/**
 * Processes payments via Stripe Delegated Payment Spec
 */
class DelegatedPaymentProcessor
{
    private const CONFIG_PATH_STRIPE_SECRET_KEY = 'acp/stripe/secret_key';
    private const CONFIG_PATH_STRIPE_ENABLED = 'acp/stripe/active';
    private const CONFIG_PATH_STRIPE_TEST_MODE = 'acp/stripe/test_mode';

    private ?StripeClient $stripeClient = null;

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
            $stripe = $this->getStripeClient();

            // Convert amount to cents (Stripe uses smallest currency unit)
            $amountInCents = (int)round($quote->getGrandTotal() * 100);

            // Create PaymentIntent with the Shared Payment Token
            $paymentIntent = $stripe->paymentIntents->create([
                'amount' => $amountInCents,
                'currency' => strtolower($quote->getQuoteCurrencyCode()),
                'payment_method' => $token,
                'confirm' => true,
                'automatic_payment_methods' => [
                    'enabled' => true,
                    'allow_redirects' => 'never'
                ],
                'metadata' => [
                    'quote_id' => $quote->getId(),
                    'store_id' => $quote->getStoreId(),
                    'integration' => 'magento_acp'
                ]
            ]);

            // Check payment status
            if ($paymentIntent->status !== 'succeeded' && $paymentIntent->status !== 'requires_capture') {
                throw new PaymentDeclinedException(
                    __('Payment authorization failed with status: %1', $paymentIntent->status)
                );
            }

            $this->logger->info('ACP Stripe payment processed', [
                'quote_id' => $quote->getId(),
                'amount' => $quote->getGrandTotal(),
                'currency' => $quote->getQuoteCurrencyCode(),
                'payment_intent_id' => $paymentIntent->id,
                'status' => $paymentIntent->status
            ]);

            return [
                'success' => true,
                'transaction_id' => $paymentIntent->id,
                'status' => $paymentIntent->status,
                'amount' => $quote->getGrandTotal(),
                'currency' => $quote->getQuoteCurrencyCode(),
                'charge_id' => $paymentIntent->latest_charge ?? null
            ];

        } catch (ApiErrorException $e) {
            $this->logger->error('ACP Stripe API error', [
                'quote_id' => $quote->getId(),
                'error_type' => $e->getError()->type ?? 'unknown',
                'error_code' => $e->getError()->code ?? 'unknown',
                'error_message' => $e->getMessage()
            ]);

            throw new PaymentDeclinedException(
                __('Payment declined: %1', $e->getError()->message ?? $e->getMessage())
            );
        } catch (\Exception $e) {
            $this->logger->error('ACP payment processing exception', [
                'quote_id' => $quote->getId(),
                'error' => $e->getMessage()
            ]);

            throw new PaymentDeclinedException(
                __('Payment processing failed: %1', $e->getMessage())
            );
        }
    }

    /**
     * Get or create Stripe client instance
     */
    private function getStripeClient(): StripeClient
    {
        if ($this->stripeClient === null) {
            $secretKey = $this->getSecretKey();

            if (empty($secretKey)) {
                throw new PaymentDeclinedException(__('Stripe API key is not configured'));
            }

            $this->stripeClient = new StripeClient($secretKey);
        }

        return $this->stripeClient;
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

    /**
     * Check if test mode is enabled
     */
    private function isTestMode(): bool
    {
        return (bool)$this->scopeConfig->isSetFlag(self::CONFIG_PATH_STRIPE_TEST_MODE);
    }
}
