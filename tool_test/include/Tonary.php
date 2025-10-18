<?php
// Tonary
// Copyright (C) 2013-2014 Tonary Management System, Inc. All Rights Reserved.

class Tonary{

	const CRLF_USE = true;
	const CRLF_CUT = false;

	public static function session_start(){
		if(!isset($_SESSION)){
//			session_save_path(Config::$tmpdir_path.'session');
			session_save_path(Config::$session_path);
			session_start();
		}
	}

	public static function suppress($value){
		return str_replace(array(' ','　','	',"\n"), '', $value);
	}

	public static function get_session($name, $crlf=self::CRLF_CUT, $none_value=''){
		$tmp = $none_value;
		if (isset($_SESSION[$name])){
			if ($crlf){
				$tmp = preg_replace('/[\x00-\x08\x0c-\x1f\x7f]/', '', $_SESSION[$name]);
			}else{
				$tmp = preg_replace('/[\x00-\x1f\x7f]/', '', $_SESSION[$name]);
				$tmp = preg_replace('/^[ 　]+/u', '', $tmp);
				$tmp = preg_replace('/[ 　]+$/u', '', $tmp);
			}
		}
		return $tmp;
	}

	public static function get_get($name, $crlf=self::CRLF_CUT, $none_value=''){
		$tmp = $none_value;
		if (isset($_GET[$name])){
			if ($crlf){
				$tmp = preg_replace('/[\x00-\x08\x0c-\x1f\x7f]/', '', $_GET[$name]);
			}else{
				$tmp = preg_replace('/[\x00-\x1f\x7f]/', '', $_GET[$name]);
				$tmp = preg_replace('/^[ 　]+/u', '', $tmp);
				$tmp = preg_replace('/[ 　]+$/u', '', $tmp);
			}
		}
		return $tmp;
	}

	public static function get_getpost($name, $crlf=self::CRLF_CUT, $none_value=''){
		$tmp = $none_value;
		if (isset($_GET[$name])){
			if ($crlf){
				$tmp = preg_replace('/[\x00-\x08\x0c-\x1f\x7f]/', '', $_GET[$name]);
			}else{
				$tmp = preg_replace('/[\x00-\x1f\x7f]/', '', $_GET[$name]);
				$tmp = preg_replace('/^[ 　]+/u', '', $tmp);
				$tmp = preg_replace('/[ 　]+$/u', '', $tmp);
			}
		}else if (isset($_POST[$name])){
			if ($crlf){
				$tmp = preg_replace('/[\x00-\x08\x0c-\x1f\x7f]/', '', $_POST[$name]);
			}else{
				$tmp = preg_replace('/[\x00-\x1f\x7f]/', '', $_POST[$name]);
				$tmp = preg_replace('/^[ 　]+/u', '', $tmp);
				$tmp = preg_replace('/[ 　]+$/u', '', $tmp);
			}
		}
		return $tmp;
	}

	public static function get_post($name, $crlf=self::CRLF_CUT, $none_value=''){
		$tmp = $none_value;
		if (isset($_POST[$name])){
			if ($crlf){
				$tmp = preg_replace('/[\x00-\x08\x0c-\x1f\x7f]/', '', $_POST[$name]);
			}else{
				$tmp = preg_replace('/[\x00-\x1f\x7f]/', '', $_POST[$name]);
				$tmp = preg_replace('/^[ 　]+/u', '', $tmp);
				$tmp = preg_replace('/[ 　]+$/u', '', $tmp);
			}
		}
		return $tmp;
	}

	public static function get_post_array($name, array $none_value=array()){
		$tmp = $none_value;
		if (is_array($_POST[$name])){
			$tmp = $_POST[$name];
		}
		return $tmp;
	}

	public static function get_posted_inputs(&$input_datas, $i, $name){
		if (isset($_POST['input_'.$name.':'.$i])){
			$tmp = preg_replace('/[\x00-\x08\x0c-\x1f\x7f]/', '', $_POST['input_'.$name.':'.$i]);
			$tmp = preg_replace('/^[ 　]+/u', '', $tmp);
			$tmp = preg_replace('/[ 　]+$/u', '', $tmp);
			$input_datas[$i][$name] = $tmp;
		}
		return $input_datas[$i][$name];
	}

	public static function remove_directory($dir) {
		if ($handle = opendir($dir)) {
			while (false !== ($item = readdir($handle))) {
				if ($item != '.' && $item != '..') {
					if (is_dir($dir.$item)) {
						$rtn = self::remove_directory($dir.$item.'/');
						if ($rtn != ''){
							return $rtn;
						}
					} else {
						if (false === unlink($dir.$item)){
							return 'unlink error '.$dir.$item;
						}
					}
				}
			}
			closedir($handle);
			if (false === rmdir($dir)){
				return 'rmdir error '.$dir;
			}
		}else{
			return 'opendir error '.$dir;
		}
		return '';
	}

	public static function copy_files($from_dir, $to_dir, $type=null){
		$list = scandir($from_dir);
		if ($list === false){
			return 'scandir error '.$from_dir;
		}
		foreach($list as $file){
			if($file == '.' || $file == '..'){
				continue;
			} else if(is_file($from_dir.$file)){
				$copy_flg = true;
				if ($type == 'jpg'){
					if (preg_match('/\.jpg$/', $file) === 0){
						$copy_flg = false;
					}
				}
				if ($copy_flg){
					if (copy($from_dir.$file, $to_dir.$file) === false){
						return 'copy error '.$file;
					}
				}
			} else if(is_dir($from_dir . $file)) {
				$rtn = self::copy_files($from_dir.$file.'/', $to_dir);
				if ($rtn != ''){
					return $rtn;
				}
			}
		}
		return '';
	}

	public static function byte($text, $encoding = null){
		if (is_null($encoding)) {
			$encoding = mb_internal_encoding();
		}
		return strlen(mb_convert_encoding($text, 'SJIS-win', $encoding));
	}

	public static function tag_sort_option($sort_field, $sort_target, $sort_forward){
		$rtn = '<span class="sort_option" sort="';
		$rtn .= $sort_field;
		$rtn .= '">';
		if ($sort_target == $sort_field){
			$rtn .= $sort_forward;
		}else{
			$rtn .= '＊';
		}
		$rtn .= '</span>';
		return $rtn;
	}

	public static function tag_sort_option_triangle($sort_field, $sort_target, $sort_forward){
		$rtn = '<span class="sort_option" sort="';
		$rtn .= $sort_field;
		$rtn .= '" forward="1">';
		if ($sort_forward == 1 and $sort_target == $sort_field){
			$rtn .= '▼';
		}else{
			$rtn .= '▽';
		}
		$rtn .= '</span>';
		$rtn .= '<span class="sort_option" sort="';
		$rtn .= $sort_field;
		$rtn .= '" forward="">';
		if ($sort_forward != 1 and $sort_target == $sort_field){
			$rtn .= '▲';
		}else{
			$rtn .= '△';
		}
		$rtn .= '</span>';
		return $rtn;
	}

	public static function html($value,$nullstr='',$crlf=null){
		$rtn = '';
		if ($value == ''){
			$rtn = $nullstr;
		}else{
			$rtn = htmlspecialchars($value,ENT_QUOTES,'UTF-8');
			if ($crlf != null){
				$rtn = preg_replace("/\r\n|\r|\n/", $crlf, $rtn);
			}
		}
		return $rtn;
	}

	public static function number($value,$nullstr=''){
		$rtn = '';
		if ($value == ''){
			$rtn = $nullstr;
		}else{
			$rtn = number_format($value);
		}
		return $rtn;
	}

	public static function ratenumber($value,$nullstr='',$prefix='',$sufix=''){
		$rtn = '';
		if ($value == ''){
			$rtn = $nullstr;
		}else{
			$rtn = $prefix.sprintf('%0.1f', $value).$sufix;
		}
		return $rtn;
	}

	public static function sjis($value){
		$rtn = '';
		if ($value == ''){
			$rtn = '';
		}else{
			$rtn = mb_convert_encoding($value, 'SJIS-win', 'UTF-8');
		}
		return $rtn;
	}

	public static function write_accesslog($message = null){
		$fp = fopen(Config::$tmpdir_path.'access.log', 'a');
//		$fp = fopen(Config::$logfile_path, 'a');
		date_default_timezone_set('Asia/Tokyo');
		$time = microtime();
		$time_list = explode(' ',$time);
		$time_micro = explode('.',$time_list[0]);
		$od_ms = substr($time_micro[1],0,3);
		if ($message == null){
			fwrite($fp, "\n");
		}
		fwrite($fp, date('Y/m/d H:i:s').'-'.$od_ms);
		if ($message == null){
			fwrite($fp, ' '.$_SERVER['REMOTE_ADDR'].' '.$_SERVER['PHP_SELF']);
			if ($_SERVER['QUERY_STRING'] != ''){
				fwrite($fp, '?'.$_SERVER['QUERY_STRING']);
			}
		}else{
			fwrite($fp, ' '.$message);
		}
		fwrite($fp, "\n");
		fclose($fp);
	}

	public static function write_errorlog($message = null){
		$fp = fopen(Config::$tmpdir_path.'error.log', 'a');
		date_default_timezone_set('Asia/Tokyo');
		$time = microtime();
		$time_list = explode(' ',$time);
		$time_micro = explode('.',$time_list[0]);
		$od_ms = substr($time_micro[1],0,3);
		if ($message == null){
			fwrite($fp, "\n");
		}
		fwrite($fp, date('Y/m/d H:i:s').'-'.$od_ms);
		fwrite($fp, ' '.$_SERVER['REMOTE_ADDR'].' '.$_SERVER['PHP_SELF']);
		if ($message != null){
			fwrite($fp, ' '.$message);
		}
		fwrite($fp, "\n");
		fclose($fp);
	}

	public static function write_inspectlog($message = null){
		$fp = fopen(Config::$tmpdir_path.'inspect.log', 'a');
		date_default_timezone_set('Asia/Tokyo');
		$time = microtime();
		$time_list = explode(' ',$time);
		$time_micro = explode('.',$time_list[0]);
		$od_ms = substr($time_micro[1],0,3);
		if ($message == null){
			fwrite($fp, "\n");
		}
		fwrite($fp, date('Y/m/d H:i:s').'-'.$od_ms);
		fwrite($fp, ' '.$_SERVER['REMOTE_ADDR'].' '.$_SERVER['PHP_SELF']);
		if ($message != null){
			fwrite($fp, ' '.$message);
		}
		fwrite($fp, "\n");
		fclose($fp);
	}

	public static function write_executelog($message = null){
		$fp = fopen(Config::$tmpdir_path.'execute.log', 'a');
		date_default_timezone_set('Asia/Tokyo');
		$time = microtime();
		$time_list = explode(' ',$time);
		$time_micro = explode('.',$time_list[0]);
		$od_ms = substr($time_micro[1],0,3);
		if ($message == null){
			fwrite($fp, "\n");
		}
		fwrite($fp, date('Y/m/d H:i:s').'-'.$od_ms);
		fwrite($fp, ' '.$_SERVER['REMOTE_ADDR'].' '.$_SERVER['PHP_SELF']);
		if ($message != null){
			fwrite($fp, ' '.$message);
		}
		fwrite($fp, "\n");
		fclose($fp);
	}

	public static function print_debug($message){
		if (Config::$print_debug){
			print '<span class="debug">';
			print $message;
			print '</span>';
		}
		if (Config::$log_debug){
			$fp = fopen(Config::$tmpdir_path.'debug.log', 'a');
			date_default_timezone_set('Asia/Tokyo');
			$time = microtime();
			$time_list = explode(' ',$time);
			$time_micro = explode('.',$time_list[0]);
			$od_ms = substr($time_micro[1],0,3);
			fwrite($fp, date('Y/m/d H:i:s').'-'.$od_ms);
			fwrite($fp, ' '.$_SERVER['REMOTE_ADDR'].' '.$_SERVER['PHP_SELF']);
			fwrite($fp, ' '.$message);
			fwrite($fp, "\n");
			fclose($fp);
		}
	}

	public static function print_error($message){
		if (Config::$print_error){
			print '<span class="error">';
			print $message;
			print '</span>';
		}
		if (Config::$log_error){
			$fp = fopen(Config::$tmpdir_path.'debug.log', 'a');
			date_default_timezone_set('Asia/Tokyo');
			$time = microtime();
			$time_list = explode(' ',$time);
			$time_micro = explode('.',$time_list[0]);
			$od_ms = substr($time_micro[1],0,3);
			fwrite($fp, date('Y/m/d H:i:s').'-'.$od_ms);
			fwrite($fp, ' '.$_SERVER['REMOTE_ADDR'].' '.$_SERVER['PHP_SELF']);
			fwrite($fp, ' '.$message);
			fwrite($fp, "\n");
			fclose($fp);
		}
	}

	public static function print_progress($message){
		if (Config::$print_progress){
			print '<span class="message">';
			print $message;
			print '</span>';
		}
		if (Config::$log_progress){
			$fp = fopen(Config::$tmpdir_path.'debug.log', 'a');
			date_default_timezone_set('Asia/Tokyo');
			$time = microtime();
			$time_list = explode(' ',$time);
			$time_micro = explode('.',$time_list[0]);
			$od_ms = substr($time_micro[1],0,3);
			fwrite($fp, date('Y/m/d H:i:s').'-'.$od_ms);
			fwrite($fp, ' '.$_SERVER['REMOTE_ADDR'].' '.$_SERVER['PHP_SELF']);
			fwrite($fp, ' '.$message);
			fwrite($fp, "\n");
			fclose($fp);
		}
	}

	//分数加算
	public static function add_fraction(array $m, array $n){
		$sum = $m[0];
		$multiple = $m[1];
		$numerator = $n[0];
		$denominator = $n[1];

		$lcm = self::lcm($multiple, $denominator);
		if ($denominator < $lcm){
			$numerator = ($numerator * $lcm) / $denominator;
		}
		if ($multiple < $lcm){
			$sum = ($sum * $lcm) / $multiple;
			$multiple = $lcm;
		}
		$sum += $numerator;
		return array($sum, $multiple);
	}

	//最大公約数
	public static function gcd($m, $n){
		while (($m % $n) != 0){
			$temp = n;
			$n = $m % $n;
			$m = $temp;
		}
		return $n;
	}

	//最小公倍数
	public static function lcm($m, $n){
		return ($m * $n) / self::gcd($m, $n); 
	}

}
