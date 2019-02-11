<?php

$file = $_GET['f'];

if(opcache_invalidate($file,true)) {
	echo "opcache invalided.";
} else {
	echo "Error invalidating.";
}


