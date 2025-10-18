<?php
// NEBundleExec

require_once(Config::$global_include_path.'aNERoutineExec.php');

class NEBundleExec extends aNERoutineExec{

	public $params = array(
		'wait_flag' => null,
	);

	public function execute(){
		$php_id = 'NEBundleExec';
		$pattern_text = '[PATTERN002]';
		$pattern_name = 'ネクタイ3本よりどり割引';
		$error_flg = false;
		$rtn_message = '';
		$target_goods_ids_query = '';
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
			require_once(Config::$global_include_path.'NE/NEBackupGoods.php');
			$target = new NEBackupGoods();
			$query_array = array();
			$query_array['offset'] = 0;
			$query_array['limit'] = 10000;
			$query_array['fields'] = $target->query_fields;
			$query_array['goods_deleted_flag-neq'] = '1';
			$query_array['goods_model_number-eq'] = '5000';
			$query_array['sort'] = 'goods_representation_id-asc,goods_id-asc';
			$api_return = $this->nextengine->apiExecute('/api_v1_master_goods/search', $query_array);
			if ($api_return['result'] != 'success'){
				$rtn_message .= 'API ERROR '.$api_return['message']."\n";
				$this->executelog->set_log_message(
					'ネクストエンジン: '.$api_return['message'],
					print_r($api_return, true),
					-1
				);
				$error_flg = true;
			}else{
				if (intval($api_return['count']) >= 10000){
					$rtn_message .= '行数が多すぎます'."\n";
					$this->executelog->set_log_message(
						'ネクストエンジン: バンドル対象商品の行数が10000件を超えています',
						'',
						-1
					);
					$error_flg = true;
				}
				$lists = $api_return['data'];
				$lists_count = intval($api_return['count']);
				for ($i_l=0; $i_l<$lists_count; $i_l++){
					if ($target_goods_ids_query != ''){
						$target_goods_ids_query .= ',';
					}
					$target_goods_ids_query .= $lists[$i_l]['goods_id'];
				}
			}

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
			$query_ro['receive_order_shop_id-in'] = '1,2,3';
			$query_ro['receive_order_row_goods_id-in'] = $target_goods_ids_query;
			$query_ro['receive_order_order_status_id-in'] = '2,20';
			$query_ro['receive_order_cancel_type_id-eq'] = '0';
			$query_ro['receive_order_row_cancel_flag-neq'] = '1';
			$query_ro['sort'] = 'receive_order_id-asc,receive_order_row_no-asc';
			$api_return = $this->nextengine->apiExecute('/api_v1_receiveorder_row/search', $query_ro);
			if ($api_return['result'] != 'success'){
				$rtn_message .= 'API ERROR '.$api_return['message']."\n";
				$this->executelog->set_log_message(
					'ネクストエンジン: '.$api_return['message'],
					print_r($api_return, true),
					-1
				);
				$error_flg = true;
			}else{
				if (intval($api_return['count']) >= 10000){
					$rtn_message .= '行数が多すぎます'."\n";
					$this->executelog->set_log_message(
						'ネクストエンジン: 該当する受注行数が10000件を超えています',
						'',
						-1
					);
					$error_flg = true;
				}
				$ros = $api_return['data'];
			}
			$ros_count = count($ros);
			if ($error_flg == false){
				$total_quantity = 0;
				$i_u = 0;
				for ($i_ro=0; $i_ro<$ros_count; $i_ro++){
					$shop_id = $ros[$i_ro]['receive_order_shop_id'];
					$receive_order_id = $ros[$i_ro]['receive_order_id'];
					$receive_order_worker_text = $ros[$i_ro]['receive_order_worker_text'];
					$receive_order_delivery_cut_form_note = $ros[$i_ro]['receive_order_delivery_cut_form_note'];
					$receive_order_note = $ros[$i_ro]['receive_order_note'];
					if (strpos($receive_order_worker_text, $pattern_text) !== false){
						continue;
					}
					$total_quantity += intval($ros[$i_ro]['receive_order_row_quantity']);
					if ($receive_order_id <> $ros[($i_ro+1)]['receive_order_id']){
						if ($total_quantity >= 3){
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
							$add_goods_id = 'nekutai';
							$add_goods_name = $pattern_name;
							$add_unit_price = 0;
							$add_quantity = intval($total_quantity / 3);
							$add_sub_total_price = 0;
							$this->executelog->add_log($ros[$i_ro]);
							$this->up_texts[$i_u]['receive_order_id']
								 = $ros[$i_ro]['receive_order_id'];
							$this->up_texts[$i_u]['receive_order_last_modified_date']
								 = $ros[$i_ro]['receive_order_last_modified_date'];
							$this->up_texts[$i_u]['xml'] = '';
							$this->up_texts[$i_u]['xml'] .= '<?xml version="1.0" encoding="utf-8"?>'."\n";
							$this->up_texts[$i_u]['xml'] .= '<root>'."\n";
							$this->up_texts[$i_u]['xml'] .= '<receiveorder_base>'."\n";
							$goods_amount = intval($ros[$i_ro]['receive_order_goods_amount']);
							$tax_amount = intval($ros[$i_ro]['receive_order_tax_amount']);
							$charge_amount = intval($ros[$i_ro]['receive_order_charge_amount']);
							$delivery_fee_amount
								 = intval($ros[$i_ro]['receive_order_delivery_fee_amount']);
							$other_amount = intval($ros[$i_ro]['receive_order_other_amount']);
							$point_amount = intval($ros[$i_ro]['receive_order_point_amount']);
							$total_amount = intval($ros[$i_ro]['receive_order_total_amount']);

							$discount = ($add_quantity * 1100);
							$original = $charge_amount;
							//手数料を割引
							$charge_amount -= $discount;

							if ($shop_id == 1){
								//商品計を加算（FSは商品計だけ割引済みなので）
								$goods_amount += $discount;
							}
							$add_note = '';
							$add_option = '手数料にて割引。';
							if (strpos($receive_order_note, '■楽天バンク決済') !== false){
								$add_note .= '手数料内訳：'."\n";
								$add_option .= '手数料内訳：';
								if ($original != 155){
									$add_note .= 'その他 '.($original-155).'円'."\n";
									$add_option .= 'その他 '.($original-155).'円、';
								}
								$add_note .= '楽天バンク決済 155円'."\n";
								$add_note .= 'ネクタイよりどり割引 ';
								$add_option .= '楽天バンク決済 155円、ネクタイよりどり割引 ';
							}else if ($original != 0){
								$add_note .= '手数料内訳：'."\n";
								$add_note .= 'その他 '.$original.'円'."\n";
								$add_note .= 'ネクタイよりどり割引 ';
								$add_option .= '手数料内訳：';
								$add_option .= 'その他 '.$original.'円、ネクタイよりどり割引 ';
							}else{
								$add_note .= '手数料内訳：'."\n";
								$add_note .= 'ネクタイよりどり割引 ';
							}
							$add_note .= '-'.$discount.'円';
							$add_option .= '-'.$discount.'円';
							if ($shop_id == 3){
								//他費用を加算（Yahooは他費用で割引済みなので）
								$other_amount += $discount;
							}

							$total_amount = $goods_amount;
							$total_amount += $tax_amount;
							$total_amount += $charge_amount;
							$total_amount += $delivery_fee_amount;
							$total_amount += $other_amount;
							$total_amount -= $point_amount;
							$this->up_texts[$i_u]['xml'] .= '<receive_order_goods_amount>';
							$this->up_texts[$i_u]['xml'] .= $goods_amount;
							$this->up_texts[$i_u]['xml'] .= '</receive_order_goods_amount>'."\n";
/*
							$this->up_texts[$i_u]['xml'] .= '<receive_order_tax_amount>';
							$this->up_texts[$i_u]['xml'] .= $tax_amount;
							$this->up_texts[$i_u]['xml'] .= '</receive_order_tax_amount>'."\n";
*/
							$this->up_texts[$i_u]['xml'] .= '<receive_order_charge_amount>';
							$this->up_texts[$i_u]['xml'] .= $charge_amount;
							$this->up_texts[$i_u]['xml'] .= '</receive_order_charge_amount>'."\n";
/*
							$this->up_texts[$i_u]['xml'] .= '<receive_order_delivery_fee_amount>';
							$this->up_texts[$i_u]['xml'] .= $delivery_fee_amount;
							$this->up_texts[$i_u]['xml'] .= '</receive_order_delivery_fee_amount>'."\n";
*/
							$this->up_texts[$i_u]['xml'] .= '<receive_order_other_amount>';
							$this->up_texts[$i_u]['xml'] .= $other_amount;
							$this->up_texts[$i_u]['xml'] .= '</receive_order_other_amount>'."\n";
/*
							$this->up_texts[$i_u]['xml'] .= '<receive_order_point_amount>';
							$this->up_texts[$i_u]['xml'] .= $point_amount;
							$this->up_texts[$i_u]['xml'] .= '</receive_order_point_amount>'."\n";
*/
							$this->up_texts[$i_u]['xml'] .= '<receive_order_total_amount>';
							$this->up_texts[$i_u]['xml'] .= $total_amount;
							$this->up_texts[$i_u]['xml'] .= '</receive_order_total_amount>'."\n";
							$this->up_texts[$i_u]['xml'] .= '<receive_order_confirm_check_id>';
							$this->up_texts[$i_u]['xml'] .= '0';
							$this->up_texts[$i_u]['xml'] .= '</receive_order_confirm_check_id>'."\n";
							$this->up_texts[$i_u]['xml'] .= '<receive_order_worker_text>';
							if ($receive_order_worker_text == ''){
								$tmp = $pattern_text;
							}else{
								$tmp = $receive_order_worker_text."\n".$pattern_text;
							}
							$this->up_texts[$i_u]['xml'] .= $tmp;
							$this->up_texts[$i_u]['xml'] .= '</receive_order_worker_text>'."\n";
/*
							$this->up_texts[$i_u]['xml'] .= '<receive_order_delivery_cut_form_note>';
							if ($receive_order_delivery_cut_form_note == ''){
								$tmp = $add_note;
							}else{
								$tmp = $receive_order_delivery_cut_form_note."\n".$add_note;
							}
							$this->up_texts[$i_u]['xml'] .= $tmp;
							$this->up_texts[$i_u]['xml'] .= '</receive_order_delivery_cut_form_note>'."\n";
*/
							$this->up_texts[$i_u]['xml'] .= '</receiveorder_base>'."\n";
							$this->up_texts[$i_u]['xml'] .= '<receiveorder_row>'."\n";
							$this->up_texts[$i_u]['xml'] .= '<receive_order_row_no value="">'."\n";
							$this->up_texts[$i_u]['xml'] .= '<receive_order_row_goods_id>';
							$this->up_texts[$i_u]['xml'] .= $add_goods_id;
							$this->up_texts[$i_u]['xml'] .= '</receive_order_row_goods_id>'."\n";
							$this->up_texts[$i_u]['xml'] .= '<receive_order_row_goods_name>';
							$this->up_texts[$i_u]['xml'] .= $add_goods_name;
							$this->up_texts[$i_u]['xml'] .= '</receive_order_row_goods_name>'."\n";
							$this->up_texts[$i_u]['xml'] .= '<receive_order_row_unit_price>';
							$this->up_texts[$i_u]['xml'] .= $add_unit_price;
							$this->up_texts[$i_u]['xml'] .= '</receive_order_row_unit_price>'."\n";
							$this->up_texts[$i_u]['xml'] .= '<receive_order_row_quantity>';
							$this->up_texts[$i_u]['xml'] .= $add_quantity;
							$this->up_texts[$i_u]['xml'] .= '</receive_order_row_quantity>'."\n";
							$this->up_texts[$i_u]['xml'] .= '<receive_order_row_sub_total_price>';
							$this->up_texts[$i_u]['xml'] .= $add_sub_total_price;
							$this->up_texts[$i_u]['xml'] .= '</receive_order_row_sub_total_price>'."\n";
							if ($add_option != ''){
								$this->up_texts[$i_u]['xml'] .= '<receive_order_row_goods_option>';
								$this->up_texts[$i_u]['xml'] .= $add_option;
								$this->up_texts[$i_u]['xml'] .= '</receive_order_row_goods_option>'."\n";
							}
							$this->up_texts[$i_u]['xml'] .= '</receive_order_row_no>'."\n";
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
									$this->executelog->set_log_message(
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
						}
						$total_quantity = 0;
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
