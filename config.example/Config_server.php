<?php
// eca.tonary.biz (ozie.tonary.biz) 環境用設定
// デプロイ時に /home/tonary/include/ozie/Config.php としてコピーして使用

class Config{
	public static $member_id = 'OZ';

	public static $test = false;
	public static $keep_days = 90;  // バックアップ保持日数
	public static $use_page = false;

	public static $page_url_prefix = '';

	// ★重要: Next Engineのリダイレクト先URL
	public static $uri_dir = 'https://ozie.tonary.biz/tool/';

	public static $local_css = '/tool/style.css';

	// ★パス設定（Docker環境用）
	// グローバルインクルードパス（Config.phpが配置される場所）
	public static $global_include_path = '/home/tonary/include/ozie/';

	// ローカルインクルードパス（Docker内のアプリケーションパス）
	public static $local_include_path = '/var/www/html/ozie/tool/include/';

	// 一時ディレクトリ（ホスト側で永続化）
	public static $tmpdir_path = '/home/tonary/tmp/ozie';
	public static $session_path = '/home/tonary/tmp/ozie/session';

	// 顧客マスタファイル
	public static $customer_path = '/var/www/html/ozie/datas/ozie_customers.tsv';

	// ★Next Engine API設定
	// 本番環境: https://api.next-engine.org
	// サンドボックス: https://api.next-engine.com
	public static $ne_server_url = 'https://api.next-engine.org';

	// ★以下は実際の値に置き換えてください
	public static $ne_client_id = 'あなたのクライアントID';
	public static $ne_client_secret = 'あなたのクライアントシークレット';

	// ★データベース設定（外部DBサーバー）
	// eca.tonary.biz から 192.168.0.n:3306 に接続
	public static $db_host = '192.168.0.n';  // 実際のIPアドレスに置き換える
	public static $db_user = 'データベースユーザー名';
	public static $db_pass = 'データベースパスワード';
	public static $db_dbname = 'ozie';
}
