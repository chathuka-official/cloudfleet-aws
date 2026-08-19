<?php

require_once __DIR__ . '/functions.php';

function page_start(string $title, string $active = ''): void
{
    $flash = get_flash();
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title><?= e($title) ?> | CloudFleet</title>
        <link rel="stylesheet" href="<?= $active === 'root' ? 'assets/css/app.css' : '../assets/css/app.css' ?>">
    </head>
    <body>
    <header class="topbar">
        <div class="brand">☁ CloudFleet</div>
        <div class="environment">AWS Development Environment</div>
    </header>

    <div class="layout">
        <aside class="sidebar">
            <div class="sidebar-title">OVERVIEW</div>
            <a class="<?= in_array($active, ['dashboard','root'], true) ? 'active' : '' ?>" href="<?= $active === 'root' ? 'index.php' : '../index.php' ?>">Dashboard</a>

            <div class="sidebar-title">OPERATIONS</div>
            <a class="<?= $active === 'tours' ? 'active' : '' ?>" href="<?= $active === 'root' ? 'tours/' : '../tours/' ?>">Tours</a>
            <a class="<?= $active === 'assignments' ? 'active' : '' ?>" href="<?= $active === 'root' ? 'assignments/' : '../assignments/' ?>">Assignments</a>
            <a class="<?= $active === 'schedule' ? 'active' : '' ?>" href="<?= $active === 'root' ? 'schedule/' : '../schedule/' ?>">Schedule</a>

            <div class="sidebar-title">FLEET</div>
            <a class="<?= $active === 'vehicles' ? 'active' : '' ?>" href="<?= $active === 'root' ? 'vehicles/' : '../vehicles/' ?>">Vehicles</a>
            <a class="<?= $active === 'maintenance' ? 'active' : '' ?>" href="<?= $active === 'root' ? 'maintenance/' : '../maintenance/' ?>">Maintenance</a>

            <div class="sidebar-title">PERSONNEL</div>
            <a class="<?= $active === 'drivers' ? 'active' : '' ?>" href="<?= $active === 'root' ? 'drivers/' : '../drivers/' ?>">Drivers</a>

            <div class="sidebar-title">FILES</div>
            <a class="<?= $active === 'documents' ? 'active' : '' ?>" href="<?= $active === 'root' ? 'documents/' : '../documents/' ?>">Documents (S3)</a>
        </aside>

        <main class="content">
            <?php if ($flash): ?>
                <div class="alert <?= e($flash['type']) ?>">
                    <?= e($flash['message']) ?>
                </div>
            <?php endif; ?>
    <?php
}

function page_end(): void
{
    ?>
        </main>
    </div>
    </body>
    </html>
    <?php
}
