<?php
if (!defined('ABSPATH') || !defined('CASTORS_THEME_VERSION'))  exit;

class Castors_Backend {
    public static function activate() {
    }

    public static function deactivate() {
    }

    public static function enqueue_scripts() {
    }

    public static function init() {
        add_filter('groups_admin_groups_edit_form_after_fields', [__CLASS__, 'groups_form_after_fields'], 5, 2);
        add_filter('groups_admin_groups_add_form_after_fields', [__CLASS__, 'groups_form_after_fields'], 5);
        add_action('groups_admin_groups_add_submit_success', [__CLASS__, 'group_saved'], 99);
        add_action('groups_admin_groups_edit_submit_success', [__CLASS__, 'group_saved'], 99);

        add_filter('woocommerce_product_data_tabs', [__CLASS__, 'product_data_tabs'], 99);
        add_filter('groups_access_meta_boxes_groups_after_read_groups', [__CLASS__, 'after_read_groups'], 99, 2);
        add_filter('groups_access_meta_boxes_after_groups_read_update', [__CLASS__, 'after_groups_read_update'], 99);
        add_action('woocommerce_product_options_related', [__CLASS__, 'product_options_related']);
        add_action('woocommerce_product_options_inventory_product_data', [__CLASS__, 'product_options_inventory']);
        add_action('woocommerce_process_product_meta', [__CLASS__, 'process_product_meta']);
    }

    public static function groups_form_after_fields($output, $group_id = 0) {
        $next_id = isset($_POST['next-id-field']) ? $_POST['next-id-field'] : get_option('group_' . $group_id . '_next_id');
        
        $next_select = '<select name="next-id-field">';
        $next_select .= sprintf('<option value="" %s>--</option>', empty($next_id) ? 'selected="selected"' : '');
        $tree = Groups_Utility::get_group_tree();
        Groups_Utility::render_group_tree_options($tree, $next_select, 0, [$next_id]);
        $next_select .= '</select>';

        $output .= '<br />';
        $output .= '<div class="field">';
        $output .= '<label for="next-id-field" class="field-label">';
        $output .= __("Groupe de retrait", 'castors');
        $output .= '</label>';
        $output .= $next_select;
        $output .= '</div>';
        $output .= '<p class="description">';
        $output .= esc_html__("Groupe dans lequel sera placé tout utilisateur supprimé automatiquement du groupe courant suite à l'expiration de son adhésion.", 'castors');
        $output .= '</p>';

        return $output;
    }

    public static function product_data_tabs($tabs) {
        unset($tabs['tisdk-suggestions']);
        return $tabs;
    }

    public static function after_read_groups($content, $object) {
        if (Groups_Access_Meta_Boxes::user_can_restrict()) {
            $checked = checked($object->castors_groups_read_visitors, 1, false);
            $label = __("Visible par les visiteurs", 'castors');
            $description = __("Sera affiché aux utilisateurs non connectés en plus des groupes sélectionnés. Si coché avec aucun groupe sélectionné, l'affichage sera limité aux seuls visiteurs.", 'castors');
            $content .= <<<EOF
                <div class="castors-groups-metabox-visitors">
                    <input type="checkbox" id="castors-groups-read-visitors" name="castors-groups-read-visitors" value="true" {$checked} />
                    <label for="castors-groups-read-visitors">{$label}</label>
                    <p class="description">{$description}</p>
                </div>
            EOF;
        }
        return $content;
    }

    public static function after_groups_read_update($post_id) {
        if (isset($_POST['castors-groups-read-visitors'])) {
            update_post_meta($post_id, 'castors_groups_read_visitors', 1);
        } else {
            delete_post_meta($post_id, 'castors_groups_read_visitors');
        }
    }

    public static function product_options_related() {
        global $post;

        include __DIR__ . '/views/html-product-data-linked-products.php';
    }

    public static function product_options_inventory() {
        global $post;

        include __DIR__ . '/views/html-product-data-inventory.php';
    }

    public static function process_product_meta($post_id) {
        $is_external = false;
		if ($product = groups_ws_get_product($post_id)) {
			$is_external = $product->is_type('external');
			unset($product);
		}

        if ($is_external) {
            return;
        }

		delete_post_meta($post_id,'castors_forced_product');
		delete_post_meta($post_id,'castors_sold_onetime');

        $forced_ids = isset($_POST['castors_forced_ids']) ? array_map('intval', (array) wp_unslash($_POST['castors_forced_ids'])) : [];
        foreach ($forced_ids as $id) {
            add_post_meta($post_id, 'castors_forced_product', $id);
        }

        if (isset($_POST['castors_sold_onetime'])) {
            update_post_meta($post_id, 'castors_sold_onetime', 1);
        } else {
            delete_post_meta($post_id, 'castors_sold_onetime');
        }

        if (isset($_POST['castors_sold_notalone'])) {
            update_post_meta($post_id, 'castors_sold_notalone', 1);
        } else {
            delete_post_meta($post_id, 'castors_sold_notalone');
        }
    }

    public static function group_saved($group_id) {
        $next_id = isset($_POST['next-id-field']) ? (int) $_POST['next-id-field'] : null;
        $option_name = 'group_' . $group_id . '_next_id';
        if ($next_id) {
            update_option($option_name, $next_id, false);
        } else {
            delete_option($option_name);
        }
    }
}
