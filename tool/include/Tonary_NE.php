<?php
// Tonary_NE
// Copyright (C) 2013-2014 Tonary Management System, Inc. All Rights Reserved.

require_once(dirname(__FILE__).'/Tonary.php');
require_once(dirname(__FILE__).'/Tonary_iLogin.php');
require_once(dirname(__FILE__).'/neApiClient.php');

class Tonary_NE extends neApiClient implements Tonary_iLogin{

	const API_LIMIT = 10000;

	public $token;
	public $ne_access_token;
	public $ne_refresh_token;
	public $login_user;

	public function __construct($redirect_dir=null){
		$access_token = null;
		$refresh_token = null;
		if ($redirect_dir == null){
			$tmps = explode('?', $_SERVER['REQUEST_URI']);
			if (empty($_SERVER['HTTPS'])){
				$redirect_dir = 'http://';
			}else{
				$redirect_dir = 'https://';
			}
			$redirect_dir .= $_SERVER['HTTP_HOST'].$tmps[0];
		}
		$this->token = Tonary::get_session('token', Tonary::CRLF_CUT, '');
		$this->ne_access_token = Tonary::get_session('access_token', Tonary::CRLF_CUT, null);
		$this->ne_refresh_token = Tonary::get_session('refresh_token', Tonary::CRLF_CUT, null);
		if($_SERVER['REQUEST_METHOD'] == 'POST'){
			$posted_token = Tonary::get_post('token', Tonary::CRLF_CUT, '');
			if ($posted_token != '' and $posted_token == $this->token){
				$access_token = $this->ne_access_token;
				$refresh_token = $this->ne_refresh_token;
			}
		}
		parent::__construct(Config::$ne_client_id, Config::$ne_client_secret, $redirect_dir
			, $access_token, $refresh_token);
	}

	public function login($id=null, $pass=null){
		$this->login_user = array();
		$api_return = $this->apiExecute('/api_v1_login_user/info');
		$session_pic_mail_address = $_SESSION['pic_mail_address'];
		session_regenerate_id();
		$_SESSION = array();
		if ($api_return['result'] == 'error'){
			header('Content-type: text/html; charset=UTF-8');
			die($api_return['message']);
		}else if ($api_return['result'] == 'success'){
			$this->login_user = $api_return['data'][0];
			$new_access_token = $api_return['access_token'];
			$new_refresh_token = $api_return['refresh_token'];
			if ($new_access_token != $this->ne_access_token
			 or $session_pic_mail_address != $this->login_user['pic_mail_address']){
				$this->token = md5(uniqid(mt_rand(), TRUE));
			}
			$_SESSION['pic_mail_address'] = $this->login_user['pic_mail_address'];
			$_SESSION['token'] = $this->token;
			$this->ne_access_token = $new_access_token;
			$this->ne_refresh_token = $new_refresh_token;
			$_SESSION['access_token'] = $this->ne_access_token;
			$_SESSION['refresh_token'] = $this->ne_refresh_token;
			$this->login_user['id'] = $this->login_user['pic_id'];
			$this->login_user['name'] = $this->login_user['pic_name'];
			$this->login_user['post_name'] = $this->login_user['pic_post_name'];
			$this->login_user['mail_address'] = $this->login_user['pic_mail_address'];

			$api_return = $this->apiExecute('/api_v1_login_company/info');
			if ($api_return['result'] == 'error'){
				header('Content-type: text/html; charset=UTF-8');
				die($api_return['message']);
			}else if ($api_return['result'] == 'success'){
				$this->login_user += $api_return['data'][0];
/*
				$this->login_user['company_name'] = $api_return['data'][0]['company_name'];
				$this->login_user['company_id'] = $api_return['data'][0]['company_id'];
				$this->login_user['company_ne_id'] = $api_return['data'][0]['company_ne_id'];
*/
			}
			if (isset(Config::$system_name)){
				$this->login_user['system_name'] = Config::$system_name;
			}else{
				$this->login_user['system_name'] = $this->login_user['company_name'].' 業務効率化ツール';
			}
			if (Config::$test){
				$this->login_user['system_name'] .= '(テスト環境)';
			}
		}
		return $this->login_user;
	}

	public function logout(){
		$this->token = '';
		$this->ne_access_token = null;
		$this->ne_refresh_token = null;
		session_regenerate_id();
		$_SESSION = array();
		return null;
	}

	public function get_types_from_ne($mysql){
		$rtn = '';
		$sql_e = '';
		$query = array();
		$query['offset'] = 0;
		$query['limit'] = 1000;
		$query['fields'] = 'shop_id,';
		$query['fields'] .= 'shop_name,';
		$query['fields'] .= 'shop_abbreviated_name';
		$query['sort'] = 'shop_id-asc';
		$api_return = $this->apiExecute('/api_v1_master_shop/search', $query) ;
		if ($api_return['result'] != 'success'){
			print '<div class="error">'.$api_return['message'].'</div>'."\n";
			$rtn = $api_return['message'];
			$error_flg = true;
		}else{
			$shops_count = intval($api_return['count']);
			if ($shops_count >= 10000){
				print '<div class="error">店舗の行数が多すぎます</div>'."\n";
				$rtn = '店舗の行数が多すぎます';
				$error_flg = true;
			}
			$datas = $api_return['data'];
			$datas_count = count($datas);
			$sql_e .= 'delete from type_master where member_id = ';
			$sql_e .= $mysql->string(Config::$member_id);
			$sql_e .= ' and type_id = "shop";'."\n";
			for ($i=0; $i<$datas_count; $i++){
				$sql_e .= 'insert into type_master (';
				$sql_e .= 'member_id';
				$sql_e .= ',type_id';
				$sql_e .= ',type_name';
				$sql_e .= ',id';
				$sql_e .= ',name';
				$sql_e .= ',abbreviated_name';
				$sql_e .= ')values(';
				$sql_e .= $mysql->string(Config::$member_id);
				$sql_e .= ', "shop"';
				$sql_e .= ', "店舗"';
				$sql_e .= ','.$mysql->string($datas[$i]['shop_id']);
				$sql_e .= ','.$mysql->string($datas[$i]['shop_name']);
				$sql_e .= ','.$mysql->string($datas[$i]['shop_abbreviated_name']);
				$sql_e .= ');'."\n";
			}
		}
		$query = array();
		$query['offset'] = 0;
		$query['limit'] = 1000;
		$query['fields'] = 'payment_method_id,';
		$query['fields'] .= 'payment_method_name,';
		$query['sort'] = 'payment_method_id-asc';
		$api_return = $this->apiExecute('/api_v1_system_paymentmethod/info', $query) ;
		if ($api_return['result'] != 'success'){
			print '<div class="error">'.$api_return['message'].'</div>'."\n";
			$rtn = $api_return['message'];
			$error_flg = true;
		}else{
			$shops_count = intval($api_return['count']);
			if ($shops_count >= 10000){
				print '<div class="error">支払区分の行数が多すぎます</div>'."\n";
				$rtn = '支払区分の行数が多すぎます';
				$error_flg = true;
			}
			$datas = $api_return['data'];
			$datas_count = count($datas);
			$sql_e .= 'delete from type_master where member_id = ';
			$sql_e .= $mysql->string(Config::$member_id);
			$sql_e .= ' and type_id = "paymentmethod";'."\n";
			for ($i=0; $i<$datas_count; $i++){
				$sql_e .= 'insert into type_master (';
				$sql_e .= 'member_id';
				$sql_e .= ',type_id';
				$sql_e .= ',type_name';
				$sql_e .= ',id';
				$sql_e .= ',name';
				$sql_e .= ',abbreviated_name';
				$sql_e .= ')values(';
				$sql_e .= $mysql->string(Config::$member_id);
				$sql_e .= ', "paymentmethod"';
				$sql_e .= ', "支払区分"';
				$sql_e .= ','.$mysql->string($datas[$i]['payment_method_id']);
				$sql_e .= ','.$mysql->string($datas[$i]['payment_method_name']);
				$sql_e .= ','.$mysql->string($datas[$i]['payment_method_name']);
				$sql_e .= ');'."\n";
			}
		}
		$query = array();
		$query['offset'] = 0;
		$query['limit'] = 1000;
		$query['fields'] = 'delivery_id,';
		$query['fields'] .= 'delivery_name,';
		$query['sort'] = 'delivery_id-asc';
		$api_return = $this->apiExecute('/api_v1_system_delivery/info', $query) ;
		if ($api_return['result'] != 'success'){
			print '<div class="error">'.$api_return['message'].'</div>'."\n";
			$rtn = $api_return['message'];
			$error_flg = true;
		}else{
			$shops_count = intval($api_return['count']);
			if ($shops_count >= 10000){
				print '<div class="error">発送方法区分の行数が多すぎます</div>'."\n";
				$rtn = '発送方法区分の行数が多すぎます';
				$error_flg = true;
			}
			$datas = $api_return['data'];
			$datas_count = count($datas);
			$sql_e .= 'delete from type_master where member_id = ';
			$sql_e .= $mysql->string(Config::$member_id);
			$sql_e .= ' and type_id = "delivery";'."\n";
			for ($i=0; $i<$datas_count; $i++){
				$sql_e .= 'insert into type_master (';
				$sql_e .= 'member_id';
				$sql_e .= ',type_id';
				$sql_e .= ',type_name';
				$sql_e .= ',id';
				$sql_e .= ',name';
				$sql_e .= ',abbreviated_name';
				$sql_e .= ')values(';
				$sql_e .= $mysql->string(Config::$member_id);
				$sql_e .= ', "delivery"';
				$sql_e .= ', "発送方法区分"';
				$sql_e .= ','.$mysql->string($datas[$i]['delivery_id']);
				$sql_e .= ','.$mysql->string($datas[$i]['delivery_name']);
				$sql_e .= ','.$mysql->string($datas[$i]['delivery_name']);
				$sql_e .= ');'."\n";
			}
		}
		if ($mysql->multi_query($sql_e) <> ''){
			Tonary::write_accesslog(__FILE__ . ': error ' . $sql_e);
			$rtn = 'sql error ' . $sql_e;
		}
		return $rtn;
	}

	public function write_errorlog($message = null){
		Tonary::write_errorlog($this->login_user['name'].' '.$message);
	}

	public function write_inspectlog($message = null){
		Tonary::write_inspectlog($this->login_user['user_name'].' '.$message);
	}

	public function write_executelog($message = null){
		Tonary::write_executelog($this->login_user['user_name'].' '.$message);
	}

	public function getData($mysql, $ne_param){
		if (Config::$ne_local){
			return 0;
		}
		if ($ne_param->compare){
			$table_name = 'compare_'.$ne_param->table_name;
		}else if ($ne_param->diff){
			$table_name = 'diff_'.$ne_param->table_name;
		}else{
			$table_name = $ne_param->table_name;
		}
		$key_fields = explode(',', $ne_param->key_field);
		$key_field_types = explode(',', $ne_param->key_field_type);
		$max_datetime = '';
		if ($ne_param->truncate){
			$sql = 'truncate table ' . $table_name;
			if (!$mysql->query($sql)){
				Tonary::write_accesslog(__FILE__ . ': error ' . $sql);
				$e = new Exception(basename(__FILE__) . ': sql error ' . $sql);
				throw $e;
			}
		}else{
			$sql = 'select  max(';
			$sql .= $ne_param->max_datetime_field;
			$sql .= ') max_datetime';
			$sql .= ' from ' . $table_name;
			if (!$rs = $mysql->query($sql)){
				Tonary::write_accesslog(__FILE__ . ': error ' . $sql);
				$e = new Exception(basename(__FILE__) . ': sql error ' . $sql);
				throw $e;
			}
			$row = $mysql->fetch_array($rs);
			if ($row){
				$max_datetime = $row['max_datetime'];
			}
			$mysql->free_result($rs);
		}
		if ($ne_param->max_datetime_from <> ''){
			if ($max_datetime == ''){
				$max_datetime = $ne_param->max_datetime_from;
			}else  if (strtotime($ne_param->max_datetime_from) > strtotime($max_datetime)){
				$max_datetime = $ne_param->max_datetime_from;
			}
		}
		$query_array = array();
		if ($max_datetime <> ''){
			$query_array[$ne_param->max_datetime_field . '-gte'] = $max_datetime;
		}
		$fields_str = '';
		foreach ($ne_param->columns as $column){
			if ($fields_str <> ''){
				$fields_str .= ',';
			}
			$fields_str .= $column['field'];
		}
		$query_array['fields'] = $fields_str;
		$offset = 0;
		$loopflg = true;
		$loopcount = 0;
		while ($loopflg){
			$loopcount += 1;
			$count = 0;
			$loopflg = false;
			$query_array['offset'] = $offset;
			$query_array['limit'] = self::API_LIMIT;
			$api_return = $this->apiExecute($ne_param->api_url, $query_array) ;
			if ($api_return['result'] != 'success'){
				Tonary::write_accesslog(__FILE__ . ': ' . $api_return['message']);
				$e = new Exception(basename(__FILE__) . ': apiExecute error ' . $api_return['message']);
				throw $e;
			}else{
				$count = $api_return['count'];
				if ($ne_param->all){
					if ($count == self::API_LIMIT){
						$loopflg = true;
						$offset = $offset + self::API_LIMIT;
					}
				}
				for ($i=0; $i<$count; $i++){
					$data = $api_return['data'][$i];
					$insert = false;
					if ($ne_param->insertonly and $loopcount<=1){
						$insert = true;
					}else{
						$row_exist = false;
						$sql = 'select ' . $ne_param->key_field;
						$sql .= ' from ' . $table_name;
						for ($j=0; $j<count($key_fields); $j++){
							if ($j == 0){
								$sql .= ' where ';
							}else{
								$sql .= ' and ';
							}
							$sql .= $key_fields[$j] . ' = ';
							if ($key_field_types[$j] == 'int'){
								$sql .= $mysql->numeric($data[$key_fields[$j]],'null');
							}else{
								$sql .= $mysql->string($data[$key_fields[$j]]);
							}
						}
						if (!$rs = $mysql->query($sql)){
							Tonary::write_accesslog(__FILE__ . ': error ' . $sql);
							$e = new Exception(basename(__FILE__) . ': sql error ' . $sql);
							throw $e;
						}
						$row = $mysql->fetch_array($rs);
						if ($row){
							$row_exist = true;
						}
						$mysql->free_result($rs);
						if ($row_exist){
							$sql_e = 'update ' . $table_name . ' set ';
							$sql_first_flg = true;
							foreach ($ne_param->columns as $column){
								if ($sql_first_flg){
									$sql_first_flg = false;
								}else{
									$sql_e .= ',';
								}
								$sql_e .= $column['field'];
								$sql_e .= '=';
								if ($data[$column['type']] == 'int'){
									$sql_e .= $mysql->numeric($data[$column['field']], 'null');
								}else{
									$sql_e .= $mysql->string($data[$column['field']]);
								}
							}
							for ($j=0; $j<count($key_fields); $j++){
								if ($j == 0){
									$sql_e .= ' where ';
								}else{
									$sql_e .= ' and ';
								}
								$sql_e .= $key_fields[$j] . ' = ';
								if ($key_field_types[$j] == 'int'){
									$sql_e .= $mysql->numeric($data[$key_fields[$j]],'null');
								}else{
									$sql_e .= $mysql->string($data[$key_fields[$j]]);
								}
							}
							$sql_e .= "\n";
						}else{
							$insert = true;
						}
					}
					if ($insert){
						$sql_e = 'insert into ' . $table_name . ' (';
						$sql_insert_fields_str = '';
						foreach ($ne_param->columns as $column){
							if ($sql_insert_fields_str <> ''){
								$sql_insert_fields_str .= ',';
							}
							$sql_insert_fields_str .= $column['field'];
						}
						$sql_e .= $sql_insert_fields_str;
						$sql_e .= ') values(';
						$sql_insert_data_str = '';
						foreach ($ne_param->columns as $column){
							if ($sql_insert_data_str <> ''){
								$sql_insert_data_str .= ',';
							}
							if ($data[$column['type']] == 'int'){
								$sql_insert_data_str .= $mysql->numeric($data[$column['field']], 'null');
							}else{
								$sql_insert_data_str .= $mysql->string($data[$column['field']]);
							}
						}
						$sql_e .= $sql_insert_data_str;
						$sql_e .= ')';
						$sql_e .= "\n";
					}
					if ($mysql->multi_query($sql_e) <> ''){
						Tonary::write_accesslog(__FILE__ . ': error ' . $sql_e);
						$e = new Exception(basename(__FILE__) . ': sql error ' . $sql_e);
						throw $e;
					}
				}
			}
		}
		return $count;
	}

	/**
	* override
	*/
	public function apiExecute($path, $api_params = array(), $redirect_uri = NULL){
		$response = parent::apiExecute($path, $api_params, $redirect_uri);
		if ($response['result'] == 'error' or $response['result'] == 'redirect'){
			$message = $path.' ['.$response['code'].']['.$response['message'].']';
			$this->write_errorlog($message);
		}
		return $response;
	}

	public $receiveorder_fields = array(
		array('店舗コード', 'receive_order_shop_id'),
		array('伝票番号', 'receive_order_id'),
		array('受注番号', 'receive_order_shop_cut_form_id'),
		array('受注日', 'receive_order_date'),
		array('取込日', 'receive_order_import_date'),
		array('重要チェック区分', 'receive_order_important_check_id'),
		array('重要チェック名', 'receive_order_important_check_name'),
		array('確認チェック区分', 'receive_order_confirm_check_id'),
		array('確認チェック名', 'receive_order_confirm_check_name'),
		array('システムメッセージ区分', 'receive_order_confirm_ids'),
		array('メール送信状態値', 'receive_order_mail_status'),
		array('受注分類タグ', 'receive_order_gruoping_tag'),
		array('取込種類区分', 'receive_order_import_type_id'),
		array('取込種類名', 'receive_order_import_type_name'),
		array('受注キャンセル区分', 'receive_order_cancel_type_id'),
		array('受注キャンセル名', 'receive_order_cancel_type_name'),
		array('受注キャンセル日', 'receive_order_cancel_date'),
		array('締後修正日', 'receive_order_closed_after_edit_date'),
		array('受注状態区分', 'receive_order_order_status_id'),
		array('受注状態名', 'receive_order_order_status_name'),
		array('発送方法区分', 'receive_order_delivery_id'),
		array('発送方法名', 'receive_order_delivery_name'),
		array('支払区分', 'receive_order_payment_method_id'),
		array('支払名', 'receive_order_payment_method_name'),
		array('総合計', 'receive_order_total_amount'),
		array('税金', 'receive_order_tax_amount'),
		array('手数料', 'receive_order_charge_amount'),
		array('発送代', 'receive_order_delivery_fee_amount'),
		array('他費用', 'receive_order_other_amount'),
		array('ポイント数', 'receive_order_point_amount'),
		array('商品計', 'receive_order_goods_amount'),
		array('入金額', 'receive_order_deposit_amount'),
		array('入金状況区分', 'receive_order_deposit_type_id'),
		array('入金状況名', 'receive_order_deposit_type_name'),
		array('入金日', 'receive_order_deposit_date'),
		array('外国総合計', 'receive_order_foreign_total_amount'),
		array('外国税金', 'receive_order_foreign_tax_amount'),
		array('外国手数料', 'receive_order_foreign_charge_amount'),
		array('外国発送代', 'receive_order_foreign_delivery_fee_amount'),
		array('外国他費用', 'receive_order_foreign_other_amount'),
		array('外国商品計', 'receive_order_foreign_goods_amount'),
		array('外国入金額', 'receive_order_foreign_deposit_amount'),
		array('備考', 'receive_order_note'),
		array('同梱候補伝票番号', 'receive_order_include_possible_order_id'),
		array('同梱先伝票番号', 'receive_order_include_to_order_id'),
		array('複数配送親伝票番号', 'receive_order_multi_delivery_parent_order_id'),
		array('複数配送親フラグ', 'receive_order_multi_delivery_parent_flag'),
		array('納品書印刷指示日', 'receive_order_statement_delivery_instruct_printing_date'),
		array('納品書発行日', 'receive_order_statement_delivery_printing_date'),
		array('納品書特記事項', 'receive_order_statement_delivery_text'),
		array('出荷確定日', 'receive_order_send_date'),
		array('出荷予定日', 'receive_order_send_plan_date'),
		array('出荷順序', 'receive_order_send_sequence'),
		array('作業用欄', 'receive_order_worker_text'),
		array('ピック指示内容', 'receive_order_picking_instruct'),
		array('ピック最小仕入れ先コード', 'receive_order_picking_min_supplier_id'),
		array('ピック最小商品コード', 'receive_order_picking_min_goods_id'),
		array('ラベル発行日', 'receive_order_label_print_date'),
		array('ラベル発行フラグ', 'receive_order_label_print_flag'),
		array('配達希望日', 'receive_order_hope_delivery_date'),
		array('配達希望時間帯区分', 'receive_order_hope_delivery_time_slot_id'),
		array('配達希望時間帯名', 'receive_order_hope_delivery_time_slot_name'),
		array('便種区分', 'receive_order_delivery_method_id'),
		array('便種名', 'receive_order_delivery_method_name'),
		array('シール1区分', 'receive_order_seal1_id'),
		array('シール1名', 'receive_order_seal1_name'),
		array('シール2区分', 'receive_order_seal2_id'),
		array('シール2名', 'receive_order_seal2_name'),
		array('シール3区分', 'receive_order_seal3_id'),
		array('シール3名', 'receive_order_seal3_name'),
		array('シール4区分', 'receive_order_seal4_id'),
		array('シール4名', 'receive_order_seal4_name'),
		array('営業止め区分', 'receive_order_business_office_stop_id'),
		array('営業止め名', 'receive_order_business_office_stop_name'),
		array('送り状区分', 'receive_order_invoice_id'),
		array('送り状名', 'receive_order_invoice_name'),
		array('温度区分', 'receive_order_temperature_id'),
		array('温度名', 'receive_order_temperature_name'),
		array('営業所名', 'receive_order_business_office_name'),
		array('ギフトフラグ', 'receive_order_gift_flag'),
		array('発送伝票番号', 'receive_order_delivery_cut_form_id'),
		array('発送伝票備考欄', 'receive_order_delivery_cut_form_note'),
		array('クレジット区分', 'receive_order_credit_type_id'),
		array('クレジット名', 'receive_order_credit_type_name'),
		array('クレジット承認番号', 'receive_order_credit_approval_no'),
		array('クレジット承認額', 'receive_order_credit_approval_amount'),
		array('クレジット承認区分', 'receive_order_credit_approval_type_id'),
		array('クレジット承認名', 'receive_order_credit_approval_type_name'),
		array('クレジット承認日', 'receive_order_credit_approval_date'),
		array('クレジット承認時レート', 'receive_order_credit_approval_rate'),
		array('クレジット支払い回数', 'receive_order_credit_number_payments'),
		array('クレジット承認センター区分', 'receive_order_credit_authorization_center_id'),
		array('クレジット承認センター名', 'receive_order_credit_authorization_center_name'),
		array('クレジット承認FAX印刷日', 'receive_order_credit_approval_fax_printing_date'),
		array('顧客区分', 'receive_order_customer_type_id'),
		array('顧客名', 'receive_order_customer_type_name'),
		array('顧客コード', 'receive_order_customer_id'),
		array('購入者名', 'receive_order_purchaser_name'),
		array('購入者カナ', 'receive_order_purchaser_kana'),
		array('購入者郵便番号', 'receive_order_purchaser_zip_code'),
		array('購入者住所1', 'receive_order_purchaser_address1'),
		array('購入者住所2', 'receive_order_purchaser_address2'),
		array('購入者電話番号', 'receive_order_purchaser_tel'),
		array('購入者FAX', 'receive_order_purchaser_fax'),
		array('購入者メールアドレス', 'receive_order_purchaser_mail_address'),
		array('送り先名', 'receive_order_consignee_name'),
		array('送り先カナ', 'receive_order_consignee_kana'),
		array('送り先郵便番号', 'receive_order_consignee_zip_code'),
		array('送り先住所1', 'receive_order_consignee_address1'),
		array('送り先住所2', 'receive_order_consignee_address2'),
		array('送り先電話番号', 'receive_order_consignee_tel'),
		array('送り先FAX', 'receive_order_consignee_fax'),
		array('督促開始日', 'receive_order_reminder_start_date'),
		array('最終督促日', 'receive_order_reminder_last_date'),
		array('督促回数', 'receive_order_reminder_count'),
		array('重要チェック担当者ID', 'receive_order_important_check_pic_id'),
		array('重要チェック担当者名', 'receive_order_important_check_pic_name'),
		array('受注担当者ID', 'receive_order_pic_id'),
		array('受注担当者名', 'receive_order_pic_name'),
		array('出荷担当者ID', 'receive_order_send_pic_id'),
		array('出荷担当者名', 'receive_order_send_pic_name'),
		array('削除フラグ', 'receive_order_deleted_flag'),
		array('作成日', 'receive_order_creation_date'),
		array('最終更新日', 'receive_order_last_modified_date'),
		array('最終更新日', 'receive_order_last_modified_null_safe_date'),
		array('作成担当者ID', 'receive_order_creator_id'),
		array('作成担当者名', 'receive_order_creator_name'),
		array('最終更新者ID', 'receive_order_last_modified_by_id'),
		array('最終更新者ID', 'receive_order_last_modified_by_null_safe_id'),
		array('最終更新者名', 'receive_order_last_modified_by_name'),
		array('最終更新者名', 'receive_order_last_modified_by_null_safe_name')
	);

	public $receiveorder_row_fields = array(
		array('伝票番号', 'receive_order_row_receive_order_id'),
		array('受注番号', 'receive_order_row_shop_cut_form_id'),
		array('明細行番号', 'receive_order_row_no'),
		array('受注明細行番号', 'receive_order_row_shop_row_no'),
		array('商品コード', 'receive_order_row_goods_id'),
		array('商品名', 'receive_order_row_goods_name'),
		array('受注数', 'receive_order_row_quantity'),
		array('単価', 'receive_order_row_unit_price'),
		array('外国単価', 'receive_order_row_foreign_unit_price'),
		array('受注時原価', 'receive_order_row_received_time_first_cost'),
		array('掛率', 'receive_order_row_wholesale_retail_ratio'),
		array('小計金額', 'receive_order_row_sub_total_price'),
		array('商品OP', 'receive_order_row_goods_option'),
		array('キャンセルフラグ', 'receive_order_row_cancel_flag'),
		array('同梱元伝票番号', 'receive_order_include_from_order_id'),
		array('同梱元明細行番号', 'receive_order_include_from_row_no'),
		array('複数配送親伝票番号', 'receive_order_row_multi_delivery_parent_order_id'),
		array('引当数', 'receive_order_row_stock_allocation_quantity'),
		array('予約引当数', 'receive_order_row_advance_order_stock_allocation_quantity'),
		array('引当日', 'receive_order_row_stock_allocation_date'),
		array('受注時取扱区分', 'receive_order_row_received_time_merchandise_id'),
		array('受注時取扱名', 'receive_order_row_received_time_merchandise_name'),
		array('受注時商品区分', 'receive_order_row_received_time_goods_type_id'),
		array('受注時商品名', 'receive_order_row_received_time_goods_type_name'),
		array('良品返品数', 'receive_order_row_returned_good_quantity'),
		array('不良品返品数', 'receive_order_row_returned_bad_quantity'),
		array('返品事由区分', 'receive_order_row_returned_reason_id'),
		array('返品事由名', 'receive_order_row_returned_reason_name'),
		array('元受注明細行番号', 'receive_order_row_org_row_no'),
		array('削除フラグ', 'receive_order_row_deleted_flag'),
		array('作成日', 'receive_order_row_creation_date'),
		array('最終更新日', 'receive_order_row_last_modified_date'),
		array('最終更新日', 'receive_order_row_last_modified_null_safe_date'),
		array('受注伝票・受注明細の最終更新日', 'receive_order_row_last_modified_newest_date'),
		array('作成担当者ID', 'receive_order_row_creator_id'),
		array('作成担当者名', 'receive_order_row_creator_name'),
		array('最終更新者ID', 'receive_order_row_last_modified_by_id'),
		array('最終更新者ID', 'receive_order_row_last_modified_by_null_safe_id'),
		array('最終更新者名', 'receive_order_row_last_modified_by_name'),
		array('最終更新者名', 'receive_order_row_last_modified_by_null_safe_name')
	);

	public $receiveorder_status = array(
		array('id'=>0, 'name'=>'取込情報不足'),
		array('id'=>1, 'name'=>'受注メール取込済'),
		array('id'=>2, 'name'=>'起票済(CSV/手入力)'),
		array('id'=>20, 'name'=>'納品書印刷待ち'),
		array('id'=>30, 'name'=>'納品書印刷中'),
		array('id'=>40, 'name'=>'納品書印刷済'),
		array('id'=>50, 'name'=>'出荷確定済（完了）')
	);

	public static $copyright_div = '<div class="copyright">
<a href="https://tonary.sakura.ne.jp/easyorder/policy.html" target="_blank">利用規約</a><br>
Copyright &copy; <a href="http://www.tonary.biz/">Tonary Management System, Inc.</a> All Rights Reserved.
</div>';

}
