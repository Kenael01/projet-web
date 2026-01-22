<?php
require_once __DIR__ . '/../../config/db.php';

$workstation_id = $_GET['workstation_id'] ?? null;

if (!$workstation_id) {
    header('Location: /configuration');
    exit;
}

// Récupérer les infos du poste
$stmt = $pdo->prepare("SELECT * FROM workstation WHERE workstation_id = ?");
$stmt->execute([$workstation_id]);
$workstation = $stmt->fetch();

if (!$workstation) {
    header('Location: /configuration');
    exit;
}

// SUPPRESSION d'étape
if (isset($_GET['delete_step'])) {
    $step_id = $_GET['delete_step'];
    $stmt = $pdo->prepare("DELETE FROM step WHERE step_id = ?");
    $stmt->execute([$step_id]);
    header("Location: /configuration/steps?workstation_id=$workstation_id");
    exit;
}

// AJOUT d'étape
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_step'])) {
    $step_number = $_POST['step_number'];
    $step_name = $_POST['step_name'];
    $description = $_POST['description'];
    $standard_time = $_POST['standard_time'];
    $execution_order = $_POST['execution_order'];
    
    $stmt = $pdo->prepare("
        INSERT INTO step (workstation_id, step_number, step_name, description, standard_time, execution_order)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([$workstation_id, $step_number, $step_name, $description, $standard_time, $execution_order]);
    header("Location: /configuration/steps?workstation_id=$workstation_id");
    exit;
}

// MODIFICATION d'étape
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_step'])) {
    $step_id = $_POST['step_id'];
    $step_number = $_POST['step_number'];
    $step_name = $_POST['step_name'];
    $description = $_POST['description'];
    $standard_time = $_POST['standard_time'];
    $execution_order = $_POST['execution_order'];
    
    $stmt = $pdo->prepare("
        UPDATE step 
        SET step_number = ?, step_name = ?, description = ?, standard_time = ?, execution_order = ?
        WHERE step_id = ?
    ");
    $stmt->execute([$step_number, $step_name, $description, $standard_time, $execution_order, $step_id]);
    header("Location: /configuration/steps?workstation_id=$workstation_id");
    exit;
}

// Récupérer toutes les étapes du poste
$stmt = $pdo->prepare("
    SELECT s.*, COUNT(sp.part_id) as parts_count
    FROM step s
    LEFT JOIN step_part sp ON s.step_id = sp.step_id
    WHERE s.workstation_id = ?
    GROUP BY s.step_id
    ORDER BY s.execution_order, s.step_number
");
$stmt->execute([$workstation_id]);
$steps = $stmt->fetchAll();
?>

<div class="page-header">
    <div>
        <a href="/configuration" class="btn btn-secondary">← Retour</a>
        <h2>📋 Étapes - Poste <?= $workstation['workstation_number'] ?>: <?= htmlspecialchars($workstation['workstation_name']) ?></h2>
    </div>
    <button class="btn btn-primary" onclick="showAddForm()">+ Ajouter une étape</button>
</div>

<!-- Formulaire d'ajout -->
<div id="add-form" class="form-card" style="display: none;">
    <h4>Nouvelle étape</h4>
    <form method="POST">
        <div class="form-row">
            <div class="form-group">
                <label>Numéro d'étape *</label>
                <input type="number" name="step_number" required min="1">
            </div>
            <div class="form-group">
                <label>Nom de l'étape *</label>
                <input type="text" name="step_name" required>
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label>Temps standard (secondes)</label>
                <input type="number" name="standard_time" min="0">
            </div>
            <div class="form-group">
                <label>Ordre d'exécution *</label>
                <input type="number" name="execution_order" required min="1">
            </div>
        </div>
        <div class="form-group">
            <label>Description</label>
            <textarea name="description" rows="2"></textarea>
        </div>
        <div class="button-group">
            <button type="submit" name="add_step" class="btn btn-primary">Enregistrer</button>
            <button type="button" class="btn btn-secondary" onclick="hideAddForm()">Annuler</button>
        </div>
    </form>
</div>

<!-- Liste des étapes -->
<div class="steps-list">
    <?php if (empty($steps)): ?>
        <p class="empty-state">Aucune étape configurée pour ce poste. Ajoutez-en une !</p>
    <?php else: ?>
        <?php foreach ($steps as $step): ?>
        <div class="step-card">
            <div class="step-header">
                <div>
                    <span class="step-badge">Étape <?= $step['step_number'] ?></span>
                    <h4><?= htmlspecialchars($step['step_name']) ?></h4>
                </div>
                <div class="step-actions">
                    <button class="btn btn-sm btn-secondary" onclick='editStep(<?= json_encode($step) ?>)'>
                        ✏️ Modifier
                    </button>
                    <a href="/configuration/step-parts?step_id=<?= $step['step_id'] ?>" class="btn btn-sm btn-primary">
                        📦 Pièces (<?= $step['parts_count'] ?>)
                    </a>
                    <button class="btn btn-sm btn-danger" onclick="confirmDelete(<?= $step['step_id'] ?>, '<?= htmlspecialchars($step['step_name'], ENT_QUOTES) ?>')">
                        🗑️
                    </button>
                </div>
            </div>
            <div class="step-body">
                <p><?= htmlspecialchars($step['description']) ?></p>
                <div class="step-info">
                    <span>⏱️ Temps standard: <?= $step['standard_time'] ?? 'N/A' ?>s</span>
                    <span>📊 Ordre: <?= $step['execution_order'] ?></span>
                    <span>📦 <?= $step['parts_count'] ?> pièce(s) nécessaire(s)</span>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<!-- Modal de modification -->
<div id="edit-modal" class="modal" style="display: none;">
    <div class="modal-content">
        <h4>Modifier l'étape</h4>
        <form method="POST">
            <input type="hidden" name="step_id" id="edit_id">
            <div class="form-row">
                <div class="form-group">
                    <label>Numéro d'étape *</label>
                    <input type="number" name="step_number" id="edit_number" required>
                </div>
                <div class="form-group">
                    <label>Nom de l'étape *</label>
                    <input type="text" name="step_name" id="edit_name" required>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Temps standard (secondes)</label>
                    <input type="number" name="standard_time" id="edit_time" min="0">
                </div>
                <div class="form-group">
                    <label>Ordre d'exécution *</label>
                    <input type="number" name="execution_order" id="edit_order" required>
                </div>
            </div>
            <div class="form-group">
                <label>Description</label>
                <textarea name="description" id="edit_description" rows="2"></textarea>
            </div>
            <div class="button-group">
                <button type="submit" name="edit_step" class="btn btn-primary">Enregistrer</button>
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

function editStep(step) {
    document.getElementById('edit_id').value = step.step_id;
    document.getElementById('edit_number').value = step.step_number;
    document.getElementById('edit_name').value = step.step_name;
    document.getElementById('edit_description').value = step.description || '';
    document.getElementById('edit_time').value = step.standard_time || '';
    document.getElementById('edit_order').value = step.execution_order;
    document.getElementById('edit-modal').style.display = 'flex';
}

function closeEditModal() {
    document.getElementById('edit-modal').style.display = 'none';
}

function confirmDelete(id, name) {
    if (confirm('Supprimer l\'étape "' + name + '" ?\n\nCela supprimera aussi les pièces associées.')) {
        window.location.href = '/configuration/steps?workstation_id=<?= $workstation_id ?>&delete_step=' + id;
    }
}

window.onclick = function(event) {
    const modal = document.getElementById('edit-modal');
    if (event.target === modal) modal.style.display = 'none';
}
</script>