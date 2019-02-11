<?php
    $xml = simplexml_load_file(dirname(__FILE__).'/app/etc/local.xml', NULL, LIBXML_NOCDATA);

	if(!extension_loaded('mysqli') && !extension_loaded('pdo_mysql')) {
		die("mysqli or pdo_mysql extension required!".PHP_EOL);
	}

    $db['host'] = $xml->global->resources->default_setup->connection->host;
    $db['name'] = $xml->global->resources->default_setup->connection->dbname;
    $db['user'] = $xml->global->resources->default_setup->connection->username;
    $db['pass'] = $xml->global->resources->default_setup->connection->password;
    $db['pref'] = $xml->global->resources->db->table_prefix;

    if(isset($_GET['clean'])) {
		$clean = $_GET['clean'];
    } else if($_SERVER['argc'] >= 1 && isset($_SERVER['argv'][1])) {
		$clean = $_SERVER['argv'][1];
    } else {
		echo "usage: ".basename(__FILE__)." (log|var)".PHP_EOL;
		exit;
    }

    if($clean == 'log') clean_log_tables();
    if($clean == 'var') clean_var_directory();
     
    function clean_log_tables() {
        global $db;
       
        $tables = array(
            'dataflow_batch_export',
            'dataflow_batch_import',
            'log_customer',
            'log_quote',
            'log_summary',
            'log_summary_type',
            'log_url',
            'log_url_info',
            'log_visitor',
            'log_visitor_info',
            'log_visitor_online',
//             'report_event'
        );

		if(extension_loaded('pdo_mysql')) {
// 			error_log("Using PDO MySQL");
			$db_conn = new PDO('mysql:host='.$db['host'].';dbname='.$db['name'], $db['user'], $db['pass']);
		} else {
// 			error_log("Using MySQLi");
			$db_conn = new mysqli($db['host'], $db['user'], $db['pass'], $db['name']) or die($db_conn->connect_error);
		}

        foreach($tables as $v => $k) {
			try {
				$db_conn->query('TRUNCATE `'.$db['pref'].$k.'`');
			} catch(Exception $e) {
				error_log("DB Error: ".$e->getMessage());
			} finally {
				// keep going
			}
        }
    }
     
    function clean_var_directory() {
		if(!chdir(dirname(__FILE__))) {
			error_log("Unable to change to the correct directory (".dirname(__FILE__).")");
			return false;
		}
        $dirs = array(
            'downloader/pearlib/cache/*',
            'downloader/pearlib/download/*',
            'var/cache/',
            'var/full_page_cache/',
            'var/log/',
            'var/report/',
            'var/session/',
            'var/tmp/'
        );
       
        foreach($dirs as $v => $k) {
            exec('rm -rf '.$k);
        }
    }
