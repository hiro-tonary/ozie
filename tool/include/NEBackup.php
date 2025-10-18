<?php
// NEBackup
// Copyright (C) 2014 Tonary Management System, Inc. All Rights Reserved.

require_once(dirname(__FILE__).'/Tonary.php');
require_once(dirname(__FILE__).'/Tonary_FileMaker.php');
require_once(dirname(__FILE__).'/NE/NEBackupGoods.php');
require_once(dirname(__FILE__).'/NE/NEBackupSupplier.php');
require_once(dirname(__FILE__).'/NE/NEBackupPage.php');
require_once(dirname(__FILE__).'/NE/NEBackupVo.php');

class NEBackup{

	public $nextengine = null;
	public $print_flg = false;

	private $subdir = 'backup';
	private $filename = '';

	public function get_subdir(){
		return $this->subdir;
	}

	public function get_filename(){
		return $this->filename;
	}

	public function get_lists(){
		$rtn_message = '';
		$lists = array();
		try{
			$filedir = Config::$tmpdir_path.$this->subdir.'/';
			$files = scandir($filedir);
			if ($files !== false){
				$i_l = 0;
				foreach($files as $file){
					if($file == '.' || $file == '..'){
						continue;
					}else if (is_file($filedir.$file)){
						$lists[$i_l]['filename'] = $file;
						$lists[$i_l]['filemtime'] = filemtime($filedir.$file);
						$i_l++;
					}
				}
			}
		} catch (Exception $e) {
			$rtn_message .= 'NEBackup get_list Exception: '. $e->getMessage();
		}
		if ($rtn_message != ''){
			return $rtn_message;
		}else{
			return $lists;
		}
	}

	public function execute(){
		$rtn_message = '';
		try{
			date_default_timezone_set('Asia/Tokyo');

			$filepaths = array();
			$file_prefix = date('YmdHis');
			$del_ymd = date('Ymd', strtotime('- '.Config::$keep_days.' days'));
			$filedir = Config::$tmpdir_path.$this->subdir.'/';
			$files = scandir($filedir);
			if ($files !== false){
				foreach($files as $file){
					if($file == '.' || $file == '..'){
						continue;
					}else if (is_file($filedir.$file)){
						if (strlen($file) > 8){
							$file_ymd = substr($file,0,8);
							if ($file_ymd < $del_ymd){
								unlink($filedir.$file);
							}
						}
					}
				}
			}
			$targets = array(
				new NEBackupGoods()
				,new NEBackupSupplier()
				,new NEBackupPage()
				,new NEBackupVo()
			);
			$i_f = 0;
			foreach ($targets as $target){
				$filename = $file_prefix.$target->file_suffix;
				$filepath = $filedir.$filename;
				$filepaths[$i_f]['filename'] = $filename;
				$filepaths[$i_f]['filepath'] = $filepath;
				if(!file_exists($filepath)){
					if ($this->print_flg){
						print '<div>';
						print $target->target_name.'をバックアップしています ';
						print '</div>'."\n";
						ob_clean();
						flush();
					}
					$fp = fopen($filepath, 'w');
					$filemaker = new Tonary_FileMaker();
					$filemaker->set_enc_type(Tonary_FileMaker::E_SJIS);
					$filemaker->set_write_type(Tonary_FileMaker::W_CSV);

					$filemaker->set_fp($fp);
					$filemaker->tokens = $target->tokens;

					$data_array = array();
					$loop_flg = true;
					$offset = 0;
					$limit = 10000;
					while ($loop_flg){
						$query_array = array();
						$query_array['offset'] = $offset;
						$query_array['limit'] = $limit;
						$query_array['fields'] = $target->query_fields;
						$query_array[$target->deleted_flag.'-neq'] = '1';
						$query_array['sort'] = $target->query_sort;
						$api_return = $this->nextengine->apiExecute($target->api_url, $query_array);
						if ($api_return['result'] != 'success'){
							$loop_flg = false;
							$rtn_message .= 'バックアップシステムエラー '
								.$target->target_name.' '.$api_return['message'];
						}else{
							$data_array = array_merge($data_array, $api_return['data']);
							$api_count = intval($api_return['count']);
							if ($api_count < $limit){
								$loop_flg = false;
							}else{
								$offset += $limit;
								if ($this->print_flg){
									print '<div>マスタの行数';
									print $limit.'超。ループ取得します';
									print '</div>'."\n";
									ob_clean();
									flush();
								}
							}
						}
					}

					$filemaker->data_array = $data_array;
					$return = $filemaker->execute();
					fclose($fp);
				}
				$i_f++;
			}
			$zip = new ZipArchive();
			$this->filename = $file_prefix.'.zip';
			$zipfilepath = $filedir.$this->filename;
			$result = $zip->open($zipfilepath, ZIPARCHIVE::CREATE | ZIPARCHIVE::OVERWRITE);
			if ($result !== true) {
				$rtn_message .= 'バックアップシステムエラー ZIP open ERROR: '.$result;
			}else{
				for ($i_f=0; $i_f<count($filepaths); $i_f++){
					$result = $zip->addFile(
						$filepaths[$i_f]['filepath'],
						Tonary::sjis($filepaths[$i_f]['filename'])
					);
					if ($result !== true) {
						$rtn_message .= 'バックアップシステムエラー ZIP addFile ERROR: '.$result;
					}
				}
				$result = $zip->close();
				if ($result !== true) {
					$rtn_message .= 'バックアップシステムエラー ZIP close ERROR: '.$result;
				}
			}
			for ($i_f=0; $i_f<count($filepaths); $i_f++){
				unlink($filepaths[$i_f]['filepath']);
			}
		} catch (Exception $e) {
			$rtn_message .= 'バックアップシステムエラー '. $e->getMessage();
		}
		return $rtn_message;
	}

}
