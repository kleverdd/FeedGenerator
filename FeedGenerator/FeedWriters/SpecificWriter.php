<?php
namespace FeedGenerator\FeedWriters;

use FeedGenerator\DBConnector;
use FeedGenerator\RegionConfig;

class SpecficWriter extends FeedWriterAbstract {
    
    function __construct(
        DBConnector $dbConnector,
        RegionConfig $regionConfig,
        array $regions = ["UK", "COM"],
        array $excludedProducts = []
    ) {
        parent::__construct($dbConnector, $regionConfig, $regions, $excludedProducts);
        
        foreach($this->regions as $region){
            switch ($region->getRegionCode()) {
                case "UK": 
                    $filename = $this->basePath . 'domesticFeed.txt';
                    break;
                case "COM":
                    $filename = $this->basePath . 'internationalFeed.txt';
                    break;
                case "US":
                case "IE":
                case "AU":
                case "DK":
                case "SE":
                case "NONEU":
                    $filename = $this->basePath . 'internationalFeed_' . $region->getRegionCode() . '.txt';
                    break;
            }
            
            $this->filesArray[$region->getRegionCode()] = fopen($filename , "w+");
            if (!$this->filesArray[$region->getRegionCode()]) {
                $this->sendGraylog("Could not open $filename", error_get_last(), "ERROR");
            }
        }
    }
    
    function writeHeaders() : void{
        foreach($this->regions as $region) {
            $headXML = "some header data, either XML or CSV";

            fwrite ($this->filesArray[$region->getRegionCode()],$headXML);
        }
    }

    function writeProducts(array $products) : void {
        /**
		Proprietary stuff here, nothing to see.
		*/
    }
    
    function closeFile() : void{
        foreach($this->regions as $region) {
            $footerXML = "Some footer data";

            fwrite($this->filesArray[$region->getRegionCode()],$footerXML);
            fclose($this->filesArray[$region->getRegionCode()]);
        }
    }
    
    function sendGraylog(string $logName = "", array $parameters = array(), string $level = "INFO") : void{
        $glParams = $parameters;
        parent::sendGraylog('Feeds generated successfully', $glParams, $level);
    }
}
