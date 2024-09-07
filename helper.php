<?php
if (!defined('ABSPATH'))  exit;

class Castors_Helper {

    public static function array_insert_after_key(&$array, $key, $array_to_insert)
    {
        $key_pos = array_search($key, array_keys($array));
        if($key_pos !== false){
            $key_pos++;
            $second_array = array_splice($array, $key_pos);
            $array = array_merge($array, $array_to_insert, $second_array);
        }
    }

    public static function add_caps($role, $singular, $plural = null, $others = false, $terms = false) {
        if ($plural === null) {
            $plural = $singular . 's';
        }
        get_role($role)->add_cap('edit_' . $singular);
        get_role($role)->add_cap('read_' . $singular);
        get_role($role)->add_cap('delete_' . $singular);
        get_role($role)->add_cap('edit_' . $plural);
        if ($others) {
            get_role($role)->add_cap('edit_others_' . $plural);
        }
        get_role($role)->add_cap('publish_' . $plural);
        get_role($role)->add_cap('read_private_' . $plural);
        if ($terms) {
            get_role($role)->add_cap('manage_' . $singular . '_terms');
        }
    }

    public static function remove_caps($role, $singular, $plural = null) {
        if ($plural === null) {
            $plural = $singular . 's';
        }
        get_role($role)->remove_cap('edit_' . $singular);
        get_role($role)->remove_cap('read_' . $singular);
        get_role($role)->remove_cap('delete_' . $singular);
        get_role($role)->remove_cap('edit_' . $plural);
        get_role($role)->remove_cap('edit_others_' . $plural);
        get_role($role)->remove_cap('publish_' . $plural);
        get_role($role)->remove_cap('read_private_' . $plural);
        get_role($role)->remove_cap('manage_' . $singular . '_terms');
    }

    public static function set_post_metadata_value(&$value, $key, $id) {
        $value = get_post_meta($id, $key, true) ?: $value;
    }

    public static function listTransferScripts($field, $settings) {
        wp_enqueue_style('castors-jquery-transfer-icon-style', CASTORS_THEME_URI . 'js/jquery-transfer/icon_font/css/icon_font.css');
        wp_enqueue_style('castors-jquery-transfer-style', CASTORS_THEME_URI . 'js/jquery-transfer/css/jquery.transfer.css');
        wp_enqueue_script('castors-jquery-transfer', CASTORS_THEME_URI . 'js/jquery-transfer/js/jquery.transfer.js', ['jquery-core'], false, ['strategy' =>'defer', 'in_footer' => true]);
        wp_enqueue_script('castors-list-transfer', CASTORS_THEME_URI . 'js/transfer.min.js', ['castors-jquery-transfer'], false, ['strategy' =>'defer', 'in_footer' => true]);
        wp_localize_script('castors-list-transfer', 'transferListItems', ['field' => $field, 'settings' => $settings]);
    }
}
