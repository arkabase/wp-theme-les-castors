<?php
if (!defined('ABSPATH'))  exit;

define('CASTORS_THEME_VERSION', '0.0.1');
define('CASTORS_THEME_DIR', trailingslashit(get_stylesheet_directory()));
define('CASTORS_THEME_URI', trailingslashit(esc_url(get_stylesheet_directory_uri())));

require_once('backend.php');
require_once('utils.php');
require_once('user.php');
require_once('map.php');

function castors_activate() {
    Castor_User::activate();
    Castor_Map::activate();
}
add_action('after_switch_theme', 'castors_activate');

function castors_global_enqueue_scripts() {
    //wp_enqueue_style('iconify', 'https://code.iconify.design/iconify-icon/2.0.0/iconify-icon.min.js');
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
    Castor_Backend::enqueue_scripts();
}
add_action('admin_enqueue_scripts', 'castors_admin_enqueue_scripts');

function castors_private_site() {
    global $pagename;
    !is_user_logged_in() && $pagename !== 'mon-compte' &&wp_redirect(site_url() . '/mon-compte');
}
add_action('wp', 'castors_private_site');
add_action('login_init', 'castors_private_site');

function castors_init() {
    Castor_User::init();
    Castor_Map::init();
}
add_action('init', 'castors_init');

function castors_admin_init() {
    Castor_Backend::init();
    Castor_User::admin_init();
    Castor_Map::admin_init();
}
add_action('admin_init', 'castors_admin_init');
