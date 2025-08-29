<?php
namespace FeedGenerator;

class RegionObject {
    private $regionCode;
    private $regionBase;
    private $currency;
    private $exchangeRate;
    private $shippingMethod;
    private $shippingRate;
    private $freeShippingAfter;
    private $brandExcludeList;
    private $productExcludeList;
    
    public function __construct(
            $regionCode, 
            $regionBase, 
            $currency, 
            $exchangeRate) 
    {
        $this->regionCode = $regionCode;
        $this->regionBase = $regionBase;
        $this->currency = $currency;
        $this->exchangeRate = $exchangeRate;
    }
    
    function setShippingSettings($shippingMethod, $shippingRate = 0, $freeShippingAfter = 0){
        $this->setShippingMethod($shippingMethod);
        $this->setShippingRate($shippingRate);
        $this->setFreeShippingAfter($freeShippingAfter);
    }
    
    /**
     * Checks if any form of a given brand is in the exclusion list
     * 
     * @param type $brand
     * @return boolean true if 
     */
    function checkBrandExclusion($givenBrand) {
        $excludeList = $this->getBrandExcludeList();
        if (!empty($excludeList)) {
            foreach ($excludeList as $brand) {
                if (!empty($brand) && strpos(strtolower($givenBrand), strtolower($brand)) !== false) {
                   return true;
                }
            }
        }
        return false;
    }
    
    /**
     * Checks if a given product is in the exclusion list
     * 
     * @param string $givenProduct - productID
     * @return boolean true if 
     */
    function checkProductExclusion($givenProduct) {
        $productList = $this->getProductExcludeList();
        if (!empty($productList)) {
            if (in_array($givenProduct, $productList)) {
                return true;
            }
        }
        return false;
    }
    
    function getRegionBase() {
        return $this->regionBase;
    }

    function getCurrency() {
        return $this->currency;
    }

    function getExchangeRate() {
        return $this->exchangeRate;
    }
      
    function getRegionCode() {
        return $this->regionCode;
    }

    function getShippingMethod() {
        return $this->shippingMethod;
    }

    function getShippingRate() {
        return $this->shippingRate;
    }

    function getFreeShippingAfter() {
        return $this->freeShippingAfter;
    }

    function setShippingMethod($shippingMethod) {
        $this->shippingMethod = $shippingMethod;
    }

    function setShippingRate($shippingRate) {
        $this->shippingRate = $shippingRate;
    }

    function setFreeShippingAfter($freeShippingAfter) {
        $this->freeShippingAfter = $freeShippingAfter;
    }
    
    function getBrandExcludeList(){
        return $this->brandExcludeList;
    }
        
    function setBrandExcludeList($brandExcludeList){
        $this->brandExcludeList = $brandExcludeList;
    }
    
    function getProductExcludeList(){
        return $this->productExcludeList;
    }
        
    function setProductExcludeList($productExcludeList){
        $this->productExcludeList = $productExcludeList;
    }
}