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

ob_start();

$rtn = check_admin_logins();

$finish = microtime(TRUE);

$totaltime = $finish - $start;  
  
echo "*************\nThis script took " . $totaltime . " seconds to run\n";

$message = ob_get_contents();
ob_end_clean();

if($rtn > 0) {
	// results so notify
	$mail_from = Mage::getStoreConfig("trans_email/ident_general/email");
	$additional_headers = "From: ".$mail_from."\r\n";
	mail (Mage::getStoreConfig(Rods_Config_Helper_Data::CONFIG_OUT_OF_STOCK_NOTIFICATION_EMAIL) , "After Hours Magento Logins" , $message, $additional_headers);
}

function check_admin_logins()
{
	$logins = array();
	$params = array();
	$sql = "SELECT * FROM (SELECT log_id,event_code,CONVERT_TZ(time, '+00:00', @@global.time_zone) AS time,action,info,status,user,user_id,fullaction,error_message FROM enterprise_logging_event) AS ele WHERE action='login' AND ( ( TIME(time)<=TIME('7:00') AND DATE(time)=CURRENT_DATE ) OR ( TIME(time)>=TIME('20:00') AND DATE(time)=CURRENT_DATE ) )";
	$db_conn = Mage::getSingleton('core/resource')->getConnection('core_read');
	// Prepary our query for binding
        $stmt = $db_conn->query($sql, $params);
        $header = false;
        while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
		if(!$header) {
			echo implode(",", array_keys($row))."\n";
			$header = true;
		}
                $logins[] = $row;
                echo utf8_decode(implode(",", $row)."\n");
        }
        $stmt = null;

	echo "*************\n";
	echo "Total After Hours Login: ".count($logins)."\n";
	$i = count($logins);
	
	return $i;
}
