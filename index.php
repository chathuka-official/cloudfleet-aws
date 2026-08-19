<?php

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/layout.php';

$stats = [
    'vehicles' => (int)$pdo->query("SELECT COUNT(*) FROM vehicles")->fetchColumn(),
    'drivers' => (int)$pdo->query("SELECT COUNT(*) FROM drivers")->fetchColumn(),
    'tours' => (int)$pdo->query("SELECT COUNT(*) FROM tours WHERE status IN ('SCHEDULED','IN_PROGRESS')")->fetchColumn(),
    'maintenance' => (int)$pdo->query("SELECT COUNT(*) FROM maintenance_records WHERE status <> 'COMPLETED'")->fetchColumn(),
];

$upcoming = $pdo->query("
    SELECT t.id, t.tour_code, t.title, t.destination, t.departure_time, t.status,
           v.vehicle_name, d.full_name AS driver_name
    FROM tours t
    LEFT JOIN tour_assignments a ON a.tour_id = t.id
    LEFT JOIN vehicles v ON v.id = a.vehicle_id
    LEFT JOIN drivers d ON d.id = a.driver_id
    WHERE t.status IN ('SCHEDULED','IN_PROGRESS')
    ORDER BY t.departure_time ASC
    LIMIT 5
")->fetchAll();

page_start('Dashboard', 'root');
?>

<div class="page-header">
    <div>
        <h1>Operations Dashboard</h1>
        <p>CloudFleet running on AWS Elastic Beanstalk + Amazon RDS.</p>
    </div>
    <a class="btn btn-primary" href="tours/create.php">+ Create Tour</a>
</div>

<div class="stats-grid">
    <div class="stat-card"><div class="stat-label">Vehicles</div><div class="stat-number"><?= $stats['vehicles'] ?></div></div>
    <div class="stat-card"><div class="stat-label">Drivers</div><div class="stat-number"><?= $stats['drivers'] ?></div></div>
    <div class="stat-card"><div class="stat-label">Active Tours</div><div class="stat-number"><?= $stats['tours'] ?></div></div>
    <div class="stat-card"><div class="stat-label">Open Maintenance</div><div class="stat-number"><?= $stats['maintenance'] ?></div></div>
</div>

<div class="panel">
    <div style="padding:20px;border-bottom:1px solid #e5e7eb;">
        <h2 style="margin:0;">Upcoming Operations</h2>
    </div>

    <?php if (!$upcoming): ?>
        <div class="empty-state">No scheduled tours.</div>
    <?php else: ?>
        <table>
            <thead>
                <tr><th>Tour</th><th>Destination</th><th>Departure</th><th>Vehicle</th><th>Driver</th><th>Status</th></tr>
            </thead>
            <tbody>
            <?php foreach ($upcoming as $tour): ?>
                <tr>
                    <td><div class="entity-name"><?= e($tour['title']) ?></div><div class="entity-code"><?= e($tour['tour_code']) ?></div></td>
                    <td><?= e($tour['destination']) ?></td>
                    <td><?= e(date('d M Y H:i', strtotime($tour['departure_time']))) ?></td>
                    <td><?= e($tour['vehicle_name'] ?? 'Not assigned') ?></td>
                    <td><?= e($tour['driver_name'] ?? 'Not assigned') ?></td>
                    <td><span class="badge <?= status_badge($tour['status']) ?>"><?= e(str_replace('_',' ',$tour['status'])) ?></span></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<div style="margin-top:16px;" class="alert success">
    🚀 GitHub Actions → AWS OIDC → Elastic Beanstalk deployment pipeline ready.
</div>

<?php page_end(); ?>
