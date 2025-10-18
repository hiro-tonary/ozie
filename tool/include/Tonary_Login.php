<?php
// Login
// Copyright (C) 2013-2014 Tonary Management System, Inc. All Rights Reserved.

require_once(dirname(__FILE__).'/Tonary.php');

class Login{

	public $token;

	public function __construct($redirect_dir=null){

	}

	public function logout(){
		session_regenerate_id();
		$_SESSION = array();
	}

//	public function login($id=null, $pass=null){
	public function login($id=null, $pass=null, $top=false){
		$session_token = Tonary::get_session('token', Tonary::CRLF_CUT, '');
		$posted_token = Tonary::get_post('token', Tonary::CRLF_CUT, '');
		$id = Tonary::get_post('id', Tonary::CRLF_CUT, '');
		if ($id == ''){
			$id = Tonary::get_get('id', Tonary::CRLF_CUT, '');
		}
		if ($id == ''){
			$id = Tonary::get_session('id', Tonary::CRLF_CUT, '');
		}
		$pass = Tonary::get_post('pass', Tonary::CRLF_CUT, '');
		if ($pass == ''){
			$pass = Tonary::get_get('pass', Tonary::CRLF_CUT, '');
		}
		if ($pass == ''){
			$pass = Tonary::get_session('pass', Tonary::CRLF_CUT, '');
		}
		$login_user = null;
		foreach ($this->_user as $user){
			if ($user['id'] == $id and $user['pass'] == $pass){
				$login_user = $user;
				break;
			}
		}
/*
		if($_SERVER['REQUEST_METHOD'] == 'POST'){
			if ($posted_token == '' or $posted_token != $session_token){
				$login_user = null;
			}
		}
				$this->token = md5(uniqid(mt_rand(), TRUE));
*/
		if ($login_user == null){
			if ($top == false){
				header('Location: '.Config::$uri_dir);
				exit;
			}
		}else{
			session_regenerate_id();
			$_SESSION = array();
			$_SESSION['id'] = $id;
			$_SESSION['pass'] = $pass;
			$_SESSION['token'] = $token;
		}
		return $login_user;
	}

	private $_user = array(
		array('name'=>'山岸 弘基', 'id'=>'hiro', 'pass'=>'tonary', 'class'=>'admin'),
		array('name'=>'内堀 幸雄', 'id'=>'uchibori', 'pass'=>'ms', 'class'=>'ms'),
		array('name'=>'飯村 昌弘', 'id'=>'iimura', 'pass'=>'ms', 'class'=>'ms'),
		array('name'=>'細工屋 忠佳', 'id'=>'saikuya', 'pass'=>'ms', 'class'=>'ms'),
		array('name'=>'高須 仁', 'id'=>'takasu', 'pass'=>'ms', 'class'=>'ms')
	);

}
