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
abstract class Creativestyle_AmazonPayments_Block_Login_Abstract extends Creativestyle_AmazonPayments_Block_Abstract
{
    /**
     * @inheritdoc
     */
    protected function _isActive()
    {
        return $this->isLoginActive()
            && $this->_isCurrentIpAllowed()
            && ($this->_isConnectionSecure() || !$this->isPopupAuthenticationExperience());
    }
}
