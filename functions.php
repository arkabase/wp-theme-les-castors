<?php
if (!defined('ABSPATH'))  exit;

define('CASTORS_THEME_VERSION', '0.0.1');
define('CASTORS_THEME_DIR', trailingslashit(get_stylesheet_directory()));
define('CASTORS_THEME_URI', trailingslashit(esc_url(get_stylesheet_directory_uri())));

require_once('external/php-jwt/src/JWT.php');
use Firebase\JWT\JWT;

function castors_enqueue_scripts() {
    wp_enqueue_style('castors-style', CASTORS_THEME_URI . 'style.css');
    wp_enqueue_style('iconify', 'https://code.iconify.design/iconify-icon/2.0.0/iconify-icon.min.js');
}
add_action( 'wp_enqueue_scripts', 'castors_enqueue_scripts');

function castors_admin_section_desc($args) {
    echo '<p>' . __("Ce secret doit être le même que celui enregistré dans la configuration du forum, afin que les utilisateurs puissent y accéder sans avoir à s'authentifier à nouveau.", 'castors') . '</div>';
}

function castors_admin_secret() {
    echo '<input id="castors_nbb_secret" type="text" name="castors_nbb_secret" value="' . get_option('castors_nbb_secret') . '" class="regular-text">';
}

function castors_admin_init() {
    add_settings_section(
        'castors-nbb-section',
        __("Authentification sur le forum", 'castors'),
        'castors_admin_section_desc',
        'general'
    );

    add_settings_field(
        'castors_nbb_secret',
        __("Secret", 'castors'),
        'castors_admin_secret',
        'general',
        'castors-nbb-section',
        [ 'label_for' => 'castors_nbb_secret' ]
    );

    register_setting('general', 'castors_nbb_secret');
}
add_action('admin_init', 'castors_admin_init');

function castors_login($user_login, $user) {
    $expiration = time() + 14 * DAY_IN_SECONDS;
    $payload = ['id' => $user->id, 'username' => $user->user_login, 'email' => $user->user_email];
    if ($user->display_name) {
        $payload['fullname'] = $user->display_name;
    }
    if ($user->user_firstname) {
        $payload['firstName'] = $user->user_firstname;
    }
    if ($user->user_lastname) {
        $payload['lastName'] = $user->user_lastname;
    }
    if ($user->user_url) {
        $payload['website'] = $user->user_url;
    }
    $groups_user = new Groups_User($user->ID);
    $groups = [];
    foreach ($groups_user->groups as $group) {
        if ($group->group->name !== 'Registered') {
            $groups[] = $group->group->name;
        }
    }
    if (count($groups)) {
        $payload['groups'] = $groups;
    }
    $secret = get_option('castors_nbb_secret');
    $jwt = JWT::encode($payload, $secret, 'HS256');
    setcookie('wp_nbb_login', $jwt, $expiration, '/', 'les-castors.fr', true, true);
}
add_action('wp_login', 'castors_login', 10, 2);

function castors_logout($action, $result)
{
    setcookie('wp_nbb_login', '', time() - 3600, '/', 'les-castors.fr', true);
    if ($action == 'log-out' && !isset($_GET['_wpnonce'])) {
        $redirect_to = isset($_REQUEST['redirect_to']) ? $_REQUEST['redirect_to'] : '/';
        $location = str_replace('&amp;', '&', wp_logout_url($redirect_to));
        header("Location: $location");
        die;
    }
}
add_action('check_admin_referer', 'castors_logout', 10, 2);

function castors_edit_user_profile_fields($user) {
    $title = __("Informations complémentaires", 'castors');
    $label_location = __("Localisation", 'castors');
    $label_member_num = __("N° d'adhérent", 'castors');
    $location = esc_attr($user->castors_location);
    $member_num = esc_attr($user->castors_member_num);
    $readOnly = IS_PROFILE_PAGE ? ' readOnly' : '';

    echo <<<EOF
        <h2>{$title }</h2>
        <table class="form-table" role="presentation">
            <tbody>
                <tr class="user-location-wrap">
                    <th><label for="location">{$label_location}</label></th>
                    <td><input type="text" name="location" id="location" value="{$location}" class="regular-text code" />
                    <input type="hidden" name="location-coordinates" id="location-coordinates" value="" />
                    <p class="description">Entrer le code postal pour choisir la ville</p></td>
                </tr>
                <tr class="user-member_num-wrap">
                    <th><label for="member_num">{$label_member_num}</label></th>
                    <td><input type="text" name="member_num" id="member_num" value="{$member_num}" class="regular-text code"{$readOnly} /></td>
                </tr>
            </tbody>
        </table>
    EOF;
    _LocationAutocompleteScript('.user-location-wrap #location', '.user-location-wrap #location-coordinates');
}
add_action('edit_user_profile', 'castors_edit_user_profile_fields', 1);

function _LocationAutocompleteScript($fieldSelector, $locationSelector) {
    echo <<<EOF
        <script type="text/javascript">
            jQuery($ => {
                $('{$fieldSelector}').autocomplete({
                    minLength: 5,
                    source: async function (query, done) {
                        try {
                            const response = await fetch('https://geo.api.gouv.fr/communes?codePostal=' + query.term + '&format=geojson&geometry=mairie')
                            const data = await response.json()
                            done(data.features.map(f => ({
                                value: query.term + ', ' + f.properties.nom,
                                code: f.properties.code,
                                coordinates: f.geometry.coordinates,
                            })))
                        } catch(err) {
                            console.log('error', err)
                            done([])
                        }
                    },
                    search: e => {
                        if (!e.target.value.match(/[0-9]{5}/g)) {
                            e.preventDefault()
                        }
                    },
                    select: (event, ui) => {
                        const selected = ui.item
                        $('{$locationSelector}').val(JSON.stringify(selected))
                    },
                });
            });
        </script>
    EOF;
}

function castors_application_passwords($available, $user) {
    return $user->has_cap('administrator');
}
add_filter('wp_is_application_passwords_available_for_user', 'castors_application_passwords', 10, 2);
