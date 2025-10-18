<?php
// Shops
// Copyright (C) 2013-2014 Tonary Management System, Inc. All Rights Reserved.

require_once(Config::$global_include_path.'Tonary_aShops.php');

class Shops extends Tonary_aShops{

	public $shops = array(
		array('id'=>1, 'name'=>'本店'),
		array('id'=>2, 'name'=>'楽天'),
		array('id'=>3, 'name'=>'Yahoo'),
		array('id'=>4, 'name'=>'アマゾン')
	);

}
