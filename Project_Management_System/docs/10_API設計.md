# API設計

Project Management System（プロジェクト管理システム）

---

# 文書管理情報

| 項目 | 内容 |
| --- | --- |
| システム名 | Project Management System |
| 文書名 | API設計 |
| 文書番号 | PMS-010 |
| 作成者 | Nguyen Minh Tri |
| 作成日 | 2026/07/18 |
| バージョン | 1.2 |
| ステータス | Draft |

---

# 改訂履歴

| Version | 日付 | 作成者 | 内容 |
| --- | --- | --- | --- |
| 0.0 | 2026/07/17 | Nguyen Minh Tri | スケルトン作成 |
| 1.0 | 2026/07/18 | Nguyen Minh Tri | 初版作成（REST API 35本 + WebSocketイベント仕様。ネストされたルートによるメンバーシップスコープの明示を方針化） |
| 1.1 | 2026/07/18 | Nguyen Minh Tri | 整合性監査による修正: ①API-028のproject_idがJOIN導出（TBL-007に列なし・タスク削除時null）であることを明記し、削除済み通知のクリック挙動を03/06と整合。②notification.createdペイロードにproject_idを追加。③3.4にステータス302を追加（API-026）。④API-007にAdmin作成不可（E002）を明記。 |
| 1.2 | 2026/07/21 | Nguyen Minh Tri | guide/コード監査で発見: 3.4節が削除成功を204と記載していたが、実装（guide全Controller::destroy）はエンベロープ維持のため200+data:nullで返す設計。API-POL-009を新設し方針を明文化、3.4節を実装に合わせて訂正。 |

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
9. WebSocket仕様（Laravel Reverb、bonus）
10. トレーサビリティ
11. まとめ

---

# 1. 本書の目的

本書はProject Management SystemのREST API仕様およびWebSocketイベント仕様を定義する。`07_機能一覧.md`のFUNC-IDと対応させ、Request/Response、バリデーション、業務ルール、エラーコードを明確化する。本書は`12_詳細設計書.md`（Controller/Service/Policy実装）および試験仕様書の基準とする。

---

# 2. API設計方針

| 方針ID | 方針 | 内容 |
| --- | --- | --- |
| API-POL-001 | REST | Resource指向のURLを使用する。 |
| API-POL-002 | JSON | Request/Responseは原則JSON形式とする（ファイルアップロードのみmultipart/form-data）。 |
| API-POL-003 | Stateless | Laravel Sanctumトークンにより認証状態を判断する（セッション非依存、NFR-008水平スケール対応）。 |
| API-POL-004 | ネストされたルートによるスコープ明示 | プロジェクト資源のURLは必ず`/projects/{projectId}/...`配下にネストする。`taskId`はグローバル一意だが、URLに親プロジェクトを含めることで①メンバーシップ判定（INC-002）の対象を宣言的にし、②projectIdとtaskIdの親子不一致をE007として弾ける（IDの総当たり探索の防止）。 |
| API-POL-005 | 権限制御の2段判定 | 全APIは「認証（INC-001）→ メンバーシップ（INC-002）→ ロール（INC-003/004）」の順に判定する。判定はLaravel Policyに一元化する（`12_詳細設計書.md`）。 |
| API-POL-006 | Audit | メンバー変更・削除系操作（API-012〜014, 020, 033, 035）はアプリケーションログに記録する（REQ-028 / FUNC-033）。 |
| API-POL-007 | Validation | 入力値はLaravel FormRequestで検証する。ファイルは拡張子+MIMEの両方（BR-FIL-001）。 |
| API-POL-008 | 命名規約の継続性 | Project 01/02と同じ規約（Base URLに`/v1`を付けない、フィールド名snake_case）を踏襲する。 |
| API-POL-009 | 削除成功もエンベロープ形式を維持 | 削除成功は仕様上204（ボディなし）が一般的だが、本APIは全エンドポイントで`{success,message,data}`エンベロープ（3.3節）をフロントが一律解釈する契約のため、削除も200 + `data:null`で返す（204はボディを持てずエンベロープと両立しないため不採用）。実装は`guide/`の各Controller::destroyを正とする。 |

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
| Content-Type | POST/PUT/PATCH時必須 | `application/json`（API-025のみ`multipart/form-data`） |
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
| 201 | 作成成功 |
| 200 | 削除成功（API-POL-009: エンベロープ維持のため204は不採用、`data:null`で返す） |
| 302 | ファイルダウンロードのリダイレクト（presigned URL方式採用時のAPI-026。方式は`14_セキュリティ設計.md`で確定） |
| 401 | E001（ログイン失敗）/ E010（未認証） |
| 403 | E002（権限エラー） |
| 404 | E007（未検出・存在秘匿） |
| 409 | E006（状態不整合）/ E011（重複操作） |
| 422 | E003（バリデーションエラー） |
| 500 | サーバー・DBエラー |

## 3.5 日付・ページネーション形式

| 種別 | 形式 | 例 |
| --- | --- | --- |
| Date | `YYYY-MM-DD` | `2026-07-18` |
| DateTime | ISO 8601 | `2026-07-18T09:00:00+09:00` |
| ページネーション | `meta: { current_page, last_page, total }` | Project 02と同一書式（通知一覧・Admin一覧で使用） |

---

# 4. 認証・認可仕様

## 4.1 認証方式

| 項目 | 内容 |
| --- | --- |
| Login ID | `email` |
| Token期限 | 8時間（業務システム基準、`00_開発計画書.md` 5.1節） |
| Password保存 | `password_hash`へハッシュ化して保存 |

## 4.2 認可の2段判定（API-POL-005）

| 段階 | 判定 | 不成立時 |
| --- | --- | --- |
| 1. 認証 | 有効なSanctumトークンか（INC-001） | E010 |
| 2. メンバーシップ | `/projects/{id}/...`配下: 当該プロジェクトのメンバーか（INC-002、`project_members`のUNIQUE複合キーで1行判定） | E007（存在秘匿、BR-PRM-006） |
| 3. ロール | Owner専用操作（INC-003）/ Admin専用操作（INC-004） | E002 |

書込系はさらにアーカイブ確認（INC-005、archived→E006）を通る。判定表の正は`02_要件定義書.md` 8章の権限マトリクス。

---

# 5. API一覧

| API ID | Method | Endpoint | API名 | 権限 | 関連FUNC |
| --- | --- | --- | --- | --- | --- |
| API-001 | POST | `/auth/register` | 会員登録 | 未認証 | FUNC-001 |
| API-002 | POST | `/auth/login` | ログイン | 未認証 | FUNC-002 |
| API-003 | POST | `/auth/logout` | ログアウト | 認証済み | FUNC-003 |
| API-004 | GET | `/auth/me` | ログインユーザー取得 | 認証済み | FUNC-002 |
| API-005 | PATCH | `/auth/password` | パスワード変更 | 認証済み | FUNC-004 |
| API-006 | GET | `/projects` | 参加プロジェクト一覧 | 認証済み | FUNC-006 |
| API-007 | POST | `/projects` | プロジェクト作成 | 認証済み | FUNC-005 |
| API-008 | GET | `/projects/{projectId}` | プロジェクト詳細（自分のロール含む） | Member以上 | FUNC-006 |
| API-009 | PUT | `/projects/{projectId}` | プロジェクト編集 | Owner | FUNC-007 |
| API-010 | PATCH | `/projects/{projectId}/status` | アーカイブ / 復元 | Owner | FUNC-008 |
| API-011 | GET | `/projects/{projectId}/members` | メンバー一覧 | Member以上 | FUNC-012 |
| API-012 | POST | `/projects/{projectId}/members` | メンバー招待 | Owner | FUNC-009 |
| API-013 | PATCH | `/projects/{projectId}/members/{userId}` | ロール変更 | Owner | FUNC-010 |
| API-014 | DELETE | `/projects/{projectId}/members/{userId}` | 除名・自主脱退 | Owner（自主脱退は本人） | FUNC-011 |
| API-015 | GET | `/projects/{projectId}/tasks` | タスク一覧・検索 | Member以上 | FUNC-016 / 017 |
| API-016 | POST | `/projects/{projectId}/tasks` | タスク作成 | Member以上 | FUNC-013 |
| API-017 | GET | `/projects/{projectId}/tasks/{taskId}` | タスク詳細 | Member以上 | FUNC-014 |
| API-018 | PATCH | `/projects/{projectId}/tasks/{taskId}` | タスク編集 | Member以上 | FUNC-014 |
| API-019 | PATCH | `/projects/{projectId}/tasks/{taskId}/position` | カンバン移動・並び替え | Member以上 | FUNC-018 / 019 |
| API-020 | DELETE | `/projects/{projectId}/tasks/{taskId}` | タスク削除 | Owner | FUNC-015 |
| API-021 | GET | `/projects/{projectId}/tasks/{taskId}/comments` | コメント一覧 | Member以上 | FUNC-020 |
| API-022 | POST | `/projects/{projectId}/tasks/{taskId}/comments` | コメント投稿 | Member以上 | FUNC-020 |
| API-023 | DELETE | `/projects/{projectId}/tasks/{taskId}/comments/{commentId}` | コメント削除 | 本人 / Owner | FUNC-021 |
| API-024 | GET | `/projects/{projectId}/tasks/{taskId}/files` | ファイル一覧 | Member以上 | FUNC-023 |
| API-025 | POST | `/projects/{projectId}/tasks/{taskId}/files` | ファイルアップロード | Member以上 | FUNC-022 |
| API-026 | GET | `/projects/{projectId}/tasks/{taskId}/files/{fileId}/download` | ファイルダウンロード | Member以上 | FUNC-023 |
| API-027 | DELETE | `/projects/{projectId}/tasks/{taskId}/files/{fileId}` | ファイル削除 | アップロード者 / Owner | FUNC-024 |
| API-028 | GET | `/notifications` | 通知一覧 | 認証済み（自分宛のみ） | FUNC-025 |
| API-029 | GET | `/notifications/unread-count` | 未読件数 | 認証済み | FUNC-025 |
| API-030 | PATCH | `/notifications/{id}/read` | 個別既読化 | 本人 | FUNC-026 |
| API-031 | PATCH | `/notifications/read-all` | 一括既読化 | 本人 | FUNC-026 |
| API-032 | GET | `/admin/users` | ユーザー一覧（Admin） | Admin | FUNC-030 |
| API-033 | PATCH | `/admin/users/{id}/status` | ユーザー無効化/有効化 | Admin | FUNC-030 |
| API-034 | GET | `/admin/projects` | 全プロジェクト一覧（Admin） | Admin | FUNC-031 |
| API-035 | PATCH | `/admin/projects/{id}/archive` | 強制アーカイブ | Admin | FUNC-031 |

FUNC-027（通知作成）・FUNC-028（期限バッチ）はAPI-016/018/022の副作用およびSchedulerであり専用エンドポイントを持たない。FUNC-029（リアルタイム配信）は9章のWebSocketイベントとして定義する。

---

# 6. API詳細

## 6.1 API-001 会員登録 / API-002 ログイン / API-003〜005

Project 02のAPI-001〜005と同一パターンのため差分のみ記す（Request/Response shapeは`EC_Site/docs/10_API設計.md` 6.1〜6.5節と同一書式）。

| 差分項目 | 内容 |
| --- | --- |
| 登録時のrole | `role=user`, `status=active`で作成（REQ-001）。管理者はSeederでのみ作成する |
| トークン期限 | 8時間（Project 02は24時間 — 業務ツールのため短縮） |
| ログイン後の遷移先 | dataに`role`を含め、フロントが`user→/projects` / `admin→/admin/users`を出し分ける（G-02） |
| 主なエラー | API-001: E003（重複メール含む）/ API-002: E001（不一致・inactive共通）/ API-003〜005: E010, E003 |

---

## 6.2 API-006 参加プロジェクト一覧

| 項目 | 内容 |
| --- | --- |
| Method / Endpoint | GET `/projects` |
| 権限 | 認証済みユーザー |
| 関連テーブル | projects / project_members / tasks（未完了件数の導出） |
| 主なエラー | E010 |

Response Data（抜粋）:

```json
{
  "data": [
    {
      "id": 1,
      "name": "サンプルプロジェクト",
      "my_role": "owner",
      "status": "active",
      "member_count": 3,
      "open_task_count": 5
    }
  ]
}
```

自分が`project_members`に行を持つプロジェクトのみ返す（他人のプロジェクトは件数にも現れない — REQ-006）。`open_task_count`は`status != done`のCOUNT導出（`09_テーブル定義.md` 11章-5: 集計列は持たない）。

---

## 6.3 API-007 プロジェクト作成

| 項目 | 内容 |
| --- | --- |
| Method / Endpoint | POST `/projects` |
| 権限 | 認証済みユーザー |
| 関連テーブル | projects / project_members |
| 主なエラー | E003 / E010 |

Request:

```json
{ "name": "新規プロジェクト", "description": "説明（任意）" }
```

Response（201）: 作成された`project`（`my_role: "owner"`を含む）。

処理: 同一トランザクションで`projects`（status=active）と作成者のOwnerメンバーシップを作成する（BR-PRJ-001）。**Adminは実行不可（E002）** — 作成するとBR-PRJ-001により自動的にOwner=業務参加者になってしまうため（BR-PRM-004、`02_要件定義書.md` v1.1）。Validation: `name` required/max:100、`description` nullable/max:2000。

---

## 6.4 API-008〜010 プロジェクト詳細・編集・アーカイブ

| 操作 | Method | Endpoint | 権限 | 主なエラー |
| --- | --- | --- | --- | --- |
| 詳細取得 | GET | `/projects/{projectId}` | Member以上 | E007 |
| 編集 | PUT | `/projects/{projectId}` | Owner | E002 / E003 / E006 / E007 |
| アーカイブ/復元 | PATCH | `/projects/{projectId}/status` | Owner | E002 / E003 / E007 |

API-010 Request: `{ "status": "archived" }` または `{ "status": "active" }`（Validation: in:active,archived）。詳細取得のResponseには`my_role`と`status`を含め、フロントは`archived`ならArchived-bannerを表示し書込UIを無効化する（G-06）。編集はarchived時E006だが、API-010自体（復元）はarchivedでも実行可能（BR-PRJ-003）。

---

## 6.5 API-011〜014 メンバー管理

| 操作 | Method | Endpoint | 権限 | 主なエラー |
| --- | --- | --- | --- | --- |
| 一覧 | GET | `/projects/{projectId}/members` | Member以上 | E007 |
| 招待 | POST | `/projects/{projectId}/members` | Owner | E002 / E003 / E006 / E007 / E011 |
| ロール変更 | PATCH | `/projects/{projectId}/members/{userId}` | Owner | E002 / E003 / E006 / E007 |
| 除名・脱退 | DELETE | `/projects/{projectId}/members/{userId}` | Owner（`userId`=自分なら本人可=自主脱退） | E002 / E006 / E007 |

招待Request: `{ "email": "member@example.com" }` — 登録済みユーザーであることをService層で判定（未登録はE007、既参加はE011 — BR-PRJ-004）。ロール変更Request: `{ "role": "owner" }`（in:owner,member）。

**最後のOwner保護（BR-PRJ-002）**: ロール変更（owner→member）・除名・自主脱退のいずれでも、対象がプロジェクト唯一のOwnerの場合はE006を返す。文脈メッセージは`06_画面設計.md` 7章 補足2に従う。

**除名の連動処理（BR-PRJ-005）**: 同一トランザクションで当該ユーザーの担当タスクを`assignee_id=NULL`に更新する。操作ログ記録（API-POL-006）。

---

## 6.6 API-015 タスク一覧・検索

| 項目 | 内容 |
| --- | --- |
| Method / Endpoint | GET `/projects/{projectId}/tasks` |
| 権限 | Member以上 |
| 関連テーブル | tasks / users（担当者名） |
| 主なエラー | E007 / E010 |

Query:

| 項目 | 必須 | 内容 |
| --- | --- | --- |
| keyword | No | タイトル部分一致（REQ-013） |
| assignee_id | No | 担当者絞込（`unassigned`指定で担当なしのみ） |
| status | No | ステータス絞込 |

Response Data（抜粋）: カンバン描画用に全件をposition順のフラット配列で返す（3列への振り分けはフロントが行う）。

```json
{
  "data": [
    {
      "id": 10, "title": "API設計レビュー", "status": "in_progress", "position": 2048,
      "assignee": { "id": 3, "name": "山田太郎" },
      "due_date": "2026-07-19", "priority": "high",
      "comment_count": 2, "file_count": 1
    }
  ]
}
```

ページネーションは行わない（1プロジェクトのタスク数は運用上数百件規模を想定。超過時の対応は将来課題として`18_UAT.md`の負荷確認で判断）。

---

## 6.7 API-016 タスク作成 / API-017 詳細 / API-018 編集

| 操作 | Method | Endpoint | 権限 | 主なエラー |
| --- | --- | --- | --- | --- |
| 作成 | POST | `/projects/{projectId}/tasks` | Member以上 | E003 / E006 / E007 |
| 詳細 | GET | `/projects/{projectId}/tasks/{taskId}` | Member以上 | E007 |
| 編集 | PATCH | `/projects/{projectId}/tasks/{taskId}` | Member以上 | E003 / E006 / E007 |

作成/編集Request（共通フィールド）:

```json
{
  "title": "API設計レビュー",
  "description": "10章まで確認する",
  "assignee_id": 3,
  "due_date": "2026-07-19",
  "priority": "high",
  "status": "todo"
}
```

Validation: `title` required/max:200、`description` nullable/max:5000、`assignee_id` nullable + 当該プロジェクトのメンバーであること（Service判定、BR-TSK-003 — 非メンバー指定はE003）、`due_date` nullable/date（過去日許容、BR-TSK-004）、`priority` in:low,middle,high、`status` in:todo,in_progress,done。

処理: 作成時はstatus列の末尾position（`最大値+1024`、`09_テーブル定義.md` 11章-1）で追加。**担当者の設定・変更時、新担当者が操作者本人でなければ`task_assigned`通知を作成する**（BR-NTF-001。API-016/018共通の副作用 — FUNC-027）。

---

## 6.8 API-019 カンバン移動・並び替え（本システムのUI中核API）

| 項目 | 内容 |
| --- | --- |
| Method / Endpoint | PATCH `/projects/{projectId}/tasks/{taskId}/position` |
| 権限 | Member以上 |
| 関連テーブル | tasks |
| 主なエラー | E003 / E006 / E007 |

Request:

```json
{ "status": "in_progress", "before_task_id": 12, "after_task_id": 8 }
```

`before_task_id`/`after_task_id`はドロップ位置の前後タスク（列先頭なら`before_task_id: null`、末尾なら`after_task_id: null`）。**クライアントはpositionの数値を送らない** — 採番（中間値計算・リナンバリング判定）はサーバー側の責務とし、クライアントの古い画面状態に基づく誤った数値を受け取らない。

処理: 対象列をロックの上（`09_テーブル定義.md` 11章-1）、前後タスクの現在positionから新値を採番。列間移動時は`status`も更新する（FUNC-018/019を同一エンドポイントで実現 — `07_機能一覧.md` 9章の想定どおり）。**ステータス変更は通知を発火しない**（BR-NTF-001〜003のいずれにも該当しない）。

Response: 更新後の`task`（確定したpositionを含む。前後タスクが既に移動済みだった場合もサーバー採番が正となり、フロントは応答で自画面を補正する）。

---

## 6.9 API-020 タスク削除 / API-021〜023 コメント

| 操作 | Method | Endpoint | 権限 | 主なエラー |
| --- | --- | --- | --- | --- |
| タスク削除 | DELETE | `/projects/{projectId}/tasks/{taskId}` | Owner | E002 / E006 / E007 |
| コメント一覧 | GET | `.../tasks/{taskId}/comments` | Member以上 | E007 |
| コメント投稿 | POST | `.../tasks/{taskId}/comments` | Member以上 | E003 / E006 / E007 |
| コメント削除 | DELETE | `.../comments/{commentId}` | 投稿者本人 / Owner | E002 / E006 / E007 |

タスク削除: 同一トランザクションでコメント・ファイル行を連動削除し、S3オブジェクトも削除する（BR-TSK-007）。関連通知は`task_id`がSET NULLされ残る（REL-011）。操作ログ記録。

コメント投稿Request: `{ "body": "レビューしました" }`（required/max:2000）。**タスクに担当者がおり、かつ投稿者本人でなければ`task_commented`通知を作成**（BR-NTF-002）。削除権限: 本人以外はOwnerのみ（BR-CMT-002、MemberはE002）。編集APIは存在しない（BR-CMT-003）。

---

## 6.10 API-024〜027 ファイル

| 操作 | Method | Endpoint | 権限 | 主なエラー |
| --- | --- | --- | --- | --- |
| 一覧 | GET | `.../tasks/{taskId}/files` | Member以上 | E007 |
| アップロード | POST | `.../tasks/{taskId}/files` | Member以上 | E003 / E006 / E007 |
| ダウンロード | GET | `.../files/{fileId}/download` | Member以上 | E007 |
| 削除 | DELETE | `.../files/{fileId}` | アップロード者 / Owner | E002 / E006 / E007 |

アップロード（multipart/form-data）: フィールド名`file`。Validation: 10MB以下・許可種別（拡張子+MIME、BR-FIL-001）・タスクあたり20件以下（超過はE003）。保存先: `projects/{project_id}/tasks/{task_id}/{uuid}.{ext}`（BR-FIL-004）。

Response（アップロード成功、201）:

```json
{
  "success": true,
  "message": "ファイルをアップロードしました。",
  "data": { "id": 7, "original_name": "設計書.pdf", "size_bytes": 1048576, "mime_type": "application/pdf" }
}
```

ダウンロード: メンバーシップ判定（INC-002）通過後にファイルを返す。返却方式（サーバー経由ストリーム or 短命presigned URLへの302リダイレクト）は`14_セキュリティ設計.md`で確定する — いずれの場合も**S3の恒久URLは絶対に応答に含めない**（BR-FIL-002）。

削除: DB行とS3オブジェクトを両方削除（BR-FIL-003）。

---

## 6.11 API-028〜031 通知

| 操作 | Method | Endpoint | 権限 | 主なエラー |
| --- | --- | --- | --- | --- |
| 一覧 | GET | `/notifications`（Query: `unread_only`, `page`） | 本人 | E010 |
| 未読件数 | GET | `/notifications/unread-count` | 本人 | E010 |
| 個別既読 | PATCH | `/notifications/{id}/read` | 本人 | E007 |
| 一括既読 | PATCH | `/notifications/read-all` | 本人 | E010 |

一覧Response Data（抜粋）:

```json
{
  "data": [
    {
      "id": 55, "type": "task_assigned", "task_id": 10,
      "task_title": "API設計レビュー", "project_id": 1,
      "is_read": false, "created_at": "2026-07-18T09:00:00+09:00"
    }
  ],
  "meta": { "current_page": 1, "last_page": 3, "total": 42 }
}
```

`task_title`はスナップショット値（TBL-007）。`project_id`は**`tasks`とのJOINで導出する付加項目**（TBL-007に列は持たない）であり、`task_id`がNULL（タスク削除済み）の場合はnullとなる — このときフロントはクリック無効とし「タスクは削除されました」をインライン表示する（遷移しない。`03_ユースケース.md` UC-015 3-a / `06_画面設計.md` 5.9。G-07は直接URLアクセス用）。他人の通知IDを指定した既読化はE007（所有権スコープ、BR-NTF-004）。Reverb未使用時、フロントは`unread-count`を30秒間隔でポーリングする（BR-NTF-006のフォールバック）。

---

## 6.12 API-032〜035 Admin

| 操作 | Method | Endpoint | 権限 | 主なエラー |
| --- | --- | --- | --- | --- |
| ユーザー一覧 | GET | `/admin/users`（Query: `keyword`, `page`） | Admin | E002 |
| ユーザー無効化/有効化 | PATCH | `/admin/users/{id}/status` | Admin | E002 / E003 / E007 |
| 全プロジェクト一覧 | GET | `/admin/projects`（Query: `page`） | Admin | E002 |
| 強制アーカイブ | PATCH | `/admin/projects/{id}/archive` | Admin | E002 / E007 |

ユーザー無効化Request: `{ "status": "inactive" }`（in:active,inactive）。無効化されたユーザーはE001でログイン拒否、メンバーシップ・担当は保持（UC-017）。全プロジェクト一覧のResponseは名称・Owner名（`project_members`から導出）・メンバー数・状態のみで、**タスク・コメント・ファイルの内容は一切含めない**（BR-PRM-004）。強制アーカイブに復元APIはない（復元はOwnerのAPI-010のみ — UC-018）。いずれも操作ログ記録。

---

# 7. エラー仕様

| コード | 内容 | HTTPステータス |
| --- | --- | --- |
| E001 | ログイン失敗（inactive含む） | 401 |
| E002 | 権限エラー（メンバーだが権限不足 / 非Adminの管理API） | 403 |
| E003 | バリデーションエラー（ファイル制限・非メンバー担当者指定を含む） | 422 |
| E006 | 状態不整合（archivedへの書込、最後のOwner保護違反） | 409 |
| E007 | 対象データ未検出（非メンバーへの存在秘匿、親子不一致URL、未登録メール招待を含む） | 404 |
| E010 | 未認証 | 401 |
| E011 | 重複操作（既参加メンバーへの再招待） | 409 |

`02_要件定義書.md` 16章と同一。E002とE007の使い分け（BR-PRM-006）が本システムのエラー設計の核心: **対象の存在を知る権利がある者（メンバー）にのみE002を見せ、それ以外にはE007で存在ごと隠す**。

---

# 8. セキュリティ仕様

| 項目 | 内容 |
| --- | --- |
| CORS | SPAの配信オリジンのみ許可（NFR-013）。認証方式の詳細（トークン保管等）は`14_セキュリティ設計.md`で確定 |
| メンバーシップスコープ | プロジェクト資源の全クエリを`project_members`経由で絞る（NFR-012）。URLネスト（API-POL-004）で親子不一致も遮断 |
| ファイル保護 | S3はPrivateバケット。ダウンロードは権限判定つきAPI-026経由のみ（BR-FIL-002） |
| WebSocket認可 | private channelの購読認可はメンバーシップで判定（9章） |
| Audit | メンバー変更・削除系操作のログ記録（API-POL-006） |

---

# 9. WebSocket仕様（Laravel Reverb、bonus — FUNC-029）

## 9.1 チャンネル設計

| チャンネル | 種別 | 購読条件（認可） | 用途 |
| --- | --- | --- | --- |
| `user.{userId}` | private | 本人のみ（`userId`=認証ユーザー） | 通知のリアルタイム受信 |
| `project.{projectId}.board` | private | 当該プロジェクトのメンバーのみ（INC-002と同一判定） | カンバン変更の同期 |

認可は`/broadcasting/auth`（Sanctumトークン）で行い、判定はREST APIと同じPolicyを流用する — **HTTPで見えないものはWebSocketでも見えない**（NFR-012の一貫適用）。

## 9.2 イベント一覧

| イベント名 | チャンネル | ペイロード（抜粋） | 発火元 |
| --- | --- | --- | --- |
| `notification.created` | `user.{userId}` | `{ id, type, task_id, project_id, task_title, created_at }`（project_idはAPI-028と同じJOIN導出） | 通知作成（FUNC-027 / 028） |
| `board.task.created` | `project.{id}.board` | `{ task: {...} }` | API-016 |
| `board.task.updated` | `project.{id}.board` | `{ task: {...} }` | API-018 |
| `board.task.moved` | `project.{id}.board` | `{ task_id, status, position }` | API-019 |
| `board.task.deleted` | `project.{id}.board` | `{ task_id }` | API-020 |

## 9.3 フォールバック方針

| 項目 | 内容 |
| --- | --- |
| 配信失敗 | エラーとしない。DB上の通知・タスク状態が常に正（BR-NTF-006） |
| Reverb停止時 | フロントは接続失敗を検知して`unread-count`ポーリング（30秒）+ ボード再取得（画面フォーカス時）へ縮退。UI上の機能差は出さない（`06_画面設計.md` 5.4） |
| 自己イベント | 操作者本人のクライアントはAPI応答で既に反映済みのため、自分が発火したboardイベントは無視してよい（イベントに`actor_id`を含める） |

---

# 10. トレーサビリティ

`07_機能一覧.md`のFUNC-ID → 本書のAPI-ID/イベント → `12_詳細設計書.md`のController/Service/Policy → 試験仕様書の試験ケース、の順に一意に追跡できる。`06_画面設計.md` 8章のトレーサビリティ表には本書確定に伴いAPI-IDを追記した（同書v1.1改訂）。

---

# 11. まとめ

REST API 35本とWebSocketイベント5種を定義した。本書の設計上の核心は3点: ①URLネストによるメンバーシップスコープの宣言（API-POL-004）、②API-019のposition採番をサーバー責務とし、クライアントは前後関係（before/after）のみを申告する契約 — 楽観的UIと整合性の両立、③E002/E007の使い分け（存在秘匿）の全API一貫適用。`12_詳細設計書.md`ではこの3点をPolicy・Service疑似コードへ落とし込む。

---
