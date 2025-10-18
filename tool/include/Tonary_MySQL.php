<?php
// Tonary_MySQL
// Copyright (C) 2013-2014 Tonary Management System, Inc. All Rights Reserved.

class Tonary_MySQL{

	public $my_con = null;

	public function __construct(array $options = array('real'=>false)){
		$db_host = Config::$db_host;
		$db_user = Config::$db_user;
		$db_pass = Config::$db_pass;
		$db_dbname = Config::$db_dbname;
		if ($options['real']){
			$db_host = Config::$real_db_host;
			$db_user = Config::$real_db_user;
			$db_pass = Config::$real_db_pass;
			$db_dbname = Config::$real_db_dbname;
		}
		if ($db_host == '' or $db_user == '' or $db_pass == '' or $db_dbname == ''){
			return;
		}
		$this->my_con = mysqli_connect($db_host, $db_user, $db_pass);
		if (!$this->my_con){
			die('error (mysqli_connect)');
		}
		if (!mysqli_set_charset($this->my_con, 'utf8')) {
			die('error (mysqli_set_charset)');
		}
		if(!mysqli_select_db($this->my_con, $db_dbname)){
			die('error (mysqli_select_db)');
		}
	}

	public function __destruct(){
		if ($this->my_con){
			mysqli_close($this->my_con);
		}
	}

	public function query($sql){
		return mysqli_query($this->my_con, $sql);
	}

	public function multi_query($sql_e){
		$rtn = '';
		if ($sql_e != ''){
			if (mysqli_multi_query($this->my_con, $sql_e)) {
				do {
				} while (mysqli_next_result($this->my_con));
			}
			$rtn .= mysqli_error($this->my_con);
		}
		return $rtn;
	}

	public function fetch_array($rs, $resulttype = MYSQL_ASSOC){
		return mysqli_fetch_array($rs, $resulttype);
	}

	public function free_result($rs){
		mysqli_free_result($rs);
	}

	public function string($value, $nullstr='', $qout='"'){
		if ($value == ''){
			$rtn = $qout.$nullstr.$qout;
		}else{
			$rtn = $qout.mysqli_real_escape_string($this->my_con, $value).$qout;
		}
		return $rtn;
	}

	public function numeric($value, $nullstr='null'){
		if ($value === ''){
			$rtn = $nullstr;
		}else if (is_numeric($value)){
			$rtn = mysqli_real_escape_string($this->my_con, $value);
		}else{
			$rtn = $nullstr;
		}
		return $rtn;
	}

}
