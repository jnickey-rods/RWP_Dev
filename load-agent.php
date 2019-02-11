#!/usr/bin/env php
<?php

// only allow to be run from a command line
if(PHP_SAPI != 'cli') {
	exit;
}

@include("load-agent-settings.inc.php");

if(!defined("DEBUG")) {
	define("DEBUG", false);
}

define("BIND_ADDRESS", '0.0.0.0');
define("BIND_PORT", 9999);
define("LISTEN_BACKLOG", 20);
if(!defined("LOAD_THRESHOLD_DEPRIORITIZE")) {
	define("LOAD_THRESHOLD_DEPRIORITIZE", 0.80);
}
define("HOSTNAME", gethostname());

function is_primary_server() {
	return (strstr(HOSTNAME, "pr-rodsweb01") !== false);
}

function get_load_status() {
	$system_load = sys_getloadavg();
	$current_load = (float)$system_load[0];
	$weight_percentage = 1.0;	// set a minimum, so it is never removed from the rotation accidentilly
	$weight_calc = (10.0-$current_load)*10.0;
	$is_deprioritized_process_running = is_deprioritized_process_running();
	if($is_deprioritized_process_running) {
		// further penalize, as it is probably chewing up more resources than the load shows
		$weight_calc /= (float)$is_deprioritized_process_running;
	}
	if($current_load > LOAD_THRESHOLD_DEPRIORITIZE || $is_deprioritized_process_running) {
		if($weight_calc > 0) {
			$weight_percentage += $weight_calc;
		}
		$status_command = sprintf("%d%%", $weight_percentage);
// 		$status_command = "drain";	// no longer accept connections, service the already open connections
	} else {
		$status_command = "ready 100%";
	}
	return $status_command;
}

function is_deprioritized_process_running() {
	if(isset($GLOBALS['cached_deprioritized_time']) && $GLOBALS['cached_deprioritized_time'] > time()) {
		return $GLOBALS['cached_deprioritized'];
	}
	$rtn = 0;
	// xz, mysqldump, php, etc.
	exec("ps axwo pid,command", $output);
	foreach($output as $process_line) {
		if(preg_match("/([ ]?[0-9]+) ([A-Z0-9_-]+)[ ]?(.*)?/i", $process_line, $matches)) {
			$process_name = $matches[2];
			$process_args = "";
			if(isset($matches[3]) && !empty($matches[3])) {
				$process_args = $matches[3];
			}
			switch($process_name) {
				case "find":
				case "xz":
				case "mysqldump":
					$rtn += 1;
					break;
				case "php":
					if(stristr($process_args, "Check") !== false || 
						stristr($process_args, "generate") !== false) {
						$rtn += 1;
					}
					break;
			}
		}
	}
	$GLOBALS['cached_deprioritized'] = $rtn;
	$GLOBALS['cached_deprioritized_time'] = time()+30;
	return $rtn;
}

function display_prompt($msgsock) {
	$msg = HOSTNAME."> ";
	return socket_write($msgsock, $msg, strlen($msg));
}

/* This script runs as a service and listens for connections on a specific port.  It reports the server load status. */
error_reporting(E_ALL);

/* Allow the script to hang around waiting for connections. */
set_time_limit(0);

/* Turn on implicit output flushing so we see what we're getting
 * as it comes in. */
ob_implicit_flush();

if (($sock = socket_create(AF_INET, SOCK_STREAM, SOL_TCP)) === false) {
    echo "socket_create() failed: reason: " . socket_strerror(socket_last_error()) . PHP_EOL;
    exit;
}

if (!socket_set_option($sock, SOL_SOCKET, SO_REUSEADDR, 1)) {
    echo "socket_set_option(SO_REUSEADDR) failed: reason: " . socket_strerror(socket_last_error()) . PHP_EOL;
    exit;
}

if (socket_bind($sock, BIND_ADDRESS, BIND_PORT) === false) {
    echo "socket_bind() failed: reason: " . socket_strerror(socket_last_error($sock)) . PHP_EOL;
    exit;
}

if (socket_listen($sock, LISTEN_BACKLOG) === false) {
    echo "socket_listen() failed: reason: " . socket_strerror(socket_last_error($sock)) . PHP_EOL;
    exit;
}

if (!socket_set_option($sock, SOL_SOCKET, SO_RCVTIMEO, [ 'sec' => 5, 'usec' => 0])) {
	echo "socket_set_option(SO_RCVTIMEO) failed: reason: " . socket_strerror(socket_last_error()) . PHP_EOL;
}

$shutdown = false;

do {
	if (($msgsock = @socket_accept($sock)) === false) {
		if(DEBUG) {
			echo "socket_accept() failed: reason: " . socket_strerror(socket_last_error($sock)) . PHP_EOL;
		}
		continue;
	}

	socket_getpeername($msgsock, $peer_address, $peer_port);

	$status_command = get_load_status();
	
	$msg = $status_command.PHP_EOL;

    do {
    
	if(isset($msg) && !empty($msg)) {
		$wr_rtn = socket_write($msgsock, $msg, strlen($msg));
		$socket_error = socket_last_error($msgsock);
		if(display_prompt($msgsock) === false || $wr_rtn === false || $socket_error) {
			echo "socket_write: failure: ".PHP_EOL;
			break;	// probably lost the connection
		}
	}

	$read_error = null;
	socket_clear_error($sock);
	if (false === ($buf = @socket_read($msgsock, 2048, PHP_BINARY_READ))) {
		$read_error = socket_last_error($msgsock);
		if(DEBUG) {
			echo "socket_read(".$peer_address.":".$peer_port.") failed: reason (".socket_last_error($msgsock)."): " . socket_strerror(socket_last_error($msgsock)) . PHP_EOL;
		}
//             break 2;
        }
        // clear current message buffer
        $msg = null;
        // check for a timeout
	if($read_error) {
		$buf = "quit";
	}
        if (!isset($buf) || empty($buf) || !$buf = trim($buf)) {
		break;
//            continue;
        }
	switch($buf) {
		case "status":
			$msg = get_load_status().PHP_EOL;
			break;
		case "quit":
		case "exit":
			break 2;
		case "shutdown":	/* this will terminate the service and exit the script */
			$shutdown = true;
			break 2;
		default:
			$msg = "invalid command".PHP_EOL;
// 			socket_write($msgsock, $msg, strlen($msg));
			break;
	
	}
    } while (true);
    socket_close($msgsock);
} while ($shutdown === false);

@socket_shutdown($sock);
socket_close($sock);
