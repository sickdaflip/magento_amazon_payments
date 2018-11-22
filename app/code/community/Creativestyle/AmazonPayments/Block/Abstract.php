<?php
/**
 * This file is part of the official Amazon Pay and Login with Amazon extension
 * for Magento 1.x
 *
 * (c) 2014 - 2017 creativestyle GmbH. All Rights reserved
 *
 * Distribution of the derivatives reusing, transforming or being built upon
 * this software, is not allowed without explicit written permission granted
 * by creativestyle GmbH
 *
 * @category   Creativestyle
 * @package    Creativestyle_AmazonPayments
 * @copyright  2014 - 2017 creativestyle GmbH
 * @author     Marek Zabrowarny <ticket@creativestyle.de>
 */

/**
 * Amazon Payments abstract block
 *
 * @method $this setIdSuffix(string $value)
 * @method string getIdSuffix()
 */
abstract class Creativestyle_AmazonPayments_Block_Abstract extends Mage_Core_Block_Template
{
    /**
     * Instance of the current quote
     *
     * @var Mage_Sales_Model_Quote
     */
    protected $_quote = null;

    /**
     * ID attribute of the top level block container
     *
     * @var string
     */
    protected $_containerId = null;

    /**
     * Prefix for automatically generated container ID
     *
     * @var string
     */
    protected $_containerIdPrefix = '';

    /**
     * CSS class of the top level block container
     *
     * @var string
     */
    protected $_containerClass = '';

    /**
     * Returns instance of Amazon Payments config object
     *
     * @return Creativestyle_AmazonPayments_Model_Config
     */
    protected function _getConfig()
    {
        return Mage::getSingleton('amazonpayments/config');
    }

    /**
     * Returns instance of the checkout session
     *
     * @return Mage_Checkout_Model_Session
     */
    protected function _getCheckoutSession()
    {
        return Mage::getSingleton('checkout/session');
    }

    /**
     * Returns instance of the customer session
     *
     * @return Mage_Customer_Model_Session
     */
    protected function _getCustomerSession()
    {
        return Mage::getSingleton('customer/session');
    }

    /**
     * Returns Amazon Pay helper
     *
     * @return Creativestyle_AmazonPayments_Helper_Data
     */
    protected function _getHelper()
    {
        return $this->helper('amazonpayments');
    }

    /**
     * Returns Magento core helper
     *
     * @return Mage_Core_Helper_Data
     */
    protected function _getCoreHelper()
    {
        return $this->helper('core');
    }

    /**
     * Returns instance of the current quote
     *
     * @return Mage_Sales_Model_Quote
     */
    protected function _getQuote()
    {
        if (null === $this->_quote) {
            $this->_quote = $this->_getCheckoutSession()->getQuote();
        }

        return $this->_quote;
    }

    /**
     * Checks whether current request is secure
     *
     * @return bool
     */
    protected function _isConnectionSecure()
    {
        return Mage::app()->getStore()->isCurrentlySecure();
    }

    /**
     * Checks whether current requester IP is allowed to display Amazon widgets
     *
     * @return bool
     */
    protected function _isCurrentIpAllowed()
    {
        return $this->_getConfig()->isCurrentIpAllowed();
    }

    /**
     * Checks whether Amazon widgets are allowed to be shown
     * in the current shop locale
     *
     * @return bool
     */
    protected function _isCurrentLocaleAllowed()
    {
        return $this->_getConfig()->isCurrentLocaleAllowed();
    }

    /**
     * Checks whether current quote has at least one virtual item
     *
     * @return bool
     */
    protected function _quoteHasVirtualItems()
    {
        if ($this->isQuoteVirtual()) {
            return true;
        }

        foreach ($this->_getQuote()->getAllVisibleItems() as $item) {
            if ($item->getIsVirtual()) {
                return true;
            }
        }

        return false;
    }

    /**
     * Returns whether block shall be rendered or not
     *
     * @return bool
     */
    protected function _isActive()
    {
        return ($this->isLoginActive() || $this->isPayActive())
            && $this->_isCurrentIpAllowed();
    }

    /**
     * Render Amazon Payments block
     *
     * @return string
     */
    protected function _toHtml()
    {
        try {
            if ($this->_isActive()) {
                return parent::_toHtml();
            }
        } catch (Exception $e) {
            Creativestyle_AmazonPayments_Model_Logger::logException($e);
        }

        return '';
    }

    /**
     * Encode provided $valueToEncode into the JSON format
     *
     * @param mixed $valueToEncode
     * @return string
     */
    protected function _jsonEncode($valueToEncode)
    {
        return $this->_getCoreHelper()->jsonEncode($valueToEncode);
    }

    /**
     * Checks whether Amazon Pay is enabled
     *
     * @return bool
     */
    public function isPayActive()
    {
        return $this->_getConfig()->isPayActive();
    }

    /**
     * Checks whether Amazon Pay is enabled
     * on product details page
     *
     * @return bool
     */
    public function isPayActiveOnProductPage()
    {
        return $this->_getConfig()->isPayActiveOnProductPage();
    }

    /**
     * Checks whether Login with Amazon is enabled
     *
     * @return bool
     */
    public function isLoginActive()
    {
        return $this->_getConfig()->isLoginActive();
    }

    /**
     * Returns Merchant ID for the configured Amazon merchant account
     *
     * @return string
     */
    public function getMerchantId()
    {
        return $this->_getConfig()->getMerchantId();
    }

    /**
     * Returns Amazon app client ID
     *
     * @return string
     */
    public function getClientId()
    {
        return $this->_getConfig()->getClientId();
    }

    /**
     * Returns order reference ID saved in session data
     *
     * @return string|null
     */
    public function getOrderReferenceId()
    {
        return $this->_getCheckoutSession()->getAmazonOrderReferenceId();
    }

    /**
     * Returns display language
     *
     * @return null|string
     */
    public function getDisplayLanguage()
    {
        return $this->_getConfig()->getDisplayLanguage();
    }

    /**
     * Checks whether extension runs in sandbox mode
     *
     * @return bool
     */
    public function isSandboxActive()
    {
        return $this->_getConfig()->isSandboxActive();
    }

    /**
     * Checks whether popup authentication experience shall be used
     *
     * @return bool
     */
    public function isPopupAuthenticationExperience()
    {
        if ($this->_getHelper()->isMobileDevice()) {
            return false;
        }

        return $this->_getConfig()->isPopupAuthenticationExperience()
            || $this->_getConfig()->isAutoAuthenticationExperience()
            && ($this->_isConnectionSecure() || !$this->isLoginActive());
    }

    /**
     * Checks whether current quote contains only virtual products
     *
     * @return bool
     */
    public function isQuoteVirtual()
    {
        return $this->_getQuote()->isVirtual();
    }

    /**
     * Returns ID of block's HTML container
     *
     * @return string
     */
    public function getContainerId()
    {
        if (null === $this->_containerId) {
            if ($containerIdSuffix = $this->getIdSuffix()) {
                $this->_containerId = $this->_containerIdPrefix . ucfirst($containerIdSuffix);
            } else {
                $this->_containerId = uniqid($this->_containerIdPrefix);
            }
        }

        return $this->_containerId;
    }

    /**
     * Returns class name of block's HTML container
     *
     * @return string
     */
    public function getContainerClass()
    {
        return $this->_containerClass;
    }

    /**
     * @return float
     */
    public function getQuoteBaseGrandTotal()
    {
        return (float)$this->_getQuote()->getBaseGrandTotal();
    }
}
