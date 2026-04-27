<?php

declare(strict_types=1);

function currentUser(): ?array
{
    return $_SESSION['user'] ?? null;
}

function requireAuth(): void
{
    if (!currentUser()) {
        flash('Please login first.', 'danger');
        redirect('index.php?page=login');
    }
}

function requireRole(array $roles): void
{
    $user = currentUser();
    if (!$user || !roleCan($user['role_name'], $roles)) {
        flash('Access denied for your role.', 'danger');
        redirect('index.php?page=dashboard');
    }
}

function loginUser(wpdb $wpdb, string $email, string $password): bool
{
    $sql = $wpdb->prepare(
        'SELECT u.id, u.full_name, u.email, u.password_hash, r.name AS role_name FROM hr_users u JOIN hr_roles r ON r.id = u.role_id WHERE u.email = %s AND u.is_active = 1 LIMIT 1',
        [$email]
    );
    $user = $wpdb->get_row($sql);

    if (!$user || !password_verify($password, $user['password_hash'])) {
        return false;
    }

    unset($user['password_hash']);
    $_SESSION['user'] = $user;
    return true;
}

function registerUser(wpdb $wpdb, string $fullName, string $email, string $password): bool
{
    if ($fullName === '' || $email === '' || $password === '') {
        return false;
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return false;
    }

    $existingUserSql = $wpdb->prepare('SELECT id FROM hr_users WHERE email = %s LIMIT 1', [$email]);
    $existingUser = $wpdb->get_row($existingUserSql);
    if ($existingUser) {
        return false;
    }

    $totalUsers = (int) ($wpdb->get_row('SELECT COUNT(*) AS c FROM hr_users')['c'] ?? 0);
    $roleId = $totalUsers === 0 ? 1 : 5; // First account becomes Admin, all following accounts default to Employee.

    return $wpdb->insert('hr_users', [
        'full_name' => $fullName,
        'email' => $email,
        'password_hash' => password_hash($password, PASSWORD_DEFAULT),
        'role_id' => $roleId,
        'is_active' => 1,
    ]);
}

function logoutUser(): void
{
    $_SESSION = [];
    session_destroy();
}
