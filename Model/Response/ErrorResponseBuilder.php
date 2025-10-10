<?php
/**
 * @author    Run_As_Root <hello@run-as-root.sh>
 * @copyright 2025 Run_As_Root
 * @license   https://opensource.org/license/mit  MIT License
 * @link      https://run-as-root.sh/
 */

declare(strict_types=1);

namespace RunAsRoot\AgenticCommerceProtocol\Model\Response;

use Magento\Framework\Exception\AuthenticationException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Validation\ValidationException;
use RunAsRoot\AgenticCommerceProtocol\Exception\PaymentDeclinedException;

/**
 * Builds ACP-compliant error responses
 */
class ErrorResponseBuilder
{
    /**
     * Build error response from exception
     *
     * @param \Throwable $exception
     * @return array
     */
    public function build(\Throwable $exception): array
    {
        return [
            'error' => [
                'code' => $this->getErrorCode($exception),
                'message' => $exception->getMessage(),
                'type' => $this->getErrorType($exception),
                'details' => $this->getErrorDetails($exception),
            ]
        ];
    }

    /**
     * Get error code from exception
     *
     * @param \Throwable $exception
     * @return string
     */
    private function getErrorCode(\Throwable $exception): string
    {
        return match (true) {
            $exception instanceof AuthenticationException => 'authentication_failed',
            $exception instanceof NoSuchEntityException => 'resource_not_found',
            $exception instanceof PaymentDeclinedException => 'payment_declined',
            $exception instanceof ValidationException => 'validation_error',
            $exception instanceof LocalizedException => 'request_invalid',
            default => 'internal_error',
        };
    }

    /**
     * Get error type classification
     *
     * @param \Throwable $exception
     * @return string
     */
    private function getErrorType(\Throwable $exception): string
    {
        return match (true) {
            $exception instanceof AuthenticationException => 'authentication_error',
            $exception instanceof NoSuchEntityException => 'not_found_error',
            $exception instanceof PaymentDeclinedException => 'payment_error',
            $exception instanceof ValidationException => 'validation_error',
            default => 'api_error',
        };
    }

    /**
     * Get additional error details
     *
     * @param \Throwable $exception
     * @return array
     */
    private function getErrorDetails(\Throwable $exception): array
    {
        $details = [];

        // For validation errors, include field-level errors if available
        if ($exception instanceof ValidationException) {
            $details['fields'] = $this->extractValidationErrors($exception);
        }

        // Add exception class for debugging (only in dev mode)
        if ($this->isDevMode()) {
            $details['exception_class'] = get_class($exception);
            $details['file'] = $exception->getFile();
            $details['line'] = $exception->getLine();
        }

        return $details;
    }

    /**
     * Extract field-level validation errors
     *
     * @param ValidationException $exception
     * @return array
     */
    private function extractValidationErrors(ValidationException $exception): array
    {
        // Magento ValidationException doesn't have structured errors by default
        // This is a placeholder for future enhancement
        return [];
    }

    /**
     * Check if in developer mode
     *
     * @return bool
     */
    private function isDevMode(): bool
    {
        // Simple check - in production this would use app state
        return true; // Always include debug info for now
    }
}
