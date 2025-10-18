<?php
// NEPresentAdd

require_once(Config::$global_include_path.'aNERoutineExec.php');

class NEPresentAdd extends aNERoutineExec{

	public $fraction_field = 'goods_1_item';
	public $php_id = 'NEPresentAdd';
	public $execute_id = '[EXEC005]';
	public $execute_name = 'プレゼント追加';

	public $receive_order_shop_id_in = '';
	public $cut_receive_order_import_type_id2 = true;

	public function execute(){
		$php_id = $this->php_id;
		$execute_id = $this->execute_id;
		$execute_name = $this->execute_name;
		$error_flg = false;
		$rtn_message = '';
		$target_goods_ids_query = '';
		try{
			$this->executelog = new Tonary_ExecuteLog(
				null,
				$this->nextengine->login_user,
				array(
					'php_id' => $php_id,
					'execute_id' => $execute_id,
					'execute_name' => $execute_name
				)
			);
			require_once(Config::$global_include_path.'NE/NEBackupGoods.php');
			$target = new NEBackupGoods();
			$query_array = array();
			$query_array['offset'] = 0;
			$query_array['limit'] = 10000;
			$query_array['fields'] = $target->query_fields;
			$query_array['goods_deleted_flag-neq'] = '1';
			$query_array['goods_tag-like'] = '%[プレゼント:%';
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
				if (intval($api_return['count']) > 1000){
					$rtn_message .= '行数が多すぎます'."\n";
					$this->executelog->set_log_message(
						'プレゼントを付ける商品の行数が1000件を超えています',
						'',
						-1
					);
					$error_flg = true;
				}
				$goods = $api_return['data'];
				$goods_count = intval($api_return['count']);
				for ($i_l=0; $i_l<$goods_count; $i_l++){
					if ($target_goods_ids_query != ''){
						$target_goods_ids_query .= ',';
					}
					$target_goods_ids_query .= $goods[$i_l]['goods_id'];

					$goods_tag = $goods[$i_l]['goods_tag'];
					if ($goods_tag != ''){
						$tmps = explode('[プレゼント:', $goods_tag);
						if (count($tmps)>0){
							$tmps = explode(']', $tmps[1]);
							if (count($tmps)>0){
								$present_add_id = $tmps[0];
								$goods[$i_l]['present_add_id'] = $present_add_id;
								if ($present_goods_ids_query != ''){
									$present_goods_ids_query .= ',';
								}
								$present_goods_ids_query .= $present_add_id;
							}
						}
					}

				}
			}
			if ($error_flg == false){
				$query_array = array();
				$query_array['offset'] = 0;
				$query_array['limit'] = 10000;
				$query_array['fields'] = $target->query_fields;
				$query_array['goods_deleted_flag-neq'] = '1';
				$query_array['goods_id-in'] = $present_goods_ids_query;
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
					if (intval($api_return['count']) > 10000){
						$rtn_message .= '行数が多すぎます'."\n";
						$this->executelog->set_log_message(
							'プレゼントする商品の行数が10000件を超えています',
							'',
							-1
						);
						$error_flg = true;
					}
					$present_goods = $api_return['data'];
					$present_goods_count = intval($api_return['count']);
				}
			}
			if ($error_flg == false){
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
				$query_ro['receive_order_row_goods_id-in'] = $target_goods_ids_query;
				if ($this->receive_order_shop_id_in != ''){
					$query_ro['receive_order_shop_id-in'] = $this->receive_order_shop_id_in;
				}
				$query_ro['receive_order_order_status_id-in'] = '2,20';
				$query_ro['receive_order_cancel_type_id-eq'] = '0';
				$query_ro['receive_order_row_cancel_flag-neq'] = '1';
				$query_ro['sort'] = 'receive_order_id-asc';
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
					$ros_count = count($ros);
					for ($i_ro=0; $i_ro<$ros_count; $i_ro++){
						for ($i_g=0; $i_g<$goods_count; $i_g++){
							if ($ros[$i_ro]['receive_order_row_goods_id'] == $goods[$i_g]['goods_id']){
								$ros[$i_ro] = array_merge($ros[$i_ro], $goods[$i_g]);
							}
						}
					}
				}
			}
			if ($error_flg == false){
				$i_u = 0;
				$pre_receive_order_id = 'なし';
				for ($i_ro=0; $i_ro<$ros_count; $i_ro++){
					$shop_id = $ros[$i_ro]['receive_order_shop_id'];
					$receive_order_id = $ros[$i_ro]['receive_order_id'];
					$receive_order_row_no = $ros[$i_ro]['receive_order_row_no'];
					$receive_order_worker_text = $ros[$i_ro]['receive_order_worker_text'];
					$receive_order_note = $ros[$i_ro]['receive_order_note'];
					$present_add_id = $ros[$i_ro]['present_add_id'];
					$quantity = $ros[$i_ro]['receive_order_row_quantity'];
					$numerator = 0;
					$denominator = 0;
					if (strpos($receive_order_worker_text, $execute_id) !== false){
						continue;
					}
					if ($receive_order_id != $pre_receive_order_id){
						$present_add_false_reason = '';
						$present_add_flag = true;
						$present_quantity = 0;
						$presents = array();
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
					}
					if ($present_add_id == ''){
						$present_add_false_reason = 'プレゼント設定エラー';
						$present_add_flag = false;
					}
					$present_good = null;
					if ($present_add_flag){
						for ($i_p=0; $i_p<$present_goods_count; $i_p++){
							if ($present_goods[$i_p]['goods_id'] == $present_add_id){
								$present_good = $present_goods[$i_p];
								break;
							}
						}
					}
					if ($present_good == null){
						$present_add_false_reason = 'プレゼントする商品がマスタに未登録';
						$present_add_flag = false;
					}else{
						$exist = false;
						for ($p=0; $p<count($presents); $p++){
							if ($presents[$p]['goods_id'] == $present_add_id){
								$exist = true;
								break;
							}
						}
						if ($exist){
							$presents[$p]['quantity'] += $quantity;
						}else{
							$presents[$p]['goods_id'] = $present_add_id;
							$presents[$p]['quantity'] = $quantity;
							$presents[$p]['goods_name'] = $present_good['goods_name'];
							$presents[$p]['goods_selling_price'] = $present_good['goods_selling_price'];
							$presents[$p]['goods_cost_price'] = $present_good['goods_cost_price'];
						}
						$ros[$i_ro]['present_goods_id'] = $present_add_id;
						$ros[$i_ro]['present_quantity'] = $presents[$p]['quantity'];
					}
					if ($this->cut_receive_order_import_type_id2){
						if ($ro['receive_order_import_type_id'] == 2){
							$present_add_false_reason = '取込種類:';
							$present_add_false_reason = $ros[$i_ro]['receive_order_import_type_name'];
							$present_add_flag = false;
						}
					}
					if ($this->print_flg){
						print '<div class="small light_color" style="margin-left:16px;">';
						print $receive_order_row_no;
						print ':';
						print $ros[$i_ro]['receive_order_row_goods_id'];
						print ' ';
						print $quantity;
						print ' →追加 ';
						print $ros[$i_ro]['present_goods_id'];
						print ' 累計:';
						print $ros[$i_ro]['present_quantity'];
						print ' ';
						print $present_add_false_reason;
						print '</div>'."\n";
						ob_clean();
						flush();
					}
					if ($receive_order_id != $ros[($i_ro+1)]['receive_order_id']){
						if ($present_add_flag == false){
							print '<div class="report" style="margin-left:16px;">';
							print 'プレゼント追加不可';
							print '</div>'."\n";
							ob_clean();
							flush();
						}else{
							print '<div class="message" style="margin-left:16px;">';
							print 'プレゼント追加';
							print '</div>'."\n";
							ob_clean();
							flush();
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
								$tmp = $execute_id;
							}else{
								$tmp = $receive_order_worker_text."\n".$execute_id;
							}
							$this->up_texts[$i_u]['xml'] .= $tmp;
							$this->up_texts[$i_u]['xml'] .= '</receive_order_worker_text>'."\n";

							$this->up_texts[$i_u]['xml'] .= '</receiveorder_base>'."\n";
							$this->up_texts[$i_u]['xml'] .= '<receiveorder_row>'."\n";

							for ($p=0; $p<count($presents); $p++){
								$add_quantity = intval($presents[$p]['quantity']);
								$add_price = intval($presents[$p]['goods_selling_price']);
								$this->up_texts[$i_u]['xml'] .= '<receive_order_row_no value="">'."\n";
								$this->up_texts[$i_u]['xml'] .= '<receive_order_row_goods_id>';
								$this->up_texts[$i_u]['xml'] .= $presents[$p]['goods_id'];
								$this->up_texts[$i_u]['xml'] .= '</receive_order_row_goods_id>'."\n";
								$this->up_texts[$i_u]['xml'] .= '<receive_order_row_goods_name>';
								$this->up_texts[$i_u]['xml'] .= $presents[$p]['goods_name'];
								$this->up_texts[$i_u]['xml'] .= '</receive_order_row_goods_name>'."\n";
								$this->up_texts[$i_u]['xml'] .= '<receive_order_row_quantity>';
								$this->up_texts[$i_u]['xml'] .= $add_quantity;
								$this->up_texts[$i_u]['xml'] .= '</receive_order_row_quantity>'."\n";
								$this->up_texts[$i_u]['xml'] .= '<receive_order_row_unit_price>';
								$this->up_texts[$i_u]['xml'] .= $add_price;
								$this->up_texts[$i_u]['xml'] .= '</receive_order_row_unit_price>'."\n";
								$this->up_texts[$i_u]['xml'] .= '<receive_order_row_received_time_first_cost>';
								$this->up_texts[$i_u]['xml'] .= $presents[$p]['goods_cost_price'];
								$this->up_texts[$i_u]['xml'] .= '</receive_order_row_received_time_first_cost>'."\n";
								$this->up_texts[$i_u]['xml'] .= '<receive_order_row_sub_total_price>';
								$this->up_texts[$i_u]['xml'] .= ($add_quantity * $add_price);
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
						}
					}
					$i_u++;
					$pre_receive_order_id = $receive_order_id;
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
