<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Récupérer l'URI et nettoyer
$uri = trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');
$page = $uri === '' ? 'home' : $uri;

// Sécurité : nettoyer l'URI
$page = preg_replace('#[^a-zA-Z0-9\-_/]#', '', $page);

// Déterminer le chemin du fichier
$pagePath = __DIR__ . "/../src/pages/{$page}.php";

// Charger le template de base
require __DIR__ . '/../src/components/base.php';

// Charger la page demandée ou 404
if (file_exists($pagePath)) {
    require $pagePath;
} else {
    http_response_code(404);
    echo '<div class="section" style="text-align: center; padding: 4rem;">';
    echo '<h2>404 - Page non trouvée</h2>';
    echo '<p>La page que vous cherchez n\'existe pas.</p>';
    echo '<a href="/" class="btn btn-primary">Retour à l\'accueil</a>';
    echo '</div>';
}

// Fermer le template
require __DIR__ . '/../src/components/end_base.php';
?>