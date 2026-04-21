<?php

declare(strict_types=1);

const DB_HOST = '127.0.0.1';
const DB_NAME = 'hr_system';
const DB_USER = 'root';
const DB_PASSWORD = '';
const DB_CHARSET = 'utf8mb4';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
