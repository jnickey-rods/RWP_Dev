<?php

// simple script to monitor time script takes
$start = microtime(TRUE); 

define('ROOT', dirname(__FILE__));
define("RUN_PATH", dirname(__FILE__));
define("MAGENTO_ROOT", ROOT);
define("COMPARE_STORE_ID", 1);
define("MAIL_TO", "anish@rods.com,phil@rods.com");
define("MAIN_WEBSITE_STORE_ID", 1);
define("MENS_WEBSITE_STORE_ID", 2);

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

ob_start();

$rtn = process_configurable_out_of_stock_with_qty();

$finish = microtime(TRUE);

$totaltime = $finish - $start;  
  
echo "*************\nThis script took " . $totaltime . " seconds to run\n";

$message = ob_get_contents();
ob_end_clean();

if($rtn > 0) {
	// results so notify
	$mail_from = Mage::getStoreConfig("trans_email/ident_general/email");
	$additional_headers = "From: ".$mail_from."\r\n";

	mail (Mage::getStoreConfig(Rods_Config_Helper_Data::CONFIG_OUT_OF_STOCK_NOTIFICATION_EMAIL) , "Configurables Out of Stock with Quantity" , $message, $additional_headers);
}

function process_configurable_out_of_stock_with_qty()
{
	$global_prods = array();
	$collectionConfigurable = Mage::getResourceModel('catalog/product_collection')->addAttributeToFilter('type_id', array('eq' => 'configurable'));

	foreach ($collectionConfigurable as $_configurableproduct) {
		/**
		* Load product by product id
		*/
		$product = Mage::getModel('catalog/product')->load($_configurableproduct->getId());

		/**
		* only process product if it is available (saleable)
		*/
// 		if ($product->isSaleable()) {
			/**
			* Get children products (all associated children products data)
			*/

			$stockItem = $product->getStockItem();
			if($stockItem->getIsInStock()){
			/**
			* All configurable products, which are in stock
			*/

			/*
			$instock_childrenisinstock = false;

			foreach ($childProducts as $childProduct) {
				$qty = Mage::getModel('cataloginventory/stock_item')->loadByProduct($childProduct)->getQty();
				if ($qty > 0) {
					$instock_childrenisinstock = true;
				}
			}
			if (!$instock_childrenisinstock) {
// 				echo "".$product->getName()." (".$product->getSku().") is IN STOCK\n";
			}
			*/

			} else {
				$childProducts = Mage::getModel('catalog/product_type_configurable')->getUsedProducts(null,$product);
				/**
				* All configurable products, which are out of stock
				*/

// 				echo "Out of Stock Configurable: ".$product->getSku()."\n";
				$outofstock_childrenisinstock = false;

				foreach ($childProducts as $childProduct) {
					$qty = Mage::getModel('cataloginventory/stock_item')->loadByProduct($childProduct)->getQty();
					if ($qty > 0) {
						$outofstock_childrenisinstock = true;
					}
				}
				if ($outofstock_childrenisinstock) {
					echo $product->getSku()." has in-stock children, but ".$product->getName()." is marked OUT OF STOCK\n";
					$global_prods[] = $product->getSku();
				}
			}
// 		}
	}

	echo "*************\n";
	echo "Total Out of Stock Configurable Products with Quantity: ".count($global_prods)."\n";
	$i = count($global_prods);
	
	return $i;
}
