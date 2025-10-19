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

function customers_to_tsv(array $customers){
    $lines = array();
    foreach ($customers as $row){
        $lines[] = implode("\t", array(
            isset($row['id']) ? $row['id'] : '',
            isset($row['shop_id']) ? strval($row['shop_id']) : '0',
            isset($row['name']) ? $row['name'] : '',
            isset($row['shop_name']) ? $row['shop_name'] : '',
            isset($row['tax_type']) ? $row['tax_type'] : '',
            isset($row['tax_method']) ? $row['tax_method'] : '',
            (isset($row['jan']) && $row['jan'] !== null) ? $row['jan'] : '',
            (isset($row['goods_name']) && $row['goods_name'] !== null) ? $row['goods_name'] : ''
        ));
    }
    return implode("\n", $lines);
}

function normalize_customer_tsv($tsv){
    $lines = preg_split("/\r\n|\r|\n/", $tsv);
    $normalized = array();
    $errors = array();
    $ids = array();
    $line_no = 0;
    foreach ($lines as $line){
        $line_no++;
        $raw = trim($line);
        if ($raw === ''){
            continue;
        }
        $cols = explode("\t", $line);
        if (count($cols) < 6){
            $errors[] = '行'.$line_no.'：カラム数が不足しています。8項目（id, shop_id, name, shop_name, tax_type, tax_method, jan, goods_name）を指定してください。';
            continue;
        }
        $cols = array_map('trim', $cols);
        $cols = array_pad($cols, 8, '');
        if ($cols[0] === ''){
            $errors[] = '行'.$line_no.'：id が空です。';
        }
        if ($cols[1] === ''){
            $cols[1] = '0';
        }
        if (!is_numeric($cols[1])){
            $errors[] = '行'.$line_no.'：shop_id は数値で指定してください。';
        }
        if ($cols[4] === ''){
            $errors[] = '行'.$line_no.'：tax_type を指定してください。';
        }
        if ($cols[5] === ''){
            $errors[] = '行'.$line_no.'：tax_method を指定してください。';
        }
        if ($cols[0] !== '' && isset($ids[$cols[0]])){
            $errors[] = '行'.$line_no.'：id が重複しています。';
        }
        if ($cols[0] !== ''){
            $ids[$cols[0]] = true;
        }
        $normalized[] = implode("\t", array(
            $cols[0],
            strval(intval($cols[1])),
            $cols[2],
            $cols[3],
            $cols[4],
            $cols[5],
            $cols[6],
            $cols[7]
        ));
    }
    if (count($normalized) === 0){
        $errors[] = '保存対象となるレコードがありません。';
    }
    return array($normalized, $errors);
}

$error_messages = array();
$info_message = '';
$customers = array();
$customer_tsv = '';
$customer_path = get_customer_master_path();
$token = '';
$nextengine = null;
$login_user = array();

try {
    Tonary::session_start();
    Tonary::write_accesslog();
    $nextengine = new Tonary_NE();
    $login_user = $nextengine->login();
    $token = $nextengine->token;
    $session_token = Tonary::get_session('token', Tonary::CRLF_CUT, '');

    $customers = load_customer_master();
    $customer_tsv = customers_to_tsv($customers);

    $mode = Tonary::get_post('mode', Tonary::CRLF_CUT, '');
    if ($_SERVER['REQUEST_METHOD'] == 'POST'){
        $posted_token = Tonary::get_post('token', Tonary::CRLF_CUT, '');
        if ($posted_token == '' || $posted_token != $session_token){
            $error_messages[] = '不正なトークンです。';
        }
    }
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && $mode == 'save' && count($error_messages) == 0){
        $customer_tsv = Tonary::get_post('customer_tsv', Tonary::CRLF_USE, '');
        list($normalized_lines, $parse_errors) = normalize_customer_tsv($customer_tsv);
        if (count($parse_errors) > 0){
            $error_messages = array_merge($error_messages, $parse_errors);
        }
        if ($customer_path === ''){
            $error_messages[] = 'Config::$customer_path が設定されていないため、保存できません。';
        }
        if (count($error_messages) == 0){
            $dir = dirname($customer_path);
            if (!is_dir($dir)){
                if (!@mkdir($dir, 0777, true)){
                    $error_messages[] = '保存先ディレクトリを作成できませんでした: '.$dir;
                }
            }
        }
        if (count($error_messages) == 0){
            $output_text = implode("\n", $normalized_lines);
            if ($output_text !== ''){
                $output_text .= "\n";
            }
            $tmp_path = $customer_path.'.tmp';
            if (file_exists($customer_path)){
                @copy($customer_path, $customer_path.'.bak');
            }
            if (file_put_contents($tmp_path, $output_text) === false){
                $error_messages[] = '一時ファイルを書き込めませんでした: '.$tmp_path;
                @unlink($tmp_path);
            } else if (!@rename($tmp_path, $customer_path)){
                $error_messages[] = 'ファイルを保存できませんでした: '.$customer_path;
                @unlink($tmp_path);
            } else {
                $info_message = '得意先マスタを保存しました。';
                $customers = load_customer_master();
                $customer_tsv = customers_to_tsv($customers);
            }
        }
    }
} catch (Exception $e) {
    if ($nextengine != null){
        $nextengine->write_errorlog('Exception: '. $e->getMessage());
    }else{
        Tonary::write_errorlog('Exception: '. $e->getMessage());
    }
    die('Exception: '. $e->getMessage());
}
try{
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
<!DOCTYPE html>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<meta name="robots" content="noindex,nofollow">
<meta http-equiv='Pragma' content='no-cache'>
<meta http-equiv='cache-control' content='no-cache'>
<meta http-equiv='expires' content='0'>
<title>得意先編集｜<?=$login_user['system_name']?></title>
<meta name="viewport" content="width=device-width">
<link rel="stylesheet" href="/cssjs/reset2014.css">
<link rel="stylesheet" href="/cssjs/tonary.css">
<link rel="stylesheet" href="<?=Config::$local_css?>">
<script src="/cssjs/jquery.js"></script>
<script>
$(function(){
    $('span[name="save_customers"]').click(function() {
        $('#mode').val('save');
        $('#editform').submit();
    });
    $('span[name="back_to_list"]').click(function() {
        window.location.href = 'csv_for_clear.php';
    });
});
</script>
</head>
<body>
<h1>得意先編集｜<?=$login_user['system_name']?></h1>
<div class="breadcrumb">
<a href="csv_for_clear.php">クリア取込用売上げデータ</a>
 &gt; <label>得意先編集</label>
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
<?php
    if ($customer_path === ''){
        print '<div class="error">Config::$customer_path が設定されていません。システム管理者にご連絡ください。</div>';
    }
    if (count($error_messages) > 0){
        print '<div class="error">';
        foreach ($error_messages as $message){
            print Tonary::html($message).'<br>';
        }
        print '</div>';
    }
    if ($info_message != ''){
        print '<div class="report">'.Tonary::html($info_message).'</div>';
    }
?>
<div class="explain small" style="margin-bottom:8px;">
保存先: <?=Tonary::html(($customer_path === '') ? '(未設定)' : $customer_path)?><br>
各行はタブ区切りで、下記 8 項目を順に入力してください。<br>
<code>id	shop_id	name	shop_name	tax_type	tax_method	jan	goods_name</code>
</div>
<form id="editform" method="post" action="customers_edit.php">
<input type="hidden" name="token" value="<?=Tonary::html($token)?>">
<input type="hidden" name="mode" id="mode" value="">
<textarea name="customer_tsv" rows="18" style="width:100%;"><?=Tonary::html($customer_tsv)?></textarea>
<div style="margin-top:12px;">
<span class="button" name="save_customers" style="margin-right:12px;">保存</span>
<span class="button" name="back_to_list">クリア取込用売上げデータに戻る</span>
</div>
</form>
</div>
</body>
</html>
<?php
} catch (Exception $e) {
    if ($nextengine != null){
        $nextengine->write_errorlog('Exception: '. $e->getMessage());
    }else{
        Tonary::write_errorlog('Exception: '. $e->getMessage());
    }
    die('Exception: '. $e->getMessage());
}
?>
