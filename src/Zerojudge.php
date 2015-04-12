<?php

// http://zerojudge.tw/UserStatistic?account=loky

namespace ZJR2;

class Zerojudge
{
	function __construct()
	{
		$this->curl = new Curl('zerojudge.cookie');
	}

	public function get_statistic($account)
	{
		usleep(rand(200,1000)); // sleep 0.2 ~ 1.0 second
		$curl = &$this->curl;
		$data = $curl->get('http://zerojudge.tw/UserStatistic', array(
			'account' => $account,
		));
		$this->ensure_login($data);
		$statistic = array();
		if(empty($data['html']))
			throw new Exception('no response html.');
		$isBanned = true;
		foreach(htmlqp($data['html'], 'a[href*=status]') as $qpv){
			$isBanned = false;
			$href = $qpv->attr('href');
			if(! preg_match('/status=(\w+)/us', $href, $href_match))
				continue;
			$status = $href_match[1];
			$cnt = intval($qpv->text());
			$statistic[strtolower($status)] = $cnt;
		}
		if($isBanned)
			throw new Exception($data['html']);
		return $statistic;
	}

	public function recent_ac($account, $n = 3)
	{
		usleep(rand(200,1000)); // sleep 0.2 ~ 1.0 second
		$curl = &$this->curl;
		$data = $curl->get('http://zerojudge.tw/Submissions', array(
			'account' => $account,
			'status' => 'AC',
		));
		$this->ensure_login($data);
		file_put_contents('debug.html', $data['html']);
		file_put_contents('debug.php', var_export($data, true));
		$recent_ac = array();
		if(empty($data['html']))
			throw new Exception('no response html.');
		$isBanned = true;
		foreach(htmlqp($data['html'], 'tr[solutionid] a[href*=ShowProblem]') as $i => $qpv){
			$isBanned = false;
			preg_match('/problemid=(\w+)/us', $qpv->attr('href'), $match);
			$problemId = $match[1];
			if(!isset($recent_ac[$problemId]))
				$recent_ac[$problemId] = $i;
		}
		if($isBanned)
			throw new Exception($data['html']);
		asort($recent_ac);
		return array_keys(array_slice($recent_ac, 0, $n, true));
	}

	private function ensure_login(&$data)
	{
		$curl = &$this->curl;
		if($data['url'] === 'http://zerojudge.tw/Login'){
			usleep(rand(200,1000)); // sleep 0.2 ~ 1.0 second
			$login_from = array();
			if(empty($data['html']))
				throw new Exception('no response html.');
			foreach( htmlqp($data['html'], 'form[action=Login] input[name]') as $qpv )
				$login_from[ $qpv->attr('name') ] = $qpv->val();
			if(!empty($login_from)){
				$login_from['account'] = ZEROJUDGE_ACCOUNT;
				$login_from['passwd'] = ZEROJUDGE_PASSWD;
				$data = $curl->post('http://zerojudge.tw/Login', $login_from);
			}
		}
		// file_put_contents('debug.html', $data['html']);
		// file_put_contents('debug.php', var_export($data, true));
	}
}
