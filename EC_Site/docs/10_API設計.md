# API設計

EC Site（ECサイト構築プロジェクト）

---

# 文書管理情報

| 項目 | 内容 |
| --- | --- |
| システム名 | EC Site |
| 文書名 | API設計 |
| 文書番号 | EC-010 |
| 作成者 | Nguyen Minh Tri |
| 作成日 | 2026/07/13 |
| バージョン | 1.3 |
| ステータス | Draft |

---

# 改訂履歴

| Version | 日付 | 作成者 | 内容 |
| --- | --- | --- | --- |
| 1.0 | 2026/07/13 | Nguyen Minh Tri | 初版作成 |
| 1.1 | 2026/07/14 | Nguyen Minh Tri | E006の一部ケースをE007/E011へ分離、Guest/Public表記統一、audit_logs記述の整合。 |
| 1.2 | 2026/07/14 | Nguyen Minh Tri | 6章のAPI詳細を16件→全33件（API-001〜033）に拡充。コーディング開始前の完成度100%化のため。 |
| 1.3 | 2026/07/17 | Nguyen Minh Tri | 6.2節API-002の主なエラーから誤記のE009を削除、6.11節のAttendanceService言及をProject 01由来と明記、6.15節の詳細設計書参照を6章→5.2節に訂正。 |

---

# 目次

1. 本書の目的
2. API設計方針
3. 共通仕様
4. 認証・認可仕様
5. API一覧
6. API詳細
7. エラー仕様
8. セキュリティ仕様
9. Webhook仕様（Stripe）
10. トレーサビリティ
11. まとめ

---

# 1. 本書の目的

本書はEC SiteのREST API仕様を定義する。`07_機能一覧.md`のFUNC-IDと対応させ、Request/Response、バリデーション、業務ルール、エラーコードを明確化する。本書は`12_詳細設計書.md`（Controller/Service実装）および`15_単体試験仕様書.md`〜`17_システム試験仕様書.md`の基準とする。

---

# 2. API設計方針

| 方針ID | 方針 | 内容 |
| --- | --- | --- |
| API-POL-001 | REST | Resource指向のURLを使用する。 |
| API-POL-002 | JSON | Request / Responseは原則JSON形式とする。 |
| API-POL-003 | Stateless | Laravel Sanctumトークンにより認証状態を判断する（セッション非依存、NFR-009水平スケール対応）。 |
| API-POL-004 | 権限制御 | Guest / Customer / Adminごとにアクセス可否を制御する。 |
| API-POL-005 | Traceability | API IDはFUNC / REQ / UC / SCRと対応付ける。 |
| API-POL-006 | Audit | 在庫・注文・クーポンに関わる重要操作は記録する。現状は在庫調整のみ`inventory_logs`（実テーブル）、注文ステータス変更・クーポン発行編集はアプリケーションログに記録する。専用の`audit_logs`テーブルは未実装で、将来対応として検討中（`14_セキュリティ設計.md`14.1節と一致させている）。 |
| API-POL-007 | Validation | 入力値はLaravel FormRequestで検証する。 |
| API-POL-008 | 命名規約の継続性 | Project 01（HR & Attendance System）と同じ規約（Base URLに`/v1`を付けない、フィールド名snake_case）を踏襲する。理由は10章の付記を参照。 |

---

# 3. 共通仕様

## 3.1 Base URL

| 環境 | Base URL |
| --- | --- |
| Local | `http://localhost/api` |
| Development | `https://dev.example.com/api` |
| Production | `https://example.com/api` |

## 3.2 Header

| Header | 必須 | 内容 |
| --- | --- | --- |
| Content-Type | POST / PUT / PATCH時必須 | `application/json`（画像アップロードのみ`multipart/form-data`） |
| Accept | 必須 | `application/json` |
| Authorization | 認証API以外必須 | `Bearer {access_token}` |
| Stripe-Signature | Webhook APIのみ必須 | Stripeが付与する署名（9章） |

## 3.3 共通Response形式

成功時:

```json
{
  "success": true,
  "message": "OK",
  "data": {}
}
```

失敗時:

```json
{
  "success": false,
  "error": {
    "code": "E004",
    "message": "在庫が不足しています。",
    "details": {}
  }
}
```

## 3.4 HTTP Status

| Status | 用途 |
| --- | --- |
| 200 | 正常取得・正常更新 |
| 201 | 作成成功 |
| 204 | 削除成功 |
| 400 | Webhook署名検証失敗等の入力形式エラー |
| 401 | 未認証 |
| 402 | 決済失敗 |
| 403 | 権限エラー |
| 404 | データなし |
| 409 | 在庫不足・クーポンエラー・ステータス不整合 |
| 422 | 入力バリデーションエラー |
| 500 | サーバー・DBエラー |

## 3.5 日付・金額形式

| 種別 | 形式 | 例 |
| --- | --- | --- |
| Date | `YYYY-MM-DD` | `2026-07-13` |
| DateTime | ISO 8601 | `2026-07-13T09:00:00+09:00` |
| 金額 | 整数（JPY、小数なし） | `1980` |

---

# 4. 認証・認可仕様

## 4.1 認証方式

| 項目 | 内容 |
| --- | --- |
| Login ID | `email` |
| Password | `password` |
| Token期限 | 24時間（Customer向けは利便性を優先しProject 01の8時間より長め） |
| Password保存 | `password_hash`へハッシュ化して保存 |

## 4.2 Role別アクセス方針

| Role | 主な操作 |
| --- | --- |
| Guest | 商品閲覧、会員登録、ログイン（未認証でアクセス可。以下5章・6章の権限列でも同じ意味で「Guest」と表記する） |
| Customer | カート、注文、決済、注文履歴、レビュー投稿 |
| Admin | 商品/在庫/注文/クーポン管理、レポート閲覧 |
| System | Stripe Webhook受信（API-017）等、人間ではなく外部サービスからの呼び出し。`03_ユースケース.md`のアクター定義と一致させている。 |

---

# 5. API一覧

| API ID | Method | Endpoint | API名 | 権限 | 関連FUNC | 関連REQ |
| --- | --- | --- | --- | --- | --- | --- |
| API-001 | POST | `/auth/register` | 会員登録 | Guest | FUNC-001 | REQ-001 |
| API-002 | POST | `/auth/login` | ログイン | Guest | FUNC-002 | REQ-002 |
| API-003 | POST | `/auth/logout` | ログアウト | Customer / Admin | FUNC-003 | REQ-003 |
| API-004 | GET | `/auth/me` | ログインユーザー取得 | Customer / Admin | FUNC-003 | REQ-003 |
| API-005 | PATCH | `/auth/password` | パスワード変更 | Customer / Admin | FUNC-004 | REQ-017 |
| API-006 | GET | `/products` | 商品一覧取得（検索・絞込） | Guest | FUNC-005 / 007 | REQ-005 / 007 |
| API-007 | GET | `/products/{id}` | 商品詳細取得 | Guest | FUNC-006 | REQ-006 |
| API-008 | GET | `/categories` | カテゴリ一覧取得 | Guest | - | - |
| API-009 | GET | `/cart` | カート取得 | Customer | FUNC-008 | REQ-008 |
| API-010 | POST | `/cart/items` | カート追加 | Customer | FUNC-008 | REQ-008 |
| API-011 | PATCH | `/cart/items/{id}` | カート数量変更 | Customer | FUNC-009 | REQ-009 |
| API-012 | DELETE | `/cart/items/{id}` | カート削除 | Customer | FUNC-009 | REQ-009 |
| API-013 | GET / POST / PUT | `/addresses` | 配送先住所管理 | Customer | FUNC-010 | REQ-010 |
| API-014 | POST | `/coupons/validate` | クーポン検証 | Customer | FUNC-011 | REQ-011 |
| API-015 | POST | `/orders` | 注文確定 | Customer | FUNC-012 / 016 | REQ-012 |
| API-016 | POST | `/orders/{id}/retry-payment` | 決済再試行 | Customer | FUNC-013 | REQ-013 |
| API-017 | POST | `/webhooks/stripe` | Stripe Webhook受信 | System | FUNC-013 / 017 / 018 | - |
| API-018 | GET | `/orders` | 注文履歴一覧取得 | Customer | FUNC-014 | REQ-014 |
| API-019 | GET | `/orders/{id}` | 注文詳細取得 | Customer | FUNC-015 | REQ-015 |
| API-020 | POST | `/reviews` | レビュー投稿 | Customer | FUNC-020 | REQ-016 |
| API-021 | GET | `/products/{id}/reviews` | 商品レビュー一覧取得 | Guest | - | - |
| API-022 | POST / PUT / PATCH | `/admin/products` | 商品管理 | Admin | FUNC-021 | REQ-018 |
| API-023 | POST / DELETE | `/admin/products/{id}/images`（DELETEは`/admin/products/{id}/images/{imageId}`） | 商品画像管理 | Admin | FUNC-024 | REQ-021 |
| API-024 | GET / POST / PUT / PATCH | `/admin/categories` | カテゴリ管理 | Admin | FUNC-022 | REQ-019 |
| API-025 | POST / PUT | `/admin/products/{id}/variants` | バリエーション管理 | Admin | FUNC-023 | REQ-020 |
| API-026 | GET | `/admin/inventories` | 在庫一覧取得 | Admin | FUNC-019 | REQ-022 |
| API-027 | PATCH | `/admin/inventories/{variantId}/adjust` | 在庫調整 | Admin | FUNC-019 | REQ-022 |
| API-028 | GET | `/admin/orders` | 注文一覧取得 | Admin | FUNC-025 | REQ-023 |
| API-029 | PATCH | `/admin/orders/{id}/status` | 注文ステータス変更 | Admin | FUNC-025 | REQ-023 |
| API-030 | POST | `/admin/orders/{id}/shipment` | 出荷登録 | Admin | FUNC-026 | REQ-024 |
| API-031 | GET / POST / PUT / PATCH | `/admin/coupons` | クーポン管理 | Admin | FUNC-027 | REQ-025 |
| API-032 | GET | `/admin/reports/sales` | 売上レポート取得 | Admin | FUNC-028 | REQ-026 |
| API-033 | GET | `/admin/reports/inventory` | 在庫僅少レポート取得 | Admin | FUNC-028 | REQ-026 |

---

# 6. API詳細

初版（v1.0）では33 APIのうち16件のみ詳細化していたが、コーディング開始前の完成度100%化のため残り17件を追加した（改訂履歴参照）。API-ID順に記載する。

## 6.1 API-001 会員登録

| 項目 | 内容 |
| --- | --- |
| Method | POST |
| Endpoint | `/auth/register` |
| 権限 | Guest |
| 関連テーブル | users |
| 主なエラー | E003 |

Request:

```json
{
  "name": "山田太郎",
  "email": "customer2@example.com",
  "password": "password123",
  "password_confirmation": "password123"
}
```

Response（201 Created）:

```json
{
  "success": true,
  "message": "会員登録が完了しました。",
  "data": {
    "access_token": "token",
    "user": { "id": 5, "name": "山田太郎", "email": "customer2@example.com", "role": "customer" }
  }
}
```

Validation: `name` required/max:100、`email` required/email/max:255/unique、`password` required/min:8/max:20/confirmed。登録成功時はAPI-002（ログイン）と同様に即座にアクセストークンを発行する（会員登録直後の再ログインを不要にするため）。

---

## 6.2 API-002 ログイン

| 項目 | 内容 |
| --- | --- |
| Method | POST |
| Endpoint | `/auth/login` |
| 権限 | Guest |
| 関連テーブル | users |
| 主なエラー | E001 / E003 |

Request:

```json
{
  "email": "customer1@example.com",
  "password": "password123"
}
```

Response:

```json
{
  "success": true,
  "message": "ログインしました。",
  "data": {
    "access_token": "token",
    "user": {
      "id": 2,
      "name": "山田太郎",
      "email": "customer1@example.com",
      "role": "customer"
    }
  }
}
```

Validation: `email` required/email/max:255、`password` required/min:8/max:20

---

## 6.3 API-003 ログアウト

| 項目 | 内容 |
| --- | --- |
| Method | POST |
| Endpoint | `/auth/logout` |
| 権限 | Customer / Admin |
| 関連テーブル | - |
| 主なエラー | E010 |

Response:

```json
{ "success": true, "message": "ログアウトしました。" }
```

処理: 現在のリクエストに付与されたSanctumトークンを失効させる。以後同じトークンでAPIへアクセスするとE010（未認証）になる。

---

## 6.4 API-004 ログインユーザー取得

| 項目 | 内容 |
| --- | --- |
| Method | GET |
| Endpoint | `/auth/me` |
| 権限 | Customer / Admin |
| 関連テーブル | users |
| 主なエラー | E010 |

Response:

```json
{
  "success": true,
  "message": "OK",
  "data": { "id": 2, "name": "山田太郎", "email": "customer1@example.com", "role": "customer" }
}
```

用途: フロントエンドがページ読込時にログイン状態・表示名を復元するために呼び出す（`06_画面設計.md`3.2節のHeader表示等）。

---

## 6.5 API-005 パスワード変更

| 項目 | 内容 |
| --- | --- |
| Method | PATCH |
| Endpoint | `/auth/password` |
| 権限 | Customer / Admin |
| 関連テーブル | users |
| 主なエラー | E003 |

Request:

```json
{
  "current_password": "password123",
  "new_password": "newpassword456",
  "new_password_confirmation": "newpassword456"
}
```

Response:

```json
{ "success": true, "message": "パスワードを変更しました。" }
```

Validation: `current_password` required（`password_hash`と一致しなければE003）、`new_password` required/min:8/max:20/confirmed。

---

## 6.6 API-006 商品一覧取得

| 項目 | 内容 |
| --- | --- |
| Method | GET |
| Endpoint | `/products` |
| 権限 | Guest |
| 関連テーブル | products / product_variants / product_images |
| 主なエラー | - |

Query:

| 項目 | 必須 | 内容 |
| --- | --- | --- |
| keyword | No | 商品名の部分一致検索 |
| category_id | No | カテゴリ絞込 |
| page | No | ページ番号（デフォルト1） |

Response Data（抜粋）:

```json
{
  "data": [
    {
      "id": 10,
      "name": "コットンTシャツ",
      "category": { "id": 2, "name": "トップス" },
      "price_min": 2980,
      "price_max": 3480,
      "primary_image_url": "https://s3.../thumb.jpg"
    }
  ],
  "meta": { "current_page": 1, "last_page": 5, "total": 96 }
}
```

`price_min` / `price_max`は`product_variants.price`のMIN/MAXであり、テーブルには存在しない集計値である（一覧表示専用、詳細は`12_詳細設計書.md`参照）。

---

## 6.7 API-007 商品詳細取得

| 項目 | 内容 |
| --- | --- |
| Method | GET |
| Endpoint | `/products/{id}` |
| 権限 | Guest |
| 関連テーブル | products / product_variants / product_images / reviews |
| 主なエラー | E007 |

Response Data（抜粋）:

```json
{
  "id": 10,
  "name": "コットンTシャツ",
  "description": "肌触りの良い綿100%Tシャツ",
  "tax_category": "standard",
  "category": { "id": 2, "name": "トップス" },
  "variants": [
    { "id": 105, "sku": "TSHIRT-M-RED", "size": "M", "color": "Red", "price": 2980, "quantity_available": 12 }
  ],
  "images": [{ "url": "https://s3.../1.jpg", "is_primary": true }],
  "review_summary": { "average": 4.3, "count": 18 }
}
```

対象商品が存在しない、またはstatus=inactiveの場合はE007。`quantity_available`は`inventories`からJOINして返す集計値であり、`products`/`product_variants`自体には存在しない。

---

## 6.8 API-008 カテゴリ一覧取得

| 項目 | 内容 |
| --- | --- |
| Method | GET |
| Endpoint | `/categories` |
| 権限 | Guest |
| 関連テーブル | categories |
| 主なエラー | - |

Response Data: status=activeのカテゴリを`parent_category_id`による階層構造（親→子）で返す。商品一覧画面の絞込メニュー（SCR-003）と、Admin商品登録画面のカテゴリ選択肢（SCR-014）の両方から参照される共通APIである。

---

## 6.9 API-009 カート取得

| 項目 | 内容 |
| --- | --- |
| Method | GET |
| Endpoint | `/cart` |
| 権限 | Customer |
| 関連テーブル | carts / cart_items |
| 主なエラー | E010 |

Response Data: ログインユーザーの現在のactiveカートの明細一覧（各行に`product_variants`情報・単価・行小計を含む）。activeカートが存在しない場合はエラーにせず空配列を返す（`items: []`）。

---

## 6.10 API-010 カート追加

| 項目 | 内容 |
| --- | --- |
| Method | POST |
| Endpoint | `/cart/items` |
| 権限 | Customer |
| 関連テーブル | carts / cart_items / inventories（参照のみ） |
| 主なエラー | E003 / E004 / E010 |

Request:

```json
{
  "variant_id": 105,
  "quantity": 2
}
```

Response:

```json
{
  "success": true,
  "message": "カートに追加しました。",
  "data": {
    "cart_item_id": 8,
    "variant_id": 105,
    "quantity": 2
  }
}
```

Business Rule:

| ルール | 内容 |
| --- | --- |
| BR-INV-002 | この時点では在庫を変更しない（`quantity_available`の確認のみ、E004は「表示上の警告」目的）。 |

---

## 6.11 API-011 カート数量変更

| 項目 | 内容 |
| --- | --- |
| Method | PATCH |
| Endpoint | `/cart/items/{id}` |
| 権限 | Customer |
| 関連テーブル | cart_items |
| 主なエラー | E003 / E007 |

Request:

```json
{ "quantity": 3 }
```

Response: 更新後の`cart_item`を返す。Service層で対象`cart_items`行の`cart.user_id`がログインユーザーと一致するか確認し、他会員の`cart_item`を指定した場合はE007を返す（本書4.2節のスコープ制御方針。Project 01（HR & Attendance System）のAttendanceService::searchで用いたIDOR対策パターンと同様）。Validation: `quantity` required/integer/min:1。

---

## 6.12 API-012 カート削除

| 項目 | 内容 |
| --- | --- |
| Method | DELETE |
| Endpoint | `/cart/items/{id}` |
| 権限 | Customer |
| 関連テーブル | cart_items |
| 主なエラー | E007 |

Response:

```json
{ "success": true, "message": "カートから削除しました。" }
```

API-011と同じ所有権チェックを行い、他会員の`cart_item`を指定した場合はE007を返す。

---

## 6.13 API-013 配送先住所管理

| 操作 | Method | Endpoint |
| --- | --- | --- |
| 一覧取得 | GET | `/addresses` |
| 登録 | POST | `/addresses` |
| 編集 | PUT | `/addresses/{id}` |

| 項目 | 内容 |
| --- | --- |
| 権限 | Customer |
| 関連テーブル | addresses |
| 主なエラー | E003 / E007 |

Request Example（登録・編集共通）:

```json
{
  "postal_code": "150-0001",
  "prefecture": "東京都",
  "city": "渋谷区神宮前",
  "address_line": "1-2-3",
  "building": "サンプルビル101",
  "recipient_name": "山田太郎",
  "phone": "090-1234-5678",
  "is_default": true
}
```

Validation: `postal_code` required/format `NNN-NNNN`、`prefecture` required/47都道府県のいずれか、`recipient_name` required/max:100、`phone` required。一覧取得・編集はログインユーザー自身の`addresses`のみが対象で、他会員の住所IDを指定した場合はE007を返す。

---

## 6.14 API-014 クーポン検証

| 項目 | 内容 |
| --- | --- |
| Method | POST |
| Endpoint | `/coupons/validate` |
| 権限 | Customer |
| 関連テーブル | coupons |
| 主なエラー | E005 |

Request:

```json
{
  "code": "SUMMER10",
  "subtotal": 8500
}
```

Response（成功時）:

```json
{
  "success": true,
  "message": "OK",
  "data": {
    "coupon_id": 3,
    "discount_type": "percent",
    "discount_value": 10,
    "discount_amount": 850
  }
}
```

Business Rule: BR-CPN-001（有効期間・最低購入金額・利用上限のいずれかを満たさない場合はE005）。本APIは注文確定前のプレビュー用であり、`used_count`はまだ更新しない（実際の消費はAPI-015で行う）。

---

## 6.15 API-015 注文確定（本システムで最も重要なAPI）

| 項目 | 内容 |
| --- | --- |
| Method | POST |
| Endpoint | `/orders` |
| 権限 | Customer |
| 関連テーブル | orders / order_items / inventories / inventory_logs / coupons |
| 主なエラー | E003 / E004 / E005 / E010 |

Request:

```json
{
  "address_id": 12,
  "coupon_code": "SUMMER10"
}
```

カート内容自体はリクエストに含めず、サーバー側で現在のactiveカート（`carts.user_id`＝ログインユーザー）を参照する（改ざん防止のため）。

Response（201 Created）:

```json
{
  "success": true,
  "message": "ご注文を受け付けました。決済にお進みください。",
  "data": {
    "order_id": 501,
    "order_number": "ORD-20260713-0501",
    "status": "pending",
    "subtotal": 8500,
    "tax_total": 850,
    "shipping_fee": 0,
    "discount_total": 850,
    "grand_total": 8500,
    "stripe_client_secret": "pi_xxx_secret_yyy"
  }
}
```

`grand_total = subtotal + tax_total + shipping_fee - discount_total`（`09_テーブル定義.md`4.11節）。この例では`tax_total`と`discount_total`がたまたま同額（850）なため`grand_total`が`subtotal`と一致して見えるが、通常は一致しない偶然の数値である。

処理内容（`12_詳細設計書.md`5.2節 OrderService::placeで詳述、ここでは概要のみ）:

1. `SELECT ... FOR UPDATE`で対象バリエーションの在庫行をロックする（BR-INV-007）
2. 在庫を確認し不足があればE004、トランザクション全体をロールバック
3. `orders`をpendingで作成し、選択住所を`shipping_*`へスナップショット（BR-ORD-003）
4. `order_items`へ商品名・単価・税率をスナップショット（BR-ORD-002, BR-TAX-002）
5. 在庫を確保（`quantity_available -= N`, `quantity_reserved += N`、BR-INV-003）
6. クーポンがあれば条件検証のうえ`used_count += 1`（BR-CPN-003）
7. Stripe PaymentIntentを作成し`client_secret`を返す

Business Rule: BR-ORD-001〜003, BR-TAX-001〜004, BR-INV-001〜003, BR-CPN-001〜004（`02_要件定義書.md`9章）

---

## 6.16 API-016 決済再試行

| 項目 | 内容 |
| --- | --- |
| Method | POST |
| Endpoint | `/orders/{id}/retry-payment` |
| 権限 | Customer（自分の注文のみ） |
| 関連テーブル | orders / payments |
| 主なエラー | E002 / E006 / E007 |

対象注文が`pending`かつ在庫確保期限内（BR-INV-006）の場合のみ、新しいStripe PaymentIntentを発行する。`status`が`pending`以外（既にcancelled等）の場合はE006。

---

## 6.17 API-017 Stripe Webhook受信

| 項目 | 内容 |
| --- | --- |
| Method | POST |
| Endpoint | `/webhooks/stripe` |
| 権限 | System（Stripeサーバーからのみ。`Stripe-Signature`ヘッダで検証） |
| 関連テーブル | orders / payments / inventories / inventory_logs / coupons |
| 主なエラー | E009 |

Request（Stripeから送信される`payment_intent.succeeded`イベント例、抜粋）:

```json
{
  "type": "payment_intent.succeeded",
  "data": { "object": { "id": "pi_xxx", "amount": 8500 } }
}
```

Response: `200 OK`（Stripeへの受信確認。ボディは空でよい）

処理内容:

1. `Stripe-Signature`ヘッダを検証、不正であれば400（E009）で即終了（BR-PAY-003を満たす前段の防御）
2. `stripe_payment_intent_id`から対象`payments`行を特定（重複イベントは`payments.status`が既にcapturedなら何もしない＝冪等性）
3. `payment_intent.succeeded` → `payments.status=captured`, `orders.status=paid`, 在庫を引き当て確定（BR-INV-004）
4. `payment_intent.payment_failed` → `payments.status=failed`（`orders.status`は`pending`のまま維持、BR-PAY-002）

詳細は9章「Webhook仕様」を参照。

---

## 6.18 API-018 注文履歴一覧取得

| 項目 | 内容 |
| --- | --- |
| Method | GET |
| Endpoint | `/orders` |
| 権限 | Customer |
| 関連テーブル | orders |
| 主なエラー | E010 |

Query: `status`（絞込）, `page`

Response Data: `orders`のうち`user_id`＝ログインユーザーのもののみ新しい順で返す（他会員のデータは一切含まれない）。

---

## 6.19 API-019 注文詳細取得

| 項目 | 内容 |
| --- | --- |
| Method | GET |
| Endpoint | `/orders/{id}` |
| 権限 | Customer（自分の注文のみ） |
| 関連テーブル | orders / order_items / payments / shipments |
| 主なエラー | E002 / E007 |

Response Data（抜粋）:

```json
{
  "order_number": "ORD-20260713-0501",
  "status": "shipped",
  "items": [
    { "product_name": "コットンTシャツ", "variant_label": "M / Red", "unit_price": 2980, "quantity": 1, "line_total": 3278 }
  ],
  "payment": { "method": "credit_card", "status": "captured", "amount": 8500 },
  "shipment": { "carrier": "ヤマト運輸", "tracking_number": "1234-5678-9012", "status": "shipped" }
}
```

対象注文が存在しない場合はE007、他会員の注文IDを直接指定した場合はE002（`user_id`スコープ、API-018と同じ方針）。`items`は`order_items`のスナップショット値であり、現在の`products`/`product_variants`の内容とは独立している（BR-ORD-002）。

---

## 6.20 API-020 レビュー投稿

| 項目 | 内容 |
| --- | --- |
| Method | POST |
| Endpoint | `/reviews` |
| 権限 | Customer |
| 関連テーブル | reviews / order_items |
| 主なエラー | E003 / E006 / E011 |

Request:

```json
{
  "order_item_id": 1203,
  "rating": 5,
  "comment": "サイズ感もちょうどよく満足です。"
}
```

Business Rule: BR-REV-001（対象`order_item`の注文が`delivered`であること。未達なら状態不整合としてE006）、BR-REV-002（`order_item_id`のUNIQUE制約により重複投稿はDB層でも二重に防止、アプリ層では重複操作エラーE011で案内。E006〈状態不整合〉とは意味が異なるため別コードとした）。

---

## 6.21 API-021 商品レビュー一覧取得

| 項目 | 内容 |
| --- | --- |
| Method | GET |
| Endpoint | `/products/{id}/reviews` |
| 権限 | Guest |
| 関連テーブル | reviews |
| 主なエラー | - |

Query: `page`

Response Data: 対象商品のstatus=visibleのレビューのみ新しい順で返す。`review_summary`（平均評価・件数、API-007参照）と合わせて商品詳細画面（SCR-004）に表示する。

---

## 6.22 API-022 商品管理

| 操作 | Method | Endpoint |
| --- | --- | --- |
| 登録 | POST | `/admin/products` |
| 編集 | PUT | `/admin/products/{id}` |
| 無効化・有効化 | PATCH | `/admin/products/{id}/status` |

| 項目 | 内容 |
| --- | --- |
| 権限 | Admin |
| 関連テーブル | products |
| 主なエラー | E002 / E003 / E010 |

Request Example:

```json
{
  "category_id": 2,
  "name": "コットンTシャツ",
  "description": "肌触りの良い綿100%Tシャツ",
  "tax_category": "standard"
}
```

---

## 6.23 API-023 商品画像管理

| 操作 | Method | Endpoint |
| --- | --- | --- |
| アップロード | POST | `/admin/products/{id}/images` |
| 削除 | DELETE | `/admin/products/{id}/images/{imageId}` |

| 項目 | 内容 |
| --- | --- |
| 権限 | Admin |
| 関連テーブル | product_images |
| 主なエラー | E003 / E007 |

Request Example（アップロード、multipart/form-data）: `image`（ファイル）, `display_order`, `is_primary`

Response（アップロード成功時）:

```json
{
  "success": true,
  "message": "画像をアップロードしました。",
  "data": { "id": 40, "image_url": "https://s3.../products/10/xxxx.jpg", "display_order": 1, "is_primary": true }
}
```

ファイルはLaravelアプリケーションが自身のIAMロールでS3へ直接書き込む（`13_インフラ設計.md`11章）。存在しない`imageId`を指定して削除しようとした場合はE007。

---

## 6.24 API-024 カテゴリ管理

| 操作 | Method | Endpoint |
| --- | --- | --- |
| 一覧取得 | GET | `/admin/categories` |
| 登録 | POST | `/admin/categories` |
| 編集 | PUT | `/admin/categories/{id}` |
| 無効化・有効化 | PATCH | `/admin/categories/{id}/status` |

| 項目 | 内容 |
| --- | --- |
| 権限 | Admin |
| 関連テーブル | categories |
| 主なエラー | E003 |

Request Example:

```json
{ "name": "アウター", "parent_category_id": 1 }
```

Validation: `name` required/max:100/unique、`parent_category_id` nullable/存在するcategories.idであること。Admin向け一覧取得（本API）はstatus=inactiveも含めて全件返す点がGuest向けAPI-008（activeのみ）と異なる。

---

## 6.25 API-025 バリエーション管理

| 操作 | Method | Endpoint |
| --- | --- | --- |
| 登録 | POST | `/admin/products/{id}/variants` |
| 編集 | PUT | `/admin/variants/{id}` |

Request Example:

```json
{
  "sku": "TSHIRT-M-RED",
  "size": "M",
  "color": "Red",
  "price": 2980
}
```

登録時、`inventories`に`quantity_available=0`の初期レコードを自動作成する（`12_詳細設計書.md`参照）。

---

## 6.26 API-026 在庫一覧取得

| 項目 | 内容 |
| --- | --- |
| Method | GET |
| Endpoint | `/admin/inventories` |
| 権限 | Admin |
| 関連テーブル | inventories / product_variants |
| 主なエラー | E002 |

Query: `low_stock_only`（`true`の場合`quantity_available < 10`のみ絞込。SCR-017在庫僅少フィルタに対応）

Response Data: バリエーションごとの`quantity_available`（利用可能数）・`quantity_reserved`（確保中数）・商品名・SKUの一覧。

---

## 6.27 API-027 在庫調整

| 項目 | 内容 |
| --- | --- |
| Method | PATCH |
| Endpoint | `/admin/inventories/{variantId}/adjust` |
| 権限 | Admin |
| 関連テーブル | inventories / inventory_logs |
| 主なエラー | E003 |

Request:

```json
{
  "quantity_change": 50,
  "reason": "入荷"
}
```

Response Data: 調整後の`quantity_available`。処理は`inventory_logs`に`change_type=adjustment`, `created_by=Admin ID`で記録する（BR-INV-008）。

---

## 6.28 API-028 注文一覧取得（Admin）

| 項目 | 内容 |
| --- | --- |
| Method | GET |
| Endpoint | `/admin/orders` |
| 権限 | Admin |
| 関連テーブル | orders |
| 主なエラー | E002 |

Query: `status`（絞込）, `order_number`（部分一致）, `user_name`（会員名の部分一致）, `page`

Response Data: 全会員の`orders`一覧を新しい順で返す。Customer向けAPI-018と異なり`user_id`によるスコープ制限はない（Adminは全注文を閲覧できる、`08_権限要件`参照）。

---

## 6.29 API-029 注文ステータス変更

| 項目 | 内容 |
| --- | --- |
| Method | PATCH |
| Endpoint | `/admin/orders/{id}/status` |
| 権限 | Admin |
| 関連テーブル | orders / payments |
| 主なエラー | E002 / E006 / E007 / E008 |

Request:

```json
{
  "status": "shipped"
}
```

Business Rule: BR-ORD-001。現在のステータスから許可されない遷移（例: `pending`→`shipped`）を指定した場合はE006を返す。`paid`→`cancelled`を指定した場合、Stripe返金処理を内部で実行してから`orders.status`を更新する（`12_詳細設計書.md`5.8/5.8.1節）。Stripe側の返金に失敗した場合はE008を返し、`orders.status`は`paid`のまま変更しない。

---

## 6.30 API-030 出荷登録

| 項目 | 内容 |
| --- | --- |
| Method | POST |
| Endpoint | `/admin/orders/{id}/shipment` |
| 権限 | Admin |
| 関連テーブル | shipments / orders |
| 主なエラー | E002 / E006 / E007 |

Request:

```json
{
  "carrier": "ヤマト運輸",
  "tracking_number": "1234-5678-9012"
}
```

処理: `shipments`を作成し、`orders.status`を`paid`→`shipped`に更新する（トランザクション、`12_詳細設計書.md`参照）。

---

## 6.31 API-031 クーポン管理

| 操作 | Method | Endpoint |
| --- | --- | --- |
| 一覧取得 | GET | `/admin/coupons` |
| 発行 | POST | `/admin/coupons` |
| 編集 | PUT | `/admin/coupons/{id}` |
| 無効化・有効化 | PATCH | `/admin/coupons/{id}/status` |

Request Example:

```json
{
  "code": "AUTUMN2026",
  "discount_type": "fixed",
  "discount_value": 500,
  "min_purchase_amount": 3000,
  "usage_limit": 100,
  "valid_from": "2026-09-01",
  "valid_to": "2026-09-30"
}
```

---

## 6.32 API-032 売上レポート取得

| 項目 | 内容 |
| --- | --- |
| Method | GET |
| Endpoint | `/admin/reports/sales` |
| 権限 | Admin |
| 関連テーブル | orders / order_items |
| 主なエラー | E002 |

Query: `year_month`（例: `2026-07`）

Response Data: 月別売上合計、カテゴリ別売上トップ5（Phase 2 DB設計特訓のクエリNo.1/2をAPI化したもの、`queries.sql`参照）。

---

## 6.33 API-033 在庫僅少レポート取得

| 項目 | 内容 |
| --- | --- |
| Method | GET |
| Endpoint | `/admin/reports/inventory` |
| 権限 | Admin |
| 関連テーブル | inventories / product_variants |
| 主なエラー | E002 |

Response Data: `quantity_available < 10`のバリエーション一覧（商品名・SKU・残数）。Phase 2 DB設計特訓のクエリNo.4をAPI化したもの。API-026（`low_stock_only=true`）と対象データは同じだが、本APIはSCR-020レポート画面専用の集計済みレスポンス形式（`06_画面設計.md`12章 帳票レイアウト参照）を返す点が異なる。

---

# 7. エラー仕様

| コード | 内容 | HTTPステータス |
| --- | --- | --- |
| E001 | ログイン失敗 | 401 |
| E002 | 権限エラー | 403 |
| E003 | バリデーションエラー | 422 |
| E004 | 在庫不足 | 409 |
| E005 | クーポン適用条件不成立 | 409 |
| E006 | 注文ステータス不正遷移 / 状態不整合（対象は存在するが、現在の状態では要求された操作を許可できない） | 409 |
| E007 | 対象データ未検出（返金対象のpayments行が存在しない場合を含む） | 404 |
| E008 | 決済失敗 | 402 |
| E009 | Webhook署名検証失敗 | 400 |
| E010 | 未認証 | 401 |
| E011 | 重複操作エラー（同一資源への重複投稿・重複登録。BR-REV-002のレビュー重複投稿など） | 409 |

**E006とE011の使い分け**: E006は「対象は存在するが状態が合わない」（例: 未`delivered`の注文へのレビュー、`paid`以外の注文への出荷登録）。E011は「同一操作を2回目以降実行しようとした」（例: 同一`order_item_id`への2件目のレビュー投稿）。前者は状態遷移、後者は一意性制約違反であり原因も対処方法も異なるため、コードを分離した。

---

# 8. セキュリティ仕様

| 項目 | 内容 |
| --- | --- |
| カード情報 | 自社サーバーで一切保持・通過させない（Stripe Elementsによるトークン化、NFR-014） |
| Webhook検証 | `Stripe-Signature`ヘッダをStripe SDKで検証し、偽装リクエストを拒否する（NFR-017） |
| Admin API | すべて`role:admin`ミドルウェアで保護する |
| 他会員データアクセス防止 | `orders` `addresses` `reviews`はすべて`WHERE user_id = 認証ユーザーID`を必須で付与する |

---

# 9. Webhook仕様（Stripe）

| 項目 | 内容 |
| --- | --- |
| 対象イベント | `payment_intent.succeeded`, `payment_intent.payment_failed` |
| 冪等性 | `payments.stripe_payment_intent_id`をUNIQUEキーとし、同一イベントの複数回配信でも二重処理しない（BR-PAY-003） |
| リトライ | Stripe側の仕様により最大3日間・指数バックオフで再送される。受信側は必ず200を返し、処理失敗時もStripe側リトライに任せて500を返さない設計とする（内部エラーはログに記録し、手動リカバリ手順を`20_運用保守手順書.md`に用意する） |
| タイムアウト | Webhook処理は5秒以内に200を返す（重い処理は非同期キューへ委譲、将来対応） |

---

# 10. トレーサビリティ

`07_機能一覧.md`のFUNC-ID → 本書のAPI-ID → `12_詳細設計書.md`のController/Service → `15_単体試験仕様書.md`/`16_結合試験仕様書.md`の試験ケース、の順に一意に追跡できる。

**付記（API-POL-008の理由）**: 個人の`~/.claude/CLAUDE.md`のグローバル規約は`/api/v1/`プレフィックス・camelCase JSONを推奨するが、本プロジェクトはPortfolio内の一貫性（Project 01との行き来のしやすさ、Eloquentのsnake_case規約との親和性）を優先し、Project 01と同じ規約を踏襲した。API/URLバージョニングとcamelCase化は、複数クライアント（モバイルアプリ等）が登場するProject 10（Mobile Companion App）以降で導入を検討する。

---

# 11. まとめ

33のAPIのうち、API-015（注文確定）とAPI-017（Stripe Webhook受信）の2つが本プロジェクトの中核である。この2つのAPIは業務ルール（BR-ORD/BR-INV/BR-TAX/BR-CPN/BR-PAY）のほぼ全てが交差する箇所であり、`12_詳細設計書.md`ではこの2APIの実装（Service層）を最も詳細に記述する。
