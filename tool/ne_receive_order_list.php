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

$mode = '';
$param_goods_id = '';
$wild_option_goods_id = '_*';
$query_goods_id = '';
$param_goods_name = '';
$wild_option_goods_name = '**';
$query_goods_name = '';
$param_goods_option = '';
$wild_option_goods_option = '**';
$query_goods_option = '';
$param_condition_view = '';
try {
	Tonary::session_start();
	Tonary::write_accesslog();
	$nextengine = new Tonary_NE();
	$login_user = $nextengine->login();
	$token = $nextengine->token;

	$mode = Tonary::get_post('mode', Tonary::CRLF_CUT, '');
	$param_goods_id = Tonary::get_post('param_goods_id', Tonary::CRLF_CUT, '');
	$wild_option_goods_id = Tonary::get_post('wild_option_goods_id',
		Tonary::CRLF_CUT,
		$wild_option_goods_id
	);
	if ($param_goods_id != ''){
		$query_goods_id = '';
		if ($wild_option_goods_id == '__'){
			$query_goods_id = $param_goods_id;
		}else if ($wild_option_goods_id == "_*"){
			$query_goods_id = $param_goods_id.'%';
		}else if ($wild_option_goods_id == "*_"){
			$query_goods_id = '%'.$param_goods_id;
		}else if ($wild_option_goods_id == "**"){
			$query_goods_id = '%'.$param_goods_id.'%';
		}
	}
	$param_goods_name = Tonary::get_post('param_goods_name', Tonary::CRLF_CUT, '');
	$wild_option_goods_name = Tonary::get_post(
		'wild_option_goods_name',
		Tonary::CRLF_CUT,
		$wild_option_goods_name
	);
	if ($param_goods_name != ''){
		$query_goods_name = '';
		if ($wild_option_goods_name == '__'){
			$query_goods_name = $param_goods_name;
		}else if ($wild_option_goods_name == "_*"){
			$query_goods_name = $param_goods_name.'%';
		}else if ($wild_option_goods_name == "*_"){
			$query_goods_name = '%'.$param_goods_name;
		}else if ($wild_option_goods_name == "**"){
			$query_goods_name = '%'.$param_goods_name.'%';
		}
	}
	$param_goods_option = Tonary::get_post('param_goods_option', Tonary::CRLF_CUT, '');
	$wild_option_goods_option = Tonary::get_post(
		'wild_option_goods_option',
		Tonary::CRLF_CUT,
		$wild_option_goods_option
	);
	if ($param_goods_option != ''){
		$query_goods_option = '';
		if ($wild_option_goods_option == '__'){
			$query_goods_option = $param_goods_option;
		}else if ($wild_option_goods_option == "_*"){
			$query_goods_option = $param_goods_option.'%';
		}else if ($wild_option_goods_option == "*_"){
			$query_goods_option = '%'.$param_goods_option;
		}else if ($wild_option_goods_option == "**"){
			$query_goods_option = '%'.$param_goods_option.'%';
		}
	}
	if($_SERVER['REQUEST_METHOD'] == 'POST'){
		$param_contain_cancel = Tonary::get_post('param_contain_cancel', Tonary::CRLF_CUT, '');
	}else{
		$param_contain_cancel = 0;
	}
	if (isset($_POST['param_status_ids'])){
		$param_status_ids = $_POST['param_status_ids'];
	}else{
		$param_status_ids = array(1,2,20);
	}
	$param_status_ids_query = implode(',', $param_status_ids);
	$param_start_date = Tonary::get_post('param_start_date', Tonary::CRLF_CUT, '');
	$param_stop_date = Tonary::get_post('param_stop_date', Tonary::CRLF_CUT, '');
	$param_receive_order_id = Tonary::get_post('param_receive_order_id', Tonary::CRLF_CUT, '');
	$param_receive_order_shop_cut_form_id
		 = Tonary::get_post('param_receive_order_shop_cut_form_id', Tonary::CRLF_CUT, '');

	$param_condition_view = Tonary::get_post('param_condition_view', Tonary::CRLF_CUT, '');
	$param_receive_order_ids = Tonary::get_post('param_receive_order_ids', Tonary::CRLF_USE, '');
	$param_receive_order_ids = trim($param_receive_order_ids);
	if ($param_receive_order_ids <> ''){
		$tmps = explode("\n", $param_receive_order_ids);
		for ($i=0; $i<count($tmps); $i++){
			if ($param_receive_order_ids_query != ''){
				$param_receive_order_ids_query .= ',';
			}
			$param_receive_order_ids_query .= trim($tmps[$i]);
		}
	}
	$param_receive_order_shop_cut_form_ids = Tonary::get_post(
		'param_receive_order_shop_cut_form_ids',
		Tonary::CRLF_USE,
		'');
	$param_receive_order_shop_cut_form_ids = trim($param_receive_order_shop_cut_form_ids);
	if ($param_receive_order_shop_cut_form_ids <> ''){
		$tmps = explode("\n", $param_receive_order_shop_cut_form_ids);
		for ($i=0; $i<count($tmps); $i++){
			if ($param_receive_order_shop_cut_form_ids_query != ''){
				$param_receive_order_shop_cut_form_ids_query .= ',';
			}
			$param_receive_order_shop_cut_form_ids_query .= trim($tmps[$i]);
		}
	}
	$param_goods_ids = Tonary::get_post('param_goods_ids', Tonary::CRLF_USE, '');
	$param_goods_ids = trim($param_goods_ids);
	if ($param_goods_ids <> ''){
		$tmps = explode("\n", $param_goods_ids);
		for ($i=0; $i<count($tmps); $i++){
			if ($param_goods_ids_query != ''){
				$param_goods_ids_query .= ',';
			}
			$param_goods_ids_query .= trim($tmps[$i]);
		}
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
<title>NE受注伝票リスト｜<?=$login_user['system_name']?></title>
<meta name="viewport" content="width=device-width">
<link rel="stylesheet" href="/cssjs/reset2014.css">
<link rel="stylesheet" href="/cssjs/tonary.css">
<link rel="stylesheet" href="<?=Config::$local_css?>">
<script src="/cssjs/jquery.js"></script>
<script src="/cssjs/tonary.js"></script>
<script>
$(function(){
	//検索
	$('span[name="to_list"]').click(function() {
		$('#mode').val('search');
		var obj_form = $('#controlform');
		obj_form.attr('action','ne_receive_order_list.php');
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
	//NE商品マスタを開く
	$("span[name='open_ne_goods']").click(function() {
		var url = "<?=Config::$ne_server_url?>Usersearchsyohin?s=";
		url += $(this).attr("goods_id");
		window.open(url,"_blank","width=1200,height=700,scrollbars=yes");
	});
	//詳細検索画面を開く
	$('span[name="open_condition_view"]').click(function() {
		$('#condition_view').css('display', 'block');
		$('#condition_hide').css('display', 'none');
		$('span[name="open_condition_view"]').css('display', 'none');
		$('#param_condition_view').val('1');
	});
	//詳細検索画面を閉じる
	$('span[name="close_condition_view"]').click(function() {
		$('#condition_view').css('display', 'none');
		$('#condition_hide').css('display', 'block');
		$('span[name="open_condition_view"]').css('display', 'inline-block');
		$('#param_condition_view').val('');
	});
});
</script>
</head>
<body>
<h1>NE受注伝票リスト｜<?=$login_user['system_name']?></h1>
<div class="breadcrumb">
<a href="index.php">トップ</a>
 &gt; <label>NE受注伝票リスト</label>
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
<form id="controlform" method="post" action="">
<input type="hidden" name="token" value="<?=$token?>">
<input type="hidden" name="mode" id="mode" value="">
<input type="hidden" name="param_condition_view" id="param_condition_view"
 value="<?=$param_condition_view ?>" />
<div class="content" style="margin-bottom:36px;">
<div class="label small">
<span style="float:left;">
受注日<br>
<input type="date" name="param_start_date" value="<?=$param_start_date?>">
～
<input type="date" name="param_stop_date" value="<?=$param_stop_date?>">
<input type="checkbox" name="param_status_ids[]" value="1"
<?=(in_array('1',$param_status_ids))?' checked':''?>>メール取込済
<input type="checkbox" name="param_status_ids[]" value="2"
<?=(in_array('2',$param_status_ids))?' checked':''?>>起票済
<input type="checkbox" name="param_status_ids[]" value="20"
<?=(in_array('20',$param_status_ids))?' checked':''?>>印刷待
<input type="checkbox" name="param_status_ids[]" value="30"
<?=(in_array('30',$param_status_ids))?' checked':''?>>印刷中
<input type="checkbox" name="param_status_ids[]" value="40"
<?=(in_array('40',$param_status_ids))?' checked':''?>>印刷済
<input type="checkbox" name="param_status_ids[]" value="50"
<?=(in_array('50',$param_status_ids))?' checked':''?>>出荷済
　<input type="checkbox" name="param_contain_cancel" value="1"
<?=($param_contain_cancel==1)?' checked':''?>>キャンセル含む
</span>
</div>
<div style="clear:both;"></div>
<div class="label small">
<span style="float:left;">
伝票番号<br>
<input style="width:120px;"
 type="text" name="param_receive_order_id"
 value="<?=Tonary::html($param_receive_order_id) ?>">
</span>
<span style="margin-left:8px;float:left;">
受注番号<br>
<input style="width:340px;"
 type="text" name="param_receive_order_shop_cut_form_id"
 value="<?=Tonary::html($param_receive_order_shop_cut_form_id) ?>">
</span>
<span style="margin-left:8px;float:left;">
<span class="wild_label" target_option="wild_option_goods_id">商品コード</span><input
 type="text" class="wild_option" id="wild_option_goods_id" name="wild_option_goods_id"
 value="<?=$wild_option_goods_id ?>"><br>
<input style="width:370px;"
 type="text" name="param_goods_id" id="param_goods_id"
 value="<?=Tonary::html($param_goods_id) ?>">
</span>
<span style="margin-left:8px;float:left;">
<br>
<span class="button" name="to_list" style="width:40px;text-align:center;">
検索</span>
<span class="button" name="open_condition_view" style="width:80px;text-align:center;<?php
	if ($param_condition_view == 1){
		print 'display:none;';
	}else{
		print 'display:inline-block;';
	}
?>">詳細検索</span>
</span>
</div>
<div style="clear:both;"></div>
<div id="condition_hide" class="label too_small"
 style="margin-top:4px;<?php
	if ($param_condition_view == 1){
		print 'display:none;';
	}else{
		print 'display:block;';
	}
	print '">';
	if ($param_receive_order_ids_query != ''){
		print '　伝票番号[';
		print Tonary::html($param_receive_order_ids_query);
		print ']';
	}
	if ($param_receive_order_shop_cut_form_ids_query != ''){
		print '　受注番号[';
		print Tonary::html($param_receive_order_shop_cut_form_ids_query);
		print ']';
	}
	if ($param_goods_ids_query != ''){
		print '　商品コード[';
		print Tonary::html($param_goods_ids_query);
		print ']';
	}
	if ($param_goods_name != ''){
		print '　商品名';
		print Tonary::html($wild_option_goods_name);
		print '[';
		print Tonary::html($param_goods_name);
		print ']';
	}
	if ($param_goods_option != ''){
		print '　商品op';
		print Tonary::html($wild_option_goods_option);
		print '[';
		print Tonary::html($param_goods_option);
		print ']';
	}
?>
</div>
<div id="condition_view"
 style="border:3px ridge silver;width:1240px;margin-top:4px;<?php
	if ($param_condition_view == 1){
		print 'display:block;';
	}else{
		print 'display:none;';
	}
?>">
<div style="background-color:dimgray;color:white;padding:2px 4px 2px 4px;">詳細検索画面
<span style="float:right;">
<span style="font-size:14px;color:whitesmoke;">（画面を閉じても条件は活きています）</span>
<span name="close_condition_view" style="cursor:pointer;padding:2px;width:20px;
 display:inline-block;border:1px solid white;color:white;font-family:'ＭＳ ゴシック';">×</span>
</span>
</div>
<div class="label small">
<span style="margin-left:8px;float:left;">
伝票番号（複数）<br>
<textarea style="width:130px;height:120px;" name="param_receive_order_ids">
<?=Tonary::html($param_receive_order_ids) ?>
</textarea>
</span>
<span style="margin-left:8px;float:left;">
受注番号（複数）<br>
<textarea style="width:350px;height:120px;" name="param_receive_order_shop_cut_form_ids">
<?=Tonary::html($param_receive_order_shop_cut_form_ids) ?>
</textarea>
</span>
<span style="margin-left:8px;float:left;">
商品コード（複数）<br>
<textarea style="width:380px;height:120px;" name="param_goods_ids">
<?=Tonary::html($param_goods_ids) ?>
</textarea>
</span>
<span style="margin-left:8px;float:left;">
<span class="wild_label" target_option="wild_option_goods_name">商品名</span><input
 type="text" class="wild_option" id="wild_option_goods_name" name="wild_option_goods_name"
 value="<?=$wild_option_goods_name ?>"><br>
<input style="width:340px;"
 type="text" name="param_goods_name" id="param_goods_name"
 value="<?=Tonary::html($param_goods_name) ?>">
<br>
<span class="wild_label" target_option="wild_option_goods_option">商品op</span><input
 type="text" class="wild_option" id="wild_option_goods_option" name="wild_option_goods_option"
 value="<?=$wild_option_goods_option ?>"><br>
<input style="width:340px;"
 type="text" name="param_goods_option" id="param_goods_option"
 value="<?=Tonary::html($param_goods_option) ?>">
</span>
</div>
<div style="clear:both;"></div>
<div style="text-align:right;background-color:whitesmoke;margin:0;padding:2px;">
<span name="close_condition_view" class="button"
 style="width:60px;text-align:center;">閉じる</span>
<span name="to_list" class="button"
 style="width:40px;text-align:center;">検索</span>
</div>
</div>
<div style="clear:both;"></div>
<?php
	if ($mode == 'search'){
		require_once(Config::$global_include_path.'NE/NEReceiveOrder.php');
		require_once(Config::$global_include_path.'NE/NEReceiveOrderRow.php');
		$ro_fields_query = '';
		for ($i=0; $i<count(NEReceiveOrder::$fields); $i++){
			if ($ro_fields_query != ''){
				$ro_fields_query .= ',';
			}
			$ro_fields_query .= NEReceiveOrder::$fields[$i]['field'];
		}
		for ($i=0; $i<count(NEReceiveOrderRow::$fields); $i++){
			if ($ro_fields_query != ''){
				$ro_fields_query .= ',';
			}
			$ro_fields_query .= NEReceiveOrderRow::$fields[$i]['field'];
		}

		$receive_order_ids = array();
		$receive_order_ids_query = '';
		$query_array = array();
		$query_array['offset'] = 0;
		$query_array['limit'] = 1000;
		$query_array['fields'] = 'receive_order_id';
		$query_array['receive_order_deleted_flag-neq'] = '1';
		$query_array['receive_order_row_deleted_flag-neq'] = '1';
		if ($query_goods_id != ''){
			$query_array['receive_order_row_goods_id-like'] = $query_goods_id;
		}
		if ($query_goods_name != ''){
			$query_array['receive_order_row_goods_name-like'] = $query_goods_name;
		}
		if ($query_goods_option != ''){
			$query_array['receive_order_row_goods_option-like'] = $query_goods_option;
		}
		if ($param_status_ids_query != ''){
			$query_array['receive_order_order_status_id-in'] = $param_status_ids_query;
		}
		if ($param_contain_cancel != 1){
			$query_array['receive_order_cancel_type_id-eq'] = '0';
		}
		if ($param_start_date != ''){
			$query_array['receive_order_date-gte'] = $param_start_date;
		}
		if ($param_stop_date != ''){
			$query_array['receive_order_date-lte'] = $param_stop_date;
		}
		if ($param_receive_order_id != ''){
			$query_array['receive_order_id-eq'] = $param_receive_order_id;
		}
		if ($param_receive_order_ids_query != ''){
			$query_array['receive_order_id-in'] = $param_receive_order_ids_query;
		}
		if ($param_receive_order_shop_cut_form_id != ''){
			$query_array['receive_order_shop_cut_form_id-eq'] = $param_receive_order_shop_cut_form_id;
		}
		if ($param_receive_order_shop_cut_form_ids_query != ''){
			$query_array['receive_order_shop_cut_form_id-in'] = $param_receive_order_shop_cut_form_ids_query;
		}
		if ($param_goods_ids_query != ''){
			$query_array['receive_order_row_goods_id-in'] = $param_goods_ids_query;
		}

		$query_array['sort'] = 'receive_order_id-asc';
		$api_return = $nextengine->apiExecute('/api_v1_receiveorder_row/search', $query_array);
		if ($api_return['result'] != 'success'){
			$lists = 'システムエラー '.$api_return['message'];
		}else{
			$lists = $api_return['data'];
			$lists_count = intval($api_return['count']);
			$slip_count = 0;
			for ($i_l=0; $i_l<count($lists); $i_l++){
				$receive_order_id = $lists[$i_l]['receive_order_id'];
				if (in_array($receive_order_id, $receive_order_ids) === false){
					$slip_count++;
					array_push($receive_order_ids, $receive_order_id);
					if ($receive_order_ids_query != ''){
						$receive_order_ids_query .= ',';
					}
					$receive_order_ids_query .= $receive_order_id;
					if ($slip_count >= 100){
						break;
					}
				}
			}
		}
		if ($receive_order_ids_query == ''){
			$lists = '０件';
		}else{
			$query_array = array();
			$query_array['offset'] = 0;
			$query_array['limit'] = 10000;
			$query_array['fields'] = $ro_fields_query;
			$query_array['receive_order_id-in'] = $receive_order_ids_query;
			$query_array['sort'] = 'receive_order_id-asc,receive_order_row_no-asc';
			$api_return = $nextengine->apiExecute('/api_v1_receiveorder_row/search', $query_array);
			if ($api_return['result'] != 'success'){
				$lists = 'システムエラー '.$api_return['message'];
			}else{
				$lists = $api_return['data'];
				$lists_count = intval($api_return['count']);
			}
			$query_s = array();
			$query_s['offset'] = 0;
			$query_s['limit'] = 1000;
			$query_s['fields'] = 'shop_id,';
			$query_s['fields'] .= 'shop_name,';
			$query_s['fields'] .= 'shop_abbreviated_name';
			$query_s['sort'] = 'shop_id-asc';
			$api_return = $nextengine->apiExecute('/api_v1_master_shop/search', $query_s) ;
			if ($api_return['result'] != 'success'){
				print '<div class="error">'.$api_return['message'].'</div>'."\n";
			}else{
				$shops_count = intval($api_return['count']);
				if ($shops_count >= 10000){
					print '<div class="error">店舗の行数が多すぎます</div>'."\n";
				}
				$shops = $api_return['data'];
			}
		}
		if (is_array($lists)){
			print '<table style="margin-top:4px;">'."\n";
			$pre_receive_order_id = null;
			for ($i_l=0; $i_l<count($lists); $i_l++){
				$receive_order_id = $lists[$i_l]['receive_order_id'];
				for ($i_s=0; $i_s<count($shops); $i_s++){
					if ($lists[$i_l]['receive_order_shop_id']
					 == $shops[$i_s]['shop_id']){
						$lists[$i_l]['shop_abbreviated_name']
							 = $shops[$i_s]['shop_abbreviated_name'];
						$lists[$i_l]['shop_name']
							 = $shops[$i_s]['shop_name'];
					}
				}
				if ($pre_receive_order_id !== $receive_order_id){
					print '<tr class="slip">'."\n";
					print '<td colspan="2">';
					print '<div>';
					print '<span class="button" name="open_ne_slip" slip_no="';
					print $receive_order_id;
					print '" style="font-size:11px;padding:0;">伝票</span> ';
					print $receive_order_id;
					print ' ';
					print $lists[$i_l]['receive_order_order_status_name'];
					print ' ';
					print date('Y/m/d H:i:s', strtotime($lists[$i_l]['receive_order_date']));
					print '</div>'."\n";
					print '<div>';
					print $lists[$i_l]['shop_abbreviated_name'];
					print ' ';
					print $lists[$i_l]['receive_order_shop_cut_form_id'];
					print '</div>'."\n";
					print '<div class="small">';
					print $lists[$i_l]['receive_order_cancel_type_name'];
					print '</div>'."\n";
					print '</td>'."\n";
					print '<td colspan="4">';
					print '<div>';
					print $lists[$i_l]['receive_order_payment_method_name'];
					print ' ';
					print $lists[$i_l]['receive_order_delivery_name'];
					print '</div>'."\n";
					print '<div>';
					print $lists[$i_l]['receive_order_purchaser_name'];
					print '</div>'."\n";
					print '<div class="small">';
					print $lists[$i_l]['receive_order_purchaser_address1'];
					print '</div>'."\n";
					print '</td>'."\n";
					print '</tr>'."\n";
				}
				print '<tr class="slip_row">'."\n";
				print '<td class="right small">';
				print '<div>';
				print $lists[$i_l]['receive_order_row_no'];
				print '</div>'."\n";
				print '<div class="cancel_flag">';
				print ($lists[$i_l]['receive_order_row_cancel_flag']==1)?'c':'';
				print '</div>'."\n";
				print '</td>'."\n";
				print '<td colspan="2">';
				print '<div>';
				print '<span class="button" name="open_ne_goods" goods_id="';
				print Tonary::html($lists[$i_l]['receive_order_row_goods_id']);
				print '" style="font-size:11px;padding:0;">マスタ</span> ';
				print Tonary::html($lists[$i_l]['receive_order_row_goods_id']);
				print '</div>'."\n";
				print '<div class="small">';
				print $lists[$i_l]['receive_order_row_goods_name'];
				print '</div>'."\n";
				print '<div class="small goods_option">';
				print $lists[$i_l]['receive_order_row_goods_option'];
				print '</div>'."\n";
				print '</td>'."\n";
				print '<td class="right">';
				print number_format(intval($lists[$i_l]['receive_order_row_quantity']));
				print '<span class="small">個</span></td>'."\n";
				print '<td class="right">';
				print number_format(intval($lists[$i_l]['receive_order_row_unit_price']));
				print '<span class="small">円</span>';
				print '</td>'."\n";
				print '<td class="right">';
				print number_format(intval($lists[$i_l]['receive_order_row_sub_total_price']));
				print '<span class="small">円</span>';
				print '</td>'."\n";
				print '</tr>'."\n";
				$pre_receive_order_id = $receive_order_id;
			}
			print '</table>'."\n";
		}else{
			print $lists;
		}
	}
	if (is_array($lists)){
		$pre_receive_order_id = null;
		for ($i_l=0; $i_l<count($lists); $i_l++){
			$receive_order_id = $lists[$i_l]['receive_order_id'];
			if ($pre_receive_order_id !== $receive_order_id){
				print '<div style="border:1px solid silver;background-color:aliceblue;">';
				for ($i=0; $i<count(NEReceiveOrder::$fields); $i++){
					print '<div>';
					print '<span class="label small">';
					print Tonary::html(NEReceiveOrder::$fields[$i]['name']);
					print ':';
					print Tonary::html(NEReceiveOrder::$fields[$i]['field']);
					print ':</span>';
					print Tonary::html($lists[$i_l][NEReceiveOrder::$fields[$i]['field']]);
					print '</div>';
				}
				print '</div>';
			}
			print '<div style="border:1px solid silver;">';
			for ($i=0; $i<count(NEReceiveOrderRow::$fields); $i++){
				print '<div>';
				print '<span class="label small">';
				print Tonary::html(NEReceiveOrderRow::$fields[$i]['name']);
				print ':';
				print Tonary::html(NEReceiveOrderRow::$fields[$i]['field']);
				print ':</span>';
				print Tonary::html($lists[$i_l][NEReceiveOrderRow::$fields[$i]['field']]);
				print '</div>';
			}
			print '</div>';
			$pre_receive_order_id = $receive_order_id;
		}
	}
?>
</div>
</form>
<?php
} catch (Exception $e) {
	die("Exception: ". $e->getMessage());
}
?>
</body>
</html>
