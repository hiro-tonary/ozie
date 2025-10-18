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
require_once(Config::$global_include_path.'Tonary_FileMaker.php');
try {
	Tonary::session_start();
	Tonary::write_accesslog();
	$nextengine = new Tonary_NE();
	$login_user = $nextengine->login();
	$token = $nextengine->token;
	$mysql = new Tonary_MySQL();

	$execute_datetime = Tonary::get_post('logparam_execute_datetime');

	$datas = array();
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
	if ($execute_datetime <> ''){
		$sql .= ' and L.execute_datetime = "';
		$sql .= $execute_datetime;
		$sql .= '"';
	}
	$sql .= ' order by L.execute_datetime desc, L.receive_order_shop_id, L.receive_order_payment_method_id, L.receive_order_id';
	$sql .= ' limit 0,10000';
	if (!$rs = $mysql->query($sql)){
		die($sql.' error');
	}
	$row = $mysql->fetch_array($rs);
	$i = 0;
	while($row){
		$datas[$i] = $row;
		$i++;
		$row = $mysql->fetch_array($rs);
	}
	$mysql->free_result($rs);

	$filemaker = new Tonary_FileMaker();
	$filemaker->set_enc_type(Tonary_FileMaker::E_SJIS);
	$filemaker->set_write_type(Tonary_FileMaker::W_CSV);
	$filemaker->set_quot('"');
	$filemaker->set_fp(null);
	$filemaker->set_print_text(false);
	$filemaker->tokens = array(
		array('実行日','execute_datetime')
		,array('実行内容','execute_name')
		,array('実行担当者','pic_name')
		,array('店舗','shop_name')
		,array('支払方法','payment_method_name')
		,array('伝票番号','receive_order_id')
		,array('受注番号','receive_order_shop_cut_form_id')
		,array('発送方法','delivery_name')
		,array('受注日','receive_order_date')
		,array('担当者','receive_order_creator_name')
	);
	$filemaker->data_array = $datas;
	$csv_text_sjis = $filemaker->execute();
	$csv_text_sjis = strtr(
		$csv_text_sjis,
		array(
			"\r" => "\r\n",
			"\n" => "\r\n"
		)
	);
	if ($execute_datetime <> ''){
		$file_datetime = strtotime($execute_datetime);
		$filename = 'log-'.date('Y_m_d_H_i_s', $file_datetime).'.csv';
	}else{
		$filename = 'log.csv';
	}
	header('Content-Type: text/comma-separated-values; name="'.$filename.'"');
	header('Content-Disposition: attachment; filename="'.$filename.'"');
	print $csv_text_sjis;

} catch (Exception $e) {
	if ($nextengine != null){
		$nextengine->write_errorlog('Exception: '. $e->getMessage());
	}else{
		Tonary::write_errorlog('Exception: '. $e->getMessage());
	}
	die('Exception: '. $e->getMessage());
}
