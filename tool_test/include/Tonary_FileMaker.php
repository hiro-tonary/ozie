<?php
// Tonary_FileMaker
// Copyright (C) 2014 Tonary Management System, Inc. All Rights Reserved.

require_once(dirname(__FILE__).'/Tonary.php');

class Tonary_FileMaker{
	const W_HTML = 'html';
	const W_TSV = 'tsv';
	const W_CSV = 'csv';
	const E_UTF = 'utf';
	const E_SJIS = 'sjis';

	protected $enc_type = self::E_UTF;
	protected $write_type = self::W_HTML;

	protected $quot = '"';

	protected $print_text = false;
	protected $fp = null;
	protected $rs = null;

	public $tokens = array();
	public $sql = '';
	public $data_array = null;
	public $return_text = '';

	public function __construct(){
	}

	public function set_enc_type($enc_type){
		$this->enc_type = $enc_type;
	}

	public function set_print_text($print_text){
		$this->print_text = $print_text;
	}

	public function set_write_type($write_type){
		$this->write_type = $write_type;
	}

	public function set_fp($fp){
		$this->fp = $fp;
	}

	protected function file_print($text){
		if ($this->print_text){
			print $text;
		}
		if ($this->fp != null){
			fwrite($this->fp, $text);
		}
		$this->return_text .= $text;
	}

	public function writeNewLine(){
		if ($this->write_type == self::W_HTML){
			$this->file_print("</tr>\n");
		}else{
			$this->file_print("\n");
		}
		return '';
	}

	public function set_quot($quot){
		$this->quot = $quot;
	}

	public function query(){
		if (!$this->rs=mysql_query($this->sql)){
			die($this->sql.' error');
		}
		return $this->rs;
	}

	public function execute($writehead=true){
		$loop_flg = false;
		if ($writehead){
			if ($this->write_type == self::W_HTML){
				$this->file_print("<table border='1' cellpadding='0' cellspacing='0'>\n");
			}
			$this->writeHeadLine();
		}
		$count = 0;
		$i = 0;
		if ($this->data_array == null and $this->sql == ''){
			return;
		}
		if ($this->data_array == null){
			if ($this->rs == null){
				if (!$this->rs=mysql_query($this->sql)){
					die($this->sql.' error');
				}
			}
			$row = mysql_fetch_array($this->rs, MYSQL_ASSOC);
			if ($row){
				$loop_flg = true;
				$data = $row;
			}
		}else{
			$count = count($this->data_array);
			if ($i < $count){
				$loop_flg = true;
				$data = $this->data_array[$i];
			}
		}
		while ($loop_flg){
			$this->writeLine($data);
			if ($this->data_array == null){
				$row = mysql_fetch_array($this->rs, MYSQL_ASSOC);
				if ($row){
					$data = $row;
				}else{
					$loop_flg = false;
				}
			}else{
				$i += 1;
				if ($i < $count){
					$data = $this->data_array[$i];
				}else{
					$loop_flg = false;
				}
			}
		}
		if ($writehead){
			if ($this->write_type == self::W_HTML){
				$this->file_print("</table>\n");
			}
		}
		if ($this->data_array == null){
			mysql_free_result($this->rs);
			$this->rs = null;
		}
		return $this->return_text;
	}

	protected function enc($value){
		if ($this->enc_type == self::E_UTF){
			if ($this->write_type == self::W_HTML){
				$tmp = Tonary::html($value);
				return str_replace('&lt;br&gt;','<br>',$tmp);
			}else{
				return Tonary::html($value);
			}
		}else{
			return Tonary::sjis($value);
		}
	}

	public function writeColumn($value, $end_column=0, $class=''){
		if ($this->write_type == self::W_HTML){
			$this->file_print('<td'.$class.'>');
			$this->file_print($this->enc($value));
			$this->file_print("</td>\n");
		}else if ($this->write_type == self::W_TSV){
			if (strpos($value,"\n") !== false
			 or strpos($value,'"') !== false){
				$this->file_print($this->quot);
				$this->file_print($this->enc(str_replace('"','""',$value)));
				$this->file_print($this->quot);
			}else{
				$this->file_print($this->enc($value));
			}
			if ($end_column==0){
				$this->file_print("\t");
			}
		}else{
			$this->file_print($this->quot);
			$this->file_print($this->enc(str_replace('"','""',$value)));
			$this->file_print($this->quot);
			if ($end_column==0){
				$this->file_print(',');
			}
		}
	}

	protected function writeHeadColumn($value,$end_column=0){
		if ($this->write_type == self::W_HTML){
			$this->file_print('<th>');
			$this->file_print($this->enc($value));
			$this->file_print("</th>\n");
		}else if ($this->write_type == self::W_TSV){
			$this->file_print($this->enc($value));
			if ($end_column==0){
				$this->file_print("\t");
			}
		}else{
			$this->file_print($this->quot);
			$this->file_print($this->enc($value));
			$this->file_print($this->quot);
			if ($end_column==0){
				$this->file_print(',');
			}
		}
	}

	public function writeLine($data){
		if ($this->write_type == self::W_HTML){
			$this->file_print("<tr>\n");
		}
		$end_i = count($this->tokens)-1;
		for ($i=0; $i<=$end_i; $i++){
			$fix = '';
			$funcname = '';
			$tabs = explode('	', $this->tokens[$i][1]);
			if (count($tabs)>=2){
				$fix = $tabs[1];
			}
			if (count($tabs)>=3){
				$funcname = $tabs[2];
			}
			$tmps = explode(':', $tabs[0]);
			$val = '';
			if ($tmps[0] == ''){
				$val = $fix;
			}else{
				$val = $data[$tmps[0]];
			}
			if ($funcname == 'strtolower'){
				$val = strtolower($val);
			}
			$class = '';
			if (count($tmps)>=2){
				$class = $tmps[1];
			}
			$endmode = 0;
			if ($i==$end_i){
				$endmode = 1;
			}
			$this->writeColumn($val,$endmode,$class);
		}
		if ($this->write_type == self::W_HTML){
			$this->file_print("</tr>\n");
		}else{
			$this->file_print("\n");
		}
		return '';
	}

	public function writeHeadLine(){
		if ($this->write_type == self::W_HTML){
			$this->file_print("<tr>\n");
		}
		$split_count = count(explode(':', $this->tokens[0][0]));
		$end_i = count($this->tokens)-1;
		for ($i=0; $i<=$end_i; $i++){
			$tmps = explode(':', $this->tokens[$i][0]);
			if ($i==$end_i){
				$this->writeHeadColumn($tmps[0],1);
			}else{
				$this->writeHeadColumn($tmps[0]);
			}
		}
		if ($split_count >= 2){
			if ($this->write_type == self::W_HTML){
				$this->file_print("</tr>\n");
			}
			$this->file_print("\n");
			for ($i=0; $i<=$end_i; $i++){
				$tabs = explode('	', $this->tokens[$i][0]);
				$tmps = explode(':', $tabs[0]);
				if (count($tmps>=2)){
					if ($i==$end_i){
						$this->writeHeadColumn($tmps[1],1);
					}else{
						$this->writeHeadColumn($tmps[1]);
					}
				}else{
					$this->writeHeadColumn('');
				}
			}
		}
		if ($this->write_type == self::W_HTML){
			$this->file_print("</tr>\n");
		}
		$this->file_print("\n");
		return '';
	}

	public function string($value, $nullstr='', $qout='"'){
		if ($value == ''){
			$rtn = $qout.$nullstr.$qout;
		}else{
			if ($qout == '"'){
				$rtn = $qout.str_replace('"', '""', $value).$qout;
			}else{
				$rtn = $qout.$value.$qout;
			}
		}
		return $rtn;
	}

}
?>
