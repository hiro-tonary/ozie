# クリア取込用売上げデータ Contract

このドキュメントは `specs/clear_csv/contract.yml` を補足し、実装コード（`tool/download/csv_for_clear.php` ほか）と一致する仕様を文章化したものです。

## 1. 目的と範囲

- Next Engine で認証済みのユーザーが、出荷日と得意先を指定して売上データを CSV 形式で取得する画面機能を対象とする。
- 対象ファイル
  - `tool/download/csv_for_clear.php`
  - `tool/download/tmp_download.php`（ダウンロード用 POST 受け口）
  - `tool/include/Customers.php`（得意先マスタ）
- 設定ファイル (`Config.php`) はリポジトリ外に配置され、`Config::$tmpdir_path` や `Config::$keep_days` などを参照する。

## 2. 入力仕様

### 2.1 フォームパラメータ

| パラメータ名 | 型 | 必須条件 | 説明 |
| --- | --- | --- | --- |
| `token` | string | 常に必須 | セッション内のトークン。`tmp_download.php` で CSRF チェックに使用。 |
| `mode` | string (`''` or `'exec'`) | ― | `'exec'` の場合に CSV 生成処理を実行。それ以外は画面描画のみ。 |
| `param_start_date` | date (`Y-m-d`) | `mode=exec` | 出荷日 From。初期値はアクセス日。 |
| `param_stop_date` | date (`Y-m-d`) | `mode=exec` | 出荷日 To。初期値はアクセス日。 |
| `param_customer_ids[]` | array<string> | `mode=exec` | 取得対象の得意先 ID。初期表示時は `Customers::$datas` の `id` 全件を選択済み。 |

### 2.2 バリデーション

- `mode=exec` の場合のみ入力チェックを実施。いずれかが欠けると画面上にエラーメッセージを表示して処理を終了する。
- `param_customer_ids` が空の場合：「得意先を選択してください。」
- `param_start_date` が空の場合：「出荷日(FROM)を指定してください。」
- `param_stop_date` が空の場合：「出荷日(TO)を指定してください。」

## 3. 処理フロー

1. **初期化**  
   `Tonary::session_start()` → `Tonary::write_accesslog()` → `Tonary_NE::login()` で Next Engine API とセッションを確立。ログインユーザー情報とトークンを取得。
2. **受注行の取得**  
   `mode=exec` かつ入力エラーがない場合、`/api_v1_receiveorder_row/search` を呼び出して出荷日範囲内の受注行を取得。以下の条件でフィルタする。
   - `receive_order_delivery_cut_form_id-neq ''`
   - 伝票・行の削除／キャンセルフラグが `1` でない
   - `receive_order_send_date` が指定期間内
   - `offset=0`, `limit=10000`, `sort=receive_order_id-asc`
   - 取得フィールドは `NEReceiveOrder::$fields` と `NEReceiveOrderRow::$fields` の結合結果
3. **商品マスタの取得**  
   受注行に含まれる商品コードを一意化し、`/api_v1_master_goods/search` を `goods_id-in` 付きで呼び出す。`NEBackupGoods::$query_fields` を利用。結果が失敗した場合はエラー表示。
4. **行データの整形**  
   - 決済方法名を正規化（例：`クレジットカード` → `クレジット`、`銀行振込` 等）。
   - `receive_order_shop_id` と `Customers::$datas` の `shop_id` を突き合わせ、`customer_code` / `tax_type` / `tax_method` を設定。
   - 配送方法が `80`/`98`/`99` の場合は楽天海外 (`000103`) として課税区分を上書き。
   - 商品情報は `goods_model_number` を `jan` に採用。商品コードが `nekutai` の行は単価を `-1650` に変更（元の単価に関わらず固定）。
5. **追加行の挿入**  
   伝票末尾で以下の条件に応じて行を追加し、既存の `receive_order_row_no` と同じ伝票に紐づける。
   - **ポイント値引き**：ポイント残高 (`receive_order_point_amount`) が正数かつ本店 (`shop_id=1`)。`customer_code=POINT`、単価は `-point_amount`。
   - **本店クーポン**：`other_amount == -500` かつ本店。`customer_code=000100`、単価 `-500`。
   - **ギフト**：`receive_order_gift_flag == 1`。本店は `charge_amount`、その他店舗は `other_amount` を単価に設定。
   - **Amazon 代引手数料**：`shop_id=4`, `receive_order_payment_method_id=1`, `charge_amount > 0`。`customer_code=DAIBIKI`。
   - **送料**：`receive_order_delivery_fee_amount > 0`。海外発送（楽天海外）なら免税・非課税で `送料　海外発送`、それ以外は内税・課税で `送料`。
6. **ソートとフィルタ**  
   追加行を含めて結合後、`receive_order_id` → `receive_order_row_no` → `receive_order_row_goods_id` で昇順ソート。`param_customer_ids` に含まれる `customer_code` の行だけを残す。
7. **CSV 生成**  
   対象行が 1 件以上の場合、`Tonary_FileMaker` で SJIS の CSV を生成し、`Config::$tmpdir_path.'clear_csv/'` に `clear_{Y_m_d_H_i_s}.csv` 形式で保存。  
   カラム構成はソースコード内のトークン配列（売上番号、売上日、得意先コード、…、原単価）に一致する。
8. **ダウンロード誘導**  
   JavaScript で `tmp_download.php` に自動 POST し、生成直後のファイルをダウンロードさせる。  
   `tmp_download.php` 側では POST かつ `token` 一致を検証し、`Config::$tmpdir_path` 配下のファイルを `readfile()` で返す。
9. **ログ & 過去ファイル表示**  
   `clear_csv` ディレクトリを走査し、`Config::$keep_days` より古いファイルを削除。残りのファイルを画面にボタン形式で表示して再ダウンロード可能にする。

## 4. 出力仕様

- **HTML**  
  - 実行時にはヒット件数と、ポイント／ギフト等の条件説明を表示。
  - `clear_csv` 配下のファイル一覧をボタンで表示。
- **CSV ファイル**  
  - 文字コード: SJIS-win  
  - 改行コード: CRLF  
  - ファイル名: `clear_YYYY_MM_DD_HH_MM_SS.csv`  
  - 列順: `Tonary_FileMaker` の `tokens` 配列（売上番号 → … → 原単価）と一致。空欄は空文字のまま出力。

## 5. エラーハンドリング

- 入力エラーまたは API エラー時は画面上にエラー文を表示し、CSV 生成処理は行わない。
- CSV の `fopen` に失敗した場合は `Tonary_NE::write_errorlog()` を出力して `die()`。
- 例外発生時は `Tonary_NE::write_errorlog()`（インスタンスあり）または `Tonary::write_errorlog()`（インスタンスなし）へ記録し、メッセージを表示して終了。

## 6. 得意先マスタ (`Customers::$datas`)

- 本店、楽天、Yahoo、Amazon など `shop_id` と `tax_type`/`tax_method` を保持。
- 特殊コード（POINT, GIFT, DELIVERY, DAIBIKI, QOUPON）も同ファイルで管理し、追加行生成時に利用。
- 現状 `Ippin (000106)` や `実店舗 (000105)` など、画面のチェックボックスに表示される値もこの配列で制御される。

## 7. セキュリティ

- レスポンスヘッダーで `X-Frame-Options: DENY`、`Cache-Control: no-store` 等を設定。
- 実行後のダウンロードは POST + CSRF トークンで保護。
- ワークディレクトリに書き込むファイルは `Config::$tmpdir_path/clear_csv/` に限定。

## 8. 運用上の注意点

- 画面内に「商品コード数の上限が 1000」との注意書きはあるが、コード上で強制制限は行っていない。運用で監視する。
- `limit=10000` で受注行を取得するため、それ以上の件数が存在する場合は仕様検討が必要。
- 得意先マスタを変更する場合は `Customers.php` を更新し、`tool_test/` での検証 → `tool/` 反映の手順を守る。
- 生成された CSV は `Config::$keep_days` 経過後に自動削除されるため、長期保存が必要な場合は外部へ退避する。
