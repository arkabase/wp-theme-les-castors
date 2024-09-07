<?php
/**
 * Defines /profil/?<username> page slug as a redirection to <username>'s profile
 * if current user is administrator and is not <username>
 * and to current user's account otherwise
 */

$query = rawurldecode($_SERVER['QUERY_STRING']);
preg_match('/user\/(.*)\/edit/', $query, $matches);
$username = $matches[1];
$me = wp_get_current_user();

if ($me->user_login !== $username) {
    $user = get_user_by('login', $username);
    if (user_can($me, 'edit_user', $user->ID)) {
        // Edit other user's profile
        wp_redirect("/wp-admin/user-edit.php?user_id={$user->ID}");
        exit;
    }
}

// Edit own profile
wp_redirect('/compte/mes-infos/');
