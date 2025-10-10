<?php
/**
 * @author    Run_As_Root <hello@run-as-root.sh>
 * @copyright 2025 Run_As_Root
 * @license   https://opensource.org/license/mit  MIT License
 * @link      https://run-as-root.sh/
 */

declare(strict_types=1);

namespace RunAsRoot\AgenticCommerceProtocol\Model\Response;

use Magento\Framework\Exception\LocalizedException;
use Magento\Quote\Api\Data\ShippingMethodInterface;
use Magento\Quote\Api\ShipmentEstimationInterface;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Address;

/**
 * Builds ACP fulfillment_options from Magento shipping methods
 */
class FulfillmentOptionsBuilder
{
    public function __construct(
        private readonly ShipmentEstimationInterface $shipmentEstimation
    ) {
    }

    /**
     * Build fulfillment options from quote
     *
     * @param Quote $quote
     * @return array
     * @throws LocalizedException
     */
    public function build(Quote $quote): array
    {
        $fulfillmentOptions = [];

        $shippingAddress = $quote->getShippingAddress();
        if (!$shippingAddress || !$shippingAddress->getCountryId()) {
            return $fulfillmentOptions;
        }

        // Get available shipping methods
        $shippingMethods = $this->shipmentEstimation->estimateByExtendedAddress(
            $quote->getId(),
            $shippingAddress
        );

        /** @var ShippingMethodInterface $method */
        foreach ($shippingMethods as $method) {
            $fulfillmentOptions[] = $this->buildFulfillmentOption(
                $method,
                $quote->getQuoteCurrencyCode()
            );
        }

        return $fulfillmentOptions;
    }

    /**
     * Build single fulfillment option from shipping method
     *
     * @param ShippingMethodInterface $method
     * @param string $currencyCode
     * @return array
     */
    private function buildFulfillmentOption(ShippingMethodInterface $method, string $currencyCode): array
    {
        $carrierCode = $method->getCarrierCode();
        $methodCode = $method->getMethodCode();
        $fullCode = $carrierCode . '_' . $methodCode;

        $option = [
            'id' => $fullCode,
            'type' => 'shipping',
            'amount' => [
                'amount' => round((float)$method->getAmount(), 2),
                'currency' => $currencyCode
            ],
            'description' => $method->getCarrierTitle() . ' - ' . $method->getMethodTitle(),
        ];

        // Add estimated delivery date if available (placeholder for now)
        // In production, this would integrate with carrier APIs
        $estimatedDays = $this->getEstimatedDeliveryDays($carrierCode);
        if ($estimatedDays) {
            $deliveryDate = date('Y-m-d', strtotime("+{$estimatedDays} days"));
            $option['estimated_delivery_date'] = $deliveryDate;
        }

        return $option;
    }

    /**
     * Get estimated delivery days for carrier
     *
     * @param string $carrierCode
     * @return int|null
     */
    private function getEstimatedDeliveryDays(string $carrierCode): ?int
    {
        // Placeholder estimates - in production this would query carrier APIs
        $estimates = [
            'flatrate' => 5,
            'tablerate' => 5,
            'freeshipping' => 7,
            'ups' => 3,
            'usps' => 5,
            'fedex' => 2,
            'dhl' => 3,
        ];

        return $estimates[$carrierCode] ?? null;
    }
}
