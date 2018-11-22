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
 * Amazon Pay JS block
 *
 * @method $this setIsCheckout(int $value)
 * @method int getIsCheckout()
 * @method $this setIsLogout(int $value)
 * @method int getIsLogout()
 */
class Creativestyle_AmazonPayments_Block_Js extends Creativestyle_AmazonPayments_Block_Abstract
{
    /**
     * @inheritdoc
     */
    protected function _construct()
    {
        parent::_construct();
        if (!$this->hasData('template')) {
            $this->setTemplate('creativestyle/amazonpayments/js.phtml');
        }
    }

    /**
     * Returns Widgets JS library URL
     *
     * @return string
     */
    public function getWidgetJsUrl()
    {
        return $this->_getConfig()->getWidgetJsUrl();
    }

    /**
     * Returns JS app configuration
     *
     * @return string
     */
    public function getJsConfig()
    {
        $jsConfig = array(
            'merchantId' => $this->getMerchantId(),
            'clientId' => $this->getClientId(),
            'live' => !$this->isSandboxActive(),
            'popup' => $this->isPopupAuthenticationExperience(),
            'virtual' => $this->isQuoteVirtual(),
            'language' => $this->getDisplayLanguage(),
            'pay' => array(
                'selector' => $this->getPayButtonSelector(),
                'callbackUrl' => $this->getPayButtonCallbackUrl(),
                'design' => $this->getPayButtonDesignParams()
            ),
            'login' => $this->isLoginActive() ? array(
                'selector' => $this->getLoginButtonSelector(),
                'callbackUrl' => $this->getLoginButtonCallbackUrl(),
                'design' => $this->getLoginButtonDesignParams()
            ) : null,
            'checkoutUrl' => $this->getCheckoutUrl(),
            'addToCartUrl' => $this->getAddToCartUrl(),
            'currency' => Mage::app()->getStore()->getBaseCurrencyCode()
        );

        return $this->helper('core')->jsonEncode($jsConfig);
    }

    /**
     * @return null|string
     */
    public function getCheckoutUrl()
    {
        return $this->getUrl('amazonpayments/checkout');
    }

    /**
     * @return null|string
     */
    public function getAddToCartUrl()
    {
        $params = array();
        if ($this->_isConnectionSecure()) {
            $params['_secure'] = true;
        }

        return $this->getUrl('amazonpayments/cart/add', $params);
    }

    /**
     * Returns callback URL for Amazon Pay button
     *
     * @return string|null
     */
    public function getPayButtonCallbackUrl()
    {
        // no callback URL for APA button
        if (!$this->isLoginActive()) {
            return null;
        }

        if ($this->isPopupAuthenticationExperience()) {
            return $this->getUrl('amazonpayments/advanced_login', array('target' => 'checkout'));
        }

        return $this->getUrl('amazonpayments/advanced_login/redirect', array('target' => 'checkout'));
    }

    /**
     * Returns Amazon Pay button design params
     *
     * @return array|null
     */
    public function getPayButtonDesignParams()
    {
        return $this->_getConfig()->getPayButtonDesign();
    }

    /**
     * Returns Amazon Pay buttons DOM selector
     *
     * @return string
     */
    public function getPayButtonSelector()
    {
        return sprintf('.%s', Creativestyle_AmazonPayments_Block_Pay_Button::WIDGET_CONTAINER_ID_PREFIX);
    }

    /**
     * Returns callback URL for Login with Amazon button
     *
     * @return string
     */
    public function getLoginButtonCallbackUrl()
    {
        if ($this->isPopupAuthenticationExperience()) {
            return Mage::getUrl('amazonpayments/advanced_login');
        }

        return Mage::getUrl('amazonpayments/advanced_login/redirect');
    }

    /**
     * Returns Login with Amazon button design params
     *
     * @return array
     */
    public function getLoginButtonDesignParams()
    {
        return $this->_getConfig()->getLoginButtonDesign();
    }

    /**
     * Returns Login with Amazon buttons DOM selector
     *
     * @return string|null
     */
    public function getLoginButtonSelector()
    {
        return $this->isLoginActive()
            ? sprintf('.%s', Creativestyle_AmazonPayments_Block_Login_Button::WIDGET_CONTAINER_ID_PREFIX)
            : null;
    }

    /**
     * Returns JSON-formatted checkout URLs
     *
     * @return string
     */
    public function getCheckoutUrls()
    {
        $urls = array(
            'saveShipping' => $this->getUrl('amazonpayments/checkout/saveShipping'),
            'saveShippingMethod' => $this->getUrl('amazonpayments/checkout/saveShippingMethod'),
            'saveOrder' => $this->getUrl('amazonpayments/checkout/saveOrder'),
            'saveCoupon' => $this->getUrl('amazonpayments/checkout/couponPost'),
            'clearOrderReference' => $this->getUrl('amazonpayments/checkout/clearOrderReference'),
            'cancelOrderReference' => $this->getUrl('amazonpayments/checkout/cancelOrderReference'),
            'failure' => $this->getUrl('checkout/cart')
        );
        return $this->_jsonEncode($urls);
    }

    public function isOnePageCheckout()
    {
        return $this->helper('amazonpayments')->isOnePageCheckout();
    }
}
