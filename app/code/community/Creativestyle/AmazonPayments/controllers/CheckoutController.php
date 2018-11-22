<?php
/**
 * This file is part of the official Amazon Pay and Login with Amazon extension
 * for Magento 1.x
 *
 * (c) 2014 - 2018 creativestyle GmbH. All Rights reserved
 *
 * Distribution of the derivatives reusing, transforming or being built upon
 * this software, is not allowed without explicit written permission granted
 * by creativestyle GmbH
 *
 * @category   Creativestyle
 * @package    Creativestyle_AmazonPayments
 * @copyright  2014 - 2018 creativestyle GmbH
 * @author     Marek Zabrowarny <ticket@creativestyle.de>
 */
class Creativestyle_AmazonPayments_CheckoutController extends Creativestyle_AmazonPayments_Controller_Checkout
{
    /**
     * Returns Amazon checkout instance
     *
     * @return Creativestyle_AmazonPayments_Model_Checkout
     */
    protected function _getCheckout()
    {
        /** @var Creativestyle_AmazonPayments_Model_Checkout $checkout */
        $checkout = Mage::getSingleton('amazonpayments/checkout');
        return $checkout;
    }

    /**
     * @return string
     * @throws Mage_Core_Exception
     */
    protected function _getShippingMethodsHtml()
    {
        $layout = $this->getLayout();
        $update = $layout->getUpdate();
        $update->load('amazonpayments_checkout_shippingmethod');
        $layout->generateXml();
        $layout->generateBlocks();
        $output = $layout->getOutput();
        return $output;
    }

    /**
     * @return string
     * @throws Mage_Core_Exception
     */
    protected function _getReviewHtml()
    {
        $layout = $this->getLayout();
        $update = $layout->getUpdate();
        $update->load('amazonpayments_checkout_review');
        $layout->generateXml();
        $layout->generateBlocks();
        $output = $layout->getOutput();
        return $output;
    }

    protected function _isSubmitAllowed()
    {
        if (!$this->_getQuote()->isVirtual()) {
            $address = $this->_getQuote()->getShippingAddress();
            $method = $address->getShippingMethod();
            $rate = $address->getShippingRateByCode($method);
            if (!$this->_getQuote()->isVirtual() && (!$method || !$rate)) {
                return false;
            }
        }

        return true;
    }

    public function indexAction()
    {
        try {
            if (!$this->_getQuote()->hasItems() || $this->_getQuote()->getHasError()) {
                $this->_redirect('checkout/cart');
                return;
            }

            if (!$this->_getQuote()->validateMinimumAmount()) {
                $error = Mage::getStoreConfig('sales/minimum_order/error_message') ?
                    Mage::getStoreConfig('sales/minimum_order/error_message') :
                    Mage::helper('checkout')->__('Subtotal must exceed minimum order amount');
                $this->_getCheckoutSession()->addError($error);
                $this->_redirect('checkout/cart');
                return;
            }

            if (null === $this->_getOrderReferenceId() && null === $this->_getAccessToken()) {
                $this->_redirect('checkout/cart');
                return;
            }

            $this->_getCheckoutSession()->setCartWasUpdated(false);
            $this->_getCheckout()->savePayment(null);

            if ($this->_getConfig()->getCheckoutType() ==
                Creativestyle_AmazonPayments_Model_Lookup_CheckoutType::CHECKOUT_TYPE_ONEPAGE) {
                $this->_redirect(
                    'checkout/onepage',
                    array(
                        'lpa' => true,
                        'orderReferenceId' => $this->_getOrderReferenceId(),
                        'accessToken' => $this->_getAccessToken()
                    )
                );
                return;
            }

            $this->loadLayout();
            $this->getLayout()->getBlock('head')->setTitle($this->__('Amazon Pay'));
            $this->renderLayout();
        } catch (Exception $e) {
            Creativestyle_AmazonPayments_Model_Logger::logException($e);
            $this->_getCheckoutSession()->addError(
                $this->__('There was an error processing your order. Please contact us or try again later.')
            );
            $this->_redirect('checkout/cart');
            return;
        }
    }

    public function saveShippingAction()
    {
        if ($this->getRequest()->isPost()) {
            try {
                if ($this->_expireAjax()) {
                    return;
                }

                $result = $this->_saveShipping($this->_getAmazonCheckout());
            } catch (Exception $e) {
                Creativestyle_AmazonPayments_Model_Logger::logException($e);
                $result = array(
                    'error' => -1,
                    'error_messages' => $e->getMessage(),
                    'allow_submit' => false
                );
            }

            if (!isset($result['error'])) {
                $result = array(
                    'render_widget' => array(
                        'shipping-method' => $this->_getLayoutHandleHtml('amazonpayments_checkout_shippingmethod')
                    ),
                    'allow_submit' => false,
                    'goto_section' => 'shipping_method',
                    'update_sections' => array(
                        array(
                            'name' => 'shipping-method',
                            'html' => $this->_getLayoutHandleHtml('checkout_onepage_shippingmethod')
                        ),
                    )
                );
            };
        } else {
            $this->_forward('noRoute');
            return;
        }

        $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($result));
    }

    public function opcSaveShippingAction()
    {
        if ($this->getRequest()->isPost()) {
            try {
                if ($this->_expireAjax()) {
                    return;
                }

                $result = $this->_saveShipping($this->_getOnePageCheckout());
            } catch (Exception $e) {
                Creativestyle_AmazonPayments_Model_Logger::logException($e);
                $result = array(
                    'error' => -1,
                    'error_messages' => $e->getMessage(),
                    'allow_submit' => false
                );
            }

            if (!isset($result['error'])) {
                $result = array(
                    'render_widget' => array(
                        'shipping-method' => $this->_getShippingMethodsHtml()
                    ),
                    'allow_submit' => false
                );
            };
        } else {
            $this->_forward('noRoute');
            return;
        }

        $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($result));
    }

    /**
     * @throws Mage_Core_Exception
     */
    public function saveShippingMethodAction()
    {
        if ($this->getRequest()->isPost()) {
            try {
                if ($this->_expireAjax()) {
                    return;
                }

                $data = $this->getRequest()->getPost('shipping_method', '');
                $this->_getCheckout()->saveShippingMethod($data);
            } catch (Exception $e) {
                Creativestyle_AmazonPayments_Model_Logger::logException($e);
                $result = array(
                    'error' => true,
                    'error_messages' => $e->getMessage(),
                    'allow_submit' => false
                );
                $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($result));
                return;
            }

            Mage::dispatchEvent(
                'checkout_controller_onepage_save_shipping_method',
                array('request' => $this->getRequest(), 'quote' => $this->_getQuote())
            );
            $this->_getQuote()->collectTotals()->save();
            $result = array(
                'render_widget' => array('review' => $this->_getReviewHtml()),
                'allow_submit' => $this->_isSubmitAllowed()
            );
            $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($result));
        } else {
            $this->_forward('noRoute');
        }
    }

    /**
     * @param array $orderReference
     * @return array|null
     */
    protected function _validateOrderReference($orderReference)
    {
        if (isset($orderReference['Constraints']) && is_array($orderReference['Constraints'])) {
            foreach ($orderReference['Constraints'] as $constraint) {
                switch ($constraint['ConstraintID']) {
                    case 'ShippingAddressNotSet':
                        return array(
                            'success' => false,
                            'error' => true,
                            'error_messages' => $this->__(
                                'There has been a problem with the selected payment method from your Amazon account, '
                                . 'please update the payment method or choose another one.'
                            ),
                            'allow_submit' => false
                        );
                    case 'PaymentMethodNotAllowed':
                    case 'PaymentPlanNotSet':
                        return array(
                            'success' => false,
                            'error' => true,
                            'error_messages' => $this->__(
                                'There has been a problem with the selected payment method from your Amazon account, '
                                . 'please update the payment method or choose another one.'
                            ),
                            'allow_submit' => $this->_isSubmitAllowed(),
                            'deselect_payment' => true,
                            'render_widget' => array(
                                'wallet' => true
                            )
                        );
                }
            }
        }

        return null;
    }

    /**
     * @param array $postedAgreements
     * @param array $requiredAgreements
     * @return array|null
     */
    protected function _validateCheckoutAgreements($postedAgreements, $requiredAgreements)
    {
        if ($requiredAgreements) {
            $diff = array_diff($requiredAgreements, $postedAgreements);
            if ($diff) {
                return array(
                    'success' => false,
                    'error' => true,
                    'error_messages' => $this->__(
                        'Please agree to all the terms and conditions before placing the order.'
                    ),
                    'allow_submit' => $this->_isSubmitAllowed()
                );
            }
        }

        return null;
    }

    /**
     * @param Creativestyle_AmazonPayments_Exception_InvalidTransaction $e
     * @return array|null
     * @throws Varien_Exception
     */
    protected function _handleInvalidTransactionException(Creativestyle_AmazonPayments_Exception_InvalidTransaction $e)
    {
        if ($e->getType() == Creativestyle_AmazonPayments_Model_Processor_Transaction::TRANSACTION_TYPE_AUTH) {
            if ($e->getState()
                == Creativestyle_AmazonPayments_Model_Processor_Transaction::TRANSACTION_STATE_DECLINED) {
                if ($e->getReasonCode()
                    == Creativestyle_AmazonPayments_Model_Processor_Transaction::TRANSACTION_REASON_INVALID_PAYMENT) {
                    return array(
                        'success'        => false,
                        'error'          => true,
                        'error_messages' => $this->__(
                            'There has been a problem with the selected payment method from your Amazon account, '
                            . 'please update the payment method or choose another one.'
                        ),
                        'allow_submit' => $this->_isSubmitAllowed(),
                        'deselect_payment' => true,
                        'render_widget' => array(
                            'wallet' => true
                        ),
                        'disable_widget' => array(
                            'address-book' => true,
                            'shipping-method' => true,
                            'review' => true
                        )
                    );
                }
            }
        }

        if ($e->getType() == Creativestyle_AmazonPayments_Model_Processor_Transaction::TRANSACTION_TYPE_AUTH) {
            if ($e->getState()
                == Creativestyle_AmazonPayments_Model_Processor_Transaction::TRANSACTION_STATE_DECLINED) {
                if ($e->getReasonCode()
                    == Creativestyle_AmazonPayments_Model_Processor_Transaction::TRANSACTION_REASON_AMAZON_REJECTED) {
                    $this->_clearOrderReferenceId();
                }
            }
        }

        if ($e->getType() == Creativestyle_AmazonPayments_Model_Processor_Transaction::TRANSACTION_TYPE_AUTH) {
            if ($e->getState()
                == Creativestyle_AmazonPayments_Model_Processor_Transaction::TRANSACTION_STATE_DECLINED) {
                if ($e->getReasonCode()
                    == Creativestyle_AmazonPayments_Model_Processor_Transaction::TRANSACTION_REASON_TIMEOUT) {
                    $this->_cancelOrderReferenceId();
                }
            }
        }

        Creativestyle_AmazonPayments_Model_Logger::logException($e);

        $this->_getCheckoutSession()->addError(
            $this->__('There was an error processing your order. Please contact us or try again later.')
        );

        return array(
            'success' => false,
            'error' => true,
            'redirect' => Mage::getUrl('checkout/cart')
        );
    }

    /**
     * @throws Varien_Exception
     */
    public function saveOrderAction()
    {
        try {
            // validate checkout agreements
            $result = $this->_validateCheckoutAgreements(
                array_keys($this->getRequest()->getPost('agreement', array())),
                Mage::helper('checkout')->getRequiredAgreementIds()
            );
            if ($result) {
                $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($result));
                return;
            }

            // validate order reference
            $orderReferenceDetails = $this->_getApi()->getOrderReferenceDetails(
                null,
                $this->_getOrderReferenceId(),
                $this->_getAccessToken()
            );
            $result = $this->_validateOrderReference($orderReferenceDetails);
            if ($result) {
                $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($result));
                return;
            }

            $skipOrderReferenceProcessing = false;
            if (isset($orderReferenceDetails['OrderReferenceStatus'])) {
                $orderReferenceStatus = $orderReferenceDetails['OrderReferenceStatus'];
                if (isset($orderReferenceStatus['State'])) {
                    $skipOrderReferenceProcessing = $orderReferenceStatus['State']
                        != Creativestyle_AmazonPayments_Model_Processor_Transaction::TRANSACTION_STATE_DRAFT;
                }
            }

            $sequenceNumber = (int)$this->_getCheckoutSession()->getAmazonSequenceNumber();
            $this->_getQuote()->getPayment()
                ->setTransactionId($this->_getOrderReferenceId())
                ->setSkipOrderReferenceProcessing($skipOrderReferenceProcessing)
                ->setAmazonSequenceNumber($sequenceNumber ? $sequenceNumber : null);
            $this->_getCheckoutSession()->setAmazonSequenceNumber($sequenceNumber ? $sequenceNumber + 1 : 1);

            $customFields = $this->getRequest()->getPost('custom_fields', array());
            foreach ($customFields as $customFieldName => $customFieldValue) {
                $this->_getQuote()->setData($customFieldName, $customFieldValue);
            }

            $simulation = $this->getRequest()->getPost('simulation', array());
            if (!empty($simulation) && isset($simulation['object'])) {
                $simulationData = array(
                    'object' => isset($simulation['object']) ? $simulation['object'] : null,
                    'state' => isset($simulation['state']) ? $simulation['state'] : null,
                    'reason_code' => isset($simulation['reason']) ? $simulation['reason'] : null
                );
                $simulationData['options'] = Creativestyle_AmazonPayments_Model_Simulator::getSimulationOptions(
                    $simulationData['object'],
                    $simulationData['state'],
                    $simulationData['reason_code']
                );
                $this->_getQuote()->getPayment()->setSimulationData($simulationData);
            }

            $this->_getCheckout()->saveOrder();
            $this->_getQuote()->save();

            $this->_getApi()->setOrderAttributes(
                null,
                $this->_getOrderReferenceId(),
                $this->_getCheckoutSession()->getLastRealOrderId()
            );

            $result = array(
                'success' => true,
                'error' => false,
                'redirect' => Mage::getUrl('checkout/onepage/success')
            );
            $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($result));
        } catch (Creativestyle_AmazonPayments_Exception_InvalidTransaction $e) {
            $result = $this->_handleInvalidTransactionException($e);
            if (is_array($result)) {
                $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($result));
                return;
            }
        } catch (Exception $e) {
            Creativestyle_AmazonPayments_Model_Logger::logException($e);
            Mage::helper('checkout')->sendPaymentFailedEmail($this->_getQuote(), $e->getMessage());
            $this->_getCheckoutSession()->addError(
                $this->__('There was an error processing your order. Please contact us or try again later.')
            );
            $result = array(
                'success' => false,
                'error' => true,
                'redirect' => Mage::getUrl('checkout/cart')
            );
            $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($result));
        }
    }

    /**
     * @throws Varien_Exception
     */
    public function clearOrderReferenceAction()
    {
        $this->_clearOrderReferenceId();
        $this->_redirect('checkout/cart');
    }

    /**
     * @throws Varien_Exception
     */
    public function cancelOrderReferenceAction()
    {
        $this->_cancelOrderReferenceId();
        $this->_redirect('checkout/cart');
    }

    /**
     * @throws Mage_Core_Exception
     */
    public function couponPostAction()
    {
        if ($this->getRequest()->isPost()) {
            try {
                if ($this->_expireAjax()) {
                    return;
                }

                $couponCode = (string) $this->getRequest()->getParam('coupon_code');
                if ($this->getRequest()->getParam('remove') == 1) {
                    $couponCode = '';
                }

                $this->_getQuote()->getShippingAddress()->setCollectShippingRates(true);
                $result = $this->_getQuote()->setCouponCode($couponCode)
                    ->collectTotals()
                    ->save();
            } catch (Exception $e) {
                Creativestyle_AmazonPayments_Model_Logger::logException($e);
                $result = array(
                    'error' => -1,
                    'error_messages' => $e->getMessage()
                );
            }

            if (!isset($result['error'])) {
                $result = array(
                    'render_widget' => array(
                        'review' => $this->_getReviewHtml()
                    ),
                    'allow_submit' => $this->_isSubmitAllowed()
                );
            };
        } else {
            $this->_forward('noRoute');
            return;
        }

        $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($result));
    }
}
