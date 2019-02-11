<?php
Header("Content-type: text/plain", 1);
echo "Discovering php";
echo "\n";
$sapi_type = php_sapi_name();
echo 'Relation=' . $sapi_type;
echo "\n";
echo 'Version=' . phpversion();
echo "\n";
echo 'OS='. PHP_OS;
echo "\n";
if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN')
{
}
else
{
	$dat = getrusage();
	echo 'Pagefaults=' . $dat["ru_majflt"];        // number of page faults
	echo "\n";
	echo 'swaps='. $dat["ru_nswap"];         // number of swaps
	echo "\n";
	echo 'usertimems='. $dat["ru_utime.tv_usec"];
	echo "\n";
	echo 'usertimesec=' . $dat["ru_utime.tv_sec"];  // user time used (seconds)
}
?>

