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
require_once(Config::$global_include_path.'Tonary_MySQL.php');

$error_flg = false;
$nextengine = null;
$mysql = null;
$login_user = array();
$token = '';
try {
	Tonary::session_start();
	Tonary::write_accesslog();
	$nextengine = new Tonary_NE();
	$login_user = $nextengine->login();
	$token = $nextengine->token;
	$mysql = new Tonary_MySQL();

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
<title>NE定期処理用アクセストークンを設定｜<?=$login_user['system_name']?></title>
<meta name="viewport" content="width=device-width">
<link rel="stylesheet" href="/cssjs/reset2014.css">
<link rel="stylesheet" href="/cssjs/tonary.css">
<link rel="stylesheet" href="<?=Config::$local_css?>">
</head>
<body>
<h1>NE定期処理用アクセストークンを設定｜<?=$login_user['system_name']?></h1>
<div class="breadcrumb">
<a href="index.php">トップ</a>
 &gt; <label>NE定期処理用アクセストークンを設定</label>
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
	$sql = 'select *';
	$sql .= ' from member';
	$sql .= ' where member_id = ';
	$sql .= $mysql->string(Config::$member_id);
	if (!$rs = $mysql->query($sql)){
		die('SQL ERROR '.$sql);
	}
	$row = $mysql->fetch_array($rs);
	if ($row){
		print '<div>';
		print $row['member_id'];
		print ' ';
		print $row['member_name'];
		print ' のNE定期処理用アクセストークンを設定します。';
		print "</div>\n";
	}else{
		$error_flg = true;
		print '<div class="error">';
		print Tonary::html(Config::$member_id);
		print ' は登録されていません';
		print "</div>\n";
	}
	$mysql->free_result($rs);
	if ($error_flg == false){
		$sql_e .= 'update member set';
		$sql_e .= ' ne_company_name = '.$mysql->string($login_user['company_name']);
		$sql_e .= ',ne_company_type_id = '.$mysql->string($login_user['company_type_id']);
		$sql_e .= ',ne_company_ip_address = '.$mysql->string($login_user['company_ip_address']);
		$sql_e .= ',ne_company_id = '.$mysql->string($login_user['company_id']);
		$sql_e .= ',ne_company_ne_id = '.$mysql->string($login_user['company_ne_id']);
		$sql_e .= ',ne_company_host = '.$mysql->string($login_user['company_host']);
		$sql_e .= ',ne_pic_id = '.$mysql->string($login_user['pic_id']);
		$sql_e .= ',ne_pic_name = '.$mysql->string($login_user['pic_name']);
		$sql_e .= ',ne_uid = '.$mysql->string($login_user['uid']);
		$sql_e .= ',ne_pic_ne_id = '.$mysql->string($login_user['pic_ne_id']);
		$sql_e .= ',ne_access_token = '.$mysql->string($nextengine->ne_access_token);
		$sql_e .= ',ne_refresh_token = '.$mysql->string($nextengine->ne_refresh_token);
		$sql_e .= ' where member_id = ';
		$sql_e .= $mysql->string(Config::$member_id);
		$sql_e .= ';'."\n";
		if ($mysql->multi_query($sql_e) <> ''){
			die('SQL ERROR '.$error.' : '.$sql_e);
		}
		$sql = 'select *';
		$sql .= ' from member';
		$sql .= ' where member_id = ';
		$sql .= $mysql->string(Config::$member_id);
		if (!$rs = $mysql->query($sql)){
			die('SQL ERROR '.$sql);
		}
		$row = $mysql->fetch_array($rs);
		if ($row){
			print '<div class="report">設定完了</div>'."\n";
			print '<pre class="small" style="color:silver;">';

			print 'ne_company_name: '.$row['ne_company_name']."\n";
			print 'ne_company_type_id: '.$row['ne_company_type_id']."\n";
			print 'ne_company_ip_address: '.$row['ne_company_ip_address']."\n";
			print 'ne_company_id: '.$row['ne_company_id']."\n";
			print 'ne_company_ne_id: '.$row['ne_company_ne_id']."\n";
			print 'ne_company_host: '.$row['ne_company_host']."\n";
			print "\n";
			print 'ne_pic_id: '.$row['ne_pic_id']."\n";
			print 'ne_pic_name: '.$row['ne_pic_name']."\n";
			print 'ne_pic_mail_address: '.$row['ne_pic_mail_address']."\n";
			print 'ne_uid: '.$row['ne_uid']."\n";
			print 'ne_pic_ne_id: '.$row['ne_pic_ne_id']."\n";
			print "\n";
			print 'ne_access_token: '.$row['ne_access_token']."\n";
			print 'ne_refresh_token: '.$row['ne_refresh_token']."\n";

			print "</pre>\n";
		}
	}
?>
</div>
<?php
} catch (Exception $e) {
	die("Exception: ". $e->getMessage());
}
?>
</body>
</html>
