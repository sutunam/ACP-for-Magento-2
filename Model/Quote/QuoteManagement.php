<?php
/**
 * @author    run_as_root GmbH <info@run-as-root.sh>
 * @copyright 2025 run_as_root GmbH
 * @license   https://opensource.org/license/mit  MIT License
 * @link      https://run-as-root.sh/
 */

declare(strict_types=1);

namespace RunAsRoot\AgenticCommerceProtocol\Model\Quote;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Quote\Api\CartManagementInterface;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Model\Quote;
use Magento\Store\Model\StoreManagerInterface;
use RunAsRoot\AgenticCommerceProtocol\Exception\InvalidProductException;
use RunAsRoot\AgenticCommerceProtocol\Exception\OutOfStockException;

/**
 * Manages Magento Quote creation and manipulation for ACP sessions
 */
class QuoteManagement
{
    public function __construct(
        private readonly CartManagementInterface $cartManagement,
        private readonly CartRepositoryInterface $cartRepository,
        private readonly ProductRepositoryInterface $productRepository,
        private readonly StoreManagerInterface $storeManager
    ) {
    }

    /**
     * Create Magento quote from ACP session items
     */
    public function createFromItems(array $items): Quote
    {
        // Create guest quote
        $cartId = $this->cartManagement->createEmptyCart();
        /** @var Quote $quote */
        $quote = $this->cartRepository->get($cartId);

        // Set store
        $quote->setStore($this->storeManager->getStore());

        // Add items to quote
        foreach ($items as $itemData) {
            $this->addItemToQuote($quote, $itemData);
        }

        // Collect totals
        $quote->collectTotals();
        $this->cartRepository->save($quote);

        return $quote;
    }

    /**
     * Update existing quote with new items
     */
    public function updateQuoteItems(Quote $quote, array $items): Quote
    {
        // Remove all existing items
        foreach ($quote->getAllVisibleItems() as $item) {
            $quote->removeItem($item->getId());
        }

        // Add new items
        foreach ($items as $itemData) {
            $this->addItemToQuote($quote, $itemData);
        }

        // Recollect totals
        $quote->collectTotals();
        $this->cartRepository->save($quote);

        return $quote;
    }

    /**
     * Add single item to quote
     */
    private function addItemToQuote(Quote $quote, array $itemData): void
    {
        if (!isset($itemData['sku'])) {
            throw new LocalizedException(__('SKU is required for each item'));
        }

        $sku = $itemData['sku'];
        $quantity = $itemData['quantity'] ?? 1;

        try {
            // Load product by SKU
            $product = $this->productRepository->get($sku);

            // Validate product allows ACP checkout
            if ($product->getData('acp_enable_checkout') === '0') {
                throw new LocalizedException(
                    __('Product "%1" is not available for instant checkout', $product->getName())
                );
            }

            // Validate product is salable
            if (!$product->isSalable()) {
                throw new OutOfStockException(
                    __('Product %1 is not available for sale', $sku)
                );
            }

            // Add to quote
            $quote->addProduct($product, $quantity);
        } catch (NoSuchEntityException $e) {
            throw new InvalidProductException(
                __('Product with SKU %1 does not exist', $sku)
            );
        }
    }

    /**
     * Get quote total
     */
    public function getQuoteTotal(Quote $quote): float
    {
        return (float)$quote->getGrandTotal();
    }
}
