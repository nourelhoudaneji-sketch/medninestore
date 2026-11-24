<?php
session_start();

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    die('Accès refusé');
}

require __DIR__ . '/../includes/db.php';
include __DIR__ . '/../includes/header.php';

$marques = $conn->query(
    "SELECT m.id, m.nom, m.categorie_id, c.nom AS categorie, m.logo, m.actif
     FROM marques m
     LEFT JOIN categories c ON c.id = m.categorie_id
     ORDER BY c.nom, m.nom"
)->fetchAll(PDO::FETCH_ASSOC);

?>

<section class="page-header">
    <h1>Diagnostic <span>Marques</span></h1>
    <p>Vérifie si plusieurs marques ont le même champ <code>marques.logo</code>.</p>
</section>

<section class="section">
    <table cellpadding="10" border="1" style="width:100%; border-collapse:collapse;">
        <thead>
            <tr>
                <th>ID</th>
                <th>Nom marque</th>
                <th>Catégorie</th>
                <th>Actif</th>
                <th>logo (nom fichier)</th>
                <th>Image</th>
            </tr>
        </thead>
        <tbody>
        <?php if (empty($marques)): ?>
            <tr><td colspan="6">Aucune marque</td></tr>
        <?php else: ?>
            <?php foreach ($marques as $m): ?>
                <?php $logo = $m['logo'] ?? ''; ?>
                <tr>
                    <td><?= (int)$m['id'] ?></td>
                    <td><?= htmlspecialchars((string)$m['nom']) ?></td>
                    <td><?= htmlspecialchars((string)($m['categorie'] ?? '')) ?></td>
                    <td><?= (int)$m['actif'] ?></td>
                    <td><?= htmlspecialchars((string)$logo) ?></td>
                    <td>
                        <?php if (!empty($logo)): ?>
                            <img src="../img/marques/<?= htmlspecialchars((string)$logo) ?>" alt="" style="width:50px;height:50px;object-fit:contain;" />
                        <?php else: ?>
                            —
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
        </tbody>
    </table>

    <h3 style="margin-top:18px;">Groupement par logo</h3>
    <?php
        $rows = $conn->query(
            "SELECT logo, COUNT(*) as nb
             FROM marques
             WHERE logo IS NOT NULL AND logo <> ''
             GROUP BY logo
             ORDER BY nb DESC"
        )->fetchAll(PDO::FETCH_ASSOC);
    ?>

    <table cellpadding="10" border="1" style="width:100%; border-collapse:collapse; margin-top:10px;">
        <thead>
            <tr>
                <th>logo (nom fichier)</th>
                <th>Nombre de marques utilisant ce logo</th>
            </tr>
        </thead>
        <tbody>
        <?php if (empty($rows)): ?>
            <tr><td colspan="2">—</td></tr>
        <?php else: ?>
            <?php foreach ($rows as $r): ?>
                <tr>
                    <td><?= htmlspecialchars((string)$r['logo']) ?></td>
                    <td><?= (int)$r['nb'] ?></td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
        </tbody>
    </table>
</section>

<?php include __DIR__ . '/../includes/footer.php'; ?>

