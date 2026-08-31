<?php

require_once __DIR__ . '/functions.php';

function page_start(string $title, string $active = ''): void
{
    $flash = get_flash();
    $prefix = $active === 'root' ? '' : '../';
    $GLOBALS['cloudfleet_prefix'] = $prefix;
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <meta name="description" content="CloudFleet - fleet and tour management system">
        <title><?= e($title) ?> | CloudFleet</title>
        <link rel="stylesheet" href="<?= e($prefix) ?>assets/css/app.css">
    </head>
    <body>
    <header class="topbar">
        <div class="topbar-left">
            <button class="menu-toggle" id="menuToggle" type="button" aria-label="Open navigation">☰</button>
            <a class="brand" href="<?= e($prefix) ?>index.php">☁ CloudFleet</a>
        </div>
    </header>

    <div class="sidebar-backdrop" id="sidebarBackdrop"></div>

    <div class="layout">
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-title">OVERVIEW</div>
            <a class="<?= in_array($active, ['dashboard', 'root'], true) ? 'active' : '' ?>" href="<?= e($prefix) ?>index.php">Dashboard</a>

            <div class="sidebar-title">OPERATIONS</div>
            <a class="<?= $active === 'tours' ? 'active' : '' ?>" href="<?= e($prefix) ?>tours/">Tours</a>
            <a class="<?= $active === 'assignments' ? 'active' : '' ?>" href="<?= e($prefix) ?>assignments/">Assignments</a>
            <a class="<?= $active === 'schedule' ? 'active' : '' ?>" href="<?= e($prefix) ?>schedule/">Schedule</a>

            <div class="sidebar-title">FLEET</div>
            <a class="<?= $active === 'vehicles' ? 'active' : '' ?>" href="<?= e($prefix) ?>vehicles/">Vehicles</a>
            <a class="<?= $active === 'maintenance' ? 'active' : '' ?>" href="<?= e($prefix) ?>maintenance/">Maintenance</a>

            <div class="sidebar-title">PERSONNEL</div>
            <a class="<?= $active === 'drivers' ? 'active' : '' ?>" href="<?= e($prefix) ?>drivers/">Drivers</a>

            <div class="sidebar-title">FILES</div>
            <a class="<?= $active === 'documents' ? 'active' : '' ?>" href="<?= e($prefix) ?>documents/">Documents</a>
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
    $scriptPrefix = $GLOBALS['cloudfleet_prefix'] ?? '';
    ?>
        </main>
    </div>
    <script src="<?= e($scriptPrefix) ?>assets/js/app.js"></script>
    </body>
    </html>
    <?php
}
