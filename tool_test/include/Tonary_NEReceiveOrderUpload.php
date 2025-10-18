<?php
class Tonary_NEReceiveOrderUpload{

	public $nextengine = null;
	public $upload_text1 = '';
	public $upload_text2 = '';
	public $quewait = false;
	public $print_state = false;
	public $print_error = false;
	public $fp = null;
	public $error_fp = null;

	public $que = null;
/*
	public $receive_order_upload_pattern_id = '';
	public $api_uploaded = null;
*/

	public function __construct(){
	}

/*
	戻り：自動修正した文字列
		$param_array['result']: 'success', 'convert', 'error'
		$param_array['original_value']
		$param_array['value']
		$param_array['message']
*/
	public function repair_int($field_name, $value, &$param_array, $max=0, $pre_message=''){
		if ($this->fp == null){
			$this->fp = fopen('php://stdout', 'w');
		}
		if ($this->error_fp == null){
			$this->error_fp = fopen('php://stdout', 'w');
		}
		$param_array = array();
		$param_array['original_value'] = $value;
		$param_array['value'] = '';
		$param_array['result'] = '';
		$param_array['message'] = '';
		$message = '';
		$repair_text = '';
		try{
			$org = trim($value);
			$org = mb_convert_kana($org, 'n');
			if (is_numeric($org)){
				$int_value = intval($org);
				$repair_text = strval($int_value);
				$length = mb_strlen($repair_text);
				if ($max > 0){
					if ($length > $max){
						$repair_text = '';
						$param_array['result'] = 'convert';
						$param_array['message'] .= $pre_message.'桁数が多すぎます。';
						if ($this->print_error){
							fwrite($this->error_fp, $pre_message.
								$field_name.' '.Tonary::html($value).' 省きました。（桁数が多すぎます）'."\n");
						}
					}else{
						$param_array['result'] = 'success';
					}
				}else{
					$param_array['result'] = 'success';
				}
			}else{
				$repair_text = '';
				$param_array['result'] = 'convert';
				$param_array['message'] .= $pre_message.'数字で指定してください。';
				if ($this->print_error){
					fwrite($this->error_fp, $pre_message.
						$field_name.' '.Tonary::html($value).' 省きました。（数字で指定してください）'."\n");
				}
			}
			$param_array['value'] = $repair_text;
		}catch (Exception $e){
			$param_array['result'] = 'error';
			$param_array['message'] =  $pre_message.'Exception: '.$e->getMessage();
			if ($this->print_error){
				fwrite($this->error_fp, $pre_message.$field_name.' Exception: '.$e->getMessage()."\n");
			}
		}
		return $repair_text;
	}

/*
	戻り：自動修正した文字列
		$param_array['result']: 'success', 'convert', 'error'
		$param_array['original_value']
		$param_array['value']
		$param_array['message']
*/
	public function repair_text($field_name, $value, &$param_array, $max=0, $pre_message='', $type=''){
		if ($this->fp == null){
			$this->fp = fopen('php://stdout', 'w');
		}
		if ($this->error_fp == null){
			$this->error_fp = fopen('php://stdout', 'w');
		}
		$param_array = array();
		$param_array['original_value'] = $value;
		$param_array['value'] = '';
		$param_array['result'] = '';
		$param_array['message'] = '';
		$message = '';
		$repair_text = '';
		try{
			$org = $value;
			if ($type == 'name'){
				$org = str_replace('\\', '￥', $org);
				$org = str_replace('/', '／', $org);
				$org = str_replace(';', '；', $org);
				$org = str_replace('*', '＊', $org);
				$org = str_replace('"', '”', $org);
				$org = str_replace('"', '“', $org);
				$org = str_replace('^', '＾', $org);
				$org = str_replace('\'', '’', $org);
				$org = str_replace('<', '＜', $org);
				$org = str_replace('>', '＞', $org);
				$org = str_replace('|', '｜', $org);
				$org = str_replace('.', '．', $org);
				$org = str_replace('~', '～', $org);
			}
			$org = str_replace(':', '：', $org);
			$org = str_replace(',', '，', $org);
			$org = str_replace('?', '？', $org);
			$org = mb_convert_kana($org, 'KV');
			if ($this->print_error){
				if (mb_strwidth($value) != mb_strwidth($org)){
					fwrite($this->error_fp, $pre_message.$field_name.' '.Tonary::html($value).' 半角→全角変換しました。'."\n");
				}
			}
			$comp = mb_convert_encoding(mb_convert_encoding($org,'SJIS','UTF-8'),'UTF-8','SJIS');
			$length = mb_strlen($comp);
			for ($i=0; $i<strlen($comp); $i++){
				$org1 = mb_substr($org, $i, 1,'UTF-8');
				$comp1 = mb_substr($comp, $i, 1,'UTF-8');
				if ($org1 == $comp1){
					$repair_text .= $org1;
				}else if ($org1 == '～'){
					$repair_text .= '～';
				}else{
					$message .= $org1;
				}
			}
			if ($message === ''){
				$param_array['result'] = 'success';
			}else{
				$param_array['result'] = 'convert';
				$param_array['message'] =  $pre_message.$field_name.' '.$message.'は使用できません。';
				if ($this->print_error){
					fwrite($this->error_fp, $pre_message.$field_name.' '.Tonary::html($value).' '.$message.'を省きました。'."\n");
				}
			}
			$length = mb_strlen($repair_text);
			if ($max > 0){
				if ($length > $max){
					$param_array['result'] = 'convert';
					$param_array['message'] .= $pre_message.'文字数が多すぎます。';
					if ($this->print_error){
						fwrite($this->error_fp, $pre_message.$field_name.' '.Tonary::html($value).' 文字数をカットしました。'."\n");
					}
					$repair_text = mb_substr($repair_text, 0, $max);
				}
			}
			$param_array['value'] = $repair_text;
		}catch (Exception $e){
			$param_array['result'] = 'error';
			$param_array['message'] = $pre_message.'Exception: '.$e->getMessage();
			if ($this->print_error){
				fwrite($this->error_fp, $pre_message.$field_name.' Exception: '.$e->getMessage()."\n");
			}
		}
		return $repair_text;
	}

/*
	戻り：''（成功）, '不明', 'エラー: '.エラー内容, 'リダイレクト: '.エラー内容
*/
	public function upload_exec(){
		if ($this->fp == null){
			$this->fp = fopen('php://stdout', 'w');
		}
		if ($this->error_fp == null){
			$this->error_fp = fopen('php://stdout', 'w');
		}
		if ($this->print_state){
			fwrite($this->fp, 'ＣＳＶファイルをネクストエンジンにアップロードします。'."\n\n");
			ob_flush();
			fflush($this->fp);
		}
		$rtn = '不明';
		$query_up = array();
		$query_up['receive_order_upload_pattern_id'] = $this->receive_order_upload_pattern_id;
		$query_up['data_type_1'] = 'gz';
		$query_up['data_1'] = gzencode($this->upload_text1, 9);
		if ($this->upload_text2 != ''){
			$query_up['data_type_2'] = 'gz';
			$query_up['data_2'] = gzencode($this->upload_text2, 9);
		}
		$this->api_uploaded = $this->nextengine->apiExecute('/api_v1_receiveorder_base/upload', $query_up);
		if ($this->api_uploaded['result'] == 'error'){
			$rtn = 'エラー: '.$this->api_uploaded['message'];
			if ($this->print_error){
				fwrite($this->error_fp, 'エラー: '.$this->api_uploaded['message']."\n");
				ob_flush();
				fflush($this->fp);
			}
		}else if ($this->api_uploaded['result'] == 'redirect'){
			$rtn = 'リダイレクト: '.$this->api_uploaded['message'];
			if ($this->print_error){
				fwrite($this->error_fp, 'リダイレクト: '.$this->api_uploaded['message']."\n");
				ob_flush();
				fflush($this->fp);
			}
		}else{
			if ($this->quewait){
				if ($this->print_state){
					fwrite($this->fp,
						'ファイルアップロード完了。キューID: '.$this->api_uploaded['que_id']."\n\n"
						.'受注一括登録の進捗状況をモニタリングします。'."\n\n");
					ob_flush();
					fflush($this->fp);
				}
				$query_q['fields'] = 'que_id, que_method_name, que_shop_id, que_upload_name,';
				$query_q['fields'] .= ' que_client_file_name, que_file_name, que_status_id, que_message,';
				$query_q['fields'] .= ' que_deleted_flag, que_creation_date, que_last_modified_date,';
				$query_q['fields'] .= ' que_creator_id, que_creator_name, que_last_modified_by_id,';
				$query_q['fields'] .= ' que_last_modified_by_name';
				$query_q['que_id-eq'] = $this->api_uploaded['que_id'];
				$wait_flg = true;
				while($wait_flg){
					sleep(5);
					$this->que = $this->nextengine->apiExecute('/api_v1_system_que/search', $query_q);
					if ($this->que['result'] == 'success'){
						$que_status_id = $this->que['data'][0]['que_status_id'];
						if ($que_status_id == 2){
							$wait_flg = false;
							$rtn = '';
							if ($this->print_state){
								fwrite($this->fp, "\n".'処理完了'."\n".$this->que['data'][0]['que_message']."\n");
								ob_flush();
								fflush($this->fp);
							}
						}else if ($que_status_id == -1){
							$wait_flg = false;
							$rtn = 'エラー: '.$this->que['data'][0]['que_message'];
							Tonary::write_errorlog($rtn);
							if ($this->print_error){
								fwrite($this->error_fp, "\n".'処理失敗'.$this->que['data'][0]['que_message']."\n");
								ob_flush();
								fflush($this->fp);
							}
						}else if ($que_status_id == 0){
							if ($this->print_state){
								fwrite($this->fp, '処理待ち'."\n");
								ob_flush();
								fflush($this->fp);
							}
						}else if ($que_status_id == 1){
							if ($this->print_state){
								fwrite($this->fp, '処理中'."\n");
								ob_flush();
								fflush($this->fp);
							}
						}else{
							if ($this->print_state){
								fwrite($this->fp, '処理待ち（状況不明）'."\n");
								ob_flush();
								fflush($this->fp);
							}
						}
					}
				}
			}else{
				$rtn = '';
				if ($this->print_state){
					fwrite($this->fp, 'ファイルアップロード完了。キューID: '.$this->api_uploaded['que_id']."\n");
					ob_flush();
					fflush($this->fp);
				}
			}
		}
		return $rtn;
	}

/*
	戻り：''（成功）, '不明', 'エラー: '.エラー内容, 'リダイレクト: '.エラー内容
*/
	public function que_check($que_id=null){
		if ($que_id == null){
			if ($this->api_uploaded != null){
				$que_id = $this->api_uploaded['que_id'];
			}
		}
		if ($que_id == null){
			return 'エラー: キューIDを指定してください。';
		}
		if ($this->fp == null){
			$this->fp = fopen('php://stdout', 'w');
		}
		if ($this->error_fp == null){
			$this->error_fp = fopen('php://stdout', 'w');
		}
		$rtn = '不明';
		if ($this->print_state){
			fwrite($this->fp,
				'キューID: '.$this->api_uploaded['que_id']
				.' の受注一括登録の進捗状況をモニタリングします。'."\n\n");
			ob_flush();
			fflush($this->fp);
		}
		$query_q['fields'] = 'que_id, que_method_name, que_shop_id, que_upload_name,';
		$query_q['fields'] .= ' que_client_file_name, que_file_name, que_status_id, que_message,';
		$query_q['fields'] .= ' que_deleted_flag, que_creation_date, que_last_modified_date,';
		$query_q['fields'] .= ' que_creator_id, que_creator_name, que_last_modified_by_id,';
		$query_q['fields'] .= ' que_last_modified_by_name';
		$query_q['que_id-eq'] = $que_id;
		$wait_flg = true;
		while($wait_flg){
			sleep(5);
			$this->que = $this->nextengine->apiExecute('/api_v1_system_que/search', $query_q);
			if ($this->que['result'] == 'success'){
				$que_status_id = $this->que['data'][0]['que_status_id'];
				if ($que_status_id == 2){
					$wait_flg = false;
					$rtn = '';
					if ($this->print_state){
						fwrite($this->fp, "\n".'処理成功'."\n");
						ob_flush();
						fflush($this->fp);
					}
				}else if ($que_status_id == -1){
					$wait_flg = false;
					$rtn = 'エラー: '.$this->que['data'][0]['que_message'];
					if ($this->print_error){
						fwrite($this->error_fp, "\n".'処理失敗'.$this->que['data'][0]['que_message']."\n");
						ob_flush();
						fflush($this->fp);
					}
				}else if ($que_status_id == 0){
					if ($this->print_state){
						fwrite($this->fp, '処理待ち'."\n");
						ob_flush();
						fflush($this->fp);
					}
				}else if ($que_status_id == 1){
					if ($this->print_state){
						fwrite($this->fp, '処理中'."\n");
						ob_flush();
						fflush($this->fp);
					}
				}else{
					if ($this->print_state){
						fwrite($this->fp, '処理待ち（状況不明）'."\n");
						ob_flush();
						fflush($this->fp);
					}
				}
			}
		}
		return $rtn;
	}

	public static function csv_clean($text, $empty = ''){
		if ($text == ''){
			$return_text = $empty;
		}else{
			$return_text = str_replace('"','""',$text);
		}
		return $return_text;
	}

}
