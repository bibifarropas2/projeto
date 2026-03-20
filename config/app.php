<?php

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

function redirect(string $path): void
{
    header("Location: {$path}");
    exit;
}

function require_auth(): void
{
    if (!isset($_SESSION['user_id'])) {
        redirect('login.php');
    }
}

function redirect_if_authenticated(): void
{
    if (isset($_SESSION['user_id'])) {
        redirect('dashboard.php');
    }
}

function h(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function csrf_validate(?string $token): bool
{
    if (empty($_SESSION['csrf_token']) || empty($token)) {
        return false;
    }

    return hash_equals($_SESSION['csrf_token'], $token);
}
