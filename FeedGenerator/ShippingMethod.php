<?php
namespace FeedGenerator;

class ShippingMethod{
    private $countryCode;
    private $currencyCode;
    private $shippingName;
    private $methodLength;
    private $price;
    private $freeShippingAfter;
    
    public function __construct(string $countryCode, string $currencyCode, $shippingName, $methodLength, $price, $freeShippingAfter){
        $this->countryCode = $countryCode;
        $this->currencyCode = $currencyCode;
        $this->shippingName = $shippingName;
        $this->methodLength = $methodLength;
        $this->price = $price;
        $this->freeShippingAfter = $freeShippingAfter;
    }
    
    function getCountryCode() {
        return $this->countryCode;
    }

    function setCountryCode($countryCode) {
        $this->countryCode = $countryCode;
    }
    
    function getPrice() {
        return $this->price;
    }
    
    function getMethodLength() {
        return $this->methodLength;
    }

    function getFreeShippingAfter() {
        return $this->freeShippingAfter;
    }
}
