<?php
if (!defined('ABSPATH'))  exit;
?>

<div class="inventory_sold_onetime options_group show_if_simple">
    <?php
    woocommerce_wp_checkbox(
        array(
            'id'            => 'castors_sold_onetime',
            'value'         => get_post_meta($post->ID, 'castors_sold_onetime', true) ? 'yes' : 'no',
            'wrapper_class' => 'show_if_simple',
            'label'         => __("Vente unique", 'castors' ),
            'description'   => __("Limiter à 1 achat par client", 'castors' ),
        )
    );
    echo wc_help_tip(__("Cochez cette case pour limiter l'achat de ce produit à un exemplaire par client. Cette option est particulièrement utile pour les articles achetés une seule fois, comme les frais de dossier ou d'inscription.", 'castors'));
    ?>
</div>
<div class="inventory_sold_notalone options_group show_if_simple">
    <?php
    woocommerce_wp_checkbox(
        array(
            'id'            => 'castors_sold_notalone',
            'value'         => get_post_meta($post->ID, 'castors_sold_notalone', true) ? 'yes' : 'no',
            'wrapper_class' => 'show_if_simple',
            'label'         => __("Complément de vente", 'castors' ),
            'description'   => __("Ne peut pas être vendu seul", 'castors' ),
        )
    );
    echo wc_help_tip(__("Cochez cette case pour rendre impossible l'ajout direct de ce produit au panier. Cette option est utile en complément d'une vente forcée avec un autre produit, comme les frais de dossier ou d'inscription.", 'castors'));
    ?>
</div>