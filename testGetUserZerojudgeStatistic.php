<?php

require_once('vendor/autoload.php');
set_time_limit(0);

$zj = new ZJR2\Zerojudge();

if($argc >= 2){
	$account = get($argv[1], 'taichunmin');
	echo sprintf('%s: %s', $account, var_export($zj->get_statistic($account), true)).PHP_EOL;
}
