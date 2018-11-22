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
class Creativestyle_AmazonPayments_Model_Api_Login extends Creativestyle_AmazonPayments_Model_Api_Abstract
{

    protected function _initApi($path, $params = null) 
    {
        $url = trim($this->_getConfig()->getLoginApiUrl($this->_store), '/') . '/' . vsprintf($path, $params);
        // @codingStandardsIgnoreStart
        $this->_api = curl_init($url);
        curl_setopt($this->_api, CURLOPT_RETURNTRANSFER, true);
        // @codingStandardsIgnoreEnd
        return $this;
    }

    protected function _call() 
    {
        if (null !== $this->_api) {
            // @codingStandardsIgnoreStart
            $response = curl_exec($this->_api);

            if ($response === false) {
                $errorNo = curl_errno($this->_api);
                $errorMsg = curl_error($this->_api);
                curl_close($this->_api);
                $this->_api = null;
                throw new Creativestyle_AmazonPayments_Exception('[LWA-cURL:' . $errorNo . '] ' . $errorMsg);
            }

            $responseData = $this->_processApiResponse($response);
            curl_close($this->_api);
            $this->_api = null;
            if ($responseData->getError()) {
                throw new Creativestyle_AmazonPayments_Exception(
                    '[LWA-API:' . $responseData->getError() . '] '
                    . ($responseData->hasErrorDescription()
                        ? $responseData->getErrorDescription() : $responseData->getError())
                );
            }
            // @codingStandardsIgnoreEnd

            return $responseData;
        }

        return null;
    }

    protected function _setAuthorizationHeader($header) 
    {
        if (null !== $this->_api) {
            // @codingStandardsIgnoreStart
            curl_setopt($this->_api, CURLOPT_HTTPHEADER, array('Authorization: bearer ' . $header));
            // @codingStandardsIgnoreEnd
        }

        return $this;
    }

    protected function _processApiResponse($response) 
    {
        if ($response !== false) {
            $responseData = json_decode($response, true);
            if (!empty($responseData)) {
                return new Varien_Object($responseData);
            }
        }

        return null;
    }

    public function getTokenInfo($accessToken) 
    {
        return $this->_initApi('auth/o2/tokeninfo?access_token=%s', array(urlencode($accessToken)))->_call();
    }

    public function getUserProfile($accessToken) 
    {
        return $this->_initApi('user/profile')->_setAuthorizationHeader($accessToken)->_call();
    }
}
