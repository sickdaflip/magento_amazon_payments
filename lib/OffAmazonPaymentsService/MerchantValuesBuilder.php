<?php

/*******************************************************************************
 *  Copyright 2013 Amazon.com, Inc. or its affiliates. All Rights Reserved.
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



class OffAmazonPaymentsService_MerchantValuesBuilder
{
    private $_config;

    private $_regionSpecificProperties;

    /**
     * Provide a static function to access the constructor so
     * that a fluent interface can be used to build the merchant
     * values object
     *
     * @param config to use, default to null
     *
     * @return new instance of OffAmazonPaymentsService_MerchantValuesBuilder
     */
    public static function create($config = null) {
        return new OffAmazonPaymentsService_MerchantValuesBuilder($config);
    }

    /**
     * Create a new instance, using global configuraton
     * values if no configuration is define
     *
     * @param config array of property values
     *
     */
    private function __construct($config = null) {
        
        if (isset($config)) {
            $this->_config = $config;
        }

        $this->_regionSpecificProperties = new OffAmazonPaymentsService_RegionSpecificProperties();
    }
    
    /**
     * Setup the region specific properties file to use for the
     * merchant values class
     *
     * @param OffAmazonPaymentsService_RegionSpecificProperties instance to use
     *
     * @return this
     */
    public function withRegionSpecificProperties(
            $regionSpecificProperties)
    {
        $this->_regionSpecificProperties = $regionSpecificProperties;
        return $this;
    }

    /**
     * Create a new instance of the merchant values object
     * with the configured properties
     *
     * @return OffAmazonPaymentsService_MerchantValues
     */
    public function build() {
        return new OffAmazonPaymentsService_MerchantValues(
            $this->_config, 
            $this->_regionSpecificProperties
        );
    }
}