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
$login_user = array();
$token = '';
try {
	Tonary::session_start();
	Tonary::write_accesslog();
	$nextengine = new Tonary_NE();
	$login_user = $nextengine->login();
	$token = $nextengine->token;
	$mysql = new Tonary_MySQL();

	$nextengine->get_types_from_ne($mysql);

	if($_SERVER['REQUEST_METHOD'] == 'POST'){
		$mode = Tonary::get_post('mode');
		$not_execute_flg = Tonary::get_post('not_execute_flg');
		$param_sleep_sec = intval(Tonary::get_post('param_sleep_sec'));
		$param_wait_flag = Tonary::get_post('param_wait_flag');
	}else{
		$mode = '';
		$not_execute_flg = 0;
		$param_sleep_sec = 0;
		$param_wait_flag = 1;
	}

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
<title>受注カスタム自動処理｜<?=$login_user['system_name']?></title>
<meta name="viewport" content="width=device-width">
<link rel="stylesheet" href="/cssjs/reset2014.css">
<link rel="stylesheet" href="/cssjs/tonary.css">
<link rel="stylesheet" href="<?=Config::$local_css?>">
<script src="/cssjs/jquery.js"></script>
<script>
$(function() {
	//実行
	$('span[name="to_exec"]').click(function() {
		$('#mode').val('exec');
		var obj_form = $('#controlform');
		obj_form.attr('action','ne_routine_exec.php');
		obj_form.submit();
	});
	//NE伝票を開く
	$("span[name='open_ne_slip']").click(function() {
		var url = "<?=Config::$ne_server_url?>Userjyuchu/jyuchuInp?";
		url += "kensaku_denpyo_no=";
		url += $(this).attr("slip_no");
		url += "&jyuchu_meisai_order=jyuchu_meisai_gyo";
		window.open(url,"_blank","width=1200,height=700,scrollbars=yes");
	});
	//実行履歴CSVをダウンロード
	$('span[name="download_log"]').click(function() {
		$("#logparam_execute_datetime").val($(this).attr("execute_datetime"));
		var obj_form = $("#controlform");
		obj_form.attr("action","ne_download_log.php");
		obj_form.submit();
	});
});
</script>
</head>
<body>
<h1>受注カスタム自動処理｜<?=$login_user['system_name']?></h1>
<div class="breadcrumb">
<a href="index.php">トップ</a>
 &gt; <label>受注カスタム自動処理</label>
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
<form id="controlform" method="post" action="">
<input type="hidden" name="token" value="<?=$token?>">
<input type="hidden" name="mode" id="mode" value="">
<input type="hidden" name="logparam_execute_datetime" id="logparam_execute_datetime" value="">
<span class="button" name="to_exec" style="width:60px;">実行</span>
<input type="checkbox" name="not_execute_flg" id="not_execute_flg" value="1"
<?=($not_execute_flg==1)?' checked':''?>>受注伝票を更新しない
<br>
<span class="developer small">
　メイン機能サーバーにアクセスが集中していると、<br>
　「ネクストエンジン: 現在メイン機能サーバーが混み合っておりますので、再度時間を置いてからアクセスして下さい。」
というエラーになります。<br>
　エラーになった場合、一度アプリを閉じて、しばらく待ってから実行してください。
</span>
<br>
<input type="checkbox" name="param_wait_flag" id="param_wait_flag" value="1"
<?=($param_wait_flag==1)?' checked':''?>>メイン機能過負荷でも可能な限りエラーにせず実行する
<br>
<span class="developer small">
↑チェックするとメイン機能サーバーが混み合っていても、空くまで待って実行されます。<br>
　（空くまで待ち時間がかかります。）
</span>
<br>
<input type="text" name="param_sleep_sec" value="<?=$param_sleep_sec?>"
 class="right" style="width:32px;">秒間隔でネクストエンジンに更新リクエストを送信する
<br>
<span class="developer small">
↑メイン機能サーバーが混み合っている場合、間隔をあけると実行されやすい。
</span>
</form>
<h2 class="explain" style="margin-top:16px;">1.裄丈詰加工</h2>
<div class="explain small">
→行追加
商品コード:<span class="data">yuki</span>、
商品名:<span class="data">【裄丈詰め加工】</span><span class="meta">該当行の商品cd</span>、
商品op:<span class="meta">該当行の商品opの数字</span><span class="data">cm</span>、
売単価:<span class="data">1540</span>、
数量:<span class="meta">該当行の数量</span>、
<br>
　小計:再計算（商品計以降は再計算しない）
<br>
<br>
本店:<br>
商品opに<span class="data">裄丈詰め加工：▼</span>が含まれる行を抽出<br>
→該当行
売単価:<span class="data">1540</span>減算、
<br>
<br>
楽天・Yahoo:<br>
商品名に<span class="data">【裄丈詰め加工】</span>
商品opに<span class="data">寸法:-</span>が含まれる行を抽出<br>
→該当行 キャンセル
<br>
<br>
</div>
<?php
	if ($mode == 'exec'){
		$up_texts = array();

		require_once(Config::$local_include_path.'NERoutineExec.php');
		$neroutine = new NERoutineExec($nextengine);
		$neroutine->params['wait_flag'] = $param_wait_flag;
		$neroutine->sleep_sec = $param_sleep_sec;
		$neroutine->print_flg = true;
		if ($not_execute_flg == 1){
			$neroutine->not_execute_flg = true;
		}
		$rtn_msg = $neroutine->execute();
		print $rtn_msg;
		$up_texts = array_merge($up_texts, $neroutine->up_texts);
	}
?>
<h2 class="explain" style="margin-top:16px;">2.ネクタイ3本よりどり割引</h2>
<div style="font-weight:bold;font-size:big;color:red;">
2022/08/18からは、実行しない。
</div>
<div class="explain small">
対象商品: 型番が<span class="data">5000</span>、数量<span class="data">3</span>ごと
<br>
→行追加
商品コード:<span class="data">nekutai</span>、
商品名:<span class="data">ネクタイ3本よりどり割引</span>、
売単価:<span class="data">0</span>、
数量:<span class="meta">該当行の数量÷3を切捨て</span>、
<br>
　商品op:<span class="data">手数料にて割引。-</span><span class="meta">追加行の数量×1100</span><span class="data">円</span>
<br>
その他の手数料があったら、<br>
→商品op:
<span class="data">手数料にて割引。手数料内訳：その他 ○○円、ネクタイよりどり割引 -</span><span class="meta">追加行の数量×1100</span></span><span class="data">円</span>
<br>
　小計:<span class="data">0</span>
<br>
　手数料:<span class="meta">追加行の数量×1100</span>分減算（割引）
<br>
　総合計:再計算する
<br>
<br>
本店:<br>
→商品計:<span class="meta">追加行の数量×1100</span>分加算（FSは商品計だけ割引済みなので加算して補正）
<br>
<br>
楽天:<br>
→確認チェック:チェックを外す<br>
備考欄に<span class="data">■楽天バンク決済</span>があったら、<br>
→商品op:<span class="data">手数料にて割引。手数料内訳：楽天バンク決済 155円、ネクタイよりどり割引 -</span><span class="meta">追加行の数量×1100</span><span class="data">円</span>
<br>
　その他の手数料があったら、<br>
　商品op:<span class="data">手数料にて割引。手数料内訳：その他 </span><span class="meta">手数料-155</span><span class="data">円、楽天バンク決済 155円、ネクタイよりどり割引 -</span><span class="meta">追加行の数量×1100</span><span class="data">円</span>
<br>
　納品書特記事項:<br>
　<span class="data" style="dispaly:inline-block;text-decoration:line-through;">
手数料内訳：<br>
楽天バンク決済 155円<br>
ネクタイよりどり割引 -1100円
</span>
<span class="error">
←NEのAPIの発送伝票備考欄と納品書特記事項は異なっていました。</span>
<br>
<br>
Yahoo:<br>
→確認チェック:チェックを外す<br>
　他費用:<span class="meta">追加行の数量×1100</span>分加算（Yahooは他費用で割引済みなので加算して補正）<br>
<br>
</div>
<?php
/*
	if ($mode == 'exec'){
		require_once(Config::$local_include_path.'NEBundleExec.php');
		$neroutine = new NEBundleExec($nextengine);
		$neroutine->params['wait_flag'] = $param_wait_flag;
		$neroutine->sleep_sec = $param_sleep_sec;
		$neroutine->print_flg = true;
		if ($not_execute_flg == 1){
			$neroutine->not_execute_flg = true;
		}
		$rtn_msg = $neroutine->execute();
		print $rtn_msg;
		$up_texts = array_merge($up_texts, $neroutine->up_texts);
	}
*/
?>
<h2 class="explain" style="margin-top:16px;">3.ギフトフラグ</h2>
<div class="explain small">
楽天の備考欄に<span class="data">[包装紙]</span>があったら、
<br>
→ギフトフラグを<span class="data">1</span>にして、備考欄の<span class="data">[包装紙]</span>をカット
<br>
<br>
</div>
<?php
	if ($mode == 'exec'){
		require_once(Config::$local_include_path.'NEGiftFlagExec.php');
		$neroutine = new NEGiftFlagExec($nextengine);
		$neroutine->params['wait_flag'] = $param_wait_flag;
		$neroutine->sleep_sec = $param_sleep_sec;
		$neroutine->print_flg = true;
		if ($not_execute_flg == 1){
			$neroutine->not_execute_flg = true;
		}
		$rtn_msg = $neroutine->execute();
		print $rtn_msg;
		$up_texts = array_merge($up_texts, $neroutine->up_texts);
	}
?>
<?php
	if ($mode == 'exec'){
		if ($not_execute_flg
		 or $login_user['mail_address'] == 'hiro.tonary@gmail.com'){
			print '<textarea style="background-color:whitesmoke;font-size:14px;';
			print 'width:100%;height:600px;">';
			print_r($up_texts);
			print '</textarea>'."\n";
		}
	}
	print '<div class="label small">実行履歴';
	print '</div>'."\n";
	print '<table>'."\n";
	print '<tr>'."\n";
	print '<th style="width:60px;">&nbsp;</th>'."\n";
	print '<th style="vertical-align:bottom;">店舗</th>'."\n";
	print '<th style="vertical-align:bottom;">支払方法</th>'."\n";
	print '<th style="vertical-align:bottom;">伝票番号</th>'."\n";
	print '<th style="vertical-align:bottom;">受注番号</th>'."\n";
	print '<th style="vertical-align:bottom;">発送方法</th>'."\n";
	print '<th style="vertical-align:bottom;">受注日</th>'."\n";
	print '<th style="vertical-align:bottom;">担当者</th>'."\n";
	print '</tr>'."\n";
	$sql = 'select L.*, S.abbreviated_name shop_name';
	$sql .= ', P.abbreviated_name payment_method_name';
	$sql .= ', D.abbreviated_name delivery_name';
	$sql .= ' from execute_log L';
	$sql .= ' left join (select * from type_master where member_id = ';
	$sql .= $mysql->string(Config::$member_id);
	$sql .= ' and type_id = "shop") S';
	$sql .= ' on L.receive_order_shop_id = S.id';
	$sql .= ' left join (select * from type_master where member_id = ';
	$sql .= $mysql->string(Config::$member_id);
	$sql .= ' and type_id = "paymentmethod") P';
	$sql .= ' on L.receive_order_payment_method_id = P.id';
	$sql .= ' left join (select * from type_master where member_id = ';
	$sql .= $mysql->string(Config::$member_id);
	$sql .= ' and type_id = "delivery") D';
	$sql .= ' on L.receive_order_delivery_id = D.id';
	$sql .= ' where L.member_id = ';
	$sql .= $mysql->string(Config::$member_id);
	$sql .= ' and L.execute_datetime >= "';
	$sql .= date('Y-m-d', strtotime('- '.Config::$keep_days.' days'));
	$sql .= '"';
	$sql .= ' order by L.execute_datetime desc, L.receive_order_shop_id, L.receive_order_payment_method_id, L.receive_order_id';
	$sql .= ' limit 0,1000';
	if (!$rs = $mysql->query($sql)){
		die($sql.' error');
	}
	$row = $mysql->fetch_array($rs);
	$pre = '';
	while($row){
		if ($pre != $row['execute_datetime']){
			print '<tr>'."\n";
			print '<td colspan="8" class="bold" style="background-color:whitesmoke;">';
			print ' <span class="button" name="download_log" execute_datetime="';
			print Tonary::html($row['execute_datetime']);
			print '">CSV</span> ';
			print Tonary::html($row['execute_datetime']);
			print ' [';
			print Tonary::html($row['execute_name']);
			print '] ';
			print Tonary::html($row['pic_name']);
			if ($row['receive_order_id'] == '' and $row['execute_message'] <> ''){
				print '<br>'."\n";
				print '<span class="error small bold">エラー</span> <span class="error small">';
				print Tonary::html($row['execute_message']);
				print '</span> ';
			}
			print '</td>'."\n";
			print '</tr>'."\n";
		}
		if ($row['receive_order_id'] <> ''){
			print '<tr>'."\n";
			print '<td>';
			print '</td>'."\n";
			print '<td>';
			print Tonary::html($row['shop_name']);
			print '</td>'."\n";
			print '<td>';
			print Tonary::html($row['payment_method_name']);
			print '</td>'."\n";
			print '<td class="right">';
			print Tonary::html($row['receive_order_id']);
			print ' <span class="button" name="open_ne_slip" slip_no="';
			print Tonary::html($row['receive_order_id']);
			print '" style="font-size:11px;padding:0;">伝票</span>';
			print '</td>'."\n";
			print '<td>';
			print Tonary::html($row['receive_order_shop_cut_form_id']);
			print '</td>'."\n";
			print '<td>';
			print Tonary::html($row['delivery_name']);
			print '</td>'."\n";
			print '<td>';
			print Tonary::html($row['receive_order_date']);
			print '</td>'."\n";
			print '<td>';
			print Tonary::html($row['receive_order_pic_name']);
			print '</td>'."\n";
			print '</tr>'."\n";
			if ($row['execute_message'] <> ''){
				print '<tr>'."\n";
				print '<td class="error small bold" style="border-top:hidden;">';
				print 'エラー</td>'."\n";
				print '<td colspan="7" class="error small">';
				print Tonary::html($row['execute_message']);
				print '</td>'."\n";
				print '</tr>'."\n";
			}
		}
		$pre = $row['execute_datetime'];
		$row = $mysql->fetch_array($rs);
	}
	$mysql->free_result($rs);
	print '</table>'."\n";
?>
</div>
<?php
} catch (Exception $e) {
	die("Exception: ". $e->getMessage());
}
?>
</body>
</html>
