<?php

// http://zerojudge.tw/UserStatistic?account=loky

namespace ZJR2;

class Zerojudge
{
	public function get_ac($account)
	{
		$curl = new Curl('zerojudge.cookie');
		$data = $curl->get('http://zerojudge.tw/UserStatistic', array(
			'account' => $account,
		));
		if($data['url'] === 'http://zerojudge.tw/Login'){
			$login_from = array();
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
		// $('a[href$="=AC"]').text()
		return htmlqp($data['html'], 'a[href$="=AC"]')->text();
	}
}
