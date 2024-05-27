<?php
if (!defined('ABSPATH') || !defined('CASTORS_THEME_VERSION'))  exit;

class Castor_Backend {
    public static function activate() {
    }

    public static function enqueue_scripts() {
    }

    public static function init() {
        add_action('wp_nav_menu_item_custom_fields', [__CLASS__, 'menu_item_fields'], 5, 2);
        add_action('wp_update_nav_menu_item', [__CLASS__, 'menu_item_update'], 10, 2);

        add_settings_section(
            'castors-nbb-section',
            __("Authentification sur le forum NodeBB", 'castors'),
            [__CLASS__, 'section_desc'],
            'general'
        );
    
        add_settings_field(
            'castors_nbb_secret',
            __("JWT Secret", 'castors'),
            [__CLASS__, 'secret'],
            'general',
            'castors-nbb-section',
            [ 'label_for' => 'castors_nbb_secret' ]
        );
    
        add_settings_field(
            'castors_nbb_api_token',
            __("Jeton API", 'castors'),
            [__CLASS__, 'api_token'],
            'general',
            'castors-nbb-section',
            [ 'label_for' => 'castors_nbb_api_token' ]
        );
    
        register_setting('general', 'castors_nbb_secret');
        register_setting('general', 'castors_nbb_api_token');
    }

    function section_desc($args) {
        echo '<p>' . __("<b>JWT Secret</b> doit être le même que celui enregistré dans le plugin <b>Session Sharing</b> du forum.", 'castors') . '</p>'
        . '<p>' . __("<b>Jeton API</b> doit être déclaré dans la section <b>Gestion API</b> de l'administration du forum, associé à un compte administrateur.", 'castors') . '</p>';
    }
    
    function secret() {
        echo '<input id="castors_nbb_secret" type="text" name="castors_nbb_secret" value="' . get_option('castors_nbb_secret') . '" class="regular-text">';
    }
    
    function api_token() {
        echo '<input id="castors_nbb_api_token" type="text" name="castors_nbb_api_token" value="' . get_option('castors_nbb_api_token') . '" class="regular-text">';
    }

    public static function menu_item_fields($item_id, $menu_item) {
        $label = __("Capacité", 'castors');
        $description = esc_html("Seuls les utilisateurs ayant cette capacité verront cet élément de menu. Laisser vide pour l'afficher à tous les utilisateurs", 'castors');

        echo <<<EOF
            <p class="field-capability description description-wide">
                <label for="edit-menu-item-capability-{$item_id}">
                    {$label}<br />
                    <input type="text" id="edit-menu-item-capability-{$item_id}" class="widefat edit-menu-item-capability" name="menu-item-capability[{$item_id}]" value="{$menu_item->menu_item_capability}" />
                    <span class="description">{$description}</span>
                </label>
            </p>
        EOF;
    }

    public static function menu_item_update($menu_id, $item_id) {
        if ($_POST['menu-item-capability']) {
            update_post_meta($item_id, 'menu-item-capability', $_POST['menu-item-capability'][$item_id] ?? '');
        }
    }
}
