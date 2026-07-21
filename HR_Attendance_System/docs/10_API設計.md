# API設計

HR & Attendance System（勤怠管理システム）

---

# 文書管理情報

| 項目 | 内容 |
| --- | --- |
| システム名 | HR & Attendance System |
| 文書名 | API設計 |
| 文書番号 | DOC-010 |
| 作成者 | Nguyen Minh Tri |
| 作成日 | 2026/07/02 |
| バージョン | 1.3 |
| ステータス | Draft |

---

# 改訂履歴

| Version | 日付 | 作成者 | 内容 |
| --- | --- | --- | --- |
| 1.0 | 2026/07/02 | Nguyen Minh Tri | 初版作成 |
| 1.1 | 2026/07/02 | Nguyen Minh Tri | 整合性レビューによる修正：E003/E009のAPI対応範囲を修正、API-006にwork_hoursスナップショット方針を明記、BR-ATT-005追加、leave statusのCompletedを削除 |
| 1.2 | 2026/07/16 | Nguyen Minh Tri | E007の対象API誤りを修正。E007はRoute Model Bindingの単一ID検索（ModelNotFoundException）専用であり、一覧取得系（`paginate()`/`get()`）では0件でも200を返す方針（ch06 API-007/008で確立済み）。この基準でAPI-007/008/010/012からE007を削除し、実装で既にRoute Model Bindingを使っているAPI-011へE007を追加（コード側は元々正しく、ドキュメントが未追随だった）。 |
| 1.3 | 2026/07/21 | Nguyen Minh Tri | 6章のRequest/Response記載を全面的に`guide/`の実コードと突き合わせ、3件の実質的な問題を修正: ①フォーマット不統一（一部APIのみ literal JSON、他は項目名の表のみでenvelopeを示していなかった）を解消し、全APIをliteral JSON形式に統一。②API-008/010/011/014〜020にResponseが丸ごと欠落していたため追加。③API-003（`/auth/me`）が`role`をstring型と誤記していたが、実装は`role`/`department`/`shift`をネストしたオブジェクトとして返す（生モデルload）ため訂正。あわせて3.3.1節にページネーション共通形式を新設（API-007/008/010が参照）、API-015のRequestから未使用の`status`フィールドを削除、API-020が実はHTTPエンドポイントではなく`AuditLogService::record()`という内部メソッドである点を明記。 |

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
9. トレーサビリティ
10. まとめ

---

# 1. 本書の目的

本書は、HR & Attendance Systemで使用するREST APIの仕様を定義する。

本書では、画面設計、機能一覧、ER図、テーブル定義と整合するように、API ID、Endpoint、HTTP Method、Request、Response、権限、エラー、関連REQ/FUNC/SCRを明確にする。

対象技術はLaravel 12を想定し、FrontendはVite経由でAPIを呼び出す。

---

# 2. API設計方針

| 方針ID | 方針 | 内容 |
| --- | --- | --- |
| API-POL-001 | REST | Resource指向のURLを使用する。 |
| API-POL-002 | JSON | Request / Responseは原則JSON形式とする。 |
| API-POL-003 | Stateless | APIはHTTP sessionまたはtokenにより認証状態を判断する。 |
| API-POL-004 | 権限制御 | User / Manager / Adminごとにアクセス可否を制御する。 |
| API-POL-005 | Traceability | API IDはFUNC / REQ / UC / SCRと対応付ける。 |
| API-POL-006 | Audit | 重要操作はaudit_logsへ記録する。 |
| API-POL-007 | Validation | 入力値はLaravel FormRequest相当で検証する。 |

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
| Content-Type | POST / PUT / PATCH時必須 | `application/json` |
| Accept | 必須 | `application/json` |
| Authorization | 認証API以外必須 | `Bearer {access_token}` またはLaravel認証Cookie |

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
    "code": "E003",
    "message": "入力内容に誤りがあります。",
    "details": {}
  }
}
```

## 3.3.1 一覧系APIの共通ページネーション形式

一覧取得系（API-007/008/010等）はLaravelの`paginate()`をそのまま`data`に渡す実装のため、`data`は単純な配列ではなく以下の形（Laravel標準のページネーションJSON）になる。個々のAPI詳細（6章）では`items`部分の要素だけを示し、この形式は本節を参照する。

```json
{
  "success": true,
  "message": "OK",
  "data": {
    "current_page": 1,
    "data": [ { "...": "1件分の要素（6章の該当APIで形を定義）" } ],
    "first_page_url": "http://localhost/api/...?page=1",
    "from": 1,
    "last_page": 1,
    "last_page_url": "http://localhost/api/...?page=1",
    "per_page": 20,
    "to": 12,
    "total": 12
  }
}
```

## 3.4 HTTP Status

| Status | 用途 |
| --- | --- |
| 200 | 正常取得・正常更新 |
| 201 | 作成成功 |
| 204 | 削除・ログアウト成功 |
| 400 | 業務エラー |
| 401 | 未認証 |
| 403 | 権限エラー |
| 404 | データなし |
| 409 | 重複・状態不整合 |
| 422 | 入力エラー |
| 500 | サーバー・DBエラー |

## 3.5 日付・時刻形式

| 種別 | 形式 | 例 |
| --- | --- | --- |
| Date | `YYYY-MM-DD` | `2026-07-02` |
| Time | `HH:mm:ss` | `09:00:00` |
| DateTime | ISO 8601 | `2026-07-02T09:00:00+09:00` |

---

# 4. 認証・認可仕様

## 4.1 認証方式

Laravel Sanctumまたは同等の認証方式を想定する。

| 項目 | 内容 |
| --- | --- |
| Login ID | `email` |
| Password | `password` |
| Token期限 | 8時間 |
| Session Timeout | 30分無操作 |
| Password保存 | `password_hash`へハッシュ化して保存 |

## 4.2 Role別アクセス方針

| Role | 主な操作 |
| --- | --- |
| User | 自分の勤怠打刻、勤怠履歴確認、休暇申請、パスワード変更 |
| Manager | User操作に加え、部下の勤怠検索、休暇承認、レポート閲覧 |
| Admin | 全操作、マスタ管理、社員管理 |

---

# 5. API一覧

| API ID | Method | Endpoint | API名 | 権限 | 関連FUNC | 関連REQ | 関連SCR |
| --- | --- | --- | --- | --- | --- | --- | --- |
| API-001 | POST | `/auth/login` | ログイン | Public | FUNC-001 | REQ-001 / REQ-003 | SCR-001 |
| API-002 | POST | `/auth/logout` | ログアウト | User / Manager / Admin | FUNC-002 | REQ-002 | SCR-002 |
| API-003 | GET | `/auth/me` | ログインユーザー取得 | User / Manager / Admin | FUNC-003 | REQ-003 | SCR-002 |
| API-004 | POST | `/attendance/check-in` | 出勤打刻 | User / Manager / Admin | FUNC-004 | REQ-004 | SCR-003 |
| API-005 | POST | `/attendance/check-out` | 退勤打刻 | User / Manager / Admin | FUNC-005 | REQ-005 | SCR-003 |
| API-006 | GET | `/attendance/{id}/work-hours` | 勤務時間取得 | User / Manager / Admin | FUNC-006 | REQ-006 | SCR-003 |
| API-007 | GET | `/attendance/me` | 自分の勤怠履歴取得 | User / Manager / Admin | FUNC-007 | REQ-007 | SCR-004 |
| API-008 | GET | `/attendance` | 勤怠検索 | User / Manager / Admin | FUNC-008 | REQ-008 / REQ-012 | SCR-004 |
| API-009 | POST | `/leave-requests` | 休暇申請登録 | User / Manager / Admin | FUNC-009 | REQ-009 | SCR-005 |
| API-010 | GET | `/leave-requests` | 休暇申請一覧取得 | User / Manager / Admin | FUNC-010 | REQ-010 | SCR-005 / SCR-006 |
| API-011 | PATCH | `/leave-requests/{id}/approval` | 休暇承認・却下 | Manager / Admin | FUNC-011 | REQ-011 | SCR-006 |
| API-012 | GET | `/reports/monthly-attendance` | 月次レポート取得 | Manager / Admin | FUNC-012 | REQ-013 | SCR-010 |
| API-013 | GET | `/reports/monthly-attendance/csv` | 月次レポートCSV出力 | Manager / Admin | FUNC-013 | REQ-014 | SCR-010 |
| API-014 | POST | `/employees` | 社員登録 | Admin | FUNC-014 | REQ-015 | SCR-007 |
| API-015 | PUT | `/employees/{id}` | 社員編集 | Admin | FUNC-015 | REQ-016 | SCR-007 |
| API-016 | PATCH | `/employees/{id}/status` | 社員無効化・有効化 | Admin | FUNC-016 | REQ-017 | SCR-007 |
| API-017 | GET / POST / PUT / PATCH | `/departments` | 部署管理 | Admin | FUNC-017 | REQ-018 | SCR-008 |
| API-018 | GET / POST / PUT / PATCH | `/shifts` | シフト管理 | Admin | FUNC-018 | REQ-019 | SCR-009 |
| API-019 | PATCH | `/auth/password` | パスワード変更 | User / Manager / Admin | FUNC-019 | REQ-020 | SCR-002 |
| API-020 | (内部メソッド) | -（HTTPエンドポイントではない、6.20節参照） | 操作ログ記録 | System | FUNC-020 | REQ-021 | - |
| API-021 | GET | `/auth/session` | セッション状態確認 | User / Manager / Admin | FUNC-021 | REQ-003 | SCR-001 / SCR-002 |

---

# 6. API詳細

## 6.1 API-001 ログイン

| 項目 | 内容 |
| --- | --- |
| Method | POST |
| Endpoint | `/auth/login` |
| 権限 | Public |
| 関連テーブル | employees / roles |
| 主なエラー | E001 / E003 / E009 |

Request:

```json
{
  "email": "user@example.com",
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
    "employee": {
      "id": 1,
      "employee_id": "EMP001",
      "name": "Yamada Taro",
      "email": "user@example.com",
      "role": "user"
    }
  }
}
```

Validation:

| 項目 | ルール |
| --- | --- |
| email | required / email / max:255 |
| password | required / min:8 / max:20 |

## 6.2 API-002 ログアウト

| 項目 | 内容 |
| --- | --- |
| Method | POST |
| Endpoint | `/auth/logout` |
| 権限 | User / Manager / Admin |
| 関連テーブル | employees |
| 主なエラー | E010 |

Request:

```json
{}
```

Response:

```json
{
  "success": true,
  "message": "ログアウトしました。",
  "data": null
}
```

## 6.3 API-003 ログインユーザー取得

| 項目 | 内容 |
| --- | --- |
| Method | GET |
| Endpoint | `/auth/me` |
| 権限 | User / Manager / Admin |
| 関連テーブル | employees / roles / departments / shifts |
| 主なエラー | E002 / E010 |

Query:

| 項目 | 必須 | 内容 |
| --- | --- | --- |
| - | - | なし |

Response:

```json
{
  "success": true,
  "message": "OK",
  "data": {
    "id": 1,
    "employee_id": "EMP001",
    "name": "Yamada Taro",
    "email": "user@example.com",
    "role_id": 1,
    "department_id": 2,
    "shift_id": 1,
    "status": "active",
    "created_at": "2026-07-02T09:00:00+09:00",
    "updated_at": "2026-07-02T09:00:00+09:00",
    "role": {
      "id": 1,
      "role_code": "user",
      "role_name": "User",
      "status": "active",
      "created_at": "2026-07-02T09:00:00+09:00",
      "updated_at": "2026-07-02T09:00:00+09:00"
    },
    "department": {
      "id": 2,
      "department_code": "dept_dev",
      "department_name": "開発部",
      "status": "active",
      "created_at": "2026-07-02T09:00:00+09:00",
      "updated_at": "2026-07-02T09:00:00+09:00"
    },
    "shift": {
      "id": 1,
      "shift_code": "shift_normal",
      "shift_name": "標準勤務",
      "start_time": "09:00:00",
      "end_time": "18:00:00",
      "break_minutes": 60,
      "status": "active",
      "created_at": "2026-07-02T09:00:00+09:00",
      "updated_at": "2026-07-02T09:00:00+09:00"
    }
  }
}
```

**注（v1.3で訂正）**: 実装（`guide/04_認証_Auth.html` AuthController::me）は`$request->user()->load(['role','department','shift'])`をそのまま返す — Employeeモデルの全カラム（`password_hash`は`$hidden`により除外）+ ロード済み関連を**そのまま**返す設計であり、API-001（ログイン）のように`role`をコード文字列だけに絞った整形は行わない。したがって`role_id`/`department_id`/`shift_id`（生カラム）と`role`/`department`/`shift`（関連オブジェクト）が同時に存在する。API-001のレスポンスと`employee`/`role`の形が異なるのは意図的な差（ログイン=軽量、me=フルプロフィール）であり、フロントエンドは2つのAPIで異なる形を扱う前提で実装する（`public/js/app.js`は`me.data.role.role_code`のようにネストで参照している）。

## 6.4 API-004 出勤打刻

| 項目 | 内容 |
| --- | --- |
| Method | POST |
| Endpoint | `/attendance/check-in` |
| 権限 | User / Manager / Admin |
| 関連テーブル | attendance_records |
| 主なエラー | E003 / E004 / E009 / E010 |

Request:

```json
{
  "work_date": "2026-07-02",
  "check_in_time": "09:00:00"
}
```

Response:

```json
{
  "success": true,
  "message": "出勤打刻を登録しました。",
  "data": {
    "id": 10,
    "employee_id": 1,
    "work_date": "2026-07-02",
    "check_in_time": "09:00:00",
    "check_out_time": null,
    "work_hours": null,
    "status": "CheckedIn",
    "created_at": "2026-07-02T09:00:00+09:00",
    "updated_at": "2026-07-02T09:00:00+09:00"
  }
}
```

HTTP Status: 201（新規作成、`guide/06_勤怠打刻.html` AttendanceController::checkIn）

Business Rule:

| ルール | 内容 |
| --- | --- |
| BR-ATT-001 | 同一社員・同一勤務日に複数の出勤打刻は不可 |
| BR-ATT-002 | 打刻対象は原則ログインユーザー本人 |

## 6.5 API-005 退勤打刻

| 項目 | 内容 |
| --- | --- |
| Method | POST |
| Endpoint | `/attendance/check-out` |
| 権限 | User / Manager / Admin |
| 関連テーブル | attendance_records |
| 主なエラー | E003 / E004 / E005 / E009 / E010 |

Request:

```json
{
  "work_date": "2026-07-02",
  "check_out_time": "18:00:00"
}
```

Response:

```json
{
  "success": true,
  "message": "退勤打刻を登録しました。",
  "data": {
    "id": 10,
    "employee_id": 1,
    "work_date": "2026-07-02",
    "check_in_time": "09:00:00",
    "check_out_time": "18:00:00",
    "work_hours": 8.00,
    "status": "CheckedOut",
    "created_at": "2026-07-02T09:00:00+09:00",
    "updated_at": "2026-07-02T18:00:00+09:00"
  }
}
```

HTTP Status: 200（`guide/06_勤怠打刻.html` AttendanceController::checkOut）

Business Rule:

| ルール | 内容 |
| --- | --- |
| BR-ATT-003 | 出勤打刻がない場合は退勤打刻不可 |
| BR-ATT-004 | 退勤時刻は出勤時刻より後であること |
| BR-ATT-005 | work_hours = (check_out_time − check_in_time) − shifts.break_minutes として算出し、確定値としてattendance_recordsへ保存する |

## 6.6 API-006 勤務時間取得

| 項目 | 内容 |
| --- | --- |
| Method | GET |
| Endpoint | `/attendance/{id}/work-hours` |
| 権限 | User / Manager / Admin |
| 関連テーブル | attendance_records / shifts |
| 主なエラー | E003 / E007 / E010 |

Path:

| 項目 | 必須 | 内容 |
| --- | --- | --- |
| id | 必須 | 勤怠記録ID |

Response:

```json
{
  "success": true,
  "message": "OK",
  "data": {
    "attendance_id": 10,
    "work_hours": 8.00,
    "break_minutes": 60
  }
}
```

`work_hours`は退勤登録時にattendance_recordsへ保存された確定値（シフト変更後も再計算しない）。`break_minutes`は参考情報として現在のシフトの休憩時間を返すのみ（`work_hours`の再計算には使用しない、`guide/参考_完成コード_ch01-09.html` AttendanceController::workHours）。

## 6.7 API-007 自分の勤怠履歴取得

| 項目 | 内容 |
| --- | --- |
| Method | GET |
| Endpoint | `/attendance/me` |
| 権限 | User / Manager / Admin |
| 関連テーブル | attendance_records |
| 主なエラー | E003 / E009 / E010 |

Query:

| 項目 | 必須 | 内容 |
| --- | --- | --- |
| target_month | 任意 | 対象月。例: `2026-07` |
| from_date | 任意 | 開始日 |
| to_date | 任意 | 終了日 |
| page | 任意 | ページ番号 |

Response:

3.3.1節のページネーション共通形式（`data.data`が配列本体）。1要素の形は6.4/6.5と同じ`attendance_records`の全カラム:

```json
{
  "success": true,
  "message": "OK",
  "data": {
    "current_page": 1,
    "data": [
      {
        "id": 10,
        "employee_id": 1,
        "work_date": "2026-07-02",
        "check_in_time": "09:00:00",
        "check_out_time": "18:00:00",
        "work_hours": 8.00,
        "status": "CheckedOut",
        "created_at": "2026-07-02T09:00:00+09:00",
        "updated_at": "2026-07-02T18:00:00+09:00"
      }
    ],
    "per_page": 20,
    "total": 1
  }
}
```

（`guide/参考_完成コード_ch01-09.html` AttendanceService::myAttendance — `paginate(20)`）

## 6.8 API-008 勤怠検索

| 項目 | 内容 |
| --- | --- |
| Method | GET |
| Endpoint | `/attendance` |
| 権限 | User / Manager / Admin |
| 関連テーブル | attendance_records / employees / departments |
| 主なエラー | E002 / E003 / E009 / E010 |

Query:

| 項目 | 必須 | 内容 |
| --- | --- | --- |
| employee_id | 任意 | 社員ID |
| department_id | 任意 | 部署ID |
| from_date | 任意 | 開始日 |
| to_date | 任意 | 終了日 |
| status | 任意 | 勤怠状態 |

Response:

3.3.1節のページネーション共通形式。1要素は6.7と同じ`attendance_records`カラムに`employee`（申請者情報）を追加:

```json
{
  "success": true,
  "message": "OK",
  "data": {
    "current_page": 1,
    "data": [
      {
        "id": 10,
        "employee_id": 1,
        "work_date": "2026-07-02",
        "check_in_time": "09:00:00",
        "check_out_time": "18:00:00",
        "work_hours": 8.00,
        "status": "CheckedOut",
        "employee": { "id": 1, "employee_id": "EMP001", "name": "Yamada Taro" }
      }
    ],
    "per_page": 20,
    "total": 1
  }
}
```

（`guide/参考_完成コード_ch01-09.html` AttendanceController::search — `AttendanceService::search()`が`with('employee')`）

Authority Rule:

| Role | 検索可能範囲 |
| --- | --- |
| User | 自分のみ |
| Manager | 自部署または承認対象社員 |
| Admin | 全社員 |

## 6.9 API-009 休暇申請登録

| 項目 | 内容 |
| --- | --- |
| Method | POST |
| Endpoint | `/leave-requests` |
| 権限 | User / Manager / Admin |
| 関連テーブル | leave_requests |
| 主なエラー | E003 / E009 / E010 |

Request:

```json
{
  "leave_type": "paid_leave",
  "start_date": "2026-07-10",
  "end_date": "2026-07-10",
  "reason": "私用のため"
}
```

Response:

```json
{
  "success": true,
  "message": "休暇申請を登録しました。",
  "data": {
    "id": 5,
    "employee_id": 1,
    "leave_type": "paid_leave",
    "start_date": "2026-07-10",
    "end_date": "2026-07-10",
    "reason": "私用のため",
    "status": "Pending",
    "approved_by": null,
    "approved_at": null,
    "comment": null,
    "created_at": "2026-07-02T09:00:00+09:00",
    "updated_at": "2026-07-02T09:00:00+09:00"
  }
}
```

HTTP Status: 201（`guide/07_休暇申請.html` LeaveRequestController::store）

Validation:

| 項目 | ルール |
| --- | --- |
| leave_type | required / in:paid_leave,absence,late,early_leave |
| start_date | required / date |
| end_date | required / date / after_or_equal:start_date |
| reason | required / max:500 |

## 6.10 API-010 休暇申請一覧取得

| 項目 | 内容 |
| --- | --- |
| Method | GET |
| Endpoint | `/leave-requests` |
| 権限 | User / Manager / Admin |
| 関連テーブル | leave_requests / employees |
| 主なエラー | E010 |

Query:

| 項目 | 必須 | 内容 |
| --- | --- | --- |
| status | 任意 | Pending / Approved / Rejected |
| employee_id | 任意 | 社員ID |
| from_date | 任意 | 開始日 |
| to_date | 任意 | 終了日 |

Response:

3.3.1節のページネーション共通形式。1要素は6.9と同じ`leave_requests`カラムに`employee`（申請者）・`approver`（承認者、未承認はnull）を追加:

```json
{
  "success": true,
  "message": "OK",
  "data": {
    "current_page": 1,
    "data": [
      {
        "id": 5,
        "employee_id": 1,
        "leave_type": "paid_leave",
        "start_date": "2026-07-10",
        "end_date": "2026-07-10",
        "reason": "私用のため",
        "status": "Pending",
        "approved_by": null,
        "approved_at": null,
        "comment": null,
        "employee": { "id": 1, "employee_id": "EMP001", "name": "Yamada Taro" },
        "approver": null
      }
    ],
    "per_page": 20,
    "total": 1
  }
}
```

（`guide/参考_完成コード_ch01-09.html` LeaveRequestController::index — `LeaveRequestService::list()`が`with(['employee','approver'])`）

Authority Rule:

| Role | 取得可能範囲 |
| --- | --- |
| User | 自分の申請 |
| Manager | 自分の申請と承認対象申請 |
| Admin | 全申請 |

## 6.11 API-011 休暇承認・却下

| 項目 | 内容 |
| --- | --- |
| Method | PATCH |
| Endpoint | `/leave-requests/{id}/approval` |
| 権限 | Manager / Admin |
| 関連テーブル | leave_requests / audit_logs |
| 主なエラー | E002 / E006 / E007 / E009 / E010 |

Request:

```json
{
  "action": "approve",
  "comment": "承認します。"
}
```

Validation:

| 項目 | ルール |
| --- | --- |
| action | required / in:approve,reject |
| comment | nullable / max:500 |

Response:

```json
{
  "success": true,
  "message": "申請を承認しました。",
  "data": {
    "id": 5,
    "employee_id": 1,
    "leave_type": "paid_leave",
    "start_date": "2026-07-10",
    "end_date": "2026-07-10",
    "reason": "私用のため",
    "status": "Approved",
    "approved_by": 2,
    "approved_at": "2026-07-02T10:00:00+09:00",
    "comment": "承認します。",
    "updated_at": "2026-07-02T10:00:00+09:00"
  }
}
```

`message`は結果により動的に変わる（`action=reject`時は`"申請を却下しました。"`、`status`は`Rejected`）。`guide/参考_完成コード_ch01-09.html` LeaveRequestController::approval。

Business Rule:

| ルール | 内容 |
| --- | --- |
| BR-LEV-001 | `Pending`以外の申請は承認・却下不可 |
| BR-LEV-002 | 承認者はManagerまたはAdmin |
| BR-LEV-003 | 処理結果をaudit_logsへ記録 |

## 6.12 API-012 月次レポート取得

| 項目 | 内容 |
| --- | --- |
| Method | GET |
| Endpoint | `/reports/monthly-attendance` |
| 権限 | Manager / Admin |
| 関連テーブル | attendance_records / employees / departments |
| 主なエラー | E002 / E003 / E009 / E010 |

Query:

| 項目 | 必須 | 内容 |
| --- | --- | --- |
| target_month | 必須 | 対象月。例: `2026-07` |
| department_id | 任意 | 部署ID |
| employee_id | 任意 | 社員ID |

Response:

```json
{
  "success": true,
  "message": "OK",
  "data": {
    "target_month": "2026-07",
    "summary": {
      "employee_count": 2,
      "total_work_days": 15,
      "total_hours": 120.00
    },
    "items": [
      {
        "employee_id": "EMP001",
        "name": "Yamada Taro",
        "department": "開発部",
        "work_days": 8,
        "total_hours": 64.00,
        "leave_days": 1,
        "late_early_count": 0
      }
    ]
  }
}
```

`items`の各要素は都度DB集計した結果（`attendance_records`のスナップショット`work_hours`合算 + 承認済み`leave_requests`から算出、テーブルへの非正規化保存はしない）。`guide/参考_完成コード_ch01-09.html` ReportService::monthly / summarizeEmployee。

## 6.13 API-013 月次レポートCSV出力

| 項目 | 内容 |
| --- | --- |
| Method | GET |
| Endpoint | `/reports/monthly-attendance/csv` |
| 権限 | Manager / Admin |
| 関連テーブル | attendance_records / employees / audit_logs |
| 主なエラー | E002 / E008 / E009 / E010 |

Query:

| 項目 | 必須 | 内容 |
| --- | --- | --- |
| target_month | 必須 | 対象月 |
| department_id | 任意 | 部署ID |
| employee_id | 任意 | 社員ID |

Response:

| 項目 | 内容 |
| --- | --- |
| Content-Type | `text/csv` |
| File Name | `attendance_YYYYMM.csv` |

## 6.14 API-014 社員登録

| 項目 | 内容 |
| --- | --- |
| Method | POST |
| Endpoint | `/employees` |
| 権限 | Admin |
| 関連テーブル | employees / audit_logs |
| 主なエラー | E002 / E003 / E009 / E010 |

Request:

```json
{
  "employee_id": "EMP001",
  "name": "Yamada Taro",
  "email": "user@example.com",
  "password": "password123",
  "role_id": 1,
  "department_id": 1,
  "shift_id": 1
}
```

Validation:

| 項目 | ルール |
| --- | --- |
| employee_id | required / max:50 / unique:employees |
| name | required / max:100 |
| email | required / email / max:255 / unique:employees |
| password | required / min:8 / max:20 |
| role_id | required / exists:roles,id |
| department_id | required / exists:departments,id |
| shift_id | nullable / exists:shifts,id |

Response:

```json
{
  "success": true,
  "message": "社員を登録しました。",
  "data": {
    "id": 5,
    "employee_id": "EMP001",
    "name": "Yamada Taro",
    "email": "user@example.com",
    "role_id": 1,
    "department_id": 1,
    "shift_id": 1,
    "status": "active",
    "created_at": "2026-07-02T09:00:00+09:00",
    "updated_at": "2026-07-02T09:00:00+09:00"
  }
}
```

HTTP Status: 201。`password`/`password_hash`はレスポンスに含まれない（`$hidden`、`guide/参考_完成コード_ch01-09.html` EmployeeController::store）。

## 6.15 API-015 社員編集

| 項目 | 内容 |
| --- | --- |
| Method | PUT |
| Endpoint | `/employees/{id}` |
| 権限 | Admin |
| 関連テーブル | employees / audit_logs |
| 主なエラー | E002 / E003 / E009 / E010 |

Request:

```json
{
  "name": "Yamada Taro",
  "email": "user@example.com",
  "role_id": 1,
  "department_id": 1,
  "shift_id": 1
}
```

**注（v1.3で訂正）**: `status`は本APIのRequestに含めない — `UpdateEmployeeRequest::rules()`が検証対象にしておらず、`EmployeeService::update()`も`status`をセットしない（実装は`name`/`email`/`role_id`/`department_id`/`shift_id`のみ`forceFill`）。無効化・有効化は必ずAPI-016（別エンドポイント）を使う。

Validation:

| 項目 | ルール |
| --- | --- |
| name | required / max:100 |
| email | required / email / max:255 / unique:employees（自分自身は除外） |
| role_id | required / exists:roles,id |
| department_id | required / exists:departments,id |
| shift_id | nullable / exists:shifts,id |

Response:

```json
{
  "success": true,
  "message": "社員情報を更新しました。",
  "data": {
    "id": 5,
    "employee_id": "EMP001",
    "name": "Yamada Taro",
    "email": "user@example.com",
    "role_id": 1,
    "department_id": 1,
    "shift_id": 1,
    "status": "active",
    "created_at": "2026-07-02T09:00:00+09:00",
    "updated_at": "2026-07-02T10:00:00+09:00"
  }
}
```

（`guide/05_社員・部署・シフト管理.html` EmployeeController::update）

## 6.16 API-016 社員無効化・有効化

| 項目 | 内容 |
| --- | --- |
| Method | PATCH |
| Endpoint | `/employees/{id}/status` |
| 権限 | Admin |
| 関連テーブル | employees / audit_logs |
| 主なエラー | E002 / E009 / E010 |

Request:

```json
{
  "status": "inactive"
}
```

Response:

```json
{
  "success": true,
  "message": "社員の状態を更新しました。",
  "data": {
    "id": 5,
    "employee_id": "EMP001",
    "name": "Yamada Taro",
    "email": "user@example.com",
    "role_id": 1,
    "department_id": 1,
    "shift_id": 1,
    "status": "inactive",
    "created_at": "2026-07-02T09:00:00+09:00",
    "updated_at": "2026-07-02T10:00:00+09:00"
  }
}
```

（`guide/05_社員・部署・シフト管理.html` EmployeeController::setStatus）

Business Rule:

| ルール | 内容 |
| --- | --- |
| BR-EMP-001 | 物理削除は行わず`status`で無効化する |

## 6.17 API-017 部署管理

| 操作 | Method | Endpoint |
| --- | --- | --- |
| 一覧取得 | GET | `/departments` |
| 登録 | POST | `/departments` |
| 編集 | PUT | `/departments/{id}` |
| 無効化・有効化 | PATCH | `/departments/{id}/status` |

| 項目 | 内容 |
| --- | --- |
| 権限 | Admin |
| 関連テーブル | departments / audit_logs |
| 主なエラー | E002 / E003 / E009 / E010 |

Request Example（登録・編集共通）:

```json
{
  "department_code": "dept_hr",
  "department_name": "人事部"
}
```

`status`は登録・編集のRequestに含めない（社員と同じ理由 — 無効化・有効化は必ず`PATCH .../status`専用で行う。`guide/参考_完成コード_ch01-09.html` DepartmentController）。

Response（操作ごと）:

| 操作 | message | HTTP Status |
| --- | --- | --- |
| 一覧取得 | `"OK"` | 200 |
| 登録 | `"部署を登録しました。"` | 201 |
| 編集 | `"部署情報を更新しました。"` | 200 |
| 無効化・有効化 | `"部署の状態を更新しました。"` | 200 |

```json
{
  "success": true,
  "message": "部署を登録しました。",
  "data": {
    "id": 3,
    "department_code": "dept_hr",
    "department_name": "人事部",
    "status": "active",
    "created_at": "2026-07-02T09:00:00+09:00",
    "updated_at": "2026-07-02T09:00:00+09:00"
  }
}
```

一覧取得の`data`は上記オブジェクトの配列（`Department::orderBy('department_name')->get()`、ページネーションなし — マスタ件数が少ないため）。

## 6.18 API-018 シフト管理

| 操作 | Method | Endpoint |
| --- | --- | --- |
| 一覧取得 | GET | `/shifts` |
| 登録 | POST | `/shifts` |
| 編集 | PUT | `/shifts/{id}` |
| 無効化・有効化 | PATCH | `/shifts/{id}/status` |

| 項目 | 内容 |
| --- | --- |
| 権限 | Admin |
| 関連テーブル | shifts / audit_logs |
| 主なエラー | E002 / E003 / E009 / E010 |

Request Example（登録・編集共通）:

```json
{
  "shift_code": "shift_normal",
  "shift_name": "標準勤務",
  "start_time": "09:00:00",
  "end_time": "18:00:00",
  "break_minutes": 60
}
```

`status`は登録・編集のRequestに含めない（部署と同じ理由。`guide/参考_完成コード_ch01-09.html` ShiftController）。

Response（操作ごと）:

| 操作 | message | HTTP Status |
| --- | --- | --- |
| 一覧取得 | `"OK"` | 200 |
| 登録 | `"シフトを登録しました。"` | 201 |
| 編集 | `"シフト情報を更新しました。"` | 200 |
| 無効化・有効化 | `"シフトの状態を更新しました。"` | 200 |

```json
{
  "success": true,
  "message": "シフトを登録しました。",
  "data": {
    "id": 2,
    "shift_code": "shift_normal",
    "shift_name": "標準勤務",
    "start_time": "09:00:00",
    "end_time": "18:00:00",
    "break_minutes": 60,
    "status": "active",
    "created_at": "2026-07-02T09:00:00+09:00",
    "updated_at": "2026-07-02T09:00:00+09:00"
  }
}
```

一覧取得の`data`は上記オブジェクトの配列（`Shift::orderBy('start_time')->get()`、ページネーションなし）。

## 6.19 API-019 パスワード変更

| 項目 | 内容 |
| --- | --- |
| Method | PATCH |
| Endpoint | `/auth/password` |
| 権限 | User / Manager / Admin |
| 関連テーブル | employees |
| 主なエラー | E003 / E009 / E010 |

Request:

```json
{
  "current_password": "password123",
  "new_password": "newPassword123",
  "new_password_confirmation": "newPassword123"
}
```

Validation:

| 項目 | ルール |
| --- | --- |
| current_password | required |
| new_password | required / min:8 / max:20 / confirmed |

Response:

```json
{
  "success": true,
  "message": "パスワードを変更しました。",
  "data": null
}
```

`current_password`不一致時はE003（`guide/参考_完成コード_ch01-09.html` AuthController::updatePassword）。

## 6.20 API-020 操作ログ記録

**注（v1.3で訂正）**: 「API-020」という番号を便宜上振っているが、これはREST HTTPエンドポイントではない — `guide/`の実装には対応するController・route定義が一切存在しない。実体は`AuditLogService::record()`という**内部メソッド**で、各Serviceが業務処理の中から直接呼び出す（例: `MemberService`/`EmployeeService`/`LeaveRequestService`が`$this->auditLog->record($actor, 'employee_updated', 'employees', $employee->id)`のように使う）。HTTPリクエストとして外部から呼べる`POST /audit-logs`は存在しない。

| 項目 | 内容 |
| --- | --- |
| 呼び出し形態 | 内部メソッド呼び出し（`AuditLogService::record()`）、HTTPエンドポイントではない |
| 権限 | -（HTTP経由で外部公開しない） |
| 関連テーブル | audit_logs |

メソッドシグネチャ（`guide/参考_完成コード_ch01-09.html` AuditLogService::record、戻り値なし）:

```php
public function record(Employee $actor, string $action, string $targetType, int $targetId): void
```

| 引数 | 内容 |
| --- | --- |
| actor | 操作を行ったEmployee |
| action | 例: `employee_updated` / `leave_request_approved` |
| targetType | 対象テーブル名（例: `employees`） |
| targetId | 対象レコードID |

`ip_address`/`result`は現在の実装には存在しない（旧版のRequest例が想定していたが未実装。将来追加する場合は本節を更新すること）。

## 6.21 API-021 セッション状態確認

| 項目 | 内容 |
| --- | --- |
| Method | GET |
| Endpoint | `/auth/session` |
| 権限 | User / Manager / Admin |
| 関連テーブル | employees |
| 主なエラー | E010 |

Response:

```json
{
  "success": true,
  "message": "OK",
  "data": {
    "authenticated": true,
    "employee": {
      "id": 1,
      "employee_id": "EMP001",
      "name": "Yamada Taro",
      "email": "user@example.com",
      "role_id": 1,
      "department_id": 2,
      "shift_id": 1,
      "status": "active"
    }
  }
}
```

**注（v1.3で訂正）**: `expires_at`は現在の実装（`guide/参考_完成コード_ch01-09.html` AuthController::session）には存在しない — `authenticated`と`employee`（role/department/shiftはロードしない生モデル。6.3節`/auth/me`より軽量）の2項目のみを返す。`auth:sanctum`を通過できた時点で有効なので、期限を別途返す設計にしていない。トークン期限自体は4.1節の8時間固定であり、フロントは401（E010）を受けたら再ログインへ誘導する方式（`localStorage`のtoken削除 + `/login`へredirect）を取る。

---

# 7. エラー仕様

| Error ID | HTTP Status | 内容 | 対象API |
| --- | --- | --- | --- |
| E001 | 401 | ログイン失敗 | API-001 |
| E002 | 403 | 権限エラー | API-003 / API-008 / API-011 / API-012 / API-013 / API-014〜API-018 |
| E003 | 422 | 入力エラー | API-001 / API-004 / API-005 / API-007〜API-009 / API-012 / API-014〜API-019 |
| E004 | 409 | 二重打刻 | API-004 / API-005 |
| E005 | 409 | 出勤打刻なし退勤 | API-005 |
| E006 | 409 | 処理済み申請の更新 | API-011 |
| E007 | 404 | データなし（Route Model Bindingが指定IDを検索して0件） | API-006 / API-011 |
| E008 | 500 | CSV出力失敗 | API-013 |
| E009 | 500 | DBエラー | API-004 / API-005 / API-007 / API-008 / API-009 / API-011 / API-012 / API-013〜API-020 |
| E010 | 401 | セッションタイムアウト | API-002〜API-021 |

---

# 8. セキュリティ仕様

| 項目 | 内容 |
| --- | --- |
| Authentication | Laravel Sanctumまたは同等方式 |
| Authorization | Role Based Access Control |
| Password | Hash化して保存 |
| CSRF | Cookie認証の場合はCSRF対策を有効化 |
| CORS | Frontend Originのみ許可 |
| Validation | すべての入力値をサーバー側で検証 |
| Audit | 承認、CSV出力、マスタ更新、社員更新を記録 |
| Rate Limit | Login APIは試行回数制限を設定 |

---

# 9. トレーサビリティ

| API ID | 関連FUNC | 関連REQ | 関連UC | 関連BF | 関連SCR | 関連Table |
| --- | --- | --- | --- | --- | --- | --- |
| API-001 | FUNC-001 | REQ-001 / REQ-003 | UC-001 | BF-001 | SCR-001 | employees / roles |
| API-002 | FUNC-002 | REQ-002 | UC-002 | BF-001 | SCR-002 | employees |
| API-003 | FUNC-003 | REQ-003 | UC-001 | BF-001 | SCR-002 | employees / roles |
| API-004 | FUNC-004 | REQ-004 | UC-003 | BF-002 | SCR-003 | attendance_records |
| API-005 | FUNC-005 | REQ-005 | UC-004 | BF-002 | SCR-003 | attendance_records |
| API-006 | FUNC-006 | REQ-006 | UC-004 | BF-002 | SCR-003 | attendance_records / shifts |
| API-007 | FUNC-007 | REQ-007 | UC-005 | BF-003 | SCR-004 | attendance_records |
| API-008 | FUNC-008 | REQ-008 / REQ-012 | UC-005 / UC-009 | BF-003 | SCR-004 | attendance_records / employees |
| API-009 | FUNC-009 | REQ-009 | UC-006 | BF-004 | SCR-005 | leave_requests |
| API-010 | FUNC-010 | REQ-010 | UC-007 | BF-004 | SCR-005 / SCR-006 | leave_requests |
| API-011 | FUNC-011 | REQ-011 | UC-008 | BF-005 | SCR-006 | leave_requests / audit_logs |
| API-012 | FUNC-012 | REQ-013 | UC-010 | BF-006 | SCR-010 | attendance_records / employees |
| API-013 | FUNC-013 | REQ-014 | UC-011 | BF-006 | SCR-010 | attendance_records / audit_logs |
| API-014 | FUNC-014 | REQ-015 | UC-012 | BF-007 | SCR-007 | employees / audit_logs |
| API-015 | FUNC-015 | REQ-016 | UC-012 | BF-007 | SCR-007 | employees / audit_logs |
| API-016 | FUNC-016 | REQ-017 | UC-012 | BF-007 | SCR-007 | employees / audit_logs |
| API-017 | FUNC-017 | REQ-018 | UC-013 | BF-008 | SCR-008 | departments / audit_logs |
| API-018 | FUNC-018 | REQ-019 | UC-014 | BF-009 | SCR-009 | shifts / audit_logs |
| API-019 | FUNC-019 | REQ-020 | UC-015 | BF-001 | SCR-002 | employees |
| API-020 | FUNC-020 | REQ-021 | UC-016 | BF-010 | - | audit_logs |
| API-021 | FUNC-021 | REQ-003 | UC-001 | BF-001 | SCR-001 / SCR-002 | employees |

---

# 10. まとめ

本書では、HR & Attendance SystemのAPIとして、認証、勤怠、休暇申請、レポート、社員管理、部署管理、シフト管理、監査ログに関するAPI-001〜API-021を定義した。

本API設計は、機能一覧、画面設計、ER図、テーブル定義と対応しており、以降の基本設計、詳細設計、実装、テスト仕様書の基準とする。
