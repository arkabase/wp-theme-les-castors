<?php
if (!defined('ABSPATH') || !defined('CASTORS_THEME_VERSION'))  exit;

class Castors_User {
    public static function activate() {
    }

    public static function deactivate() {
        flush_rewrite_rules();
    }

    public static function enqueue_scripts() {
    }

    public static function init() {
        add_filter('auth_cookie_expiration', [__CLASS__, 'cookie_expiration']);

        add_rewrite_endpoint('adhesion', EP_PAGES);
        add_rewrite_endpoint('chantiers', EP_PERMALINK | EP_PAGES);
        add_action('woocommerce_account_membership_endpoint', [__CLASS__, 'account_membership_endpoint']);
        add_action('woocommerce_account_worksites_endpoint', [__CLASS__, 'account_worksites_endpoint']);
        add_filter('woocommerce_get_query_vars', [__CLASS__, 'query_vars'], 99);

        add_filter('woocommerce_account_menu_items', [__CLASS__, 'account_menu_items'], 40);
        add_filter('woocommerce_registration_redirect', [__CLASS__, 'registration_redirect']);
        add_action('woocommerce_edit_account_form_fields', [__CLASS__, 'edit_account']);
        add_action('woocommerce_save_account_details_errors', [__CLASS__, 'save_account_details']);
        add_filter('pre_user_login', [__CLASS__, 'sanitize_username']);
        add_action('user_register', [__CLASS__, 'profile_saved']);
        add_action('profile_update', [__CLASS__, 'profile_saved']);
    }

    public static function admin_init() {
        add_action('edit_user_profile', [__CLASS__, 'edit_profile'], 1);
        add_action('show_user_profile', [__CLASS__, 'edit_profile'], 1);
        add_action('user_new_form', [__CLASS__, 'edit_profile'], 1);
        add_filter('wp_is_application_passwords_available_for_user', [__CLASS__, 'application_passwords'], 10, 2);
        add_action('user_profile_update_errors', [__CLASS__, 'profile_update']);
    }

    public static function query_vars($vars) {
        $vars['membership'] = 'adhesion';
        $vars['worksites'] = 'chantiers';
        return $vars;
    }

    public static function cookie_expiration($length) {
        return 30 * DAY_IN_SECONDS;
    }

    public static function sanitize_username($username) {
        return str_replace([' ', ',', '@'], '_', $username);
    }

    public static function edit_profile($user) {
        $title = __("Informations complémentaires", 'castors');
        $label_location = __("Localisation", 'castors');
        $required = __("(required)");
        $label_member_num = __("N° d'adhérent", 'castors');
        $member_num = esc_attr($user->castors_member_num);
        $readOnly = defined('IS_PROFILE_PAGE') ? ' readOnly' : '';
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
        $new_items = [
            'dashboard'       => __("Tableau de bord", 'castors'),
            'edit-account'    => __("Mes infos", 'castors'),
            'membership'      => __("Mon adhésion", 'castors'),
            'orders'          => __("Mes commandes", 'castors'),
            'downloads'       => __("Mes téléchargements", 'castors'),
            'edit-address'    => __("Mes adresses", 'castors'),
            'customer-logout' => __("Quitter", 'castors'),
        ];

        if (current_user_can('edit_worksites')) {
            Castors_Helper::array_insert_after_key($new_items, 'membership', [
                'worksites' => __("Mes chantiers", 'castors'),
            ]);
        }

        $other_items = [];
        foreach ($items as $key => $label) {
            if (!array_key_exists($key, $new_items)) {
                $other_items[$key] = $label;
            }
        }
        if (count($other_items) > 0) {
            Castors_Helper::array_insert_after_key($new_items, 'edit-address', $other_items);
        }
        return $new_items;
    }

    public static function account_membership_endpoint() {
        wc_get_template('myaccount/membership.php');
    }

    public static function account_worksites_endpoint() {
        wc_get_template('myaccount/worksites.php');
    }

    public static function locationAutocompleteScript() {
        wp_enqueue_script('castors-location', CASTORS_THEME_URI . 'js/location.min.js', ['jquery-ui-autocomplete'], false, ['strategy' =>'defer', 'in_footer' => true]);
    }

    public static function application_passwords($available, $user) {
        return $user->has_cap('administrator');
    }

    public static function profile_update(&$errors) {
        static::save_account_details($errors);
    }

    public static function save_account_details(&$errors) {
        $location = !empty($_POST['location-details']) ? wc_clean(wp_unslash($_POST['location-details'])) : '';
        if (!$location) {
            $errors->add('castors_location_invalid', __("La localisation n'est pas valide, merci d'entrer un code postal pour sélectionner la ville.", 'castors'));
        }
    }

    public static function profile_saved($id) {
        $user = get_user_by('id', $id);
        $location = !empty($_POST['location-details']) ? wc_clean(wp_unslash($_POST['location-details'])) : '';
        update_user_meta($user->ID, 'castors_location_details', $location);

        $current_user = wp_get_current_user();
        if ($current_user->ID !== $user->ID) {
            $member_num = !empty($_POST['member_num']) ? wc_clean(wp_unslash($_POST['member_num'])) : '';
            update_user_meta($user->ID, 'castors_member_num', $member_num);
        }
    }
}
