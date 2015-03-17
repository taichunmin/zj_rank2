<?php
/**
 * /usr/bin/php /home/taichunmin/zj_rank2/updateGoogleSheetRank.php 1>/home/taichunmin/zj_rank2/last.log 2>&1
 */

require_once('vendor/autoload.php');
set_time_limit(0);
chdir(dirname(__FILE__));

use Google\Spreadsheet\DefaultServiceRequest;
use Google\Spreadsheet\ServiceRequestFactory;
use Google\Spreadsheet\Worksheet;

$go2d = new ZJR2\GoogleOauth2Device();

$googleAccessToken = $go2d->access_token(600);

$serviceRequest = new DefaultServiceRequest($googleAccessToken['token'], $googleAccessToken['type']);
ServiceRequestFactory::setInstance($serviceRequest);

$worksheet = new Worksheet(
	new SimpleXMLElement(
		ServiceRequestFactory::getInstance()->get(sprintf('feeds/worksheets/%s/private/full/%s', ZJR2\GOOGLE_SPREADSHEET_ID, ZJR2\GOOGLE_WORKSHEET_ID))
	)
);
$listFeed = $worksheet->getListFeed();
$listEntries = $listFeed->getEntries();

$zj = new ZJR2\Zerojudge();
$pb = new ZJR2\ProgressBar(count($listEntries));

echo 'Please input "yes" to display ProgressBar -> ';
$confirm = strtolower(trim(fgets(STDIN)));
if( !in_array($confirm, array('yes', 'y')) )
	ZJR2\ProgressBar::$_display = false;
$pb->p();

foreach($listEntries as $entry){
	$row = $entry->getValues();
	if(!empty($row['account'])){
		$statistic = $zj->get_statistic($row['account']);
		$entry->update(array_merge($row, $statistic));
		$pb->c();
	}
}

$pb->cls();
echo 'Finish '.count($listEntries).' Records!'.PHP_EOL;
