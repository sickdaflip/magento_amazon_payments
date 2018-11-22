<?php
/**
 * This file is part of the official Amazon Pay and Login with Amazon extension
 * for Magento 1.x
 *
 * (c) 2018 creativestyle GmbH. All Rights reserved
 *
 * Distribution of the derivatives reusing, transforming or being built upon
 * this software, is not allowed without explicit written permission granted
 * by creativestyle GmbH
 *
 * @category   Creativestyle
 * @package    Creativestyle_AmazonPayments
 * @copyright  2018 creativestyle GmbH
 * @author     Marek Zabrowarny <ticket@creativestyle.de>
 */

class Creativestyle_AmazonPayments_Controller_Checkout extends Creativestyle_AmazonPayments_Controller_Action
{
    /**
     * Current order reference ID
     *
     * @var string|null
     */
    private $_orderReferenceId = null;

    /**
     * Current access token value
     *
     * @var string|null
     */
    private $_accessToken = null;

    /**
     * Returns Amazon checkout instance
     *
     * @return Creativestyle_AmazonPayments_Model_Checkout
     */
    protected function _getAmazonCheckout()
    {
        /** @var Creativestyle_AmazonPayments_Model_Checkout $checkout */
        $checkout = Mage::getSingleton('amazonpayments/checkout');
        return $checkout;
    }

    /**
     * Returns OnePage checkout instance
     *
     * @return Mage_Checkout_Model_Type_Onepage
     */
    protected function _getOnePageCheckout()
    {
        /** @var Mage_Checkout_Model_Type_Onepage $checkout */
        $checkout = Mage::getSingleton('checkout/type_onepage');
        return $checkout;
    }

    /**
     * Returns checkout session instance
     *
     * @return Mage_Checkout_Model_Session
     */
    protected function _getCheckoutSession()
    {
        /** @var Mage_Checkout_Model_Session $checkoutSession */
        $checkoutSession = Mage::getSingleton('checkout/session');
        return $checkoutSession;
    }

    /**
     * Returns saved order reference ID
     *
     * @return string|null
     */
    protected function _getOrderReferenceId()
    {
        return $this->_orderReferenceId;
    }

    /**
     * Returns saved access token
     *
     * @return string|null
     */
    protected function _getAccessToken()
    {
        return $this->_accessToken;
    }

    /**
     * Returns Amazon Pay API adapter instance
     *
     * @return Creativestyle_AmazonPayments_Model_Api_Pay
     */
    protected function _getApi()
    {
        /** @var Creativestyle_AmazonPayments_Model_Api_Pay $api */
        $api = Mage::getSingleton('amazonpayments/api_pay');
        return $api;
    }

    /**
     * Returns current quote entity
     *
     * @return Mage_Sales_Model_Quote
     */
    protected function _getQuote()
    {
        return $this->_getAmazonCheckout()->getQuote();
    }

    /**
     * @param string $handle
     * @return string
     * @throws Mage_Core_Exception
     */
    protected function _getLayoutHandleHtml($handle)
    {
        $layout = $this->getLayout();
        $update = $layout->getUpdate();
        $update->load($handle);
        $layout->generateXml();
        $layout->generateBlocks();
        $output = $layout->getOutput();
        return $output;
    }

    /**
     * Clear Order Reference ID in controller properties
     * and in session data and return its value
     *
     * @throws Varien_Exception
     */
    protected function _clearOrderReferenceId()
    {
        $this->_orderReferenceId = null;
        $this->_getCheckoutSession()->setData('amazon_order_reference_id', null);
    }

    /**
     * Cancels order reference at Amazon Payments gateway
     * and clears corresponding session data
     *
     * @throws Varien_Exception
     * @throws Exception
     */
    protected function _cancelOrderReferenceId()
    {
        if ($this->_orderReferenceId) {
            $this->_getApi()->cancelOrderReference(null, $this->_orderReferenceId);
            $this->_clearOrderReferenceId();
        }
    }

    /**
     * Send Ajax redirect response
     *
     * @return $this
     */
    protected function _ajaxRedirectResponse()
    {
        $this->getResponse()
            ->setHeader('HTTP/1.1', '403 Session Expired')
            ->setHeader('Login-Required', 'true')
            ->sendResponse();
        return $this;
    }

    /**
     * Validate ajax request and redirect on failure
     *
     * @return bool
     * @throws Varien_Exception
     */
    protected function _expireAjax()
    {
        if (!$this->_getQuote()->hasItems() || $this->_getQuote()->getHasError()) {
            $this->_ajaxRedirectResponse();
            return true;
        }

        if ($this->_getCheckoutSession()->getCartWasUpdated(true)) {
            $this->_ajaxRedirectResponse();
            return true;
        }

        if (null === $this->_getOrderReferenceId()) {
            $this->_ajaxRedirectResponse();
            return true;
        }

        return false;
    }

    /**
     * @throws Varien_Exception
     */
    public function preDispatch()
    {
        parent::preDispatch();
        $this->_orderReferenceId = $this->getRequest()->getParam(
            'orderReferenceId',
            $this->_getCheckoutSession()->getData('amazon_order_reference_id')
        );
        $this->_accessToken = $this->getRequest()->getParam(
            'accessToken',
            $this->_getCheckoutSession()->getData('amazon_access_token')
        );
        $this->_getCheckoutSession()->setData('amazon_order_reference_id', $this->_orderReferenceId);
        $this->_getCheckoutSession()->setData('amazon_access_token', $this->_accessToken);
    }

    /**
     * @param Mage_Checkout_Model_Type_Onepage $checkout
     * @return array|Mage_Checkout_Model_Type_Onepage
     * @throws Exception
     */
    protected function _saveShipping(Mage_Checkout_Model_Type_Onepage $checkout)
    {
        // submit draft data of order reference to Amazon gateway
        $this->_getApi()->setOrderReferenceDetails(
            null,
            $this->_getOrderReferenceId(),
            $checkout->getQuote()->getBaseGrandTotal(),
            $checkout->getQuote()->getBaseCurrencyCode()
        );

        $orderReferenceDetails = $this->_getApi()->getOrderReferenceDetails(
            null,
            $this->_getOrderReferenceId(),
            $this->_getAccessToken()
        );

        /** @var Creativestyle_AmazonPayments_Model_Processor_Transaction $transactionProcessor */
        $transactionProcessor = Mage::getModel('amazonpayments/processor_transaction');
        $transactionProcessor->setTransactionDetails($orderReferenceDetails);
        $shippingAddress = $transactionProcessor->getMagentoShippingAddress();
        $billingAddress = $transactionProcessor->getMagentoBillingAddress();
        if (empty($billingAddress)) {
            $billingAddress = $shippingAddress;
        }

        $checkout->saveBilling($billingAddress, false);

        $result = $checkout->saveShipping(
            array_merge($shippingAddress, array('use_for_shipping' => true)),
            false
        );
        return $result;
    }
}
