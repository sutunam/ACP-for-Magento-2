<?php
declare(strict_types=1);

namespace RunAsRoot\AgenticCommerceProtocol\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Store\Model\ScopeInterface;

class Data extends AbstractHelper
{
    private const XML_PATH_SELLER_PRIVACY_POLICY_PAGE = 'acp/product_feed/seller_privacy_policy_page';
    private const XML_PATH_SELLER_TOS_PAGE = 'acp/product_feed/seller_tos_page';
    private const XML_PATH_RETURN_POLICY_PAGE = 'acp/product_feed/return_policy_page';

    /**
     * @var \Magento\Framework\UrlInterface
     */
    protected $urlBuilder;

    /**
     * Constructor
     *
     * @param \Magento\Framework\UrlInterface $urlBuilder
     * @param Context $context
     */
    public function __construct(
        \Magento\Framework\UrlInterface $urlBuilder,
        Context $context
    )
    {
        $this->urlBuilder = $urlBuilder;
        parent::__construct($context);
    }

    /**
     * Get Seller Privacy Policy Page
     *
     * @return string
     */
    public function getSellerPrivacyPolicyPage(): string
    {
        return $this->scopeConfig->getValue(
            self::XML_PATH_SELLER_PRIVACY_POLICY_PAGE,
            ScopeInterface::SCOPE_STORE
        ) ?? '';
    }

    /**
     * Get Seller Tos Page
     *
     * @return string
     */
    public function getSellerTosPage(): string
    {
        return $this->scopeConfig->getValue(
            self::XML_PATH_SELLER_TOS_PAGE,
            ScopeInterface::SCOPE_STORE
        ) ?? '';
    }

    /**
     * Get Return Policy Page
     *
     * @return string
     */
    public function getReturnPolicyPage(): string
    {
        return $this->scopeConfig->getValue(
            self::XML_PATH_RETURN_POLICY_PAGE,
            ScopeInterface::SCOPE_STORE
        ) ?? '';
    }

    /**
     * Get PageUrl
     *
     * @param string|null $pageIdentifier
     * @return string
     */
    public function getPageUrl(?string $pageIdentifier): string
    {
        if (!$pageIdentifier) {
            return '';
        }

        return $this->urlBuilder->getUrl(null, ['_direct' => $pageIdentifier]);
    }

}
