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
$param_tally = '';
$param_include_goods_un_visible = '';
$param_goods_id = '';
$wild_option_goods_id = '_*';
$query_goods_id = '';
$param_goods_name = '';
$wild_option_goods_name = '**';
$query_goods_name = '';
try {
	Tonary::session_start();
	Tonary::write_accesslog();
	$nextengine = new Tonary_NE();
	$login_user = $nextengine->login();
	$token = $nextengine->token;

	$mode = Tonary::get_post('mode', Tonary::CRLF_CUT, '');
	$param_tally = Tonary::get_post('param_tally', Tonary::CRLF_CUT, '');
	$param_include_goods_un_visible
		 = Tonary::get_post('param_include_goods_un_visible', Tonary::CRLF_CUT, '');
	$param_goods_id = Tonary::get_post('param_goods_id', Tonary::CRLF_CUT, '');
	$wild_option_goods_id = Tonary::get_post(
		'wild_option_goods_id',
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
<title>NE商品リスト｜<?=$login_user['system_name']?></title>
<meta name="viewport" content="width=device-width">
<link rel="stylesheet" href="/cssjs/reset2014.css">
<link rel="stylesheet" href="/cssjs/tonary.css">
<link rel="stylesheet" href="<?=Config::$local_css?>">
<script src="/cssjs/jquery.js"></script>
<script src="/cssjs/tonary.js"></script>
<script>
var win_formedit;
$(function(){
	//作成
	$('span[name="to_create"]').click(function() {
		if(!win_formedit || win_formedit.closed){
			win_formedit = window.open('','formedit','width=1300,height=700,scrollbars=yes');
		}else{
			alert('他の編集作業途中です');
			return;
		}
		$('#mode').val('create');
		$('#target_page_id').val($(this).attr('target_page_id'));
		$('#target_goods_id').val($(this).attr('target_goods_id'));
		var obj_form = $('#controlform');
		obj_form.attr('action','goods/goods_edit.php');
		obj_form.attr("target","formedit");
		obj_form.submit();
	});
	//編集
	$('span[name="to_edit"]').click(function() {
		if(!win_formedit || win_formedit.closed){
			win_formedit = window.open('','formedit','width=1300,height=700,scrollbars=yes');
		}else{
			alert('他の編集作業途中です');
			return;
		}
		$('#mode').val('edit');
		$('#target_page_id').val($(this).attr('target_page_id'));
		$('#target_goods_id').val("");
		var obj_form = $('#controlform');
		obj_form.attr('action','goods/goods_edit.php');
		obj_form.attr("target","formedit");
		obj_form.submit();
	});
	//検索
	$('span[name="to_list"]').click(function() {
		var v = $('#param_goods_id').val();
		v += $('#param_goods_name').val();
		if (v.length > 0){
			$('#mode').val('search');
			var obj_form = $('#controlform');
			obj_form.attr('action','ne_goods_list.php');
			obj_form.attr("target","");
			obj_form.submit();
		}else{
			if(window.confirm("絞込み条件が指定されていませんが、検索しますか？")){
				$('#mode').val('search');
				var obj_form = $('#controlform');
				obj_form.attr('action','ne_goods_list.php');
				obj_form.attr("target","");
				obj_form.submit();
			}
		}
	});
	//NE商品マスタを開く
	$("span[name='open_ne_goods']").click(function() {
		var url = "<?=Config::$ne_server_url?>Usersearchsyohin?s=";
		url += $(this).attr("goods_id");
		window.open(url,"_blank","width=1200,height=700,scrollbars=yes");
	});
});
</script>
</head>
<body>
<h1>NE商品リスト｜<?=$login_user['system_name']?></h1>
<div class="breadcrumb">
<a href="index.php">トップ</a>
 &gt; <label>NE商品リスト</label>
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
<input type="hidden" name="target_page_id" id="target_page_id" value="">
<input type="hidden" name="target_goods_id" id="target_goods_id" value="">
<div class="content" style="margin-bottom:36px;">
<div>
<span style="margin-left:16px;float:left;">
<span class="wild_label" target_option="wild_option_goods_id">商品コード</span><input
 type="text" class="wild_option" id="wild_option_goods_id" name="wild_option_goods_id"
 value="<?=$wild_option_goods_id ?>"><br>
<input style="width:240px;"
 type="text" name="param_goods_id" id="param_goods_id"
 value="<?=Tonary::html($param_goods_id) ?>">
</span>
<span style="margin-left:16px;float:left;">
<span class="wild_label" target_option="wild_option_goods_name">商品名</span><input
 type="text" class="wild_option" id="wild_option_goods_name" name="wild_option_goods_name"
 value="<?=$wild_option_goods_name ?>"><br>
<input style="width:240px;"
 type="text" name="param_goods_name" id="param_goods_name"
 value="<?=Tonary::html($param_goods_name) ?>">
</span>
<span class="small label" style="margin-left:16px;float:left;">
<br>
<input type="checkbox" name="param_tally"<?=($param_tally==1)?' checked':'' ?>
 value="1">代表商品コードで束ねる
<br>
<input type="checkbox" name="param_include_goods_un_visible"
<?=($param_include_goods_un_visible==1)?' checked':'' ?>
 value="1">商品マスタ非表示商品を含める
</span>
<span style="margin-left:16px;float:left;">
<br>
<span class="button" name="to_list" style="width:60px;text-align:center;">
検索</span>
</span>
</div>
<div style="clear:both;"></div>
<?php
	if ($mode == 'search'){
		require_once(Config::$global_include_path.'NE/NEBackupGoods.php');
		$target = new NEBackupGoods();
		$query_array = array();
		$query_array['offset'] = 0;
		$query_array['limit'] = 1000;
		$query_array['fields'] = $target->query_fields;
		$query_array['fields'] .= ',stock_quantity,stock_allocation_quantity,stock_free_quantity';
		$query_array['goods_deleted_flag-neq'] = '1';
		if ($param_include_goods_un_visible != 1){
			$query_array['goods_visible_flag-eq'] = '1';
		}
		if ($query_goods_id != ''){
			$query_array['goods_id-like'] = $query_goods_id;
		}
		if ($query_goods_name != ''){
			$query_array['goods_name-like'] = $query_goods_name;
		}
		$query_array['sort'] = 'goods_representation_id-asc,goods_id-asc';
		$api_return = $nextengine->apiExecute('/api_v1_master_goods/search', $query_array);
		if ($api_return['result'] != 'success'){
			$lists = 'システムエラー '.$api_return['message'];
		}else{
			$lists = $api_return['data'];
			$lists_count = intval($api_return['count']);
			for ($i_l=0; $i_l<$lists_count; $i_l++){
				if ($lists[$i_l]['goods_representation_id'] == ''){
					$lists[$i_l]['page_id'] = $lists[$i_l]['goods_id'];
				}else{
					$lists[$i_l]['page_id'] = $lists[$i_l]['goods_representation_id'];
				}
			}
		}

		if (is_array($lists) and Config::$use_page){
			require_once(Config::$global_include_path.'NE/NEBackupPage.php');
			$target = new NEBackupPage();
			$query_array = array();
			$query_array['offset'] = 0;
			$query_array['limit'] = 10000;
			$query_array['fields'] = $target->query_fields;
			$query_array['page_base_deleted_flag-neq'] = '1';
			if ($query_goods_id != ''){
				$query_array['page_base_goods_id-like'] = $query_goods_id;
			}
			$query_array['sort'] = 'page_base_goods_id-asc';
			$api_return = $nextengine->apiExecute('/api_v1_master_pagebase/search', $query_array);
			if ($api_return['result'] != 'success'){
				$lists = 'システムエラー '.$api_return['message'];
			}else{
				$pages = $api_return['data'];
				$pages_count = intval($api_return['count']);
			}
			for ($i_l=0; $i_l<$lists_count; $i_l++){
				for ($i_p=0; $i_p<$pages_count; $i_p++){
					if ($lists[$i_l]['page_id'] == $pages[$i_p]['page_base_goods_id']){
						$lists[$i_l] += $pages[$i_p];
						break;
					}
				}
			}
		}
		if (is_array($lists) and Config::$use_page){
			require_once(Config::$global_include_path.'NE/NEBackupVo.php');
			$target = new NEBackupVo();
			$query_array = array();
			$query_array['offset'] = 0;
			$query_array['limit'] = 10000;
			$query_array['fields'] = $target->query_fields;
			$query_array['page_base_v_o_deleted_flag-neq'] = '1';
			if ($query_goods_id != ''){
				$query_array['page_base_v_o_goods_id-like'] = $query_goods_id;
			}
			$query_array['sort'] = 'page_base_v_o_goods_id-asc,page_base_v_o_display_order-asc';
			$api_return = $nextengine->apiExecute(
				'/api_v1_master_pagebasevariationoroption/search',
				$query_array
			);
			if ($api_return['result'] != 'success'){
				$lists = 'システムエラー '.$api_return['message'];
			}else{
				$vos = $api_return['data'];
				$vos_count = intval($api_return['count']);
			}
			for ($i_l=0; $i_l<$lists_count; $i_l++){
				if ($lists[$i_l]['goods_representation_id'] != ''){
					for ($i_vo=0; $i_vo<$vos_count; $i_vo++){
						$vo_id = $vos[$i_vo]['page_base_v_o_goods_id'];
						$vo_id .= $vos[$i_vo]['page_base_v_o_horizontal_value'];
						$vo_id .= $vos[$i_vo]['page_base_v_o_vertical_value'];
						if ($lists[$i_l]['goods_id'] == $vo_id){
							$lists[$i_l] += $vos[$i_vo];
							break;
						}
					}
				}
			}
			usort($lists, function($a, $b){
				if ($a['page_id'] == $b['page_id']){
					if ($a['page_base_v_o_display_order'] == $b['page_base_v_o_display_order']){
						return $a['goods_id'] > $b['goods_id'];
					}
				}
				return $a['page_id'] > $b['page_id'];
			});
		}
		if (is_array($lists)){
			print '<table>'."\n";
			print '<tr>'."\n";
			print '<th>商品コード<br>商品名</th>'."\n";
			print '<th>売価</th>'."\n";
			print '<th>原価</th>'."\n";
			print '<th>取扱区分</th>'."\n";
			print '<th>在庫数</th>'."\n";
			print '<th>引当数</th>'."\n";
			print '<th>フリー在庫</th>'."\n";
			print '</tr>'."\n";
			$pre_page_id = null;
			$error_representation_id = '';
			$min_goods_selling_price = 99999999;
			$max_goods_selling_price = 0;
			$min_goods_cost_price = 99999999;
			$max_goods_cost_price = 0;
			$min_stock_quantity = 99999999;
			$max_stock_quantity = 0;
			$min_stock_allocation_quantity = 99999999;
			$max_stock_allocation_quantity = 0;
			$min_stock_free_quantity = 99999999;
			$max_stock_free_quantity = 0;
			for ($i_l=0; $i_l<count($lists); $i_l++){
				$page_id = $lists[$i_l]['page_id'];
				$goods_representation_id = $lists[$i_l]['goods_representation_id'];
				$goods_id = $lists[$i_l]['goods_id'];
				$representation_id_error = false;
				if ($goods_representation_id != ''){
					if (strpos($goods_id, $goods_representation_id) !== 0){
						$representation_id_error = true;
						$error_representation_id = $goods_representation_id;
					}
				}
				$goods_selling_price = $lists[$i_l]['goods_selling_price'];
				if ($min_goods_selling_price > $goods_selling_price){
					$min_goods_selling_price = $goods_selling_price;
				}
				if ($max_goods_selling_price < $goods_selling_price){
					$max_goods_selling_price = $goods_selling_price;
				}
				$goods_cost_price = $lists[$i_l]['goods_cost_price'];
				if ($min_goods_cost_price > $goods_cost_price){
					$min_goods_cost_price = $goods_cost_price;
				}
				if ($max_goods_cost_price < $goods_cost_price){
					$max_goods_cost_price = $goods_cost_price;
				}
				$stock_quantity = $lists[$i_l]['stock_quantity'];
				if ($min_stock_quantity > $stock_quantity){
					$min_stock_quantity = $stock_quantity;
				}
				if ($max_stock_quantity < $stock_quantity){
					$max_stock_quantity = $stock_quantity;
				}
				$stock_allocation_quantity = $lists[$i_l]['stock_allocation_quantity'];
				if ($min_stock_allocation_quantity > $stock_allocation_quantity){
					$min_stock_allocation_quantity = $stock_allocation_quantity;
				}
				if ($max_stock_allocation_quantity < $stock_allocation_quantity){
					$max_stock_allocation_quantity = $stock_allocation_quantity;
				}
				$stock_free_quantity = $lists[$i_l]['stock_free_quantity'];
				if ($min_stock_free_quantity > $stock_free_quantity){
					$min_stock_free_quantity = $stock_free_quantity;
				}
				if ($max_stock_free_quantity < $stock_free_quantity){
					$max_stock_free_quantity = $stock_free_quantity;
				}
				if ($param_tally != 1 and $pre_page_id !== $page_id){
//				if ($pre_page_id !== $page_id){
					print '<tr>'."\n";
					if ($lists[$i_l]['page_base_goods_id'] == '' and Config::$use_page){
						print '<td class="bold page_none">';
						if (Config::$page_url_prefix != ''){
							$url = Config::$page_url_prefix.strtolower($page_id);
							print '<a href="';
							print $url;
							print '" target="_blank">';
							print Tonary::html($page_id);
							print '</a>';
						}else{
							print Tonary::html($page_id);
						}
						if ($representation_id_error){
							print '<span class="small error" style="float:right;">';
							print Tonary::html($goods_representation_id);
							print ' 代表商品コード不正</span>';
						}
						print '<br>';
						print ' <span class="button" name="to_create" target_page_id="';
						print Tonary::html($page_id);
						print '" target_goods_id="';
						if ($lists[$i_l]['goods_representation_id'] == ''){
							print Tonary::html($lists[$i_l]['goods_id']);
						}
						print '">作成</span> ';
						print '<span class="small alert">ページ無</span>';
						print '</td>'."\n";
						print '<td class="bold page_none">';
						print '</td>'."\n";
						print '<td class="bold page_none">';
						print '</td>'."\n";
						print '<td class="bold page_none">';
						print '</td>'."\n";
						print '<td class="bold page_none">';
						print '</td>'."\n";
						print '<td class="bold page_none">';
						print '</td>'."\n";
						print '<td class="bold page_none">';
						print '</td>'."\n";
					}else{
						print '<td class="bold page">';
						if (Config::$page_url_prefix != ''){
							$url = Config::$page_url_prefix.strtolower($page_id);
							print '<a href="';
							print $url;
							print '" target="_blank">';
							print Tonary::html($page_id);
							print '</a>';
						}else{
							print Tonary::html($page_id);
						}
						if ($representation_id_error){
							print '<span class="small error" style="float:right;">';
							print Tonary::html($goods_representation_id);
							print ' 代表商品コード不正</span>';
						}
						print '<br>';
						print ' <span class="button" name="to_edit" target_page_id="';
						print Tonary::html($page_id);
						print '">編集</span> ';
						print Tonary::html($lists[$i_l]['page_base_goods_name']);
						print '</td>'."\n";
						print '<td class="bold page">';
						print '<div class="right">';
						print Tonary::number($lists[$i_l]['page_base_selling_price']);
						print '</div>';
						print '</td>'."\n";
						print '<td class="bold page">';
						print '</td>'."\n";
						print '<td class="bold page">';
						print '</td>'."\n";
						print '<td class="bold page">';
						print '</td>'."\n";
						print '<td class="bold page">';
						print '</td>'."\n";
						print '<td class="bold page">';
						print '</td>'."\n";
					}
					print '</tr>'."\n";
				}
				if ($param_tally != 1){
					print '<tr>'."\n";
					print '<td>';
					print '<div>';
					print '<span class="button" name="open_ne_goods" goods_id="';
					print Tonary::html($goods_id);
					print '" style="font-size:11px;padding:0;">マスタ</span> ';
					print Tonary::html($goods_id);
					if ($representation_id_error){
						print '<span class="small error" style="float:right;">';
						print Tonary::html($goods_representation_id);
						print ' 代表商品コード不正</span>';
					}
					if ($lists[$i_l]['page_base_goods_id'] != '' and Config::$use_page){
						if ($lists[$i_l]['page_base_v_o_goods_id'] == ''){
							print ' <span class="small alert">横軸縦軸無し</span>';
						}
					}
					print '</div>';
					print '<div class="small">';
					print Tonary::html($lists[$i_l]['goods_name']);
					print '</div>';
					print '</td>'."\n";
					print '<td>';
					print '<div class="small right">';
					print Tonary::number($lists[$i_l]['goods_selling_price']);
					print '</div>';
					print '</td>'."\n";
					print '<td>';
					print '<div class="small right">';
					print Tonary::number($lists[$i_l]['goods_cost_price']);
					print '</div>';
					print '</td>'."\n";
					print '<td>';
					if ($lists[$i_l]['goods_merchandise_id'] >= 1){
						print '<div class="small center alert">';
					}else{
						print '<div class="small center">';
					}
					print Tonary::html($lists[$i_l]['goods_merchandise_name']);
					print '</div>';
					if ($lists[$i_l]['goods_visible_flag'] != 1){
						print '<div class="small center alert">';
						print '非表示';
						print '</div>';
					}
					print '</td>'."\n";
					print '<td>';
					print '<div class="small right">';
					print Tonary::number($lists[$i_l]['stock_quantity']);
					print '</div>';
					print '</td>'."\n";
					print '<td>';
					print '<div class="small right">';
					print Tonary::number($lists[$i_l]['stock_allocation_quantity']);
					print '</div>';
					print '</td>'."\n";
					print '<td>';
					print '<div class="small right">';
					print Tonary::number($lists[$i_l]['stock_free_quantity']);
					print '</div>';
					print '</td>'."\n";
					print '</tr>'."\n";
				}

				if ($param_tally and $lists[($i_l+1)]['page_id'] !== $page_id){
//				if (count($lists) == ($i_l+1) or $lists[($i_l+1)]['page_id'] !== $page_id){
					print '<tr>'."\n";
					if ($lists[$i_l]['page_base_goods_id'] == '' and Config::$use_page){
						print '<td class="bold page_none">';
						if (Config::$page_url_prefix != ''){
							$url = Config::$page_url_prefix.strtolower($page_id);
							print '<a href="';
							print $url;
							print '" target="_blank">';
							print Tonary::html($page_id);
							print '</a>';
						}else{
							print Tonary::html($page_id);
						}
						if ($error_representation_id != ''){
							print '<span class="small error" style="float:right;">';
							print Tonary::html($error_representation_id);
							print ' 代表商品コード不正</span>';
						}
						print '<br>';
						print ' <span class="button" name="to_create" target_page_id="';
						print Tonary::html($page_id);
						print '" target_goods_id="';
						if ($lists[$i_l]['goods_representation_id'] == ''){
							print Tonary::html($lists[$i_l]['goods_id']);
						}
						print '">作成</span> ';
						print '<span class="small alert">ページ無</span>';
						print '</td>'."\n";
						print '<td class="page_none right small">';
						print Tonary::number($min_goods_selling_price);
						print '<br>';
						print Tonary::number($max_goods_selling_price);
						print '</td>'."\n";
						print '<td class="page_none right small">';
						print Tonary::number($min_goods_cost_price);
						print '<br>';
						print Tonary::number($max_goods_cost_price);
						print '</td>'."\n";
						print '<td class="bold page_none">';
						print '</td>'."\n";
						print '<td class="page_none right small">';
						print Tonary::number($min_stock_quantity);
						print '<br>';
						print Tonary::number($max_stock_quantity);
						print '</td>'."\n";
						print '<td class="page_none right small">';
						print Tonary::number($min_stock_allocation_quantity);
						print '<br>';
						print Tonary::number($max_stock_allocation_quantity);
						print '</td>'."\n";
						print '<td class="page_none right small">';
						print Tonary::number($min_stock_free_quantity);
						print '<br>';
						print Tonary::number($max_stock_free_quantity);
						print '</td>'."\n";
					}else{
						print '<td class="bold page">';
						if (Config::$page_url_prefix != ''){
							$url = Config::$page_url_prefix.strtolower($page_id);
							print '<a href="';
							print $url;
							print '" target="_blank">';
							print Tonary::html($page_id);
							print '</a>';
						}else{
							print Tonary::html($page_id);
						}
						if ($error_representation_id != ''){
							print '<span class="small error" style="float:right;">';
							print Tonary::html($error_representation_id);
							print ' 代表商品コード不正</span>';
						}
						print '<br>';
						print ' <span class="button" name="to_edit" target_page_id="';
						print Tonary::html($page_id);
						print '">編集</span> ';
						print Tonary::html($lists[$i_l]['page_base_goods_name']);
						print '</td>'."\n";
						print '<td class="page">';
						print '<div class="right">';
						print '<span class="small">';
						print Tonary::number($min_goods_selling_price);
						print '<br>';
						print Tonary::number($max_goods_selling_price);
						print '</span>';
						if ($lists[$i_l]['page_base_selling_price'] != ''){
							print '<br>';
							print '<span class="bold">';
							print Tonary::number($lists[$i_l]['page_base_selling_price']);
							print '</span>';
						}
						print '</div>';
						print '</td>'."\n";
						print '<td class="page right small">';
						print Tonary::number($min_goods_cost_price);
						print '<br>';
						print Tonary::number($max_goods_cost_price);
						print '</td>'."\n";
						print '<td class="bold page">';
						print '</td>'."\n";
						print '<td class="page right small">';
						print Tonary::number($min_stock_quantity);
						print '<br>';
						print Tonary::number($max_stock_quantity);
						print '</td>'."\n";
						print '<td class="page right small">';
						print Tonary::number($min_stock_allocation_quantity);
						print '<br>';
						print Tonary::number($max_stock_allocation_quantity);
						print '</td>'."\n";
						print '<td class="page right small">';
						print Tonary::number($min_stock_free_quantity);
						print '<br>';
						print Tonary::number($max_stock_free_quantity);
						print '</td>'."\n";
					}
					print '</tr>'."\n";
					$error_representation_id = '';
					$min_goods_selling_price = 99999999;
					$max_goods_selling_price = 0;
					$min_goods_cost_price = 99999999;
					$max_goods_cost_price = 0;
					$min_stock_quantity = 99999999;
					$max_stock_quantity = 0;
					$min_stock_allocation_quantity = 99999999;
					$max_stock_allocation_quantity = 0;
					$min_stock_free_quantity = 99999999;
					$max_stock_free_quantity = 0;
				}

				$pre_page_id = $page_id;
			}
			print '</table>'."\n";
		}else{
			print $lists;
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
