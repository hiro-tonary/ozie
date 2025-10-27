# ozie - Next Engine Integration Tool

Next Engine連携ツール（受注管理、商品管理、会計CSV出力）

## プロジェクト概要

PHP 7.4ベースのNext Engine統合ツール。受注処理、商品管理、会計ソフト（Clear）向けCSV出力などを提供します。

- **本番環境**: ozie.tonary.biz (eca.tonary.biz サーバー上でDocker運用)
- **レガシー環境**: tonary.sakura.ne.jp (FreeBSD/Apache/PHP)

## クイックスタート

### ローカル開発環境

```bash
# リポジトリクローン
git clone <repository-url>
cd ozie

# ローカル開発用にDBコンテナを有効化
# docker-compose.yml の db サービスのコメントを外す

# Docker環境起動
docker compose up -d

# ブラウザでアクセス
open http://localhost:8003/tool/
```

**注意**: 本番環境（eca.tonary.biz）では外部DBサーバー（192.168.0.n）を使用します。

### サーバーデプロイ

詳細は [docker/DEPLOYMENT.md](docker/DEPLOYMENT.md) を参照してください。

## 主要機能

- **受注管理** (`ne_receive_order_list.php`): Next Engineの受注一覧表示
- **商品管理** (`ne_goods_list.php`): 商品マスタ管理
- **Clear向けCSV出力** (`download/csv_for_clear.php`): 会計ソフト用売上データ生成
- **ルーチン処理** (`ne_routine_exec.php`): 受注自動処理バッチ
- **顧客マスタ編集** (`download/customers_edit.php`): TSV形式の顧客データ管理

## ドキュメント

- [CLAUDE.md](CLAUDE.md) - 開発ガイドライン、アーキテクチャ説明
- [docker/DEPLOYMENT.md](docker/DEPLOYMENT.md) - サーバーデプロイ手順
- [rfcs/](rfcs/) - 開発提案・RFC
- [specs/](specs/) - システム仕様・契約

## 技術スタック

- **PHP**: 7.4.33
- **Webサーバー**: Apache 2.4
- **データベース**: MariaDB/MySQL (外部DBサーバー: 192.168.0.n)
- **インフラ**: Docker Compose + Nginx (リバースプロキシ)

## プロジェクト構造

```
├── tool/                   # 本番環境コード
├── tool_test/             # テスト環境コード
├── datas/                 # データファイル
├── config.example/        # 設定サンプル
├── docker/                # Docker関連設定
│   ├── DEPLOYMENT.md     # デプロイ手順
│   ├── nginx/            # Nginx設定
│   └── php/              # PHP/Apache設定
├── rfcs/                  # 開発提案
└── specs/                 # システム仕様
```

## 開発コマンド

### Docker操作

```bash
# 起動
docker compose up -d

# ログ確認
docker compose logs -f web

# コンテナに入る
docker compose exec web bash

# 停止
docker compose down
```

### ポート管理（eca.tonary.biz サーバー用）

複数のDockerプロジェクトを運用する際のポート使用状況確認：

```bash
# Nginx proxy_pass 設定の確認
sudo grep -rh "proxy_pass.*127.0.0.1:" /etc/nginx/conf.d/ --include="*.conf" | sort

# 使用中ポート番号のみ抽出
sudo grep -rh "proxy_pass.*127.0.0.1:" /etc/nginx/conf.d/ --include="*.conf" | \
  grep -oP '127\.0\.0\.1:\K[0-9]+' | sort -n | uniq

# リスニング中のポート確認
sudo ss -tlnp | grep 127.0.0.1 | grep LISTEN

# Dockerコンテナとポートマッピング
docker ps --format "table {{.Names}}\t{{.Ports}}"
```

**現在の使用ポート（eca.tonary.biz）:**
```
Django/バックエンド: 8000, 8001, 8002, 8008, 8011
React/フロントエンド: 3000, 3001
ozie: 8003 (本プロジェクト)
```

## 環境変数・設定

設定ファイルは**リポジトリ外**に配置（セキュリティのため）:

- **本番**: `/home/tonary/include/ozie/Config.php`
- **サンプル**: `config.example/Config_server.php`

主な設定項目:
- Next Engine API認証情報
- データベース接続情報
- ファイルパス設定

## ライセンス

非公開プロジェクト

## お問い合わせ

開発者: Tonary
