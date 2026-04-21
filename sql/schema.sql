CREATE DATABASE IF NOT EXISTS hr_system CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE hr_system;

-- =========================
-- User / Role Management
-- =========================
CREATE TABLE IF NOT EXISTS hr_roles (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(80) NOT NULL UNIQUE,
  description VARCHAR(255) DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS hr_permissions (
  id INT AUTO_INCREMENT PRIMARY KEY,
  module_key VARCHAR(80) NOT NULL,
  action_key VARCHAR(80) NOT NULL,
  description VARCHAR(255) DEFAULT NULL,
  UNIQUE KEY uniq_permission (module_key, action_key)
);

CREATE TABLE IF NOT EXISTS hr_role_permissions (
  role_id INT NOT NULL,
  permission_id INT NOT NULL,
  PRIMARY KEY (role_id, permission_id),
  FOREIGN KEY (role_id) REFERENCES hr_roles(id),
  FOREIGN KEY (permission_id) REFERENCES hr_permissions(id)
);

CREATE TABLE IF NOT EXISTS hr_users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  full_name VARCHAR(150) NOT NULL,
  email VARCHAR(150) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  role_id INT NOT NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  last_login_at DATETIME DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (role_id) REFERENCES hr_roles(id)
);

CREATE TABLE IF NOT EXISTS hr_user_roles (
  user_id INT NOT NULL,
  role_id INT NOT NULL,
  PRIMARY KEY (user_id, role_id),
  FOREIGN KEY (user_id) REFERENCES hr_users(id),
  FOREIGN KEY (role_id) REFERENCES hr_roles(id)
);

CREATE TABLE IF NOT EXISTS hr_audit_logs (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  user_id INT DEFAULT NULL,
  module_key VARCHAR(80) NOT NULL,
  action_key VARCHAR(80) NOT NULL,
  entity_type VARCHAR(80) DEFAULT NULL,
  entity_id VARCHAR(80) DEFAULT NULL,
  ip_address VARCHAR(45) DEFAULT NULL,
  user_agent VARCHAR(255) DEFAULT NULL,
  payload JSON DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES hr_users(id),
  INDEX idx_audit_created_at (created_at),
  INDEX idx_audit_module_action (module_key, action_key)
);

-- =========================
-- Organization & Employee
-- =========================
CREATE TABLE IF NOT EXISTS hr_departments (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL UNIQUE
);

CREATE TABLE IF NOT EXISTS hr_positions (
  id INT AUTO_INCREMENT PRIMARY KEY,
  department_id INT DEFAULT NULL,
  title VARCHAR(120) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (department_id) REFERENCES hr_departments(id)
);

CREATE TABLE IF NOT EXISTS hr_employees (
  id INT AUTO_INCREMENT PRIMARY KEY,
  employee_code VARCHAR(40) NOT NULL UNIQUE,
  full_name VARCHAR(150) NOT NULL,
  email VARCHAR(150) NOT NULL UNIQUE,
  phone VARCHAR(50),
  department_id INT,
  position_id INT DEFAULT NULL,
  position VARCHAR(120) DEFAULT NULL,
  hire_date DATE NOT NULL,
  profile_json JSON DEFAULT NULL,
  status ENUM('Active','Resigned','Terminated') DEFAULT 'Active',
  manager_id INT DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (department_id) REFERENCES hr_departments(id),
  FOREIGN KEY (position_id) REFERENCES hr_positions(id),
  FOREIGN KEY (manager_id) REFERENCES hr_employees(id),
  INDEX idx_employee_status (status),
  INDEX idx_employee_department (department_id)
);

CREATE TABLE IF NOT EXISTS hr_employee_emergency_contacts (
  id INT AUTO_INCREMENT PRIMARY KEY,
  employee_id INT NOT NULL,
  contact_name VARCHAR(150) NOT NULL,
  relationship VARCHAR(80) NOT NULL,
  phone VARCHAR(50) NOT NULL,
  email VARCHAR(150) DEFAULT NULL,
  address VARCHAR(255) DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (employee_id) REFERENCES hr_employees(id)
);

CREATE TABLE IF NOT EXISTS hr_employee_history (
  id INT AUTO_INCREMENT PRIMARY KEY,
  employee_id INT NOT NULL,
  history_type ENUM('DepartmentChange','PositionChange','SalaryChange','StatusChange','Promotion','Transfer') NOT NULL,
  old_value VARCHAR(255) DEFAULT NULL,
  new_value VARCHAR(255) DEFAULT NULL,
  effective_date DATE NOT NULL,
  remarks VARCHAR(255) DEFAULT NULL,
  created_by INT DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (employee_id) REFERENCES hr_employees(id),
  FOREIGN KEY (created_by) REFERENCES hr_users(id)
);

CREATE TABLE IF NOT EXISTS hr_custom_fields (
  id INT AUTO_INCREMENT PRIMARY KEY,
  module_key VARCHAR(80) NOT NULL,
  field_key VARCHAR(80) NOT NULL,
  label VARCHAR(120) NOT NULL,
  field_type ENUM('text','textarea','number','date','select','checkbox','file') NOT NULL,
  options_json JSON DEFAULT NULL,
  is_required TINYINT(1) NOT NULL DEFAULT 0,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  UNIQUE KEY uniq_custom_field (module_key, field_key)
);

CREATE TABLE IF NOT EXISTS hr_custom_field_values (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  custom_field_id INT NOT NULL,
  entity_type VARCHAR(80) NOT NULL,
  entity_id BIGINT NOT NULL,
  field_value TEXT DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (custom_field_id) REFERENCES hr_custom_fields(id),
  INDEX idx_custom_value_entity (entity_type, entity_id)
);

-- =========================
-- Document Management
-- =========================
CREATE TABLE IF NOT EXISTS hr_documents (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  document_type ENUM('EmployeeDocument','CompanyPolicy','ClaimReceipt','Other') NOT NULL,
  employee_id INT DEFAULT NULL,
  title VARCHAR(180) NOT NULL,
  category VARCHAR(120) DEFAULT NULL,
  current_version INT NOT NULL DEFAULT 1,
  access_scope ENUM('AdminOnly','HROnly','ManagerAndAbove','OwnerAndHR','PublicInternal') DEFAULT 'OwnerAndHR',
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_by INT DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (employee_id) REFERENCES hr_employees(id),
  FOREIGN KEY (created_by) REFERENCES hr_users(id)
);

CREATE TABLE IF NOT EXISTS hr_document_versions (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  document_id BIGINT NOT NULL,
  version_no INT NOT NULL,
  file_name VARCHAR(255) NOT NULL,
  storage_path VARCHAR(500) NOT NULL,
  mime_type VARCHAR(120) DEFAULT NULL,
  file_size BIGINT DEFAULT NULL,
  uploaded_by INT DEFAULT NULL,
  uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uniq_doc_version (document_id, version_no),
  FOREIGN KEY (document_id) REFERENCES hr_documents(id),
  FOREIGN KEY (uploaded_by) REFERENCES hr_users(id)
);

-- =========================
-- Attendance & Scheduling
-- =========================
CREATE TABLE IF NOT EXISTS hr_shift_templates (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  start_time TIME NOT NULL,
  end_time TIME NOT NULL,
  break_minutes INT NOT NULL DEFAULT 60,
  late_grace_minutes INT NOT NULL DEFAULT 0,
  overtime_after_minutes INT NOT NULL DEFAULT 0,
  is_active TINYINT(1) NOT NULL DEFAULT 1
);

CREATE TABLE IF NOT EXISTS hr_shift_schedules (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  employee_id INT DEFAULT NULL,
  department_id INT DEFAULT NULL,
  shift_template_id INT NOT NULL,
  schedule_date DATE NOT NULL,
  is_rotating TINYINT(1) NOT NULL DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (employee_id) REFERENCES hr_employees(id),
  FOREIGN KEY (department_id) REFERENCES hr_departments(id),
  FOREIGN KEY (shift_template_id) REFERENCES hr_shift_templates(id),
  INDEX idx_schedule_date (schedule_date)
);

CREATE TABLE IF NOT EXISTS hr_attendance (
  id INT AUTO_INCREMENT PRIMARY KEY,
  employee_id INT NOT NULL,
  attendance_date DATE NOT NULL,
  check_in TIME,
  check_out TIME,
  entry_type ENUM('Clock','Manual') NOT NULL DEFAULT 'Clock',
  minutes_late INT NOT NULL DEFAULT 0,
  minutes_early_leave INT NOT NULL DEFAULT 0,
  overtime_minutes INT NOT NULL DEFAULT 0,
  ip_address VARCHAR(45) DEFAULT NULL,
  geo_location VARCHAR(255) DEFAULT NULL,
  status ENUM('Present','Absent','Remote') DEFAULT 'Present',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (employee_id) REFERENCES hr_employees(id),
  UNIQUE KEY uniq_attendance_employee_date (employee_id, attendance_date),
  INDEX idx_attendance_date (attendance_date)
);

CREATE TABLE IF NOT EXISTS hr_attendance_restrictions (
  id INT AUTO_INCREMENT PRIMARY KEY,
  restriction_type ENUM('IP','GeoFence') NOT NULL,
  value VARCHAR(255) NOT NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- =========================
-- Leave Management
-- =========================
CREATE TABLE IF NOT EXISTS hr_leave_types (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL UNIQUE,
  annual_quota DECIMAL(8,2) NOT NULL DEFAULT 0,
  carry_forward_limit DECIMAL(8,2) NOT NULL DEFAULT 0,
  requires_attachment TINYINT(1) NOT NULL DEFAULT 0,
  is_active TINYINT(1) NOT NULL DEFAULT 1
);

CREATE TABLE IF NOT EXISTS hr_leaves (
  id INT AUTO_INCREMENT PRIMARY KEY,
  employee_id INT NOT NULL,
  leave_type_id INT NOT NULL,
  date_from DATE NOT NULL,
  date_to DATE NOT NULL,
  total_days DECIMAL(8,2) DEFAULT NULL,
  reason TEXT,
  status ENUM('Pending','Approved','Rejected','Cancelled') DEFAULT 'Pending',
  approved_by INT DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (employee_id) REFERENCES hr_employees(id),
  FOREIGN KEY (leave_type_id) REFERENCES hr_leave_types(id),
  FOREIGN KEY (approved_by) REFERENCES hr_users(id)
);

CREATE TABLE IF NOT EXISTS hr_leave_approvals (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  leave_id INT NOT NULL,
  approval_level INT NOT NULL,
  approver_user_id INT NOT NULL,
  decision ENUM('Pending','Approved','Rejected') DEFAULT 'Pending',
  comments VARCHAR(255) DEFAULT NULL,
  decided_at DATETIME DEFAULT NULL,
  FOREIGN KEY (leave_id) REFERENCES hr_leaves(id),
  FOREIGN KEY (approver_user_id) REFERENCES hr_users(id)
);

CREATE TABLE IF NOT EXISTS hr_leave_balances (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  employee_id INT NOT NULL,
  leave_type_id INT NOT NULL,
  year YEAR NOT NULL,
  entitlement DECIMAL(8,2) NOT NULL DEFAULT 0,
  used_days DECIMAL(8,2) NOT NULL DEFAULT 0,
  carry_forward DECIMAL(8,2) NOT NULL DEFAULT 0,
  remaining_days DECIMAL(8,2) NOT NULL DEFAULT 0,
  UNIQUE KEY uniq_leave_balance (employee_id, leave_type_id, year),
  FOREIGN KEY (employee_id) REFERENCES hr_employees(id),
  FOREIGN KEY (leave_type_id) REFERENCES hr_leave_types(id)
);

-- =========================
-- Payroll Management
-- =========================
CREATE TABLE IF NOT EXISTS hr_salary_structures (
  id INT AUTO_INCREMENT PRIMARY KEY,
  employee_id INT NOT NULL,
  basic_salary DECIMAL(12,2) NOT NULL,
  effective_from DATE NOT NULL,
  effective_to DATE DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (employee_id) REFERENCES hr_employees(id)
);

CREATE TABLE IF NOT EXISTS hr_payroll_components (
  id INT AUTO_INCREMENT PRIMARY KEY,
  component_name VARCHAR(120) NOT NULL,
  component_type ENUM('Allowance','Deduction','Incentive','Bonus') NOT NULL,
  calculation_type ENUM('Fixed','Percentage') NOT NULL DEFAULT 'Fixed',
  default_value DECIMAL(12,2) NOT NULL DEFAULT 0,
  is_statutory TINYINT(1) NOT NULL DEFAULT 0
);

CREATE TABLE IF NOT EXISTS hr_payroll (
  id INT AUTO_INCREMENT PRIMARY KEY,
  employee_id INT NOT NULL,
  pay_period CHAR(7) NOT NULL,
  basic_salary DECIMAL(12,2) NOT NULL,
  allowance DECIMAL(12,2) NOT NULL DEFAULT 0,
  deduction DECIMAL(12,2) NOT NULL DEFAULT 0,
  net_salary DECIMAL(12,2) NOT NULL,
  epf_employee DECIMAL(12,2) NOT NULL DEFAULT 0,
  epf_employer DECIMAL(12,2) NOT NULL DEFAULT 0,
  socso DECIMAL(12,2) NOT NULL DEFAULT 0,
  pcb DECIMAL(12,2) NOT NULL DEFAULT 0,
  payroll_status ENUM('Draft','Processed','Paid') DEFAULT 'Draft',
  payslip_pdf_path VARCHAR(500) DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (employee_id) REFERENCES hr_employees(id),
  UNIQUE KEY uniq_payroll_employee_period (employee_id, pay_period)
);

CREATE TABLE IF NOT EXISTS hr_payroll_component_items (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  payroll_id INT NOT NULL,
  component_id INT NOT NULL,
  amount DECIMAL(12,2) NOT NULL,
  FOREIGN KEY (payroll_id) REFERENCES hr_payroll(id),
  FOREIGN KEY (component_id) REFERENCES hr_payroll_components(id)
);

-- =========================
-- Claims / Reimbursement
-- =========================
CREATE TABLE IF NOT EXISTS hr_claims (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  employee_id INT NOT NULL,
  claim_type VARCHAR(120) NOT NULL,
  claim_date DATE NOT NULL,
  amount DECIMAL(12,2) NOT NULL,
  currency CHAR(3) NOT NULL DEFAULT 'MYR',
  description VARCHAR(255) DEFAULT NULL,
  status ENUM('Submitted','Approved','Rejected','Paid') DEFAULT 'Submitted',
  payroll_id INT DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (employee_id) REFERENCES hr_employees(id),
  FOREIGN KEY (payroll_id) REFERENCES hr_payroll(id)
);

CREATE TABLE IF NOT EXISTS hr_claim_receipts (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  claim_id BIGINT NOT NULL,
  file_name VARCHAR(255) NOT NULL,
  storage_path VARCHAR(500) NOT NULL,
  uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (claim_id) REFERENCES hr_claims(id)
);

CREATE TABLE IF NOT EXISTS hr_claim_approvals (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  claim_id BIGINT NOT NULL,
  approval_level INT NOT NULL,
  approver_user_id INT NOT NULL,
  decision ENUM('Pending','Approved','Rejected') DEFAULT 'Pending',
  comments VARCHAR(255) DEFAULT NULL,
  decided_at DATETIME DEFAULT NULL,
  FOREIGN KEY (claim_id) REFERENCES hr_claims(id),
  FOREIGN KEY (approver_user_id) REFERENCES hr_users(id)
);

-- =========================
-- Performance Management
-- =========================
CREATE TABLE IF NOT EXISTS hr_performance_cycles (
  id INT AUTO_INCREMENT PRIMARY KEY,
  cycle_name VARCHAR(150) NOT NULL,
  cycle_type ENUM('Quarterly','HalfYearly','Yearly','Custom') NOT NULL DEFAULT 'Yearly',
  start_date DATE NOT NULL,
  end_date DATE NOT NULL,
  status ENUM('Draft','Open','Closed') DEFAULT 'Draft'
);

CREATE TABLE IF NOT EXISTS hr_kpis (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  employee_id INT NOT NULL,
  cycle_id INT NOT NULL,
  title VARCHAR(180) NOT NULL,
  metric_type ENUM('KPI','OKR') NOT NULL DEFAULT 'KPI',
  target_value VARCHAR(80) DEFAULT NULL,
  weightage DECIMAL(5,2) DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (employee_id) REFERENCES hr_employees(id),
  FOREIGN KEY (cycle_id) REFERENCES hr_performance_cycles(id)
);

CREATE TABLE IF NOT EXISTS hr_performance_reviews (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  employee_id INT NOT NULL,
  reviewer_id INT NOT NULL,
  cycle_id INT NOT NULL,
  review_type ENUM('Self','Manager','Peer','Subordinate') NOT NULL,
  score DECIMAL(5,2) DEFAULT NULL,
  comments TEXT DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (employee_id) REFERENCES hr_employees(id),
  FOREIGN KEY (reviewer_id) REFERENCES hr_users(id),
  FOREIGN KEY (cycle_id) REFERENCES hr_performance_cycles(id)
);

-- =========================
-- Communication & Notifications
-- =========================
CREATE TABLE IF NOT EXISTS hr_announcements (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(180) NOT NULL,
  content TEXT NOT NULL,
  scope ENUM('Company','Department','HR') NOT NULL DEFAULT 'Company',
  department_id INT DEFAULT NULL,
  published_by INT DEFAULT NULL,
  published_at DATETIME DEFAULT NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  FOREIGN KEY (department_id) REFERENCES hr_departments(id),
  FOREIGN KEY (published_by) REFERENCES hr_users(id)
);

CREATE TABLE IF NOT EXISTS hr_notifications (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  channel ENUM('System','Email') NOT NULL DEFAULT 'System',
  type_key VARCHAR(80) NOT NULL,
  title VARCHAR(180) NOT NULL,
  message TEXT NOT NULL,
  related_entity_type VARCHAR(80) DEFAULT NULL,
  related_entity_id BIGINT DEFAULT NULL,
  is_read TINYINT(1) NOT NULL DEFAULT 0,
  sent_at DATETIME DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES hr_users(id)
);

-- =========================
-- Recruitment (ATS)
-- =========================
CREATE TABLE IF NOT EXISTS hr_job_postings (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  department_id INT DEFAULT NULL,
  position_id INT DEFAULT NULL,
  title VARCHAR(180) NOT NULL,
  description TEXT DEFAULT NULL,
  status ENUM('Draft','Open','Closed','OnHold') DEFAULT 'Draft',
  posted_at DATETIME DEFAULT NULL,
  FOREIGN KEY (department_id) REFERENCES hr_departments(id),
  FOREIGN KEY (position_id) REFERENCES hr_positions(id)
);

CREATE TABLE IF NOT EXISTS hr_candidates (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  full_name VARCHAR(150) NOT NULL,
  email VARCHAR(150) DEFAULT NULL,
  phone VARCHAR(50) DEFAULT NULL,
  current_company VARCHAR(150) DEFAULT NULL,
  resume_path VARCHAR(500) DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS hr_job_applications (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  job_posting_id BIGINT NOT NULL,
  candidate_id BIGINT NOT NULL,
  stage ENUM('Applied','Screening','Interview','Offer','Hired','Rejected') DEFAULT 'Applied',
  applied_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (job_posting_id) REFERENCES hr_job_postings(id),
  FOREIGN KEY (candidate_id) REFERENCES hr_candidates(id)
);

CREATE TABLE IF NOT EXISTS hr_interviews (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  job_application_id BIGINT NOT NULL,
  interviewer_user_id INT DEFAULT NULL,
  interview_at DATETIME NOT NULL,
  mode ENUM('Online','Onsite','Phone') DEFAULT 'Online',
  result ENUM('Pending','Pass','Fail') DEFAULT 'Pending',
  remarks VARCHAR(255) DEFAULT NULL,
  FOREIGN KEY (job_application_id) REFERENCES hr_job_applications(id),
  FOREIGN KEY (interviewer_user_id) REFERENCES hr_users(id)
);

-- =========================
-- System Settings
-- =========================
CREATE TABLE IF NOT EXISTS hr_company_settings (
  id INT AUTO_INCREMENT PRIMARY KEY,
  company_name VARCHAR(180) NOT NULL,
  timezone VARCHAR(80) NOT NULL DEFAULT 'Asia/Kuala_Lumpur',
  currency CHAR(3) NOT NULL DEFAULT 'MYR',
  locale VARCHAR(20) NOT NULL DEFAULT 'en_MY',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS hr_holidays (
  id INT AUTO_INCREMENT PRIMARY KEY,
  holiday_date DATE NOT NULL UNIQUE,
  holiday_name VARCHAR(180) NOT NULL,
  is_optional TINYINT(1) NOT NULL DEFAULT 0
);

INSERT IGNORE INTO hr_roles (id,name) VALUES
(1,'Admin'),(2,'HR'),(3,'Manager'),(4,'Payroll Officer'),(5,'Employee');

INSERT IGNORE INTO hr_departments (id,name) VALUES
(1,'Human Resources'),(2,'Finance'),(3,'Engineering'),(4,'Sales'),(5,'Operations');

INSERT IGNORE INTO hr_permissions (id,module_key,action_key,description) VALUES
(1,'employee','view','View employee profiles'),
(2,'employee','manage','Create/update employee records'),
(3,'attendance','manage','Manage attendance and shifts'),
(4,'leave','approve','Approve leave requests'),
(5,'payroll','process','Process payroll'),
(6,'claims','approve','Approve claims'),
(7,'recruitment','manage','Manage ATS pipeline'),
(8,'documents','manage','Manage document storage'),
(9,'reports','view','View analytics and reports'),
(10,'settings','manage','Manage system settings');

INSERT IGNORE INTO hr_users (id, full_name, email, password_hash, role_id, is_active)
VALUES (1, 'System Administrator', 'admin@company.com', '$2y$10$4p30VvGQkXjgp0YhG65J6uEpgVUQw0xNQSa8CaIrScMvwYXAWi8rS', 1, 1);
-- password: password
