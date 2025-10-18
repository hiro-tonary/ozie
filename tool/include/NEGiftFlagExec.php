<?php
// NEGiftFlagExec

require_once(Config::$global_include_path.'aNERoutineExec.php');

class NEGiftFlagExec extends aNERoutineExec{

	public $params = array(
		'wait_flag' => null,
	);

	public function execute(){
		$php_id = 'NEGiftFlagExec';
		$pattern_text = '[PATTERN003]';
		$pattern_name = 'ギフトフラグ';
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
			$ro_fields_query = '';
			for ($j=0; $j<count(NEReceiveOrder::$fields); $j++){
				if ($ro_fields_query != ''){
					$ro_fields_query .= ',';
				}
				$ro_fields_query .= NEReceiveOrder::$fields[$j]['field'];
			}
			$query_ro = array();
			$query_ro['fields'] = $ro_fields_query;
			$query_ro['offset'] = 0;
			$query_ro['limit'] = 10000;
			$query_ro['receive_order_shop_id-in'] = '2';
			$query_ro['receive_order_note-like'] = '%[包装紙]%';
			$query_ro['receive_order_order_status_id-in'] = '2,20';
			$query_ro['receive_order_cancel_type_id-eq'] = '0';
			$query_ro['sort'] = 'receive_order_id-asc';
			$api_return = $this->nextengine->apiExecute('/api_v1_receiveorder_base/search', $query_ro);
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
			$ros_count = count($ros);
			if ($error_flg == false){
				$i_u = 0;
				for ($i_ro=0; $i_ro<$ros_count; $i_ro++){
					$shop_id = $ros[$i_ro]['receive_order_shop_id'];
					$receive_order_id = $ros[$i_ro]['receive_order_id'];
					$receive_order_worker_text = $ros[$i_ro]['receive_order_worker_text'];
					$receive_order_note = $ros[$i_ro]['receive_order_note'];
					if (strpos($receive_order_worker_text, $pattern_text) !== false){
						continue;
					}
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

					$this->up_texts[$i_u]['xml'] .= '<receive_order_note>';
					$tmp = str_replace('[包装紙]', '', $receive_order_note);
					$this->up_texts[$i_u]['xml'] .= $tmp;
					$this->up_texts[$i_u]['xml'] .= '</receive_order_note>'."\n";

					$this->up_texts[$i_u]['xml'] .= '<receive_order_worker_text>';
					if ($receive_order_worker_text == ''){
						$tmp = $pattern_text;
					}else{
						$tmp = $receive_order_worker_text."\n".$pattern_text;
					}
					$this->up_texts[$i_u]['xml'] .= $tmp;
					$this->up_texts[$i_u]['xml'] .= '</receive_order_worker_text>'."\n";

					$this->up_texts[$i_u]['xml'] .= '<receive_order_gift_flag>';
					$this->up_texts[$i_u]['xml'] .= '1';
					$this->up_texts[$i_u]['xml'] .= '</receive_order_gift_flag>'."\n";

					$this->up_texts[$i_u]['xml'] .= '</receiveorder_base>'."\n";
					$this->up_texts[$i_u]['xml'] .= '<receiveorder_row>'."\n";
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
