<?php

function e(?string $value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function redirect(string $location): never
{
    header('Location: ' . $location);
    exit;
}

function start_session_once(): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
}

function csrf_token(): string
{
    start_session_once();

    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function verify_csrf(): void
{
    start_session_once();

    $token = $_POST['csrf_token'] ?? '';

    if (
        empty($_SESSION['csrf_token']) ||
        !is_string($token) ||
        !hash_equals($_SESSION['csrf_token'], $token)
    ) {
        http_response_code(403);
        die('Invalid request.');
    }
}

function flash(string $type, string $message): void
{
    start_session_once();
    $_SESSION['flash'] = [
        'type' => $type,
        'message' => $message
    ];
}

function get_flash(): ?array
{
    start_session_once();

    $flash = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);

    return $flash;
}

function status_badge(string $status): string
{
    return match ($status) {
        'AVAILABLE', 'ACTIVE', 'COMPLETED' => 'badge-success',
        'ASSIGNED', 'SCHEDULED' => 'badge-info',
        'MAINTENANCE', 'ON_LEAVE', 'IN_PROGRESS', 'SUSPENDED' => 'badge-warning',
        'INACTIVE', 'TERMINATED', 'CANCELLED', 'EXPIRED' => 'badge-danger',
        default => 'badge-neutral'
    };
}
