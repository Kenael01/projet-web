<?php
require_once __DIR__ . '/../config/db.php';

// Récupérer les stats globales
$stmt = $pdo->query("
    SELECT COUNT(*) as total_workstations 
    FROM workstation 
    WHERE is_active = 1
");
$stats = $stmt->fetch();

$stmt = $pdo->query("
    SELECT COUNT(*) as total_parts 
    FROM part
");
$parts_stats = $stmt->fetch();

$stmt = $pdo->query("
    SELECT COUNT(*) as total_operators 
    FROM operator 
    WHERE is_active = 1
");
$operators_stats = $stmt->fetch();

// Dernières exécutions
$stmt = $pdo->query("
    SELECT 
        et.execution_time,
        et.execution_date,
        w.workstation_name,
        s.step_name,
        CONCAT(o.first_name, ' ', o.last_name) as operator_name
    FROM execution_time et
    JOIN workstation w ON et.workstation_id = w.workstation_id
    JOIN step s ON et.step_id = s.step_id
    JOIN operator o ON et.operator_id = o.operator_id
    ORDER BY et.execution_date DESC
    LIMIT 5
");
$recent_executions = $stmt->fetchAll();
?>

<div class="welcome-section">
    <h2>Tableau de bord CesiBike</h2>
    <p>Bienvenue sur le système de supervision de production</p>
</div>

<div class="stats-grid">
    <div class="stat-card">
        <h3>Postes de travail</h3>
        <div class="stat-number"><?= $stats['total_workstations'] ?></div>
        <p>postes actifs</p>
    </div>
    <div class="stat-card">
        <h3>Pièces</h3>
        <div class="stat-number"><?= $parts_stats['total_parts'] ?></div>
        <p>références</p>
    </div>
    <div class="stat-card">
        <h3>Opérateurs</h3>
        <div class="stat-number"><?= $operators_stats['total_operators'] ?></div>
        <p>actifs</p>
    </div>
</div>

<div class="section">
    <h3>Dernières exécutions</h3>
    <table class="data-table">
        <thead>
            <tr>
                <th>Date</th>
                <th>Poste</th>
                <th>Étape</th>
                <th>Opérateur</th>
                <th>Temps (s)</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($recent_executions as $exec): ?>
            <tr>
                <td><?= date('d/m/Y H:i', strtotime($exec['execution_date'])) ?></td>
                <td><?= htmlspecialchars($exec['workstation_name']) ?></td>
                <td><?= htmlspecialchars($exec['step_name']) ?></td>
                <td><?= htmlspecialchars($exec['operator_name']) ?></td>
                <td><?= $exec['execution_time'] ?>s</td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<div class="action-section">
    <h3>Actions rapides</h3>
    <div class="button-group">
        <a href="/configuration" class="btn btn-primary">⚙️ Configurer les postes</a>
        <a href="/visualization" class="btn btn-secondary">📊 Visualiser la production</a>
        <a href="/import-csv" class="btn btn-accent">📁 Importer des données CSV</a>
    </div>
</div>