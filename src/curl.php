<?php

namespace ZJR2;

class Curl
{

	/**
	 * Headers for all method.
	 * @var array
	 */
	public $headers_all = array(
		'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
		'Accept-Charset' => 'utf-8;q=0.7,*;q=0.3',
		'Accept-Language' => 'zh-TW,zh;q=0.8,en-US;q=0.6,en;q=0.4',
		'Cache-Control' => 'max-age=0',
		'Connection' => 'keep-alive',
	);

	/**
	 * Headers for POST method.
	 * @var array
	 */
	public $headers_post = array();

	/**
	 * Curlopts for all method.
	 * @var array
	 */
	public $curlopts_all = array(
		CURLOPT_CONNECTTIMEOUT_MS => 30000, // 單次超時 30 秒
		CURLOPT_TIMEOUT_MS        => 60000, // 總計超時 60 秒
		CURLINFO_HEADER_OUT => true,
		CURLOPT_FAILONERROR => true,
		CURLOPT_FOLLOWLOCATION => true,
		CURLOPT_HEADER => true,
		CURLOPT_RETURNTRANSFER => true,
		CURLOPT_SSL_VERIFYHOST => false,
		CURLOPT_SSL_VERIFYPEER => false,
		CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/41.0.2272.89 Safari/537.36', // chrome://version/
	);

	public $curlopts_post = array(
		CURLOPT_POST => true,
	);

	private $parse_url = array();

	function __construct($cookie = 'default.cookie')
	{
		$this->curlopts_all[CURLOPT_COOKIEFILE] = $cookie;
		$this->curlopts_all[CURLOPT_COOKIEJAR] = $cookie;
	}

	private function _header_build($headers)
	{
		$parse_url = &$this->parse_url;
		$headers['Origin'] = sprintf('%s://%s', $parse_url['scheme'], $parse_url['host']);
		foreach($headers as $key => &$value)
			$value = sprintf('%s: %s', $key, $value);
		return array_values($headers);
	}

	private function _curlopts_build($curlopts, $headers)
	{
		$parse_url = &$this->parse_url;
		$curlopts[CURLOPT_REFERER] = sprintf('%s://%s', $parse_url['scheme'], $parse_url['host']);
		$curlopts[CURLOPT_HTTPHEADER] = $headers;
		return $curlopts;
	}

	public function get($url, $query = array(), $opts = array())
	{
		$this->set_url($url, $query);
		$headers = $this->_header_build( $this->headers_all );
		$curlopts = $this->_curlopts_build( $this->curlopts_all, $headers );
		return $this->exec($curlopts);
	}

	public function post($url, $postfields = array(), $opts = array())
	{
		$this->set_url($url, get($opts['query'], array()));
		$this->curlopts_post[CURLOPT_POSTFIELDS] = is_array($postfields) ? http_build_query($postfields) : $postfields;
		$headers = $this->_header_build( $this->headers_all + $this->headers_post );
		$curlopts = $this->_curlopts_build( $this->curlopts_all + $this->curlopts_post, $headers );
		return $this->exec($curlopts);
	}

	public function exec($curlopts)
	{
		$ch = curl_init();
		curl_setopt_array($ch, $curlopts);
		$frame = curl_exec($ch);
		$data = curl_getinfo($ch);
		if(false !== $frame){
			$data['header'] = explode("\r\n\r\n", $frame, $data['redirect_count']+2);
			$data['html'] = array_pop($data['header']);
		} else {
			$data['curl_error'] = sprintf('curl(%d): %s', curl_errno($ch), curl_error($ch));
		}
		curl_close($ch);
		return $data;
	}

	private function set_url($url, $query = array())
	{
		$parse_url = &$this->parse_url;
		$parse_url = parse_url($url);
		if(!empty($query)){
			if(is_string($query))
				parse_str($query, $query);
			if(!is_array($query))
				throw new Exception('Error on set_url '.var_export($query, true));
			$parse_url['query'] = get($parse_url['query'], '');
			parse_str($parse_url['query'], $query2);
			if(!empty($parse_url['query']))
				$url = substr($url, 0, -strlen($parse_url['query']));
			if($url[strlen($url)-1]!='?')
				$url .= '?';
			$url .= http_build_query(array_merge($query2, $query));
			$parse_url = parse_url($url);
		}
		$this->curlopts_all[CURLOPT_URL] = $url;
		if( false === $parse_url )
			throw new Exception('curl parse url error');
	}
}
