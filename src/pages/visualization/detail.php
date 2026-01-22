<?php
require_once __DIR__ . '/../../config/db.php';

$workstation_id = $_GET['workstation_id'] ?? null;

if (!$workstation_id) {
    header('Location: /visualization');
    exit;
}

// Info du poste
$stmt = $pdo->prepare("SELECT * FROM workstation WHERE workstation_id = ?");
$stmt->execute([$workstation_id]);
$workstation = $stmt->fetch();

if (!$workstation) {
    header('Location: /visualization');
    exit;
}

// Stock du poste
$stmt = $pdo->prepare("
    SELECT 
        p.part_name,
        p.reference,
        ws.available_quantity,
        ws.minimum_quantity,
        ROUND(ws.available_quantity * 100.0 / NULLIF(ws.minimum_quantity, 0), 2) as stock_percentage
    FROM workstation_stock ws
    JOIN part p ON ws.part_id = p.part_id
    WHERE ws.workstation_id = ?
    ORDER BY p.part_name
");
$stmt->execute([$workstation_id]);
$stock = $stmt->fetchAll();

// Temps d'exécution par étape
$stmt = $pdo->prepare("
    SELECT 
        s.step_number,
        s.step_name,
        AVG(et.execution_time) as avg_time,
        MIN(et.execution_time) as min_time,
        MAX(et.execution_time) as max_time,
        s.standard_time,
        COUNT(et.time_id) as executions_count
    FROM step s
    LEFT JOIN execution_time et ON s.step_id = et.step_id
    WHERE s.workstation_id = ?
    GROUP BY s.step_id
    ORDER BY s.execution_order
");
$stmt->execute([$workstation_id]);
$steps_times = $stmt->fetchAll();

// Historique des temps (dernières 20 exécutions)
$stmt = $pdo->prepare("
    SELECT 
        et.execution_time,
        et.execution_date,
        s.step_name,
        s.step_number,
        CONCAT(o.first_name, ' ', o.last_name) as operator_name
    FROM execution_time et
    JOIN step s ON et.step_id = s.step_id
    JOIN operator o ON et.operator_id = o.operator_id
    WHERE et.workstation_id = ?
    ORDER BY et.execution_date DESC
    LIMIT 20
");
$stmt->execute([$workstation_id]);
$history = $stmt->fetchAll();
?>

<div class="page-header">
    <div>
        <a href="/visualization" class="btn btn-secondary">← Retour</a>
        <h2>📊 Poste <?= $workstation['workstation_number'] ?>: <?= htmlspecialchars($workstation['workstation_name']) ?></h2>
    </div>
</div>

<!-- Stock du poste -->
<div class="section">
    <h3>📦 État du stock</h3>
    <?php if (empty($stock)): ?>
        <p class="empty-state">Aucune pièce en stock pour ce poste</p>
    <?php else: ?>
        <table class="data-table">
            <thead>
                <tr>
                    <th>Référence</th>
                    <th>Pièce</th>
                    <th>Quantité disponible</th>
                    <th>Minimum requis</th>
                    <th>Pourcentage</th>
                    <th>Statut</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($stock as $item): ?>
                <tr class="<?= $item['available_quantity'] < $item['minimum_quantity'] ? 'row-warning' : '' ?>">
                    <td><code><?= htmlspecialchars($item['reference']) ?></code></td>
                    <td><?= htmlspecialchars($item['part_name']) ?></td>
                    <td><strong><?= $item['available_quantity'] ?></strong></td>
                    <td><?= $item['minimum_quantity'] ?></td>
                    <td><?= round($item['stock_percentage']) ?>%</td>
                    <td>
                        <?php if ($item['available_quantity'] < $item['minimum_quantity']): ?>
                            <span class="badge badge-danger">⚠️ Niveau bas</span>
                        <?php elseif ($item['stock_percentage'] < 150): ?>
                            <span class="badge badge-warning">⚡ Surveiller</span>
                        <?php else: ?>
                            <span class="badge badge-success">✅ OK</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<!-- Temps par étape -->
<div class="section">
    <h3>⏱️ Temps d'exécution par étape</h3>
    <?php if (empty($steps_times)): ?>
        <p class="empty-state">Aucune donnée d'exécution disponible</p>
    <?php else: ?>
        <table class="data-table">
            <thead>
                <tr>
                    <th>Étape</th>
                    <th>Nom</th>
                    <th>Temps moyen</th>
                    <th>Temps min</th>
                    <th>Temps max</th>
                    <th>Temps standard</th>
                    <th>Nb exécutions</th>
                    <th>Performance</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($steps_times as $step): ?>
                <tr>
                    <td><strong>Étape <?= $step['step_number'] ?></strong></td>
                    <td><?= htmlspecialchars($step['step_name']) ?></td>
                    <td><strong><?= round($step['avg_time']) ?>s</strong></td>
                    <td><?= round($step['min_time']) ?>s</td>
                    <td><?= round($step['max_time']) ?>s</td>
                    <td><?= $step['standard_time'] ?? 'N/A' ?><?= $step['standard_time'] ? 's' : '' ?></td>
                    <td><?= $step['executions_count'] ?></td>
                    <td>
                        <?php if ($step['standard_time'] && $step['avg_time']): ?>
                            <?php 
                            $perf = ($step['standard_time'] / $step['avg_time']) * 100;
                            if ($perf >= 95): ?>
                                <span class="badge badge-success">✅ Conforme</span>
                            <?php elseif ($perf >= 80): ?>
                                <span class="badge badge-warning">⚡ À surveiller</span>
                            <?php else: ?>
                                <span class="badge badge-danger">⚠️ Retard</span>
                            <?php endif; ?>
                        <?php else: ?>
                            <span class="badge badge-secondary">-</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<!-- Historique -->
<?php if (!empty($history)): ?>
<div class="section">
    <h3>📈 Historique des exécutions (20 dernières)</h3>
    <table class="data-table">
        <thead>
            <tr>
                <th>Date & Heure</th>
                <th>Étape</th>
                <th>Opérateur</th>
                <th>Temps d'exécution</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($history as $exec): ?>
            <tr>
                <td><?= date('d/m/Y H:i:s', strtotime($exec['execution_date'])) ?></td>
                <td>Étape <?= $exec['step_number'] ?> - <?= htmlspecialchars($exec['step_name']) ?></td>
                <td><?= htmlspecialchars($exec['operator_name']) ?></td>
                <td><strong><?= $exec['execution_time'] ?>s</strong></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>