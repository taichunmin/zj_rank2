<?php

// http://zerojudge.tw/UserStatistic?account=loky

namespace ZJR2;
use \SplMinHeap;
use \Exception;

class UVaUser{
	protected $uid = null, $api, $profile, $pid_key, $pid_stats, $sid2pid, $sids, $sorted_sids, $fetched_subs, $first_ac_sbt;

	function __construct($opt = array())
	{
		$this->api = new UhuntApi;
		$this->detect_uid($opt);
	}

	public function clear()
	{
		$this->fetched_subs = false;
		$this->first_ac_sbt = [];
		$this->name = false;
		$this->pid_key = [];
		$this->pid_stats = [];
		$this->profile = null;
		$this->sid2pid = [];
		$this->sids = [];
		$this->sorted_sids = true;
		$this->uname = false;
		return $this;
	}

	public function detect_uid($opt)
	{
		if(!empty($opt['uid']) && intval($opt['uid'])>0)
			$this->uid = intval($opt['uid']);
		elseif(!empty($opt['uname']) && is_string($opt['uname']))
			$this->uid = $this->api->uname2uid($opt['uname']);
		$this->clear(); // reset data
		return $this;
	}

	public function uname(){
		if($this->uid < 1)
			throw new Exception('uid is required.');
		$ret = $this->api->subs_user_last($this->uid, 0);
		if(empty($ret['uname']) || "--- ? ---" === $ret['uname'])
			throw new Exception('uid2uname() error.');
		return $ret['uname'];
	}

	public function profile()
	{
		if(is_null($this->profile)){
			if($this->uid < 1)
				throw new Exception('uid is required.');
			$tmp = $this->api->ranklist($this->uid);
			$this->profile = $tmp[0];
			if($this->uid != $this->profile['userid'])
				throw new Exception('there are something wrong with profile.');
			if("--- ? ---" === $this->profile['username'])
				throw new Exception('uname fetched error.');
			$this->profile['uid'] = $this->profile['userid'];
			$this->profile['uname'] = $this->profile['username'];
			$this->profile['last_update'] = time();
		}
		return $this->profile;
	}

	public function add_sub($s)
	{
		unset($this->pid_stats[ $s['pid'] ]);
		$this->pid_key[ $s['pid'] ][ $s['sid'] ] = $s;
		if(empty($this->sid2pid[ $s['sid'] ])){
			if($s['sid'] < end($this->sids))
				$this->sorted_sids = false;
			$this->sids[] = $s['sid'];
			$this->sid2pid[ $s['sid'] ] = $s['pid'];
		}
		return $this;
	}

	public function each_pid()
	{
		$this->fetch_subs();
		$arg = func_get_args();
		if(is_callable($arg[0])){
			foreach(array_keys($this->pid_key) as $i)
				call_user_func($arg[0], $i);
		} elseif(!empty($this->pid_key[ $arg[0] ])) {
			foreach($this->pid_key[ $arg[0] ] as $s)
				call_user_func($arg[1], $s);
		}
		return $this;
	}

	public function stats($pid)
	{
		$this->fetch_subs();
		if(empty($this->pid_stats[$pid])){
			$st = [
				'pid' => $pid,
				'ac' => false,
				'nos' => 0,
				'ntry' => 0,
				'last_sbt' => ~ PHP_INT_MAX,
				'rank' => PHP_INT_MAX,
				'first_ac_sbt' => PHP_INT_MAX,
				'mrun' => PHP_INT_MAX,
				// 'mmem' => PHP_INT_MAX,
			];
			$p = &$this->pid_key[$pid];
			$sbtHeap = new SplMinHeap;
			if(empty($p))
				return $st;
			foreach($p as $s){
				$st['nos'] ++;
				$sbtHeap->insert($s['sbt']);
				$st['last_sbt'] = max($st['last_sbt'], $s['sbt']);
				if('ac' === $s['ver']){
					$st['ac'] = true;
					$st['first_ac_sbt'] = min($st['first_ac_sbt'], $s['sbt']);
					$st['mrun'] = min($st['mrun'], $s['run']);
					$st['rank'] = min($st['rank'], $s['rank']);
				}
			}
			if(!$st['ac'])
				$st['ntry'] = $st['nos'];
			else{
				foreach($sbtHeap as $sbt){
					if($sbt >= $st['first_ac_sbt'])
						break;
					$st['ntry']++;
				}
			}
			$this->pid_stats[$pid] = $st;
		}
		return $this->pid_stats[$pid];
	}

	public function stats_pnum($pnum)
	{
		return $this->stats($this->api->pnum2pid($pnum));
	}

	public function each_last_subs($n, $f){
		if(!$this->sorted_sids){
			$this->sorted_sids = true;
			natsort($this->sids);
		}
		$cnt = count($this->sids);
		for($i=0; $i<$n && $i<$cnt; $i++){
			$sid = $this->sids[$cnt-i-1];
			$pid = $this->sid2pid[$sid];
			call_user_func($f, $i, $this->pid_key[$pid][$sid]);
		}
	}

	public function substats_count()
	{
		$cnt = [];
		foreach($this->pid_key as $pid => &$p)
			foreach($p as $sid => &$s){
				if(!isset($cnt[$s['ver']]))
					$cnt[$s['ver']] = 0;
				$cnt[$s['ver']]++;
			}
		unset($p, $s);
		return $cnt;
	}

	public function fetch_subs($force = false)
	{
		if($this->uid < 1)
			throw new Exception('uid is required.');
		if($force || !$this->fetched_subs){
			if(!$this->sorted_sids){
				$this->sorted_sids = true;
				natsort($this->sids);
			}
			$subs_user = $this->api->subs_user($this->uid, end($this->sids) ?: null);
			foreach($subs_user['subs'] as $s)
				$this->add_sub($s);
			$this->fetched_subs = true;
		}
		return $this;
	}

	public function recent_ac($n = 3)
	{
		$this->fetch_subs();
		$pid_sbt = [];
		foreach($this->pid_key as $pid => &$p)
			foreach($p as $sid => &$s){
				if('ac' !== $s['ver'])
					continue;
				if(!isset($pid_sbt[ $pid ]))
					$pid_sbt[ $pid ] = PHP_INT_MAX;
				$pid_sbt[ $pid ] = min($pid_sbt[ $pid ], $s['sbt']);
			}
		unset($p, $s);
		arsort($pid_sbt);
		$recent_ac_pid = array_keys(array_slice($pid_sbt, 0, $n, true));
		$recent_ac_pnum = array_map(array($this->api, 'pid2pnum'), $recent_ac_pid);
		return array_map(null, $recent_ac_pid, $recent_ac_pnum);
	}

	public function nos()
	{
		return count($this->sids);
	}
}
