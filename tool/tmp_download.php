<?php
ini_set('display_errors', 1);
if (!mb_language('uni')){
	die('language error');
}
if (!mb_internal_encoding('UTF-8')){
	die('internal_encoding error');
}
if (!mb_http_output('UTF-8')){
	die('http_output error');
}
require_once('env/env.php');
require_once(Config::$global_include_path.'Tonary.php');
require_once(Config::$global_include_path.'Tonary_NE.php');

$error_flg = false;
$nextengine = null;
$login_user = array();
$token = '';
try {
	Tonary::session_start();
	Tonary::write_accesslog();
	$nextengine = new Tonary_NE();
	$login_user = $nextengine->login();
	$token = $nextengine->token;

	if($_SERVER['REQUEST_METHOD'] == 'POST'){
		$session_token = Tonary::get_session('token', Tonary::CRLF_CUT, '');
		$posted_token = Tonary::get_post('token', Tonary::CRLF_CUT, '');
		if ($posted_token == '' or $posted_token != $session_token){
			header('Content-type: text/html; charset=UTF-8');
			Tonary::write_errorlog(
				'ERROR TOKEN posted_token:'.$posted_token.' session_token:'.$session_token
			);
			die('ERROR TOKEN posted_token:'.$posted_token.' session_token:'.$session_token);
		}
	}else{
		header('Content-type: text/html; charset=UTF-8');
		Tonary::write_errorlog('ERROR NOT POST');
		die('ERROR NOT POST');
	}
	$subdir = Tonary::get_post('subdir', Tonary::CRLF_CUT, '');
	$filename = Tonary::get_post('filename', Tonary::CRLF_CUT, '');
	$filepath = Config::$tmpdir_path.$subdir.'/'.$filename;

} catch (Exception $e) {
	header('Content-type: text/html; charset=UTF-8');
	Tonary::write_errorlog('Exception: '. $e->getMessage());
	die('Exception: '. $e->getMessage());
}
try {
	header('Content-Type: application/octet-stream');
	header('Content-Disposition: attachment; filename="'.$filename.'"');
	readfile($filepath);
} catch (Exception $e) {
	Tonary::write_errorlog('Exception: '. $e->getMessage());
	print 'Exception: '.$e->getMessage();
}
?>
