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
require_once(Config::$global_include_path.'Tonary_NE.php');
require_once(Config::$global_include_path.'Tonary_FileMaker.php');
require_once(dirname(__FILE__).'/../include/Customers.php');

function get_customer_master_path(){
    if (property_exists('Config', 'customer_path') && Config::$customer_path !== ''){
        return Config::$customer_path;
    }
    return '';
}

function load_customer_master(){
    $customers = Customers::$datas;
    $path = get_customer_master_path();
    if ($path !== '' && file_exists($path)){
        $lines = file($path, FILE_IGNORE_NEW_LINES);
        if ($lines !== false){
            $loaded = array();
            foreach ($lines as $line){
                $line = trim($line);
                if ($line === ''){
                    continue;
                }
                $cols = explode("\t", $line);
                if (count($cols) < 6){
                    continue;
                }
                $cols = array_map('trim', $cols);
                $cols = array_pad($cols, 8, '');
                $loaded[] = array(
                    'id' => $cols[0],
                    'shop_id' => ($cols[1] === '') ? 0 : intval($cols[1]),
                    'name' => $cols[2],
                    'shop_name' => ($cols[3] === '') ? $cols[2] : $cols[3],
                    'tax_type' => $cols[4],
                    'tax_method' => $cols[5],
                    'jan' => ($cols[6] === '') ? null : $cols[6],
                    'goods_name' => ($cols[7] === '') ? null : $cols[7]
                );
            }
            if (count($loaded) > 0){
                $customers = $loaded;
            }
        }
    }
    $normalized = array();
    foreach ($customers as $row){
        $row['shop_id'] = isset($row['shop_id']) ? intval($row['shop_id']) : 0;
        if (!isset($row['shop_name'])){
            $row['shop_name'] = isset($row['name']) ? $row['name'] : '';
        }
        if (!array_key_exists('jan', $row)){
            $row['jan'] = null;
        }
        if (!array_key_exists('goods_name', $row)){
            $row['goods_name'] = null;
        }
        $normalized[] = $row;
    }
    return $normalized;
}

$error_flg = false;
$nextengine = null;
$login_user = array();
$token = '';

$mode = '';
$param_start_date = '';
$param_stop_date = '';
$param_customer_ids = array();
$target_goods_ids = array();
$target_goods_ids_query = '';
$goods = array();
$ros = array();
$ros_count = 0;
$datas = array();

try {
    Tonary::session_start();
    Tonary::write_accesslog();
    $nextengine = new Tonary_NE();
    $login_user = $nextengine->login();
    $token = $nextengine->token;

    $customers = load_customer_master();
    $customers_count = count($customers);
    $param_start_date = date('Y-m-d');
    $param_stop_date = date('Y-m-d');

    $mode = Tonary::get_post('mode', Tonary::CRLF_CUT, '');
    $param_start_date = Tonary::get_post('param_start_date', Tonary::CRLF_CUT, $param_start_date);
    $param_stop_date = Tonary::get_post('param_stop_date', Tonary::CRLF_CUT, $param_stop_date);
    if ($param_start_date != ''){
        $param_start_date = date('Y-m-d', strtotime($param_start_date));
    }
    if ($param_stop_date != ''){
        $param_stop_date = date('Y-m-d', strtotime($param_stop_date));
    }
    if($_SERVER['REQUEST_METHOD'] == 'POST'){
        $param_customer_ids = $_POST['param_customer_ids'];
    }else{
        $param_customer_ids = array();
        for ($i=0; $i<$customers_count; $i++){
            $param_customer_ids[$i] = $customers[$i]['id'];
        }
    }
    $param_customer_ids_count = count($param_customer_ids);

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
<title>クリア取込用売上げデータ｜<?=$login_user['system_name']?></title>
<meta name="viewport" content="width=device-width">
<link rel="stylesheet" href="/cssjs/reset2014.css">
<link rel="stylesheet" href="/cssjs/tonary.css">
<link rel="stylesheet" href="<?=Config::$local_css?>">
<script src="/cssjs/jquery.js"></script>
<script src="/cssjs/tonary.js"></script>
<script>
$(function(){
    //実行
    $('span[name="to_exec"]').click(function() {
        $('#mode').val('exec');
        var obj_form = $('#controlform');
        obj_form.attr('action','csv_for_clear.php');
        obj_form.submit();
    });
    //過去のCSVをダウンロード
    $("span[id^='clear_']").click(function() {
        $("#subdir").val("clear_csv");
        $("#filename").val($(this).attr("id"));
        var obj_form = $("#controlform");
        obj_form.attr("action","tmp_download.php");
        obj_form.submit();
    });
    //得意先編集
    $('span[name="edit_customers"]').click(function() {
        $('#editform').submit();
    });
});
</script>
</head>
<body>
<h1>クリア取込用売上げデータ｜<?=$login_user['system_name']?></h1>
<div class="breadcrumb">
<a href="../index.php">トップ</a>
 &gt; <label>クリア取込用売上げデータ</label>
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
<input type="hidden" name="token" id="token" value="<?=$token?>">
<input type="hidden" name="mode" id="mode" value="">
<input type="hidden" name="subdir" id="subdir" value="">
<input type="hidden" name="filename" id="filename" value="">
<div class="content" style="margin-top:8px;margin-bottom:36px;">
<div class="developer small">
↓売上データに含まれる商品コード数の上限が1000です。それ以上になりそうな場合、ご連絡ください。
</div>
<div>
<span style="float:left;">
<span class="label small">出荷日</span><br>
<input type="date" name="param_start_date" value="<?=$param_start_date?>">
～
<input type="date" name="param_stop_date" value="<?=$param_stop_date?>">
</span>
<span style="margin-left:8px;float:left;">
<br>
<span class="button" name="to_exec" style="width:60px;text-align:center;">
実行</span>
</span>
</div>
<div style="clear:both;"></div>
<div class="label small" style="margin-top:8px;">
<span style="display:inline-block;min-width:72px;">得意先</span>
<span style="display:inline-block;margin-left:8px;">
<?php
    for ($i=0; $i<$customers_count; $i++){
        print '<label style="margin-right:8px;white-space:nowrap;">';
        print '<input type="checkbox" name="param_customer_ids[]"';
        print ' value="'.$customers[$i]['id'].'"';
        print (in_array($customers[$i]['id'], $param_customer_ids))?' checked':'';
        print '>'.$customers[$i]['name'];
        print '</label>';
    }
?>
</span>
</div>
<div style="margin:6px 0 12px 0;">
<span class="button" name="edit_customers">得意先編集</span>
</div>
<div style="clear:both;"></div>
<?php
    if ($mode == 'exec' and $error_flg == false){
        if ($param_customer_ids_count <= 0){
            $error_flg = true;
            $error_message = '得意先を選択してください。';
        }
        if ($param_start_date == ''){
            $error_flg = true;
            $error_message = '出荷日(FROM)を指定してください。';
        }
        if ($param_stop_date == ''){
            $error_flg = true;
            $error_message = '出荷日(TO)を指定してください。';
        }
    }
    if ($mode == 'exec' and $error_flg == false){
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
        $query_array['limit'] = 10000;
        $query_array['fields'] = $ro_fields_query;
        $query_array['receive_order_delivery_cut_form_id-neq'] = '';
        $query_array['receive_order_deleted_flag-neq'] = '1';
        $query_array['receive_order_row_cancel_flag-neq'] = '1';
        $query_array['receive_order_row_deleted_flag-neq'] = '1';
        $query_array['receive_order_send_date-gte'] = $param_start_date;
        $query_array['receive_order_send_date-lte'] = $param_stop_date;
        $query_array['sort'] = 'receive_order_id-asc';
        $api_return = $nextengine->apiExecute('/api_v1_receiveorder_row/search', $query_array);
/*
print '<pre>query_array:';
print_r($query_array);
print '<pre>';
print '<pre>api_return:';
print_r($api_return);
print '<pre>';
*/
        if ($api_return['result'] != 'success'){
            $error_flg = true;
            $error_message = 'システムエラー '.$api_return['message'];
        }else{
            $ros = $api_return['data'];
            $ros_count = intval($api_return['count']);
            for ($i=0; $i<$ros_count; $i++){
                $goods_id = $ros[$i]['receive_order_row_goods_id'];
                if (in_array($goods_id, $target_goods_ids) == false){
                    array_push($target_goods_ids, $goods_id);
                    if ($target_goods_ids_query != ''){
                        $target_goods_ids_query .= ',';
                    }
                    $target_goods_ids_query .= $goods_id;
                }
            }
        }
    }
    if ($mode == 'exec' and $error_flg == false){
        if ($target_goods_ids_query != ''){
            require_once(Config::$global_include_path.'NE/NEBackupGoods.php');
            $target = new NEBackupGoods();
            $query_array = array();
            $query_array['offset'] = 0;
            $query_array['limit'] = 10000;
            $query_array['fields'] = $target->query_fields;
            $query_array['goods_id-in'] = $target_goods_ids_query;
            $query_array['goods_deleted_flag-neq'] = '1';
            $query_array['sort'] = 'goods_representation_id-asc,goods_id-asc';
            $api_return = $nextengine->apiExecute('/api_v1_master_goods/search', $query_array);
            if ($api_return['result'] != 'success'){
                $error_flg = true;
                $error_message = 'システムエラー '.$api_return['message'];
            }else{
                $goods = $api_return['data'];
                $goods_count = intval($api_return['count']);
            }
        }
    }
    $adds = array();
    $a = 0;
    if ($mode == 'exec' and $error_flg == false){
        for ($i=0; $i<$ros_count; $i++){
            $goods_id = $ros[$i]['receive_order_row_goods_id'];
            $charge_amount = $ros[$i]['receive_order_charge_amount'];
            $delivery_fee_amount = $ros[$i]['receive_order_delivery_fee_amount'];
            $other_amount = $ros[$i]['receive_order_other_amount'];
            $point_amount = $ros[$i]['receive_order_point_amount'];
            $gift_flag = $ros[$i]['receive_order_gift_flag'];
            $delivery_id = $ros[$i]['receive_order_delivery_id'];
            $receive_order_payment_method_id = $ros[$i]['receive_order_payment_method_id'];

            $ros[$i]['unit_price']
                 = intval($ros[$i]['receive_order_row_unit_price']);
            $ros[$i]['send_date']
                 = date('Y/n/j', strtotime($ros[$i]['receive_order_send_date']));
/*
            $ros[$i]['payment_method_name'] = mb_substr(
                $ros[$i]['receive_order_payment_method_name'],
                0, 7
            );
*/
            $tmp = $ros[$i]['receive_order_payment_method_name'];
            if (strpos($tmp, 'クレジットカード') !== false){
                $tmp = 'クレジット';
            }else if (strpos($tmp, 'Amazonペイメント') !== false){
                $tmp = 'クレジット';
            }else if (strpos($tmp, 'ポイント') !== false){
                $tmp = 'ポイント';
            }else if (strpos($tmp, '代金引換') !== false){
                $tmp = '代金引換';
            }else if (strpos($tmp, 'みずほ銀行') !== false){
                $tmp = 'みずほ普通';
            }else if (strpos($tmp, 'みすほ銀行') !== false){
                $tmp = 'みずほ普通';
            }else if (strpos($tmp, 'ジャパンネットバンク') !== false){
                $tmp = 'ＪＮＢ';
            }else if (strpos($tmp, '銀行振込') !== false){
                $tmp = '銀行振込';
            }else if (strpos($tmp, 'ぱるる') !== false){
                $tmp = 'ぱるる';
            }else if (strpos($tmp, '郵便送金') !== false){
                $tmp = '銀行振込';
            }else if (strpos($tmp, 'ＹＪＰＰＯＩＮＴ') !== false){
                $tmp = 'Ｙポイント';
            }
            $ros[$i]['payment_method_name'] = $tmp;
            for ($c=0; $c<$customers_count; $c++){
                if ($ros[$i]['receive_order_shop_id'] == $customers[$c]['shop_id']){
                    $ros[$i]['customer_code'] = $customers[$c]['id'];
                    $ros[$i]['id'] = $customers[$c]['id'];
                    $ros[$i]['shop_name'] = $customers[$c]['shop_name'];
                    $ros[$i]['tax_method'] = $customers[$c]['tax_method'];
                    $ros[$i]['tax_type'] = $customers[$c]['tax_type'];
                    break;
                }
            }

            for ($g=0; $g<$goods_count; $g++){
                if ($goods_id == $goods[$g]['goods_id']){
                    $ros[$i]['jan'] = $goods[$g]['goods_model_number'];
                    if ($goods_id == 'nekutai'){
                        $quantity = intval($ros[$i]['receive_order_row_quantity']);
//                        $ros[$i]['unit_price'] = -1100;
                        $ros[$i]['unit_price'] = -1650;
                    }
                    break;
                }
            }
            if ($delivery_id == 80 or $delivery_id == 98 or $delivery_id == 99){
                $ros[$i]['id'] = '000103';
                $ros[$i]['customer_code'] = '000103';
                $ros[$i]['shop_name'] = '楽天海外';
                $ros[$i]['tax_method'] = '免税';
                $ros[$i]['tax_type'] = '非課税';
            }

            if ($ros[$i]['receive_order_id'] != $ros[($i+1)]['receive_order_id']){
                if ($point_amount > 0){
                    if ($ros[$i]['receive_order_shop_id'] == 1){
                        $adds[$a] = $ros[$i];
                        $adds[$a]['customer_code'] = 'POINT';
                        $adds[$a]['tax_method'] = '内税';
                        $adds[$a]['jan'] = '1313';
                        $adds[$a]['receive_order_row_goods_id']
                            = 'ozie本店 ﾎﾟｲﾝﾄ還元 値引';
                        $adds[$a]['receive_order_row_quantity'] = 1;
                        $adds[$a]['unit_price'] = -1 * intval($point_amount);
                        $adds[$a]['tax_type'] = '非課税';
                        $a++;
                    }
                }
                if ($other_amount == -500){
                    if ($ros[$i]['receive_order_shop_id'] == 1){
                        $adds[$a] = $ros[$i];
                        $adds[$a]['customer_code'] = '000100';
                        $adds[$a]['tax_method'] = '内税';
                        $adds[$a]['jan'] = '1082';
                        $adds[$a]['receive_order_row_goods_id']
                            = 'ozie本店NEWクーポン 値引';
                        $adds[$a]['receive_order_row_quantity'] = 1;
                        $adds[$a]['unit_price'] = -500;
                        $adds[$a]['tax_type'] = '非課税';
                        $a++;
                    }
                }
                if ($gift_flag == 1){
                    $adds[$a] = $ros[$i];
                    $adds[$a]['customer_code'] = 'GIFT';
                    $adds[$a]['tax_method'] = '内税';
                    $adds[$a]['jan'] = '1555';
                    $adds[$a]['receive_order_row_goods_id'] = 'ギフト';
                    $adds[$a]['receive_order_row_quantity'] = 1;
                    if ($ros[$i]['receive_order_shop_id'] == 1){
                        $adds[$a]['unit_price'] = intval($charge_amount);
                    }else{
                        $adds[$a]['unit_price'] = intval($other_amount);
                    }
                    $adds[$a]['tax_type'] = '課税';
                    $a++;
                }else if ($charge_amount > 0
                      and $ros[$i]['receive_order_shop_id'] == 4
                      and $receive_order_payment_method_id == 1){
                    $adds[$a] = $ros[$i];
                    $adds[$a]['customer_code'] = 'DAIBIKI';
                    $adds[$a]['tax_method'] = '内税';
                    $adds[$a]['jan'] = '1333';
                    $adds[$a]['receive_order_row_goods_id'] = 'ｱﾏｿﾞﾝ代金引換手数料';
                    $adds[$a]['receive_order_row_quantity'] = 1;
                    $adds[$a]['unit_price'] = intval($charge_amount);
                    $adds[$a]['tax_type'] = '課税';
                    $a++;
                }
                if ($delivery_fee_amount > 0){
                    $adds[$a] = $ros[$i];
                    $adds[$a]['customer_code'] = 'DELIVERY';
                    if ($ros[$i]['customer_code'] == '000103'){
                        $adds[$a]['tax_method'] = '免税';
                        $adds[$a]['receive_order_row_goods_id'] = '送料　海外発送';
                        $adds[$a]['tax_type'] = '非課税';
                    }else{
                        $adds[$a]['tax_method'] = '内税';
                        $adds[$a]['receive_order_row_goods_id'] = '送料';
                        $adds[$a]['tax_type'] = '課税';
                    }
                    $adds[$a]['jan'] = '1090';
                    $adds[$a]['receive_order_row_quantity'] = 1;
                    $adds[$a]['unit_price'] = intval($delivery_fee_amount);
                    $a++;
                }
            }
        }
        $ros = array_merge($ros, $adds);
        usort($ros, function($a, $b){
            if ($a['receive_order_id'] == $b['receive_order_id']){
                if ($a['receive_order_row_no'] == $b['receive_order_row_no']){
                    return $a['receive_order_row_goods_id'] > $b['receive_order_row_goods_id'];
                }
                return $a['receive_order_row_no'] > $b['receive_order_row_no'];
            }
            return $a['receive_order_id'] > $b['receive_order_id'];
        });
        $j = 0;
        for ($i=0; $i<count($ros); $i++){
            if (in_array($ros[$i]['customer_code'], $param_customer_ids)){
                $datas[$j] = $ros[$i];
                $j++;
            }else{
//                print $ros[$i]['id'];
            }
        }
        $datas_count = count($datas);
    }
    if ($error_flg){
        print '<div class="error">';
        print $error_message;
        print '</div>'."\n";
    }
    if ($mode == 'exec' and $error_flg == false){
        print '<div class="report">';
        print $datas_count;
        print '行ヒット</div>'."\n";
        if ($datas_count > 0){
            $filemaker = new Tonary_FileMaker();
            $filemaker->set_quot('"');
            $filemaker->set_fp(null);
            $filemaker->tokens = array(
                array('売上番号','receive_order_id')
                ,array('売上日','send_date')
                ,array('得意先コード','id')
                ,array('得意先名','shop_name')
                ,array('納品先郵便番号（親番）','')
                ,array('納品先郵便番号（枝番）','')
                ,array('納品先住所（上段）','')
                ,array('納品先住所（下段）','')
                ,array('得意先担当者','')
                ,array('取引形態','')
                ,array('消費税計算方法','tax_method')
                ,array('請求締日','')
                ,array('入金予定日','')
                ,array('入金方法','')
                ,array('自社部門コード','')
                ,array('自社部門名','')
                ,array('自社営業担当者コード','')
                ,array('自社営業担当者名','')
                ,array('商品コード','jan')
                ,array('商品名','receive_order_row_goods_id')
                ,array('数量','receive_order_row_quantity')
                ,array('単位','')
                ,array('販売単価','unit_price')
                ,array('消費税課税区分','tax_type')
                ,array('備考','payment_method_name')
                ,array('事業区分','')
                ,array('商品名（下段）','')
                ,array('原単価','')
            );
            $filemaker->data_array = $datas;
            $filemaker->set_print_text(false);
            $filemaker->set_enc_type(Tonary_FileMaker::E_SJIS);
            $filemaker->set_write_type(Tonary_FileMaker::W_CSV);
            $csv_text_sjis = $filemaker->execute();

/*
            $filemaker->return_text = '';
            $filemaker->set_print_text(true);
            $filemaker->set_enc_type(Tonary_FileMaker::E_UTF);
            $filemaker->set_write_type(Tonary_FileMaker::W_HTML);
            $filemaker->execute();
*/
            $csv_filename = 'clear_'.date('Y_m_d_H_i_s').'.csv';
            $csv_filepath = Config::$tmpdir_path.'clear_csv/'.$csv_filename;
            $csv_fp = fopen($csv_filepath, 'w');
            if ($csv_fp === false) {
                $nextengine->write_errorlog("fopen error " . $csv_filepath);
                die("fopen error " . $csv_filepath);
            }
            $csv_text_sjis = strtr(
                $csv_text_sjis,
                array(
                    "\r" => "\r\n",
                    "\n" => "\r\n"
                )
            );
            fwrite($csv_fp, $csv_text_sjis);
            if ($csv_fp != null){
                fclose($csv_fp);
            }
        }
    }
?>
<div style="margin-top:16px;" class="explain small">
楽天海外: 受注の発送方法が98:楽天国際発送、99:海外発送、80:EMSの場合<br>
　得意先コード:000103、得意先名:楽天海外、消費税計算方法:免税、商品コード:jan、商品名:商品cd、<br>
　数量:受注数、販売単価:売単価、消費税課税区分:非課税、備考:支払方法<br>
<br>
BtoB: 受注の店舗が5:実店舗の受注（店舗を新規登録して、受注を手入力してください）<br>
<br>
ポイント: 受注のポイント数がある場合（本店のみ）
<span style="text-decoration:line-through;color:red;">（運用で本店以外にはポイントが無いので、本店のみ出力されます。）</span><br>
　得意先コード:店舗毎、得意先名:店舗毎、消費税計算方法:内税、商品コード:1313、商品名:ozie本店 ﾎﾟｲﾝﾄ還元 値引、<br>
　数量:1、販売単価:ポイント数、消費税課税区分:非課税、備考:支払方法<br>
<br>
ギフト: 受注のギフトが1:有りの場合<br>
　得意先コード:店舗毎、得意先名:店舗毎、消費税計算方法:内税、商品コード:1555、商品名:ギフト、<br>
　数量:1、販売単価:(本店は)手数料(その他は)他費用、消費税課税区分:課税、備考:支払方法<br>
<br>
送料: 受注の発送代がある場合<br>
　得意先コード:店舗毎、得意先名:店舗毎、消費税計算方法:内税、商品コード:1090、商品名:送料、<br>
　数量:1、販売単価:発送代、消費税課税区分:課税、備考:支払方法<br>
<br>
nekutai: 販売単価:-1650<br>
</div>
<?php
    $csv_dir = Config::$tmpdir_path.'clear_csv/';
    $del_time = strtotime('- '.Config::$keep_days.' days');
    $del_ymd = date('Ymd', strtotime('- '.Config::$keep_days.' days'));
    $list = scandir($csv_dir);
    if ($list === false){
        print 'scandir error '.$csv_dir;
    }else{
        print '<div class="report" style="margin-top:16px;margin-bottom:8px;">';
        print '↓過去のCSVをダウンロードできます。<div>';
        foreach($list as $file){
            if($file == '.' || $file == '..'){
                continue;
            } else if (is_file($csv_dir.$file)) {
                $flg = true;
                if (filemtime($csv_dir.$file) < $del_time){
                    unlink($csv_dir.$file);
                    $flg = false;
                }
                if ($flg){
                    print '<div style="margin-bottom:8px;"><span id="';
                    print Tonary::html($file);
                    print '" class="button">';
                    print Tonary::html($file);
                    print '</span>';
                    print '</div>';
                }
            }
        }
    }
?>
</div>
</form>
<form id="editform" method="post" action="customers_edit.php" style="display:none;">
<input type="hidden" name="token" value="<?=$token?>">
</form>
</body>
</html>
<?php
    if ($mode == 'exec' and $csv_filename != ''){
        print '<script>'."\n";
        print '$(function(){'."\n";
        print '$("#subdir").val("clear_csv");'."\n";
        print '$("#filename").val("'.$csv_filename.'");'."\n";
        print 'var obj_form = $("#controlform");'."\n";
        print 'obj_form.attr("action","tmp_download.php");'."\n";
        print 'obj_form.submit();'."\n";
        print '});'."\n";
        print '</script>'."\n";
    }
} catch (Exception $e) {
    if ($nextengine != null){
        $nextengine->write_errorlog('Exception: '. $e->getMessage());
    }else{
        Tonary::write_errorlog('Exception: '. $e->getMessage());
    }
    die('Exception: '. $e->getMessage());
}
?>
