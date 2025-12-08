<?php

declare(strict_types=1);

namespace RunAsRoot\AgenticCommerceProtocol\Model\Source;

use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory;
use Magento\Framework\Api\SortOrder;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Data\OptionSourceInterface;
use Magento\Store\Model\Store;

class Category implements OptionSourceInterface
{
    public const NONE = 0;
    public const SYSTEM_CATEGORY_ID = 0;
    public const ROOT_LEVEL = 0;

    /**
     * @var CollectionFactory
     */
    private $collectionFactory;

    /**
     * @var RequestInterface
     */
    private $request;

    /**
     * @param CollectionFactory $collectionFactory
     * @param RequestInterface $request
     */
    public function __construct(
        CollectionFactory $collectionFactory,
        RequestInterface $request
    ) {
        $this->collectionFactory = $collectionFactory;
        $this->request = $request;
    }

    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray(): array
    {
        $optionArray = [];
        $arr = $this->toArray();
        foreach ($arr as $value => $label) {
            $optionArray[] = [
                'value' => $value,
                'label' => $label
            ];
        }

        return $optionArray;
    }

    /**
     * Get options in "key-value" format
     *
     * @return array
     */
    public function toArray(): array
    {
        return $this->getChildren(self::SYSTEM_CATEGORY_ID, self::ROOT_LEVEL);
    }

    /**
     * Get Children option
     *
     * @param mixed $parentCategoryId
     * @param mixed $level
     * @return array
     */
    private function getChildren(int $parentCategoryId, int $level)
    {
        $storeId = (int) $this->request->getParam(Store::ENTITY, Store::DEFAULT_STORE_ID);
        $options[self::NONE] = __('None');
        $collection = $this->collectionFactory->create();
        $collection->setStoreId($storeId);
        $collection->addAttributeToSelect('name');
        $collection->addAttributeToFilter('level', $level);
        $collection->addAttributeToFilter('parent_id', $parentCategoryId);
        $collection->setOrder('position', SortOrder::SORT_ASC);

        foreach ($collection as $category) {
            $options[$category->getId()] =
                str_repeat(". ", max(0, ($category->getLevel() - 1) * 3)) . $category->getName();
            if ($category->hasChildren()) {
                $options = array_replace(
                    $options,
                    $this->getChildren((int) $category->getId(), $category->getLevel() + 1)
                );
            }
        }

        return $options;
    }
}
