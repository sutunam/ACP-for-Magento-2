<?php
/**
 * @author    run_as_root GmbH <info@run-as-root.sh>
 * @copyright 2025 run_as_root GmbH
 * @license   https://opensource.org/license/mit  MIT License
 * @link      https://run-as-root.sh/
 */

declare(strict_types=1);

namespace RunAsRoot\AgenticCommerceProtocol\Model\Feed;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Catalog\Model\Product\Gallery\ReadHandler as GalleryReadHandler;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\CatalogInventory\Api\StockRegistryInterface;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\Framework\App\CacheInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Framework\UrlInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory as CategoryCollectionFactory;

/**
 * Generates product feed for ChatGPT discovery
 */
class ProductFeedGenerator
{
    private const CONFIG_PATH_MAX_PRODUCTS = 'acp/product_feed/max_products';
    private const CONFIG_PATH_CATEGORIES = 'acp/product_feed/categories';
    private const CACHE_TAG = 'acp_product_feed';
    private const CACHE_LIFETIME = 3600; // 1 hour

    /**
     * @var string
     */
    private $currencyCode;

    /**
     * Constructor
     *
     * @param CollectionFactory $productCollectionFactory
     * @param ScopeConfigInterface $scopeConfig
     * @param StoreManagerInterface $storeManager
     * @param ProductRepositoryInterface $productRepository
     * @param CacheInterface $cache
     * @param SerializerInterface $serializer
     * @param StockRegistryInterface $stockRegistry
     * @param GalleryReadHandler $galleryReadHandler
     * @param Configurable $configurableType
     * @param CategoryCollectionFactory $categoryCollectionFactory
     */
    public function __construct(
        private readonly CollectionFactory $productCollectionFactory,
        private readonly ScopeConfigInterface $scopeConfig,
        private readonly StoreManagerInterface $storeManager,
        private readonly ProductRepositoryInterface $productRepository,
        private readonly CacheInterface $cache,
        private readonly SerializerInterface $serializer,
        private readonly StockRegistryInterface $stockRegistry,
        private readonly GalleryReadHandler $galleryReadHandler,
        private readonly Configurable $configurableType,
        private readonly CategoryCollectionFactory $categoryCollectionFactory
    ) {
    }

    /**
     * Generate product feed as JSON (with caching)
     *
     * @return mixed
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function generate()
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
            // Skip products with acp_enable_search disabled
            if ($product->getData('acp_enable_search') === '0') {
                continue;
            }

            $feed[] = $this->formatProduct($product);
        }
        /** @var \Magento\Store\Model\Store $store */
        $store = $this->storeManager->getStore();
        $result = [
            'products' => $feed,
            'total_count' => count($feed),
            'generated_at' => date('c'),
            'store' => $store->getName(),
            'supported_currencies' => $store->getAvailableCurrencyCodes(true),
            // Merchant Info
            'seller_name' => $store->getName(),
            'seller_url' => $store->getBaseUrl(),
            'seller_privacy_policy' => $store->getBaseUrl() . 'privacy', // create cms page for this field
            'seller_tos' => $store->getBaseUrl() . 'terms', // create cms page for this field

            //Returns
            'return_policy' => $store->getBaseUrl() . 'returns', // create cms page for this field
            'return_window' => 30,
        ];

        // Save to cache
        $this->cache->save(
            (string)$this->serializer->serialize($result),
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
     *
     * @return mixed
     */
    private function getProducts()
    {
        $collection = $this->productCollectionFactory->create();

        // Add attributes needed for feed (ACP spec requirements)
        $collection->addAttributeToSelect([
            'name',
            'sku',
            'type_id',
            'price',
            'special_price',
            'description',
            'short_description',
            'image',
            'status',
            'visibility',
            'weight',
            'manufacturer',        // Brand per ACP spec
            'gtin',                // Global Trade Item Number
            'mpn',                 // Manufacturer Part Number
            'acp_enable_search',   // Per-product search control
            'acp_enable_checkout',  // Per-product checkout control
            'material',
            'condition'  //new, refurbished, used
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
     * Format product for feed per ACP specification
     *
     * @param ProductInterface $product
     * @return mixed
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function formatProduct(ProductInterface $product)
    {
        /** @var \Magento\Catalog\Model\Product $product */
        /** @var \Magento\Store\Model\Store $store */
        $store = $this->storeManager->getStore();
        $currencyCode = $store->getCurrentCurrency()->getCode();
        $this->currencyCode = $currencyCode;

        $regularPrice = $this->getProductPrice($product);
        // Build ACP-compliant product data
        $data = [
            // Required fields
            'id' => (string)$product->getSku(),
            'title' => $product->getName(), // ACP spec uses 'title' not 'name'
            'description' => $this->cleanDescription($product->getDescription()),
            'link' => $product->getProductUrl(), // ACP spec uses 'link' not 'url'
            'price' => $this->formatPrice($regularPrice, $currencyCode),
            'availability' => $product->isSalable() ? 'in_stock' : 'out_of_stock',
            'type_id' => $product->getTypeId(),
            'product_category' => $this->getProductCategory($product),
            'brand' => $this->getBrand($product),
            'material' => $this->getMaterial($product),
            'weight' => $this->getWeightFormatted($product),
            'image_link' => $this->getMainImage($product),

            // Recommended fields
            'additional_image_link' => $this->getAdditionalImages($product),
            'length' => $product->getData('length') ?? '',
            'width' => $product->getData('width') ?? '',
            'height' => $product->getData('length') ?? '',

            // ACP spec flags (use product attributes, fallback to defaults)
            'enable_search' => $product->getData('acp_enable_search') !== '0',
            'enable_checkout' => $product->getData('acp_enable_checkout') !== '0' && $product->isSalable(),

            // Inventory
            'inventory_quantity' => $this->getInventoryQuantity($product),
        ];
        // ====== Add variant fields if simple and has parent configurable ======
        if ($product->getTypeId() === 'simple'
            && $parentIds = $this->configurableType->getParentIdsByChild($product->getId())) {
            // Take first parent configurable
            $parentId = $parentIds[0];
            $parentProduct = $this->productRepository->getById($parentId);

            $data['item_group_id'] = $parentProduct->getSku();
            $data['item_group_title'] = $parentProduct->getName();

            if ($color = $product->getAttributeText('color')) {
                $data['color'] = $color;
            }
            if ($size = $product->getAttributeText('size')) {
                $data['size'] = $size;
            }
            if ($sizeSystem = $product->getData('size_system')) {
                $data['size_system'] = strtoupper($sizeSystem);
            }
            if ($gender = $product->getData('gender')) {
                $data['gender'] = strtolower($gender);
            }

            // offer_id: SKU + color + price
            $priceValue = $this->getProductPrice($product);
            /** @phpstan-ignore-next-line */
            $data['offer_id'] = sprintf('%s-%s-%s', $product->getSku(), $data['color'] ?? 'NA', $priceValue);
        }
        $salePrice    = $this->getSalePrice($product);

        if ($salePrice !== null && $salePrice < $regularPrice) {
            $data['sale_price'] = $this->formatPrice($salePrice, $currencyCode);
        }

        $condition = $this->getCondition($product);

        if ($condition !== 'new') {
            $data['condition'] = $condition;
        } else {
            $data['condition'] = 'new';
        }

        // Add GTIN if available
        $gtin = $product->getData('gtin');
        if ($gtin) {
            $data['gtin'] = $gtin;
        }

        // Add MPN if available
        $mpn = $product->getData('mpn');
        if ($mpn) {
            $data['mpn'] = $mpn;
        }

        //Fulfillment
        $data['shipping'] = ''; //TODO: implement later
        $data['delivery_estimate'] = ''; //TODO: implement later

        //Performance Signals
        $data['popularity_score'] = ''; //TODO: implement later
        $data['return_rate'] = ''; //TODO: implement later

        //Compliance
        $data['warning'] = ''; //TODO: implement later
        $data['age_restriction'] = ''; //TODO: implement later

        //Reviews and Q&A
        $data['product_review_count'] = ''; //TODO: implement later
        $data['product_review_rating'] = ''; //TODO: implement later
        $data['store_review_count'] = ''; //TODO: implement later
        $data['store_review_rating'] = ''; //TODO: implement later
        $data['q_and_a'] = ''; //TODO: implement later
        $data['raw_review_data'] = ''; //TODO: implement later

        //Related Products
        if ($this->getRelatedProducts($product)) {
            $data['related_products'] = $this->getRelatedProducts($product);
            $data['relationship_type'] = 'often_bought_with';
        }

        //Geo Tagging
        $data['geo_price'] = ''; //TODO: implement later
        $data['geo_availability'] = ''; //TODO: implement later

        // Add variants for configurable products
        if ($product->getTypeId() === 'configurable') {
            $data['variants'] = $this->getProductVariants($product);
        }

        return $data;
    }

    /**
     * Get Related Products
     *
     * @param ProductInterface $product
     * @return string
     */
    private function getRelatedProducts(ProductInterface $product): string
    {
        /** @var \Magento\Catalog\Model\Product $product */
        $relatedProducts = '';
        $relatedItems = $product->getRelatedProducts();

        if (!empty($relatedItems)) {
            $relatedIds = [];
            foreach ($relatedItems as $item) {
                $relatedIds[] = $item->getSku();
            }
            $relatedProducts = implode(',', $relatedIds);
        }

        return $relatedProducts;
    }

    /**
     * Get Weight Formatted
     *
     * @param ProductInterface $product
     * @return string
     */
    public function getWeightFormatted(ProductInterface $product): string
    {
        /** @var \Magento\Catalog\Model\Product $product */
        $weight = $product->getWeight();

        if ($weight === null || $weight <= 0) {
            return '';
        }

        $unit = $this->scopeConfig->getValue(
            'general/locale/weight_unit',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );

        if (!$unit) {
            $unit = 'kg';
        }

        return sprintf('%s %s', rtrim((string)$weight, '0.'), $unit);
    }

    /**
     * Get Product Category
     *
     * @param ProductInterface $product
     * @return string
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function getProductCategory(ProductInterface $product): string
    {
        /** @var \Magento\Catalog\Model\Product $product */
        $categoryIds = $product->getCategoryIds();

        if (empty($categoryIds)) {
            return 'Uncategorized';
        }

        // Load categories
        $categories = $this->categoryCollectionFactory->create()
            ->addAttributeToSelect('name')
            ->addAttributeToSelect('path')
            ->addAttributeToFilter('entity_id', ['in' => $categoryIds])
            ->addIsActiveFilter();

        if ($categories->getSize() === 0) {
            return 'Uncategorized';
        }

        $selected = null;
        $maxDepth = 0;

        foreach ($categories as $category) {
            $depth = substr_count($category->getPath(), '/');

            if ($depth > $maxDepth) {
                $maxDepth = $depth;
                $selected = $category;
            }
        }

        if (!$selected) {
            return 'Uncategorized';
        }

        // Build full path
        $pathIds = explode('/', $selected->getPath());

        $pathCategories = $this->categoryCollectionFactory->create()
            ->addAttributeToSelect('name')
            ->addAttributeToFilter('entity_id', ['in' => $pathIds])
            ->addIsActiveFilter();

        // Map for quick lookup
        $pathMap = [];
        foreach ($pathCategories as $cat) {
            $pathMap[$cat->getId()] = $cat->getName();
        }

        $names = [];
        foreach ($pathIds as $id) {
            if (isset($pathMap[$id])) {
                // Skip root category
                if (strtolower($pathMap[$id]) === 'default category') {
                    continue;
                }
                $names[] = $pathMap[$id];
            }
        }

        // Combine using spec separator " > "
        $final = implode(' > ', $names);

        return $final !== '' ? $final : 'Uncategorized';
    }

    /**
     * GetMaterial
     *
     * @param ProductInterface $product
     * @return string
     */
    private function getMaterial(ProductInterface $product): string
    {
        /** @var \Magento\Catalog\Model\Product $product */
        $value = $product->getData('material');

        if (!$value) {
            return 'unknown';
        }

        $text = $product->getAttributeText('material');

        if (is_array($text)) {
            $material = implode(', ', array_map('trim', $text));
        } else {
            $material = trim((string)$text);
        }

        if ($material === '') {
            return 'unknown';
        }

        return mb_substr($material, 0, 100);
    }

    /**
     * Get Condition
     *
     * @param ProductInterface $product
     * @return string
     */
    private function getCondition(ProductInterface $product): string
    {
        /** @var \Magento\Catalog\Model\Product $product */
        $value = strtolower((string)$product->getData('condition'));
        return match ($value) {
            'used',
            'refurbished' => $value,
            default => 'new',
        };
    }

    /**
     * Get product price handling all product types
     *
     * @param ProductInterface $product
     * @return float
     */
    private function getProductPrice(ProductInterface $product): float
    {
        /** @var \Magento\Catalog\Model\Product $product */
        $price = 0.0;

        switch ($product->getTypeId()) {
            case 'simple':
            case 'virtual':
            case 'downloadable':
                // Simple products have direct price
                $price = (float)$product->getPrice();
                break;

            case 'configurable':
                // Get minimum price from child products
                /** @phpstan-ignore-next-line */
                $price = (float)$product->getPriceInfo()
                    ->getPrice('final_price')
                    ->getMinimalPrice()
                    ->getValue();
                break;

            case 'bundle':
                // Get minimum bundle price
                /** @phpstan-ignore-next-line */
                $price = (float)$product->getPriceInfo()
                    ->getPrice('final_price')
                    ->getMinimalPrice()
                    ->getValue();
                break;

            case 'grouped':
                // Get minimum price from associated products
                /** @phpstan-ignore-next-line */
                $price = (float)$product->getPriceInfo()
                    ->getPrice('final_price')
                    ->getMinimalPrice()
                    ->getValue();
                break;

            default:
                // Fallback to regular price
                $price = (float)$product->getPrice();
        }

        return max($price, 0.0); // Ensure non-negative
    }

    /**
     * Clean HTML from description for AI consumption
     *
     * @param string|null $description
     * @return string
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
        $text = trim((string)$text);
        return mb_substr($text, 0, 500);
    }

    /**
     * Get categories filter from config
     *
     * @return array <int, string>
     */
    private function getCategoriesFilter(): array
    {
        $categories = $this->scopeConfig->getValue(self::CONFIG_PATH_CATEGORIES);
        if (empty($categories)) {
            return [];
        }

        return array_filter(explode(',', $categories));
    }

    /**
     * Get Main Image
     *
     * @param ProductInterface $product
     * @return string|null
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function getMainImage(ProductInterface $product): ?string
    {
        /** @var \Magento\Catalog\Model\Product $product */
        $store = $this->storeManager->getStore();
        $baseUrl = $store->getBaseUrl(UrlInterface::URL_TYPE_MEDIA) . 'catalog/product';

        $image = $product->getImage();

        if ($image && $image !== 'no_selection') {
            return $baseUrl . $image;
        }

        $this->galleryReadHandler->execute($product);
        $galleryImages = $product->getMediaGalleryImages();

        if ($galleryImages && $galleryImages->getSize()) {
            $first = $galleryImages->getFirstItem();
            return $baseUrl . $first->getFile();
        }

        return null;
    }

    /**
     * Get Additional Images
     *
     * @param ProductInterface $product
     * @return array <int, string>
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function getAdditionalImages(ProductInterface $product): array
    {
        /** @var \Magento\Catalog\Model\Product $product */
        $store = $this->storeManager->getStore();
        $baseUrl = $store->getBaseUrl(UrlInterface::URL_TYPE_MEDIA) . 'catalog/product';

        $this->galleryReadHandler->execute($product);
        $galleryImages = $product->getMediaGalleryImages();

        $mainImage = $product->getImage();
        $result = [];

        if ($galleryImages) {
            foreach ($galleryImages as $image) {
                $file = $image->getFile();

                if ($file === $mainImage) {
                    continue;
                }

                $result[] = $baseUrl . $file;
            }
        }

        return $result;
    }

    /**
     * Get brand/manufacturer
     *
     * @param ProductInterface $product
     * @return string|null
     */
    private function getBrand(ProductInterface $product): ?string
    {
        /** @var \Magento\Catalog\Model\Product $product */
        // get label của dropdown/multiselect attribute 'manufacturer'
        $brand = $product->getAttributeText('manufacturer');

        if ($brand) {
            if (is_array($brand)) {
                return implode(', ', $brand);
            }
            return (string)$brand;
        }

        return null;
    }

    /**
     * Get inventory quantity
     *
     * @param ProductInterface $product
     * @return int
     */
    private function getInventoryQuantity(ProductInterface $product): int
    {
        /** @var \Magento\Catalog\Model\Product $product */
        try {
            $stockItem = $this->stockRegistry->getStockItemBySku($product->getSku());
            return (int)$stockItem->getQty();
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Get product variants for configurables
     *
     * @param ProductInterface $product
     * @return array<int, array<string, mixed>>
     */
    private function getProductVariants(ProductInterface $product): array
    {
        /** @var \Magento\Catalog\Model\Product $product */
        $variants = [];

        try {
            $childProducts = $this->configurableType->getUsedProducts($product);

            foreach ($childProducts as $child) {
                /** @var \Magento\Catalog\Model\Product $child */
                $variants[] = [
                    'id' => (string)$child->getId(),
                    'sku' => $child->getSku(),
                    'title' => $child->getName(),
                    'price' => $this->formatPrice($child->getFinalPrice(), $this->currencyCode),
                    'weight' => $this->getWeightFormatted($child),
                    'availability' => $child->isSalable() ? 'in_stock' : 'out_of_stock',
                    'inventory_quantity' => $this->getInventoryQuantity($child),
                ];
            }
        } catch (\Exception $e) {
            // If can't load variants, return empty array
            return [];
        }

        return $variants;
    }

    /**
     * Format Price
     *
     * @param mixed $price
     * @param string $currency
     * @return string
     */
    private function formatPrice($price, string $currency): string
    {
        return number_format($price, 2, '.', '') . ' ' . $currency;
    }

    /**
     * Get Sale Price
     *
     * @param ProductInterface $product
     * @return float|null
     */
    private function getSalePrice(ProductInterface $product): ?float
    {
        /** @var \Magento\Catalog\Model\Product $product */
        $specialPrice = $product->getSpecialPrice();

        if (!$specialPrice) {
            return null;
        }

        $from = $product->getSpecialFromDate();
        $to   = $product->getSpecialToDate();
        $now  = (new \DateTime())->format('Y-m-d H:i:s');

        if (($from && $now < $from) || ($to && $now > $to)) {
            return null;
        }

        return (float)$specialPrice;
    }
}
