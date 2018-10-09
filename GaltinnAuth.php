<?php

class GaltinnAuth {
    var $require_is_volunteer = true;

    public function __construct($run_wp_hooks = true) {
        if ($run_wp_hooks) {
            // Hook before wp_authenticate_username_password and wp_authenticate_email_password at pri 20
            add_filter('authenticate', array($this, 'authenticate'), 10, 3);
            // FIXME: run require_is_volunteer_filter
        }
    }

    function authenticate($user, $username, $password) {
        /* Override authenticate function
         * Ref: https://codex.wordpress.org/Plugin_API/Filter_Reference/authenticate
        **/
        if ($user instanceof WP_User) {
            return $user;
        }
        if (empty($username) && empty($password) && is_wp_error($user)) {
            return $user;
        }

        $galtinn_data = $this->get_galtinn_user($username, $password);

        if ($galtinn_data->status_code !== 200) {
            $error_msg = json_decode($galtinn_data->body, true)['detail'];
            return new WP_Error('invalid_username_or_password',
                __("<strong>ERROR</strong>: $error_msg") .
                ' <a href="' . wp_lostpassword_url() . '">' .
                __('Lost your password?') .
                '</a>'
            );
        }

        $galtinn_user = json_decode($galtinn_data->body, true);
        if ($this->require_is_volunteer && !$galtinn_user['is_volunteer']) {
            $error_msg = 'User with email ' . $galtinn_user['email'] . ' is not a volunteer, which is required to log in.';
            return new WP_Error('invalid_volunteer_status', __("<strong>ERROR</strong>: $error_msg"));
        }
        return $this->get_or_create_user(
            $galtinn_user['username'],
            $password,
            $galtinn_user['email'],
            $galtinn_user['first_name'],
            $galtinn_user['last_name']);
    }

    function get_galtinn_user($username, $password) {
        /* Authenticate by trying to get the user profile */
        $headers = ['Accept' => 'application/json'];
        $options = ['auth' => [$username, $password]];
        $request = Requests::get(GALTINN_URL, $headers, $options);
        return $request;
    }

    function get_or_create_user($username, $password, $email, $first_name, $last_name) {
        $userdata = [
            'user_login' => $username,
            'user_pass' => $password,
            'user_email' => $email,
            'first_name' => $first_name,
            'last_name' => $last_name];
        $existing_user = get_user_by('login', $username);

        if (!$existing_user) {
            $new_user_id = wp_insert_user($userdata);
            if (is_wp_error($new_user_id)) {
                error_log("[error] " . $new_user_id->get_error_message() . "(username: $username)");
            }
            return get_user_by('id', $new_user_id);
        }
        /* Update data */
        $userdata['ID'] = $existing_user->ID;
        $userdata['user_pass'] = wp_hash_password($password);

        $updated_user_id = wp_insert_user($userdata);
        if (is_wp_error($updated_user_id)) {
            error_log("[error] " . $updated_user_id->get_error_message() . "(username: $username)");
        }
        return get_user_by('id', $updated_user_id);
    }
}
