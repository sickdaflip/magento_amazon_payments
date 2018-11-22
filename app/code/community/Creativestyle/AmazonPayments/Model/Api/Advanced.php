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
class Creativestyle_AmazonPayments_Model_Api_Advanced extends Creativestyle_AmazonPayments_Model_Api_Abstract
{
    /**
     * @var array
     */
    protected $_api = array();

    /**
     * Returns Amazon Pay API client instance
     *
     * @return OffAmazonPaymentsService_Client
     */
    protected function _getApi()
    {
        if (!isset($this->_api[$this->_store])) {
            $this->_api[$this->_store] = new OffAmazonPaymentsService_Client(
                $this->_getConfig()->getApiConnectionParams($this->_store)
            );
        }

        return $this->_api[$this->_store];
    }

    /**
     * @param string $orderReferenceId
     * @param string|null $accessToken
     * @return OffAmazonPaymentsService_Model_OrderReferenceDetails|null
     */
    public function getOrderReferenceDetails($orderReferenceId, $accessToken = null)
    {
        $request = new OffAmazonPaymentsService_Model_GetOrderReferenceDetailsRequest(
            array(
                'SellerId' => $this->getMerchantId(),
                'AmazonOrderReferenceId' => $orderReferenceId,
                'AddressConsentToken' => $accessToken
            )
        );

        $response = $this->_getApi()->getOrderReferenceDetails($request);
        if ($response->isSetGetOrderReferenceDetailsResult()) {
            $result = $response->getGetOrderReferenceDetailsResult();
            if ($result->isSetOrderReferenceDetails()) {
                return $result->getOrderReferenceDetails();
            }
        }

        return null;
    }

    /**
     * @param float $orderAmount
     * @param string $orderCurrency
     * @param string $orderReferenceId
     * @param string|null $magentoOrderId
     * @param string|null $storeName
     * @return null|OffAmazonPaymentsService_Model_OrderReferenceDetails
     */
    public function setOrderReferenceDetails(
        $orderAmount,
        $orderCurrency,
        $orderReferenceId,
        $magentoOrderId = null,
        $storeName = null
    ) {
        $request = new OffAmazonPaymentsService_Model_SetOrderReferenceDetailsRequest(
            array(
                'SellerId' => $this->getMerchantId(),
                'AmazonOrderReferenceId' => $orderReferenceId,
                'OrderReferenceAttributes' => array(
                    'PlatformId' => 'AIOVPYYF70KB5',
                    'OrderTotal' => array(
                        'Amount' => $orderAmount,
                        'CurrencyCode' => $orderCurrency
                    ),
                    'SellerOrderAttributes' => array(
                        'SellerOrderId' => $magentoOrderId ? $magentoOrderId : null,
                        'StoreName' => $storeName ? $storeName : null,
                        'CustomInformation' => 'Created by Creativestyle_AmazonPayments/'
                            . (string)Mage::getConfig()->getNode('modules/Creativestyle_AmazonPayments/version')
                            . ' (Platform=Magento/' . trim(Mage::getVersion()) . ')'
                    )
                )
            )
        );

        $response = $this->_getApi()->setOrderReferenceDetails($request);
        if ($response->isSetSetOrderReferenceDetailsResult()) {
            $result = $response->getSetOrderReferenceDetailsResult();
            if ($result->isSetOrderReferenceDetails()) {
                return $result->getOrderReferenceDetails();
            }
        }

        return null;
    }

    public function confirmOrderReference($orderReferenceId)
    {
        $request = new OffAmazonPaymentsService_Model_ConfirmOrderReferenceRequest(
            array(
                'SellerId' => $this->getMerchantId(),
                'AmazonOrderReferenceId' => $orderReferenceId
            )
        );
        $response = $this->_getApi()->confirmOrderReference($request);
        return $response;
    }

    public function cancelOrderReference($orderReferenceId)
    {
        $request = new OffAmazonPaymentsService_Model_CancelOrderReferenceRequest(
            array(
                'SellerId' => $this->getMerchantId(),
                'AmazonOrderReferenceId' => $orderReferenceId
            )
        );
        $response = $this->_getApi()->cancelOrderReference($request);
        return $response;
    }

    /**
     * @param string $orderReferenceId
     * @param string|null $closureReason
     * @return OffAmazonPaymentsService_Model_CloseOrderReferenceResponse
     */
    public function closeOrderReference($orderReferenceId, $closureReason = null)
    {
        $request = new OffAmazonPaymentsService_Model_CloseOrderReferenceRequest(
            array(
                'SellerId' => $this->getMerchantId(),
                'AmazonOrderReferenceId' => $orderReferenceId,
            )
        );
        if (null !== $closureReason) {
            $request->setClosureReason($closureReason);
        }

        $response = $this->_getApi()->closeOrderReference($request);
        return $response;
    }

    /**
     * @param float $authorizationAmount
     * @param string $authorizationCurrency
     * @param string $authorizationReferenceId
     * @param string $orderReferenceId
     * @param int|null $transactionTimeout
     * @param bool $captureNow
     * @param string|null $sellerAuthorizationNote
     * @param string|null $softDescriptor
     * @return OffAmazonPaymentsService_Model_AuthorizationDetails|null
     */
    public function authorize(
        $authorizationAmount,
        $authorizationCurrency,
        $authorizationReferenceId,
        $orderReferenceId,
        $transactionTimeout = null,
        $captureNow = false,
        $sellerAuthorizationNote = null,
        $softDescriptor = null
    ) {
        $request = new OffAmazonPaymentsService_Model_AuthorizeRequest(
            array(
                'SellerId' => $this->getMerchantId(),
                'AmazonOrderReferenceId' => $orderReferenceId,
                'AuthorizationReferenceId' => $authorizationReferenceId,
                'AuthorizationAmount' => array(
                    'Amount' => $authorizationAmount,
                    'CurrencyCode' => $authorizationCurrency
                ),
                'CaptureNow' => $captureNow,
                'SoftDescriptor' => $softDescriptor
            )
        );

        if (null !== $sellerAuthorizationNote) {
            $request->setSellerAuthorizationNote($sellerAuthorizationNote);
        }

        if (null !== $transactionTimeout) {
            $request->setTransactionTimeout($transactionTimeout);
        }

        $response = $this->_getApi()->authorize($request);
        if ($response->isSetAuthorizeResult()) {
            $result = $response->getAuthorizeResult();
            if ($result->isSetAuthorizationDetails()) {
                $resp = $result->getAuthorizationDetails();
                return $resp;
            }
        }

        return null;
    }

    /**
     * @param string $authorizationId
     * @return null|OffAmazonPaymentsService_Model_AuthorizationDetails
     */
    public function getAuthorizationDetails($authorizationId)
    {
        $request = new OffAmazonPaymentsService_Model_GetAuthorizationDetailsRequest(
            array(
                'SellerId' => $this->getMerchantId(),
                'AmazonAuthorizationId' => $authorizationId
            )
        );

        $response = $this->_getApi()->getAuthorizationDetails($request);
        if ($response->isSetGetAuthorizationDetailsResult()) {
            $result = $response->getGetAuthorizationDetailsResult();
            if ($result->isSetAuthorizationDetails()) {
                return $result->getAuthorizationDetails();
            }
        }

        return null;
    }

    /**
     * @param float $captureAmount
     * @param string $captureCurrency
     * @param string $captureReferenceId
     * @param string $authParentId
     * @param string|null $sellerCaptureNote
     * @param string|null $softDescriptor
     * @return null|OffAmazonPaymentsService_Model_CaptureDetails
     */
    public function capture(
        $captureAmount,
        $captureCurrency,
        $captureReferenceId,
        $authParentId,
        $sellerCaptureNote = null,
        $softDescriptor = null
    ) {
        $request = new OffAmazonPaymentsService_Model_CaptureRequest(
            array(
                'SellerId' => $this->getMerchantId(),
                'AmazonAuthorizationId' => $authParentId,
                'CaptureReferenceId' => $captureReferenceId,
                'CaptureAmount' => array(
                    'Amount' => $captureAmount,
                    'CurrencyCode' => $captureCurrency
                ),
                'SoftDescriptor' => $softDescriptor
            )
        );
        if (null !== $sellerCaptureNote) {
            $request->setSellerCaptureNote($sellerCaptureNote);
        }

        $response = $this->_getApi()->capture($request);
        if ($response->isSetCaptureResult()) {
            $result = $response->getCaptureResult();
            if ($result->isSetCaptureDetails()) {
                return $result->getCaptureDetails();
            }
        }

        return null;
    }

    public function getCaptureDetails($captureId)
    {
        $request = new OffAmazonPaymentsService_Model_GetCaptureDetailsRequest(
            array(
                'SellerId' => $this->getMerchantId(),
                'AmazonCaptureId' => $captureId
            )
        );

        $response = $this->_getApi()->getCaptureDetails($request);
        if ($response->isSetGetCaptureDetailsResult()) {
            /** @var OffAmazonPaymentsService_Model_GetCaptureDetailsResult $result */
            $result = $response->getGetCaptureDetailsResult();
            if ($result->isSetCaptureDetails()) {
                return $result->getCaptureDetails();
            }
        }

        return null;
    }

    /**
     * @param float $refundAmount
     * @param string $refundCurrency
     * @param string $refundReferenceId
     * @param string $captureParentId
     * @param string|null $sellerRefundNote
     * @return null|OffAmazonPaymentsService_Model_RefundDetails
     */
    public function refund(
        $refundAmount,
        $refundCurrency,
        $refundReferenceId,
        $captureParentId,
        $sellerRefundNote = null
    ) {
        $request = new OffAmazonPaymentsService_Model_RefundRequest(
            array(
                'SellerId' => $this->getMerchantId(),
                'AmazonCaptureId' => $captureParentId,
                'RefundReferenceId' => $refundReferenceId,
                'RefundAmount' => array(
                    'Amount' => $refundAmount,
                    'CurrencyCode' => $refundCurrency
                )
            )
        );
        if (null !== $sellerRefundNote) {
            $request->setSellerRefundNote($sellerRefundNote);
        }

        $response = $this->_getApi()->refund($request);
        if ($response->isSetRefundResult()) {
            $result = $response->getRefundResult();
            if ($result->isSetRefundDetails()) {
                return $result->getRefundDetails();
            }
        }

        return null;
    }

    /**
     * @param string $refundId
     * @return null|OffAmazonPaymentsService_Model_RefundDetails
     */
    public function getRefundDetails($refundId)
    {
        $request = new OffAmazonPaymentsService_Model_GetRefundDetailsRequest(
            array(
                'SellerId' => $this->getMerchantId(),
                'AmazonRefundId' => $refundId
            )
        );

        $response = $this->_getApi()->getRefundDetails($request);
        if ($response->isSetGetRefundDetailsResult()) {
            $result = $response->getGetRefundDetailsResult();
            if ($result->isSetRefundDetails()) {
                return $result->getRefundDetails();
            }
        }

        return null;
    }
}
