<?php
if (!defined('ABSPATH') || !defined('CASTORS_THEME_VERSION'))  exit;

class Castors_Frontend {
    public static function activate() {
    }

    public static function deactivate() {
    }

    public static function enqueue_scripts() {
    }

    public static function init() {
        add_filter('wp_get_nav_menu_items', [__CLASS__, 'get_menu_items'], 99, 3);
        add_filter('woocommerce_add_to_cart_quantity', [__CLASS__, 'add_to_cart_quantity'], 99, 2);
        add_filter('woocommerce_loop_add_to_cart_link', [__CLASS__, 'add_to_cart_link'], 99, 2);
        add_filter('woocommerce_quantity_input_args', [__CLASS__, 'quantity_input_args'], 99, 2);
        add_filter('astra_woo_single_product_structure', [__CLASS__, 'single_product_structure'], 99);
        add_filter('woocommerce_cart_item_remove_link', [__CLASS__, 'cart_item_remove_link'], 99, 2);
        add_action('woocommerce_remove_cart_item', [__CLASS__, 'remove_cart_item'], 10, 2);
        add_action('woocommerce_restore_cart_item', [__CLASS__, 'restore_cart_item'], 10, 2);
    }

    public static function get_menu_items($items, $menu, $args) {
        foreach ($items as $key => $item) {
            $caps = get_post_meta($item->ID, '_menu_item_capabilities', true);
            if (!$caps) {
                continue;
            }
            foreach (explode(',', $caps) as $cap) {
                if (current_user_can($cap)) {
                    continue 2;
                }
            }
            unset($items[$key]);
        }
        return $items;
    }

    public static function add_to_cart_quantity($quantity, $product_id) {
        global $isForced;
        
        $sold_notalone = get_post_meta($product_id, 'castors_sold_notalone', true);
        if (!empty($sold_notalone) && !$isForced) {
            return 0;
        }
        $sold_onetime = get_post_meta($product_id, 'castors_sold_onetime', true);
        if (!empty($sold_onetime)) {
            if (static::is_in_cart($product_id) || static::user_has_bought($product_id)) {
                return 0;
            }
            $quantity = 1;
        }
        $forced_ids = get_post_meta($product_id, 'castors_forced_product');
        foreach ($forced_ids as $forced_id) {
            $isForced = true;
            WC()->cart->add_to_cart($forced_id, $quantity, 0, [], ['_forced_by' => intval($product_id)]);
        }
        $isForced = false;
        return $quantity;
    }

    public static function add_to_cart_link($link, $product) {
        $sold_notalone = get_post_meta($product->id, 'castors_sold_notalone', true);
        if (!empty($sold_notalone)) {
            return '<p>' . __("Ce produit est vendu en complément d'un autre produit et ne peut pas être acheté seul.", 'castors') . '</p>';
        }
        return $link;
    }

    public static function quantity_input_args($args, $product) {
        $sold_onetime = get_post_meta($product->id, 'castors_sold_onetime', true);
        if (!empty($sold_onetime)) {
            $args['input_value'] = 1;
            $args['max_value'] = 1;
		    $args['min_value'] = 1;
		    $args['step'] = 1;
        }
        return $args;
    }

    public static function single_product_structure($options) {
        global $product;

        $sold_notalone = get_post_meta($product->id, 'castors_sold_notalone', true);
        if (!empty($sold_notalone)) {
            remove_action('woocommerce_simple_add_to_cart', 'woocommerce_simple_add_to_cart', 30);
            add_action('woocommerce_simple_add_to_cart', [__CLASS__, 'sold_notalone_notice'], 99);
        }
        return array_filter($options, fn($item) => $item !== 'meta');
    }

    public static function sold_notalone_notice() {
        echo '<p>' . __("Ce produit est vendu en complément d'un autre produit et ne peut pas être acheté seul.", 'castors') . '</p>';
    }

    public static function is_in_cart($product_id) {
        $cart = WC()->cart->get_cart_contents();
        foreach ($cart as $item) {
            if ($item['product_id'] === intval($product_id)) {
                return $item['quantity'];
            }
        }
        return 0;
    }

    public static function user_has_bought($product_id) {
        global $wpdb;

        $user_id = get_current_user_id();
        $paid_statuses = array_map('esc_sql', wc_get_is_paid_statuses());
        $count = $wpdb->get_var("
            SELECT COUNT(wo.id) FROM {$wpdb->prefix}wc_orders AS wo
            INNER JOIN {$wpdb->prefix}woocommerce_order_items AS woi ON wo.id = woi.order_id
            INNER JOIN {$wpdb->prefix}woocommerce_order_itemmeta AS woim ON woi.order_item_id = woim.order_item_id
            WHERE wo.status IN ('wc-" . implode("','wc-", $paid_statuses) . "')
            AND wo.customer_id = {$user_id}
            AND woim.meta_key = '_product_id' AND woim.meta_value = {$product_id}
        ");
        return $count > 0;
    }

    public static function cart_item_remove_link($link, $item_key) {
        $items = WC()->cart->get_cart_contents();
        if ($items[$item_key]['_forced_by']) {
            return '';
        }
        return $link;
    }

    public static function remove_cart_item($item_key, $cart) {
        $items = $cart->get_cart_contents();
        foreach ($items as $item) {
            if ($item['_forced_by'] === $items[$item_key]['product_id']) {
                $cart->remove_cart_item($item['key']);
            }
        }
    }

    public static function restore_cart_item($item_key, $cart) {
        $items = $cart->get_removed_cart_contents();
        foreach ($items as $item) {
            if ($item['_forced_by'] === $items[$item_key]['product_id']) {
                $cart->restore_cart_item($item['key']);
            }
        }
    }
}
