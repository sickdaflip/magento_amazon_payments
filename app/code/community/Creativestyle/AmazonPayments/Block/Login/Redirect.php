<?php
/**
 * This file is part of the official Amazon Pay and Login with Amazon extension
 * for Magento 1.x
 *
 * (c) 2015 - 2017 creativestyle GmbH. All Rights reserved
 *
 * Distribution of the derivatives reusing, transforming or being built upon
 * this software, is not allowed without explicit written permission granted
 * by creativestyle GmbH
 *
 * @category   Creativestyle
 * @package    Creativestyle_AmazonPayments
 * @copyright  2015 - 2017 creativestyle GmbH
 * @author     Marek Zabrowarny <ticket@creativestyle.de>
 */
class Creativestyle_AmazonPayments_Block_Login_Redirect extends Creativestyle_AmazonPayments_Block_Login_Abstract
{
    /**
     * Returns access token param name
     *
     * @return string
     */
    public function getAccessTokenParamName() 
    {
        if ($this->hasData('access_token_param_name')) {
            return $this->getData('access_token_param_name');
        }

        return 'access_token';
    }

    /**
     * Returns redirect URL
     *
     * @return string
     */
    public function getRedirectUrl() 
    {
        if ($this->hasData('redirect_url')) {
            return $this->getData('redirect_url');
        }

        return $this->getFailureUrl();
    }

    /**
     * Returns failure URL
     *
     * @return string
     */
    public function getFailureUrl() 
    {
        if ($this->hasData('failure_url')) {
            return $this->getData('failure_url');
        }

        return $this->getUrl('customer/account/login');
    }
}
