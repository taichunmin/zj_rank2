<?php

require_once('vendor/autoload.php');
set_time_limit(0);
chdir(dirname(__FILE__));

use Google\Spreadsheet\DefaultServiceRequest;
use Google\Spreadsheet\ServiceRequestFactory;
use Google\Spreadsheet\WorksheetFeed;

$go2d = new ZJR2\GoogleOauth2Device();

$googleAccessToken = $go2d->access_token(600);

$serviceRequest = new DefaultServiceRequest($googleAccessToken['token'], $googleAccessToken['type']);
ServiceRequestFactory::setInstance($serviceRequest);
$worksheetFeed = new WorksheetFeed(
	ServiceRequestFactory::getInstance()->get(sprintf('feeds/worksheets/%s/private/full', ZJR2\GOOGLE_SPREADSHEET_ID))
);

$progressbar_display = true;
echo 'Please input "yes" to display ProgressBar -> ';
$confirm = strtolower(trim(fgets(STDIN)));
if( !in_array($confirm, array('yes', 'y')) )
	$progressbar_display = false;

foreach($worksheetFeed as $worksheet){
	echo 'Worksheet: ' . trim($worksheet->getTitle()) .PHP_EOL;

	$listFeed = $worksheet->getListFeed();
	$listEntries = $listFeed->getEntries();
	$pb = new ZJR2\ProgressBar(count($listEntries));
	ZJR2\ProgressBar::$_display = $progressbar_display;
	$pb->p();

	switch ($worksheet->getTitle()) {
		case 'ZeroJudge':
			$zj = new ZJR2\Zerojudge();

			foreach($listEntries as $entry){
				$row = $entry->getValues();
				if(!empty($row['account'])){
					$statistic = $zj->get_statistic($row['account']);
					$statistic['nos'] = 0;
					foreach(array('ac', 'wa', 'tle', 'mle', 're', 'ce') as $ik)
						$statistic['nos'] += get($statistic[$ik], 0);
					$statistic['recent-ac'] = encode_json($zj->recent_ac($row['account']));
					$entry->update(array_merge(
						$row,
						array_only($statistic, ['ac', 'nos', 'recent-ac'])
					));
					$pb->c();
				}
			}
			break;

		case 'UVa':
			$uvauser = new ZJR2\UVaUser();

			foreach($listEntries as $entry){
				$row = $entry->getValues();
				try{
					$uvauser->detect_uid($row);
					if(empty($row['uname']))
						$row['uname'] = $uvauser->uname();
					$profile = $uvauser->profile();
					$profile['recent-ac'] = encode_json($uvauser->recent_ac());
					$entry->update(array_merge(
						$row,
						array_only($profile, ['uid', 'ac', 'nos', 'recent-ac'])
					));
					$pb->c();
				} catch(Exception $e) {
					echo $e->getMessage().PHP_EOL.$e->getTraceAsString();
				}
			}
			break;
	}

	$pb->cls();
	echo 'Finish '.$pb->i().' Records!'.PHP_EOL;
}

