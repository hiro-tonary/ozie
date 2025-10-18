<?php
// NEBundleExec

require_once(Config::$global_include_path.'aNERoutineExec.php');

class NEBundleExec extends aNERoutineExec{

    public $params = array(
        'wait_flag' => null,
    );

    public function execute(){
        $msg_ne = 'ネクストエンジン: ';
        $ne = $this->nextengine;
        $php_id = 'NEBundleExec';
        $pattern_text = '[PATTERN002]';
        $pattern_name = 'ネクタイ3本よりどり割引';
        $error_flg = false;
        $rtn_message = '';
        $target_goods_ids_query = '';
        try{
            $this->executelog = new Tonary_ExecuteLog(
                null,
                $ne->login_user,
                array(
                    'php_id' => $php_id,
                    'execute_id' => $pattern_text,
                    'execute_name' => $pattern_name
                )
            );
            $exlog = $this->executelog;
            require_once(Config::$global_include_path.'NE/NEBackupGoods.php');
            $target = new NEBackupGoods();
            $query = array();
            $query['offset'] = 0;
            $query['limit'] = 10000;
            $query['fields'] = $target->query_fields;
            $query['goods_deleted_flag-neq'] = '1';
            $query['goods_jan_code-eq'] = '5000';
            $query['sort'] = 'goods_representation_id-asc,goods_id-asc';
            $api_return = $ne->apiExecute(
                '/api_v1_master_goods/search',
                $query
            );
            if ($api_return['result'] != 'success'){
                $rtn_message .= 'API ERROR '.$api_return['message']."\n";
                $exlog->set_log_message(
                    $msg_ne.$api_return['message'],
                    print_r($api_return, true),
                    -1
                );
                $error_flg = true;
            }else{
                if (intval($api_return['count']) >= 10000){
                    $rtn_message .= '行数が多すぎます'."\n";
                    $exlog->set_log_message(
                        $msg_ne.'バンドル対象商品の行数が10000件を超えています',
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
            $query = array();
            $query['fields'] = $ro_fields_query;
            $query['offset'] = 0;
            $query['limit'] = 10000;
            $query['receive_order_shop_id-in'] = '1,2,3';
            $query['receive_order_row_goods_id-in'] = $target_goods_ids_query;
            $query['receive_order_order_status_id-in'] = '2,20';
            $query['receive_order_cancel_type_id-eq'] = '0';
            $query['receive_order_row_cancel_flag-neq'] = '1';
            $query['sort'] = 'receive_order_id-asc,receive_order_row_no-asc';
            $api_return = $ne->apiExecute(
                '/api_v1_receiveorder_row/search',
                $query
            );
            if ($api_return['result'] != 'success') {
                $rtn_message .= 'API ERROR '.$api_return['message']."\n";
                $exlog->set_log_message(
                    $msg_ne.$api_return['message'],
                    print_r($api_return, true),
                    -1
                );
                $error_flg = true;
            } else {
                if (intval($api_return['count']) >= 10000) {
                    $rtn_message .= '行数が多すぎます'."\n";
                    $exlog->set_log_message(
                        $msg_ne.'該当する受注行数が10000件を超えています',
                        '',
                        -1
                    );
                    $error_flg = true;
                }
                $ros = $api_return['data'];
            }
            $ros_count = count($ros);
            if ($error_flg == false) {
                $total_quantity = 0;
                $i_u = 0;
                for ($i_ro=0; $i_ro<$ros_count; $i_ro++) {
                    $ro = $ros[$i_ro];
                    $ro_worker_text = $ro['receive_order_worker_text'];
                    if (strpos($ro_worker_text, $pattern_text) !== false) {
                        continue;
                    }
                    $ro_id = $ro['receive_order_id'];
                    $next_ro_id = $ros[($i_ro+1)]['receive_order_id'];
                    $ro_shop_id = $ro['receive_order_shop_id'];
                    $ro_delivery_cut_form_note
                        = $ro['receive_order_delivery_cut_form_note'];
                    $ro_note = $ro['receive_order_note'];
                    $ro_row_quantity = intval($ro['receive_order_row_quantity']);
                    $total_quantity += $ro_row_quantity;
                    if ($ro_id <> $next_ro_id) {
                        if ($total_quantity >= 3) {
                            if ($this->print_flg) {
                                print '<div>伝票番号.';
                                print $ro_id;
                                print ' <span class="button" name="open_ne_slip"';
                                print ' slip_no="';
                                print $ro_id;
                                print '" style="font-size:11px;padding:0;">';
                                print '伝票</span> ';
                                print '</div>'."\n";
                                ob_clean();
                                flush();
                            }
                            $add_goods_id = 'nekutai';
                            $add_goods_name = $pattern_name;
                            $add_unit_price = 0;
                            $add_quantity = intval($total_quantity / 3);
                            $add_sub_total_price = 0;
                            $exlog->add_log($ro);
                            $up_text = array();
                            $up_text['receive_order_id'] = $ro_id;
                            $up_text['receive_order_last_modified_date']
                                 = $ro['receive_order_last_modified_date'];
                            $xml = '';
                            $xml .= '<?xml version="1.0" encoding="utf-8"?>'."\n";
                            $xml .= '<root>'."\n";
                            $xml .= '<receiveorder_base>'."\n";
                            $goods_amount
                                = intval($ro['receive_order_goods_amount']);
                            $tax_amount
                                = intval($ro['receive_order_tax_amount']);
                            $charge_amount
                                = intval($ro['receive_order_charge_amount']);
                            $delivery_fee_amount
                                 = intval($ro['receive_order_delivery_fee_amount']);                            $other_amount
                                = intval($ro['receive_order_other_amount']);
                            $point_amount
                                = intval($ro['receive_order_point_amount']);
                            $total_amount
                                = intval($ro['receive_order_total_amount']);
                            $discount = ($add_quantity * 1080);
                            $original = $charge_amount;
                            //手数料を割引
                            $charge_amount -= $discount;
                            if ($ro_shop_id == 1) {
                                //商品計を加算（FSは商品計だけ割引済みなので）
                                $goods_amount += $discount;
                            }
                            $add_note = '';
                            $add_option = '手数料にて割引。';
                            if (strpos($ro_note, '■楽天バンク決済') !== false) {
                                $add_note .= '手数料内訳：'."\n";
                                $add_option .= '手数料内訳：';
                                if ($original != 155){
                                    $add_note .= 'その他 '.($original-155);
                                    $add_note .= '円'."\n";
                                    $add_option .= 'その他 '.($original-155);
                                    $add_option .= '円、';
                                }
                                $add_note .= '楽天バンク決済 155円'."\n";
                                $add_note .= 'ネクタイよりどり割引 ';
                                $add_option .= '楽天バンク決済 155円、';
                                $add_option .= 'ネクタイよりどり割引 ';
                            } else if ($original != 0) {
                                $add_note .= '手数料内訳：'."\n";
                                $add_note .= 'その他 '.$original.'円'."\n";
                                $add_note .= 'ネクタイよりどり割引 ';
                                $add_option .= '手数料内訳：';
                                $add_option .= 'その他 '.$original.'円、';
                                $add_option .= 'ネクタイよりどり割引 ';
                            } else {
                                $add_note .= '手数料内訳：'."\n";
                                $add_note .= 'ネクタイよりどり割引 ';
                            }
                            $add_note .= '-'.$discount.'円';
                            $add_option .= '-'.$discount.'円';
                            if ($ro_shop_id == 3) {
                                //他費用を加算（Yahooは他費用で割引済みなので）
                                //$other_amount += $discount;
                                //2016/1 カット
                            }
                            $total_amount = $goods_amount;
                            $total_amount += $tax_amount;
                            $total_amount += $charge_amount;
                            $total_amount += $delivery_fee_amount;
                            $total_amount += $other_amount;
                            $total_amount -= $point_amount;
                            $xml .= '<receive_order_goods_amount>';
                            $xml .= $goods_amount;
                            $xml .= '</receive_order_goods_amount>'."\n";
/*
                            $xml .= '<receive_order_tax_amount>';
                            $xml .= $tax_amount;
                            $xml .= '</receive_order_tax_amount>'."\n";
*/
                            $xml .= '<receive_order_charge_amount>';
                            $xml .= $charge_amount;
                            $xml .= '</receive_order_charge_amount>'."\n";
/*
                            $xml .= '<receive_order_delivery_fee_amount>';
                            $xml .= $delivery_fee_amount;
                            $xml .= '</receive_order_delivery_fee_amount>'."\n";
*/
                            $xml .= '<receive_order_other_amount>';
                            $xml .= $other_amount;
                            $xml .= '</receive_order_other_amount>'."\n";
/*
                            $xml .= '<receive_order_point_amount>';
                            $xml .= $point_amount;
                            $xml .= '</receive_order_point_amount>'."\n";
*/
                            $xml .= '<receive_order_total_amount>';
                            $xml .= $total_amount;
                            $xml .= '</receive_order_total_amount>'."\n";
                            $xml .= '<receive_order_confirm_check_id>';
                            $xml .= '0';
                            $xml .= '</receive_order_confirm_check_id>'."\n";
                            $xml .= '<receive_order_worker_text>';
                            if ($ro_worker_text == '') {
                                $tmp = $pattern_text;
                            } else {
                                $tmp = $ro_worker_text."\n".$pattern_text;
                            }
                            $xml .= $tmp;
                            $xml .= '</receive_order_worker_text>'."\n";
/*
                            $xml .= '<receive_order_delivery_cut_form_note>';
                            if ($ro_delivery_cut_form_note == ''){
                                $tmp = $add_note;
                            }else{
                                $tmp = $ro_delivery_cut_form_note."\n".$add_note;
                            }
                            $xml .= $tmp;
                            $xml .= '</receive_order_delivery_cut_form_note>'."\n";
*/
                            $xml .= '</receiveorder_base>'."\n";
                            $xml .= '<receiveorder_row>'."\n";
                            $xml .= '<receive_order_row_no value="">'."\n";
                            $xml .= '<receive_order_row_goods_id>';
                            $xml .= $add_goods_id;
                            $xml .= '</receive_order_row_goods_id>'."\n";
                            $xml .= '<receive_order_row_goods_name>';
                            $xml .= $add_goods_name;
                            $xml .= '</receive_order_row_goods_name>'."\n";
                            $xml .= '<receive_order_row_unit_price>';
                            $xml .= $add_unit_price;
                            $xml .= '</receive_order_row_unit_price>'."\n";
                            $xml .= '<receive_order_row_quantity>';
                            $xml .= $add_quantity;
                            $xml .= '</receive_order_row_quantity>'."\n";
                            $xml .= '<receive_order_row_sub_total_price>';
                            $xml .= $add_sub_total_price;
                            $xml .= '</receive_order_row_sub_total_price>'."\n";
                            if ($add_option != '') {
                                $xml .= '<receive_order_row_goods_option>';
                                $xml .= $add_option;
                                $xml .= '</receive_order_row_goods_option>'."\n";
                            }
                            $xml .= '</receive_order_row_no>'."\n";
                            $xml .= '</receiveorder_row>'."\n";
                            $xml .= '</root>'."\n";

                            $query = array();
                            $query['receive_order_id']
                                = $up_text['receive_order_id'];
                            $query['receive_order_last_modified_date']
                                 = $up_text['receive_order_last_modified_date'];
                            $query['data'] = $xml;
                            $up_text['xml'] = $xml;
                            $this->up_texts[$i_u] = $up_text;
                            if ($this->params['wait_flag'] !== null) {
                                $query['wait_flag'] = $this->params['wait_flag'];
                            }
                            if ($this->not_execute_flg) {
                                print '<div class="alert">';
                                print '受注伝票を更新しないモード';
                                print '（テスト用）です。</div>'."\n";
                            } else {
                                $api_update_result = $ne->apiExecute(
                                    '/api_v1_receiveorder_base/update',
                                    $query
                                );
                                if ($api_update_result['result'] == 'success') {
                                    if ($this->print_flg){
                                        print '<div class="report">';
                                        print '更新成功</div>'."\n";
                                        ob_clean();
                                        flush();
                                    }
                                } else {
                                    $rtn_message .= '伝票番号.'
                                        .$up_text['receive_order_id']
                                        .' 更新失敗 '
                                        .print_r($api_update_result, true)."\n";
                                    $exlog->set_log_message(
                                        $msg_ne.$api_update_result['message'],
                                        print_r($api_update_result, true),
                                        $i_u
                                    );
                                    $error_flg = true;
                                    if ($this->print_flg) {
                                        print '<div class="error">';
                                        print '更新失敗</div>'."\n";
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
            if ($this->not_execute_flg == false) {
                $log = print_r($this->up_texts, true);
                $ne->write_inspectlog($log);
                $exlog->save_log();
            }
        } catch (Exception $e) {
            $rtn_message .= 'システムエラー '. $e->getMessage()."\n";
        }
        if ($rtn_message != '') {
            $ne->write_errorlog($rtn_message);
        }
        return $rtn_message;
    }

}
