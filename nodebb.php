<?php
if (!defined('ABSPATH') || !defined('CASTORS_THEME_VERSION'))  exit;

class Castor_NodeBB {
    public static function formatEndpoint($endpoint) {
        return sprintf('%s?ts=%d', $endpoint, current_time('timestamp'));
    }

    public static function parseArgs($args) {
        $token = get_option('castors_nbb_api_token');
        $defaults = [
            'headers' => [
                'Authorization' => "Bearer {$token}",
                'Accept' => 'application/json',
            ],
        ];
        return wp_parse_args($args, $defaults);
    }

    public static function read($endpoint, $args = []) {
        $response = wp_remote_get(static::formatEndpoint($endpoint), static::parseArgs($args));
        if (is_wp_error($response)) {
            return $response;
        }
        if ($response['response']['code'] === 404) {
            return null;
        }
        return json_decode($response['body']);
    }

    public static function create($endpoint, $body, $args = []) {
        if ($body) {
            $args['body'] = $body;
            $args['Content-Type'] = 'application/json';
        }
        $response = wp_remote_post(static::formatEndpoint($endpoint), static::parseArgs($args));
        if (is_wp_error($response)) {
            return $response;
        }
        return json_decode($response['body']);
    }

    public static function update($endpoint, $body, $args = []) {
        $args['method'] = 'PUT';
        if ($body) {
            $args['body'] = $body;
            $args['Content-Type'] = 'application/json';
        }
        $response = wp_remote_request(static::formatEndpoint($endpoint), static::parseArgs($args));
        if (is_wp_error($response)) {
            return $response;
        }
        return json_decode($response['body']);
    }

    public static function getAccountById($id) {
        return static::read(get_site_url() . "/le-forum/api/v3/users/{$id}");
    }

    public static function getAccountByUsername($username) {
        return static::read(get_site_url() . "/le-forum/api/user/username/{$username}");
    }

    public static function updateEmail($user, $nodeBBUser) {
        if ($user->user_email !== $nodeBBUser->email) {
            $result = static::create(get_site_url() . "/le-forum/api/v3/users/{$nodeBBUser->uid}/emails", ["email" => $user->user_email, "skipConfirmation" => 1]);
            return $result;
        }
        return null;
    }

    public static function createAccount($user) {
        $data = [
            'username' => $user->user_login,
            'fullname' => $user->display_name,
        ];
        return static::create(get_site_url() . "/le-forum/api/v3/users", $data);
    }

    public static function updateAccount($user, $createIfNotExist = true) {
        $nodeBBUser = static::getAccountByUsername($user->user_login);
        $location = $user->castors_location_details ? json_decode(htmlspecialchars_decode($user->castors_location_details)) : null;
        $data = [
            'fullname' => $user->display_name,
            'website' => $user->user_url ?: '',
            'coordinates' => $location ? $location->coordinates : '',
            'location' => $location ? $location->value : '',
            'aboutme' => $user->description ?: '',
        ];
        if ($location) {
            $data['fullname'] .= " ({$location->department})";
        }

        if (!$nodeBBUser && $createIfNotExist) {
            $nodeBBUser = static::createAccount($user);
        }
            
        if (!$nodeBBUser) {
            return null;
        }

        static::updateEmail($user, $nodeBBUser);
        return static::update(get_site_url() . "/le-forum/api/v3/users/{$nodeBBUser->uid}", $data);
    }

    public static function updateGroups($user) {
        if (!class_exists('Groups_User')) {
            return null;
        }
        $user = new Groups_User($user->ID);
        var_dump($user->groups);exit;
        $nodeBBUser = static::getAccountByUsername($user->user_login);
        $location = $user->castors_location_details ? json_decode(htmlspecialchars_decode($user->castors_location_details)) : null;
        $data = [
            'fullname' => $user->display_name,
            'website' => $user->user_url ?: '',
            'coordinates' => $location ? $location->coordinates : '',
            'location' => $location ? $location->value : '',
            'aboutme' => $user->description ?: '',
        ];
        if ($location) {
            $data['fullname'] .= " ({$location->department})";
        }

        if ($nodeBBUser) {
            static::updateEmail($user, $nodeBBUser);
            return static::update(get_site_url() . "/le-forum/api/v3/users/{$nodeBBUser->uid}", $data);
        }

        if ($createIfNotExist) {
            $data['username'] = $user->user_login;
            $data['email'] = $user->user_email;
            return static::create(get_site_url() . "/le-forum/api/v3/users", $data);
        }

        return null;
    }
}
