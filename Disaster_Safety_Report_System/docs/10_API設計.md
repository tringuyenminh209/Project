# API設計

Disaster Safety Report System（防災安全報告システム）

---

# 文書管理情報

| 項目 | 内容 |
| --- | --- |
| システム名 | Disaster Safety Report System |
| 文書名 | API設計 |
| 文書番号 | DSR-10 |
| 作成者 | Nguyen Minh Tri |
| 作成日 | 2026/07/23 |
| バージョン | 1.1 |
| ステータス | Draft |

---

# 改訂履歴

| Version | 日付 | 作成者 | 内容 |
| --- | --- | --- | --- |
| 0.0 | 2026/07/22 | Nguyen Minh Tri | スケルトン作成 |
| 1.0 | 2026/07/23 | Nguyen Minh Tri | 初版作成。REST API 26本を確定。安全報告の提出・再報告を1本のPUT（upsert）に統合し、部署ダッシュボードはURLに部署IDを持たせない設計としてIDORの入力経路自体をなくした。 |
| 1.1 | 2026/07/23 | Nguyen Minh Tri | 設計監査で発見した重大な業務リスクを修正: 6.7節のmap_pinsが「報告なし/位置情報なし」を一括して`locations`マスタ（通常勤務地）へフォールバックしていたため、`needs_help`報告でジオコーディング/GPSに失敗した従業員がオフィスにいるかのような赤ピンで誤表示される欠陥があった。緯度経度を持たない報告済み従業員はピン表示せず`unlocated_reports`という別配列で返す設計に変更（06_画面設計と連動修正）。 |

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
9. リアルタイム性の実現方針
10. トレーサビリティ
11. まとめ

---

# 1. 本書の目的

本書はDisaster Safety Report SystemのREST API仕様を定義する。07_機能一覧のFUNC-IDと対応させ、Request/Response、バリデーション、業務ルール、エラーコードを明確化する。本書は11_基本設計書・12_詳細設計書（Controller/Service/Policy実装）および試験仕様書の基準とする。

---

# 2. API設計方針

| 方針ID | 方針 | 内容 |
| --- | --- | --- |
| API-POL-001 | REST | Resource指向のURLを使用する。 |
| API-POL-002 | JSON | Request/Responseは全APIでJSON形式とする。本システムはファイルアップロード機能を持たないため（07_機能一覧に該当FUNCなし）、multipart/form-dataは使用しない。 |
| API-POL-003 | Stateless | Sanctumトークンにより認証状態を判断する（セッション非依存、NFR-008水平スケール対応）。 |
| API-POL-004 | スコープをURLパラメータで受け取らない設計 | 部署ダッシュボード（`GET /dashboard/department`）は部署IDをURL・Queryのいずれにも含めない。表示対象は常にトークンの持ち主（部門管理者）の`department_id`をサーバー側で導出する（BR-PRM-002、05_画面遷移図 G-04）。PMSのAPI-POL-004（`/projects/{id}/...`とURLへ明示することでスコープを宣言する設計）とは逆方向の判断だが理由がある: PMSのプロジェクトメンバーシップは多対多（1ユーザーが複数プロジェクトに参加しうる）ため「どのプロジェクトか」をURLで指定する必要があったが、DSRの部門管理者と部署の関係は常に1対1（`employees.department_id`）であり、そもそも指定する余地を作らないことが最も強いIDOR対策になる（NFR-012）。 |
| API-POL-005 | 権限制御の2段判定 | 全APIは「認証 → ロール（admin/manager/staff）」の2段のみで判定する。PMSは「認証 → メンバーシップ → ロール」の3段だったが、本システムは3層フラットロール（BR-PRM-001）でありメンバーシップに相当する中間層が存在しないため2段で足りる（02_要件定義書 3章の学習ポイントがAPI設計にも反映される）。判定はLaravel Middleware + Policyに一元化する（12_詳細設計書）。 |
| API-POL-006 | Audit | 災害イベントの作成・編集・収束切替、組織マスタ（部署・従業員）の変更操作をアプリケーションログに記録する（REQ-018、NFR-015）。 |
| API-POL-007 | Validation | 入力値はLaravel FormRequestで検証する。 |
| API-POL-008 | 命名規約の継続性 | Project 01〜03と同じ規約（Base URLに`/v1`を付けない、フィールド名snake_case）を踏襲する。 |
| API-POL-009 | 安全報告はPUTによるupsertとし、提出と再報告でエンドポイントを分けない | `safety_reports`は`UNIQUE(disaster_id, employee_id)`でUPDATE方式（BR-RPT-002）と確定済みであるため、初回提出（FUNC-009）と再報告（FUNC-010）はサーバー内部では同一のUPSERT処理であり、クライアント側で「これは初回か再報告か」を判定してURLを出し分ける必要がない。PUTの冪等性（同じ内容を2回送っても結果が変わらない）とも自然に整合する。 |
| API-POL-010 | 削除APIを持たない | 本システムには物理削除を伴う操作が1つも存在しない（09_テーブル定義 TBL-POL-003）。部署・従業員は無効化、災害は収束切替、いずれもPATCH `.../status`で表現し、DELETEメソッドの出番自体がない。PMSのAPI-POL-009（削除成功のHTTPステータスをどう返すか）のような論点は本システムには発生しない。 |

---

# 3. 共通仕様

## 3.1 Base URL

| 環境 | Base URL |
| --- | --- |
| Local | `http://localhost/api` |
| Production | `https://example.com/api` |

## 3.2 Header

| Header | 必須 | 内容 |
| --- | --- | --- |
| Content-Type | POST/PUT/PATCH時必須 | `application/json` |
| Accept | 必須 | `application/json` |
| Authorization | 認証API以外必須 | `Bearer {access_token}` |

## 3.3 共通Response形式

成功時:

```json
{ "success": true, "message": "OK", "data": {} }
```

失敗時:

```json
{ "success": false, "error": { "code": "E007", "message": "対象のデータが見つかりません。", "details": {} } }
```

## 3.4 HTTP Status

| Status | 用途 |
| --- | --- |
| 200 | 正常取得・正常更新 |
| 201 | 作成成功（部署・従業員・災害の新規作成） |
| 401 | E001（ログイン失敗）/ E010（未認証） |
| 403 | E002（権限エラー） |
| 404 | E007（未検出・存在秘匿） |
| 409 | E006（状態不整合） |
| 422 | E003（バリデーションエラー） |
| 500 | サーバー・DBエラー |

204・302は使用しない（API-POL-010。ファイルダウンロードAPI自体が存在しない）。

## 3.5 日付・ページネーション形式

| 種別 | 形式 | 例 |
| --- | --- | --- |
| Date | `YYYY-MM-DD` | `2026-07-23` |
| DateTime | ISO 8601 | `2026-07-23T09:00:00+09:00` |
| ページネーション | `meta: { current_page, last_page, total }` | 通知一覧・従業員一覧で使用（Project 02/03と同一書式） |

---

# 4. 認証・認可仕様

## 4.1 認証方式

| 項目 | 内容 |
| --- | --- |
| Login ID | `email` |
| Token期限 | 8時間（業務システム基準、00_開発計画書 5.1節） |
| Password保存 | `password_hash`へハッシュ化して保存 |

## 4.2 認可の2段判定（API-POL-005）

| 段階 | 判定 | 不成立時 |
| --- | --- | --- |
| 1. 認証 | 有効なSanctumトークンか | E010 |
| 2. ロール | 操作に必要なロール（admin/manager/staff）を満たすか（02_要件定義書 8章の権限マトリクスが正） | E002、ただし部署スコープ外へのアクセスはE007（4.3節） |

## 4.3 E002とE007の使い分け（02_要件定義書 8章の凡例が正）

| ケース | コード |
| --- | --- |
| ロール自体が不足（例: 一般社員がAdmin専用APIを叩く） | E002 |
| 一般社員が他人の安全報告を見ようとする | E002（「自分の分のみ閲覧可」、存在自体は秘匿しない） |
| 部門管理者が他部署の情報にアクセスしようとする経路 | 発生しない（4.4節、API-POL-004でURL上そもそも他部署を指定できない） |
| 認証済みユーザーが他人の通知IDを指定する | E007（存在秘匿。通知の所有権はBR-NTF-005の帰結） |

## 4.4 「自分自身のリソース」限定APIの設計

安全報告（自分の分）・通知（自分宛）・パスワード変更（自分）・部署ダッシュボード（自部署固定）は、いずれも対象を指定するIDパラメータをURL・Query・Requestボディのいずれにも持たせない。対象は常にトークンから解決する。これにより「他人のIDを指定してアクセスする」というIDOR攻撃の入力経路自体が存在しない（4.3節の「発生しない」はこの設計に起因する）。

---

# 5. API一覧

| API ID | Method | Endpoint | API名 | 権限 | 関連FUNC |
| --- | --- | --- | --- | --- | --- |
| API-001 | POST | `/auth/login` | ログイン | 未認証 | FUNC-001 |
| API-002 | POST | `/auth/logout` | ログアウト | 認証済み | FUNC-002 |
| API-003 | GET | `/auth/me` | ログインユーザー取得（ロール判定用） | 認証済み | FUNC-001 |
| API-004 | PATCH | `/auth/password` | パスワード変更 | 認証済み | FUNC-003 |
| API-005 | GET | `/departments` | 部署一覧 | Admin | FUNC-004 |
| API-006 | POST | `/departments` | 部署作成 | Admin | FUNC-004 |
| API-007 | PUT | `/departments/{departmentId}` | 部署編集 | Admin | FUNC-004 |
| API-008 | PATCH | `/departments/{departmentId}/status` | 部署の無効化/有効化 | Admin | FUNC-004 |
| API-009 | GET | `/employees` | 従業員一覧・検索 | Admin | FUNC-005 |
| API-010 | POST | `/employees` | 従業員作成 | Admin | FUNC-005 |
| API-011 | PUT | `/employees/{employeeId}` | 従業員編集 | Admin | FUNC-005 |
| API-012 | PATCH | `/employees/{employeeId}/status` | 従業員の無効化/有効化 | Admin | FUNC-005 |
| API-013 | GET | `/disasters` | 災害一覧 | 全ユーザー | FUNC-008 |
| API-014 | POST | `/disasters` | 災害イベント作成（対象範囲指定） | Admin | FUNC-006 / 013 |
| API-015 | GET | `/disasters/{disasterId}` | 災害詳細 | 全ユーザー | FUNC-008 |
| API-016 | PUT | `/disasters/{disasterId}` | 災害イベント編集 | Admin | FUNC-007 |
| API-017 | PATCH | `/disasters/{disasterId}/status` | 収束切替（active⇄resolved） | Admin | FUNC-007 |
| API-018 | GET | `/disasters/{disasterId}/safety-reports` | 安全報告一覧・集計サマリ | 全ユーザー（スコープは4.3節） | FUNC-008 |
| API-019 | GET | `/disasters/{disasterId}/safety-reports/me` | 自分の安全報告取得（フォーム初期表示） | 一般社員 / 部門管理者 | FUNC-009 / 010 |
| API-020 | PUT | `/disasters/{disasterId}/safety-reports/me` | 安全報告の提出・再報告（upsert） | 一般社員 / 部門管理者 | FUNC-009 / 010 |
| API-021 | GET | `/notifications` | 通知一覧 | 認証済み（自分宛のみ） | FUNC-011 |
| API-022 | GET | `/notifications/unread-count` | 未読件数 | 認証済み | FUNC-011 |
| API-023 | PATCH | `/notifications/{notificationId}/read` | 個別既読化 | 本人 | FUNC-012 |
| API-024 | PATCH | `/notifications/read-all` | 一括既読化 | 本人 | FUNC-012 |
| API-025 | GET | `/dashboard/department` | 部署ダッシュボード（自部署固定、地図含む） | 部門管理者 | FUNC-015 / 017 |
| API-026 | GET | `/dashboard/company` | 全社ダッシュボード（部署別内訳・地図含む） | Admin | FUNC-016 / 017 |

FUNC-013（同報通知作成）はAPI-014の副作用であり専用エンドポイントを持たない。FUNC-014（未報告者への催促通知バッチ）はLaravel Scheduleによる定期実行コマンドであり、HTTPエンドポイントを持たない（09_テーブル定義・12_詳細設計書で実装根拠を明記する）。FUNC-018（権限制御）・FUNC-019（操作ログ記録）は全APIに横断適用される仕組みであり、これも専用エンドポイントを持たない。`companies`・`locations`はいずれも本システムのFUNC一覧にCRUD機能を持たないため（09_テーブル定義 4.1/4.4節の設計判断）、対応するAPIも存在しない — `locations`のデータはAPI-025/026の地図ピン情報として読み取り専用で埋め込まれる。

---

# 6. API詳細

## 6.1 API-001〜004 認証

| 操作 | Method | Endpoint | 権限 | 主なエラー |
| --- | --- | --- | --- | --- |
| ログイン | POST | `/auth/login` | 未認証 | E001 |
| ログアウト | POST | `/auth/logout` | 認証済み | E010 |
| ログインユーザー取得 | GET | `/auth/me` | 認証済み | E010 |
| パスワード変更 | PATCH | `/auth/password` | 認証済み | E003 / E010 |

ログインRequest: `{ "email": "staff@example.com", "password": "password123" }`。

ログインResponse（200）:

```json
{
  "success": true,
  "message": "OK",
  "data": {
    "token": "1|xxxxxxxxxxxxxxxxxxxx",
    "employee": { "id": 3, "name": "山田太郎", "role": "staff", "department_id": 2 }
  }
}
```

`role`をフロントが受け取り、ロール別ランディング3分岐（05_画面遷移図 2章）に使う。`/auth/me`は同じ`employee`形状を再取得する用途（トークン再検証・画面リロード時）。パスワード変更Request: `{ "current_password": "...", "new_password": "..." }`（`new_password`は8〜20文字、02_要件定義書 12章）。inactiveな従業員のログイン試行はE001（BR-ORG-003）。

---

## 6.2 API-005〜008 部署管理

| 操作 | Method | Endpoint | 権限 | 主なエラー |
| --- | --- | --- | --- | --- |
| 一覧 | GET | `/departments` | Admin | E002 |
| 作成 | POST | `/departments` | Admin | E002 / E003 |
| 編集 | PUT | `/departments/{departmentId}` | Admin | E002 / E003 / E007 |
| 無効化/有効化 | PATCH | `/departments/{departmentId}/status` | Admin | E002 / E003 / E007 |

作成/編集Request: `{ "name": "営業部" }`（required/max:100）。ステータス変更Request: `{ "status": "inactive" }`（in:active,inactive）。

Response（一覧、200）:

```json
{
  "success": true,
  "message": "OK",
  "data": [
    { "id": 1, "name": "総務部", "status": "active", "employee_count": 4 },
    { "id": 2, "name": "営業部", "status": "active", "employee_count": 12 }
  ]
}
```

`employee_count`は`employees`のCOUNT導出（集計列は持たない、09_テーブル定義と同じ正規化方針）。無効化された部署は災害作成フォーム（API-014）の対象部署選択肢からは除外するが、既存従業員の`department_id`参照自体は維持する（BR-ORG-002は「必須」を規定するのみで、部署無効化時の従業員の再割当は本スコープ外 — 18章）。

---

## 6.3 API-009〜012 従業員管理

| 操作 | Method | Endpoint | 権限 | 主なエラー |
| --- | --- | --- | --- | --- |
| 一覧・検索 | GET | `/employees`（Query: `keyword`, `department_id`, `role`, `page`） | Admin | E002 |
| 作成 | POST | `/employees` | Admin | E002 / E003 |
| 編集 | PUT | `/employees/{employeeId}` | Admin | E002 / E003 / E007 |
| 無効化/有効化 | PATCH | `/employees/{employeeId}/status` | Admin | E002 / E003 / E007 |

作成/編集Request:

```json
{
  "department_id": 2,
  "location_id": 1,
  "name": "山田太郎",
  "name_kana": "ヤマダタロウ",
  "email": "yamada@example.com",
  "password": "password123",
  "role": "staff"
}
```

Validation: `email` required/email形式/max:255/unique、`password`（作成時必須・編集時任意）required/8〜20文字、`name` required/max:100、`role` in:admin,manager,staff、`department_id`必須かつ実在する部署（BR-ORG-002）、`location_id`任意（未設定を許容、REL-002）。ステータス変更Request: `{ "status": "inactive" }`。無効化された従業員はAPI-001でE001となる（BR-ORG-003）。

Response（一覧、200、抜粋）:

```json
{
  "data": [
    { "id": 3, "name": "山田太郎", "email": "yamada@example.com", "role": "staff",
      "department": { "id": 2, "name": "営業部" }, "status": "active" }
  ],
  "meta": { "current_page": 1, "last_page": 2, "total": 24 }
}
```

`department`はオブジェクト（id・nameのみ、他カラムは含めない）で返す。パスワードはいかなるレスポンスにも含めない。

---

## 6.4 API-013〜017 災害管理

| 操作 | Method | Endpoint | 権限 | 主なエラー |
| --- | --- | --- | --- | --- |
| 一覧 | GET | `/disasters`（Query: `status`） | 全ユーザー | E010 |
| 作成 | POST | `/disasters` | Admin | E002 / E003 |
| 詳細 | GET | `/disasters/{disasterId}` | 全ユーザー | E007 |
| 編集 | PUT | `/disasters/{disasterId}` | Admin | E002 / E003 / E007 |
| 収束切替 | PATCH | `/disasters/{disasterId}/status` | Admin | E002 / E003 / E007 |

作成Request（全社対象）:

```json
{ "type": "地震", "occurred_at": "2026-07-23T10:00:00+09:00", "target_scope": "all" }
```

作成Request（特定部署対象）:

```json
{ "type": "水害", "occurred_at": "2026-07-23T10:00:00+09:00", "target_scope": "specific", "target_department_ids": [1, 3] }
```

Validation: `type` required/max:50、`occurred_at` required/日時形式、`target_scope` in:all,specific、`target_scope=specific`の場合`target_department_ids`は1件以上必須かつ全件が実在し`status=active`の部署であること（Service層検証、09_テーブル定義 11章-1。存在しないIDが混入した場合はE003）。

作成Response（201）:

```json
{
  "success": true,
  "message": "対象の従業員128名へ通知を送信しました。",
  "data": {
    "id": 10, "type": "地震", "occurred_at": "2026-07-23T10:00:00+09:00",
    "target_scope": "all", "status": "active", "notified_count": 128
  }
}
```

処理: 同一トランザクションで`disasters`を作成し、対象範囲の全従業員へ`disaster_alert`通知を作成する（BR-NTF-001、FUNC-013）。件数が多い場合の性能はNFR-001（1,000人・60秒以内）に従う。同期ループで満たせない場合のQueue化判断は12_詳細設計書で行う。

一覧Response（200、抜粋。認証者が一般社員/部門管理者の場合）:

```json
{
  "data": [
    { "id": 10, "type": "地震", "occurred_at": "2026-07-23T10:00:00+09:00",
      "status": "active", "my_report_status": null },
    { "id": 9, "type": "水害（デモ）", "occurred_at": "2026-07-01T08:00:00+09:00",
      "status": "resolved", "my_report_status": "safe" }
  ]
}
```

`my_report_status`は認証者自身の`safety_reports.status`（未報告は`null`。BR-RPT-001の「未確認は導出表示」をAPIレベルで具体化したもの）。SCR-002（ホーム）はこの1回のGETで「進行中の災害一覧」と「自分の報告状況」を同時に取得し、追加のAPI呼び出しを必要としない（NFR-021の低操作コストをAPI設計でも支える）。Adminが呼んだ場合は`my_report_status`を含めない（BR-PRM-003、Adminは報告対象外）。

収束切替Request: `{ "status": "resolved" }`または`{ "status": "active" }`（BR-DIS-002。取り消しによる再進行中化も同一エンドポイント）。

---

## 6.5 API-018〜020 安全報告

| 操作 | Method | Endpoint | 権限 | 主なエラー |
| --- | --- | --- | --- | --- |
| 一覧・集計サマリ | GET | `/disasters/{disasterId}/safety-reports` | 全ユーザー（スコープあり） | E007 |
| 自分の報告取得 | GET | `/disasters/{disasterId}/safety-reports/me` | 一般社員 / 部門管理者 | E006 / E007 |
| 提出・再報告 | PUT | `/disasters/{disasterId}/safety-reports/me` | 一般社員 / 部門管理者 | E003 / E006 / E007 |

一覧・集計サマリResponse（200。部門管理者は自部署の従業員のみ、Adminは全社、一般社員はこのAPIを呼ばず自分の分はAPI-019で取得する — 8章権限マトリクスの「他人の安全報告詳細閲覧」欄）:

```json
{
  "data": {
    "summary": { "safe": 45, "needs_help": 3, "unreported": 2 },
    "reports": [
      { "employee_id": 3, "employee_name": "山田太郎", "department_name": "営業部",
        "status": "safe", "comment": null, "latitude": null, "longitude": null,
        "reported_at": "2026-07-23T10:05:00+09:00" },
      { "employee_id": 5, "employee_name": "佐藤花子", "department_name": "営業部",
        "status": "needs_help", "comment": "自宅付近が冠水しています", "latitude": 35.681236, "longitude": 139.767125,
        "reported_at": "2026-07-23T10:12:00+09:00" }
    ]
  }
}
```

`unreported`は対象範囲の従業員数から`safety_reports`の行数を引いた**導出値**であり、DBに保存された値ではない（BR-RPT-001）。`reports`は`status`に関わらず報告済みの行のみを含む（未報告者は`summary.unreported`の件数にのみ現れ、個別の行としては現れない — 未報告者の氏名一覧が必要になった場合は将来のFUNC拡張として13章で検討する）。部門管理者が自部署以外の`disasterId`配下を叩いても、対象範囲外であれば`reports`は自部署分のみにフィルタされる（E007ではなく、レスポンスの中身が自動的に自部署に限定される設計 — 4.4節の「対象を指定するIDを持たせない」設計と対になる）。

自分の報告取得Response（200、未報告の場合）:

```json
{ "success": true, "message": "OK", "data": null }
```

提出・再報告Request:

```json
{ "status": "needs_help", "comment": "自宅付近が冠水しています", "address_text": "東京都渋谷区...", "latitude": 35.681236, "longitude": 139.767125 }
```

Validation: `status` required/in:safe,needs_help（BR-RPT-001）、`comment` nullable/max:500、`address_text` nullable/max:200、`latitude`/`longitude`は両方存在するか両方省略するかのいずれか（片方だけの送信はE003 — 09_テーブル定義 8章CHECKと同じ整合性をアプリ層でも検証する）。**ジオコーディング（住所→緯度経度変換）はフロントエンドがGoogle Geocoding APIを直接呼び出して行う**（00_開発計画書 4章のシステム構成、02_要件定義書 14章）。本APIは既に解決済みの緯度経度を受け取るだけであり、バックエンドはGoogle APIを一切呼び出さない。ジオコーディングに失敗した場合、フロントエンドは`latitude`/`longitude`を省略して送信し、本APIはそれを正常な報告として受理する（NFR-007、BR-RPT-003）。

処理: `disasters.status=active`かつ自分の部署が対象範囲に含まれることを確認し（不成立はE006）、`(disaster_id, employee_id)`でUPSERT — 既存行があればUPDATE（`reported_at`を現在時刻に更新）、なければINSERT（BR-RPT-002）。Adminによる呼び出しはE002（BR-PRM-003、8章権限マトリクス）。

---

## 6.6 API-021〜024 通知

| 操作 | Method | Endpoint | 権限 | 主なエラー |
| --- | --- | --- | --- | --- |
| 一覧 | GET | `/notifications`（Query: `unread_only`, `page`） | 本人 | E010 |
| 未読件数 | GET | `/notifications/unread-count` | 本人 | E010 |
| 個別既読 | PATCH | `/notifications/{notificationId}/read` | 本人 | E007 |
| 一括既読 | PATCH | `/notifications/read-all` | 本人 | E010 |

一覧Response（200、抜粋）:

```json
{
  "data": [
    { "id": 55, "type": "disaster_alert", "disaster_id": 10, "disaster_type": "地震",
      "is_read": false, "created_at": "2026-07-23T10:00:00+09:00" }
  ],
  "meta": { "current_page": 1, "last_page": 1, "total": 3 }
}
```

`disaster_type`は`disasters`とのJOINで都度取得する（PMSの`notifications.task_title`のようなスナップショット列は不要 — `disasters`は削除されないため参照が失われるケースがない、08_ER図 ER-006/エンティティ詳細で確定済み）。通知タップ時、フロントは`type=disaster_alert`または`report_reminder`かつ未報告であれば`/disasters/{disaster_id}/report`（SCR-003）へ、報告済みであれば`/disasters/{disaster_id}`（SCR-011）へ遷移する（05_画面遷移図 3章）。他人の通知IDを指定した既読化はE007（BR-NTF-005、4.3節）。

---

## 6.7 API-025〜026 ダッシュボード・地図

| 操作 | Method | Endpoint | 権限 | 主なエラー |
| --- | --- | --- | --- | --- |
| 部署ダッシュボード | GET | `/dashboard/department` | 部門管理者 | E002 / E010 |
| 全社ダッシュボード | GET | `/dashboard/company` | Admin | E002 / E010 |

部署ダッシュボードResponse（200。`departmentId`はURLに現れない — API-POL-004）:

```json
{
  "data": {
    "department": { "id": 2, "name": "営業部" },
    "active_disasters": [
      { "disaster_id": 10, "type": "地震", "summary": { "safe": 10, "needs_help": 1, "unreported": 1 } }
    ],
    "map_pins": [
      { "employee_id": 5, "employee_name": "佐藤花子", "status": "needs_help", "latitude": 35.681236, "longitude": 139.767125, "source": "safety_report" },
      { "employee_id": 3, "employee_name": "山田太郎", "status": "safe", "latitude": 35.658, "longitude": 139.701, "source": "safety_report" },
      { "employee_id": 9, "employee_name": "鈴木一郎", "status": "unreported", "latitude": 35.660, "longitude": 139.703, "source": "location" }
    ],
    "unlocated_reports": [
      { "employee_id": 12, "employee_name": "田中次郎", "status": "needs_help", "comment": "自宅付近が停電しています", "reported_at": "2026-07-23T10:20:00+09:00" }
    ]
  }
}
```

**設計修正（v1.1、設計監査で発見）**: `map_pins`は`source=safety_report`（**報告に緯度経度が実際に含まれる場合のみ**、ステータスは問わない）と`source=location`（**未報告者（`status=unreported`）に限り**`locations`マスタの通常勤務地へフォールバック）の2種類のみとする。**報告済み（`safe`/`needs_help`）だが緯度経度を持たない従業員は、地図に一切ピン表示しない** — v1.0では「報告がない/位置情報なしの従業員」を一括して`locations`マスタへフォールバックしていたが、これは自宅等で被災し`needs_help`を報告したがジオコーディング/GPS取得に失敗した従業員を、実際にはいない勤務地（オフィス）に赤ピンで表示してしまう欠陥だった。BCPの地図は救助・安否確認の意思決定に直結するため、**実際の座標がない報告を実在するかのように地図上に描画すること自体が誤誘導のリスクを生む**（未報告者向けのフォールバックは「本来いるはずの場所」を示す参考情報として許容されるが、報告済みの従業員には成立しない）。そのため緯度経度を持たない報告済み従業員は`unlocated_reports`という別配列で返し、フロントは地図とは独立したテキストリスト（06_画面設計で「位置情報：未確定」と表示）として扱う。

全社ダッシュボードResponse（200、抜粋）は`department`の代わりに部署別内訳配列を持つ:

```json
{
  "data": {
    "company_summary": { "safe": 89, "needs_help": 4, "unreported": 7 },
    "by_department": [
      { "department_id": 1, "department_name": "総務部", "summary": { "safe": 4, "needs_help": 0, "unreported": 0 } },
      { "department_id": 2, "department_name": "営業部", "summary": { "safe": 10, "needs_help": 1, "unreported": 1 } }
    ],
    "map_pins": [ "...(全社分。仕様は部署ダッシュボードと同一)" ],
    "unlocated_reports": [ "...(全社分。仕様は部署ダッシュボードと同一)" ]
  }
}
```

`by_department`により、Adminは独立した「部署ダッシュボード」画面を持たずに全部署の内訳を1画面で把握できる（05_画面遷移図 5章 注1）。いずれのAPIも進行中の災害が複数ある場合は`active_disasters`（部署）・`company_summary`（全社、進行中の全災害を横断集計）を配列/集約で返す。

---

# 7. エラー仕様

| コード | 内容 | HTTPステータス |
| --- | --- | --- |
| E001 | ログイン失敗（inactive含む） | 401 |
| E002 | 権限エラー | 403 |
| E003 | バリデーションエラー（存在しない部署ID・緯度経度の片方欠落を含む） | 422 |
| E006 | 状態不整合（収束済み・対象範囲外の災害への報告） | 409 |
| E007 | 対象データ未検出（他人の通知IDを含む） | 404 |
| E010 | 未認証 | 401 |

02_要件定義書 16章と同一（E004/E005/E008/E009/E011は本システムでは使用しない）。PMSのような「メンバーでない者への存在秘匿（E007）」の適用範囲は本システムには存在しない — 部門管理者の部署スコープはAPI-POL-004によりURL設計自体で防いでいるため、判定の結果としてのE007ではなく、そもそもエラーが発生する入力経路がない。

---

# 8. セキュリティ仕様

| 項目 | 内容 |
| --- | --- |
| CORS | SPAの配信オリジンのみ許可（NFR-013） |
| 部署スコープ | URLパラメータとして受け取らない設計（API-POL-004）でIDORの入力経路自体をなくす（NFR-012） |
| 外部API呼び出しの分離 | Google Maps/Geocoding APIはフロントエンドから直接呼び出し、バックエンドは一切呼び出さない（6.5節）。APIキーはHTTPリファラ制限（NFR-014、14_セキュリティ設計で確定） |
| Mass Assignment対策 | `employees.role`・`status`・`password_hash`は`$fillable`に含めず、Admin確認済みのService層からのみ設定する（14_セキュリティ設計で詳述） |
| Audit | 災害・組織マスタの変更操作のログ記録（API-POL-006） |

---

# 9. リアルタイム性の実現方針

本システムはWebSocket（PMSのLaravel Reverb相当のbonus機能）を採用しない。00_開発計画書 6章の使用技術一覧にWebSocket関連技術が含まれておらず、NFR-002も「3秒以内の応答」であって真のリアルタイム配信（プッシュ通知的な即時反映）までは要求していないため、以下のポーリング方式で十分と判断する。

| 画面 | 方式 |
| --- | --- |
| SCR-004/005（ダッシュボード） | 画面表示中30秒間隔で`GET /dashboard/department`または`/company`を再取得（PMSのWebSocket未接続時フォールバックと同じ間隔を採用） |
| SCR-009（通知一覧）・ヘッダー未読件数 | 30秒間隔で`GET /notifications/unread-count`をポーリング |

将来的に大規模化しリアルタイム性の要求が高まった場合、WebSocket導入は13章（今後の拡張予定）の対象とする。

---

# 10. トレーサビリティ

07_機能一覧のFUNC-ID → 本書のAPI-ID → 12_詳細設計書のController/Service → 15〜17の試験仕様書の試験ケース、の順に一意に追跡できる。

---

# 11. まとめ

REST API 26本を定義した。本書の設計上の核心は4点である。①安全報告の提出・再報告を1本のPUT（upsert）に統合し、クライアントに「初回か再報告か」の判定を持たせない設計（API-POL-009、BR-RPT-002の直接的な帰結）。②部署ダッシュボードから部署IDパラメータそのものを排除し、IDOR対策を「判定ロジック」ではなく「入力経路の不存在」で実現したこと（API-POL-004、PMSのURL明示スコープとは逆方向だが、1対1の所属関係という業務構造の違いに基づく正しい判断）。③WebSocketを採用せずポーリングで要件を満たす判断（9章）— 全ての機能を実装すればよいのではなく、非機能要件（NFR-002）に対して過剰な技術を選ばないという判断も設計の一部であることを示した。④地図ピン（6.7節、v1.1で修正）— 位置情報のないデータを推測で補って表示することが、防災システムでは「親切な機能」ではなく「誤った意思決定を誘発するリスク」になり得ることを設計判断として明記した。12_詳細設計書ではこれらをController/Service/Policyの疑似コードへ落とし込む。

---
