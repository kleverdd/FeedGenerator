<?php
namespace FeedGenerator;

/**
 * Factory for region objects.
 * Uses DBConnector to fetch exchange rates and exclusion lists
 */

class RegionConfig {
    private $dbConnector;
    private $regionArray;
    
    public function __construct(DBConnector $dbConnector) {
        $this->dbConnector = $dbConnector;
        $intermediateExchangeRates = $this->dbConnector->fetchExchangeRates();
        
        $this->regionArray = array();
        $this->regionArray["UK"] = new RegionObject("UK", $this->dbConnector->ukBase, "GBP", 1);
        $this->regionArray["AU"] = new RegionObject("AU", $this->dbConnector->comBase . "au/", "AUD", $intermediateExchangeRates["AUD"]);
        $this->regionArray["US"] = new RegionObject("US", $this->dbConnector->comBase . "us/", "USD", $intermediateExchangeRates["USD"]);
        $this->regionArray["IE"] = new RegionObject("IE", $this->dbConnector->comBase . "ie/", "EUR", $intermediateExchangeRates["EUR"]);
        $this->regionArray["DK"] = new RegionObject("DK", $this->dbConnector->comBase . "dk/", "DKK", $intermediateExchangeRates["DKK"] );
        $this->regionArray["SE"] = new RegionObject("SE", $this->dbConnector->comBase . "se/", "SEK", $intermediateExchangeRates["SEK"] );

        $gbpRegions = ["COM", "NONEU", "CH", "HU", "PL", "AT"];
        foreach ($gbpRegions as $region) {
            $this->regionArray[$region] = new RegionObject($region, $this->dbConnector->comBase, "GBP", 1 );
        }

        $euRegions = ["EU", "IT", "BE", "PT", "MT", "LT", "GR", "FR", "ES", "DE", "NL", "FI"];
        foreach ($euRegions as $region) {
            $this->regionArray[$region] = new RegionObject($region, $this->dbConnector->comBase, "EUR", $intermediateExchangeRates["EUR"] );
        }
        
        $this->regionArray["NZ"] = new RegionObject("NZ", $this->dbConnector->comBase, "NZD", $intermediateExchangeRates["NZD"] );     
        $this->regionArray["NO"] = new RegionObject("NO", $this->dbConnector->comBase, "NOK", $intermediateExchangeRates["NOK"] );
        $this->regionArray["CA"] = new RegionObject("CA", $this->dbConnector->comBase, "CAD", $intermediateExchangeRates["CAD"] );
        $this->regionArray["SG"] = new RegionObject("SG", $this->dbConnector->comBase, "SGD", $intermediateExchangeRates["SGD"] );
               
        $this->setExclusionLists();
    }
    
    
    function setExclusionLists() : void{
        $rawData = $this->dbConnector->getExcludeDataConfig();
        $excludeDataProducts = [];
        $excludeDataBrands = [];
        foreach ($rawData as $row) {
            $excludeDataProducts[$row["regionName"]] = explode(",", $row["feedExcludedProducts"]);
            $excludeDataBrands[$row["regionName"]] = explode(",", $row["excludedBrands"]);
        }
        
        foreach ($this->regionArray as $regionCode => $region) {
            $region->setBrandExcludeList($excludeDataBrands[$regionCode]);
            $region->setProductExcludeList($excludeDataProducts[$regionCode]);
        }
    }
    
    function fetchRegionByCode(string $regionCode) : RegionObject {
        return $this->regionArray[$regionCode];
    }
    
    function fetchGivenRegions(array $givenRegionArray) : array {
        foreach($givenRegionArray as $region){
            $returnArray[$region] = $this->fetchRegionByCode($region);
        }
        return $returnArray;
    }
}