<?php
require_once __DIR__ . '/../config/db.php';

// Traitement SUPPRESSION de poste
if (isset($_GET['delete_workstation'])) {
    $id = $_GET['delete_workstation'];
    $stmt = $pdo->prepare("DELETE FROM workstation WHERE workstation_id = ?");
    $stmt->execute([$id]);
    header('Location: /configuration');
    exit;
}

// Traitement AJOUT de poste
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_workstation'])) {
    $workstation_number = $_POST['workstation_number'];
    $workstation_name = $_POST['workstation_name'];
    $description = $_POST['description'];
    
    $stmt = $pdo->prepare("
        INSERT INTO workstation (workstation_number, workstation_name, description, model_id, is_active)
        VALUES (?, ?, ?, 1, 1)
    ");
    $stmt->execute([$workstation_number, $workstation_name, $description]);
    header('Location: /configuration');
    exit;
}

// Traitement MODIFICATION de poste
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_workstation'])) {
    $id = $_POST['workstation_id'];
    $workstation_number = $_POST['workstation_number'];
    $workstation_name = $_POST['workstation_name'];
    $description = $_POST['description'];
    
    $stmt = $pdo->prepare("
        UPDATE workstation 
        SET workstation_number = ?, workstation_name = ?, description = ?
        WHERE workstation_id = ?
    ");
    $stmt->execute([$workstation_number, $workstation_name, $description, $id]);
    header('Location: /configuration');
    exit;
}

// Récupérer tous les postes
$workstations = $pdo->query("
    SELECT w.*, 
           COUNT(DISTINCT s.step_id) as total_steps,
           COUNT(DISTINCT ws.part_id) as total_parts_stock
    FROM workstation w
    LEFT JOIN step s ON w.workstation_id = s.workstation_id
    LEFT JOIN workstation_stock ws ON w.workstation_id = ws.workstation_id
    GROUP BY w.workstation_id
    ORDER BY w.workstation_number
")->fetchAll();
?>

<div class="page-header">
    <h2>⚙️ Configuration des postes de travail</h2>
    <p>Gérez les 6 postes de la ligne de production</p>
</div>

<div class="config-section">
    <div class="section-header">
        <h3>Liste des postes</h3>
        <button class="btn btn-primary" onclick="showAddForm()">+ Ajouter un poste</button>
    </div>

    <!-- Formulaire d'ajout (caché par défaut) -->
    <div id="add-form" class="form-card" style="display: none;">
        <h4>Nouveau poste de travail</h4>
        <form method="POST">
            <div class="form-row">
                <div class="form-group">
                    <label>Numéro du poste *</label>
                    <input type="number" name="workstation_number" required min="1">
                </div>
                <div class="form-group">
                    <label>Nom du poste *</label>
                    <input type="text" name="workstation_name" required>
                </div>
            </div>
            <div class="form-group">
                <label>Description</label>
                <textarea name="description" rows="3"></textarea>
            </div>
            <div class="button-group">
                <button type="submit" name="add_workstation" class="btn btn-primary">Enregistrer</button>
                <button type="button" class="btn btn-secondary" onclick="hideAddForm()">Annuler</button>
            </div>
        </form>
    </div>

    <!-- Liste des postes -->
    <div class="workstations-grid">
        <?php foreach ($workstations as $ws): ?>
        <div class="workstation-card">
            <div class="card-header">
                <h4>Poste <?= $ws['workstation_number'] ?></h4>
                <span class="badge <?= $ws['is_active'] ? 'badge-success' : 'badge-danger' ?>">
                    <?= $ws['is_active'] ? 'Actif' : 'Inactif' ?>
                </span>
            </div>
            <div class="card-body">
                <h5><?= htmlspecialchars($ws['workstation_name']) ?></h5>
                <p class="description"><?= htmlspecialchars($ws['description']) ?></p>
                <div class="stats-mini">
                    <span>📋 <?= $ws['total_steps'] ?> étapes</span>
                    <span>📦 <?= $ws['total_parts_stock'] ?> pièces en stock</span>
                </div>
            </div>
            <div class="card-footer">
                <button class="btn btn-sm btn-secondary" onclick="editWorkstation(<?= $ws['workstation_id'] ?>, '<?= $ws['workstation_number'] ?>', '<?= htmlspecialchars($ws['workstation_name'], ENT_QUOTES) ?>', '<?= htmlspecialchars($ws['description'], ENT_QUOTES) ?>')">
                    ✏️ Modifier
                </button>
                <a href="/configuration/steps?workstation_id=<?= $ws['workstation_id'] ?>" class="btn btn-sm btn-primary">
                    📋 Gérer les étapes
                </a>
                <button class="btn btn-sm btn-danger" onclick="confirmDelete(<?= $ws['workstation_id'] ?>, '<?= htmlspecialchars($ws['workstation_name'], ENT_QUOTES) ?>')">
                    🗑️ Supprimer
                </button>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- Modal de modification (caché par défaut) -->
<div id="edit-modal" class="modal" style="display: none;">
    <div class="modal-content">
        <h4>Modifier le poste</h4>
        <form method="POST">
            <input type="hidden" name="workstation_id" id="edit_id">
            <div class="form-row">
                <div class="form-group">
                    <label>Numéro du poste *</label>
                    <input type="number" name="workstation_number" id="edit_number" required>
                </div>
                <div class="form-group">
                    <label>Nom du poste *</label>
                    <input type="text" name="workstation_name" id="edit_name" required>
                </div>
            </div>
            <div class="form-group">
                <label>Description</label>
                <textarea name="description" id="edit_description" rows="3"></textarea>
            </div>
            <div class="button-group">
                <button type="submit" name="edit_workstation" class="btn btn-primary">Enregistrer</button>
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

function editWorkstation(id, number, name, description) {
    document.getElementById('edit_id').value = id;
    document.getElementById('edit_number').value = number;
    document.getElementById('edit_name').value = name;
    document.getElementById('edit_description').value = description;
    document.getElementById('edit-modal').style.display = 'flex';
}

function closeEditModal() {
    document.getElementById('edit-modal').style.display = 'none';
}

function confirmDelete(id, name) {
    if (confirm('Êtes-vous sûr de vouloir supprimer le poste "' + name + '" ?\n\nCette action supprimera également toutes les étapes et données associées.')) {
        window.location.href = '/configuration?delete_workstation=' + id;
    }
}

// Fermer le modal en cliquant en dehors
window.onclick = function(event) {
    const modal = document.getElementById('edit-modal');
    if (event.target === modal) {
        modal.style.display = 'none';
    }
}
</script>