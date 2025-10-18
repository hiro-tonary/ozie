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
require_once('../env/env.php');
require_once(Config::$global_include_path.'Tonary.php');

try {
	Tonary::session_start();
	Tonary::write_accesslog();

	if($_SERVER['REQUEST_METHOD'] == 'POST'){
		$session_token = Tonary::get_session('token', Tonary::CRLF_CUT, '');
		$posted_token = Tonary::get_post('token', Tonary::CRLF_CUT, '');
		if ($posted_token == '' or $posted_token != $session_token){
			die('ERROR TOKEN posted_token:'.$posted_token.' session_token:'.$session_token);
		}
	}else{
		die('ERROR NOT POST');
	}
	if (isset($_POST['subdir'])){
		$subdir = preg_replace('/[\x00-\x1f\x7f]/', '', $_POST['subdir']);
	}
	if (isset($_POST['filename'])){
		$filename = preg_replace('/[\x00-\x1f\x7f]/', '', $_POST['filename']);
	}
	$filepath = Config::$tmpdir_path.$subdir.'/'.$filename;

} catch (Exception $e) {
	Tonary::write_errorlog('Exception: '. $e->getMessage());
	die('Exception: '. $e->getMessage());
}
try {
	header('Content-Type: text/comma-separated-values; name="'.$filename.'"');
	header('Content-Disposition: attachment; filename="'.$filename.'"');
	readfile($filepath);
} catch (Exception $e) {
	Tonary::write_errorlog('Exception: '. $e->getMessage());
	print 'Exception: '.$e->getMessage();
}
?>
