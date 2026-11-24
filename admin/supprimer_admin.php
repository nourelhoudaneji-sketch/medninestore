
<?php
require_once 'protection.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Méthode non autorisée.');
}

if (!hash_equals($_SESSION['csrf_token'],
                 $_POST['csrf_token'] ?? '')) {
    die('Requête invalide.');
}

$id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);

if (!$id) {
    die('Identifiant invalide.');
}

if ($id == $_SESSION['user']['id']) {
    die('Vous ne pouvez pas supprimer votre propre compte.');
}

$stmt = $conn->prepare(
    "DELETE FROM utilisateurs
     WHERE id = ? AND role = 'admin'"
);

$stmt->execute([$id]);

header('Location: liste_admin.php');
exit;
