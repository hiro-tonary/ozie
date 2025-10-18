<?php
// Copyright (C) 2013-2014 Tonary Management System, Inc. All Rights Reserved.

class Tonary_Posted{
	public static function parse_posted_toplainarray($prefix=''){
		$datas = array();
		foreach ($_POST as $key => $value){
			$flg = false;
			if ($prefix == ''){
				$flg = true;
			}else{
				if (preg_match('/^'.$prefix.'/', $key)){
					$flg = true;
				}
			}
			if ($flg){
				$name = str_replace($prefix, '', $key);
				if ($name == ''){
					$name = 'tmp';
				}
				$datas[$name] = $value;
			}
		}
		return $datas;
	}

	public static function parse_posted_toarray($prefix=''){
		$datas = array();
		foreach ($_POST as $key => $value){
			$flg = false;
			if ($prefix == ''){
				$flg = true;
			}else{
				if (preg_match('/^'.$prefix.'/', $key)){
					$flg = true;
				}
			}
			if ($flg){
				$tmps = explode(':', $key);
				$name = str_replace($prefix, '', $tmps[0]);
				if ($name == ''){
					$name = 'tmp';
				}
				$datas = self::sub_parse_toarray($datas, $tmps, $name, $value, 0);
			}
		}
		return $datas;
	}

	private static function sub_parse_toarray($d, $tmps, $name, $value, $level){
		$level++;
		if ($level < count($tmps)){
			$num = intval($tmps[($level)]);
			$d[$num] = self::sub_parse_toarray($d[$num], $tmps, $name, $value, $level);
		}else{
			$d[$name] = $value;
		}
		return $d;
	}

	public static function parse_posted($prefix=''){
		$datas = new stdClass();
		foreach ($_POST as $key => $value){
			$flg = false;
			if ($prefix == ''){
				$flg = true;
			}else{
				if (preg_match('/^'.$prefix.'/', $key)){
					$flg = true;
				}
			}
			if ($flg){
				$tmps = explode(':', $key);
				$name = str_replace($prefix, '', $tmps[0]);
				if ($name == ''){
					$name = 'tmp';
				}
				$datas = self::sub_parse($datas, $tmps, $name, $value, 0);
			}
		}
		return $datas;
	}

	private static function sub_parse($d, $tmps, $name, $value, $level){
		$level++;
		if ($level < count($tmps)){
			$num = intval($tmps[($level)]);
			if (!array_key_exists('lists', $d)){
				$d->lists = array();
			}
			if (!$d->lists[$num] instanceof stdClass){
				$d->lists[$num] = new stdClass();
			}
			$d->lists[$num] = self::sub_parse($d->lists[$num], $tmps, $name, $value, $level);
		}else{
			$d->$name = $value;
		}
		return $d;
	}

}
