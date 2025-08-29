<?php
namespace FeedGenerator;
/**
 * Wrapper for functions that must be performed on all product fields, regardless of destination
 * */
class ProductParser {
    private $parserColours;
    private $imgBase;

    function __construct($imgBase = "someAddress") {
        $this->parserColours = array('red', 'yellow', 'pink', 'green', 'purple', 'orange', 'blue', 'white', 'black', 'brown', 'grey', 'navy', 'pink', 'beige', 'khaki', 'cream', 'green', 'orange', 'silver', 'lilac', 'gold', 'denim');
        $this->imgBase = $imgBase;
    }
    
    function parseProducts(array $products) : array {
        foreach ($products as $key => $product) {
            $products[$key] = $this->setCleanURL($product);
            $products[$key] = $this->addImagePath($product);
            $products[$key] = $this->priceFormat($product);
            $products[$key] = $this->retrieveColour($product);
            $products[$key] = $this->setBrand($product);
            $products[$key] = $this->stripFields($product);
        }
        return $products;
    }

    function setCleanURL(array &$product) : array {
        $product['url'] = $this->getCronjobProductUrl($product['ProductID'], $product['ProductName']);
        return $product;
    }
    
    function addImagePath(array &$product) : array {
        $product['Thumbnail'] = $this->imgBase . "/" . encodeSpace($product['Thumbnail']);
        $product['Mainimage'] = $this->imgBase . "/" . encodeSpace($product['Mainimage']);
        $product['Extrafield1'] = $this->imgBase . "/"  . encodeSpace($product['Extrafield1']);
        $product['Extrafield11'] = $this->imgBase . "/"  . encodeSpace($product['Extrafield11']);
        return $product;
    }

    function priceFormat(array &$product) : array {
        if ($product['Rrp1'] > 0 || $product['Rrp1'] != '') {
            $product['Price'] = number_format($product['Rrp1'], 2, '.', '');
            $product['SalePrice'] = number_format($product['Price1'], 2, '.', '');
        } else {
            $product['Price'] = number_format($product['Price1'], 2, '.', '');
        }
        return $product;
    }

    function retrieveColour(array &$product) : array {
        global $parserColours;
        if (strip($product['Colour'])) {
            $colour = strtolower(strip($product['Colour']));
        } else {
            $colour = retrieveColor(strip(strtolower($product['Description'])), $parserColours);
        }
        $product['Colour'] = $colour;
        return $product;
    }

//Most of actual checks for brand are done on the feedProducts cronjob. 
//This function just lumps both into one field for easier reference
    function setBrand(array &$product) : array {
        if (isset($product['Showbrand'])) {
            $product['Brand'] = $product['Showbrand'];
        }
        return $product;
    }
    
    function stripFields(array &$product) : array {
        $product['ProductName'] = strip($product['ProductName']);
        $product['Description'] = strip($product['Description']);
        $product['Category'] = strip($product['Category']);
        return $product;
    }

    private function getCronjobProductUrl($ProductID, $ProductName)
    {
        return 'someurl';
    }

}
