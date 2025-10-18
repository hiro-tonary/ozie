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
require_once(Config::$global_include_path.'Tonary_Posted.php');
require_once(Config::$global_include_path.'Tonary_NE.php');
require_once(Config::$global_include_path.'Tonary_MySQL.php');

$nextengine = null;
$mysql = null;
$login_user = array();
$token = '';
$error_flg = false;

$brands = array();
$seasons = array();
$horizontal_categorys = array();
$vertical_categorys = array();
$category_classes = array();
$categorys = array();
$class_tags = array();

$disp = array();
$page_horizontals = array();
$page_verticals = array();

try {
	Tonary::session_start();
	Tonary::write_accesslog();
	$nextengine = new Tonary_NE();
	$login_user = $nextengine->login();
	$token = $nextengine->token;
	$mysql = new Tonary_MySQL();
	$execute_datetime = date('Y-m-d H:i:s');

	$disp['release_state'] = '作業中';
	$disp['display_flag'] = 1;
	$disp['create_yyyymm'] = '【'.date('Ym').'】';
	$disp['postage_type'] = '';
	$disp['tax_type'] = 1;
	$disp['use_vo_flag'] = 1;
	$disp['horizontal_category'] = '';
	$disp['vertical_category'] = '';
	$disp['alert_comment_m'] = 1;
	$disp['measure_m'] = 1;
	$disp['target_m'] = 1;
	$disp['brand_m'] = 1;
	$disp['material_m'] = 1;
	$disp['season_m'] = 1;
	$disp['img_item_number'] = '';

	$category_classes[1] = true;
	$categorys[1][1] = true;
	$categorys[1][2] = true;
	$categorys[1][3] = true;
	$category_classes[2] = true;
	$categorys[2][1] = true;
	$categorys[2][2] = true;
	$category_classes[3] = true;
	$categorys[3][1] = true;

	$mode = Tonary::get_post('mode', Tonary::CRLF_CUT, '');
	$page_id = Tonary::get_getpost('target_page_id', Tonary::CRLF_CUT, '');
	$target_goods_id = Tonary::get_getpost('target_goods_id', Tonary::CRLF_CUT, '');

} catch (Exception $e) {
	header('Content-type: text/html; charset=UTF-8');
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
<title>商品ページ編集｜<?=$login_user['system_name']?></title>
<meta name="viewport" content="width=device-width">
<link rel="stylesheet" href="/cssjs/reset2014.css">
<link rel="stylesheet" href="/cssjs/tonary.css">
<link rel="stylesheet" href="<?=Config::$local_css?>">
<script src="/cssjs/jquery.js"></script>
<script src="/cssjs/tonary.js"></script>
</head>
<body>
<h1>商品ページ編集｜<?=$login_user['system_name']?></h1>
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
<div class="developer big bold" style="text-align:center;margin-bottom:8px;">
この機能は別途オーダーメイド(有償)です。このままでは使えません。
</div>
<div style="text-align:center;margin-bottom:16px;">
<span class="button" name="update_rakuten">楽天に送信</span>
<span class="button" name="update_amazon">Amazonに送信</span>
<span class="button" name="update_ne_master">NE商品マスタを更新</span>
<span class="button" name="download_ne_csv">NEページ管理／項目選択肢在庫の更新用CSVダウンロード</span>
</div>

<form id="controlform" method="post" action="goods_edit.php">
<input type="hidden" name="token" value="<?=$token?>">
<input type="hidden" name="mode" id="mode" value="">
<input type="hidden" name="target_page_id" id="target_page_id" value="">
<div class="page_edit_div" style="padding-top:16px;border:1px solid #a88048;">

<span name="to_save" class="button_catch save">保存</span>
<div></div>

<span class="label_area big bold alt_label">メモ</span>
<span class="balloon" style="margin-left:0px;margin-top:-32px;">
未入荷、未登録、などの社内用メモ欄。自由に使用して良い。
</span>
<span class="input_area">
<?php
	if ($message != ''){
		print '<span class="report big bold">';
		print Tonary::html($message);
		print '</span><br>';
	}
?>
<textarea name="input_memo"
 style="width:980px;height:90px;color:red;"><?=Tonary::html($disp['memo'])?></textarea>
</span>
<div></div>

<span class="label_area big bold alt_label">公開</span>
<span class="balloon" style="margin-left:0px;margin-top:-32px;">NEページ管理のスペック１</span>
<span class="input_area">
<span class="box">
<input type="radio" name="input_release_state"
 value="作業中"<?=('作業中'==$disp['release_state'])?' checked':''?>>作業中
<input type="radio" name="input_release_state"
 value="新規"<?=('新規'==$disp['release_state'])?' checked':''?>>新規
<input type="radio" name="input_release_state"
 value="更新"<?=('更新'==$disp['release_state'])?' checked':''?>>更新
<input type="radio" name="input_release_state"
 value="削除"<?=('削除'==$disp['release_state'])?' checked':''?>>削除
</span>
<span class="alt_label">優先度</span>
<span class="balloon" style="margin-left:0px;margin-top:-32px;">NEページ管理のスペック２</span>
<input type="text" name="input_priority"
 value="<?=Tonary::html($disp['priority'])?>"
 class="right"
 style="width:160px;">
</span>
<span style="display:inline-block;float:right;margin-right:16px;">

<span class="alt_label">作成年月</span>
<span class="balloon" style="margin-left:-60px;margin-top:-32px;">
NEページ管理のスペック４【YYYYMM】
</span>
<input type="text" name="input_create_yyyymm" class="center" style="width:140px;"
 value="<?=Tonary::html($disp['create_yyyymm'])?>">　

<span class="alt_label">表示</span>
<span class="balloon" style="margin-left:-60px;margin-top:-32px;">原則、表示</span>
<span class="box" style="width:160px;">
<input type="radio" name="input_display_flag" value="1"
<?=(1==$disp['display_flag'])?' checked':''?>>表示
<input type="radio" name="input_display_flag" value="0"
<?=(1!=$disp['display_flag'])?' checked':''?>>非表示
</span>

</span>
<div></div>

<span class="label_area">&nbsp;</span>
<span class="input_area">
<input type="checkbox" name="input_no_amazon_flag" value="1"
<?=(1==$disp['no_amazon_flag'])?' checked':''?>>
amazon掲載しない
　　　<input type="checkbox" name="input_no_rakuten_flag" value="1"
<?=(1==$disp['no_rakuten_flag'])?' checked':''?>>
楽天掲載しない
</span>
<span style="display:inline-block;float:right;margin-right:16px;">

<span class="alt_label">個別送料</span>
<span class="balloon" style="margin-left:-60px;margin-top:-32px;">NEページ管理の個別送料</span>
<input type="text" name="input_postage_amount"
 value="<?=Tonary::html($disp['postage_amount'])?>"
 class="right"
 style="width:140px;">円

<span class="alt_label">送料</span>
<span class="balloon" style="margin-left:-60px;margin-top:-32px;">原則、送料込</span>
<span class="box" style="width:160px;">
<input type="radio" name="input_postage_type" value=""
<?=(1!=$disp['postage_type'])?' checked':''?>>送料込
<input type="radio" name="input_postage_type" value="1"
<?=(1==$disp['postage_type'])?' checked':''?>>送料別
</span>

</span>
<div></div>

<span class="label_area big bold">価格</span>
<span class="input_area">
<span class="alt_label">売価</span>
<span class="balloon" style="margin-left:-60px;margin-top:-58px;">NEページ管理の販売価格<br>
＝NE商品マスタの売価</span>
<input type="text" name="input_selling_price"
 value="<?=Tonary::html($disp['selling_price'])?>"
 class="right"
 style="width:140px;">円
　<span class="alt_label">定価</span>
<span class="balloon" style="margin-left:-60px;margin-top:-58px;">NEページ管理の表示価格<br>
＝NE商品マスタの定価</span>
<input type="text" name="input_display_price"
 value="<?=Tonary::html($disp['display_price'])?>"
 class="right"
 style="width:140px;">円
</span>
<span style="display:inline-block;float:right;margin-right:16px;">
<span class="alt_label">消費税</span>
<span class="balloon" style="margin-left:-60px;margin-top:-32px;">原則、税込</span>
<span class="box" style="width:160px;">
<input type="radio" name="input_tax_type" value="1"
<?=(1==$disp['tax_type'])?' checked':''?>>税込
<input type="radio" name="input_tax_type" value="0"
<?=(1!=$disp['tax_type'])?' checked':''?>>税別
</span>
</span>
<div></div>
<span class="label_area big bold">カテゴリ</span>
<span class="input_area">
<?php
	for ($i=1; $i<=5; $i++){
		if ($category_classes[$i]){
			for ($j=1; $j<=5; $j++){
				if ($categorys[$i][$j]){
					print '<select name="input_category:'.$i.':'.$j.'"';
					print ' number="'.$i.'" level="'.$j.'">'."\n";
					print '</select>'."\n";
				}
			}
			print '<br>'."\n";
		}
	}
?>
</span>
<div></div>

<span class="label_area big bold alt_label">商品名</span>
<span class="balloon" style="margin-left:0px;margin-top:-58px;">NEページ管理の商品名<br>
これに 横軸選択肢 縦軸選択肢を付けたものがNE商品マスタの商品名</span>
<span class="input_area">
<input type="text" name="input_page_name"
 value="<?=Tonary::html($disp['page_name'])?>"
 style="width:980px;">
</span>
<div></div>

<span class="label_area big bold alt_label">代表商品コード</span>
<span class="balloon" style="margin-left:0px;margin-top:-82px;">NEページ管理の商品コード<br>
＝(横軸縦軸を使用する場合)NE商品マスタの代表商品コード<br>
＝(使用しない場合)NE商品マスタの商品コード</span>
<span class="input_area">
<input type="text" name="input_page_id"
 value="<?=Tonary::html($page_id)?>"
 style="width:300px;">
　<input type="checkbox" name="input_use_vo_flag" value="1"
<?=(1==$disp['use_vo_flag'])?' checked':''?>>
<span class="alt_label">横軸縦軸を使用する</span>
<span class="balloon" style="margin-left:280px;margin-top:-106px;">(使用する場合)<br>
[商品コード][横軸選択肢番号][縦軸選択肢番号]<br>
がNE商品マスタの商品コード</span>
</span>
<div></div>

<span class="label_area big bold">横軸縦軸<br>
<?php
	if ($vo_string != ''){
		print '<textarea style="height:260px;background-color:whitesmoke;color:gray;">';
		print Tonary::html($vo_string);
		print '</textarea>';
	}
?>
</span>
<span class="input_area">
<span class="label_area_narrow">横軸名</span>
<span class="input_area">
<select name="input_horizontal_category" style="width:260px;">
<option value=""></option>
<?php
	for ($i=0; $i<count($horizontal_categorys); $i++){
		print '<option value="';
		print Tonary::html($horizontal_categorys[$i]['option_value']);
		print '"';
		if ($horizontal_categorys[$i]['option_value'] == $disp['horizontal_category']){
			print ' selected';
			$match_flg = true;
		}
		print '>';
		print Tonary::html($horizontal_categorys[$i]['option_name']);
		print '</option>'."\n";
	}
?>
</select>
<br>
<span class="center" style="display:inline-block;width:130px;">横軸番号</span>
<span style="display:inline-block;width:260px;">横軸選択肢</span><br>
<?php
	for ($i=1; $i<=8; $i++){
		print '<span class="grip"></span>'."\n";
		print '<input type="text" style="width:100px;" name="input_horizontal_value:';
		print $i;
		print '" value="';
		print $page_horizontals[$i]['horizontal_value'];
		print '">'."\n";
		print '<input type="text" style="width:260px;" name="input_horizontal_name:';
		print $i;
		print '" value="';
		print $page_horizontals[$i]['horizontal_name'];
		print '">'."\n";
		print '<br>'."\n";
		print '';
		print '';
	}
?>
</span>
<span class="label_area_narrow">縦軸名</span>
<span class="input_area">
<select name="input_vertical_category" style="width:260px;">
<option value=""></option>
<?php
	for ($i=0; $i<count($vertical_categorys); $i++){
		print '<option value="';
		print Tonary::html($vertical_categorys[$i]['option_value']);
		print '"';
		if ($vertical_categorys[$i]['option_value'] == $disp['vertical_category']){
			print ' selected';
			$match_flg = true;
		}
		print '>';
		print Tonary::html($vertical_categorys[$i]['option_name']);
		print '</option>'."\n";
	}
?>
</select>
<br>
<span class="center" style="display:inline-block;width:130px;">縦軸番号</span>
<span style="display:inline-block;width:260px;">縦軸選択肢</span><br>
<?php
	for ($i=1; $i<=8; $i++){
		print '<span class="grip"></span>'."\n";
		print '<input type="text" style="width:100px;" name="input_vertical_value:';
		print $i;
		print '" value="';
		print $page_verticals[$i]['vertical_value'];
		print '">'."\n";
		print '<input type="text" style="width:260px;" name="input_vertical_name:';
		print $i;
		print '" value="';
		print $page_verticals[$i]['vertical_name'];
		print '">'."\n";
		print '<br>'."\n";
		print '';
		print '';
	}
?>
</span><br>
</span>
<div></div>

<span name="to_save" class="button_catch save">保存</span>
<div></div>

<span class="label_area"><span class="big bold">キャッチコピー１</span><br>
全角<span id="catch_phrase1_len" class="len"></span>文字<br>
<span id="catch_phrase1_byte" class="len"></span>byte
</span>
<span class="input_area">
<span class="explain">
(全角90文字)<br>
</span>
<textarea name="input_catch_phrase1" id="catch_phrase1"
 style="width:980px;height:90px;"><?=Tonary::html($disp['catch_phrase1'])?></textarea>
</span>
<div></div>

<span class="label_area"><span class="big bold">キャッチコピー２</span><br>
全角<span id="catch_phrase2_len" class="len"></span>文字<br>
<span id="catch_phrase2_byte" class="len"></span>byte
</span>
<span class="input_area">
<span class="explain">
(全角30文字)（モバイルなど）<br>
</span>
<input type="text" name="input_catch_phrase2" id="catch_phrase2"
 value="<?=Tonary::html($disp['catch_phrase2'])?>"
 style="width:980px;">
</span>
<div></div>

<span class="label_area big bold">説明文</span>
<span class="input_area">
</span>
<div></div>

<span class="label_area alt_label">■</span>
<span class="balloon" style="margin-left:0px;margin-top:-32px;">注意書き（赤文字）</span>
<span class="input_area">
<textarea name="input_alert_comment" style="width:800px;height:60px;color:red;vertical-align:top;"
><?=Tonary::html($disp['alert_comment'])?></textarea>
<input type="checkbox" name="input_alert_comment_m" value="1"
<?=(1==$disp['alert_comment_m'])?' checked':''?>>モバイルに表示
</span>
<div></div>

<span class="label_area">【特徴】</span>
<span class="input_area">
<textarea style="width:980px;height:180px;"
 name="input_feature"><?=Tonary::html($disp['feature'])?></textarea>
<br>
<span class="button" style="font-size:14px;">&gt;モバイル&gt;</span>
<textarea style="width:280px;height:260px;vertical-align:top;"
 name="input_feature_m"><?=Tonary::html($disp['feature_m'])?></textarea>
<?php
	if ($page_base_description2 != ''){
?>
<textarea style="width:600px;height:260px;vertical-align:top;background-color:whitesmoke;color:gray;">
<?=Tonary::html($page_base_description2)?>
</textarea>
<?php
	}
?>
</span>
<div></div>

<span class="label_area">
【仕様】
</span>
<div></div>

<span class="label_area alt_label">ブランド</span>
<span class="balloon" style="margin-left:0px;margin-top:-32px;">NEページ管理のメーカーに連動</span>
<span class="input_area">
<select style="width:240px;" name="input_brand">
<option value=""></option>
<?php
	for ($i=0; $i<count($brands); $i++){
		print '<option value="';
		print Tonary::html($brands[$i]['option_value']);
		print '"';
		if ($brands[$i]['option_value'] == $disp['brand']){
			print ' selected';
			$match_flg = true;
		}
		print '>';
		print Tonary::html($brands[$i]['option_name']);
		print '</option>'."\n";
	}
?>
<option value="input">新規登録→</option>
</select><input type="text" name="input_brand_input"
 value=""
 style="width:240px;">
<input type="checkbox" name="input_brand_m" value="1"
<?=(1==$disp['brand_m'])?' checked':''?>>モバイルに表示
</span>
<div></div>

<span class="label_area">素材</span>
<span class="input_area">
<input type="text" name="input_material"
 value="<?=Tonary::html($disp['material'])?>"
 style="width:480px;">
<input type="checkbox" name="input_material_m" value="1"
<?=(1==$disp['material_m'])?' checked':''?>>モバイルに表示
</span>
<div></div>

<span class="label_area">メンテナンス方法</span>
<span class="input_area">
<textarea style="width:480px;height:180px;vertical-align:top;"
 name="input_maintenance"><?=Tonary::html($disp['maintenance'])?></textarea>
<span class="button" style="font-size:14px;">&gt;モバイル&gt;</span>
<textarea style="width:280px;height:180px;vertical-align:top;"
 name="input_maintenance_m"><?=Tonary::html($disp['maintenance_m'])?></textarea>
</span>
<div></div>

<span class="label_area big bold alt_label">スタッフコメント</span>
<span class="balloon" style="margin-left:0px;margin-top:-32px;">NEページ管理の説明文４</span>
<span class="input_area">
<textarea style="width:980px;height:90px;"
 name="input_staff_comment"><?=Tonary::html($disp['staff_comment'])?></textarea>
</span>
<div></div>

<span class="label_area big bold alt_label">分類タグ</span>
<span class="balloon" style="margin-left:0px;margin-top:-32px;">NEページ管理の説明文３</span>
<span class="input_area">
<?php
	foreach ($class_tags as $tag_type => $tags){
		if ($tag_type == '対象'){
			continue;
		}
		print '<span class="label_area_narrow">';
		print Tonary::html($tag_type);
		print '</span>'."\n";
		print '<span class="input_area" style="width:900px;margin-bottom:4px;">';
		for ($i=0; $i<count($tags); $i++){
			print '<span class="box" style="width:210px;">';
			print '<input type="checkbox" name="input_class_tag[]" value="';
			print Tonary::html($tags[$i]['option_value']);
			print '"';
			if (strpos($disp['class_tags'], $tags[$i]['option_value']) !== false ){
				print ' checked';
			}
			print '>';
			print Tonary::html($tags[$i]['option_name']);
			print '</span>';
		}
		print '</span>'."\n";
		print '<br>'."\n";
	}
?>
</span>
<div></div>

<span name="to_save" class="button_catch save">保存</span>
<div></div>

<span class="label_area big bold">画像</span>
<span class="input_area">
<?php
	$url_code = strtolower($page_id);
	for ($i=0; $i<10; $i++){
		if (($i % 2) != 0){
			print '<span style="float:left;margin-top:36px;margin-left:128px;">'."\n";
		}else{
			print '<span style="float:left;margin-top:36px;">'."\n";
		}
		print '<img src="https://thumbnail.image.rakuten.co.jp/@0_mall/ozie/cabinet/';
		print $url_code.'/';
		print $url_code;
		if ($i > 0){
			print '_'.$i;
		}
		print '.jpg';
		print '?_ex=180x180&s=2&r=1" style="border:1px solid silver;"><br>'."\n";
		print $url_code;
		if ($i > 0){
			print '_'.$i;
		}
		print '.jpg';
		print '</span>'."\n";
		print '<span style="float:left;margin-top:36px;margin-left:8px;">'."\n";
		print '画像'.($i+1).'ALT<br>'."\n";
		print '<textarea name="input_img_alt'.$i.'" style="height:120px;">';
		print Tonary::html($disp['img_alt'.$i]);
		print '</textarea>'."\n";
		print '</span>'."\n";
		if (($i % 2) != 0){
			print '<div></div>'."\n\n";
		}
	}
?>
<span class="developer"><br>
画像は、別途、画像一括アップロードツールでまとめて各店舗にアップします。
</span>
</span>
<div></div>

<span name="to_save" class="button_catch save">保存</span>
<div></div>
</div>
</form>
</div>
<?php
} catch (Exception $e) {
	die("Exception: ". $e->getMessage());
}
?>
<script type="text/javascript">
var flg = 1;
$(function(){
	$(window).on("beforeunload",function(e){
		if (flg) return "保存しましたか？";
	});

	$('span[name="update_ne_master"]').click(function(){
		$('#mode').val('update_ne_master');
		$('#target_page_id').val('<?=$page_id?>');
		var obj_form = $('#controlform');
		obj_form.attr('action','goods_edit.php');
		obj_form.submit();
	});

	$('span[name="download_ne_csv"]').click(function(){
		$('#mode').val('download_ne_csv');
		$('#target_page_id').val('<?=$page_id?>');
		var obj_form = $('#controlform');
		obj_form.attr('action','goods_edit.php');
		obj_form.submit();
	});

	$('span[name="to_save"]').click(function(){
		flg = 0;
		$('#mode').val('save');
		$('#target_page_id').val('');
		var obj_form = $('#controlform');
		obj_form.attr('action','goods_edit.php');
		obj_form.submit();
	});

	$("#catch_phrase1").bind("keyup", function(e){
		display_len($(this), $("#catch_phrase1_len"), $("#catch_phrase1_byte"));
	});
	$("#catch_phrase2").bind("keyup", function(e){
		display_len($(this), $("#catch_phrase2_len"), $("#catch_phrase2_byte"));
	});

	$(window).load(function(){
		display_len(
			$("#catch_phrase1"),
			$("#catch_phrase1_len"),
			$("#catch_phrase1_byte")
		);
		display_len(
			$("#catch_phrase2"),
			$("#catch_phrase2_len"),
			$("#catch_phrase2_byte")
		);
	});
});

function print_r(arr, br, nbsp){
	br = (br) ? br : "\n";
	nbsp = (nbsp) ? nbsp : " ";
	function dump(arr, br, nbsp, level){
		var dumped_text = "";
		if(!level){
			level = 0;
		}
		var level_padding = "";
		for (var j=0; j<level+1; j++){
			level_padding += nbsp + nbsp;
		}
		if (typeof(arr)=="object"){
			for (var item in arr){
				var value = arr[item];
				if (typeof(value)=="object"){
					dumped_text += level_padding + "[" + item + "] => Array" + br;
					dumped_text += nbsp + level_padding + "(" + br
					dumped_text += dump(value, br, nbsp, level+1) + nbsp + level_padding + ")" + br;
				}else{
					dumped_text += level_padding + "[" + item + "] => '" + value + "'" + br;
				}
			}
		}else{
			dumped_text = "===>" + arr + "<===(" + typeof(arr) + ")";
		}
		return dumped_text;
	}
	return "Array" + br + nbsp + "(" + br + dump(arr, br, nbsp) + nbsp + ")";
}
</script>
</body>
</html>
