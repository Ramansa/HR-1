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

    if ($action === 'employee_create' && roleCan($user['role_name'], ['Admin', 'HR Manager'])) {
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

    if ($action === 'attendance_mark' && roleCan($user['role_name'], ['Admin', 'HR Manager', 'Team Lead'])) {
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
            'leave_type' => trim($_POST['leave_type']),
            'date_from' => $_POST['date_from'],
            'date_to' => $_POST['date_to'],
            'reason' => trim($_POST['reason']),
            'status' => 'Pending',
        ]);
        flash('Leave request submitted.');
        redirect('index.php?page=leaves');
    }

    if ($action === 'leave_approve' && roleCan($user['role_name'], ['Admin', 'HR Manager'])) {
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
            ?>
                <div class="row g-3 mb-4">
                    <div class="col-md-3"><div class="card stat-card"><div class="card-body"><h6>Total Staff</h6><h3><?= e((string) $totalEmployees) ?></h3></div></div></div>
                    <div class="col-md-3"><div class="card stat-card"><div class="card-body"><h6>Pending Leaves</h6><h3><?= e((string) $activeLeaves) ?></h3></div></div></div>
                    <div class="col-md-3"><div class="card stat-card"><div class="card-body"><h6>Today Present</h6><h3><?= e((string) $todayPresence) ?></h3></div></div></div>
                    <div class="col-md-3"><div class="card stat-card"><div class="card-body"><h6>Monthly Payroll</h6><h3>$<?= number_format((float) $monthlyPayroll, 0) ?></h3></div></div></div>
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
                        <input type="hidden" name="action" value="employee_create">
                        <div class="col-md-2"><input name="employee_code" class="form-control" placeholder="EMP1001" required></div>
                        <div class="col-md-3"><input name="full_name" class="form-control" placeholder="Full name" required></div>
                        <div class="col-md-3"><input name="email" type="email" class="form-control" placeholder="Email" required></div>
                        <div class="col-md-2"><input name="phone" class="form-control" placeholder="Phone"></div>
                        <div class="col-md-2"><input name="position" class="form-control" placeholder="Position" required></div>
                        <div class="col-md-3"><select name="department_id" class="form-select" required><?php foreach ($departments as $d): ?><option value="<?= (int) $d['id'] ?>"><?= e($d['name']) ?></option><?php endforeach; ?></select></div>
                        <div class="col-md-2"><input type="date" name="hire_date" class="form-control" required></div>
                        <div class="col-md-2"><select name="status" class="form-select"><option>Active</option><option>Inactive</option></select></div>
                        <div class="col-md-3"><select name="manager_id" class="form-select"><option value="0">No Manager</option><?php foreach ($managers as $m): ?><option value="<?= (int) $m['id'] ?>"><?= e($m['full_name']) ?></option><?php endforeach; ?></select></div>
                        <div class="col-md-2"><button class="btn btn-primary w-100">Save</button></div>
                    </form>
                </div>
            </div>
            <div class="card shadow-sm">
                <div class="card-body table-responsive">
                    <table class="table table-hover" id="dataTable">
                        <thead><tr><th>Code</th><th>Name</th><th>Department</th><th>Position</th><th>Status</th></tr></thead>
                        <tbody><?php foreach ($employees as $emp): ?><tr><td><?= e($emp['employee_code']) ?></td><td><?= e($emp['full_name']) ?><br><small class="text-muted"><?= e($emp['email']) ?></small></td><td><?= e((string) $emp['department']) ?></td><td><?= e($emp['position']) ?></td><td><span class="badge bg-<?= badgeClass($emp['status']) ?>"><?= e($emp['status']) ?></span></td></tr><?php endforeach; ?></tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?>

            <?php if ($page === 'attendance'):
                $employees = $wpdb->get_results("SELECT id, full_name FROM hr_employees WHERE status='Active' ORDER BY full_name");
                $attendance = $wpdb->get_results('SELECT a.*, e.full_name FROM hr_attendance a JOIN hr_employees e ON e.id = a.employee_id ORDER BY a.attendance_date DESC LIMIT 100');
            ?>
            <div class="card shadow-sm mb-3"><div class="card-body">
                <form method="post" class="row g-2">
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
                $leaves = $wpdb->get_results('SELECT l.*, e.full_name FROM hr_leaves l JOIN hr_employees e ON e.id=l.employee_id ORDER BY l.id DESC LIMIT 100');
            ?>
            <div class="row g-3">
                <div class="col-md-5"><div class="card shadow-sm"><div class="card-body">
                    <h5>Request Leave</h5>
                    <form method="post">
                        <input type="hidden" name="action" value="leave_request">
                        <div class="mb-2"><select name="employee_id" class="form-select" required><?php foreach ($employees as $e): ?><option value="<?= (int)$e['id'] ?>"><?= e($e['full_name']) ?></option><?php endforeach; ?></select></div>
                        <div class="mb-2"><select name="leave_type" class="form-select"><option>Annual</option><option>Sick</option><option>Emergency</option><option>Maternity/Paternity</option></select></div>
                        <div class="row g-2 mb-2"><div class="col"><input type="date" name="date_from" class="form-control" required></div><div class="col"><input type="date" name="date_to" class="form-control" required></div></div>
                        <div class="mb-2"><textarea name="reason" class="form-control" rows="3" placeholder="Reason"></textarea></div>
                        <button class="btn btn-primary w-100">Submit</button>
                    </form>
                </div></div></div>
                <div class="col-md-7"><div class="card shadow-sm"><div class="card-body table-responsive">
                    <table class="table"><thead><tr><th>Employee</th><th>Type</th><th>Period</th><th>Status</th><th>Action</th></tr></thead><tbody>
                    <?php foreach ($leaves as $leave): ?><tr>
                        <td><?= e($leave['full_name']) ?></td><td><?= e($leave['leave_type']) ?></td><td><?= e($leave['date_from']) ?> → <?= e($leave['date_to']) ?></td>
                        <td><span class="badge bg-<?= badgeClass($leave['status']) ?>"><?= e($leave['status']) ?></span></td>
                        <td>
                            <?php if (roleCan($user['role_name'], ['Admin', 'HR Manager']) && $leave['status'] === 'Pending'): ?>
                            <form method="post" class="d-flex gap-1">
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
                    <input type="hidden" name="action" value="payroll_add">
                    <div class="col-md-3"><select name="employee_id" class="form-select" required><?php foreach ($employees as $e): ?><option value="<?= (int)$e['id'] ?>"><?= e($e['full_name']) ?></option><?php endforeach; ?></select></div>
                    <div class="col-md-2"><input type="month" name="pay_period" class="form-control" required></div>
                    <div class="col-md-2"><input type="number" step="0.01" name="basic_salary" class="form-control" placeholder="Basic" required></div>
                    <div class="col-md-2"><input type="number" step="0.01" name="allowance" class="form-control" placeholder="Allowance" required></div>
                    <div class="col-md-2"><input type="number" step="0.01" name="deduction" class="form-control" placeholder="Deduction" required></div>
                    <div class="col-md-1"><button class="btn btn-primary w-100">Save</button></div>
                </form>
            </div></div>
            <div class="card shadow-sm"><div class="card-body table-responsive"><table class="table"><thead><tr><th>Employee</th><th>Period</th><th>Basic</th><th>Allow.</th><th>Deduct.</th><th>Net</th></tr></thead><tbody><?php foreach ($payroll as $p): ?><tr><td><?= e($p['full_name']) ?></td><td><?= e($p['pay_period']) ?></td><td>$<?= number_format((float) $p['basic_salary'], 2) ?></td><td>$<?= number_format((float) $p['allowance'], 2) ?></td><td>$<?= number_format((float) $p['deduction'], 2) ?></td><td><strong>$<?= number_format((float) $p['net_salary'], 2) ?></strong></td></tr><?php endforeach; ?></tbody></table></div></div>
            <?php endif; ?>

            <?php if ($page === 'reports'):
                $departmentBreakdown = $wpdb->get_results('SELECT d.name, COUNT(e.id) total FROM hr_departments d LEFT JOIN hr_employees e ON e.department_id=d.id GROUP BY d.id ORDER BY total DESC');
            ?>
            <div class="card shadow-sm"><div class="card-body">
                <h5>Department Workforce Distribution</h5>
                <table class="table"><thead><tr><th>Department</th><th>Total Employees</th></tr></thead><tbody><?php foreach ($departmentBreakdown as $row): ?><tr><td><?= e($row['name']) ?></td><td><?= e((string)$row['total']) ?></td></tr><?php endforeach; ?></tbody></table>
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
