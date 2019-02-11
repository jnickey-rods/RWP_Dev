<?php

header("X-Robots-Tag: noindex,nofollow");

if(preg_match('/^[A-Z0-9\.\/-]+$/i', $_GET['sku']) !== false) {
	$sku = $_GET['sku'];

	switch($_SERVER['HTTP_HOST'])
	{
		case "test.rods.com":
		case "test-mens.rods.com":
		case "test-outlet.rods.com":
			$server = "http://192.168.1.196:8000";
			break;
		default:
			$server = "https://sizefinder.rods.com";
			break;
	}
	$chart_html = file_get_contents($server."/product-size-chart/".$sku);
	if(empty($chart_html)) {
		header($_SERVER['SERVER_PROTOCOL']." 404 Not Found", true, 404);
	}
	echo $chart_html;
}
