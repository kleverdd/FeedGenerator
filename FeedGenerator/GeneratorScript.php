<?php
namespace FeedGenerator;

require_once __DIR__ . '/DBConnector.php';
require_once __DIR__ . '/ProductParser.php';
require_once __DIR__ . '/RegionConfig.php';
use FeedGenerator\FeedWriters\SpecificWriter;


$timeStart = microtime(TRUE);

$dbInstance = new DBConnector();
$parserInstance = new ProductParser(); 
$regionConfig = new RegionConfig($dbInstance);

$result = $dbInstance->fetchProducts();
$result = $parserInstance->parseProducts($result);

echo "All products parsed in ". (microtime(TRUE) - $timeStart) ."\n";

$specificParser = new SpecificWriter($dbInstance, $regionConfig, array("US"));
$specificParser->createFeed($result);


$messageArray = [];
$timeEnd = microtime(TRUE);
$time = round($timeEnd - $timeStart, 6);
$timeMessage = "Overall Total Time: " . $time;
$messageArray[] = $timeMessage;

$memory = "Overall memory consumed: " . memory_get_peak_usage();
$messageArray[] = $memory;

/**
 * Set of debug values for Graylog
 */
$glValues = [];
$glValues["memoryConsumed"] = memory_get_peak_usage();
$glValues["timeTaken"] = $time;
$glValues["file"] = __FILE__;
$glValues["parsedProducts"] = count($result);
//$glValues["facility"] = generateFacilityName($dbInstance->getCurrentServer(), "FeedGenerator");
//logGLShortMessage("Finished the feed generation", $glValues);