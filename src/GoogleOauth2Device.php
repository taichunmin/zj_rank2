<?php

namespace ZJR2;
use \Exception;

class GoogleOauth2Device
{
	protected $client_id = GOOGLE_CLIENT_ID;
	protected $client_secret = GOOGLE_CLIENT_SECRET;
	protected $curl_cookie = '';
	protected $session_file = '';
	protected $states;
	protected $try_cnt = 0;
	protected $minAvailable = 60;
	public $curl;
	public $try_cnt_limit = 3;

	function __construct($session_name = 'GoogleOauth2DeviceSession')
	{
		$this->session_file = $session_name.'.inc.php';
		$this->curl_cookie = $session_name.'.cookie';
		$this->restore_states();
		$this->curl = new Curl($this->curl_cookie);
		$this->curl->curlopts_all[CURLOPT_FAILONERROR] = false;
	}

	function __destruct()
	{
		$this->save_states();
	}

	public function access_token( $minAvailable = 60 )
	{
		$states = &$this->states;
		$this->minAvailable = $minAvailable;
		if(empty($states['stage']))
			$this->reset_states();
		while(get($states['access_token_expire'], 0) < time() + $this->minAvailable)
			$this->{'stage'.$states['stage']}();
		return array('token' => $states['access_token'], 'type' => $states['token_type']);
	}

	private function stage1()
	{
		// https://developers.google.com/accounts/docs/OAuth2ForDevices#obtainingacode
		if($this->try_cnt > $this->try_cnt_limit)
			throw new Exception('Google Oauth2 error after trying '.$this->try_cnt.' times');

		$curl = &$this->curl;
		$state = &$this->states[__FUNCTION__];
		$state = (array) $state;

		$respon = $curl->post('https://accounts.google.com/o/oauth2/device/code', array(
			'client_id' => $this->client_id,
			'scope' => 'https://spreadsheets.google.com/feeds',
		));
		if(!empty($respon['error']))
			throw new Exception($respon['error']);
		$state = json_decode($respon['html'], true);
		if(empty($state))
			throw new Exception('json parse error: '.$respon['html']);
		/*
			{
				"device_code" : "4/4-GMMhmHCXhWEzkobqIHGG_EnNYYsAkukHspeYUk9E8",
				"user_code" : "GQVQ-JKEC",
				"verification_url" : "https://www.google.com/device",
				"expires_in" : 1800,
				"interval" : 5
			}
		*/
		$state['expire'] = time() + $state['expires_in'];
		$this->states['stage'] = 2;
		$this->try_cnt ++;
		// die(var_export($this->states, true));
	}

	private function stage2()
	{
		// https://developers.google.com/accounts/docs/OAuth2ForDevices#displayingthecode
		$curl = &$this->curl;
		$state = &$this->states[__FUNCTION__];
		$state = (array) $state;
		$stage1_state = get($this->states['stage1'], array());

		if(get($stage1_state['expire'], 0) <= time()){ // expired
			$this->reset_states();
			return $this;
		}

		// Promote to inform user to auth application
		echo PHP_EOL;
		echo "Please goto the URL: ".$stage1_state['verification_url'].PHP_EOL;
		echo "Enter The User Code: ".$stage1_state['user_code'].PHP_EOL;
		echo PHP_EOL;
		echo "If you finish the authorise, Please input anything -> ";

		$input = fgets(STDIN); // Wait for user input

		// https://developers.google.com/accounts/docs/OAuth2ForDevices#obtainingatoken
		$respon = $curl->post('https://www.googleapis.com/oauth2/v3/token', array(
			'client_id' => $this->client_id,
			'client_secret' => $this->client_secret,
			'code' => $stage1_state['device_code'],
			'grant_type' => 'http://oauth.net/grant_type/device/1.0',
		));
		if(!empty($respon['error']))
			throw new Exception($respon['error']);
		$state = json_decode($respon['html'], true);
		if(empty($state))
			throw new Exception('json parse error: '.$respon['html']);
		if(!empty($state['error'])){
			echo 'Obtaining Access Token Error: '.$state['error'].PHP_EOL;
			echo sprintf('Sleep %d second...'.PHP_EOL, $stage1_state['interval']);
			sleep($stage1_state['interval']);
			return $this;
		}
		$this->states['stage'] = 3;
		$this->states['access_token'] = $state['access_token'];
		$this->states['token_type'] = $state['token_type'];
		$this->states['access_token_expire'] = time() + $state['expires_in'];
		if($state['expires_in'] < $this->minAvailable)
			$this->minAvailable = $state['expires_in'];
		$this->states['refresh_token'] = $state['refresh_token'];
		echo PHP_EOL;
		echo 'Success! '.PHP_EOL;
		echo 'Token: '.$this->states['access_token'].PHP_EOL;
		echo sprintf('Expires in %d seconds'.PHP_EOL, $state['expires_in']);
		echo 'Refresh Token: '.$this->states['refresh_token'].PHP_EOL;
		/*
			{
				"access_token" : "ya29.AHES6ZSuY8f6WFLswSv0HELP2J4cCvFSj-8GiZM0Pr6cgXU",
				"token_type" : "Bearer",
				"expires_in" : 3600,
				"refresh_token" : "1/551G1yXUqgkDGnkfFk6ZbjMLMDIMxo3JFc8lY8CAR-Q"
			}
		*/
		// die(var_export($this->states, true));
	}

	private function stage3()
	{
		// https://developers.google.com/accounts/docs/OAuth2UserAgent#validatetoken
		$curl = &$this->curl;
		$state = &$this->states[__FUNCTION__];
		$state = (array) $state;
		$access_token = get($this->states['access_token'], '');
		if(empty($access_token)){
			$this->reset_states();
			return $this;
		}

		echo 'Validating Token...'.PHP_EOL;
		$respon = $curl->get('https://www.googleapis.com/oauth2/v1/tokeninfo', array(
			'access_token' => $access_token,
		));
		if(!empty($respon['error']))
			throw new Exception($respon['error']);
		$state = json_decode($respon['html'], true);
		if(empty($state))
			throw new Exception('json parse error: '.$respon['html']);
		/*
			{
				"audience":"8819981768.apps.googleusercontent.com",
				"user_id":"123456789",
				"scope":"profile email",
				"expires_in":436
			}
		 */
		if(!empty($state['error']) || $state['expires_in'] < $this->minAvailable){
			$this->states['stage'] = 4;
			return $this;
		} elseif($state['audience'] !== $this->client_id) {
			$this->reset_states();
			return $this;
		}
		$this->states['access_token_expire'] = time() + $state['expires_in'];
		echo PHP_EOL;
		echo 'Token Validate Success!'.PHP_EOL;
		echo 'Token: '.$access_token.PHP_EOL;
		echo sprintf('Expires in %d seconds'.PHP_EOL, $state['expires_in']);
		// die(var_export($this->states, true));
	}

	public function stage4()
	{
		// https://developers.google.com/accounts/docs/OAuth2ForDevices#refreshtoken
		$curl = &$this->curl;
		$state = &$this->states[__FUNCTION__];
		$state = (array) $state;
		$refresh_token = get($this->states['refresh_token'], '');
		if(empty($refresh_token)){
			$this->reset_states();
			return $this;
		}

		echo 'Try Refresh Token...'.PHP_EOL;
		$respon = $curl->post('https://www.googleapis.com/oauth2/v3/token', array(
			'client_id' => $this->client_id,
			'client_secret' => $this->client_secret,
			'refresh_token' => $refresh_token,
			'grant_type' => 'refresh_token',
		));
		if(!empty($respon['error']))
			throw new Exception($respon['error']);
		$state = json_decode($respon['html'], true);
		if(empty($state))
			throw new Exception('json parse error: '.$respon['html']);
		if(!empty($state['error'])){
			echo 'Refresh Access Token Error: '.$state['error'].PHP_EOL;
			$this->reset_states();
			return $this;
		}
		$this->states['stage'] = 3;
		$this->states['access_token'] = $state['access_token'];
		$this->states['token_type'] = $state['token_type'];
		$this->states['access_token_expire'] = time() + $state['expires_in'];
		if($state['expires_in'] < $this->minAvailable)
			$this->minAvailable = $state['expires_in'];
		echo PHP_EOL;
		echo 'Success! '.PHP_EOL;
		echo 'Token: '.$this->states['access_token'].PHP_EOL;
		echo sprintf('Expires in %d seconds'.PHP_EOL, $state['expires_in']);
		/*
			{
				"access_token" : "ya29.AHES6ZSuY8f6WFLswSv0HELP2J4cCvFSj-8GiZM0Pr6cgXU",
				"token_type" : "Bearer",
				"expires_in" : 3600,
				"refresh_token" : "1/551G1yXUqgkDGnkfFk6ZbjMLMDIMxo3JFc8lY8CAR-Q"
			}
		*/
	}

	/**
	 * Save Current Class States
	 * @return [type] [description]
	 */
	public function save_states()
	{
		$session_str = '<?php'.PHP_EOL;
		$session_str .= '$config = '.var_export($this->states, true).';'.PHP_EOL;
		$session_str .= 'return $config;'.PHP_EOL;
		if(false === file_put_contents($this->session_file, $session_str))
			throw new Exception(sprintf('Failed on write session "%s":'.PHP_EOL.PHP_EOL.'%s', $this->session_file, $session_str));
		return $this;
	}

	/**
	 * Restore Old Class States
	 * @return [type] [description]
	 */
	public function restore_states()
	{
		if(file_exists($this->session_file))
			$this->states = include($this->session_file);
		if(empty($this->states))
			$this->reset_states();
		return $this;
	}

	public function reset_states()
	{
		$this->states = array(
			'stage' => 1,
		);
		return $this;
	}
}
