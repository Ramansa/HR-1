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

function logoutUser(): void
{
    $_SESSION = [];
    session_destroy();
}
