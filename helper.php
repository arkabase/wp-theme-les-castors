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

    public static function add_caps($role, $singular, $plural = null) {
        if ($plural === null) {
            $plural = $singular . 's';
        }
        get_role($role)->add_cap('edit_' . $singular);
        get_role($role)->add_cap('read_' . $singular);
        get_role($role)->add_cap('delete_' . $singular);
        get_role($role)->add_cap('edit_' . $plural);
        get_role($role)->add_cap('edit_others_' . $plural);
        get_role($role)->add_cap('publish_' . $plural);
        get_role($role)->add_cap('read_private_' . $plural);
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
    }

    public static function set_post_metadata_value(&$value, $key, $id) {
        $value = get_post_meta($id, $key, true) ?: $value;
    }
}