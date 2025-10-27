<?php
//認証情報
class Config{
	public static $member_id = 'OZ';

	public static $test = false;
	public static $keep_days = 7;
	public static $use_page = false;

	public static $page_url_prefix = '';
	public static $uri_dir = 'https://tonary.sakura.ne.jp/ozie/tool/';

	public static $local_css = '/ozie/style.css';

	public static $global_include_path = '/home/tonary/www/ozie/tool/include/';
	public static $local_include_path = '/home/tonary/www/ozie/tool/include/';

	public static $tmpdir_path = '/home/tonary/tmp/ozie/tool/';
	public static $session_path = '/home/tonary/tmp/ozie/tool/session';
	public static $customer_path = '/home/tonary/www/ozie/datas/customers.tsv';

	public static $ne_server_url = 'https://ne02.next-engine.com/';
	public static $ne_client_id = 'xxx';
	public static $ne_client_secret = 'xxx';

	public static $db_host = 'db';
	public static $db_user = 'u';
	public static $db_pass = 'p';
	public static $db_dbname = 'o';