<?php
if (!defined('ABSPATH'))  exit;
?>

<div class="form-wrap castors-worksite">
    <div class="form-field form-field-small">
        <label for="castors-worksite-user-min"><?= esc_html__("Utilisateur(s) min.", 'arkabase-booking') ?></label>
        <input type="number" name="user-min" id="castors-worksite-user-min" class="small-text" title="<?= esc_attr__("Ce champ doit contenir un nombre entier positif", 'arkabase-booking') ?>" value="<?= $abbooking_resource_metadata['user-min'] ?>" min="1" step="1" max="9999" required />
    </div>
    <div class="form-field form-field-small">
        <label for="castors-worksite-user-max"><?= esc_html__("Utilisateur(s) max.", 'arkabase-booking') ?></label>
        <input type="number" name="user-max" id="castors-worksite-user-max" class="small-text" title="<?= esc_attr__("Ce champ doit contenir un nombre entier positif", 'arkabase-booking') ?>" value="<?= $abbooking_resource_metadata['user-max'] ?>" min="<?= $abbooking_resource_metadata['user-min'] ?>" step="1" max="9999" required />
    </div>
    <div class="clear"></div>
    <div class="form-field form-field-small">
        <label for="castors-worksite-quantity"><?= esc_html__("Quantité disponible", 'arkabase-booking') ?></label>
        <input type="number" name="quantity" id="castors-worksite-quantity" class="small-text" title="<?= esc_attr__("Ce champ doit contenir un nombre entier positif", 'arkabase-booking') ?>" value="<?= $abbooking_resource_metadata['quantity'] ?>" min="1" step="1" max="9999" required />
    </div>
    <div class="clear"></div>
    <div class="form-field form-field-small">
        <label for="castors-worksite-unit"><?= esc_html__("Unité réservable (minutes)", 'arkabase-booking') ?></label>
        <input type="number" name="unit" id="castors-worksite-unit" class="small-text" title="<?= esc_attr__("Ce champ doit contenir un nombre entier positif", 'arkabase-booking') ?>" value="<?= $abbooking_resource_metadata['unit'] ?>" min="5" step="5" max="9999" required />
    </div>
    <div class="clear"></div>
    <p><?= esc_html__("La durée d'une réservation sera toujours un multiple de l'unité réservable.", 'arkabase-booking') ?></p>
</div>