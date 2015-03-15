<?php

require_once('vendor/autoload.php');
set_time_limit(0);

$zj = new ZJR2\Zerojudge();

if($argc >= 2){
	$account = get($argv[1], 'taichunmin');
	echo sprintf('%s: %d', $account, $zj->get_ac($account)).PHP_EOL;
}
