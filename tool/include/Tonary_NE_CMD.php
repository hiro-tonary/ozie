<?php
// Tonary_NE_CMD
// Copyright (C) 2013-2014 Tonary Management System, Inc. All Rights Reserved.

require_once(dirname(__FILE__).'/Tonary.php');
require_once(dirname(__FILE__).'/Tonary_iLogin.php');
require_once(dirname(__FILE__).'/neApiClient.php');

class Tonary_NE_CMD extends neApiClient{

	public $login_user;

	public function write_errorlog($message = null){
		Tonary::write_errorlog($this->login_user['name'].' '.$message);
	}

	public function write_inspectlog($message = null){
		Tonary::write_inspectlog($this->login_user['user_name'].' '.$message);
	}

}
