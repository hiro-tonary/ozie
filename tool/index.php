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

$nextengine = null;
$mysql = null;
$login_user = array();

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
<title><?=$login_user['system_name']?></title>
<meta name="viewport" content="width=device-width">
<link rel="stylesheet" href="/cssjs/reset2014.css">
<link rel="stylesheet" href="/cssjs/tonary.css">
<link rel="stylesheet" href="<?=Config::$local_css?>">
</head>
<body>
<h1><?=$login_user['system_name']?></h1>
<div class="breadcrumb">
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
<div class="content" style="margin-bottom:36px;">
<div>
<a href="download/csv_for_clear.php">クリア取込用売上げデータをダウンロードする</a>
</div>
<hr>
<div>
<a href="ne_routine_exec.php">受注カスタム自動処理を手動で実行する</a>
</div>
<hr>
<div>
<a href="ne_goods_list.php">ネクストエンジン商品リストを一覧表示する</a>
</div>
<div>
<a href="ne_receive_order_list.php">ネクストエンジン受注伝票を一覧表示する</a>
</div>
<hr>
<div class="developer">
↓システム管理者向けの機能です。
</div>
<div>
<a href="ne_backup_list.php">ネクストエンジンのマスタバックアップを一覧表示する</a>
</div>
<hr>
<div>
<a href="ne_backup.php">ネクストエンジンのマスタをバックアップする</a>
</div>
<div>
<a href="set_ne_token.php">ネクストエンジン定期処理用アクセストークンを設定</a>
</div>

<hr style="margin-top:36px;">

<pre class="small" style="color:silver;margin-top:72px;margin-left:36px;">
↓以下は動作検証用の情報です。

<?php
	print 'company_name: '.$login_user['company_name']."\n";
	print 'company_id: '.$login_user['company_id']."\n";
	print 'company_ne_id: '.$login_user['company_ne_id']."\n";
	print 'company_host: '.$login_user['company_host']."\n";
	print 'company_type_id: '.$login_user['company_type_id']."\n";
	print 'company_ip_address: '.$login_user['company_ip_address']."\n";
	print 'pic_name: '.$login_user['pic_name']."\n";
	print 'pic_id: '.$login_user['pic_id']."\n";
	print 'pic_mail_address: '.$login_user['pic_mail_address']."\n";
	print 'uid: '.$login_user['uid']."\n";
	print 'pic_ne_id: '.$login_user['pic_ne_id']."\n";
	print "\n";
	print 'access_token: '.$nextengine->ne_access_token."\n";
	print 'refresh_token: '.$nextengine->ne_refresh_token."\n";

	$sql = 'select *';
	$sql .= ' from member';
	$sql .= ' where member_id = ';
	$sql .= $mysql->string(Config::$member_id);
	if (!$rs = $mysql->query($sql)){
		die('SQL ERROR '.$sql);
	}
	$row = $mysql->fetch_array($rs);
	if ($row){
		print "\n";
		print 'DATABASE'."\n";
		print 'ne_uid: '.$row['ne_uid']."\n";
		print 'ne_company_name: '.$row['ne_company_name']."\n";
		print 'ne_company_id: '.$row['ne_company_id']."\n";
		print 'ne_company_ne_id: '.$row['ne_company_ne_id']."\n";
		print 'ne_access_token: '.$row['ne_access_token']."\n";
		print 'ne_refresh_token: '.$row['ne_refresh_token']."\n";
	}
?>
</pre>

</div>
<?php
} catch (Exception $e) {
	die("Exception: ". $e->getMessage());
}
?>
</body>
</html>
