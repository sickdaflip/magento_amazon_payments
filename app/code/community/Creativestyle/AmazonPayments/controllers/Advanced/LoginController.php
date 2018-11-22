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
class Creativestyle_AmazonPayments_Advanced_LoginController extends Mage_Core_Controller_Front_Action
{

    const ACCESS_TOKEN_PARAM_NAME = 'access_token';

    protected function _extractAccessTokenFromUrl()
    {
        $accessToken = $this->getRequest()->getParam(self::ACCESS_TOKEN_PARAM_NAME, null);
        $accessToken = str_replace('|', '%7C', $accessToken);
        return $accessToken;
    }

    protected function _getApi()
    {
        return Mage::getModel('amazonpayments/api_login');
    }

    protected function _getConfig()
    {
        return Mage::getSingleton('amazonpayments/config');
    }

    /**
     * Returns checkout session instance
     *
     * @return Mage_Checkout_Model_Session
     */
    protected function _getCheckoutSession()
    {
        return Mage::getSingleton('checkout/session');
    }

    /**
     * Returns customer session instance
     *
     * @return Mage_Customer_Model_Session
     */
    protected function _getCustomerSession()
    {
        return Mage::getSingleton('customer/session');
    }

    protected function _getRedirectUrl() 
    {
        return Mage::getUrl(
            '*/*/index', array(
                self::ACCESS_TOKEN_PARAM_NAME => '%s',
                'target' => $this->getRequest()->getParam('target', null)
            )
        );
    }

    protected function _getRedirectFailureUrl() 
    {
        if (strtolower($this->getRequest()->getParam('target', null)) == 'checkout') {
            return Mage::getUrl('checkout/cart');
        }

        return Mage::getUrl('customer/account/login');
    }

    protected function _getTargetUrl() 
    {
        if (strtolower($this->getRequest()->getParam('target', null)) == 'checkout') {
            $accessToken = $this->_extractAccessTokenFromUrl();
            return Mage::getUrl('amazonpayments/checkout/', array('accessToken' => $accessToken));
        }

        return Mage::getUrl('customer/account/');
    }

    protected function _validateUserProfile($userProfile)
    {
        return $userProfile instanceof Varien_Object
            && $userProfile->getEmail()
            && $userProfile->getName()
            && $userProfile->getUserId();
    }

    protected function _validateAuthToken($authToken)
    {
        return $authToken instanceof Varien_Object && $authToken->getAud() != '';
    }

    public function preDispatch() 
    {
        parent::preDispatch();
        if (!$this->_getConfig()->isLoginActive()) {
            $this->_forward('noRoute');
        }
    }

    // @codingStandardsIgnoreStart
    public function indexAction() 
    {
        $accessToken = $this->_extractAccessTokenFromUrl();
        if (null !== $accessToken) {
            $accessToken = urldecode($accessToken);
            try {
                $tokenInfo = $this->_getApi()->getTokenInfo($accessToken);
                if ($this->_validateAuthToken($tokenInfo)) {
                    $userProfile = $this->_getApi()->getUserProfile($accessToken);
                    if ($this->_validateUserProfile($userProfile)) {
                        $loginService = Mage::getModel('amazonpayments/service_login', $userProfile);
                        $connectStatus = $loginService->connect();
                        switch ($connectStatus->getStatus()) {
                            case Creativestyle_AmazonPayments_Model_Service_Login::ACCOUNT_STATUS_OK:
                                $this->_getCustomerSession()->setCustomerAsLoggedIn($connectStatus->getCustomer());
                                $this->_redirectUrl($this->_getTargetUrl());
                                return;
                            case Creativestyle_AmazonPayments_Model_Service_Login::ACCOUNT_STATUS_CONFIRM:
                                $loginPost = $this->getRequest()->getPost('login', array());
                                if (!empty($loginPost) && array_key_exists('password', $loginPost)) {
                                    if ($connectStatus->getCustomer()->validatePassword($loginPost['password'])) {
                                        $connectStatus->getCustomer()->setAmazonUserId($userProfile->getUserId())
                                            ->save();
                                        $this->_getCustomerSession()
                                            ->setCustomerAsLoggedIn($connectStatus->getCustomer());
                                        $this->_redirectUrl($this->_getTargetUrl());
                                        return;
                                    } else {
                                        $this->_getCustomerSession()->addError($this->__('Invalid password'));
                                    }
                                }

                                $update = $this->getLayout()->getUpdate();
                                $update->addHandle('default');
                                $this->addActionLayoutHandles();
                                $update->addHandle('amazonpayments_account_confirm');
                                $this->loadLayoutUpdates();
                                $this->generateLayoutXml()->generateLayoutBlocks();
                                $this->_initLayoutMessages('customer/session');
                                $formBlock = $this->getLayout()->getBlock('amazonpayments_login_account_confirm');
                                if ($formBlock) {
                                    $formBlock->setData('back_url', $this->_getRefererUrl());
                                    $formBlock->setUsername($connectStatus->getCustomer()->getEmail());
                                }

                                $this->renderLayout();
                                return;
                            case Creativestyle_AmazonPayments_Model_Service_Login::ACCOUNT_STATUS_DATA_MISSING:
                                $accountPost = $this->getRequest()->getPost('account', array());
                                if ($connectStatus->getRequiredData() && !empty($accountPost)) {
                                    $requiredData = $connectStatus->getRequiredData();
                                    $postedData = array();
                                    foreach ($accountPost as $attribute => $value) {
                                        if ($value) {
                                            $postedData[] = $attribute;
                                        }
                                    }

                                    $dataDiff = array_diff($requiredData, $postedData);
                                    if (empty($dataDiff)) {
                                        $connectStatus = $loginService->connect($accountPost);
                                        if ($connectStatus->getStatus() == Creativestyle_AmazonPayments_Model_Service_Login::ACCOUNT_STATUS_OK) {
                                            $this->_getCustomerSession()->setCustomerAsLoggedIn($connectStatus->getCustomer());
                                            $this->_redirectUrl($this->_getTargetUrl());
                                            return;
                                        } else {
                                            $this->_getCustomerSession()->addError($this->__('Please provide all required data.'));
                                        }
                                    } else {
                                        $this->_getCustomerSession()->addError($this->__('Please provide all required data.'));
                                    }
                                }

                                $update = $this->getLayout()->getUpdate();
                                $update->addHandle('default');
                                $this->addActionLayoutHandles();
                                $update->addHandle('amazonpayments_account_update');
                                $this->loadLayoutUpdates();
                                $this->generateLayoutXml()->generateLayoutBlocks();
                                $this->_initLayoutMessages('customer/session');
                                $formBlock = $this->getLayout()->getBlock('amazonpayments_login_account_update');
                                if ($formBlock) {
                                    $formBlock->setData('back_url', $this->_getRefererUrl());
                                    $formBlock->setFieldNameFormat('account[%s]');
                                    $formData = new Varien_Object($accountPost);
                                    if (!$formData->getFirstname() || !$formData->getLastname()) {
                                        $customerName = Mage::helper('amazonpayments')->explodeCustomerName($userProfile->getName());
                                        if (!$formData->getFirstname()) {
                                            $formData->setData('firstname', $customerName->getFirstname());
                                        }

                                        if (!$formData->getLastname()) {
                                            $formData->setData('lastname', $customerName->getLastname());
                                        }
                                    }

                                    $formBlock->setFormData($formData);
                                }

                                $this->renderLayout();
                                return;
                            case Creativestyle_AmazonPayments_Model_Service_Login::ACCOUNT_STATUS_ERROR:
                                throw new Creativestyle_AmazonPayments_Exception('[LWA-controller] Error when connecting accounts');
                        }
                    }

                    throw new Creativestyle_AmazonPayments_Exception('[LWA-controller] Retrieved user profile is invalid');
                }

                throw new Creativestyle_AmazonPayments_Exception('[LWA-controller] Provided access_token is invalid');
            } catch (Exception $e) {
                Creativestyle_AmazonPayments_Model_Logger::logException($e);
                if (strtolower($this->getRequest()->getParam('target', null)) == 'checkout') {
                    $this->_getCheckoutSession()->addError($this->__('There was an error connecting your Amazon account. Please contact us or try again later.'));
                } else {
                    $this->_getCustomerSession()->addError($this->__('There was an error connecting your Amazon account. Please contact us or try again later.'));
                }

                $this->_redirectReferer();
                return;
            }
        } elseif ($error = $this->getRequest()->getParam('error', false)) {
            if (strtolower($this->getRequest()->getParam('target', null)) == 'checkout') {
                $this->_getCheckoutSession()->addError($this->__('You have aborted the login with Amazon. Please contact us or try again.'));
            } else {
                $this->_getCustomerSession()->addError($this->__('You have aborted the login with Amazon. Please contact us or try again.'));
            }

            $this->_redirectUrl($this->_getRedirectFailureUrl());
            return;
        }

        $this->_forward('noRoute');
    }
    // @codingStandardsIgnoreEnd

    public function redirectAction() 
    {
        $this->loadLayout();
        $this->getLayout()->getBlock('head')->setTitle($this->__('Login with Amazon'));
        $this->getLayout()->getBlock('amazonpayments_login_redirect')
            ->setAccessTokenParamName(self::ACCESS_TOKEN_PARAM_NAME)
            ->setRedirectUrl($this->_getRedirectUrl())
            ->setFailureUrl($this->_getRedirectFailureUrl());
        $this->renderLayout();
    }

    public function disconnectAction() 
    {
        if ($customer = $this->_getCustomerSession()->getCustomer()) {
            if ($customer->getAmazonUserId()) {
                $customer->setAmazonUserId(null)->save();
            }
        }

        $this->_redirect('customer/account');
    }
}
