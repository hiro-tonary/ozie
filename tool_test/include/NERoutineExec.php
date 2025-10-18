<?php
// NERoutineExec

require_once(Config::$global_include_path.'aNERoutineExec.php');

class NERoutineExec extends aNERoutineExec{

	public $params = array(
		'wait_flag' => null,
	);

	public function execute(){
		$php_id = 'NERoutineExec';
		$pattern_text = '[PATTERN001]';
		$pattern_name = '裄丈詰加工';
		$datas = array();
		$error_flg = false;
		$rtn_message = '';
		try{
			$this->executelog = new Tonary_ExecuteLog(
				null,
				$this->nextengine->login_user,
				array(
					'php_id' => $php_id,
					'execute_id' => $pattern_text,
					'execute_name' => $pattern_name
				)
			);
			$ro_fields_query = '';
			for ($j=0; $j<count(NEReceiveOrder::$fields); $j++){
				if ($ro_fields_query != ''){
					$ro_fields_query .= ',';
				}
				$ro_fields_query .= NEReceiveOrder::$fields[$j]['field'];
			}
			for ($j=0; $j<count(NEReceiveOrderRow::$fields); $j++){
				if ($ro_fields_query != ''){
					$ro_fields_query .= ',';
				}
				$ro_fields_query .= NEReceiveOrderRow::$fields[$j]['field'];
			}
			$query_ro = array();
			$query_ro['fields'] = $ro_fields_query;
			$query_ro['offset'] = 0;
			$query_ro['limit'] = 10000;
			$query_ro['receive_order_shop_id-in'] = '1';
			$query_ro['receive_order_row_goods_option-like'] = '%裄丈詰め加工：▼%';
			$query_ro['receive_order_order_status_id-in'] = '2,20';
			$query_ro['receive_order_cancel_type_id-eq'] = '0';
			$query_ro['receive_order_row_cancel_flag-neq'] = '1';
			$query_ro['sort'] = 'receive_order_id-asc,receive_order_row_no-asc';
			$api_return = $this->nextengine->apiExecute('/api_v1_receiveorder_row/search', $query_ro);
			if ($api_return['result'] != 'success'){
				$rtn_message .= 'API ERROR '.$api_return['message']."\n";
				$executelog->set_log_message(
					'ネクストエンジン: '.$api_return['message'],
					print_r($api_return, true),
					-1
				);
				$error_flg = true;
			}else{
				if (intval($api_return['count']) >= 10000){
					$rtn_message .= '行数が多すぎます'."\n";
					$executelog->set_log_message(
						'ネクストエンジン: 該当する受注行数が10000件を超えています',
						'',
						-1
					);
					$error_flg = true;
				}
				$ros = $api_return['data'];
			}
			if ($error_flg == false){
				$query_ro = array();
				$query_ro['fields'] = $ro_fields_query;
				$query_ro['offset'] = 0;
				$query_ro['limit'] = 10000;
				$query_ro['receive_order_shop_id-in'] = '2,3';
				$query_ro['receive_order_row_goods_name-like'] = '%【裄丈詰め加工】%';
//				$query_ro['receive_order_row_goods_option-like'] = '%寸法:-%cm%';
				$query_ro['receive_order_row_goods_option-like'] = '%寸法:-%';
				$query_ro['receive_order_order_status_id-in'] = '2,20';
				$query_ro['receive_order_cancel_type_id-eq'] = '0';
				$query_ro['receive_order_row_cancel_flag-neq'] = '1';
				$query_ro['sort'] = 'receive_order_id-asc,receive_order_row_no-asc';
				$api_return = $this->nextengine->apiExecute(
					'/api_v1_receiveorder_row/search',
					$query_ro
				);
				if ($api_return['result'] != 'success'){
					$rtn_message .= 'API ERROR '.$api_return['message']."\n";
					$error_flg = true;
				}else{
					if (intval($api_return['count']) >= 10000){
						$rtn_message .= '行数が多すぎます'."\n";
						$error_flg = true;
					}
					$ros = array_merge($ros, $api_return['data']);
				}
			}
			$ros_count = count($ros);
			if ($error_flg == false){
				$i_u = 0;
				$j_u = 0;
				$j_a = 0;
				$update_goods = array();
				$add_goods = array();
				for ($i_ro=0; $i_ro<$ros_count; $i_ro++){
					$shop_id = $ros[$i_ro]['receive_order_shop_id'];
					$receive_order_id = $ros[$i_ro]['receive_order_id'];
					$receive_order_worker_text = $ros[$i_ro]['receive_order_worker_text'];
					$receive_order_row_goods_id = $ros[$i_ro]['receive_order_row_goods_id'];
					$receive_order_row_goods_option = $ros[$i_ro]['receive_order_row_goods_option'];
					if (strpos($receive_order_worker_text, $pattern_text) !== false){
						continue;
					}
					if ($shop_id == 1){
						$tmps = explode('裄丈詰め加工：▼', $receive_order_row_goods_option);
						if (count($tmps) > 1){
							$tmps = explode('cm', $tmps[1]);
							$update_goods[$j_u]['row_no'] = $ros[$i_ro]['receive_order_row_no'];
							$unit_price = intval($ros[$i_ro]['receive_order_row_unit_price']);
							$quantity = intval($ros[$i_ro]['receive_order_row_quantity']);
							$sub_total_price = intval($ros[$i_ro]['receive_order_row_sub_total_price']);
							$unit_price -= 1296;
							$sub_total_price = $unit_price * $quantity;
							$update_goods[$j_u]['unit_price'] = $unit_price;
							$update_goods[$j_u]['sub_total_price'] = $sub_total_price;
							$add_goods[$j_a]['goods_id'] = 'yuki';
							$add_goods[$j_a]['goods_name']
								 = '【裄丈詰め加工】'.$receive_order_row_goods_id;
							$add_goods[$j_a]['goods_option'] = $tmps[0].'cm';
							$add_goods[$j_a]['unit_price'] = 1296;
							$add_goods[$j_a]['quantity'] = $quantity;
							$add_goods[$j_a]['sub_total_price'] = 1296 * $quantity;
							$j_u++;
							$j_a++;
						}
					}
					if ($shop_id == 2
					 or $shop_id == 3){
						$flg = strpos(
							$ros[$i_ro]['receive_order_row_goods_name'],
							'【裄丈詰め加工】'
						);
						$tmps = explode('寸法:', $receive_order_row_goods_option);
						if (count($tmps) > 1 and $flg !== false){
							$tmps = explode('cm', $tmps[1]);
							$quantity = intval($ros[$i_ro]['receive_order_row_quantity']);
							$update_goods[$j_u]['row_no'] = $ros[$i_ro]['receive_order_row_no'];
							$update_goods[$j_u]['cancel_flag'] = '1';
							$add_goods[$j_a]['goods_id'] = 'yuki';
							$add_goods[$j_a]['goods_name']
								 = '【裄丈詰め加工】'.$receive_order_row_goods_id;
							$add_goods[$j_a]['goods_option'] = $tmps[0].'cm';
							$add_goods[$j_a]['unit_price'] = 1296;
							$add_goods[$j_a]['quantity'] = $quantity;
							$add_goods[$j_a]['sub_total_price'] = 1296 * $quantity;
							$j_u++;
							$j_a++;
						}
					}
					if ($receive_order_id <> $ros[($i_ro+1)]['receive_order_id']){
						if ($this->print_flg){
							print '<div>伝票番号.';
							print $receive_order_id;
							print ' <span class="button" name="open_ne_slip" slip_no="';
							print $receive_order_id;
							print '" style="font-size:11px;padding:0;">伝票</span> ';
							print '</div>'."\n";
							ob_clean();
							flush();
						}
						$this->executelog->add_log($ros[$i_ro]);
						$this->up_texts[$i_u]['receive_order_id']
							 = $ros[$i_ro]['receive_order_id'];
						$this->up_texts[$i_u]['receive_order_last_modified_date']
							 = $ros[$i_ro]['receive_order_last_modified_date'];
						$this->up_texts[$i_u]['xml'] = '';
						$this->up_texts[$i_u]['xml'] .= '<?xml version="1.0" encoding="utf-8"?>'."\n";
						$this->up_texts[$i_u]['xml'] .= '<root>'."\n";
						$this->up_texts[$i_u]['xml'] .= '<receiveorder_base>'."\n";
						$this->up_texts[$i_u]['xml'] .= '<receive_order_worker_text>';
						if ($receive_order_worker_text == ''){
							$tmp = $pattern_text;
						}else{
							$tmp = $receive_order_worker_text."\n".$pattern_text;
						}
						$this->up_texts[$i_u]['xml'] .= $tmp;
						$this->up_texts[$i_u]['xml'] .= '</receive_order_worker_text>'."\n";
						$this->up_texts[$i_u]['xml'] .= '</receiveorder_base>'."\n";
						$this->up_texts[$i_u]['xml'] .= '<receiveorder_row>'."\n";
						for ($j=0; $j<count($update_goods); $j++){
							$this->up_texts[$i_u]['xml'] .= '<receive_order_row_no value="';
							$this->up_texts[$i_u]['xml'] .= $update_goods[$j]['row_no'];
							$this->up_texts[$i_u]['xml'] .= '">'."\n";
							if ($update_goods[$j]['cancel_flag'] == 1){
								$this->up_texts[$i_u]['xml'] .= '<receive_order_row_cancel_flag>';
								$this->up_texts[$i_u]['xml'] .= '1';
								$this->up_texts[$i_u]['xml'] .= '</receive_order_row_cancel_flag>'."\n";
							}else{
								$this->up_texts[$i_u]['xml'] .= '<receive_order_row_unit_price>';
								$this->up_texts[$i_u]['xml'] .= $update_goods[$j]['unit_price'];
								$this->up_texts[$i_u]['xml'] .= '</receive_order_row_unit_price>'."\n";
								$this->up_texts[$i_u]['xml'] .= '<receive_order_row_sub_total_price>';
								$this->up_texts[$i_u]['xml'] .= $update_goods[$j]['sub_total_price'];
								$this->up_texts[$i_u]['xml'] .= '</receive_order_row_sub_total_price>'."\n";
							}
							$this->up_texts[$i_u]['xml'] .= '</receive_order_row_no>'."\n";
						}
						for ($j=0; $j<count($add_goods); $j++){
							$this->up_texts[$i_u]['xml'] .= '<receive_order_row_no value="">'."\n";
							$this->up_texts[$i_u]['xml'] .= '<receive_order_row_goods_id>';
							$this->up_texts[$i_u]['xml'] .= $add_goods[$j]['goods_id'];
							$this->up_texts[$i_u]['xml'] .= '</receive_order_row_goods_id>'."\n";
							$this->up_texts[$i_u]['xml'] .= '<receive_order_row_goods_name>';
							$this->up_texts[$i_u]['xml'] .= $add_goods[$j]['goods_name'];
							$this->up_texts[$i_u]['xml'] .= '</receive_order_row_goods_name>'."\n";
							$this->up_texts[$i_u]['xml'] .= '<receive_order_row_goods_option>';
							$this->up_texts[$i_u]['xml'] .= $add_goods[$j]['goods_option'];
							$this->up_texts[$i_u]['xml'] .= '</receive_order_row_goods_option>'."\n";
							$this->up_texts[$i_u]['xml'] .= '<receive_order_row_unit_price>';
							$this->up_texts[$i_u]['xml'] .= $add_goods[$j]['unit_price'];
							$this->up_texts[$i_u]['xml'] .= '</receive_order_row_unit_price>'."\n";
							$this->up_texts[$i_u]['xml'] .= '<receive_order_row_quantity>';
							$this->up_texts[$i_u]['xml'] .= $add_goods[$j]['quantity'];
							$this->up_texts[$i_u]['xml'] .= '</receive_order_row_quantity>'."\n";
							$this->up_texts[$i_u]['xml'] .= '<receive_order_row_sub_total_price>';
							$this->up_texts[$i_u]['xml'] .= $add_goods[$j]['sub_total_price'];
							$this->up_texts[$i_u]['xml'] .= '</receive_order_row_sub_total_price>'."\n";
							$this->up_texts[$i_u]['xml'] .= '</receive_order_row_no>'."\n";
						}
						$this->up_texts[$i_u]['xml'] .= '</receiveorder_row>'."\n";
						$this->up_texts[$i_u]['xml'] .= '</root>'."\n";

						$query_up = array();
						$query_up['receive_order_id'] = $this->up_texts[$i_u]['receive_order_id'];
						$query_up['receive_order_last_modified_date']
							 = $this->up_texts[$i_u]['receive_order_last_modified_date'];
						$query_up['data'] = $this->up_texts[$i_u]['xml'];
						if ($this->params['wait_flag'] !== null){
							$query_up['wait_flag'] = $this->params['wait_flag'];
						}
						if ($this->not_execute_flg){
							print '<div class="alert">';
							print '受注伝票を更新しないモード（テスト用）です。';
							print '</div>'."\n";
						}else{
							$api_update_result = $this->nextengine->apiExecute(
								'/api_v1_receiveorder_base/update',
								$query_up
							);
							if ($api_update_result['result'] == 'success'){
								if ($this->print_flg){
									print '<div class="report">更新成功</div>'."\n";
									ob_clean();
									flush();
								}
							}else{
								$rtn_message .= '伝票番号.'.$this->up_texts[$i_u]['receive_order_id']
									.' 更新失敗 '.print_r($api_update_result, true)."\n";
								$executelog->set_log_message(
									'ネクストエンジン: '.$api_update_result['message'],
									print_r($api_update_result, true),
									$i_u
								);
								$error_flg = true;
								if ($this->print_flg){
									print '<div class="error">更新失敗</div>'."\n";
									print '<div class="light_color small">';
									print $api_update_result['result'];
									print ' ';
									print $api_update_result['code'];
									print ' ';
									print $api_update_result['message'];
									print '</div>'."\n";
									ob_clean();
									flush();
								}
							}
							sleep($this->sleep_sec);
						}
						$i_u++;
						$j_u = 0;
						$j_a = 0;
						$update_goods = array();
						$add_goods = array();
					}
				}
			}
			if ($this->not_execute_flg == false){
				$log = print_r($this->up_texts, true);
				$this->nextengine->write_inspectlog($log);
				$this->executelog->save_log();
			}
		} catch (Exception $e) {
			$rtn_message .= 'システムエラー '. $e->getMessage()."\n";
		}
		if ($rtn_message != ''){
			$this->nextengine->write_errorlog($rtn_message);
		}
		return $rtn_message;
	}

}
