<?php
/**
 * @author    Run_As_Root <hello@run-as-root.sh>
 * @copyright 2025 Run_As_Root
 * @license   https://opensource.org/license/mit  MIT License
 * @link      https://run-as-root.sh/
 */

declare(strict_types=1);

namespace RunAsRoot\AgenticCommerceProtocol\Model\Feed;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\Framework\App\CacheInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Framework\UrlInterface;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Generates product feed for ChatGPT discovery
 */
class ProductFeedGenerator
{
    private const CONFIG_PATH_MAX_PRODUCTS = 'acp/product_feed/max_products';
    private const CONFIG_PATH_CATEGORIES = 'acp/product_feed/categories';
    private const CACHE_TAG = 'acp_product_feed';
    private const CACHE_LIFETIME = 3600; // 1 hour

    public function __construct(
        private readonly CollectionFactory $productCollectionFactory,
        private readonly ScopeConfigInterface $scopeConfig,
        private readonly StoreManagerInterface $storeManager,
        private readonly ProductRepositoryInterface $productRepository,
        private readonly CacheInterface $cache,
        private readonly SerializerInterface $serializer
    ) {
    }

    /**
     * Generate product feed as JSON (with caching)
     */
    public function generate(): array
    {
        $cacheKey = $this->getCacheKey();

        // Try to load from cache
        $cachedFeed = $this->cache->load($cacheKey);
        if ($cachedFeed) {
            return $this->serializer->unserialize($cachedFeed);
        }

        // Generate fresh feed
        $products = $this->getProducts();
        $feed = [];

        foreach ($products as $product) {
            $feed[] = $this->formatProduct($product);
        }

        $result = [
            'products' => $feed,
            'total_count' => count($feed),
            'generated_at' => date('c'),
            'store' => $this->storeManager->getStore()->getName(),
            'supported_currencies' => $this->storeManager->getStore()->getAvailableCurrencyCodes(true)
        ];

        // Save to cache
        $this->cache->save(
            $this->serializer->serialize($result),
            $cacheKey,
            [self::CACHE_TAG],
            self::CACHE_LIFETIME
        );

        return $result;
    }

    /**
     * Get cache key for product feed
     */
    private function getCacheKey(): string
    {
        $storeId = $this->storeManager->getStore()->getId();
        return self::CACHE_TAG . '_store_' . $storeId;
    }

    /**
     * Get products for feed
     */
    private function getProducts(): array
    {
        $collection = $this->productCollectionFactory->create();
        
        // Add attributes needed for feed
        $collection->addAttributeToSelect([
            'name',
            'sku',
            'price',
            'description',
            'short_description',
            'image',
            'status',
            'visibility'
        ]);

        // Only visible, enabled, in-stock products
        $collection->addAttributeToFilter('status', ['eq' => 1]);
        $collection->addAttributeToFilter('visibility', ['neq' => 1]); // Not "Not Visible Individually"

        // Filter by categories if configured
        $categories = $this->getCategoriesFilter();
        if (!empty($categories)) {
            $collection->addCategoriesFilter(['in' => $categories]);
        }

        // Limit products
        $maxProducts = (int)$this->scopeConfig->getValue(self::CONFIG_PATH_MAX_PRODUCTS) ?: 1000;
        $collection->setPageSize($maxProducts);

        return $collection->getItems();
    }

    /**
     * Format product for feed
     */
    private function formatProduct(ProductInterface $product): array
    {
        $store = $this->storeManager->getStore();

        return [
            'sku' => $product->getSku(),
            'name' => $product->getName(),
            'description' => $this->cleanDescription($product->getDescription()),
            'short_description' => $this->cleanDescription($product->getShortDescription()),
            'price' => [
                'amount' => $this->getProductPrice($product),
                'currency' => $store->getCurrentCurrency()->getCode()
            ],
            'product_type' => $product->getTypeId(),
            'url' => $product->getProductUrl(),
            'image_url' => $this->getProductImageUrl($product),
            'availability' => $product->isSalable() ? 'in_stock' : 'out_of_stock',
            'categories' => $this->getProductCategoryNames($product)
        ];
    }

    /**
     * Get product price handling all product types
     */
    private function getProductPrice(ProductInterface $product): float
    {
        $price = 0.0;

        switch ($product->getTypeId()) {
            case 'simple':
            case 'virtual':
            case 'downloadable':
                // Simple products have direct price
                $price = (float)$product->getFinalPrice();
                break;

            case 'configurable':
                // Get minimum price from child products
                $price = (float)$product->getPriceInfo()
                    ->getPrice('final_price')
                    ->getMinimalPrice()
                    ->getValue();
                break;

            case 'bundle':
                // Get minimum bundle price
                $price = (float)$product->getPriceInfo()
                    ->getPrice('final_price')
                    ->getMinimalPrice()
                    ->getValue();
                break;

            case 'grouped':
                // Get minimum price from associated products
                $price = (float)$product->getPriceInfo()
                    ->getPrice('final_price')
                    ->getMinimalPrice()
                    ->getValue();
                break;

            default:
                // Fallback to regular price
                $price = (float)$product->getFinalPrice();
        }

        return max($price, 0.0); // Ensure non-negative
    }

    /**
     * Clean HTML from description for AI consumption
     */
    private function cleanDescription(?string $description): string
    {
        if (empty($description)) {
            return '';
        }

        // Strip HTML tags
        $text = strip_tags($description);
        
        // Remove extra whitespace
        $text = preg_replace('/\s+/', ' ', $text);
        
        // Trim and limit length for AI context
        $text = trim($text);
        return mb_substr($text, 0, 500);
    }

    /**
     * Get product image URL
     */
    private function getProductImageUrl(ProductInterface $product): ?string
    {
        $image = $product->getImage();
        if (empty($image) || $image === 'no_selection') {
            return null;
        }

        $store = $this->storeManager->getStore();
        return $store->getBaseUrl(UrlInterface::URL_TYPE_MEDIA) . 'catalog/product' . $image;
    }

    /**
     * Get category names for product
     */
    private function getProductCategoryNames(ProductInterface $product): array
    {
        $categoryIds = $product->getCategoryIds();
        if (empty($categoryIds)) {
            return [];
        }

        // TODO: Load category names (requires category repository)
        return $categoryIds;
    }

    /**
     * Get categories filter from config
     */
    private function getCategoriesFilter(): array
    {
        $categories = $this->scopeConfig->getValue(self::CONFIG_PATH_CATEGORIES);
        if (empty($categories)) {
            return [];
        }

        return array_filter(explode(',', $categories));
    }
}
