<?php
namespace FeedGenerator;
/**
 * Abstraction class to enable use of db connector we deem useful.
 * 
 * At the time of development, it was *REDACTED*
 */
class DBConnector {
    private $dbInstance;
    public $ukBase;
    public $comBase;
    public $imgBase;
    public $currentServer;

    function __construct(
        string $dbInstance = "dbInstance",
        string $ukBaseSecure = "https://www.example.co.uk/",
        string $DotComBaseSecure = "https://www.example.com/",
        string $imgBaseSecure = "https://images.example.com/",
        string $currentServer = "unknown",
        string $transport = "http"
    ) {
        $this->dbInstance = $dbInstance;
        $GLOBALS['transport'] = $transport;
        $this->ukBase = $ukBaseSecure;
        $this->comBase = $DotComBaseSecure;
        $this->imgBase = $imgBaseSecure;
        $this->currentServer = $currentServer;
    }

    function fetchDbResults(string $query, array $parameters = []) : array {
        $result = $this->dbInstance->sql($query, $parameters);
        return $result;
    }
    
    function fetchProducts() : array {
        $productSql = "SELECT * FROM feedProductTable LEFT JOIN 
            (SELECT ProductID, 
                SUM(Onlinestock) AS stockQty, 
                COUNT(SizeTag) AS itemCount
                FROM feedProductTable 
                GROUP BY ProductID 
                ORDER BY ProductID DESC) AS derived 
            ON feedProductTable.ProductID = derived.ProductID LIMIT 10000
            ";
        return $this->fetchDbResults($productSql);
    }

    function fetchBestSellerProducts() : array {
        $timeLimit = 3;
        $timeDate = date('YmdHis', strtotime("-" . $timeLimit . " days"));

        $bestSellerProductSql = "SELECT baseProductTable.productID,
            SUM(qty) as totalqty 
            FROM baseOrdersTable, extraOrdersTable, baseProductTable_tree, baseProductTable 
            WHERE (baseProductTable.productID > 1 AND baseProductTable.visible =  'Y') 
                AND (baseProductTable.accTypes LIKE  '%;1;%' OR baseProductTable.accTypes LIKE  '%;0;%')
              AND baseOrdersTable.orderID = extraOrdersTable.orderID 
              AND baseProductTable.productID = baseOrdersTable.productID 
              AND baseProductTable_tree.productID = baseProductTable.productID 
              AND extraOrdersTable.status = 6 
              AND extraOrdersTable.datetime >=  '" . $timeDate . "' 
              GROUP BY baseProductTable.productID 
              ORDER BY totalqty 
              DESC LIMIT 100";

        $bestSellerProductResult = $this->fetchDbResults($bestSellerProductSql);
        $bestSellerProducts = array();
        foreach ($bestSellerProductResult as $bestSellerProductsRow) {
            $bestSellerProductCount = count($bestSellerProducts);
            if ($bestSellerProductCount < 100) {
                $bestSellerProducts[] = $bestSellerProductsRow['productID'];
            }
        }
        return $bestSellerProducts;
    }
    
    function fetchExchangeRates() : array {
        $result = $this->fetchDbResults("SELECT code, exchangeRate FROM currencyTable");
        $output = array();
        foreach($result as $entry) {
            $output[$entry['code']] = $entry['exchangeRate'];
        }
        $output['GBP'] = 1;
        return $output;
    }
    
    /**
     * Fetching sizes for rakuten affiliates
     * Returns an array in format:
     * ProductID => comma-separated string of size names
     */
    function fetchAllSizes() : array {
        $strSQL = "SELECT ProductID, GROUP_CONCAT(SizeName ORDER BY SizeName DESC SEPARATOR ',') AS sizeString FROM feedProductTable GROUP BY ProductID ORDER By ProductID DESC";
        $result = $this->fetchDbResults($strSQL);
        $returnedArray = array();
        foreach ($result as $sizeOption) {
                $returnedArray[$sizeOption['ProductID']] = $sizeOption['sizeString'];
        }
 
        return $returnedArray;
    }
    
    /**
     * Fetching sizes for Modesens in the format
     * array [ProductID] => semicolon-separated string of size names with size system
     *
     */
    function fetchAllSizesModesens() : array {
        $strSQL = "SELECT ProductID, SizeName AS sizeString 
            FROM feedProductTable ORDER BY ProductID DESC";
        $result = $this->fetchDbResults($strSQL);
        $returnedArray = array();
        foreach ($result as $sizeOption) {
            $oldValue = $returnedArray[$sizeOption['ProductID']];
            $returnedArray[$sizeOption['ProductID']] = $oldValue . "UK:" . $sizeOption['sizeString'] . ";";
        }
        return $returnedArray;
    }
    
    /**
     * Fetching sizes for rakuten affiliates
     * @return array 
     */
    function fetchAdditionalAffiliateFields() : array {
        $strSQL = "SELECT productID, code, shortdescription, keywords 
            FROM baseProductTable 
            WHERE productID IN (SELECT ProductID FROM feedProductTable) 
            GROUP BY productID";
        $result = $this->fetchDbResults($strSQL);
        $resultSet = [];
        foreach($result as $product) {
            $resultSet[$product['productID']] = [
                'code'              => strip($product['code']),
                'shortdescription'  => strip($product['shortdescription']),
                'keywords'          => strip($product['keywords'])
            ];
        }
        return $resultSet;
    }
    
    /**
     * Given time of day, returns a list of items that went out of stock or no longer
     * satisfy the stock requirements.
     * 
     * @param string $timeOfDay "Noon" or "midnight", regardless of case
     * @param string $feedName Special case for FB feed - it needs to know if 
     *        amount of items is above a certain point.
     * @return array All items that DO NOT satisfy the conditions.
     *         returns ProductID, ExvalID
     */
    function getHourlyStockUpdate(string $timeOfDay, string $feedName = "") : array {
        $resultSet = $this->fetchStockDifference($timeOfDay);
        $returnArray = [];
        foreach ($resultSet as $product){
            if (is_null($product['NewStockQty'])) {
                $returnArray[] = array("ProductID" => $product["ProductID"], "ExvalID" => $product["ExvalID"]);
            }
        }

        return $returnArray;
    }
    
    function getHourlyFacebookStockUpdate() : array {
        $FBItems = $this->getHourlyStockUpdate("noon", "Facebook");
        $exvalArray = [];
        foreach ($FBItems as $item) {
            $exvalArray[] = $item['ExvalID'];
        }
        
        return $this->prepareFacebookStockItems($exvalArray);
    }
    
    /**
     * Before we can pass the items to the ProductParser that assembles items properly,
     * we need to set the products' fields to be called the same as in feedProductTable table.
     * Below function does that.
     * 
     * Need to add Google Taxonomy as well, since it's done in feedProductTable script
     */
    function prepareFacebookStockItems(array $FBProducts) : array{
        $FBSQL = "*Query hidden*";
        $FBSQL .= " AND ExvalID IN (". implode(",", $FBProducts) .")";
        $FBSQL .= " ORDER BY baseProductTable.productID DESC";
        $productInfo = $this->fetchDbResults($FBSQL);
        
        $googleTaxanomyArrays = $this->fetchGoogleTaxonomy();
        
        foreach($productInfo as &$product) {
            $productCategory = "";
            $productTitle = strtolower(strip($product['ProductName']));
            foreach($googleTaxanomyArrays as $googleTaxanomyArray){
                $keywordsArray = explode(",",$googleTaxanomyArray['keywords']);
                foreach($keywordsArray as $taxanomyKeywords){
                    if(strpos($productTitle,strtolower($taxanomyKeywords)) !== false){
                        $productCategory = $googleTaxanomyArray['categoryID'];
                        break 2;
                    }
                }
            }
            $product['GoogleTaxonomy'] = $productCategory;
        }
        
        return $productInfo;
    }
    
    function fetchStockDifference(string $timeOfDay) {
        if (strtolower($timeOfDay) == "noon") {
            $table = "feedProductTableNoonStock";
        } else if (strtolower($timeOfDay) == "midnight") {
            $table = "feedProductTableMidnightStock";
        }
        $query = "SELECT fpms.ProductID, fpms.ExvalID, fpms.StockQty AS OldStockQty, fpms.SizeQty AS OldSizeQty, OneSize, NewStockQty, NewSizeQty
            FROM $table AS fpms LEFT JOIN (
                SELECT ProductID, ExvalID, StockQty AS NewStockQty, SizeQty AS NewSizeQty FROM feedProductTable 
                INNER JOIN (
                    SELECT ProductID,SUM(Onlinestock) as StockQty, COUNT(Onlinestock) as SizeQty 
                    FROM feedProductTable GROUP BY ProductID
                )AS sizeSumInternal USING (ProductID)
            ) as fp
            ON (fpms.ExvalID = fp.ExvalID) ";
        return $this->fetchDbResults($query);
    }
    
    function fetchAllShippingMethods(): array {
        $shippingMethodsSQL = "SELECT *
            FROM shippingRatesTable
            INNER JOIN countryTable AS countries
                ON countries.countryID = shippingRatesTable.CountryID
            INNER JOIN currencyTable AS currencies
                ON currencies.currencyID = countries.currencyID;";
        $result = $this->fetchDbResults($shippingMethodsSQL);
        return $result;
    }
    
    function fetchMonetateStockLevels(): array {
        $sql = "WITH StockData AS (
                SELECT 
                    pf.ProductID,
                    SUM(pf.onlinestock) AS TotalStock,
                    SUM(ev.Stocksold) AS TotalSold
                FROM database.feedProductTable pf
                JOIN database.extraProductValues ev ON pf.productID = ev.productID
                GROUP BY pf.ProductID
            )
            SELECT 
                ProductID,
                CASE
                    WHEN ROUND(TotalStock / (TotalStock + TotalSold) * 100, 2) <= 14 THEN 'Lowstock'
                    WHEN ROUND(TotalStock / (TotalStock + TotalSold) * 100, 2) <= 49 THEN 'Mediumstock'
                    ELSE 'Highstock'
                END AS Stocklevel
            FROM StockData
            ORDER BY ProductID DESC;";
        $result = $this->fetchDbResults($sql);
        return $result;      
    }
    
    function getMonetateStockLevels(): array {
        $stockLevels = $this->fetchMonetateStockLevels();
        $returnArray = [];
        foreach ($stockLevels as $stockLevel) {
            $returnArray[$stockLevel['ProductID']] = $stockLevel['Stocklevel'];
        }
        return $returnArray;
    }
    
    function fetchProductReviews () : array {
        $reviewsSql = "SELECT reviewsTable.Review as reviewText, reviewsTable.productID 
                FROM reviewsTable, reviewsTable_shop 
                WHERE reviewsTable_shop.OrderID = reviewsTable.OrderID 
                    AND reviewsTable.ProductID IN (SELECT DISTINCT ProductID FROM feedProductTable) 
                    AND reviewsTable.Stars > 2 
                ORDER BY reviewsTable.ReviewID";
        $result = $this->fetchDbResults($reviewsSql);
        $returnArray = [];
        foreach ($result as $review) {
            $oldValue = '';
            if (!empty($returnArray[$review["productID"]])) {
                $oldValue = $returnArray[$review["productID"]];
            }
            $returnArray[$review["productID"]] = $oldValue . $review["reviewText"] . ";";
        }
        return $returnArray;
    }
    
    function fetchFitWriterProducts() {
        $inStockSql = "SELECT jev.productID FROM extraProductValues AS jev 
        INNER JOIN (SELECT productID, SUM(onlinestock) AS stockSum FROM extraProductValues AS jev2 GROUP BY productID ) AS jevDerived 
        ON jevDerived.productID = jev.productID
        WHERE stockSum > 0
        GROUP BY jev.productID
        ORDER BY jev.productID DESC";

        $prodSql = "SELECT baseProductTable.name, baseProductTable.productID, baseProductTable.colour, baseProductTable.category, baseProductTable.mainimage, extraProductValues.content15, extraProductValues.content, extraProductValues.barcode, brandTitleTable.showBrand, extraProductValues.onlinestock AS stock"
                . " FROM (baseProductTable LEFT JOIN extraProductValues ON baseProductTable.productID = extraProductValues.productID) LEFT JOIN brandTitleTable ON baseProductTable.brand = brandTitleTable.trackitBrand"
                . " WHERE baseProductTable.visible = 'Y'"
                . " AND extraProductValues.extraFieldID = 5"
                . " AND extraProductValues.content15 !=''"
                . " AND baseProductTable.productID IN ($inStockSql)"
                . " ORDER BY baseProductTable.productID DESC";

        $prodResults = $this->fetchDbResults($prodSql);
        return $prodResults;
    }
    
    /**
     * Fetches a size table from the sizes table
     * @return array [brand] => [[size] => [euSize]]
     */
    function fetchSizeTable () {
        $sizeTable = [];
        $sizes = $this->fetchDbResults("SELECT brand, size, euSize FROM eu_sizes ");
        foreach ($sizes as $row) {
            $sizeTable[strtolower($row["brand"])][$row["size"]] = $row["euSize"];
        }
        return $sizeTable;
    }

    /**
     * Fetch and format the google taxonomy table
     *
     * @return array|array[]
     */
    function fetchGoogleTaxonomy () {
        $googleTaxanomySql = "SELECT * FROM google_taxanomy";
        $googleTaxanomyResult = $this->fetchDbResults($googleTaxanomySql);
        $googleTaxanomyArrays = array_map(function($googleTaxanomy) {
            $searchWords = explode(",", $googleTaxanomy['keywords']);
            return [
                "id" => $googleTaxanomy["id"],
                "keywords" => $googleTaxanomy["keywords"],
                "taxanomy1" => $googleTaxanomy["taxanomy1"],
                "taxanomy2" => $googleTaxanomy["taxanomy2"],
                "taxanomy3" => $googleTaxanomy["taxanomy3"],
                "taxanomy4" => $googleTaxanomy["taxanomy4"],
                "taxanomy5" => $googleTaxanomy["taxanomy5"],
                "categoryID" => $googleTaxanomy["categoryID"],
                "words_count" => count($searchWords)
            ];
        }, $googleTaxanomyResult);
        return $googleTaxanomyArrays;
    }
    
    /**
     * Fashiola doesn't support number-based google taxonomy, so we have to
     * attach words
     */
    function addFashiolaTextTaxonomy (array &$productInfo) {
        $googleTaxanomyArrays = $this->fetchGoogleTaxonomy();
        
        foreach($productInfo as &$product) {
            $productCategory = "";
            $productTitle = strtolower(strip($product['ProductName']));
            foreach($googleTaxanomyArrays as $googleTaxanomyArray){
                $keywordsArray = explode(",",$googleTaxanomyArray['keywords']);
                foreach($keywordsArray as $taxanomyKeywords){
                    if(strpos($productTitle,strtolower($taxanomyKeywords)) !== false){
                        $productCategory = $googleTaxanomyArray['taxanomy1'];
                        if (!empty($googleTaxanomyArray['taxanomy2'])) {
                           $productCategory .= " > " . $googleTaxanomyArray['taxanomy2'];
                        }
                        if (!empty($googleTaxanomyArray['taxanomy3'])) {
                           $productCategory .= " > " . $googleTaxanomyArray['taxanomy3'];
                        }
                        if (!empty($googleTaxanomyArray['taxanomy4'])) {
                           $productCategory .= " > " . $googleTaxanomyArray['taxanomy4'];
                        }
                        if (!empty($googleTaxanomyArray['taxanomy5'])) {
                           $productCategory .= " > " . $googleTaxanomyArray['taxanomy5'];
                        }
                        break 2;
                    }
                }
            }
            $product['GoogleTaxonomyText'] = $productCategory;
        }
    }
    
    function getExcludeDataConfig() {
        return $this->fetchDbResults("SELECT regionName, excludedBrands, feedExcludedProducts FROM excludedDataTable");
    }
    
    function getDbInstance() {
        return $this->dbInstance;
    }
    
    function getCurrentServer() {
        return $this->currentServer;
    }
}
