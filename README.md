# NovaHR - Standalone HR System (PHP + Bootstrap + jQuery)

A complete standalone HR system designed for companies with up to 1000 staff. It uses a **standalone `$wpdb` implementation** (custom class) and does **not** use any WordPress classes or functions.

## Core modules
- Authentication + session management
- Role-based access for **Admin, HR Manager, Payroll Officer, Team Lead, Employee**
- Employee lifecycle management
- Attendance tracking
- Leave request and approval workflow
- Payroll processing and net-salary computation
- Dashboard KPIs and departmental report views

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

## Demo login
- Email: `admin@company.com`
- Password: `password`

## Notes for scaling toward 1000 staff
- Add DB indexes on frequent filters (`attendance_date`, `department_id`, `status`).
- Add pagination server-side to all list pages.
- Add audit logs and soft-delete for compliance.
- Add REST API + background jobs for payroll/notifications.
