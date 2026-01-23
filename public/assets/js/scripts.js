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
window.onclick = function (event) {
    const modal = document.getElementById('edit-modal');
    if (event.target === modal) {
        modal.style.display = 'none';
    }
}