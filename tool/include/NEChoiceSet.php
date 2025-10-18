<?php
// NEChoiceSet

require_once(Config::$global_include_path.'aNERoutineExec.php');

class NEChoiceSet extends aNERoutineExec{

	public $php_id = 'NEChoiceSet';
	public $execute_id = '[EXEC001]';
	public $execute_name = 'よりどりセット展開';

	public $goods_tag = '[よりどりセット]';
	public $choice_add_field = 'goods_id';

	public function execute(){
		$php_id = $this->php_id;
		$execute_id = $this->execute_id;
		$execute_name = $this->execute_name;
		$error_flg = false;
		$rtn_message = '';
		$target_goods = array();
		$target_goods_ids_query = '';
		$choice_goods = array();
		$op_goods_ids = array();
		$op_goods_ids_query = '';
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
			$query_array['goods_tag-like'] = '%'.$this->goods_tag.'%';
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
						'よりどりセットの行数が1000件を超えています',
						'',
						-1
					);
					$error_flg = true;
				}
				$target_goods = $api_return['data'];
				$target_goods_count = intval($api_return['count']);
				for ($i_l=0; $i_l<$target_goods_count; $i_l++){
					if ($target_goods_ids_query != ''){
						$target_goods_ids_query .= ',';
					}
					$target_goods_ids_query .= $target_goods[$i_l]['goods_id'];
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
				}
			}
			if ($error_flg == false){
				for ($i_ro=0; $i_ro<$ros_count; $i_ro++){
					$choice_add_ids = array();
					$choice_add_prices = array();
					$unit_price = $ros[$i_ro]['receive_order_row_unit_price'];
					$tmps = explode('[', $ros[$i_ro]['receive_order_row_goods_option']);
					$tmp_count = count($tmps);
					$price2 = floor($unit_price / ($tmp_count-1));
					if (($unit_price % ($tmp_count-1)) == 0){
						$price1 = $price2;
					}else{
						$price1 = $price2 + 1;
					}
					if (count($tmps) > 1){
						for ($j=1; $j<count($tmps); $j++){
							$values = explode(']', $tmps[$j]);
							$op_goods_id = $values[0];
							if (in_array($op_goods_id, $op_goods_ids) == false){
								array_push($op_goods_ids, $op_goods_id);
								if ($op_goods_ids_query != ''){
									$op_goods_ids_query .= ',';
								}
								$op_goods_ids_query .= $op_goods_id;
							}
							array_push($choice_add_ids, $op_goods_id);
							if ($j == 1){
								array_push($choice_add_prices, $price1);
							}else{
								array_push($choice_add_prices, $price2);
							}
						}
					}
					$ros[$i_ro]['choice_add_ids'] = $choice_add_ids;
					$ros[$i_ro]['choice_add_prices'] = $choice_add_prices;

				}
				$op_goods_ids_count = count($op_goods_ids);
				if ($op_goods_ids_count <= 0){
					$error_flg = true;
				}
			}
			if ($error_flg == false){
				$query_array = array();
				$query_array['offset'] = 0;
				$query_array['limit'] = 10000;
				$query_array['fields'] = $target->query_fields;
				$query_array['goods_deleted_flag-neq'] = '1';
				$query_array[$this->choice_add_field.'-in'] = $op_goods_ids_query;
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
							'よりどり商品の行数が1000件を超えています',
							'',
							-1
						);
						$error_flg = true;
					}
					$choice_goods = $api_return['data'];
					$choice_goods_count = intval($api_return['count']);
				}
			}
			if ($error_flg == false){
				$i_u = 0;
				$pre_receive_order_id = 'なし';
				for ($i_ro=0; $i_ro<$ros_count; $i_ro++){
					if (strpos($receive_order_worker_text, $execute_id) !== false){
						continue;
					}
					$shop_id = $ros[$i_ro]['receive_order_shop_id'];
					$receive_order_id = $ros[$i_ro]['receive_order_id'];
					$receive_order_row_no = $ros[$i_ro]['receive_order_row_no'];
					$receive_order_worker_text = $ros[$i_ro]['receive_order_worker_text'];
					$receive_order_note = $ros[$i_ro]['receive_order_note'];
					$quantity = $ros[$i_ro]['receive_order_row_quantity'];
					$choice_add_ids = $ros[$i_ro]['choice_add_ids'];
					$choice_add_ids_count = count($choice_add_ids);
					$choice_add_prices = $ros[$i_ro]['choice_add_prices'];
					$choice_goods_id_msg = '';
					$cancel_row_no = $ros[$i_ro]['receive_order_row_no'];
					if ($receive_order_id != $pre_receive_order_id){
						$choice_add_false_reason = '';
						$choice_add_flag = true;
						$choice_quantity = 0;
						$cancel_row_nos = array();
						$choices = array();
						$p = 0;
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
					for ($i_c=0; $i_c<$choice_add_ids_count; $i_c++){
						$choice_add_id = $choice_add_ids[$i_c];
						$choice_add_price = $choice_add_prices[$i_c];
						if ($choice_add_id == ''){
							$choice_add_false_reason = 'よりどりセット設定エラー';
							$choice_add_flag = false;
						}
						$choice_good = null;
						if ($choice_add_flag){
							for ($c=0; $c<$choice_goods_count; $c++){
								if ($choice_goods[$c][$this->choice_add_field] == $choice_add_id){
									$choice_good = $choice_goods[$c];
									break;
								}
							}
						}
						if ($choice_good == null){
							$choice_add_false_reason = 'よりどり商品がマスタに未登録';
							$choice_add_flag = false;
						}else{
							if (in_array($cancel_row_no, $cancel_row_nos) == false){
								array_push($cancel_row_nos, $cancel_row_no);
							}
							$choices[$p]['goods_id'] = $choice_good['goods_id'];
							$choices[$p]['quantity'] = $quantity;
							$choices[$p]['goods_name'] = $choice_good['goods_name'];
							$choices[$p]['goods_selling_price'] = $choice_add_price;
							$choices[$p]['goods_cost_price'] = $choice_good['goods_cost_price'];
							$choice_goods_id_msg .= $choice_add_id.' ';
							$p++;
						}
					}
					if ($this->print_flg){
						print '<div class="small light_color" style="margin-left:16px;">';
						print $receive_order_row_no;
						print ':';
						print $ros[$i_ro]['receive_order_row_goods_id'];
						print ' →追加 ';
						print $choice_goods_id_msg;
						print '×';
						print $quantity;
						print ' ';
						print $choice_add_false_reason;
						print '</div>'."\n";
						ob_clean();
						flush();
					}
					if ($receive_order_id != $ros[($i_ro+1)]['receive_order_id']){
						if ($choice_add_flag == false){
							print '<div class="report" style="margin-left:16px;">';
							print 'よりどり商品追加不可';
							print '</div>'."\n";
							ob_clean();
							flush();
						}else{
							print '<div class="message" style="margin-left:16px;">';
							print 'よりどり商品追加';
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

							foreach ($cancel_row_nos as $cancel_row_no){
								$this->up_texts[$i_u]['xml'] .= '<receive_order_row_no value="';
								$this->up_texts[$i_u]['xml'] .= $cancel_row_no;
								$this->up_texts[$i_u]['xml'] .= '">'."\n";
								$this->up_texts[$i_u]['xml'] .= '<receive_order_row_cancel_flag>';
								$this->up_texts[$i_u]['xml'] .= '1';
								$this->up_texts[$i_u]['xml'] .= '</receive_order_row_cancel_flag>'."\n";
								$this->up_texts[$i_u]['xml'] .= '</receive_order_row_no>'."\n";
							}

							for ($c=0; $c<count($choices); $c++){
								$add_quantity = intval($choices[$c]['quantity']);
								$add_price = intval($choices[$c]['goods_selling_price']);
								$this->up_texts[$i_u]['xml'] .= '<receive_order_row_no value="">'."\n";
								$this->up_texts[$i_u]['xml'] .= '<receive_order_row_goods_id>';
								$this->up_texts[$i_u]['xml'] .= $choices[$c]['goods_id'];
								$this->up_texts[$i_u]['xml'] .= '</receive_order_row_goods_id>'."\n";
								$this->up_texts[$i_u]['xml'] .= '<receive_order_row_goods_name>';
								$this->up_texts[$i_u]['xml'] .= $choices[$c]['goods_name'];
								$this->up_texts[$i_u]['xml'] .= '</receive_order_row_goods_name>'."\n";
								$this->up_texts[$i_u]['xml'] .= '<receive_order_row_quantity>';
								$this->up_texts[$i_u]['xml'] .= $add_quantity;
								$this->up_texts[$i_u]['xml'] .= '</receive_order_row_quantity>'."\n";
								$this->up_texts[$i_u]['xml'] .= '<receive_order_row_unit_price>';
								$this->up_texts[$i_u]['xml'] .= $add_price;
								$this->up_texts[$i_u]['xml'] .= '</receive_order_row_unit_price>'."\n";
								$this->up_texts[$i_u]['xml'] .= '<receive_order_row_received_time_first_cost>';
								$this->up_texts[$i_u]['xml'] .= $choices[$c]['goods_cost_price'];
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
