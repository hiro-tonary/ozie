# eca.tonary.biz サーバーへのデプロイ手順

## 前提条件

- サーバー: eca.tonary.biz (AlmaLinux)
- ドメイン: ozie.tonary.biz
- Docker, Docker Compose がインストール済み
- Nginx が稼働中
- Git がインストール済み
- 外部DBサーバー (192.168.0.n) が稼働中

## デプロイ手順

### 1. プロジェクトのクローン

```bash
# プロジェクトディレクトリ作成
sudo mkdir -p /var/www/containers/ozie
sudo chown $USER:$USER /var/www/containers/ozie

# リポジトリクローン
cd /var/www/containers/ozie
git clone <リポジトリURL> .
```

### 2. 設定ファイルの作成

#### 2.1 Config.php の作成

```bash
# 設定ディレクトリ作成
sudo mkdir -p /home/tonary/include/ozie
sudo chown $USER:$USER /home/tonary/include/ozie

# サンプルをコピーして編集
cp config.example/Config.php /home/tonary/include/ozie/Config.php
vi /home/tonary/include/ozie/Config.php
```

**編集する項目:**
```php
<?php
class Config {
    // パス設定（eca.tonary.biz用）
    public static $global_include_path = '/home/tonary/include/ozie/';
    public static $local_include_path = '/var/www/html/ozie/tool/include/';
    public static $tmpdir_path = '/home/tonary/tmp/ozie';
    public static $session_path = '/home/tonary/tmp/ozie/session';
    public static $customer_path = '/var/www/html/ozie/datas/ozie_customers.tsv';

    // Next Engine API設定
    public static $ne_server_url = 'https://api.next-engine.org';
    public static $ne_client_id = 'あなたのクライアントID';
    public static $ne_client_secret = 'あなたのクライアントシークレット';
    public static $ne_redirect_uri = 'https://ozie.tonary.biz/tool/';

    // データベース設定（外部DBサーバー: 192.168.0.n）
    public static $db_host = '192.168.0.XXX';  // 実際のIPアドレス
    public static $db_user = 'データベースユーザー名';
    public static $db_pass = 'データベースパスワード';
    public static $db_name = 'ozie';

    // その他
    public static $keep_days = 90;
}
```

#### 2.2 データベースの準備（外部DBサーバー側で実施）

**外部DBサーバー（192.168.0.n）で以下を実行:**

```sql
-- データベース作成
CREATE DATABASE IF NOT EXISTS ozie CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- ユーザー作成と権限付与
CREATE USER IF NOT EXISTS 'ozie_user'@'192.168.0.%' IDENTIFIED BY 'your_password';
GRANT ALL PRIVILEGES ON ozie.* TO 'ozie_user'@'192.168.0.%';
FLUSH PRIVILEGES;
```

**接続確認（eca.tonary.bizサーバーから）:**

```bash
# MySQLクライアントで接続テスト
mysql -h 192.168.0.XXX -u ozie_user -p ozie
```

#### 2.3 一時ディレクトリの作成

```bash
# Docker用一時ディレクトリ
sudo mkdir -p /home/tonary/tmp/ozie/{clear_csv,session}
sudo chown -R $USER:$USER /home/tonary/tmp/ozie
chmod -R 777 /home/tonary/tmp/ozie
```

### 3. Nginx設定

#### 3.1 設定ファイルの配置

```bash
# Nginx設定をコピー
sudo cp docker/nginx/ozie.tonary.biz.conf /etc/nginx/conf.d/

# 設定ファイルの文法チェック
sudo nginx -t
```

#### 3.2 SSL証明書の取得

```bash
# Let's Encryptで証明書取得
sudo certbot --nginx -d ozie.tonary.biz

# 自動更新の確認
sudo certbot renew --dry-run
```

#### 3.3 Nginxリロード

```bash
sudo systemctl reload nginx
```

### 4. Dockerコンテナの起動

```bash
cd /var/www/containers/ozie

# イメージのビルドと起動
docker compose up -d --build

# 起動確認
docker compose ps
docker compose logs -f
```

**期待される出力:**
```
NAME       IMAGE          STATUS         PORTS
ozie_web   ozie-web       Up X minutes   127.0.0.1:8003->80/tcp
```

### 5. 動作確認

#### 5.1 ローカルでの確認

```bash
# サーバー上で
curl http://127.0.0.1:8003/tool/
```

#### 5.2 外部からの確認

ブラウザで `https://ozie.tonary.biz/tool/` にアクセス

#### 5.3 Next Engine認証テスト

1. `https://ozie.tonary.biz/tool/` にアクセス
2. Next Engineログイン画面が表示されることを確認
3. 認証後、ツール画面が表示されることを確認

### 6. Next Engine アプリ設定

Next Engine管理画面で以下を設定：

- **リダイレクトURL**: `https://ozie.tonary.biz/tool/`
- **コールバックURL**: 同上

## 運用コマンド

### コンテナの操作

```bash
# 起動
docker compose up -d

# 停止
docker compose down

# 再起動
docker compose restart

# ログ確認
docker compose logs -f web

# コンテナに入る
docker compose exec web bash

# 外部DBサーバーに接続（ホストから直接）
mysql -h 192.168.0.XXX -u ozie_user -p ozie
```

### 更新手順

```bash
cd /var/www/containers/ozie

# コードを更新
git pull

# コンテナ再ビルド・再起動
docker compose up -d --build

# ログ確認
docker compose logs -f
```

### データベースバックアップ

```bash
# ダンプ作成（eca.tonary.bizから外部DBサーバーへ接続）
mysqldump -h 192.168.0.XXX -u ozie_user -p ozie > backup_$(date +%Y%m%d).sql

# リストア
mysql -h 192.168.0.XXX -u ozie_user -p ozie < backup_YYYYMMDD.sql
```

**注意**: データベースの定期バックアップは外部DBサーバー側で設定してください。

## トラブルシューティング

### ポート使用状況の確認

```bash
# Nginxプロキシ設定の確認
sudo grep -rh "proxy_pass.*127.0.0.1:" /etc/nginx/conf.d/ --include="*.conf" | sort

# リスニング中のポート確認
sudo ss -tlnp | grep 127.0.0.1

# Dockerコンテナのポート確認
docker ps --format "table {{.Names}}\t{{.Ports}}"
```

### ログ確認

```bash
# Nginxログ
sudo tail -f /var/log/nginx/ozie.tonary.biz.access.log
sudo tail -f /var/log/nginx/ozie.tonary.biz.error.log

# Dockerログ
docker compose logs -f web

# Apacheエラーログ（コンテナ内）
docker compose exec web tail -f /var/log/apache2/error.log

# PHPエラーログ（コンテナ内）
docker compose exec web tail -f /var/log/php_errors.log
```

### 権限エラーの場合

```bash
# 一時ディレクトリの権限確認・修正
sudo chown -R www-data:www-data /home/tonary/tmp/ozie
sudo chmod -R 777 /home/tonary/tmp/ozie
```

### コンテナが起動しない場合

```bash
# 詳細ログ確認
docker compose logs

# コンテナの状態確認
docker compose ps -a

# イメージの再ビルド
docker compose down
docker compose build --no-cache
docker compose up -d
```

### データベース接続エラーの場合

```bash
# 1. eca.tonary.bizから外部DBへの接続確認
mysql -h 192.168.0.XXX -u ozie_user -p ozie

# 2. ファイアウォール確認（eca.tonary.biz側）
sudo firewall-cmd --list-all

# 3. 外部DBサーバー側のファイアウォール確認
# 192.168.0.0/24 からの3306ポートアクセスが許可されているか確認

# 4. DBユーザー権限確認（外部DBサーバーで実行）
mysql -u root -p
> SELECT user, host FROM mysql.user WHERE user='ozie_user';
> SHOW GRANTS FOR 'ozie_user'@'192.168.0.%';

# 5. Config.phpの設定確認
cat /home/tonary/include/ozie/Config.php | grep db_
```

## セキュリティ注意事項

1. **Config.phpは必ずリポジトリ外に配置**
   - `/home/tonary/include/ozie/Config.php`
   - Gitで管理しない

2. **ポートは127.0.0.1にバインド**
   - 外部から直接Dockerコンテナにアクセスさせない
   - 必ずNginxリバースプロキシ経由

3. **SSL証明書の自動更新確認**
   ```bash
   sudo certbot renew --dry-run
   ```

4. **定期的な更新**
   - Dockerイメージの更新
   - セキュリティパッチの適用

## システム構成

### ネットワーク構成

```
インターネット
    ↓ HTTPS (443)
Nginx (ozie.tonary.biz)
    ↓ HTTP (127.0.0.1:8003)
Docker (Apache + PHP 7.4)
    ↓ MySQL (3306)
外部DBサーバー (192.168.0.n)
```

### 使用ポート

| サービス | ポート | バインド先 | 用途 |
|---------|--------|-----------|------|
| Apache (Docker) | 8003 | 127.0.0.1 | Webサーバー（Nginxからプロキシ） |
| MariaDB | 3306 | 192.168.0.n | 外部DBサーバー |

### ディレクトリ構成

```
/var/www/containers/ozie/            # アプリケーションコード（Gitリポジトリ）
├── tool/                            # 本番ツール
├── tool_test/                       # テストツール
├── docker/                          # Docker設定
│   ├── nginx/                       # Nginx設定ファイル
│   ├── apache/                      # Apache設定ファイル
│   ├── php/                         # PHP設定ファイル
│   ├── config/                      # Config.php の配置先（サンプル）
│   └── tmp/                         # 一時ファイル（ホスト側永続化）
└── docker-compose.yml               # Docker Compose設定

/home/tonary/include/ozie/Config.php # 実際の設定ファイル（リポジトリ外）
/home/tonary/tmp/ozie/               # 一時ファイル置き場
```
