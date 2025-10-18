<?php
// Tonary_FileReader
// Copyright (C) 2014 Tonary Management System, Inc. All Rights Reserved.

require_once(dirname(__FILE__).'/Tonary.php');

class Tonary_FileReader{
	const R_TSV = 'tsv';
	const R_CSV = 'csv';
	const E_UTF = 'utf';
	const E_SJIS = 'sjis';

	protected $enc_type = self::E_UTF;
	protected $read_type = self::R_CSV;
	protected $print_text = false;

	protected $fp = null;

	public $params = array(
		'none_define_error_flag' => null,
	);

	public $tokens = array();
	public $return_text = '';

	public function __construct(){
	}

	public function set_enc_type($enc_type){
		$this->enc_type = $enc_type;
	}

	public function set_read_type($read_type){
		$this->read_type = $read_type;
	}

	public function set_print_text($print_text){
		$this->print_text = $print_text;
	}

	public function set_fp($fp){
		$this->fp = $fp;
	}

	public function read(){
		$rtn = array();
		$data_array = array();

		$head = array();
		$data = fgetcsv($this->fp, 0, ',', '"');
		if ($this->enc_type == self::E_SJIS){
			for ($j=0; $j<count($data); $j++){
				$data[$j] = mb_convert_encoding($data[$j], 'UTF-8', 'SJIS-win');
			}
		}
		for ($j=0; $j<count($data); $j++){
			$exit_flg = false;
			for ($k=0; $k<count($this->tokens); $k++){
				if ($data[$j] == $this->tokens[$k]['csv_name']){
					$head[$j] = $this->tokens[$k];
					$exit_flg = true;
					break;
				}
			}
			if ($exit_flg == false){
				$head[$j] = array();
				if ($this->params['none_define_error_flag']){
					$rtn['error'] .= '列'.($j+1).'['.$data[$j].']は不正な項目です。'."\n";
				}
			}
		}
		$i = 0;
		while (($data = fgetcsv($this->fp, 0, ',', '"')) !== false){
			if ($this->enc_type == self::E_SJIS){
				for ($j=0; $j<count($data); $j++){
					$data[$j] = mb_convert_encoding($data[$j], 'UTF-8', 'SJIS-win');
				}
			}
			for ($j=0; $j<count($data); $j++){
				if ($head[$j]['csv_name'] != ''){
					if ($head[$j]['number'] == 1){
						$tmp = str_replace(array(',','\\'), '', $data[$j]);
						$data_array[$i][$head[$j]['csv_name']] = $tmp;
					}else{
						$data_array[$i][$head[$j]['csv_name']] = $data[$j];
					}
				}
			}
			$i++;
		}
		$rtn['head'] = $head;
		$rtn['data'] = $data_array;
		return $rtn;
	}

}
?>
