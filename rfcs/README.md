# RFCs (Requests for Comments)

## 概要

開発提案・案件を管理します。要件定義から実装完了までのライフサイクル全体を記録します。

## ディレクトリ構造

```
rfcs/
├── planned/         # 計画中
├── in_progress/     # 実装中
└── completed/       # 完了
```

ファイルを移動することで状態を表現します。

## ファイル命名規則

### planned/
```
{カテゴリ}_{概要}.md

例:
- architecture_goods_api_refactoring.md
- feature_batch_retry_mechanism.md
- bugfix_parallel_processing_limit.md
```

### in_progress/ & completed/
```
{開始日}_{カテゴリ}_{概要}.md

例:
- 2025-10-17_feature_sale_price.md
- 2025-11-01_architecture_goods_api_refactoring.md
```

## カテゴリ

- `feature` - 新機能追加
- `architecture` - アーキテクチャ改善
- `refactoring` - リファクタリング
- `bugfix` - バグ修正
- `performance` - パフォーマンス改善
- `security` - セキュリティ改善

## RFCファイルのテンプレート

```markdown
# {タイトル}

## ステータス
- **状態**: 計画中 / 進行中 / 完了
- **開始日**: YYYY-MM-DD
- **完了予定日**: YYYY-MM-DD
- **完了日**: YYYY-MM-DD（完了時）
- **優先度**: 最高 / 高 / 中 / 低

## 概要
[1-2段落で提案の概要]

## 目的
- [目的1]
- [目的2]

## 背景・課題
[なぜこの提案が必要か]

## 提案内容

### フェーズ1: {名前}
- [ ] タスク1
- [ ] タスク2

### フェーズ2: {名前}
- [ ] タスク3

## 成果物
- 更新されるContract
- 新規作成されるクラス/テーブル

## 影響範囲
- 影響するクラス
- 影響するテーブル
- 影響するAPI

## 依存関係
- 前提となるRFC
- ブロッカー

## リスク
- リスク1とその対策

## 関連ドキュメント
- Contract
- 外部API仕様

## 学んだ教訓（完了時）
- 教訓1
- 教訓2

## 次のステップ（完了時）
- 次に検討すべきこと
```
