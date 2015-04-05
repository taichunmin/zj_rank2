<?php

namespace ZJR2;

class ProgressBar
{
	private $max = 1;
	private $cnt = 0;
	private $molecular = 0;	// 分子
	private $ng;			// 下一個目標
	public $last_echo_length=0;
	public $printFormat;
	public static $_display = true;	// 用來關閉輸出
	function __construct($new_max = 1, $f = 'Running: %d%%')
	{
		if( get($new_max, 0) > $this->max )
			$this->max = $new_max;
		$this->ng = $this->cnt = $this->molecular = 0;
		$this->setf($f);
		$this->_nextGoalCompute();
	}
	private function _nextGoalCompute()
	{
		if( $this->cnt + 1 >= $this->max )
			$this->ng = $this->max;
		else $this->ng = floor($this->max*($this->molecular+1)/100.0);
	}
	public function c($step = 1)
	{
		$this->cnt += $step;
		if($this->cnt >= $this->ng)
		{
			$this->molecular = round( $this->cnt / $this->max * 100.0 );
			$this->_nextGoalCompute();
			$this->p();
		}
		return $this;
	}
	public function g()
	{
		return $this->molecular;
	}
	public function i()
	{
		return $this->cnt;
	}
	public function p($f=null)
	{
		if(isset($f))$this->setf($f);
		if( !self::$_display || empty($this->printFormat) ) return;
		$this->cls();
		$output = sprintf($this->printFormat."\r",$this->molecular);
		$this->last_echo_length = strlen($output) -1;
		echo $output;
		return $this;
	}
	public function cls()
	{
		if($this->last_echo_length > 0){
			echo "\r".str_repeat(' ', $this->last_echo_length)."\r";
			$this->last_echo_length = 0;
		}
		return $this;
	}
	public function setf($f)
	{
		$this->printFormat = ' '.str_replace(array("\n","\r"),'',$f);
		return $this;
	}
	public function debug()
	{
		var_dump($this);
	}
}

/*

$pb = new Progress_bar(2000);
$pb->p();
for($i=0; $i<2000; $i++)
	$pb->c();
$pb->cls();

 */
