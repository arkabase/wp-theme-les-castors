<?php
if (!defined('ABSPATH'))  exit;

define('CASTORS_THEME_VERSION', '0.0.1');
define('CASTORS_THEME_DIR', trailingslashit(get_stylesheet_directory()));
define('CASTORS_THEME_URI', trailingslashit(esc_url(get_stylesheet_directory_uri())));

require_once('utils.php');
require_once('user.php');
require_once('map.php');

function castors_activate() {
    Castor_User::activate();
    Castor_Map::activate();
}
add_action('after_switch_theme', 'castors_activate');

function castors_global_enqueue_scripts() {
    wp_enqueue_style('iconify', 'https://code.iconify.design/iconify-icon/2.0.0/iconify-icon.min.js');
    wp_enqueue_style('jquery-ui-style', WC()->plugin_url() . '/assets/css/jquery-ui/jquery-ui.min.css');
    wp_enqueue_style('castors-style', CASTORS_THEME_URI . 'style.css');
}

function castors_enqueue_scripts() {
    castors_global_enqueue_scripts();
    Castor_User::enqueue_scripts();
    Castor_Map::enqueue_scripts();
}
add_action('wp_enqueue_scripts', 'castors_enqueue_scripts');

function castors_admin_enqueue_scripts() {
    castors_global_enqueue_scripts();
}
add_action('admin_enqueue_scripts', 'castors_admin_enqueue_scripts');

function castors_private_site() {
    global $pagename;
    !is_user_logged_in() && $pagename !== 'mon-compte' &&wp_redirect(site_url() . '/mon-compte');
}
add_action('wp', 'castors_private_site');
add_action('login_init', 'castors_private_site');

function castors_admin_section_desc($args) {
    echo '<p>' . __("<b>JWT Secret</b> doit être le même que celui enregistré dans le plugin <b>Session Sharing</b> du forum.", 'castors') . '</p>'
    . '<p>' . __("<b>Jeton API</b> doit être déclaré dans la section <b>Gestion API</b> de l'administration du forum, associé à un compte administrateur.", 'castors') . '</p>';
}

function castors_admin_secret() {
    echo '<input id="castors_nbb_secret" type="text" name="castors_nbb_secret" value="' . get_option('castors_nbb_secret') . '" class="regular-text">';
}

function castors_admin_api_token() {
    echo '<input id="castors_nbb_api_token" type="text" name="castors_nbb_api_token" value="' . get_option('castors_nbb_api_token') . '" class="regular-text">';
}

function castors_init() {
    Castor_User::init();
    Castor_Map::init();
}
add_action('init', 'castors_init');

function castors_admin_init() {
    add_settings_section(
        'castors-nbb-section',
        __("Authentification sur le forum NodeBB", 'castors'),
        'castors_admin_section_desc',
        'general'
    );

    add_settings_field(
        'castors_nbb_secret',
        __("JWT Secret", 'castors'),
        'castors_admin_secret',
        'general',
        'castors-nbb-section',
        [ 'label_for' => 'castors_nbb_secret' ]
    );

    add_settings_field(
        'castors_nbb_api_token',
        __("Jeton API", 'castors'),
        'castors_admin_api_token',
        'general',
        'castors-nbb-section',
        [ 'label_for' => 'castors_nbb_api_token' ]
    );

    register_setting('general', 'castors_nbb_secret');
    register_setting('general', 'castors_nbb_api_token');

    Castor_User::admin_init();
    Castor_Map::admin_init();
}
add_action('admin_init', 'castors_admin_init');
