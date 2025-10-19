# クリア取込用売上げデータ ダウンロード仕様

## 概要
- 本ドキュメントは、`tool/download/csv_for_clear.php` を中心とした「クリア取込用売上げデータをダウンロードする」機能について整理したものです。
- 実運用では Next Engine 認証済みユーザーがツールへアクセスし、出荷日と得意先を指定して売上データを CSV 形式で取得します。
- Config 本体は Web 公開ディレクトリ外に設置され、リポジトリ内にはサンプル設定として `config.example/Config.php`・`Config_test.php` を同梱しています。

## 処理フロー
1. `tool/download/csv_for_clear.php:26`  
   `Tonary::session_start()` と `Tonary::write_accesslog()` を実行後、`Tonary_NE` を初期化して Next Engine API にログイン。セッション内にアクセストークン・ユーザー情報を保持します。
2. `tool/download/csv_for_clear.php:63` 以降  
   画面初期表示時は当日の日付と `Customers::$datas` に定義された全得意先が選択された状態で描画します。フォーム送信時には POST 値から以下の条件を組み立てます。
   - `param_start_date` / `param_stop_date`：出荷日範囲（`Y-m-d` に正規化）
   - `param_customer_ids[]`：得意先 ID 複数選択（初回は全件）
   - `mode`：`exec` の場合に CSV 生成処理を実行
3. 実行ボタン押下時 (`mode=exec`)  
   Next Engine API から条件に合致する受注データを取得し、`Tonary_FileMaker` を利用して SJIS の CSV を生成。出力先は `Config::$tmpdir_path.'clear_csv/'` 配下です。商品コード数が 1000 件を超える場合は警告を表示します。
4. 生成されたファイルは `tool/download/tmp_download.php`（本番系は `tool/tmp_download.php` と同仕様）経由でダウンロードします。POST 時にセッショントークンをチェックし、不正アクセスを防止します。
5. 画面下部の履歴では、`clear_csv` ディレクトリに存在する過去ファイルを再ダウンロードできます。

## Contract バージョン
- **v1**（現行運用）: `specs/clear_csv/v1/contract.{yml,md}`
- **v2**（計画中: 得意先編集対応）: `specs/clear_csv/v2/contract.{yml,md}`

## 関連モジュール
- `tool/include/Tonary.php`  
  セッション操作、入力値取得、ログ出力、文字列整形などの共通ユーティリティ。
- `tool/include/Tonary_NE.php`  
  Next Engine API クライアントラッパー。ログイン処理とトークン管理を実装。
- `tool/include/Tonary_FileMaker.php`  
  CSV 出力のためのユーティリティ。SJIS エンコーディングとカンマ区切りの書き出しを担います。
- `tool/include/Customers.php`  
  得意先の固定リストを保持。`csv_for_clear.php` でチェックボックスとして利用します。

## 設定ファイル
- 本番設定ファイルは Web 公開ディレクトリ外（例：`/home/tonary/include/ozie/Config.php`）に配置します。
- リポジトリ内にはサンプルとして `config.example/Config.php`（本番想定）と `config.example/Config_test.php`（サンドボックス想定）を用意しており、これらをコピーして環境に合わせて修正します。
- 主要な設定項目
  - Next Engine API 認証情報：`$ne_client_id`, `$ne_client_secret`, `$ne_server_url`
  - ローカルファイルパス：`$global_include_path`, `$local_include_path`, `$tmpdir_path`, `$session_path`
  - DB 接続情報：`$db_host`, `$db_user`, `$db_pass`, `$db_dbname`
  - スタイルシート：`$local_css`

## 運用上の注意
- `Config::$tmpdir_path` 配下の `clear_csv/` は Web サーバーユーザーが書き込み可能である必要があります。定期的に不要ファイルを削除する運用を推奨します。
- Next Engine API のトークン有効期限切れ時には再ログインが必要です。ログインに失敗した場合、`Tonary_NE` がエラーメッセージを出力して処理を停止します。
- `Customers::$datas` に変更がある場合は、該当ファイルを更新してからツールを再利用してください。
