<?php
if (!defined('ABSPATH') || !defined('CASTORS_THEME_VERSION'))  exit;

class Castor_Map {
    public static function activate() {
        get_role('administrator')->add_cap('castor_admin_map');
        get_role('administrator')->add_cap('castors_map_show_members');
        get_role('administrator')->add_cap('castors_map_show_worksites');
        get_role('administrator')->add_cap('castors_map_show_experts');
    }

    public static function enqueue_scripts() {
        global $post;
        if(is_a($post, 'WP_Post') && has_shortcode($post->post_content, 'castors_map')) {
            wp_enqueue_script('leaflet', 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js');
            wp_enqueue_script('leaflet-markercluster', 'https://unpkg.com/leaflet.markercluster@1.4.1/dist/leaflet.markercluster.js');
            wp_enqueue_script('castors-map', CASTORS_THEME_URI . 'js/map.js', ['leaflet'], false, ['strategy' =>'defer', 'in_footer' => true]);

            wp_enqueue_style('leaflet', 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css');
            wp_enqueue_style('leaflet-markercluster', 'https://unpkg.com/leaflet.markercluster@1.4.1/dist/MarkerCluster.css');
            wp_enqueue_style('leaflet-markercluster-default', 'https://unpkg.com/leaflet.markercluster@1.4.1/dist/MarkerCluster.Default.css');
            
            $user = wp_get_current_user();
            $config = [
                'root' => esc_url_raw(rest_url()),
                'nonce' => wp_create_nonce('wp_rest'),
                'center' => [45.7461252, 4.8331952],
                'zoom' => 13,
            ];
            $location_details = $user->castors_location_details;
            if ($location_details) {
                $location = json_decode(htmlspecialchars_decode($location_details));
                $config['center'] = array_reverse($location->coordinates);
            }
            wp_localize_script('castors-map', 'castorsMapApiSettings', $config);
        }
    }

    public static function init() {
        add_action('rest_api_init', [__CLASS__, 'add_api_routes']);
        add_action('admin_menu', [__CLASS__, 'admin_menu'], 99);
        static::addShortcodes();
    }

    public static function admin_menu() {
        $page = add_menu_page(
			esc_html__('La carte', 'castors' ),
			esc_html__('Carte', 'castors' ),
			'list_users',
			'castor_admin_map',
			[__CLASS__, 'admin_map'],
			'dashicons--location-alt',
			50
		);
    }

    public static function admin_map() {
        var_dump('ADMIN MAP');
    }

    public static function admin_init() {

    }

    public static function addShortcodes() {
        add_shortcode('castors_map', [__CLASS__, 'shortcode_map']);
    }

    public static function shortcode_map($args) {
        return '<div id="castors-map"></div>';
    }

    public static function add_api_routes() {
        register_rest_route(
            'castors/v1',
            '/map/layer/(?P<layer>.+)',
            [
                'methods' => WP_REST_Server::READABLE,
                'callback' => [__CLASS__, 'map_layer'],
                'permission_callback' => [__CLASS__, 'map_layer_check_permissions'],
            ]
        );
    }

    public static function map_layer($request) {
        $layer = explode(',', $request['layer']);
        $response = [];
        switch ($request['layer']) {
            case 'adherents':
                $users = get_users(['meta_key' => 'castors_location_details']);
                $features = array_filter(array_map([__CLASS__, 'map_layer_member_geojson'], $users));
                return rest_ensure_response([
                    'type' => 'FeatureCollection',
                    'features' => array_values($features)
                ]);

            default:
                return [];
        }
    }

    public static function map_layer_member_geojson($user) {
        $location_details = $user->castors_location_details;
        $location = json_decode(htmlspecialchars_decode($location_details));
        if ($location) {
            return [
                'type' => 'Feature',
                'properties' => [
                    'type' => 'member',
                    'id' => $user->ID,
                    'name' => $user->user_login,
                    'location' => $location->value,
                ],
                'geometry' => [
                    'type' => 'Point',
                    'coordinates' => $location->coordinates,
                ]
            ];
        }
        return null;
    }

    public static function map_layer_check_permissions($request) {
        switch ($request['layer']) {
            case 'adherents':
                if (current_user_can('castors_map_show_members')) {
                    return true;
                }
                break;
            case 'pros':
                if (current_user_can('castors_map_show_experts')) {
                    return true;
                }
                break;
            case 'chantiers':
                if (current_user_can('castors_map_show_worksites')) {
                    return true;
                }
                break;
            default:
                break;
        }
        return new WP_Error('rest_forbidden', esc_html__("Sorry, you cannot do that !", 'castors'), array('status' => 401));
    }
}
