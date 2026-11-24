
<?php
session_start();
require __DIR__ . '/../includes/db.php';
include __DIR__ . '/../includes/header.php';
if (!isset($_SESSION['user']) || (($_SESSION['user']['role'] ?? '') !== 'admin')) {
    die('Accès refusé');
}

$stmt = $conn->query("
    SELECT c.*, u.email
    FROM commandes c
    LEFT JOIN utilisateurs u ON u.id = c.utilisateur_id
    ORDER BY DATE(c.date_commande) DESC, c.date_commande DESC

");

$commandes = $stmt->fetchAll(PDO::FETCH_ASSOC);
$stmtNotif = $conn->query("
    SELECT COUNT(*) AS total
    FROM commandes
    WHERE statut = 'en_attente'
");

$nbNouvelles = (int)$stmtNotif->fetch(PDO::FETCH_ASSOC)['total'];

?>
<section class="section">
<?php if ($nbNouvelles > 0): ?>
<div style="
    background:pink;
    color:white;
    padding:15px;
    margin-bottom:20px;
    border-radius:8px;
    font-size:18px;
    font-weight:bold;
">
    🔔 <?= $nbNouvelles ?> nouvelle(s) commande(s) en attente
</div>
<?php endif; ?>
    <h1>📦 Commandes clients</h1>
    <table class="admin-table">
        <thead>
            <tr >
                <th>ID</th>
                <th>Client</th>
                <th>Téléphone</th>
                <th>Total</th>
                <th>Statut</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($commandes as $c): ?>
            <?php
            $rowClasse = 'status-row-unknown';
            $badgeClasse = 'status-badge-unknown';
            $statutTexte = $c['nom_statut'] ?? '';

            if (($c['nom_statut'] ?? '') === 'en_cours') {
                $rowClasse = 'status-row-en-cours';
                $badgeClasse = 'status-badge-en-cours';
                $statutTexte = '🕒 En cours';
            } elseif (($c['nom_statut'] ?? '') === 'livree') {
                $rowClasse = 'status-row-livree';
                $badgeClasse = 'status-badge-livree';
                $statutTexte = '✅ Livrée';
            } elseif (($c['nom_statut'] ?? '') === 'prete') {
                $rowClasse = 'status-row-prete';
                $badgeClasse = 'status-badge-prete';
                $statutTexte = '🟡 Prête';
            }
            ?>
            <tr <?= (($c['nom_statut'] ?? '') === 'en_attente') ? 'style="background:#fff3cd;"' : '' ?>>
                <td>#<?= $c['id'] ?></td>
                <td><?= htmlspecialchars($c['nom']) ?></td>
                <td><?= htmlspecialchars($c['telephone']) ?></td>
                <td><?= number_format($c['total'], 2) ?> DT</td>
                <td class="<?= $rowClasse ?>">
                    <div class="status-cell">
                        <strong>Statut :</strong>
                        <span class="<?= $badgeClasse ?>"><?= htmlspecialchars($statutTexte) ?></span>
                    </div>

                    <form method="post" action="changer_statut.php" style="display:inline">
                        <input type="hidden" name="id_commande" value="<?= $c['id'] ?>">
                        <select name="nom_statut" onchange="this.form.submit()">
                            <option value="en_cours" <?= ($c['nom_statut']=='en_cours')?'selected':'' ?>>En cours</option>
                            <option value="prete" <?= ($c['nom_statut']=='prete')?'selected':'' ?>>Prête</option>
                            <option value="livree" <?= ($c['nom_statut']=='livree')?'selected':'' ?>>Livrée</option>
                        </select>
                    </form>
                </td>

                <td>
                    <a class="btn" href="commande_detail.php?id=<?= $c['id'] ?>">Détails</a>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</section>
<script>
setTimeout(function(){
    location.reload();
}, 30000);
</script>
<?php include __DIR__ . '/../includes/footer.php'; ?>
