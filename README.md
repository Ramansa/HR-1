# NovaHR - Standalone HR System (PHP + Bootstrap + jQuery)

A complete standalone HR system designed for companies with up to 1000 staff. It uses a **standalone `$wpdb` implementation** (custom class) and does **not** use any WordPress classes or functions.

## Feature coverage

### 👤 Employee Management
- Employee profile with full details (`hr_employees.profile_json`)
- Employment history tracking (`hr_employee_history`)
- Document upload and storage (`hr_documents`, `hr_document_versions`)
- Emergency contacts (`hr_employee_emergency_contacts`)
- Custom fields (`hr_custom_fields`, `hr_custom_field_values`)
- Department and position assignment (`hr_departments`, `hr_positions`)
- Employee status (`Active`, `Resigned`, `Terminated`)

### 🔐 User & Role Management
- Multi-role system (`Admin`, `HR`, `Manager`, `Payroll Officer`, `Employee`)
- Role-based permissions / RBAC (`hr_permissions`, `hr_role_permissions`)
- User account management (`hr_users`, `hr_user_roles`)
- Access control by module/action (`module_key`, `action_key`)
- Activity/audit logs (`hr_audit_logs`)

### ⏱️ Attendance Management
- Clock in / clock out (`hr_attendance`)
- Manual attendance entry (`entry_type = Manual`)
- Shift scheduling (`hr_shift_templates`, `hr_shift_schedules`)
- Late / early / overtime tracking (`minutes_late`, `minutes_early_leave`, `overtime_minutes`)
- Attendance logs and reporting indexes (`idx_attendance_date`)
- Geo/IP restrictions (optional) (`hr_attendance_restrictions`)

### 🏖️ Leave Management
- Leave type configuration (`hr_leave_types`)
- Leave application (`hr_leaves`)
- Multi-level approval workflow (`hr_leave_approvals`)
- Leave balance tracking (`hr_leave_balances`)
- Leave calendar support via date ranges (`date_from`, `date_to`)
- Carry-forward rules (`carry_forward_limit`, `carry_forward`)

### 💰 Payroll Management
- Salary structure setup (`hr_salary_structures`)
- Allowances and deductions (`hr_payroll_components`, `hr_payroll_component_items`)
- EPF / SOCSO / PCB fields in payroll (`hr_payroll`)
- Payslip generation path (`payslip_pdf_path`)
- Bonus & incentives (`component_type` values)
- Payroll reporting-ready model (`pay_period`, `payroll_status`)

### 🧾 Claims & Reimbursement
- Expense submission (`hr_claims`)
- Receipt upload (`hr_claim_receipts`)
- Approval workflow (`hr_claim_approvals`)
- Claims tracking (`status`)
- Payroll integration (`hr_claims.payroll_id`)

### 📊 Performance Management
- KPI / OKR setup (`hr_kpis.metric_type`)
- Employee evaluations (`hr_performance_reviews`)
- Appraisal cycles (`hr_performance_cycles`)
- Manager reviews (`review_type = Manager`)
- 360-degree feedback (`Self`, `Manager`, `Peer`, `Subordinate` review types)

### 📅 Scheduling & Shifts
- Shift templates (`hr_shift_templates`)
- Rotating schedules (`hr_shift_schedules.is_rotating`)
- Department-based scheduling (`department_id`)
- Shift assignment (`employee_id`, `shift_template_id`)

### 📁 Document Management
- Company policy storage (`document_type = CompanyPolicy`)
- Employee documents (`document_type = EmployeeDocument`)
- File versioning (`hr_document_versions.version_no`)
- Secure access control (`hr_documents.access_scope`)

### 📢 Communication
- Company announcements (`hr_announcements.scope = Company`)
- Department notices (`scope = Department`)
- HR circulars (`scope = HR`)
- Notification system (email/system) (`hr_notifications.channel`)

### 📈 Reports & Analytics
- Attendance reports (from `hr_attendance`)
- Payroll reports (from `hr_payroll`)
- Leave reports (from `hr_leaves`, `hr_leave_balances`)
- Employee headcount (from `hr_employees`)
- Custom export-ready model (CSV/PDF generation at app/service layer)

### 🧑‍💼 Recruitment (ATS)
- Job posting (`hr_job_postings`)
- Candidate management (`hr_candidates`)
- Interview scheduling (`hr_interviews`)
- Hiring pipeline tracking (`hr_job_applications.stage`)

### ⚙️ System Settings
- Company settings (`hr_company_settings`)
- Leave and payroll rules (configurable through leave/payroll tables)
- Holiday calendar (`hr_holidays`)
- Localization (`currency`, `timezone`, `locale`)

### 🔔 Notifications
- Email notifications (`channel = Email`)
- System alerts (`channel = System`)
- Approval reminders (modeled via `type_key` + pending approval data)

### 🔐 Security & Compliance
- Password hashing (`hr_users.password_hash`)
- CSRF protection (implemented at application layer)
- Role-based access control (`hr_permissions`, `hr_role_permissions`)
- Audit logs (`hr_audit_logs`)

## Tech stack
- PHP 8+
- MySQL / MariaDB
- Bootstrap 5
- Font Awesome
- jQuery

## Setup
1. Create DB and tables:
   ```bash
   mysql -u root -p < sql/schema.sql
   ```
2. Update DB credentials in `config.php`.
3. Run the app using PHP server:
   ```bash
   php -S 0.0.0.0:8000
   ```
4. Open `http://localhost:8000/index.php?page=login`

## First-time access
- Open `http://localhost:8000/index.php?page=register`
- The **very first** registered user is automatically assigned the **Admin** role.
- Every user registered afterwards is assigned the **Employee** role by default.
