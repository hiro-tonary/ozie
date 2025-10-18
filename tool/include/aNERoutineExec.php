<?php
// aNERoutineExec
// Copyright (C) 2015 Tonary Management System, Inc. All Rights Reserved.

require_once(dirname(__FILE__).'/Tonary.php');
require_once(dirname(__FILE__).'/Tonary_ExecuteLog.php');
require_once(dirname(__FILE__).'/NE/NEReceiveOrder.php');
require_once(dirname(__FILE__).'/NE/NEReceiveOrderRow.php');

abstract class aNERoutineExec{

	public $php_id = '';
	public $execute_id = '';
	public $execute_name = '';

	public $executelog = null;
	public $nextengine = null;
	public $print_flg = false;
	public $sleep_sec = 5;
	public $up_texts = array();

	public $not_execute_flg = false;

	public function __construct($nextengine){
		$this->nextengine = $nextengine;
	}

	abstract public function execute();

	public function print_log($mysql){
		print '<table>'."\n";
		print '<tr>'."\n";
		print '<th colspan="8" class="left bold" style="padding-left:60px;">実行日時 実行機能 実行者</th>'."\n";
		print '</tr>'."\n";
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
		$sql .= ' and L.php_id = ';
		$sql .= $mysql->string($this->php_id);
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
				print ' ';
				print Tonary::html($row['execute_id']);
				print Tonary::html($row['execute_name']);
				print ' (';
				print Tonary::html($row['pic_name']);
				print ')';
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
				if (Config::$ne_server_url != ''){
					print ' <span class="button" name="open_ne_slip" slip_no="';
					print Tonary::html($row['receive_order_id']);
					print '" style="font-size:11px;padding:0;">伝票</span>';
				}
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
	}

}
