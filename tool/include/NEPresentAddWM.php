<?php
// NEPresentAddWM

require_once(Config::$global_include_path.'aNERoutineExec.php');

class NEPresentAddWM extends aNERoutineExec{

	public $php_id = 'NEPresentAddWM';
	public $execute_id = '[EXEC005]';
	public $execute_name = 'プレゼント追加';

	public $receive_order_shop_id_in = '';
	public $cut_receive_order_import_type_id2 = true;

	public $mysql = null;

	public function execute(){
		$php_id = $this->php_id;
		$execute_id = $this->execute_id;
		$execute_name = $this->execute_name;
		$error_flg = false;
		$rtn_message = '';
		$target_goods_ids_query = '';
		$present_item_ids = array();
		$present_add_sku_ids = array();
		$present_goods_ids = array();
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
			$sql = 'select * from present';
			$sql .= ' where ifnull(present_deleted_flag,0) = 0';
			$sql .= ' order by present_item_id';
			if (!$rs = $this->mysql->query($sql)){
				$rtn_message .= 'SQL エラー '.$sql."\n";
				$this->executelog->set_log_message(
					'SQL エラー',
					$sql,
					-1
				);
				$error_flg = true;
			}
			$row = $this->mysql->fetch_array($rs);
			while ($row){
				$presents[] = $row;
				$present_item_id = $row['present_item_id'];
				if (in_array($present_item_id, $present_item_ids) == false){
					array_push($present_item_ids, $present_item_id);
					if ($present_item_ids_query <> ''){
						$present_item_ids_query .= ',';
					}
					$present_item_ids_query .= $present_item_id;
				}
				$present_add_sku_id = $row['present_add_sku_id'];
				if (in_array($present_add_sku_id, $present_add_sku_ids) == false){
					array_push($present_add_sku_ids, $present_add_sku_id);
					if ($present_add_sku_ids_query <> ''){
						$present_add_sku_ids_query .= ',';
					}
					$present_add_sku_ids_query .= $present_add_sku_id;
				}
				$row = $this->mysql->fetch_array($rs);
			}
			$this->mysql->free_result($rs);
			if ($error_flg == false){
				$query_g = array();
				$query_g['offset'] = 0;
				$query_g['limit'] = 10000;
				$query_g['goods_id-in'] = $present_add_sku_ids_query;
				$query_g['fields'] = 'goods_id,goods_name';
				$query_g['sort'] = 'goods_id-asc';
				$api_return = $this->nextengine->apiExecute('/api_v1_master_goods/search', $query_g) ;
				if ($api_return['result'] != 'success'){
					print '<div class="error">'.$api_return['message'].'</div>'."\n";
					$rtn_message .= 'API ERROR '.$api_return['message']."\n";
					$this->executelog->set_log_message(
						'ネクストエンジン: '.$api_return['message'],
						print_r($api_return, true),
						-1
					);
					$error_flg = true;
					$goods_adds = array();
				}else{
					if ($api_return['count'] >= 10000){
						$rtn_message .= '行数が多すぎます'."\n";
						$this->executelog->set_log_message(
							'商品の行数が10000件を超えています',
							'',
							-1
						);
						$error_flg = true;
					}
					$goods_adds = $api_return['data'];
				}
				$goods_adds_count = count($goods_adds);
			}
			if ($error_flg == false){
				$query_g = array();
				$query_g['offset'] = 0;
				$query_g['limit'] = 10000;
				$query_g['goods_model_number-in'] = $present_item_ids_query;
				$query_g['fields'] = 'goods_id,goods_name,goods_model_number';
				$query_g['sort'] = 'goods_id-asc';
				$api_return = $this->nextengine->apiExecute('/api_v1_master_goods/search', $query_g) ;
				if ($api_return['result'] != 'success'){
					print '<div class="error">'.$api_return['message'].'</div>'."\n";
					$rtn_message .= 'API ERROR '.$api_return['message']."\n";
					$this->executelog->set_log_message(
						'ネクストエンジン: '.$api_return['message'],
						print_r($api_return, true),
						-1
					);
					$error_flg = true;
					$goods = array();
				}else{
					if ($api_return['count'] >= 10000){
						$rtn_message .= '行数が多すぎます'."\n";
						$this->executelog->set_log_message(
							'商品の行数が10000件を超えています',
							'',
							-1
						);
						$error_flg = true;
					}
					$goods = $api_return['data'];
				}
				$goods_count = count($goods);
			}
			if ($error_flg == false){
				for ($i_p=0; $i_p<count($presents); $i_p++){
					foreach($goods as $good){
						if ($presents[$i_p]['present_item_id'] == $good['goods_model_number']){
							$presents[$i_p]['present_goods_model_number'] = $good['goods_model_number'];
							break;
						}
					}
					if ($presents[$i_p]['present_goods_model_number'] == ''){
						$rtn_message .= '型番 ';
						$rtn_message .= $presents[$i_p]['present_item_id'];
						$rtn_message .= ' の商品が商品マスタに登録されていません'."\n";
						$this->executelog->set_log_message(
							$rtn_message,
							'',
							-1
						);
						$error_flg = true;
					}
					foreach($goods_adds as $goods_add){
						if ($presents[$i_p]['present_add_sku_id'] == $goods_add['goods_id']){
							$presents[$i_p]['add_goods_id'] = $goods_add['goods_id'];
							$presents[$i_p]['add_goods_name'] = $goods_add['goods_name'];
							break;
						}
					}
					if ($presents[$i_p]['add_goods_id'] == ''){
						print '<div class="error">';
						$rtn_message .= $presents[$i_p]['present_add_sku_id'];
						$rtn_message .= ' が商品マスタに登録されていません'."\n";
						$this->executelog->set_log_message(
							$rtn_message,
							'',
							-1
						);
						$error_flg = true;
					}
				}
				for ($i_g=0; $i_g<$goods_count; $i_g++){
					foreach($presents as $present){
						if ($goods[$i_g]['goods_model_number'] == $present['present_item_id']){
							$goods[$i_g] += $present;
							break;
						}
					}
					$present_goods_id = $goods[$i_g]['goods_id'];
					if (in_array($present_goods_id, $present_goods_ids) == false){
						array_push($present_goods_ids, $present_goods_id);
						if ($present_goods_ids_query <> ''){
							$present_goods_ids_query .= ',';
						}
						$present_goods_ids_query .= $present_goods_id;
					}
				}
				$ro_fields_query = '';
				for ($i=0; $i<count($this->nextengine->receiveorder_fields); $i++){
					if ($ro_fields_query != ''){
						$ro_fields_query .= ',';
					}
					$ro_fields_query .= $this->nextengine->receiveorder_fields[$i][1];
				}
				for ($i=0; $i<count($this->nextengine->receiveorder_row_fields); $i++){
					if ($ro_fields_query != ''){
						$ro_fields_query .= ',';
					}
					$ro_fields_query .= $this->nextengine->receiveorder_row_fields[$i][1];
				}
				$query_ro = array();
				$query_ro['fields'] = $ro_fields_query;
				$query_ro['offset'] = 0;
				$query_ro['limit'] = 10000;
				if ($this->receive_order_shop_id_in != ''){
					$query_ro['receive_order_shop_id-in'] = $this->receive_order_shop_id_in;
				}
				$query_ro['receive_order_order_status_id-in'] = '2,20';
				$query_ro['receive_order_cancel_type_id-eq'] = '0';
				$query_ro['receive_order_row_cancel_flag-neq'] = '1';
				$query_ro['sort'] = 'receive_order_id-asc,receive_order_row_no-asc';
				$query_ro['receive_order_row_goods_id-in'] = $present_goods_ids_query;
				$ros_count = 0;
				$ros = array();
				$api_return = $this->nextengine->apiExecute('/api_v1_receiveorder_row/search', $query_ro);
				if ($api_return['result'] != 'success'){
					$rtn_message .= 'API ERROR '.$api_return['message']."\n";
					$this->executelog->set_log_message(
						'ネクストエンジン: '.$api_return['message'],
						print_r($api_return, true),
						-1
					);
					$error_flg = true;
					$ros = array();
				}else{
					$ros_count = intval($api_return['count']);
					if ($ros_count >= 10000){
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
				$pre_receive_order_id = '';
				$i_u = -1;
				$update_exist = false;
				for ($i_ro=0; $i_ro<$ros_count; $i_ro++){
					$skip_flg = false;
					foreach($goods as $good){
						if ($ros[$i_ro]['receive_order_row_goods_id'] == $good['goods_id']){
							$ros[$i_ro] += $good;
							break;
						}
					}
					$ro = $ros[$i_ro];
					$receive_order_worker_text = $ro['receive_order_worker_text'];
					if (strpos($receive_order_worker_text, $execute_id) !== false){
						continue;
					}
					$present_deleted_flag = $ro['present_deleted_flag'];
					if ($present_deleted_flag == 1){
						continue;
					}
					$update_exist = ture;
					$receive_order_id = $ro['receive_order_id'];
					$receive_order_date = $ro['receive_order_date'];
					$receive_order_date_timestamp = strtotime($receive_order_date);
					$present_start_date = $ro['present_start_date'];
					$present_stop_date = $ro['present_stop_date'];
					if ($present_start_date){
						$present_start_date_timestamp = strtotime($present_start_date);
						if ($receive_order_date_timestamp < $present_start_date_timestamp){
							if ($this->print_flg){
								print '<div class="alert" style="margin-top:4px;">';
								print '伝票番号[';
								print '<span class="a_button" name="open_ne_slip" slip_no="';
								print $receive_order_id;
								print '" style="font-size:24px;">';
								print $receive_order_id;
								print '</span>';
								print ']';
								print ' 行[';
								print $ro['receive_order_row_no'];
								print '] スキップ ';
								print $ro['receive_order_row_goods_id'];
								print ' 受注日:';
								print $receive_order_date;
								print ' < 開始日:';
								print $present_start_date;
								print '</div>';
								print "\n";
							}
							$skip_flg = true;
						}
					}
					if ($skip_flg == false){
						if ($this->cut_receive_order_import_type_id2){
							if ($ro['receive_order_import_type_id'] == 2){
								if ($this->print_flg){
									print '<div class="alert" style="margin-top:4px;">';
									print '伝票番号[';
									print '<span class="a_button" name="open_ne_slip" slip_no="';
									print $receive_order_id;
									print '" style="font-size:24px;">';
									print $receive_order_id;
									print '</span>';
									print ']';
									print ' 行[';
									print $ro['receive_order_row_no'];
									print '] スキップ ';
									print $ro['receive_order_row_goods_id'];
									print ' 取込種類:';
									print $ro['receive_order_import_type_name'];
									print '</div>';
									print "\n";
								}
								$skip_flg = true;
							}
						}
					}
					if ($skip_flg == false){
						if ($pre_receive_order_id <> $receive_order_id){
							$i_u++;
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
						if ($ro['receive_order_row_cancel_flag'] != 1){
							if ($this->print_flg){
								print '<div class="small light_color" style="margin-left:16px;">';
								print $ro['receive_order_row_no'];
								print ':';
								print $ro['receive_order_row_goods_id'];
								print ' ';
								print $ro['receive_order_row_quantity'];
								print ' →追加 ';
								print $ro['add_goods_id'];
								print ' ';
								print $present_add_false_reason;

								print ' 取込種類:';
								print $ro['receive_order_import_type_name'];

								print '</div>'."\n";
								ob_clean();
								flush();
							}
							$this->up_texts[$i_u]['xml'] .= '<receive_order_row_no value="">'."\n";
							$this->up_texts[$i_u]['xml'] .= '<receive_order_row_goods_id>';
							$this->up_texts[$i_u]['xml'] .= $ro['add_goods_id'];
							$this->up_texts[$i_u]['xml'] .= '</receive_order_row_goods_id>'."\n";
							$this->up_texts[$i_u]['xml'] .= '<receive_order_row_goods_name>';
							$this->up_texts[$i_u]['xml'] .= $ro['add_goods_name'];
							$this->up_texts[$i_u]['xml'] .= '</receive_order_row_goods_name>'."\n";
							$this->up_texts[$i_u]['xml'] .= '<receive_order_row_quantity>';
							$this->up_texts[$i_u]['xml'] .= '1';
							$this->up_texts[$i_u]['xml'] .= '</receive_order_row_quantity>'."\n";
							$this->up_texts[$i_u]['xml'] .= '<receive_order_row_unit_price>';
							$this->up_texts[$i_u]['xml'] .= '0';
							$this->up_texts[$i_u]['xml'] .= '</receive_order_row_unit_price>'."\n";
							$this->up_texts[$i_u]['xml'] .= '<receive_order_row_sub_total_price>';
							$this->up_texts[$i_u]['xml'] .= '0';
							$this->up_texts[$i_u]['xml'] .= '</receive_order_row_sub_total_price>'."\n";
							$this->up_texts[$i_u]['xml'] .= '</receive_order_row_no>'."\n";
						}
						if ($receive_order_id <> $ros[($i_ro+1)]['receive_order_id']){
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
						}
						$pre_receive_order_id = $receive_order_id;
					}
				}
				if ($update_exist == false){
					if ($this->print_flg){
						print '<div class="error bold">該当伝票無し</div>'."\n";
						ob_clean();
						flush();
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
