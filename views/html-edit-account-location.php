<?php
if (!defined('ABSPATH'))  exit;

$user = wp_get_current_user();
$location_details = $user->castors_location_details;
$location = json_decode(htmlspecialchars_decode($location_details));
?>

<div class="clear"></div>
<p class="woocommerce-form-row woocommerce-form-row--first form-row form-row-first castors-location-wrap">
    <label for="location"><?php esc_html_e("Localisation", 'castors'); ?>&nbsp;<span class="required">*</span></label>
    <input type="text" class="woocommerce-Input woocommerce-Input--text input-text" name="location" id="location" value="<?= $location ? $location->value : ''; ?>" />
    <input type="hidden" name="location-details" id="location-details" value="<?= htmlentities($location_details); ?>" />
</p>
<p class="woocommerce-form-row woocommerce-form-row--last form-row form-row-last castors-location-nomap">
    <label class="woocommerce-form__label woocommerce-form__label-for-checkbox checkbox" for="location-nomap">
        <input type="checkbox" class="woocommerce-form__input woocommerce-form__input-checkbox input-checkbox" name="location-nomap" id="location-nomap" value="1" <?php checked($user->castors_location_nomap) ?> />
        <?php esc_html_e("Ne pas apparaître sur la carte des adhérents", 'castors'); ?>
    </label>
</p>
<div class="clear"></div>
<p class="arkabase-field-desc"><em><?php esc_html_e("Entrez le code postal pour sélectionnez votre ville de résidence. Cette information sera utilisée pour afficher votre département et vous localiser sur la carte des adhérents, sauf si vous cochez la case indiquant que vous ne souhaitez pas y apparaître.", 'castors'); ?></em></p>
