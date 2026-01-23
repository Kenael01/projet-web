<?php
    require_once __DIR__ . '/../config/db.php';

    $sql = "SELECT * FROM workstation;";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $positions = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<main>
    <h1>Configuration</h1>
    <h2>Postes</h2>
   
    <ul>
        <?php foreach ($positions as $position): ?>
            <li>
                Numero du poste : <?= htmlspecialchars($position['workstation_number']) ?>,
                nom du poste : <?= htmlspecialchars($position['workstation_name']) ?>,
                description : <?= htmlspecialchars($position['description']) ?>
            </li>
        <?php endforeach; ?>
    </ul>

     <form action='create_workstation' method='POST'>
        <h2>Creer un poste</h2>
        <label for='workstation_number'>Numero du poste :</label>
        <input type='number' id='workstation_number' name='workstation_number' required><br>
        <label for='name_workstation'>Nom du poste :</label>
        <input type='text' id='name_workstation' name='name_workstation' required><br>
        <label for='description'>Description :</label>
        <input type='text' id='description' name='description' required><br>
        <input type='submit' value='Enregistrer'>
      </form>";
</main>