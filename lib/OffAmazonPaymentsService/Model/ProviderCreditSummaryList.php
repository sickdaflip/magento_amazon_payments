<?php

/*******************************************************************************
 *  Copyright 2011 Amazon.com, Inc. or its affiliates. All Rights Reserved.
 *  Licensed under the Apache License, Version 2.0 (the "License");
 *
 *  You may not use this file except in compliance with the License.
 *  You may obtain a copy of the License at:
 *  http://aws.amazon.com/apache2.0
 *  This file is distributed on an "AS IS" BASIS, WITHOUT WARRANTIES OR
 *  CONDITIONS OF ANY KIND, either express or implied. See the License
 *  for the
 *  specific language governing permissions and limitations under the
 *  License.
 * *****************************************************************************
 */


/**
 *  @see OffAmazonPaymentsService_Model
 */



/**
 * OffAmazonPaymentsService_Model_ProviderCreditSummaryList
 *
 * Properties:
 * <ul>
 *
 * <li>member: OffAmazonPaymentsService_Model_ProviderCreditSummary</li>
 *
 * </ul>
 */
class OffAmazonPaymentsService_Model_ProviderCreditSummaryList extends OffAmazonPaymentsService_Model
{
    
    /**
     * Construct new OffAmazonPaymentsService_Model_ProviderCreditSummaryList
     *
     * @param mixed $data DOMElement or Associative Array to construct from.
     *
     * Valid properties:
     * <ul>
     *
     * <li>member: OffAmazonPaymentsService_Model_ProviderCreditSummary</li>
     *
     * </ul>
     */
    public function __construct($data = null)
    {
        $this->_fields = array(
            'member' => array(
                'FieldValue' => array(),
                'FieldType' => array(
                    'OffAmazonPaymentsService_Model_ProviderCreditSummary'
                )
            )
        );
        parent::__construct($data);
    }
    
    /**
     * Gets the value of the member.
     *
     * @return array of ProviderCreditSummary member
     */
    public function getmember()
    {
        return $this->_fields['member']['FieldValue'];
    }
    
    /**
     * Sets the value of the member.
     *
     * @param mixed ProviderCreditSummary or an array of ProviderCreditSummary member
     * @return this instance
     */
    public function setmember($member)
    {
        if (!$this->_isNumericArray($member)) {
            $member = array(
                $member
            );
        }
        $this->_fields['member']['FieldValue'] = $member;
        return $this;
    }
    
    
    /**
     * Sets single or multiple values of member list via variable number of arguments.
     * For example, to set the list with two elements, simply pass two values as arguments to this function
     * <code>withmember($member1, $member2)</code>
     *
     * @param ProviderCreditSummary  $providerCreditSummaryArgs one or more member
     * @return OffAmazonPaymentsService_Model_ProviderCreditSummaryList  instance
     */
    public function withmember($providerCreditSummaryArgs)
    {
        foreach (func_get_args() as $member) {
            $this->_fields['member']['FieldValue'][] = $member;
        }
        return $this;
    }
    
    
    
    /**
     * Checks if member list is non-empty
     *
     * @return bool true if member list is non-empty
     */
    public function isSetmember()
    {
        return count($this->_fields['member']['FieldValue']) > 0;
    }
    
}