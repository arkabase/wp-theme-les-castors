<?php
if (!defined('ABSPATH') || !defined('CASTORS_THEME_VERSION'))  exit;

define('NODEBB_API_USER_ID', 1);

class Castor_NodeBB {
    public static function formatEndpoint($endpoint) {
        return sprintf('%s?ts=%d&_uid=%d', $endpoint, current_time('timestamp'), NODEBB_API_USER_ID);
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

    public static function delete($endpoint, $args = []) {
        $args['method'] = 'DELETE';
        $response = wp_remote_request(static::formatEndpoint($endpoint), static::parseArgs($args));
        if (is_wp_error($response)) {
            return $response;
        }
        return null;
    }

    public static function getAccountById($id) {
        return static::read(get_site_url() . "/forum/api/v3/users/{$id}");
    }

    public static function getAccountByUsername($username) {
        return static::read(get_site_url() . "/forum/api/user/{$username}");
    }

    public static function updateEmail($user, $nodeBBUser) {
        if ($user->user_email !== $nodeBBUser->email) {
            $result = static::create(get_site_url() . "/forum/api/v3/users/{$nodeBBUser->uid}/emails", ["email" => $user->user_email, "skipConfirmation" => 1]);
            return $result;
        }
        return null;
    }

    public static function createAccount($user) {
        $data = [
            'username' => $user->user_login,
            'fullname' => $user->display_name,
        ];
        return static::create(get_site_url() . "/forum/api/v3/users", $data);
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
        return static::update(get_site_url() . "/forum/api/v3/users/{$nodeBBUser->uid}", $data);
    }

    public static function getGroups() {
        return static::read(get_site_url() . "/forum/api/v3/groups");
    }

    public static function updateGroups($user) {
        if (!class_exists('Groups_User')) {
            // Groups extension is not active
            return null;
        }

        $nodeBBUser = static::getAccountByUsername($user->user_login);
        $groupsUser = new Groups_User($user->ID);
        $nodeBBgroups = static::getGroups()->response->groups;
        $nodeBBgroupNames = array_column($nodeBBgroups, 'name');
        $groupNames = [];

        foreach ($groupsUser->groups as $group) {
            $groupNames[] = $group->group->name;

            if (in_array($group->group->name, $nodeBBUser->groupTitleArray)) {
                // Already in NodeBB group
                continue;
            }

            $index = array_search($group->group->name, $nodeBBgroupNames);
            if ($index === false) {
                // Not a NodeBB group
            }

            // Add user to NodeBB group
            $slug = $nodeBBgroups[$index]->slug;
            static::update(get_site_url() . "/forum/api/v3/groups/{$slug}/membership/{$nodeBBUser->uid}", []);
        }
        
        foreach ($nodeBBUser->groups as $group) {
            if (!in_array($group->name, $groupNames)) {
                // User no longer in group
                static::delete(get_site_url() . "/forum/api/v3/groups/{$group->slug}/membership/{$nodeBBUser->uid}");
            }
        }
    }
}
