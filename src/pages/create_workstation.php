<?php
ini_set('display_errors', 'On');
error_reporting(E_ALL);

$workstationnumber = $_POST['workstation_number'] ?? '';
$workstationname = $_POST['workstation_name'] ?? '';
$description = $_POST['description'] ?? '';

// Afficher ce qu'on reçoit
echo "Données reçues :<br>";
echo "workstation_number: " . htmlspecialchars($workstationnumber) . "<br>";
echo "workstation_name: " . htmlspecialchars($workstationname) . "<br>";
echo "description: " . htmlspecialchars($description) . "<br><br>";

try {
    $user = 'kenav';
    $pass = 'azerty12345';
    $dbh = new PDO('mysql:host=localhost;dbname=cesi_bike', $user, $pass);
    $dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "Connexion OK<br>";
    
    $sth = $dbh->prepare('INSERT INTO workstation 
        (workstation_number, workstation_name, description, model_id, is_active) 
        VALUES (:number, :name, :desc, 1, 1)');
    
    $result = $sth->execute([
        ':number' => $workstationnumber,
        ':name' => $workstationname,
        ':desc' => $description
    ]);
    
    echo "Insertion OK<br>";
    echo "ID inséré : " . $dbh->lastInsertId();
    
} catch (PDOException $e) {
    echo "ERREUR : " . $e->getMessage() . "<br>";
    echo "Code erreur : " . $e->getCode();
}

// COMMENTEZ la redirection pour voir les messages
//header('Location: configuration.php');
// exit();