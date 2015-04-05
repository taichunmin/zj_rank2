<?php

require_once('vendor/autoload.php');
set_time_limit(0);
chdir(dirname(__FILE__));

$html = file_get_contents('debug.html');
foreach(htmlqp($html, 'tr[solutionid] a[href*=ShowProblem]') as $qpv){
	echo $qpv->attr('href').PHP_EOL;
}
