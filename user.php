<?php
if (!defined('ABSPATH') || !defined('CASTORS_THEME_VERSION'))  exit;

require_once('nodebb.php');

require_once('external/php-jwt/src/JWT.php');
use Firebase\JWT\JWT;

class Castor_User {
    public static function enqueue_scripts() {
        wp_enqueue_script('jquery-ui-autocomplete');
        wp_enqueue_style('jquery-ui-style', WC()->plugin_url() . '/assets/css/jquery-ui/jquery-ui.min.css');
    }
    
    public static function init() {
        add_action('wp_login', 'Castor_User::login', 10, 2);
        add_action('wp_logout', 'Castor_User::logout');

        add_rewrite_endpoint('mon-adhesion', EP_PAGES);
        add_action('woocommerce_account_mon-adhesion_endpoint', 'Castor_User::account_adhesion_endpoint'); 
        add_filter('woocommerce_account_menu_items', 'Castor_User::account_menu_items', 40);
        add_filter('woocommerce_registration_redirect', 'Castor_User::registration_redirect');
        add_action('woocommerce_edit_account_form_fields', 'Castor_User::edit_account');
        add_action('woocommerce_save_account_details_errors', 'Castor_User::save_account_details', 10, 2);
        add_action('wp_update_user', 'Castor_User::user_updated');
    }

    public static function admin_init() {
        add_action('edit_user_profile', 'Castor_User::edit_profile', 1);
        add_action('show_user_profile', 'Castor_User::edit_profile', 1);
        add_filter('wp_is_application_passwords_available_for_user', 'Castor_User::application_passwords', 10, 2);
        add_action('user_profile_update_errors', 'Castor_User::profile_update', 10, 3);
    }

    public static function login($user_login, $user) {
        $expiration = time() + 14 * DAY_IN_SECONDS;
        $payload = ['id' => $user->ID, 'username' => $user->user_login];
        /*
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
        */
        $secret = get_option('castors_nbb_secret');
        $jwt = JWT::encode($payload, $secret, 'HS256');
        setcookie('wp_nbb_login', $jwt, $expiration, '/', 'les-castors.fr', true, true);
    }
    
    public static function logout($user_id)
    {
        setcookie('wp_nbb_login', '', time() - 3600, '/', 'les-castors.fr', true);
    }

    public static function edit_profile($user) {
        $title = __("Informations complémentaires", 'castors');
        $label_location = __("Localisation", 'castors');
        $required = __("(required)");
        $label_member_num = __("N° d'adhérent", 'castors');
        $member_num = esc_attr($user->castors_member_num);
        $readOnly = IS_PROFILE_PAGE ? ' readOnly' : '';
        $location_details = $user->castors_location_details;
        $location = json_decode(htmlspecialchars_decode($location_details));
        $location_label = $location ? $location->value : '';

        echo <<<EOF
            <h2>{$title }</h2>
            <table class="form-table" role="presentation">
                <tbody>
                    <tr class="user-location-wrap">
                        <th><label for="location">{$label_location} <span class="description">{$required}</span></label></th>
                        <td><input type="text" name="location" id="location" value="{$location_label}" class="regular-text code" />
                        <input type="hidden" name="location-details" id="location-details" value="{$location_details}" />
                        <p class="description">Entrer le code postal pour sélectionner la ville</p></td>
                    </tr>
                    <tr class="user-member_num-wrap">
                        <th><label for="member_num">{$label_member_num}</label></th>
                        <td><input type="text" name="member_num" id="member_num" value="{$member_num}" class="regular-text code"{$readOnly} /></td>
                    </tr>
                </tbody>
            </table>
        EOF;
        static::locationAutocompleteScript();
    }

    public static function edit_account() {
        $user = wp_get_current_user();
        $label_location = __("Localisation", 'castors');
        $location_details = $user->castors_location_details;
        $location = json_decode(htmlspecialchars_decode($location_details));
        $location_label = $location ? $location->value : '';
        $description = esc_html("Entrez le code postal puis sélectionnez votre ville de résidence, ou celle de votre chantier. Cette information sera utilisée pour afficher votre département et vous localiser sur la carte des adhérents.", 'castors');

        echo <<<EOF
            <div class="clear"></div>
            <p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide user-location-wrap">
                <label for="location">{$label_location}&nbsp;<span class="required">*</span></label>
                <input type="text" class="woocommerce-Input woocommerce-Input--text input-text" name="location" id="location" value="{$location_label}" />
                <input type="hidden" name="location-details" id="location-details" value="{$location_details}" />
                <span><em>{$description}</em></span>
            </p>
        EOF;
        static::locationAutocompleteScript();
    }

    public static function registration_redirect($redirect) {
        return wc_get_account_endpoint_url('edit-account');
    }

    public static function account_menu_items($items) {
        return [
            'dashboard'       => __( "Tableau de bord", 'castors' ),
            'edit-account'    => __( "Mes infos", 'castors' ),
            'mon-adhesion'    => __( "Mon adhésion", 'castors' ),
            'edit-address'    => __( "Mes adresses", 'castors' ),
            'orders'          => __( "Mes commandes", 'castors' ),
            'downloads'       => __( "Mes téléchargements", 'castors' ),
            'customer-logout' => __( "Quitter", 'castors' ),
        ];
    }

    public static function account_adhesion_endpoint() {
        wc_get_template('myaccount/mon-adhesion.php', array('user' => get_user_by('id', get_current_user_id())));
    }
    
    public static function locationAutocompleteScript() {
        echo <<<EOF
            <script type="text/javascript">
                jQuery($ => {
                    $('.user-location-wrap #location').autocomplete({
                        source: async function (query, done) {
                            try {
                                const postcode = query.term.substring(0, 5)
                                const response = await fetch('https://geo.api.gouv.fr/communes?codePostal=' + postcode + '&format=geojson&geometry=mairie')
                                const data = await response.json()
                                if (!data?.features?.length) {
                                    throw new Error("Not a valid postcode")
                                }
                                done(data.features.map(f => ({
                                    value: postcode + ', ' + f.properties.nom,
                                    postcode,
                                    code: f.properties.code,
                                    department: f.properties.codeDepartement,
                                    coordinates: f.geometry.coordinates,
                                })))
                            } catch(err) {
                                console.log('error', err)
                                done([])
                            }
                        },
                        search: e => {
                            $('.user-location-wrap #location-details').val('')
                            if (!e.target.value.match(/^[0-9]{5}/g)) {
                                e.preventDefault()
                            }
                        },
                        select: (event, ui) => {
                            const selected = ui.item
                            $('.user-location-wrap #location-details').val(JSON.stringify(selected))
                        },
                    });
                });
            </script>
        EOF;
    }

    public static function application_passwords($available, $user) {
        return $user->has_cap('administrator');
    }

    public static function profile_update($errors, $update, $user) {
        static::save_account_details($errors, $user);

        if ($errors->get_error_messages()) {
            return;
        }
        
        $current_user = wp_get_current_user();
        if ($current_user->ID !== $user->ID) {
            $member_num = !empty($_POST['member_num']) ? wc_clean(wp_unslash($_POST['member_num'])) : '';
            update_user_meta($user->ID, 'castors_member_num', $member_num);
        }
    }

    public static function save_account_details($errors, $user) {
        $location = !empty($_POST['location-details']) ? wc_clean(wp_unslash($_POST['location-details'])) : '';
        if (!$location) {
            $errors->add('castors_location_invalid', __("La localisation n'est pas valide, merci d'entrer un code postal pour sélectionner la ville.", 'castors'));
        }
    
        if ($errors->get_error_messages()) {
            return;
        }
    
        update_user_meta($user->ID, 'castors_location_details', $location);
    }

    public static function user_updated($id) {
        $user = get_user_by('id', $id);
        Castor_NodeBB::updateAccount($user);
        Castor_NodeBB::updateGroups($user);
    }
}