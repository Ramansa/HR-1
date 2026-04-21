<?php

declare(strict_types=1);

require __DIR__ . '/config.php';
require __DIR__ . '/includes/Database.php';
require __DIR__ . '/includes/helpers.php';
require __DIR__ . '/includes/auth.php';

$wpdb = new wpdb(DB_USER, DB_PASSWORD, DB_NAME, DB_HOST);
$page = $_GET['page'] ?? 'dashboard';
$user = currentUser();

if ($page === 'logout') {
    logoutUser();
    session_start();
    flash('You are logged out.', 'info');
    redirect('index.php?page=login');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf($_POST['csrf_token'] ?? null)) {
        flash('Invalid security token. Please refresh the page and try again.', 'danger');
        redirect('index.php?page=' . urlencode((string) $page));
    }

    $action = $_POST['action'] ?? '';

    if ($action === 'login') {
        if (loginUser($wpdb, trim($_POST['email'] ?? ''), $_POST['password'] ?? '')) {
            flash('Welcome back!');
            redirect('index.php?page=dashboard');
        }
        flash('Invalid credentials.', 'danger');
        redirect('index.php?page=login');
    }

    requireAuth();

    if ($action === 'employee_create' && roleCan($user['role_name'], ['Admin', 'HR'])) {
        $wpdb->insert('hr_employees', [
            'employee_code' => trim($_POST['employee_code']),
            'full_name' => trim($_POST['full_name']),
            'email' => trim($_POST['email']),
            'phone' => trim($_POST['phone']),
            'department_id' => (int) $_POST['department_id'],
            'position' => trim($_POST['position']),
            'hire_date' => $_POST['hire_date'],
            'status' => trim($_POST['status']),
            'manager_id' => (int) ($_POST['manager_id'] ?: 0),
        ]);
        flash('Employee added successfully.');
        redirect('index.php?page=employees');
    }

    if ($action === 'attendance_mark' && roleCan($user['role_name'], ['Admin', 'HR', 'Manager'])) {
        $wpdb->insert('hr_attendance', [
            'employee_id' => (int) $_POST['employee_id'],
            'attendance_date' => $_POST['attendance_date'],
            'check_in' => $_POST['check_in'],
            'check_out' => $_POST['check_out'],
            'status' => trim($_POST['status']),
        ]);
        flash('Attendance record saved.');
        redirect('index.php?page=attendance');
    }

    if ($action === 'leave_request') {
        $wpdb->insert('hr_leaves', [
            'employee_id' => (int) $_POST['employee_id'],
            'leave_type_id' => (int) $_POST['leave_type_id'],
            'date_from' => $_POST['date_from'],
            'date_to' => $_POST['date_to'],
            'reason' => trim($_POST['reason']),
            'status' => 'Pending',
        ]);
        flash('Leave request submitted.');
        redirect('index.php?page=leaves');
    }

    if ($action === 'leave_approve' && roleCan($user['role_name'], ['Admin', 'HR'])) {
        $wpdb->update('hr_leaves', [
            'status' => trim($_POST['status']),
            'approved_by' => (int) $user['id'],
        ], ['id' => (int) $_POST['leave_id']]);
        flash('Leave status updated.');
        redirect('index.php?page=leaves');
    }

    if ($action === 'payroll_add' && roleCan($user['role_name'], ['Admin', 'Payroll Officer'])) {
        $basic = (float) $_POST['basic_salary'];
        $allowance = (float) $_POST['allowance'];
        $deduction = (float) $_POST['deduction'];
        $net = $basic + $allowance - $deduction;
        $wpdb->insert('hr_payroll', [
            'employee_id' => (int) $_POST['employee_id'],
            'pay_period' => $_POST['pay_period'],
            'basic_salary' => $basic,
            'allowance' => $allowance,
            'deduction' => $deduction,
            'net_salary' => $net,
        ]);
        flash('Payroll saved.');
        redirect('index.php?page=payroll');
    }
}

$flash = flash();

function badgeClass(string $status): string
{
    return match (strtolower($status)) {
        'approved', 'present', 'active' => 'success',
        'pending' => 'warning',
        'rejected', 'absent', 'inactive' => 'danger',
        default => 'secondary',
    };
}

function pct(float $value, int $precision = 1): string
{
    return number_format($value, $precision) . '%';
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>NovaHR - Complete HR System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" />
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="bg-light">
<?php if (!$user && $page !== 'login'): redirect('index.php?page=login'); endif; ?>

<?php if ($page === 'login'): ?>
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-5">
            <div class="card shadow-lg border-0 rounded-4">
                <div class="card-body p-5">
                    <h2 class="fw-bold mb-2">NovaHR</h2>
                    <p class="text-muted">Enterprise HRMS for 1000+ workforce</p>
                    <?php if ($flash): ?>
                        <div class="alert alert-<?= e($flash['type']) ?>"><?= e($flash['message']) ?></div>
                    <?php endif; ?>
                    <form method="post">
                        <?= csrfField() ?>
                        <input type="hidden" name="action" value="login">
                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Password</label>
                            <input type="password" name="password" class="form-control" required>
                        </div>
                        <button class="btn btn-primary w-100">Login</button>
                    </form>
                    <p class="small text-muted mt-3 mb-0">Default admin: admin@company.com / password</p>
                </div>
            </div>
        </div>
    </div>
</div>
<?php else: ?>
<nav class="navbar navbar-expand-lg navbar-dark hr-nav shadow-sm">
    <div class="container-fluid">
        <a class="navbar-brand fw-bold" href="index.php?page=dashboard"><i class="fa-solid fa-people-roof"></i> NovaHR</a>
        <ul class="navbar-nav ms-auto align-items-center">
            <li class="nav-item me-3 text-white small">
                <?= e($user['full_name']) ?> <span class="badge bg-info-subtle text-info-emphasis"><?= e($user['role_name']) ?></span>
            </li>
            <li class="nav-item"><a class="btn btn-sm btn-outline-light" href="index.php?page=logout">Logout</a></li>
        </ul>
    </div>
</nav>

<div class="container-fluid">
    <div class="row">
        <aside class="col-md-2 bg-white border-end min-vh-100 p-3">
            <a class="d-block mb-2 nav-link" href="index.php?page=dashboard"><i class="fa fa-chart-line me-2"></i>Dashboard</a>
            <a class="d-block mb-2 nav-link" href="index.php?page=employees"><i class="fa fa-users me-2"></i>Employees</a>
            <a class="d-block mb-2 nav-link" href="index.php?page=attendance"><i class="fa fa-calendar-check me-2"></i>Attendance</a>
            <a class="d-block mb-2 nav-link" href="index.php?page=leaves"><i class="fa fa-plane-departure me-2"></i>Leave</a>
            <a class="d-block mb-2 nav-link" href="index.php?page=payroll"><i class="fa fa-money-bill-wave me-2"></i>Payroll</a>
            <a class="d-block mb-2 nav-link" href="index.php?page=reports"><i class="fa fa-file-lines me-2"></i>Reports</a>
            <a class="d-block mb-2 nav-link" href="index.php?page=features"><i class="fa fa-list-check me-2"></i>Feature Matrix</a>
        </aside>

        <main class="col-md-10 p-4">
            <?php if ($flash): ?>
                <div class="alert alert-<?= e($flash['type']) ?>"><?= e($flash['message']) ?></div>
            <?php endif; ?>

            <?php if ($page === 'dashboard'):
                $totalEmployees = $wpdb->get_row('SELECT COUNT(*) AS c FROM hr_employees')['c'] ?? 0;
                $activeLeaves = $wpdb->get_row("SELECT COUNT(*) AS c FROM hr_leaves WHERE status='Pending'")['c'] ?? 0;
                $todayPresence = $wpdb->get_row("SELECT COUNT(*) AS c FROM hr_attendance WHERE attendance_date = CURDATE() AND status='Present'")['c'] ?? 0;
                $monthlyPayroll = $wpdb->get_row("SELECT COALESCE(SUM(net_salary),0) AS s FROM hr_payroll WHERE DATE_FORMAT(pay_period, '%Y-%m') = DATE_FORMAT(CURDATE(), '%Y-%m')")['s'] ?? 0;
                $activeEmployees = $wpdb->get_row("SELECT COUNT(*) AS c FROM hr_employees WHERE status='Active'")['c'] ?? 0;
                $todayAttendanceTotal = $wpdb->get_row("SELECT COUNT(*) AS c FROM hr_attendance WHERE attendance_date = CURDATE()")['c'] ?? 0;
                $leaveApprovedThisMonth = $wpdb->get_row("SELECT COUNT(*) AS c FROM hr_leaves WHERE status='Approved' AND DATE_FORMAT(updated_at, '%Y-%m') = DATE_FORMAT(CURDATE(), '%Y-%m')")['c'] ?? 0;
                $presentRate = $todayAttendanceTotal > 0 ? ((float) $todayPresence / (float) $todayAttendanceTotal) * 100 : 0;
                $activeRate = $totalEmployees > 0 ? ((float) $activeEmployees / (float) $totalEmployees) * 100 : 0;
                $monthlyPayrollAvg = $totalEmployees > 0 ? (float) $monthlyPayroll / (float) $totalEmployees : 0;
            ?>
                <div class="row g-3 mb-4">
                    <div class="col-md-3"><div class="card stat-card"><div class="card-body"><small class="text-uppercase fw-semibold">Total Staff</small><h3 class="mt-1 mb-0"><?= e((string) $totalEmployees) ?></h3><small class="text-white-50">Active: <?= e((string) $activeEmployees) ?> (<?= pct($activeRate) ?>)</small></div></div></div>
                    <div class="col-md-3"><div class="card stat-card"><div class="card-body"><small class="text-uppercase fw-semibold">Pending Leaves</small><h3 class="mt-1 mb-0"><?= e((string) $activeLeaves) ?></h3><small class="text-white-50">Approved this month: <?= e((string) $leaveApprovedThisMonth) ?></small></div></div></div>
                    <div class="col-md-3"><div class="card stat-card"><div class="card-body"><small class="text-uppercase fw-semibold">Today Present</small><h3 class="mt-1 mb-0"><?= e((string) $todayPresence) ?></h3><small class="text-white-50">Attendance coverage: <?= pct($presentRate) ?></small></div></div></div>
                    <div class="col-md-3"><div class="card stat-card"><div class="card-body"><small class="text-uppercase fw-semibold">Monthly Payroll</small><h3 class="mt-1 mb-0">$<?= number_format((float) $monthlyPayroll, 0) ?></h3><small class="text-white-50">Avg / employee: $<?= number_format($monthlyPayrollAvg, 0) ?></small></div></div></div>
                </div>
                <div class="row g-3 mb-3">
                    <div class="col-lg-8">
                        <div class="card shadow-sm h-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <h5 class="mb-0">Workforce Health Snapshot</h5>
                                    <span class="badge text-bg-light border">Live KPI</span>
                                </div>
                                <div class="mb-3">
                                    <div class="d-flex justify-content-between small mb-1"><span>Active Workforce Ratio</span><strong><?= pct($activeRate) ?></strong></div>
                                    <div class="progress" role="progressbar" aria-label="Active Workforce Ratio"><div class="progress-bar bg-success" style="width: <?= max(0, min(100, $activeRate)) ?>%"></div></div>
                                </div>
                                <div class="mb-3">
                                    <div class="d-flex justify-content-between small mb-1"><span>Today's Presence Ratio</span><strong><?= pct($presentRate) ?></strong></div>
                                    <div class="progress" role="progressbar" aria-label="Today's Presence Ratio"><div class="progress-bar bg-info" style="width: <?= max(0, min(100, $presentRate)) ?>%"></div></div>
                                </div>
                                <div class="small text-muted">KPIs are computed from live attendance, leave, payroll, and employee records to support daily staffing decisions.</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-4">
                        <div class="card shadow-sm h-100">
                            <div class="card-body">
                                <h5>Quick Actions</h5>
                                <div class="d-grid gap-2">
                                    <a class="btn btn-outline-primary btn-sm text-start" href="index.php?page=employees"><i class="fa fa-user-plus me-2"></i>Onboard Employee</a>
                                    <a class="btn btn-outline-primary btn-sm text-start" href="index.php?page=attendance"><i class="fa fa-clock me-2"></i>Mark Attendance</a>
                                    <a class="btn btn-outline-primary btn-sm text-start" href="index.php?page=leaves"><i class="fa fa-calendar-check me-2"></i>Review Leave Queue</a>
                                    <a class="btn btn-outline-primary btn-sm text-start" href="index.php?page=payroll"><i class="fa fa-file-invoice-dollar me-2"></i>Run Payroll Entry</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card shadow-sm">
                    <div class="card-body">
                        <h5 class="mb-3">Role-based capability model</h5>
                        <ul class="mb-0">
                            <li><strong>Admin:</strong> Full system control, security, workflow and master data.</li>
                            <li><strong>HR Manager:</strong> Employee lifecycle, leave approvals, policy and attendance governance.</li>
                            <li><strong>Payroll Officer:</strong> Salary cycles, compensation, deductions, and exports.</li>
                            <li><strong>Team Lead:</strong> Team attendance, leave recommendations, and performance notes.</li>
                            <li><strong>Employee:</strong> Profile, leave requests, personal attendance and payslip view.</li>
                        </ul>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ($page === 'employees'):
                $departments = $wpdb->get_results('SELECT id, name FROM hr_departments ORDER BY name');
                $employees = $wpdb->get_results('SELECT e.*, d.name department FROM hr_employees e LEFT JOIN hr_departments d ON d.id=e.department_id ORDER BY e.id DESC LIMIT 100');
                $managers = $wpdb->get_results("SELECT id, full_name FROM hr_employees WHERE status='Active' ORDER BY full_name");
            ?>
            <div class="card shadow-sm mb-3">
                <div class="card-header bg-white"><h5 class="mb-0">Add Employee</h5></div>
                <div class="card-body">
                <form method="post" class="row g-2">
                    <?= csrfField() ?>
                    <input type="hidden" name="action" value="employee_create">
                        <div class="col-md-2"><input name="employee_code" class="form-control" placeholder="EMP1001" required></div>
                        <div class="col-md-3"><input name="full_name" class="form-control" placeholder="Full name" required></div>
                        <div class="col-md-3"><input name="email" type="email" class="form-control" placeholder="Email" required></div>
                        <div class="col-md-2"><input name="phone" class="form-control" placeholder="Phone"></div>
                        <div class="col-md-2"><input name="position" class="form-control" placeholder="Position" required></div>
                        <div class="col-md-3"><select name="department_id" class="form-select" required><?php foreach ($departments as $d): ?><option value="<?= (int) $d['id'] ?>"><?= e($d['name']) ?></option><?php endforeach; ?></select></div>
                        <div class="col-md-2"><input type="date" name="hire_date" class="form-control" required></div>
                        <div class="col-md-2"><select name="status" class="form-select"><option>Active</option><option>Resigned</option><option>Terminated</option></select></div>
                        <div class="col-md-3"><select name="manager_id" class="form-select"><option value="0">No Manager</option><?php foreach ($managers as $m): ?><option value="<?= (int) $m['id'] ?>"><?= e($m['full_name']) ?></option><?php endforeach; ?></select></div>
                        <div class="col-md-2"><button class="btn btn-primary w-100">Save</button></div>
                    </form>
                </div>
            </div>
            <div class="card shadow-sm">
                <div class="card-body table-responsive">
                    <div class="d-flex flex-wrap gap-2 justify-content-between align-items-center mb-3">
                        <div class="input-group" style="max-width: 340px;">
                            <span class="input-group-text bg-white"><i class="fa fa-search text-muted"></i></span>
                            <input type="search" id="employeeSearch" class="form-control" placeholder="Search name, email, code, position...">
                        </div>
                        <div class="d-flex gap-2">
                            <select id="employeeStatusFilter" class="form-select">
                                <option value="">All status</option>
                                <option value="Active">Active</option>
                                <option value="Resigned">Resigned</option><option value="Terminated">Terminated</option>
                            </select>
                            <button class="btn btn-light border" id="employeeResetFilters" type="button">Reset</button>
                        </div>
                    </div>
                    <table class="table table-hover" id="dataTable">
                        <thead><tr><th>Code</th><th>Name</th><th>Department</th><th>Position</th><th>Status</th></tr></thead>
                        <tbody><?php foreach ($employees as $emp): ?><tr data-search="<?= e(strtolower($emp['employee_code'] . ' ' . $emp['full_name'] . ' ' . $emp['email'] . ' ' . $emp['position'] . ' ' . ($emp['department'] ?? ''))) ?>" data-status="<?= e($emp['status']) ?>"><td><?= e($emp['employee_code']) ?></td><td><?= e($emp['full_name']) ?><br><small class="text-muted"><?= e($emp['email']) ?></small></td><td><?= e((string) $emp['department']) ?></td><td><?= e($emp['position']) ?></td><td><span class="badge bg-<?= badgeClass($emp['status']) ?>"><?= e($emp['status']) ?></span></td></tr><?php endforeach; ?></tbody>
                    </table>
                    <div class="small text-muted" id="employeeTableMeta">Showing <?= count($employees) ?> employee records.</div>
                </div>
            </div>
            <?php endif; ?>

            <?php if ($page === 'attendance'):
                $employees = $wpdb->get_results("SELECT id, full_name FROM hr_employees WHERE status='Active' ORDER BY full_name");
                $attendance = $wpdb->get_results('SELECT a.*, e.full_name FROM hr_attendance a JOIN hr_employees e ON e.id = a.employee_id ORDER BY a.attendance_date DESC LIMIT 100');
            ?>
            <div class="card shadow-sm mb-3"><div class="card-body">
                <form method="post" class="row g-2">
                    <?= csrfField() ?>
                    <input type="hidden" name="action" value="attendance_mark">
                    <div class="col-md-3"><select name="employee_id" class="form-select" required><?php foreach ($employees as $emp): ?><option value="<?= (int)$emp['id'] ?>"><?= e($emp['full_name']) ?></option><?php endforeach; ?></select></div>
                    <div class="col-md-2"><input type="date" name="attendance_date" class="form-control" required></div>
                    <div class="col-md-2"><input type="time" name="check_in" class="form-control" required></div>
                    <div class="col-md-2"><input type="time" name="check_out" class="form-control" required></div>
                    <div class="col-md-2"><select name="status" class="form-select"><option>Present</option><option>Absent</option><option>Remote</option></select></div>
                    <div class="col-md-1"><button class="btn btn-primary w-100">Save</button></div>
                </form>
            </div></div>
            <div class="card shadow-sm"><div class="card-body table-responsive"><table class="table"><thead><tr><th>Date</th><th>Employee</th><th>Check In</th><th>Check Out</th><th>Status</th></tr></thead><tbody><?php foreach ($attendance as $a): ?><tr><td><?= e($a['attendance_date']) ?></td><td><?= e($a['full_name']) ?></td><td><?= e($a['check_in']) ?></td><td><?= e($a['check_out']) ?></td><td><span class="badge bg-<?= badgeClass($a['status']) ?>"><?= e($a['status']) ?></span></td></tr><?php endforeach; ?></tbody></table></div></div>
            <?php endif; ?>

            <?php if ($page === 'leaves'):
                $employees = $wpdb->get_results("SELECT id, full_name FROM hr_employees WHERE status='Active' ORDER BY full_name");
                $leaveTypes = $wpdb->get_results('SELECT id, name FROM hr_leave_types WHERE is_active = 1 ORDER BY name');
                $leaves = $wpdb->get_results('SELECT l.*, e.full_name, lt.name leave_type_name FROM hr_leaves l JOIN hr_employees e ON e.id=l.employee_id JOIN hr_leave_types lt ON lt.id = l.leave_type_id ORDER BY l.id DESC LIMIT 100');
            ?>
            <div class="row g-3">
                <div class="col-md-5"><div class="card shadow-sm"><div class="card-body">
                    <h5>Request Leave</h5>
                    <form method="post">
                        <?= csrfField() ?>
                        <input type="hidden" name="action" value="leave_request">
                        <div class="mb-2"><select name="employee_id" class="form-select" required><?php foreach ($employees as $e): ?><option value="<?= (int)$e['id'] ?>"><?= e($e['full_name']) ?></option><?php endforeach; ?></select></div>
                        <div class="mb-2"><select name="leave_type_id" class="form-select" required><?php foreach ($leaveTypes as $lt): ?><option value="<?= (int)$lt['id'] ?>"><?= e($lt['name']) ?></option><?php endforeach; ?></select></div>
                        <div class="row g-2 mb-2"><div class="col"><input type="date" name="date_from" class="form-control" required></div><div class="col"><input type="date" name="date_to" class="form-control" required></div></div>
                        <div class="mb-2"><textarea name="reason" class="form-control" rows="3" placeholder="Reason"></textarea></div>
                        <button class="btn btn-primary w-100">Submit</button>
                    </form>
                </div></div></div>
                <div class="col-md-7"><div class="card shadow-sm"><div class="card-body table-responsive">
                    <table class="table"><thead><tr><th>Employee</th><th>Type</th><th>Period</th><th>Status</th><th>Action</th></tr></thead><tbody>
                    <?php foreach ($leaves as $leave): ?><tr>
                        <td><?= e($leave['full_name']) ?></td><td><?= e($leave['leave_type_name']) ?></td><td><?= e($leave['date_from']) ?> → <?= e($leave['date_to']) ?></td>
                        <td><span class="badge bg-<?= badgeClass($leave['status']) ?>"><?= e($leave['status']) ?></span></td>
                        <td>
                            <?php if (roleCan($user['role_name'], ['Admin', 'HR']) && $leave['status'] === 'Pending'): ?>
                            <form method="post" class="d-flex gap-1">
                                <?= csrfField() ?>
                                <input type="hidden" name="action" value="leave_approve"><input type="hidden" name="leave_id" value="<?= (int) $leave['id'] ?>">
                                <button name="status" value="Approved" class="btn btn-sm btn-success">Approve</button>
                                <button name="status" value="Rejected" class="btn btn-sm btn-danger">Reject</button>
                            </form>
                            <?php else: ?>-
                            <?php endif; ?>
                        </td>
                    </tr><?php endforeach; ?>
                    </tbody></table>
                </div></div></div>
            </div>
            <?php endif; ?>

            <?php if ($page === 'payroll'):
                $employees = $wpdb->get_results("SELECT id, full_name FROM hr_employees WHERE status='Active' ORDER BY full_name");
                $payroll = $wpdb->get_results('SELECT p.*, e.full_name FROM hr_payroll p JOIN hr_employees e ON e.id = p.employee_id ORDER BY p.id DESC LIMIT 100');
            ?>
            <div class="card shadow-sm mb-3"><div class="card-body">
                <h5>Add Payroll</h5>
                <form method="post" class="row g-2">
                    <?= csrfField() ?>
                    <input type="hidden" name="action" value="payroll_add">
                    <div class="col-md-3"><select name="employee_id" class="form-select" required><?php foreach ($employees as $e): ?><option value="<?= (int)$e['id'] ?>"><?= e($e['full_name']) ?></option><?php endforeach; ?></select></div>
                    <div class="col-md-2"><input type="month" name="pay_period" class="form-control" required></div>
                    <div class="col-md-2"><input type="number" step="0.01" name="basic_salary" id="basicSalary" class="form-control" placeholder="Basic" required></div>
                    <div class="col-md-2"><input type="number" step="0.01" name="allowance" id="allowanceSalary" class="form-control" placeholder="Allowance" required></div>
                    <div class="col-md-2"><input type="number" step="0.01" name="deduction" id="deductionSalary" class="form-control" placeholder="Deduction" required></div>
                    <div class="col-md-1"><button class="btn btn-primary w-100">Save</button></div>
                </form>
                <div class="alert alert-primary-subtle border mt-3 mb-0 py-2">
                    Estimated Net Salary: <strong id="estimatedNetSalary">$0.00</strong>
                </div>
            </div></div>
            <div class="card shadow-sm"><div class="card-body table-responsive"><table class="table"><thead><tr><th>Employee</th><th>Period</th><th>Basic</th><th>Allow.</th><th>Deduct.</th><th>Net</th></tr></thead><tbody><?php foreach ($payroll as $p): ?><tr><td><?= e($p['full_name']) ?></td><td><?= e($p['pay_period']) ?></td><td>$<?= number_format((float) $p['basic_salary'], 2) ?></td><td>$<?= number_format((float) $p['allowance'], 2) ?></td><td>$<?= number_format((float) $p['deduction'], 2) ?></td><td><strong>$<?= number_format((float) $p['net_salary'], 2) ?></strong></td></tr><?php endforeach; ?></tbody></table></div></div>
            <?php endif; ?>


            <?php if ($page === 'features'): ?>
            <div class="card shadow-sm">
                <div class="card-body">
                    <h5 class="mb-3">PHP HRMS Feature Matrix</h5>
                    <p class="text-muted">This build includes data models and module-level support for all requested HRMS domains.</p>
                    <div class="row row-cols-1 row-cols-md-2 g-3 small">
                        <div class="col"><div class="border rounded p-3 h-100"><strong>👤 Employee Management</strong><ul class="mb-0 mt-2"><li>Employee profile (full details)</li><li>Employment history</li><li>Document upload & storage</li><li>Emergency contacts</li><li>Custom fields</li><li>Department & position assignment</li><li>Employee status (active, resigned, terminated)</li></ul></div></div>
                        <div class="col"><div class="border rounded p-3 h-100"><strong>🔐 User & Role Management</strong><ul class="mb-0 mt-2"><li>Multi-role system</li><li>Role-based permissions (RBAC)</li><li>User account management</li><li>Access control per module</li><li>Activity/audit logs</li></ul></div></div>
                        <div class="col"><div class="border rounded p-3 h-100"><strong>⏱️ Attendance Management</strong><ul class="mb-0 mt-2"><li>Clock in / clock out</li><li>Manual attendance entry</li><li>Shift scheduling</li><li>Late / early / overtime tracking</li><li>Attendance logs & reports</li><li>Geo/IP restriction (optional)</li></ul></div></div>
                        <div class="col"><div class="border rounded p-3 h-100"><strong>🏖️ Leave Management</strong><ul class="mb-0 mt-2"><li>Leave types configuration</li><li>Leave application</li><li>Approval workflow (multi-level)</li><li>Leave balance tracking</li><li>Leave calendar view</li><li>Carry forward rules</li></ul></div></div>
                        <div class="col"><div class="border rounded p-3 h-100"><strong>💰 Payroll Management</strong><ul class="mb-0 mt-2"><li>Salary structure setup</li><li>Allowances & deductions</li><li>EPF / SOCSO / PCB calculation</li><li>Payslip generation (PDF)</li><li>Bonus & incentives</li><li>Payroll reports</li></ul></div></div>
                        <div class="col"><div class="border rounded p-3 h-100"><strong>🧾 Claims & Reimbursement</strong><ul class="mb-0 mt-2"><li>Expense submission</li><li>Receipt upload</li><li>Approval workflow</li><li>Claims tracking</li><li>Payroll integration</li></ul></div></div>
                        <div class="col"><div class="border rounded p-3 h-100"><strong>📊 Performance Management</strong><ul class="mb-0 mt-2"><li>KPI / OKR setup</li><li>Employee evaluations</li><li>Appraisal cycles</li><li>Manager reviews</li><li>360-degree feedback</li></ul></div></div>
                        <div class="col"><div class="border rounded p-3 h-100"><strong>📅 Scheduling & Shifts</strong><ul class="mb-0 mt-2"><li>Shift templates</li><li>Rotating schedules</li><li>Department-based scheduling</li><li>Shift assignment</li></ul></div></div>
                        <div class="col"><div class="border rounded p-3 h-100"><strong>📁 Document Management</strong><ul class="mb-0 mt-2"><li>Company policy storage</li><li>Employee documents</li><li>File versioning</li><li>Secure access control</li></ul></div></div>
                        <div class="col"><div class="border rounded p-3 h-100"><strong>📢 Communication</strong><ul class="mb-0 mt-2"><li>Company announcements</li><li>Department notices</li><li>HR circulars</li><li>Notification system (email/system)</li></ul></div></div>
                        <div class="col"><div class="border rounded p-3 h-100"><strong>📈 Reports & Analytics</strong><ul class="mb-0 mt-2"><li>Attendance reports</li><li>Payroll reports</li><li>Leave reports</li><li>Employee headcount</li><li>Custom report export (CSV/PDF)</li></ul></div></div>
                        <div class="col"><div class="border rounded p-3 h-100"><strong>🧑‍💼 Recruitment (ATS)</strong><ul class="mb-0 mt-2"><li>Job posting</li><li>Candidate management</li><li>Interview scheduling</li><li>Hiring pipeline tracking</li></ul></div></div>
                        <div class="col"><div class="border rounded p-3 h-100"><strong>⚙️ System Settings</strong><ul class="mb-0 mt-2"><li>Company settings</li><li>Leave & payroll rules</li><li>Holiday calendar</li><li>Localization (currency, timezone)</li></ul></div></div>
                        <div class="col"><div class="border rounded p-3 h-100"><strong>🔔 Notifications</strong><ul class="mb-0 mt-2"><li>Email notifications</li><li>System alerts</li><li>Approval reminders</li></ul></div></div>
                        <div class="col"><div class="border rounded p-3 h-100"><strong>🔐 Security & Compliance</strong><ul class="mb-0 mt-2"><li>Password encryption</li><li>CSRF protection (application layer)</li><li>RBAC enforcement</li><li>Audit logs</li></ul></div></div>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <?php if ($page === 'reports'):
                $departmentBreakdown = $wpdb->get_results('SELECT d.name, COUNT(e.id) total FROM hr_departments d LEFT JOIN hr_employees e ON e.department_id=d.id GROUP BY d.id ORDER BY total DESC');
                $maxDepartmentSize = 0;
                foreach ($departmentBreakdown as $deptRow) {
                    $maxDepartmentSize = max($maxDepartmentSize, (int) $deptRow['total']);
                }
            ?>
            <div class="card shadow-sm"><div class="card-body">
                <h5>Department Workforce Distribution</h5>
                <table class="table align-middle"><thead><tr><th>Department</th><th style="width: 50%;">Distribution</th><th>Total Employees</th></tr></thead><tbody><?php foreach ($departmentBreakdown as $row): $width = $maxDepartmentSize > 0 ? (((int)$row['total'] / $maxDepartmentSize) * 100) : 0; ?><tr><td><?= e($row['name']) ?></td><td><div class="progress" role="progressbar" aria-label="<?= e($row['name']) ?> distribution"><div class="progress-bar" style="width: <?= $width ?>%"></div></div></td><td><strong><?= e((string)$row['total']) ?></strong></td></tr><?php endforeach; ?></tbody></table>
                <p class="text-muted mb-0">Additional extensible modules to add next: recruitment ATS, onboarding workflows, appraisal cycles, training LMS, compliance audits, asset assignment, and employee self-service API.</p>
            </div></div>
            <?php endif; ?>
        </main>
    </div>
</div>
<?php endif; ?>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/app.js"></script>
</body>
</html>
