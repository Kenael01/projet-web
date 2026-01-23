<?php
require_once __DIR__ . '/../config/db.php';

// Récupérer tous les postes avec leurs stats
$workstations = $pdo->query("
    SELECT 
        w.workstation_id,
        w.workstation_number,
        w.workstation_name,
        COALESCE(AVG(ws.available_quantity * 100.0 / NULLIF(ws.minimum_quantity * 2, 0)), 100) as stock_percentage,
        COALESCE(AVG(et.execution_time), 0) as avg_time,
        COALESCE(MAX(et.execution_date), NULL) as last_execution
    FROM workstation w
    LEFT JOIN workstation_stock ws ON w.workstation_id = ws.workstation_id
    LEFT JOIN execution_time et ON w.workstation_id = et.workstation_id
    WHERE w.is_active = 1
    GROUP BY w.workstation_id
    ORDER BY w.workstation_number
")->fetchAll();
?>

<div class="page-header">
    <h2> Visualisation de la production</h2>
    <div class="button-group">
        <button class="btn btn-primary" onclick="location.reload()"> Rafraîchir</button>
    </div>
</div>

<!-- Vue d'ensemble -->
<div class="section">
    <h3>Vue d'ensemble des postes</h3>
    <table class="data-table">
        <thead>
            <tr>
                <th>Poste</th>
                <th>Nom</th>
                <th>Stock (%)</th>
                <th>Temps moyen (s)</th>
                <th>Dernière exécution</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($workstations as $ws): ?>
            <tr>
                <td><strong>Poste <?= $ws['workstation_number'] ?></strong></td>
                <td><?= htmlspecialchars($ws['workstation_name']) ?></td>
                <td>
                    <span class="stock-status <?= $ws['stock_percentage'] < 50 ? 'low' : ($ws['stock_percentage'] < 80 ? 'medium' : 'good') ?>">
                        <?= round($ws['stock_percentage']) ?>%
                    </span>
                </td>
                <td><?= round($ws['avg_time']) ?>s</td>
                <td><?= $ws['last_execution'] ? date('d/m/Y H:i', strtotime($ws['last_execution'])) : 'Aucune' ?></td>
                <td>
                    <a href="/visualization/detail?workstation_id=<?= $ws['workstation_id'] ?>" class="btn btn-sm btn-primary">
                         Voir détails
                    </a>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<!-- Alertes stock -->
<div class="section">
    <h3> Alertes stock</h3>
    <?php
    $alerts = $pdo->query("
        SELECT 
            w.workstation_number,
            w.workstation_name,
            p.part_name,
            ws.available_quantity,
            ws.minimum_quantity
        FROM workstation_stock ws
        JOIN workstation w ON ws.workstation_id = w.workstation_id
        JOIN part p ON ws.part_id = p.part_id
        WHERE ws.available_quantity < ws.minimum_quantity
        ORDER BY ws.available_quantity ASC
    ")->fetchAll();
    ?>
    
    <?php if (empty($alerts)): ?>
        <p class="alert-success"> Tous les stocks sont au niveau requis</p>
    <?php else: ?>
        <table class="data-table">
            <thead>
                <tr>
                    <th>Poste</th>
                    <th>Pièce</th>
                    <th>Disponible</th>
                    <th>Minimum requis</th>
                    <th>Statut</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($alerts as $alert): ?>
                <tr class="row-warning">
                    <td>Poste <?= $alert['workstation_number'] ?> - <?= htmlspecialchars($alert['workstation_name']) ?></td>
                    <td><?= htmlspecialchars($alert['part_name']) ?></td>
                    <td><strong><?= $alert['available_quantity'] ?></strong></td>
                    <td><?= $alert['minimum_quantity'] ?></td>
                    <td><span class="badge badge-danger"> Niveau bas</span></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>