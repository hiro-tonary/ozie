<?php
// NEBackupVo
// Copyright (C) 2014 Tonary Management System, Inc. All Rights Reserved.

class NEBackupVo{

	public $target_name = '商品ページマスタの項目選択肢情報';
	public $api_url = '/api_v1_master_pagebasevariationoroption/search';
	public $file_suffix = '_page_kihon_option.csv';
	public $deleted_flag = 'page_base_v_o_deleted_flag';
	public $query_sort = 'page_base_v_o_goods_id-asc,page_base_v_o_display_order-asc';
	public $query_fields = 'page_base_v_o_goods_id,page_base_v_o_option_category,page_base_v_o_option_name,page_base_v_o_horizontal_value,page_base_v_o_vertical_value,page_base_v_o_horizontal_name,page_base_v_o_vertical_name,page_base_v_o_type,page_base_v_o_display_order,page_base_v_o_deleted_flag,page_base_v_o_creation_date,page_base_v_o_last_modified_date,page_base_v_o_last_modified_null_safe_date,page_base_v_o_creator_id,page_base_v_o_creator_name,page_base_v_o_last_modified_by_id,page_base_v_o_last_modified_by_null_safe_id,page_base_v_o_last_modified_by_name,page_base_v_o_last_modified_by_null_safe_name';
	public $tokens = array(
		array('商品コード','page_base_v_o_goods_id')
		,array('選択肢タイプ','page_base_v_o_type')
		,array('Select/Checkbox用項目名','page_base_v_o_option_category')
		,array('Select/Checkbox用選択肢','page_base_v_o_option_name')
		,array('項目選択肢別在庫用横軸選択肢','page_base_v_o_horizontal_name')
		,array('項目選択肢別在庫用横軸選択肢子番号','page_base_v_o_horizontal_value')
		,array('項目選択肢別在庫用縦軸選択肢','page_base_v_o_vertical_name')
		,array('項目選択肢別在庫用縦軸選択肢子番号','page_base_v_o_vertical_value')
	);

}
