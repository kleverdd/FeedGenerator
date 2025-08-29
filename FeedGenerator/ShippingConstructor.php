<?php
namespace FeedGenerator;

class ShippingConstructor {
    private $shippingMethodArray;
    
    function __construct(DBConnector $dbConnector){
        $this->shippingMethodArray = array();
        $shippingResults = $dbConnector->fetchAllShippingMethods();
        foreach ($shippingResults as $shippingMethod) {
            $shippingPrice = $shippingMethod['price' . $shippingMethod['currencyID']];
            $freeShippingAfter = $shippingMethod['IntSfrom'];
            if ($shippingPrice == 0) {
                $shippingName = 'FREE-' . $shippingMethod['isocode'];
            } else {
                $shippingName = $shippingMethod['DeliverySpeed'];
            }
            
            if (!($shippingPrice == 0 && $shippingMethod['DeliverySpeed'] == '')) {
                $newObject = new ShippingMethod(
                    $shippingMethod['isocode'],
                    $shippingMethod['code'],
                    $shippingName,
                    $shippingMethod['DeliverySpeed'],
                    $shippingPrice,
                    $freeShippingAfter
                );
                if (!in_array($newObject ,$this->shippingMethodArray)) {
                    $this->shippingMethodArray[] = $newObject;
                } 
            }
            
        }
    }
    
    function fetchAllShippingByCountryCode(string $countryCode) : array {
        $result = [];
        foreach($this->shippingMethodArray as $shippingMethod){
            if ($shippingMethod->getCountryCode() == $countryCode) {
                $result[] = $shippingMethod;
            }
        }
        return $result;
    }
    
    function fetchPaidShippingByCountryCode(string $countryCode) : array {
        $result = [];
        foreach($this->shippingMethodArray as $shippingMethod){
            if ($shippingMethod->getCountryCode() == $countryCode && $shippingMethod->getPrice() > 0) {
                $result[] = $shippingMethod;
            }
        }
        return $result;
    }
    
    function fetchFreeShippingByCountryCode(string $countryCode) : array {
        $result = [];
        foreach($this->shippingMethodArray as $shippingMethod){
            if ($shippingMethod->getCountryCode() == $countryCode && $shippingMethod->getPrice() == 0) {
                $result[] = $shippingMethod;
            }
        }
        return $result;
    }
}