<?php
// admin/dashboard.php : tableau de bord de l'administration
session_start();

// Sécurité : admin uniquement (même règle que admin/index.php)
if (!isset($_SESSION['user']) || ($_SESSION['user']['role'] ?? '') !== 'admin') {
    header('Location: ../login.php');
    exit();
}

require '../includes/db.php';

// Petites fonctions : une table absente ou en erreur ne casse pas toute la page
function valeur(PDO $conn, string $sql, $defaut = 0) {
    try {
        $v = $conn->query($sql)->fetchColumn();
        return $v === false || $v === null ? $defaut : $v;
    } catch (Throwable $e) {
        return $defaut;
    }
}
function lignes(PDO $conn, string $sql): array {
    try {
        return $conn->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    } catch (Throwable $e) {
        return [];
    }
}

// --- Chiffres clés
$nbProduits   = (int)valeur($conn, "SELECT COUNT(*) FROM produits");
$nbMarques    = (int)valeur($conn, "SELECT COUNT(*) FROM marques");
$nbClients    = (int)valeur($conn, "SELECT COUNT(*) FROM utilisateurs WHERE role <> 'admin'");
$nbCommandes  = (int)valeur($conn, "SELECT COUNT(*) FROM commandes");
$nbAttente    = (int)valeur($conn, "SELECT COUNT(*) FROM commandes WHERE nom_statut = 'en_attente'");
$caTotal      = (float)valeur($conn, "SELECT SUM(total) FROM commandes");
$caLivre      = (float)valeur($conn, "SELECT SUM(total) FROM commandes WHERE nom_statut = 'livree'");
$nbSansImage  = (int)valeur($conn, "SELECT COUNT(*) FROM produits WHERE image IS NULL OR image = ''");
$nbPromos     = (int)valeur($conn, "SELECT COUNT(*) FROM promotions WHERE actif = 1 AND (date_debut IS NULL OR date_debut <= CURDATE()) AND (date_fin IS NULL OR date_fin >= CURDATE())");

// --- Commandes par statut
$libelles = [
    'en_attente' => 'En attente',
    'en_cours'   => 'En cours',
    'prete'      => 'Prête',
    'livree'     => 'Livrée',
];
$parStatut = [];
foreach (lignes($conn, "SELECT nom_statut, COUNT(*) AS n FROM commandes GROUP BY nom_statut") as $r) {
    $parStatut[$r['nom_statut'] ?? ''] = (int)$r['n'];
}

// --- 7 derniers jours (jours sans commande = 0)
$jours = [];
for ($i = 6; $i >= 0; $i--) {
    $jours[date('Y-m-d', strtotime("-$i day"))] = ['n' => 0, 't' => 0.0];
}
foreach (lignes($conn, "SELECT DATE(date_commande) AS j, COUNT(*) AS n, SUM(total) AS t
                        FROM commandes
                        WHERE date_commande >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
                        GROUP BY DATE(date_commande)") as $r) {
    if (isset($jours[$r['j']])) {
        $jours[$r['j']] = ['n' => (int)$r['n'], 't' => (float)$r['t']];
    }
}
$maxJour = max(1, max(array_column($jours, 'n')));

// --- Produits les plus vendus
$top = lignes($conn, "SELECT p.id, p.nom, SUM(cp.quantite) AS q
                      FROM commande_produits cp
                      JOIN produits p ON p.id = cp.produit_id
                      GROUP BY p.id, p.nom
                      ORDER BY q DESC
                      LIMIT 5");

// --- Dernières commandes
$dernieres = lignes($conn, "SELECT * FROM commandes ORDER BY date_commande DESC LIMIT 8");

$e = fn($s) => htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Tableau de bord | Mednine Store</title>
    <link rel="stylesheet" href="../style.css">
    <style>
        .dash { max-width: 1100px; margin: 0 auto; padding: 16px; }
        .dash-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(190px, 1fr)); gap: 14px; margin-bottom: 22px; }
        .dash-card { background: #fff; border: 1px solid #eee; border-radius: 12px; padding: 16px; box-shadow: 0 2px 8px rgba(0,0,0,.04); }
        .dash-card .label { font-size: .85rem; color: #777; }
        .dash-card .valeur { font-size: 1.7rem; font-weight: 700; margin-top: 4px; }
        .dash-card.alerte { border-color: #f0c36d; background: #fff8e6; }
        .dash-2col { display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 16px; margin-bottom: 22px; }
        .dash h3 { margin: 0 0 12px; }
        .dash table { width: 100%; border-collapse: collapse; }
        .dash th, .dash td { text-align: left; padding: 8px 6px; border-bottom: 1px solid #f0f0f0; font-size: .92rem; }
        .barres { display: flex; align-items: flex-end; gap: 10px; height: 150px; }
        .barre { flex: 1; text-align: center; font-size: .75rem; color: #666; }
        .barre span { display: block; background: #d6336c; border-radius: 6px 6px 0 0; min-height: 3px; }
        .dash-liens a { display: inline-block; margin: 0 8px 8px 0; padding: 8px 14px; border-radius: 8px; background: #fbe9ef; text-decoration: none; color: #333; }
    </style>
</head>
<body>

<?php include '../includes/header.php'; ?>

<main class="dash">
    <h2>📊 Tableau de bord</h2>

    <div class="dash-liens">
        <a href="index.php">📦 Produits</a>
        <a href="marques.php">+🏷️ Marques</a>
        <a href="commandes.php">🧾 Commandes</a>
        <a href="promotions.php">+🎁 Promotions</a>
        <a href="ajouter_produit.php">➕ Ajouter un produit</a>
    </div>

    <div class="dash-grid">
        <div class="dash-card <?= $nbAttente > 0 ? 'alerte' : '' ?>">
            <div class="label">Commandes en attente</div>
            <div class="valeur"><?= $nbAttente ?></div>
        </div>
        <div class="dash-card"><div class="label">Commandes (total)</div><div class="valeur"><?= $nbCommandes ?></div></div>
        <div class="dash-card"><div class="label">Chiffre d'affaires (toutes commandes)</div><div class="valeur"><?= number_format($caTotal, 2) ?> DT</div></div>
        <div class="dash-card"><div class="label">Encaissé (commandes livrées)</div><div class="valeur"><?= number_format($caLivre, 2) ?> DT</div></div>
        <div class="dash-card"><div class="label">Produits</div><div class="valeur"><?= $nbProduits ?></div></div>
        <div class="dash-card"><div class="label">Marques</div><div class="valeur"><?= $nbMarques ?></div></div>
        <div class="dash-card"><div class="label">Clients</div><div class="valeur"><?= $nbClients ?></div></div>
        <div class="dash-card"><div class="label">Promotions actives</div><div class="valeur"><?= $nbPromos ?></div></div>
        <div class="dash-card <?= $nbSansImage > 0 ? 'alerte' : '' ?>">
            <div class="label">Produits sans image</div>
            <div class="valeur"><?= $nbSansImage ?></div>
        </div>
    </div>

    <div class="dash-2col">
        <div class="dash-card">
            <h3>Commandes des 7 derniers jours</h3>
            <div class="barres">
                <?php foreach ($jours as $date => $j): ?>
                    <div class="barre" title="<?= $e($date) ?> : <?= $j['n'] ?> commande(s), <?= number_format($j['t'], 2) ?> DT">
                        <?= $j['n'] ?>
                        <span style="height: <?= (int)round(($j['n'] / $maxJour) * 110) ?>px"></span>
                        <?= $e(date('d/m', strtotime($date))) ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="dash-card">
            <h3>Commandes par statut</h3>
            <table>
                <?php foreach ($libelles as $cle => $texte): ?>
                    <tr><td><?= $e($texte) ?></td><td><strong><?= $parStatut[$cle] ?? 0 ?></strong></td></tr>
                <?php endforeach; ?>
            </table>
        </div>
    </div>

    <div class="dash-2col">
        <div class="dash-card">
            <h3>Produits les plus vendus</h3>
            <?php if (!$top): ?>
                <p>Aucune vente enregistrée.</p>
            <?php else: ?>
                <table>
                    <?php foreach ($top as $t): ?>
                        <tr><td><?= $e($t['nom']) ?></td><td><strong><?= (int)$t['q'] ?></strong> vendu(s)</td></tr>
                    <?php endforeach; ?>
                </table>
            <?php endif; ?>
        </div>

        <div class="dash-card">
            <h3>Dernières commandes</h3>
            <?php if (!$dernieres): ?>
                <p>Aucune commande.</p>
            <?php else: ?>
                <table>
                    <tr><th>N°</th><th>Client</th><th>Total</th><th>Statut</th></tr>
                    <?php foreach ($dernieres as $c): ?>
                        <tr>
                            <td><a href="commande_detail.php?id=<?= (int)$c['id'] ?>">#<?= (int)$c['id'] ?></a></td>
                            <td><?= $e($c['nom'] ?? '') ?></td>
                            <td><?= number_format((float)($c['total'] ?? 0), 2) ?> DT</td>
                            <td><?= $e($libelles[$c['nom_statut'] ?? ''] ?? ($c['nom_statut'] ?? '')) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </table>
            <?php endif; ?>
        </div>
    </div>
</main>

<?php include '../includes/footer.php'; ?>
</body>
</html>
