<?php

require_once('vendor/autoload.php');
set_time_limit(0);

// $uhuntApi = new ZJR2\UhuntApi();

// echo $uhuntApi->uname2uid('taichunmin');
// var_export($uhuntApi->p_id('100'));
// var_export($uhuntApi->p_num('10071'));
// var_export($uhuntApi->subs_user('4530'));
// var_export($uhuntApi->ranklist('4530'));
// var_export($uhuntApi->get_statistic('taichunmin'));

// echo PHP_INT_MAX.PHP_EOL;
// echo ~PHP_INT_MAX.PHP_EOL;

$uvaUser = new ZJR2\UVaUser(['uname'=>'taichunmin']);
// var_export($uvaUser->stats_pnum($argv[1] ?: 10071));
// var_export($uvaUser->profile());
var_export($uvaUser->recent_ac());
