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

} catch (Exception $e) {
	die('Exception: '. $e->getMessage());
}
try{
	//クリックジャッキング対策
	header('X-Frame-Options:DENY');
	header('Content-type: text/html; charset=UTF-8');
	header('Expires: Sat, 26 Jul 1997 05:00:00 GMT');
	header('Last-Modified: ' . gmdate( 'D, d M Y H:i:s' ) . ' GMT');
	header('Cache-Control: no-store, no-cache, must-revalidate');
	header('Cache-Control: post-check=0, pre-check=0', false);
	header('pragma: no-cache');
	ob_flush();
	flush();
?>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<meta name="robots" content="noindex,nofollow">
<meta http-equiv='Pragma' content='no-cache'>
<meta http-equiv='cache-control' content='no-cache'>
<meta http-equiv='expires' content='0'>
<title>NEバックアップ｜<?=$login_user['system_name']?></title>
<meta name="viewport" content="width=device-width">
<link rel="stylesheet" href="/cssjs/reset2014.css">
<link rel="stylesheet" href="/cssjs/tonary.css">
<link rel="stylesheet" href="<?=Config::$local_css?>">
<script src="/cssjs/jquery.js"></script>
</head>
<body>
<h1>NEバックアップ｜<?=$login_user['system_name']?></h1>
<div class="breadcrumb">
<a href="index.php">トップ</a>
 &gt; <label>NEバックアップ</label>
<?php
	print '　<span class="login_user">';
	print $login_user['pic_id'];
	print ':';
	print $login_user['name'];
	print ' (';
	print $login_user['mail_address'];
	print ') さん ';
	print '</span>';
	print '<span style="color:silver;">';
	print $token;
	print '</span>';
?>
</div>
<div class="content">
<?php
	require_once(Config::$global_include_path.'NEBackup.php');
	$nebackup = new NEBackup();
	$nebackup->nextengine = $nextengine;
	$nebackup->print_flg = true;
	$rtn_msg = $nebackup->execute();
	print $rtn_msg;
?>
</div>
<form id="controlform" method="post" action="">
<input type="hidden" name="token" value="<?=$token?>">
<input type="hidden" name="subdir" id="subdir" value="">
<input type="hidden" name="filename" id="filename" value="">
</form>
<script>
$(function() {
	$(window).load(function(){
		$("#subdir").val("<?=$nebackup->get_subdir()?>");
		$("#filename").val("<?=$nebackup->get_filename()?>");
		var obj_form = $("#controlform");
		obj_form.attr("action","tmp_download.php");
		obj_form.submit();
	});
});
</script>
<?php
} catch (Exception $e) {
	die("Exception: ". $e->getMessage());
}
?>
</body>
</html>
