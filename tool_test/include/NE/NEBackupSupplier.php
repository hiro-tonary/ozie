<?php
// NEBackupSupplier
// Copyright (C) 2014 Tonary Management System, Inc. All Rights Reserved.

class NEBackupSupplier{

	public $target_name = '仕入先マスタ';
	public $api_url = '/api_v1_master_supplier/search';
	public $file_suffix = '_supplier.csv';
	public $deleted_flag = 'supplier_deleted_flag';
	public $query_sort = 'supplier_id-asc';
	public $query_fields = 'supplier_id,supplier_name,supplier_kana,supplier_zip_code,supplier_address1,supplier_address2,supplier_tel,supplier_fax,supplier_mail_address,supplier_post,supplier_pic_name,supplier_pic_kana,supplier_post_zip_code,supplier_post_address1,supplier_post_address2,supplier_post_tel,supplier_post_fax,supplier_post_mail_address,supplier_order_id,supplier_order_name,supplier_condition_quantity,supplier_condition_money,supplier_order_condition_id,supplier_order_condition_name,supplier_order_pending_date,supplier_pay_out_id,supplier_pay_out_name,supplier_closing_day,supplier_usance_date,supplier_order_invalid_flag,supplier_order_forbid_date,supplier_note,supplier_wholesale_retail_ratio,supplier_deleted_flag,supplier_creation_date,supplier_last_modified_date,supplier_last_modified_null_safe_date,supplier_creator_id,supplier_creator_name,supplier_last_modified_by_id,supplier_last_modified_by_null_safe_id,supplier_last_modified_by_name,supplier_last_modified_by_null_safe_name';
	public $tokens = array(
		array('sire_code','supplier_id')
		,array('sire_name','supplier_name')
		,array('sire_kana','supplier_kana')
		,array('yubin_bangou','supplier_zip_code')
		,array('jyusyo1','supplier_address1')
		,array('jyusyo2','supplier_address2')
		,array('denwa','supplier_tel')
		,array('fax','supplier_fax')
		,array('mail_adr','supplier_mail_address')
		,array('busyo','supplier_post')
		,array('tantou_name','supplier_pic_name')
		,array('tantou_kana','supplier_pic_kana')
		,array('busyo_jyusyo1','supplier_post_address1')
		,array('busyo_jyusyo2','supplier_post_address2')
		,array('busyo_denwa','supplier_post_tel')
		,array('busyo_fax','supplier_post_fax')
		,array('busyo_mail_adr','supplier_post_mail_address')
		,array('hachu_kbn','supplier_order_id')
		,array('su_jyoken','supplier_condition_quantity')
		,array('kin_jyoken','supplier_condition_money')
		,array('hachu_jyoken_kbn','supplier_order_condition_id')
		,array('hachu_horyu_bi','supplier_order_pending_date')
		,array('siharai_houhou_kbn','supplier_pay_out_id')
		,array('sime_bi','supplier_closing_day')
		,array('siharai_sight_bi','supplier_usance_date')
		,array('hachu_mukou_flg','supplier_order_invalid_flag')
		,array('hachu_kinsi_bi','supplier_order_forbid_date')
		,array('bikou','supplier_note')
	);

}
