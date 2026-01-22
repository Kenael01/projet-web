<?php
require_once __DIR__ . '/../config/db.php';

$step_id = $_GET['step_id'] ?? null;

if (!$step_id) {
    header('Location: /configuration');
    exit;
}

// Récupérer les infos de l'étape et du poste
$stmt = $pdo->prepare("
    SELECT s.*, w.workstation_id, w.workstation_number, w.workstation_name
    FROM step s
    JOIN workstation w ON s.workstation_id = w.workstation_id
    WHERE s.step_id = ?
");
$stmt->execute([$step_id]);
$step = $stmt->fetch();

if (!$step) {
    header('Location: /configuration');
    exit;
}

// SUPPRESSION d'une pièce de l'étape
if (isset($_GET['delete_part'])) {
    $step_part_id = $_GET['delete_part'];
    $stmt = $pdo->prepare("DELETE FROM step_part WHERE step_part_id = ?");
    $stmt->execute([$step_part_id]);
    header("Location: /configuration/step-parts?step_id=$step_id");
    exit;
}

// AJOUT d'une pièce à l'étape
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_part'])) {
    $part_id = $_POST['part_id'];
    $required_quantity = $_POST['required_quantity'];
    
    // Vérifier si la pièce n'est pas déjà associée
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as count 
        FROM step_part 
        WHERE step_id = ? AND part_id = ?
    ");
    $stmt->execute([$step_id, $part_id]);
    $exists = $stmt->fetch()['count'];
    
    if ($exists > 0) {
        $error = "Cette pièce est déjà associée à cette étape !";
    } else {
        $stmt = $pdo->prepare("
            INSERT INTO step_part (step_id, part_id, required_quantity)
            VALUES (?, ?, ?)
        ");
        $stmt->execute([$step_id, $part_id, $required_quantity]);
        header("Location: /configuration/step-parts?step_id=$step_id");
        exit;
    }
}

// MODIFICATION d'une pièce
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_part'])) {
    $step_part_id = $_POST['step_part_id'];
    $required_quantity = $_POST['required_quantity'];
    
    $stmt = $pdo->prepare("
        UPDATE step_part 
        SET required_quantity = ?
        WHERE step_part_id = ?
    ");
    $stmt->execute([$required_quantity, $step_part_id]);
    header("Location: /configuration/step-parts?step_id=$step_id");
    exit;
}

// Récupérer les pièces associées à l'étape
$stmt = $pdo->prepare("
    SELECT sp.*, p.reference, p.part_name, p.category, p.unit
    FROM step_part sp
    JOIN part p ON sp.part_id = p.part_id
    WHERE sp.step_id = ?
    ORDER BY p.part_name
");
$stmt->execute([$step_id]);
$step_parts = $stmt->fetchAll();

// Récupérer toutes les pièces disponibles (pour le formulaire)
$all_parts = $pdo->query("
    SELECT * FROM part 
    ORDER BY category, part_name
")->fetchAll();
?>

<div class="page-header">
    <div>
        <a href="/configuration/steps?workstation_id=<?= $step['workstation_id'] ?>" class="btn btn-secondary">← Retour aux étapes</a>
        <h2>📦 Pièces nécessaires</h2>
        <p class="breadcrumb">
            Poste <?= $step['workstation_number'] ?>: <?= htmlspecialchars($step['workstation_name']) ?> 
            → Étape <?= $step['step_number'] ?>: <?= htmlspecialchars($step['step_name']) ?>
        </p>
    </div>
    <button class="btn btn-primary" onclick="showAddForm()">+ Ajouter une pièce</button>
</div>

<?php if (isset($error)): ?>
<div class="alert-card alert-danger">
    <div class="alert-icon">⚠️</div>
    <div class="alert-content">
        <strong>Erreur</strong>
        <p><?= $error ?></p>
    </div>
</div>
<?php endif; ?>

<!-- Formulaire d'ajout -->
<div id="add-form" class="form-card" style="display: none;">
    <h4>Ajouter une pièce à l'étape</h4>
    <form method="POST">
        <div class="form-row">
            <div class="form-group" style="flex: 2;">
                <label>Pièce *</label>
                <select name="part_id" required>
                    <option value="">-- Sélectionner une pièce --</option>
                    <?php 
                    $current_category = '';
                    foreach ($all_parts as $part): 
                        if ($current_category !== $part['category']) {
                            if ($current_category !== '') echo '</optgroup>';
                            echo '<optgroup label="' . ucfirst($part['category']) . '">';
                            $current_category = $part['category'];
                        }
                    ?>
                        <option value="<?= $part['part_id'] ?>">
                            <?= htmlspecialchars($part['reference']) ?> - <?= htmlspecialchars($part['part_name']) ?>
                        </option>
                    <?php endforeach; ?>
                    <?php if ($current_category !== '') echo '</optgroup>'; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Quantité requise *</label>
                <input type="number" name="required_quantity" required min="1" value="1">
            </div>
        </div>
        <div class="button-group">
            <button type="submit" name="add_part" class="btn btn-primary">Ajouter</button>
            <button type="button" class="btn btn-secondary" onclick="hideAddForm()">Annuler</button>
        </div>
    </form>
</div>

<!-- Liste des pièces -->
<div class="section">
    <h3>Pièces associées (<?= count($step_parts) ?>)</h3>
    
    <?php if (empty($step_parts)): ?>
        <p class="empty-state">Aucune pièce associée à cette étape. Ajoutez-en une !</p>
    <?php else: ?>
        <table class="data-table">
            <thead>
                <tr>
                    <th>Référence</th>
                    <th>Nom de la pièce</th>
                    <th>Catégorie</th>
                    <th>Quantité requise</th>
                    <th>Unité</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($step_parts as $sp): ?>
                <tr>
                    <td><code><?= htmlspecialchars($sp['reference']) ?></code></td>
                    <td><?= htmlspecialchars($sp['part_name']) ?></td>
                    <td>
                        <span class="badge badge-<?= $sp['category'] === 'part' ? 'primary' : ($sp['category'] === 'tool' ? 'success' : 'secondary') ?>">
                            <?= ucfirst($sp['category']) ?>
                        </span>
                    </td>
                    <td><strong><?= $sp['required_quantity'] ?></strong></td>
                    <td><?= htmlspecialchars($sp['unit']) ?></td>
                    <td>
                        <div class="action-buttons">
                            <button class="btn btn-sm btn-secondary" onclick='editPart(<?= json_encode($sp) ?>)'>
                                ✏️ Modifier
                            </button>
                            <button class="btn btn-sm btn-danger" onclick="confirmDelete(<?= $sp['step_part_id'] ?>, '<?= htmlspecialchars($sp['part_name'], ENT_QUOTES) ?>')">
                                🗑️ Retirer
                            </button>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        
        <!-- Résumé -->
        <div class="parts-summary">
            <h4>📋 Résumé des besoins pour cette étape</h4>
            <ul>
                <?php foreach ($step_parts as $sp): ?>
                <li><?= $sp['required_quantity'] ?> × <?= htmlspecialchars($sp['part_name']) ?> (<?= htmlspecialchars($sp['reference']) ?>)</li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>
</div>

<!-- Modal de modification -->
<div id="edit-modal" class="modal" style="display: none;">
    <div class="modal-content">
        <h4>Modifier la quantité requise</h4>
        <form method="POST">
            <input type="hidden" name="step_part_id" id="edit_id">
            <div class="form-group">
                <label>Pièce</label>
                <input type="text" id="edit_part_name" disabled class="input-disabled">
            </div>
            <div class="form-group">
                <label>Quantité requise *</label>
                <input type="number" name="required_quantity" id="edit_quantity" required min="1">
            </div>
            <div class="button-group">
                <button type="submit" name="edit_part" class="btn btn-primary">Enregistrer</button>
                <button type="button" class="btn btn-secondary" onclick="closeEditModal()">Annuler</button>
            </div>
        </form>
    </div>
</div>

<script>
function showAddForm() {
    document.getElementById('add-form').style.display = 'block';
}

function hideAddForm() {
    document.getElementById('add-form').style.display = 'none';
}

function editPart(part) {
    document.getElementById('edit_id').value = part.step_part_id;
    document.getElementById('edit_part_name').value = part.part_name;
    document.getElementById('edit_quantity').value = part.required_quantity;
    document.getElementById('edit-modal').style.display = 'flex';
}

function closeEditModal() {
    document.getElementById('edit-modal').style.display = 'none';
}

function confirmDelete(id, name) {
    if (confirm('Retirer "' + name + '" de cette étape ?')) {
        window.location.href = '/configuration/step-parts?step_id=<?= $step_id ?>&delete_part=' + id;
    }
}

window.onclick = function(event) {
    const modal = document.getElementById('edit-modal');
    if (event.target === modal) modal.style.display = 'none';
}
</script>

<style>
.breadcrumb {
    color: #718096;
    font-size: 0.9rem;
    margin-top: 0.5rem;
}

.action-buttons {
    display: flex;
    gap: 0.5rem;
}

code {
    background: #f7fafc;
    padding: 0.25rem 0.5rem;
    border-radius: 4px;
    font-family: 'Courier New', monospace;
    color: #667eea;
}

.badge-primary {
    background: #bee3f8;
    color: #2c5282;
}

.badge-secondary {
    background: #e2e8f0;
    color: #4a5568;
}

.parts-summary {
    margin-top: 2rem;
    padding: 1.5rem;
    background: #f7fafc;
    border-radius: 8px;
    border-left: 4px solid #667eea;
}

.parts-summary h4 {
    color: #2d3748;
    margin-bottom: 1rem;
}

.parts-summary ul {
    list-style: none;
    padding: 0;
}

.parts-summary li {
    padding: 0.5rem 0;
    color: #4a5568;
    border-bottom: 1px solid #e2e8f0;
}

.parts-summary li:last-child {
    border-bottom: none;
}

.input-disabled {
    background: #f7fafc;
    color: #718096;
    cursor: not-allowed;
}

.alert-danger {
    background: #fed7d7;
    border-left: 4px solid #f56565;
}

.alert-danger .alert-content strong,
.alert-danger .alert-content p {
    color: #742a2a;
}
</style>