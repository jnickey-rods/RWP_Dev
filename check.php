<?php

// 2017.09.07 - amistry
// Lets be a little more sophisticated and handle some error conditions that might need
// to force this server to be automatically removed from the rotation
define("ROOT", dirname(__FILE__));

$notification_email = "anish@rods.com, phil@rods.com";
$server_online = false;
$is_maintenance_mode = file_exists(ROOT."/maintenance.flag");
$hostname = gethostname();

if ($is_maintenance_mode) {
	$server_online = true;
} else {
	try {
		require_once(ROOT.'/app/Mage.php');
		umask(0);
		Mage::app();
		Mage::app()->setCurrentStore(Mage_Core_Model_App::ADMIN_STORE_ID);
		$resource = Mage::getSingleton('core/resource');
		$read_db_conn = $resource->getConnection('core_read');
		$query = "SELECT * FROM ".$resource->getTableName('catalog/product')." LIMIT 1";
		$results = $read_db_conn->fetchAll($query);
		$server_online = true;
	} catch (Exception $e) {
		// database / site offline?
		$notify_time_file = sys_get_temp_dir()."/check-last-notification";
		$last_notify_time = 0;
		$file_attr = stat($notify_time_file);
		if($file_attr) {
			$last_notify_time = $file_attr['mtime'];
		}
		if(time() > strtotime("+15 minutes", $last_notify_time)) {
			$additional_headers = "";
			$message = "Unable to load Magento:\n\nCaught Exception:\n".$e->getMessage()."\n\n".$e->getTraceAsString();
			mail ($notification_email , "Server ".$hostname." Unable to Load Magento" , $message, $additional_headers);
			touch($notify_time_file);
		}
	}
}

if($server_online === true) {
?>
<html>
 <head>
  <title>PHP Test</title>
 </head>
 <body>
 PHP Server <?php echo $hostname; ?> is Up!<br />
<?php
	if($is_maintenance_mode === true) {
		echo "<strong>Maintenance Mode active!</strong>";
	}
?>
 </body>
</html>
<?php
} else {
	http_response_code(503);	// service unavailable HTTP Status
?>
<html>
 <head>
  <title>PHP Test</title>
 </head>
 <body>
 PHP Server <?php echo $hostname; ?> is <strong>Offline!</strong>!<br />
 </body>
</html>
<?php
}
