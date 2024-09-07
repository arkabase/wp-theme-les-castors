<?php
if (!defined('ABSPATH'))  exit;
?>

<div class="options_group show_if_simple show_if_variable">
    <p class="form-field">
        <label for="castors_forced_ids"><?php esc_html_e("Ventes forcées", 'castors'); ?></label>
        <select class="wc-product-search" multiple="multiple" style="width: 50%;" id="castors_forced_ids" name="castors_forced_ids[]" data-placeholder="<?php esc_attr_e('Search for a product&hellip;', 'woocommerce'); ?>" data-action="woocommerce_json_search_products_and_variations" data-exclude="<?= intval($post->ID); ?>">
            <?php
            $product_ids = get_post_meta($post->ID, 'castors_forced_product');
            foreach ($product_ids as $product_id) {
                $product = wc_get_product($product_id);
                if (is_object($product)) {
                    echo '<option value="' . esc_attr($product_id) . '"' . selected(true, true, false) . '>' . esc_html(wp_strip_all_tags($product->get_formatted_name())) . '</option>';
                }
            }
            ?>
        </select> <?= wc_help_tip(__("Ces produits seront automatiquement ajoutés à la commande en même temps que le produit en cours.", 'castors')); ?>
    </p>
</div>