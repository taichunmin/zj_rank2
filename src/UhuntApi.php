<?php

// http://zerojudge.tw/UserStatistic?account=loky

namespace ZJR2;
use \Exception;

class UhuntApi
{
	const API_BASE = 'http://uhunt.felix-halim.net/api/';
	static $MAP_VERDICT = [
		'0' => 'qu', // In queue
		'10' => 'se', // Submission error
		'15' => 'cj', // Can't be judged
		'20' => 'qu', // In queue
		'30' => 'ce', // Compile error
		'35' => 'rf', // Restricted function
		'40' => 're', // Runtime error
		'45' => 'ole', // Output limit
		'50' => 'tle', // Time limit
		'60' => 'mle', // Memory limit
		'70' => 'wa', // Wrong answer
		'80' => 'pe', // PresentationE
		'90' => 'ac', // Accepted
	];
	static $MAP_SUB_KEY = [
		0 => 'sid', // 上傳 ID
		1 => 'pid', // 題目 ID
		2 => 'ver', // Judge 結果
		3 => 'run', // 執行時間 (ms)
		4 => 'sbt', // 上傳時間 (unix timestamp)
		5 => 'lan', // 程式語言 (1=ANSI C, 2=Java, 3=C++, 4=Pascal, 5=C++11)
		6 => 'rank', // 排行
	];

	protected $pnum2pid = [], $pid2pnum = [];

	public function uname2uid($uname = null)
	{
		if(empty($uname))
			throw new Exception('error: uname is required');
		$curl = new Curl('uhunt.cookie');
		$data = $curl->get(sprintf(self::API_BASE.'uname2uid/'.$uname));
		if(intval($data['html']) < 1)
			throw new Exception('Error: failed to map uname to uid.');
		return intval($data['html']);
	}

	public function pnum2pid($pnum)
	{
		if(empty($this->pnum2pid[$pnum])){
			$tmp = $this->p_num($pnum);
			$this->pnum2pid[$pnum] = $tmp['pid'];
			$this->pid2pnum[$tmp['pid']] = $pnum;
		}
		return $this->pnum2pid[$pnum];
	}

	public function pid2pnum($pid)
	{
		if(empty($this->pid2pnum[$pid])){
			$tmp = $this->p_id($pid);
			$this->pnum2pid[$tmp['num']] = $pid;
			$this->pid2pnum[$pid] = $tmp['num'];
		}
		return $this->pid2pnum[$pid];
	}

	/**
	 * @return array (
	 *   'pid' => 100, // 題目 ID
	 *   'num' => 164, // 題號
	 *   'title' => 'String Computer', // 題目標題
	 *   'dacu' => 1754, // 不重複的 AC 帳號數量
	 *   'mrun' => 20, // 該題最佳 AC 所用時間
	 *   'mmem' => 1000000000, // 該題最佳 AC 所用記憶體
	 *   'nover' => 0, // 沒有 Judge 狀態的題數 (系統錯誤數量，可忽略)
	 *   'sube' => 134, // 上傳錯誤的數量
	 *   'noj' => 0, // 無法被 Judge 的數量
	 *   'inq' => 0, // 等候 Judge 的數量
	 *   'ce' => 859, // 編譯錯誤的數量
	 *   'rf' => 0, // 使用被限制函式的數量
	 *   're' => 773, // 執行時期錯誤的數量
	 *   'ole' => 0, // 輸出過量的數量
	 *   'tle' => 793, // 執行時間超過的數量
	 *   'mle' => 55, // 使用的記憶體超過的數量
	 *   'wa' => 5454, // 答案錯誤的數量
	 *   'pe' => 9, // 格是錯誤的數量
	 *   'ac' => 3388, // 通過的數量
	 *   'rtl' => 3000, // 題目的執行時間限制
	 *   'status' => 2, // 題目狀態 (0=不可用, 1=一般, 2=特殊 Judge)
	 * )
	 */
	public function p_id($problemId)
	{
		if(empty($problemId))
			throw new Exception('error: problemId is required');
		$curl = new Curl('uhunt.cookie');
		$data = $curl->get(sprintf(self::API_BASE.'p/id/'.$problemId));
		return json_decode($data['html'], true) ?: array();
	}

	/**
	 * @return array (
	 *   'pid' => 100, // 題目 ID
	 *   'num' => 164, // 題目編號
	 *   'title' => 'String Computer', // 題目標題
	 *   'dacu' => 1754, // 不重複的 AC 帳號數量
	 *   'mrun' => 20, // 該題最佳 AC 所用時間
	 *   'mmem' => 1000000000, // 該題最佳 AC 所用記憶體
	 *   'nover' => 0, // 沒有 Judge 狀態的題數 (系統錯誤數量，可忽略)
	 *   'sube' => 134, // 上傳錯誤的數量
	 *   'noj' => 0, // 無法被 Judge 的數量
	 *   'inq' => 0, // 等候 Judge 的數量
	 *   'ce' => 859, // 編譯錯誤的數量
	 *   'rf' => 0, // 使用被限制函式的數量
	 *   're' => 773, // 執行時期錯誤的數量
	 *   'ole' => 0, // 輸出過量的數量
	 *   'tle' => 793, // 執行時間超過的數量
	 *   'mle' => 55, // 使用的記憶體超過的數量
	 *   'wa' => 5454, // 答案錯誤的數量
	 *   'pe' => 9, // 格是錯誤的數量
	 *   'ac' => 3388, // 通過的數量
	 *   'rtl' => 3000, // 題目的執行時間限制
	 *   'status' => 2, // 題目狀態 (0=不可用, 1=一般, 2=特殊 Judge)
	 * )
	 */
	public function p_num($problemNumber)
	{
		if(empty($problemNumber))
			throw new Exception('error: problemNumber is required');
		$curl = new Curl('uhunt.cookie');
		$data = $curl->get(sprintf(self::API_BASE.'p/num/'.$problemNumber));
		return json_decode($data['html'], true) ?: array();
	}

	/**
	 * [subs_user description]
	 * @param  [type] $userId [description]
	 * @param  [type] $minSid [description]
	 * @return array (
	 *   'name' => 'taichunmin', // 使用者名稱
	 *   'uname' => 'taichunmin', // 使用者帳號
	 *   'subs' => // 上傳紀錄
	 *   array (
	 *     0 =>
	 *     array (
	 *       'sid' => 4980033, // 上傳 ID
	 *       'pid' => 1012, // 題目 ID
	 *       'ver' => 10, // Judge 結果
	 *       'run' => 5068, // 執行時間 (ms)
	 *       'sbt' => 1159348120, // 上傳時間 (unix timestamp)
	 *       'lan' => 3, // 程式語言 (1=ANSI C, 2=Java, 3=C++, 4=Pascal, 5=C++11)
	 *       'rank' => -1, // 排行
	 *     ),
	 *   ),
	 * )
	 */
	public function subs_user($userId, $minSid = null)
	{
		if(empty($userId) || intval($userId) < 1)
			throw new Exception('error: userId is required');
		$url = self::API_BASE.'subs-user/'.$userId;
		if(!empty($minSid))
			$url .= '/'.$minSid;
		$curl = new Curl('uhunt.cookie');
		$data = $curl->get($url);
		$subs_user = json_decode($data['html'], true) ?: array();
		foreach($subs_user['subs'] as &$sub){
			$sub[2] = self::$MAP_VERDICT[ $sub[2] ] ?: 'qu';
			$sub = array_combine(self::$MAP_SUB_KEY, $sub);
		}
		unset($sub);
		return $subs_user;
	}

	public function subs_user_last($userId, $count = null)
	{
		if(empty($userId) || intval($userId) < 1)
			throw new Exception('error: userId is required');
		$userId = intval($userId);
		$url = self::API_BASE.'subs-user-last/'.$userId;
		if(!empty($count))
			$url .= '/'.$count;
		$curl = new Curl('uhunt.cookie');
		$data = $curl->get($url);
		$subs_user = json_decode($data['html'], true) ?: array();
		foreach($subs_user['subs'] as &$sub){
			$sub[2] = self::$MAP_VERDICT[ $sub[2] ] ?: 'qu';
			$sub = array_combine(self::$MAP_SUB_KEY, $sub);
		}
		unset($sub);
		return $subs_user;
	}

	/**
	 * [ranklist description]
	 * @param  [type]  $userId [description]
	 * @param  integer $nabove [description]
	 * @param  integer $nbelow [description]
	 * @return array (
	 *   0 =>
	 *   array (
	 *     'rank' => 6372, // 排行
	 *     'old' => 0, // 如果非 0 代表該使用者需要 migrate 舊資料
	 *     'userid' => 4530, // 使用者 ID
	 *     'name' => 'taichunmin', // 使用者名稱
	 *     'username' => 'taichunmin', // 使用者帳號
	 *     'ac' => 120, // AC 題數
	 *     'nos' => 353, // 累計上傳數量
	 *     'activity' => // 活躍情形
	 *     array (
	 *       0 => 0, // 2 天內 AC 題數
	 *       1 => 1, // 7 天內 AC 題數
	 *       2 => 1, // 31 天內 AC 題數
	 *       3 => 1, // 3 月內 AC 題數
	 *       4 => 1, // 1 年內 AC 題數
	 *     ),
	 *   ),
	 * )
	 */
	public function ranklist($userId, $nabove=0, $nbelow=0)
	{
		if(empty($userId))
			throw new Exception('error: userId is required');
		$url = sprintf(self::API_BASE.'ranklist/%s/%s/%s', $userId, $nabove, $nbelow);
		$curl = new Curl('uhunt.cookie');
		$data = $curl->get($url);
		$ranklists = json_decode($data['html'], true) ?: array();
		return $ranklists;
	}
}
