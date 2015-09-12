<?php /*

 Composr
 Copyright (c) ocProducts, 2004-2015

 See text/EN/licence.txt for full licencing information.


 NOTE TO PROGRAMMERS:
   Do not edit this file. If you need to make changes, save your changed file to the appropriate *_custom folder
   **** If you ignore this advice, then your website upgrades (e.g. for bug fixes) will likely kill your changes ****

*/

/**
 * @license    http://opensource.org/licenses/cpal_1.0 Common Public Attribution License
 * @copyright  ocProducts Ltd
 * @package    core
 */

/**
 * Make sure that the given URL contains a session if cookies are disabled.
 * NB: This is used for login redirection. It had to add the session ID into the redirect url.
 *
 * @param  URLPATH $url The URL to enforce results in session persistence for the user
 * @return URLPATH The fixed URL
 * @ignore
 */
function _enforce_sessioned_url($url)
{
    // Take hash off
    $hash = '';
    $hash_pos = strpos($url, '#');
    if ($hash_pos !== false) {
        $hash = substr($url, $hash_pos);
        $url = substr($url, 0, $hash_pos);
    }

    // Take hash off
    $hash = '';
    $hash_pos = strpos($url, '#');
    if ($hash_pos !== false) {
        $hash = substr($url, $hash_pos);
        $url = substr($url, 0, $hash_pos);
    }

    if (strpos($url, '?') === false) {
        $url_scheme = get_option('url_scheme');
        if (($url_scheme == 'HTM') || ($url_scheme == 'SIMPLE')) {
            $url .= '?';
        } else {
            $url .= '/index.php?';
        }
    } else {
        $url .= '&';
    }
    $url = preg_replace('#keep\_session=\w+&#', '', $url);
    $url = preg_replace('#&keep\_session=\w+#', '', $url);

    // Get hash back
    $url .= $hash;
    $url = preg_replace('#\?keep\_session=\w+#', '', $url);

    // Possibly a nested URL too
    $url = preg_replace('#keep\_session=\w+' . preg_quote(urlencode('&')) . '#', '', $url);
    $url = preg_replace('#' . preg_quote(urlencode('&')) . 'keep\_session=\w+#', '', $url);
    $url = preg_replace('#' . preg_quote(urlencode('?')) . 'keep\_session=\w+#', '', $url);

    // Put keep_session back
    $url .= 'keep_session=' . urlencode(get_session_id());

    // Get hash back
    $url .= $hash;

    return $url;
}

/**
 * Set up a new session / Restore an existing one that was lost.
 *
 * @sets_output_state
 *
 * @param  MEMBER $member Logged in member
 * @param  BINARY $session_confirmed Whether the session should be considered confirmed
 * @param  boolean $invisible Whether the session should be invisible
 * @return ID_TEXT New session ID
 */
function create_session($member, $session_confirmed = 0, $invisible = false)
{
    global $SESSION_CACHE, $MEMBER_CACHED;
    $MEMBER_CACHED = $member;

    if (($invisible) && (get_option('is_on_invisibility') == '0')) {
        $invisible = false;
    }

    $new_session = mixed();
    $restored_session = delete_expired_sessions_or_recover($member);
    if (is_null($restored_session)) { // We're force to make a new one
        // Generate random session
        require_code('crypt');
        $new_session = get_rand_password();

        // Store session
        $username = $GLOBALS['FORUM_DRIVER']->get_username($member);
        $new_session_row = array(
            'the_session' => $new_session,
            'last_activity' => time(),
            'member_id' => $member,
            'ip' => get_ip_address(3),
            'session_confirmed' => $session_confirmed,
            'session_invisible' => $invisible ? 1 : 0,
            'cache_username' => $username,
            'the_title' => '',
            'the_zone' => get_zone_name(),
            'the_page' => substr(get_page_name(), 0, 80),
            'the_type' => substr(get_param_string('type', '', true), 0, 80),
            'the_id' => substr(either_param_string('id', ''), 0, 80),
        );
        if (!$GLOBALS['SITE_DB']->table_is_locked('sessions')) { // Better to have no session than a 5+ second loading page
            $GLOBALS['SITE_DB']->query_insert('sessions', $new_session_row, false, true);
            if ((get_forum_type() == 'cns') && (!$GLOBALS['FORUM_DB']->table_is_locked('f_members'))) {
                $GLOBALS['FORUM_DB']->query('UPDATE ' . $GLOBALS['FORUM_DB']->get_table_prefix() . 'f_members SET m_total_sessions=m_total_sessions+1 WHERE id=' . strval($member), 1, null, true);
            }
        }

        $SESSION_CACHE[$new_session] = $new_session_row;

        $big_change = true;
    } else {
        $new_session = $restored_session;
        $prior_session_row = $SESSION_CACHE[$new_session];
        $new_session_row = array(
            'the_title' => '',
            'the_zone' => get_zone_name(),
            'the_page' => get_page_name(),
            'the_type' => substr(either_param_string('type', ''), 0, 80),
            'the_id' => substr(either_param_string('id', ''), 0, 80),
            'last_activity' => time(),
            'ip' => get_ip_address(3),
            'session_confirmed' => $session_confirmed,
        );
        $big_change = ($prior_session_row['last_activity'] < time() - 10) || ($prior_session_row['session_confirmed'] != $session_confirmed) || ($prior_session_row['ip'] != $new_session_row['ip']);
        if ($big_change) {
            if (!$GLOBALS['SITE_DB']->table_is_locked('sessions')) {// Better to have wrong session than a 5+ second loading page
                $GLOBALS['SITE_DB']->query_update('sessions', $new_session_row, array('the_session' => $new_session), '', 1, null, false, true);
            }
        }

        $SESSION_CACHE[$new_session] = array_merge($SESSION_CACHE[$new_session], $new_session_row);
    }

    if ($big_change) { // Only update the persistent cache for non-trivial changes.
        if (get_option('session_prudence') == '0') {// With session prudence we don't store all these in persistent cache due to the size of it all. So only re-save if that's not on.
            persistent_cache_set('SESSION_CACHE', $SESSION_CACHE);
        }
    }

    set_session_id($new_session, is_guest($member));

    // New sessions=Login points
    if ((!is_null($member)) && (!is_guest($member)) && (addon_installed('points')) && (addon_installed('stats'))) {
        // See if this is the first visit today
        global $SESSION_CACHE;
        $test = isset($SESSION_CACHE[get_session_id()]['last_activity']) ? $SESSION_CACHE[get_session_id()]['last_activity'] : null;
        if ($test === null) {
            $test = $GLOBALS['SITE_DB']->query_select_value('stats', 'MAX(date_and_time)', array('member_id' => $member));
        }
        if (!is_null($test)) {
            require_code('temporal');
            require_code('tempcode');
            if (date('d/m/Y', tz_time($test, get_site_timezone())) != date('d/m/Y', tz_time(time(), get_site_timezone()))) {
                require_code('points');
                $_before = point_info($member);
                if (array_key_exists('points_gained_visiting', $_before)) {
                    $GLOBALS['FORUM_DRIVER']->set_custom_field($member, 'points_gained_visiting', strval(intval($_before['points_gained_visiting']) + 1));
                }
            }
        }
    }

    $GLOBALS['SESSION_CONFIRMED_CACHE'] = ($session_confirmed == 1);

    return $new_session;
}

/**
 * Set the session ID of the user.
 *
 * @sets_output_state
 *
 * @param  ID_TEXT $id The session ID
 * @param  boolean $guest_session Whether this is a guest session (guest sessions will use persistent cookies)
 */
function set_session_id($id, $guest_session = false)  // NB: Guests sessions can persist because they are more benign
{
    // If checking safe mode, can really get in a spin. Don't let it set a session cookie till we've completed startup properly.
    global $CHECKING_SAFEMODE;
    if (($CHECKING_SAFEMODE) && ($id == '')) {
        return;
    }

    // Save cookie
    $timeout = $guest_session ? (time() + intval(60.0 * 60.0 * max(0.017, floatval(get_option('session_expiry_time'))))) : null;
    /*if (($GLOBALS['DEV_MODE']) && (get_param_integer('keep_debug_has_cookies',0)==0))      Useful for testing non-cookie support, but annoying if left on
    {
        $test=false;
    } else {*/
    $test = @setcookie(get_session_cookie(), $id, $timeout, get_cookie_path()); // Set a session cookie with our session ID. We only use sessions for secure browser-session login... the database and url's do the rest
    if (is_null($test)) {
        $test = false;
    }
    //}
    $_COOKIE[get_session_cookie()] = $id; // So we remember for this page view

    // If we really have to, store in URL
    if (((!has_cookies()) || (!$test)) && (!$guest_session/*restorable with no special auth*/) && (is_null(get_bot_type()))) {
        $_GET['keep_session'] = $id;
    }

    if ($id != get_session_id()) {
        decache('side_users_online');
    }
}

/**
 * Force an HTTP authentication login box / relay it as if it were a posted login. This function is rarely used.
 */
function force_httpauth()
{
    if (empty($_SERVER['PHP_AUTH_USER'])) {
        header('WWW-Authenticate: Basic realm="' . addslashes(get_site_name()) . '"');
        set_http_status_code('401');
        exit();
    }
    if (isset($_SERVER['PHP_AUTH_PW'])) { // Ah, route as a normal login if we can then
        $_POST['login_username'] = $_SERVER['PHP_AUTH_USER'];
        $_POST['password'] = $_SERVER['PHP_AUTH_PW'];
    }
}

/**
 * Filter a member ID through SU, if SU is on and if the user has permission.
 *
 * @param  MEMBER $member Real logged in member
 * @return MEMBER Simulated member
 */
function try_su_login($member)
{
    $ks = get_param_string('keep_su', '');

    require_code('permissions');
    if (method_exists($GLOBALS['FORUM_DRIVER'], 'forum_layer_initialise')) {
        $GLOBALS['FORUM_DRIVER']->forum_layer_initialise();
    }
    if (has_privilege($member, 'assume_any_member')) {
        $su = $GLOBALS['FORUM_DRIVER']->get_member_from_username($ks);
        if ((is_null($su)) && (is_numeric($ks))) {
            $su = intval($ks);
        }

        if (!is_null($su)) {
            $member = $su;
        } elseif (is_numeric($ks)) {
            $member = intval($ks);
        } else {
            $member = null;
        }

        if (is_null($member)) {
            require_code('site');
            attach_message(do_lang_tempcode('_MEMBER_NO_EXIST', escape_html($ks)), 'warn');
            return get_member();
        }

        if ((!$GLOBALS['FORUM_DRIVER']->is_super_admin($su)) || ($GLOBALS['FORUM_DRIVER']->is_super_admin($member))) {
            if ((!is_guest($member)) && ($GLOBALS['FORUM_DRIVER']->is_banned($member))) { // All hands to the guns
                global $USER_THEME_CACHE;
                $USER_THEME_CACHE = 'default';
                critical_error('YOU_ARE_BANNED');
            }
        }
        $GLOBALS['IS_ACTUALLY_ADMIN'] = true;
        $GLOBALS['IS_ACTUALLY'] = $member;

        if ((get_forum_type() == 'cns') && (get_param_integer('keep_su_online', 0) == 1)) {
            require_code('crypt');
            $new_session_row = array(
                'the_session' => get_rand_password(),
                'last_activity' => time(),
                'member_id' => $member,
                'ip' => get_ip_address(3),
                'session_confirmed' => 0,
                'session_invisible' => 0,
                'cache_username' => $GLOBALS['FORUM_DRIVER']->get_username($member),
                'the_title' => '',
                'the_zone' => get_zone_name(),
                'the_page' => substr(get_page_name(), 0, 80),
                'the_type' => substr(get_param_string('type', '', true), 0, 80),
                'the_id' => substr(either_param_string('id', ''), 0, 80),
            );
            $GLOBALS['SITE_DB']->query_insert('sessions', $new_session_row);
            global $FLOOD_CONTROL_ONCE;
            $FLOOD_CONTROL_ONCE = false;
            $GLOBALS['FORUM_DRIVER']->cns_flood_control($member);
            $GLOBALS['SITE_DB']->query_update('sessions', array('session_invisible' => 1), array('the_session' => get_session_id()), '', 1);
        }
    }

    return $member;
}

/**
 * Try and login via HTTP authentication. This function is only called if HTTP authentication is currently active. With HTTP authentication we trust the PHP_AUTH_USER setting.
 *
 * @return ?MEMBER Logged in member (null: no login happened)
 */
function try_httpauth_login()
{
    global $LDAP_CONNECTION;

    require_code('cns_members');
    require_code('cns_groups');
    require_lang('cns');

    $member = cns_authusername_is_bound_via_httpauth($_SERVER['PHP_AUTH_USER']);
    if ((is_null($member)) && ((running_script('index')) || (running_script('execute_temp')))) {
        require_code('cns_members_action');
        require_code('cns_members_action2');
        if ((trim(post_param_string('email_address', '')) == '') && (get_option('finish_profile') == '1')) {
            require_code('failure');
            if (throwing_errors()) {
                throw new CMSException(do_lang('ENTER_PROFILE_DETAILS_FINISH'));
            }

            @ob_end_clean(); // Emergency output, potentially, so kill off any active buffer
            $middle = cns_member_external_linker_ask($_SERVER['PHP_AUTH_USER'], ((get_option('windows_auth_is_enabled') != '1') || is_null($LDAP_CONNECTION)) ? 'httpauth' : 'ldap');
            $tpl = globalise($middle, null, '', true);
            $tpl->evaluate_echo();
            exit();
        } else {
            $member = cns_member_external_linker($_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_USER'], ((get_option('windows_auth_is_enabled') != '1') || is_null($LDAP_CONNECTION)) ? 'httpauth' : 'ldap');
        }
    }

    if (!is_null($member)) {
        create_session($member, 1, (isset($_COOKIE[get_member_cookie() . '_invisible'])) && ($_COOKIE[get_member_cookie() . '_invisible'] == '1')); // This will mark it as confirmed
    }

    return $member;
}

/**
 * Do a cookie login.
 *
 * @return MEMBER Logged in member (null: no login happened)
 */
function try_cookie_login()
{
    $member = null;

    // Preprocess if this is a serialized cookie
    $member_cookie_name = get_member_cookie();
    $bar_pos = strpos($member_cookie_name, '|');
    $colon_pos = strpos($member_cookie_name, ':');
    if ($colon_pos !== false) {
        $base = substr($member_cookie_name, 0, $colon_pos);
        if ((array_key_exists($base, $_COOKIE)) && ($_COOKIE[$base] != '')) {
            $real_member_cookie = substr($member_cookie_name, $colon_pos + 1);
            $real_pass_cookie = substr(get_pass_cookie(), $colon_pos + 1);

            $the_cookie = $_COOKIE[$base];
            if (get_magic_quotes_gpc()) {
                $the_cookie = stripslashes($_COOKIE[$base]);
            }

            secure_serialized_data($the_cookie, array());

            $unserialize = @unserialize($the_cookie);

            if (is_array($unserialize)) {
                if (array_key_exists($real_member_cookie, $unserialize)) {
                    $the_member = $unserialize[$real_member_cookie];
                    if (get_magic_quotes_gpc()) {
                        $the_member = addslashes(@strval($the_member));
                    }
                    $_COOKIE[get_member_cookie()] = $the_member;
                }
                if (array_key_exists($real_pass_cookie, $unserialize)) {
                    $the_pass = $unserialize[$real_pass_cookie];
                    if (get_magic_quotes_gpc()) {
                        $the_pass = addslashes($the_pass);
                    }
                    $_COOKIE[get_pass_cookie()] = $the_pass;
                }
            }
        }
    } elseif ($bar_pos !== false) {
        $base = substr($member_cookie_name, 0, $bar_pos);
        if ((array_key_exists($base, $_COOKIE)) && ($_COOKIE[$base] != '')) {
            $real_member_cookie = substr($member_cookie_name, $bar_pos + 1);
            $real_pass_cookie = substr(get_pass_cookie(), $bar_pos + 1);

            $the_cookie = $_COOKIE[$base];
            if (get_magic_quotes_gpc()) {
                $the_cookie = stripslashes($_COOKIE[$base]);
            }

            $cookie_contents = explode('||', $the_cookie);

            $the_member = $cookie_contents[intval($real_member_cookie)];
            if (get_magic_quotes_gpc()) {
                $the_member = addslashes(@strval($the_member));
            }
            $_COOKIE[get_member_cookie()] = $the_member;

            $the_pass = $cookie_contents[intval($real_pass_cookie)];
            if (get_magic_quotes_gpc()) {
                $the_pass = addslashes($the_pass);
            }
            $_COOKIE[get_pass_cookie()] = $the_pass;
        }
    }

    if ((array_key_exists(get_member_cookie(), $_COOKIE)) && (array_key_exists(get_pass_cookie(), $_COOKIE))) {
        $store = $_COOKIE[get_member_cookie()];
        $pass = $_COOKIE[get_pass_cookie()];
        if (get_magic_quotes_gpc()) {
            $store = stripslashes($store);
            $pass = stripslashes($pass);
        }
        if ($GLOBALS['FORUM_DRIVER']->is_cookie_login_name()) {
            $username = $store;
            $store = strval($GLOBALS['FORUM_DRIVER']->get_member_from_username($store));
        } else {
            $username = $GLOBALS['FORUM_DRIVER']->get_username(intval($store));
        }
        $member = intval($store);
        if (!is_guest($member)) {
            if ($GLOBALS['FORUM_DRIVER']->is_hashed()) {
                // Test password hash
                $login_array = $GLOBALS['FORUM_DRIVER']->forum_authorise_login(null, $member, $pass, $pass, true);
                $member = $login_array['id'];
            } else {
                // Test password plain
                $login_array = $GLOBALS['FORUM_DRIVER']->forum_authorise_login(null, $member, apply_forum_driver_md5_variant($pass, $username), $pass, true);
                $member = $login_array['id'];
            }

            if (!is_null($member)) {
                global $IS_A_COOKIE_LOGIN;
                $IS_A_COOKIE_LOGIN = true;

                create_session($member, 0, (isset($_COOKIE[get_member_cookie() . '_invisible'])) && ($_COOKIE[get_member_cookie() . '_invisible'] == '1'));
            }
        }
    }

    return $member;
}
