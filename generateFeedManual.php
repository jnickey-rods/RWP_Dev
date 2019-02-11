<?php

// simple script to monitor time script takes
$start = microtime(TRUE); 

define('ROOT', dirname(__FILE__));
define("RUN_PATH", dirname(__FILE__));
define("MAGENTO_ROOT", ROOT);

set_time_limit(0);
ini_set('memory_limit', '-1');

require_once(ROOT.'/app/Mage.php');

$store_id = 1;
$feed_id = null;

if($argc > 1 && isset($argv[1])) {
	$feed_id = $argv[1];
}

ini_set('display_errors', 1);

$current_user = posix_getpwnam("nginx");
$current_group = posix_getgrnam("nginx");
posix_setuid($current_user['uid']);
posix_setgid($current_group['gid']);

umask(0);
Mage::app();
Mage::app()->setCurrentStore(Mage_Core_Model_App::ADMIN_STORE_ID);


process($feed_id);

$finish = microtime(TRUE);

$totaltime = $finish - $start;  
  
echo "This script took " . $totaltime . " seconds to run\n";

    function process($feed_id = null)
    {
        // set memory limit (Mb)
        //ini_set('memory_limit', Mage::getStoreConfig('amfeed/system/max_memory') . 'M');
        set_time_limit(60*60);
        $message = '';
        $feeds = Mage::getModel('amfeed/profile')->getCollection();

        foreach ($feeds as $currentFeed) {
            if(isset($feed_id) && $feed_id != $currentFeed->getId()) {
		// skip this feed
		continue;
            }

            if ((Amasty_Feed_Model_Profile::STATE_READY == $currentFeed->getStatus()) || (Amasty_Feed_Model_Profile::STATE_ERROR == $currentFeed->getStatus())) {
                
                $isCompleted = false;
        	echo $currentFeed->getId()." (".$currentFeed->getStatus().") "; 

                while (!$isCompleted) {
			echo "Generating (".$currentFeed->getTitle().")...\n";
                    try {
                        $feed = Mage::getModel('amfeed/profile')->load($currentFeed->getId());
                        $hasGenerated = $feed->generate();
                        $total = $feed->getInfoTotal();
                        if (!$total) {
                            $message = Mage::helper('amfeed')->__('There are no products to export for feed `%s`', $feed->getTitle());
                            $isCompleted = true;
                        } elseif ($hasGenerated) {
                            $feed->sendTo();
                            $message = Mage::helper('amfeed')->__('The `%s` feed has been generated.', $feed->getTitle());
                            $isCompleted = true;
                        }
                    } catch (Exception $e) {
                        $message = Mage::helper('amfeed')->__('The `%s` feed generation has failed: %s', $currentFeed->getTitle(), $e->getMessage());
                        $isCompleted = true;
                        $currentFeed->setStatus(Amasty_Feed_Model_Profile::STATE_ERROR);
                        $currentFeed->save();
                    }
                }
                echo $message."\n";
            }
        }
        
    }
