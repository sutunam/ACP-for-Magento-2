<?php
/**
 * @author    run_as_root GmbH <info@run-as-root.sh>
 * @copyright 2025 run_as_root GmbH
 * @license   https://opensource.org/license/mit  MIT License
 * @link      https://run-as-root.sh/
 */

declare(strict_types=1);

namespace RunAsRoot\AgenticCommerceProtocol\Model\Address;

use Magento\Framework\Exception\LocalizedException;
use Magento\Quote\Api\Data\AddressInterface;
use Magento\Quote\Api\Data\AddressInterfaceFactory;
use Magento\Quote\Model\Quote;

/**
 * Manages address handling for ACP sessions
 */
class AddressManagement
{
    public function __construct(
        private readonly AddressInterfaceFactory $addressFactory
    ) {
    }

    /**
     * Set shipping address on quote from ACP fulfillment address
     *
     * @param Quote $quote
     * @param array $fulfillmentAddress ACP address data
     * @return Quote
     * @throws LocalizedException
     */
    public function setShippingAddress(Quote $quote, array $fulfillmentAddress): Quote
    {
        $this->validateAddress($fulfillmentAddress);

        $address = $this->addressFactory->create();
        
        // Map ACP address fields to Magento address
        $address->setFirstname($fulfillmentAddress['first_name'] ?? 'Guest');
        $address->setLastname($fulfillmentAddress['last_name'] ?? 'Customer');
        $address->setStreet($this->getStreetLines($fulfillmentAddress));
        $address->setCity($fulfillmentAddress['city'] ?? '');
        $address->setRegion($fulfillmentAddress['state'] ?? '');
        $address->setPostcode($fulfillmentAddress['postal_code'] ?? '');
        $address->setCountryId($fulfillmentAddress['country'] ?? 'US');
        $address->setTelephone($fulfillmentAddress['phone'] ?? '000-000-0000');
        $address->setEmail($fulfillmentAddress['email'] ?? 'guest@example.com');

        // Set as shipping address
        $quote->setShippingAddress($address);
        $quote->getBillingAddress()->addData($address->getData());

        return $quote;
    }

    /**
     * Validate address has required fields
     */
    private function validateAddress(array $address): void
    {
        $requiredFields = ['address_line1', 'city', 'postal_code', 'country'];
        
        foreach ($requiredFields as $field) {
            if (empty($address[$field])) {
                throw new LocalizedException(
                    __('Address field "%1" is required', $field)
                );
            }
        }
    }

    /**
     * Get street lines from ACP address
     */
    private function getStreetLines(array $address): array
    {
        $lines = [];
        
        if (!empty($address['address_line1'])) {
            $lines[] = $address['address_line1'];
        }
        
        if (!empty($address['address_line2'])) {
            $lines[] = $address['address_line2'];
        }

        return $lines;
    }

    /**
     * Get formatted address for display
     */
    public function formatAddress(array $address): string
    {
        $parts = array_filter([
            $address['address_line1'] ?? '',
            $address['address_line2'] ?? '',
            $address['city'] ?? '',
            $address['state'] ?? '',
            $address['postal_code'] ?? '',
            $address['country'] ?? ''
        ]);

        return implode(', ', $parts);
    }
}
