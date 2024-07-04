<?php
if (!defined('ABSPATH') || !defined('CASTORS_THEME_VERSION'))  exit;

require_once('helper.php');

class Castors_Worksite {
    public static function activate() {
        static::register();
        Castors_Helper::add_caps('administrator', 'worksite');
    }

    public static function deactivate() {
	    unregister_post_type('worksite');
        Castors_Helper::remove_caps('administrator', 'worksite');
    }

    public static function enqueue_scripts() {
    }

    public static function admin_enqueue_scripts() {
        wp_enqueue_style('castors-admin-worksite', CASTORS_THEME_URI . 'css/admin-worksite.css');
        wp_enqueue_script('castors-admin-worksite', CASTORS_THEME_URI . 'js/worksite.min.js', ['jquery']);
    }

    public static function admin_menu() {
    }

    public static function admin_init() {
        add_action('add_meta_boxes_worksite', [__CLASS__, 'edit_meta_boxes'], 99);
        add_action('save_post_worksite', [__CLASS__, 'worksite_saved'], 10, 2);
    }

    public static function init() {
        static::register();
    }

    public static function register() {
        register_post_type('worksite', [
            'labels' => [
                'name' => __("Chantiers", 'castors'),
                'singular_name' => __("Chantier", 'castors'),
                'add_new' => __("Ajouter", 'castors'),
                'add_new_item' => __("Ajouter un chantier", 'castors'),
                'edit_item' => __("Modifier un chantier", 'castors'),
                'new_item' => __("Nouveau chantier", 'castors'),
                'view_item' => __("Voir le chantier", 'castors'),
                'view_items' => __("Voir les chantiers", 'castors'),
                'search_items' => __("Rechercher un chantier", 'castors'),
                'not_found' => __("Aucun chantier n'a été trouvé", 'castors'),
                'not_found_in_trash' => __("Aucun chantier n'a été trouvé", 'castors'),
                'all_items' => __("Tous les chantiers", 'castors'),
                'filter_items_list' => __("Filtrer les chantiers", 'castors'),
            ],
            'description' => __("Chantier géré par un adhérent", 'castors'),
            'public' => true,
            'show_ui' => true,
            'menu_position' => 30,
            'menu_icon' => 'data:image/svg+xml;base64,' . base64_encode('<svg width="20" height="20" viewBox="0 0 576 512" xmlns="http://www.w3.org/2000/svg"><path fill="black" d="M208 64a48 48 0 1 1 96 0 48 48 0 1 1 -96 0zM9.8 214.8c5.1-12.2 19.1-18 31.4-12.9L60.7 210l22.9-38.1C99.9 144.6 129.3 128 161 128c51.4 0 97 32.9 113.3 81.7l34.6 103.7 79.3 33.1 34.2-45.6c6.4-8.5 16.6-13.3 27.2-12.8s20.3 6.4 25.8 15.5l96 160c5.9 9.9 6.1 22.2 .4 32.2s-16.3 16.2-27.8 16.2H288c-11.1 0-21.4-5.7-27.2-15.2s-6.4-21.2-1.4-31.1l16-32c5.4-10.8 16.5-17.7 28.6-17.7h32l22.5-30L22.8 246.2c-12.2-5.1-18-19.1-12.9-31.4zm82.8 91.8l112 48c11.8 5 19.4 16.6 19.4 29.4v96c0 17.7-14.3 32-32 32s-32-14.3-32-32V405.1l-60.6-26-37 111c-5.6 16.8-23.7 25.8-40.5 20.2S-3.9 486.6 1.6 469.9l48-144 11-33 32 13.7z"/></svg>'),
            'capability_type' => 'worksite',
            'supports' => ['title', 'author', 'editor', 'revisions', 'thumbnail'],
            'register_meta_box_cb' => [__CLASS__, 'register_metaboxes'],
            'has_archive' => true,
            'rewrite' => ['slug' => 'chantier'],
            'query_var' => 'chantier',
            'delete_with_user' => true,
        ]);
    }

    public static function edit_meta_boxes() {
        global $wp_meta_boxes;

        $postimagediv = $wp_meta_boxes['worksite']['side']['low']['postimagediv'];
        add_meta_box($postimagediv['id'], $postimagediv['title'], $postimagediv['callback'], null, 'normal', 'default', $postimagediv['args']);
        remove_meta_box('postimagediv', 'worksite', 'side');
        remove_meta_box('postcustom', 'worksite', 'normal');
    }

    public static function get_metadata() {
        return [
            'start-date' => '',
            'end-date' => '',
            'participatory' => false,
            'topic-uri' => '',
            'chatroom-uri' => '',
        ];
    }

    public static function details_metabox($post, $metabox) {
        $worksite_metadata = static::get_metadata();
        if ($post->ID) {
            array_walk($worksite_metadata, ['Castors_Helper', 'set_post_metadata_value'], $post->ID);
        }
		include __DIR__ . '/views/html-worksite-details.php';
    }
    
    public static function register_metaboxes() {
        add_meta_box('castors_worksite_details', __("Détails du chantier", 'castors'), [__CLASS__, 'details_metabox'], 'worksite', 'normal', 'core');
    }

    public static function worksite_saved($post_id, $post) {
        foreach (static::get_metadata() as $key => $data) {
            $value = intval($_POST['castors-worksite-' . $key]);
            update_post_meta($post_id, $key, $value);
        }
    }
}