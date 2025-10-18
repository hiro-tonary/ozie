<?php
// Tonary_ExecuteLog

require_once(dirname(__FILE__).'/Tonary.php');
require_once(dirname(__FILE__).'/Tonary_MySQL.php');

class Tonary_ExecuteLog{

	public $mysql = null;
	public $loginuser = null;

	public $data = null;
	private $ids = null;

	public function __construct($mysql=null,$loginuser=null,array $params=array()){
		$this->data = array();
		$this->ids = array();
		$this->data['php_id'] = $params['php_id'];
		$this->data['execute_id'] = $params['execute_id'];
		$this->data['execute_name'] = $params['execute_name'];
		$this->data['execute_datetime'] = date('Y-m-d H:i:s');
		if ($mysql == null){
			$this->mysql = new Tonary_MySQL();
		}else{
			$this->mysql = $mysql;
		}
		if ($loginuser == null){
			$this->loginuser = array();
		}else{
			$this->loginuser = $loginuser;
		}
	}
/*
	public function set_phpid($value){
		$this->data['php_id'] = $value;
	}

	public function set_execute_name($value){
		$this->data['execute_name'] = $value;
	}
*/
	public function add_log(array $ro){
		if (in_array($ro['receive_order_id'], $this->ids) == false){
			array_push($this->ids, $ro['receive_order_id']);
			$i = count($this->data['slip']);
			$this->data['slip'][$i]['receive_order_shop_id'] = $ro['receive_order_shop_id'];
			$this->data['slip'][$i]['receive_order_date'] = $ro['receive_order_date'];
			$this->data['slip'][$i]['receive_order_pic_name'] = $ro['receive_order_pic_name'];
			$this->data['slip'][$i]['receive_order_id'] = $ro['receive_order_id'];
			$this->data['slip'][$i]['receive_order_shop_cut_form_id'] = $ro['receive_order_shop_cut_form_id'];
			$this->data['slip'][$i]['receive_order_delivery_id'] = $ro['receive_order_delivery_id'];
			$this->data['slip'][$i]['receive_order_payment_method_id'] = $ro['receive_order_payment_method_id'];
		}
	}

	public function set_log_message($message, $execute_error_detail, $num=null){
		if ($num === null){
			$i = count($this->data['slip'])-1;
		}else{
			$i = $num;
		}
		if ($i < 0){
			$this->data['execute_message'] = $message;
			$this->data['execute_error_detail'] = $execute_error_detail;
		}else{
			$this->data['slip'][$i]['execute_message'] = $message;
			$this->data['slip'][$i]['execute_error_detail'] = $execute_error_detail;
		}
	}

	public function save_log(){
		$sql_e = '';
		$all_execute_message = '';
		$all_execute_error_detail = '';
		if ($this->data['execute_message'] <> ''){
			$all_execute_message = $this->data['execute_message'];
			$all_execute_error_detail = $this->data['execute_error_detail'];
		}
		if (count($this->data['slip']) == 0){
			if ($all_execute_message == '' and $all_execute_error_detail == ''){
				return;
			}
			$sql_e .= 'insert into execute_log (';
			$sql_e .= 'member_id';
			$sql_e .= ',pic_id';
			$sql_e .= ',pic_name';
			$sql_e .= ',php_id';
			$sql_e .= ',execute_id';
			$sql_e .= ',execute_name';
			$sql_e .= ',execute_datetime';
			$sql_e .= ',execute_message';
			$sql_e .= ',execute_error_detail';
			$sql_e .= ')values(';
			$sql_e .= $this->mysql->string(Config::$member_id);
			$sql_e .= ','.$this->mysql->numeric($this->loginuser['pic_id']);
			$sql_e .= ','.$this->mysql->string($this->loginuser['pic_name']);
			$sql_e .= ','.$this->mysql->string($this->data['php_id']);
			$sql_e .= ','.$this->mysql->string($this->data['execute_id']);
			$sql_e .= ','.$this->mysql->string($this->data['execute_name']);
			$sql_e .= ','.$this->mysql->string($this->data['execute_datetime']);
			$sql_e .= ','.$this->mysql->string($all_execute_message);
			$sql_e .= ','.$this->mysql->string($all_execute_error_detail);
			$sql_e .= ');'."\n";
		}
		for ($i=0; $i<count($this->data['slip']); $i++){
			$sql_e .= 'insert into execute_log (';
			$sql_e .= 'member_id';
			$sql_e .= ',pic_id';
			$sql_e .= ',pic_name';
			$sql_e .= ',php_id';
			$sql_e .= ',execute_id';
			$sql_e .= ',execute_name';
			$sql_e .= ',execute_datetime';

			$sql_e .= ',receive_order_shop_id';
			$sql_e .= ',receive_order_date';
			$sql_e .= ',receive_order_pic_name';

			$sql_e .= ',receive_order_delivery_id';
			$sql_e .= ',receive_order_payment_method_id';
			$sql_e .= ',execute_message';
			$sql_e .= ',execute_error_detail';

			$sql_e .= ',receive_order_id';
			$sql_e .= ',receive_order_shop_cut_form_id';
			$sql_e .= ')values(';
			$sql_e .= $this->mysql->string(Config::$member_id);
			$sql_e .= ','.$this->mysql->numeric($this->loginuser['pic_id']);
			$sql_e .= ','.$this->mysql->string($this->loginuser['pic_name']);
			$sql_e .= ','.$this->mysql->string($this->data['php_id']);
			$sql_e .= ','.$this->mysql->string($this->data['execute_id']);
			$sql_e .= ','.$this->mysql->string($this->data['execute_name']);
			$sql_e .= ','.$this->mysql->string($this->data['execute_datetime']);

			$sql_e .= ','.$this->mysql->numeric($this->data['slip'][$i]['receive_order_shop_id']);
			$sql_e .= ','.$this->mysql->string($this->data['slip'][$i]['receive_order_date']);
			$sql_e .= ','.$this->mysql->string($this->data['slip'][$i]['receive_order_pic_name']);

			$sql_e .= ','.$this->mysql->numeric($this->data['slip'][$i]['receive_order_delivery_id']);
			$sql_e .= ','.$this->mysql->numeric($this->data['slip'][$i]['receive_order_payment_method_id']);
			if ($all_execute_message == ''){
				$sql_e .= ','.$this->mysql->string($this->data['slip'][$i]['execute_message']);
				$sql_e .= ','.$this->mysql->string($this->data['slip'][$i]['execute_error_detail']);
			}else{
				$sql_e .= ','.$this->mysql->string($all_execute_message);
				$sql_e .= ','.$this->mysql->string($all_execute_error_detail);
			}

			$sql_e .= ','.$this->mysql->numeric($this->data['slip'][$i]['receive_order_id']);
			$sql_e .= ','.$this->mysql->string($this->data['slip'][$i]['receive_order_shop_cut_form_id']);
			$sql_e .= ');'."\n";
		}
		$debug_message .= $sql_e."\n";
		if ($this->mysql->multi_query($sql_e) <> ''){
			Tonary::write_accesslog(__FILE__ . ': error ' . $sql_e);
			die('sql error ' . $sql_e);
		}
	}

}
