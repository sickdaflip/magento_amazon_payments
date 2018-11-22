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
abstract class Creativestyle_AmazonPayments_Model_Api_Abstract
{
    /**
     * @var mixed
     */
    protected $_api = null;

    /**
     * @var mixed
     */
    protected $_store = null;

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
     * Returns Merchant ID for the configured Amazon merchant account
     *
     * @return string
     */
    public function getMerchantId()
    {
        return $this->_getConfig()->getMerchantId($this->_store);
    }

    /**
     * Sets store view scope for API connection
     *
     * @param mixed $store
     * @return $this
     */
    public function setStore($store = null)
    {
        $this->_store = $store;
        return $this;
    }
}
