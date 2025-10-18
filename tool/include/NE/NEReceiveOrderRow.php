<?php
// NEReceiveOrderRow
// Copyright (C) 2014 Tonary Management System, Inc. All Rights Reserved.

class NEReceiveOrderRow{
	public static $fields = array(
		array('name'=>'伝票番号', 'field'=>'receive_order_row_receive_order_id'),
		array('name'=>'受注番号', 'field'=>'receive_order_row_shop_cut_form_id'),
		array('name'=>'明細行番号', 'field'=>'receive_order_row_no'),
		array('name'=>'受注明細行番号', 'field'=>'receive_order_row_shop_row_no'),
		array('name'=>'商品コード', 'field'=>'receive_order_row_goods_id'),
		array('name'=>'商品名', 'field'=>'receive_order_row_goods_name'),
		array('name'=>'受注数', 'field'=>'receive_order_row_quantity'),
		array('name'=>'単価', 'field'=>'receive_order_row_unit_price'),
		array('name'=>'外国単価', 'field'=>'receive_order_row_foreign_unit_price'),
		array('name'=>'受注時原価', 'field'=>'receive_order_row_received_time_first_cost'),
		array('name'=>'掛率', 'field'=>'receive_order_row_wholesale_retail_ratio'),
		array('name'=>'小計金額', 'field'=>'receive_order_row_sub_total_price'),
		array('name'=>'商品OP', 'field'=>'receive_order_row_goods_option'),
		array('name'=>'キャンセルフラグ', 'field'=>'receive_order_row_cancel_flag'),
		array('name'=>'同梱元伝票番号', 'field'=>'receive_order_include_from_order_id'),
		array('name'=>'同梱元明細行番号', 'field'=>'receive_order_include_from_row_no'),
		array('name'=>'複数配送親伝票番号', 'field'=>'receive_order_row_multi_delivery_parent_order_id'),
		array('name'=>'引当数', 'field'=>'receive_order_row_stock_allocation_quantity'),
		array('name'=>'予約引当数', 'field'=>'receive_order_row_advance_order_stock_allocation_quantity'),
		array('name'=>'引当日', 'field'=>'receive_order_row_stock_allocation_date'),
		array('name'=>'受注時取扱区分', 'field'=>'receive_order_row_received_time_merchandise_id'),
		array('name'=>'受注時取扱名', 'field'=>'receive_order_row_received_time_merchandise_name'),
		array('name'=>'受注時商品区分', 'field'=>'receive_order_row_received_time_goods_type_id'),
		array('name'=>'受注時商品名', 'field'=>'receive_order_row_received_time_goods_type_name'),
		array('name'=>'良品返品数', 'field'=>'receive_order_row_returned_good_quantity'),
		array('name'=>'不良品返品数', 'field'=>'receive_order_row_returned_bad_quantity'),
		array('name'=>'返品事由区分', 'field'=>'receive_order_row_returned_reason_id'),
		array('name'=>'返品事由名', 'field'=>'receive_order_row_returned_reason_name'),
		array('name'=>'元受注明細行番号', 'field'=>'receive_order_row_org_row_no'),
		array('name'=>'削除フラグ', 'field'=>'receive_order_row_deleted_flag'),
		array('name'=>'作成日', 'field'=>'receive_order_row_creation_date'),
		array('name'=>'最終更新日', 'field'=>'receive_order_row_last_modified_date'),
		array('name'=>'最終更新日', 'field'=>'receive_order_row_last_modified_null_safe_date'),
		array('name'=>'受注伝票・受注明細の最終更新日', 'field'=>'receive_order_row_last_modified_newest_date'),
		array('name'=>'作成担当者ID', 'field'=>'receive_order_row_creator_id'),
		array('name'=>'作成担当者名', 'field'=>'receive_order_row_creator_name'),
		array('name'=>'最終更新者ID', 'field'=>'receive_order_row_last_modified_by_id'),
		array('name'=>'最終更新者ID', 'field'=>'receive_order_row_last_modified_by_null_safe_id'),
		array('name'=>'最終更新者名', 'field'=>'receive_order_row_last_modified_by_name'),
		array('name'=>'最終更新者名', 'field'=>'receive_order_row_last_modified_by_null_safe_name')
	);
}
