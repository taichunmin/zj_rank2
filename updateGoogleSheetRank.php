<?php

require_once('vendor/autoload.php');
set_time_limit(0);

use Google\Spreadsheet\DefaultServiceRequest;
use Google\Spreadsheet\ServiceRequestFactory;

$go2d = new ZJR2\GoogleOauth2Device();

$googleAccessToken = $go2d->access_token();
// echo var_export($googleAccessToken, true).PHP_EOL;

$serviceRequest = new DefaultServiceRequest($googleAccessToken['token'], $googleAccessToken['type']);
// die(var_export($serviceRequest, true));
ServiceRequestFactory::setInstance($serviceRequest);

$spreadsheetService = new Google\Spreadsheet\SpreadsheetService();
$spreadsheetFeed = $spreadsheetService->getSpreadsheets();
$spreadsheet = $spreadsheetFeed->getByTitle('103下 - Zerojudge 排行榜');
$worksheetFeed = $spreadsheet->getWorksheets();
$worksheet = $worksheetFeed->getByTitle('表單回應 1');
$listFeed = $worksheet->getListFeed();

$zj = new ZJR2\Zerojudge();

foreach($listFeed->getEntries() as $entry){
	$row = $entry->getValues();
	if(!empty($row['account'])){
		$statistic = $zj->get_statistic($row['account']);
		$entry->update(array_merge($row, $statistic));
	}
}
