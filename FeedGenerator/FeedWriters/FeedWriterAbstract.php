<?php
namespace FeedGenerator\FeedWriters;

use FeedGenerator\DBConnector;
use FeedGenerator\RegionConfig;

/**
 * Abstract class for things like consistent Graylogging
 */
abstract class FeedWriterAbstract {
    protected $dbConnector;
    protected $regionConfig;
    
    protected $regions;
    protected $facility;
    protected $oneSizeAccessories;
    protected $errorLog;
    protected $excludedProducts;
    protected $startTime;
    protected $filesArray;
    protected $productsParsed;

    public function __construct(
        DBConnector $dbConnector,
        RegionConfig $regionalConfig,
        array $regions,
        array $excludedProducts = []
    ) {
        $this->dbConnector = $dbConnector;
        $this->regionConfig = $regionalConfig;
        $this->regions = $this->regionConfig->fetchGivenRegions($regions);
        $this->facility = "MMW-" . strtoupper($dbConnector->currentServer) . "-FEEDGENERATOR";
        $this->oneSizeAccessories = ["caps and hats","socks","bags / holdalls","belts","accessories","jewellery","sunglasses","watches"];
        $this->errorLog = [];
        $this->excludedProducts = $excludedProducts;
        $this->basePath = __DIR__ . "/../../../feeds/";
    }
    
    function createFeed(?array $products) : void {
        $this->startTime = microtime(TRUE);
        $this->writeHeaders();
        $this->writeProducts($products);
        $this->closeFile();
        $this->sendGraylog();
    }
         
    function closeFile() : void{
        foreach ($this->regions as $region) {
            fclose ($this->filesArray[$region->getRegionCode()]);
        }
    }
    
    public function sendGraylog(string $logName = "", array $parameters = array(), string $level = 'INFO') : void {
        $parameters['productsParsed'] = "Products parsed: {$this->productsParsed}";
        $parameters['time'] = "Time elapsed: " . (microtime(TRUE) - $this->startTime);
        $this->sendToLog($logName, json_encode($parameters), $this->facility, __FILE__, $level);
    }


    /**
     * This service function resided outside of this codebase, hence a stub
     *
     * @param $logName
     * @param $params
     * @param $facility
     * @param $filename
     * @param $level
     * @return void
     */
    public function sendToLog($logName, $params, $facility, $filename, $level) {

    }
}