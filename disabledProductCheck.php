<?php

// simple script to monitor time script takes
$start = microtime(TRUE); 

define('ROOT', dirname(__FILE__));
define("RUN_PATH", dirname(__FILE__));
define("MAGENTO_ROOT", ROOT);

set_time_limit(0);
ini_set('memory_limit', '-1');

require_once(ROOT.'/app/Mage.php');

if($argc > 1 && isset($argv[1])) {
	$feed_id = $argv[1];
}

ini_set('display_errors', 1);
umask(0);
Mage::app();
Mage::app()->setCurrentStore(Mage_Core_Model_App::ADMIN_STORE_ID);


$notification_email = Mage::getStoreConfig(Rods_Config_Helper_Data::CONFIG_DISABLED_PRODUCT_NOTIFICATION_EMAIL);

ob_start();

$rtn = process();

$finish = microtime(TRUE);

$totaltime = $finish - $start;  
  
echo "*************\nThis script took " . $totaltime . " seconds to run\n";

$message = ob_get_contents();
ob_end_clean();

if($rtn > 0) {
	// results so notify
	$mail_from = Mage::getStoreConfig("trans_email/ident_general/email");
	$additional_headers = "From: ".$mail_from."\r\n";

	mail($notification_email, "Products Disabled with Quantity Report" , $message, $additional_headers);
}

function process()
{
	$global_prods = getProductsQty(Mage_Core_Model_App::ADMIN_STORE_ID, Mage_Catalog_Model_Product_Status::STATUS_DISABLED);

	$i = 0;
	foreach($global_prods as $prod_sku => $prod_qty) {
            if($prod_qty > 0) {
                $i++;
                echo $prod_sku.": has qty (".$prod_qty."), but disabled globally\n";
            }
        }

	echo "*************\n";
	echo "Total Disabled Products: ".count($global_prods)."\n";
	echo "Found ".$i." matches\n";
	
	return $i;
}

    function getProductsQty($store_id = null, $status = Mage_Catalog_Model_Product_Status::STATUS_ENABLED) {
        $products = array();
        $productCollection = Mage::getResourceModel('catalog/product_collection')
            ->setStore($store_id)
            ->addAttributeToSelect(array('name', 'image', 'price', 'id'))
            ->addAttributeToFilter('status', array('eq' => $status))
            ->joinField('qty',
                'cataloginventory/stock_item',
                'qty',
                'product_id=entity_id',
                '{{table}}.stock_id=1',
                'left');
        foreach($productCollection as $_product) {
            $products[$_product->getSku()] = $_product->getQty();
	}

	return $products;
}
