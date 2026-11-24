<?php
// Backward-compatibility shim.
// Some pages/clients may still link to the site-root promo_hero_widget.php.
// Redirect to the actual widget inside /includes.
header('Location: /medninestore/includes/promo_hero_widget.php', true, 302);
exit;

