<?php
ini_set('display_errors', 'On');
$workstationnumber = $_POST['workstation_number'] ?? '';
$workstationname = $_POST['workstation_name'] ?? '';
$description = $_POST['description'] ?? '';


$user = 'kenav';
$pass = 'azerty12345';
$dbh = new PDO('mysql:host=localhost;dbname=rpg_game', $user, $pass);
// use the connection here
$sth = $dbh->query('INSERT INTO workstation (workstation_number, workstation_name, description) VALUES ("' . 
    $workstationnumber . '", ' . 
    $workstationname . ', ' . 
    $description . ', ' . ');');
// and now we're done; close it
$sth = null;
$dbh = null;
header('Location: /index.php');; 
exit();