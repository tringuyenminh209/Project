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
| バージョン | 1.2 |
| ステータス | Draft |

---

# 改訂履歴

| Version | 日付 | 作成者 | 内容 |
| --- | --- | --- | --- |
| 1.0 | 2026/07/02 | Nguyen Minh Tri | 初版作成 |
| 1.1 | 2026/07/02 | Nguyen Minh Tri | 整合性レビューによる修正：E003/E009のAPI対応範囲を修正、API-006にwork_hoursスナップショット方針を明記、BR-ATT-005追加、leave statusのCompletedを削除 |
| 1.2 | 2026/07/16 | Nguyen Minh Tri | E007の対象API誤りを修正。E007はRoute Model Bindingの単一ID検索（ModelNotFoundException）専用であり、一覧取得系（`paginate()`/`get()`）では0件でも200を返す方針（ch06 API-007/008で確立済み）。この基準でAPI-007/008/010/012からE007を削除し、実装で既にRoute Model Bindingを使っているAPI-011へE007を追加（コード側は元々正しく、ドキュメントが未追随だった）。 |

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
| API-020 | POST | `/audit-logs` | 操作ログ記録 | System | FUNC-020 | REQ-021 | - |
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

Response Data:

| 項目 | 型 | 内容 |
| --- | --- | --- |
| id | number | 社員ID |
| employee_id | string | 社員番号 |
| name | string | 氏名 |
| email | string | メールアドレス |
| role | string | 権限 |
| department | object | 部署 |
| shift | object | シフト |

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

Response Data:

| 項目 | 型 | 内容 |
| --- | --- | --- |
| id | number | 勤怠記録ID |
| work_date | date | 勤務日 |
| check_in_time | time | 出勤時刻 |
| status | string | `CheckedIn` |

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

Response Data:

| 項目 | 型 | 内容 |
| --- | --- | --- |
| id | number | 勤怠記録ID |
| work_date | date | 勤務日 |
| check_in_time | time | 出勤時刻 |
| check_out_time | time | 退勤時刻 |
| work_hours | number | 勤務時間 |
| status | string | `CheckedOut` |

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

Response Data:

| 項目 | 型 | 内容 |
| --- | --- | --- |
| attendance_id | number | 勤怠記録ID |
| work_hours | number | 勤務時間（退勤登録時にattendance_recordsへ保存された確定値。シフト変更後も再計算しない） |
| break_minutes | number | 参考情報として現在のシフトの休憩時間を返す（work_hoursの再計算には使用しない） |

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

Response Data:

| 項目 | 型 | 内容 |
| --- | --- | --- |
| items | array | 勤怠履歴一覧 |
| total | number | 件数 |

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

Response Data:

| 項目 | 型 | 内容 |
| --- | --- | --- |
| id | number | 休暇申請ID |
| status | string | `Pending` |

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

Response Data:

| 項目 | 型 | 内容 |
| --- | --- | --- |
| target_month | string | 対象月 |
| summary | object | 集計情報 |
| items | array | 社員別勤怠一覧 |

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
  "shift_id": 1,
  "status": "active"
}
```

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

Request Example:

```json
{
  "department_code": "dept_hr",
  "department_name": "人事部",
  "status": "active"
}
```

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

Request Example:

```json
{
  "shift_code": "shift_normal",
  "shift_name": "標準勤務",
  "start_time": "09:00:00",
  "end_time": "18:00:00",
  "break_minutes": 60,
  "status": "active"
}
```

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

## 6.20 API-020 操作ログ記録

| 項目 | 内容 |
| --- | --- |
| Method | POST |
| Endpoint | `/audit-logs` |
| 権限 | System |
| 関連テーブル | audit_logs |
| 主なエラー | E009 |

Request:

```json
{
  "employee_id": 1,
  "action": "leave_request_approved",
  "target_type": "leave_requests",
  "target_id": 10,
  "result": "success",
  "ip_address": "127.0.0.1"
}
```

Note:

| 項目 | 内容 |
| --- | --- |
| 呼び出し元 | Controller / Service内部 |
| 外部公開 | 原則公開しない |

## 6.21 API-021 セッション状態確認

| 項目 | 内容 |
| --- | --- |
| Method | GET |
| Endpoint | `/auth/session` |
| 権限 | User / Manager / Admin |
| 関連テーブル | employees |
| 主なエラー | E010 |

Response Data:

| 項目 | 型 | 内容 |
| --- | --- | --- |
| authenticated | boolean | 認証済みか |
| expires_at | datetime | セッション期限 |
| employee | object | ログインユーザー情報 |

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
