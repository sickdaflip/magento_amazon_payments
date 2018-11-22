<?php
/**
 * This file is part of the official Amazon Pay and Login with Amazon extension
 * for Magento 1.x
 *
 * (c) 2017 - 2018 creativestyle GmbH. All Rights reserved
 *
 * Distribution of the derivatives reusing, transforming or being built upon
 * this software, is not allowed without explicit written permission granted
 * by creativestyle GmbH
 *
 * @category   Creativestyle
 * @package    Creativestyle_AmazonPayments
 * @copyright  2017 - 2018 creativestyle GmbH
 * @author     Marek Zabrowarny <ticket@creativestyle.de>
 */

class Creativestyle_AmazonPayments_Controller_Action extends Mage_Core_Controller_Front_Action
{
    /**
     * Returns instance of Amazon Payments config object
     *
     * @return Creativestyle_AmazonPayments_Model_Config
     */
    protected function _getConfig()
    {
        /** @var Creativestyle_AmazonPayments_Model_Config $config */
        $config = Mage::getSingleton('amazonpayments/config');
        return $config;
    }
}
