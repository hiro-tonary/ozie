<?php
// Tonary_Ymd
// Copyright (C) 2013-2014 Tonary Management System, Inc. All Rights Reserved.

class Tonary_Ymd{
	public $result_text = '';
	public $result = false;
	private $_timestamp = null;

	public function __construct($ymd){
		$this->result = false;
		$timestamp = false;
		if(strptime($ymd, '%Y-%m-%d')){
			$this->result_text = 'Y-m-d';
			$timestamp = strtotime($ymd);
		}
		if(strptime($ymd, '%Y/%m/%d')){
			$this->result_text = 'Y/m/d';
			$timestamp = strtotime($ymd);
		}
		if ($timestamp !== false){
			$this->result_text .= '(timestamp)';
			$this->_timestamp = $timestamp;
			$this->result = true;
		}else if (is_numeric($ymd)){
			$this->result_text = 'YYYYMMDD';
			$yyyy = substr($ymd,0,4);
			$mm = substr($ymd,4,2);
			$dd = substr($ymd,6,2);
			$ymd = date('Y-m-d', mktime(0,0,0,$mm,$dd,$yyyy));
			$this->_timestamp = strtotime($ymd);
			$this->result = true;
		}else{
			$temps = explode('/', $ymd);
			if (count($temps) > 2){
				if (is_numeric($temps[0]) && is_numeric($temps[1]) && is_numeric($temps[2])){
					$this->result_text = 'Y/M/D';
					$ymd = date('Y-m-d', mktime(0,0,0,$temps[1],$temps[2],$temps[0]));
					$this->_timestamp = strtotime($ymd);
					$this->result = true;
				}
			}else if (count($temps) > 1){
				$year = date('Y');
				if (is_numeric($temps[0]) && is_numeric($temps[1])){
					$this->result_text = 'M/D';
					$ymd = date('Y-m-d', mktime(0,0,0,$temps[0],$temps[1],$year));
					$this->_timestamp = strtotime($ymd);
					$this->result = true;
				}
			}
		}
	}

	public function getParamYyyymmdd(){
		if ($this->_timestamp == null){
			return '';
		}
		return date('Y-m-d', $this->_timestamp);
	}

	public function getYyyymmdd(){
		if ($this->_timestamp == null){
			return '';
		}
		return date('Y/m/d', $this->_timestamp);
	}

	public function getFileYyyymmdd(){
		if ($this->_timestamp == null){
			return '';
		}
		return date('Ymd', $this->_timestamp);
	}

	public static function day_diff($date1, $date2) {
		$timestamp1 = strtotime($date1);
		$timestamp2 = strtotime($date2);
		$seconddiff = abs($timestamp2 - $timestamp1);
		$daydiff = $seconddiff / (60 * 60 * 24);
		return $daydiff;
	}

}
?>
