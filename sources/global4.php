<?php /*

 Composr
 Copyright (c) ocProducts, 2004-2018

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
 * Standard code module initialisation function.
 *
 * @ignore
 */
function init__global4()
{
    global $ADMIN_LOGGING_ON;
    $ADMIN_LOGGING_ON = true;
}

/**
 * Attach a message mentioning how the site is closed.
 *
 * @param  Tempcode $messages_bottom Where to place the message
 */
function attach_message_site_closed(&$messages_bottom)
{
    if ((!in_array(get_page_name(), array('login', 'join'))) && (get_param_integer('wide_high', 0) == 0) && (($GLOBALS['IS_ACTUALLY_ADMIN']) || (has_privilege(get_member(), 'access_closed_site')))) {
        $messages_bottom->attach(do_template('MESSAGE', array(
            '_GUID' => '03a41a91606b3ad05330e7d6f3e741c1',
            'TYPE' => 'notice',
            'MESSAGE' => do_lang_tempcode(has_privilege(get_member(), 'access_closed_site') ? 'SITE_SPECIAL_ACCESS' : 'SITE_SPECIAL_ACCESS_SU'),
        )));
    }
}

/**
 * Attach a message mentioning SU is active.
 *
 * @param  Tempcode $messages_bottom Where to place the message
 */
function attach_message_su(&$messages_bottom)
{
    $unsu_url = get_self_url(true, true, array('keep_su' => null));
    $su_username = $GLOBALS['FORUM_DRIVER']->get_username(get_member());
    $messages_bottom->attach(do_template('MESSAGE', array(
        '_GUID' => '13a41a91606b3ad05330e7d6f3e741c1',
        'TYPE' => 'notice',
        'MESSAGE' => do_lang_tempcode('USING_SU', escape_html($unsu_url), escape_html($su_username)),
    )));
}

/**
 * Save a file of merged web resources.
 *
 * @param  array $resources Resources (map of keys to 1), passed by reference as we alter it
 * @param  ID_TEXT $type Resource type
 * @set .css .js
 * @param  PATH $write_path Write path
 * @return boolean If the merge happened
 *
 * @ignore
 */
function _save_web_resource_merging($resources, $type, $write_path)
{
    // Create merged resource...

    $data = '';
    $good_to_go = true;
    $all_strict = true;
    foreach ($resources as $resource) {
        if ($resource == 'no_cache') {
            continue;
        }

        if ($type == '.js') {
            $merge_from = javascript_enforce($resource);
        } else { // .css
            $merge_from = css_enforce($resource);
        }
        if ($merge_from != '') {
            if (is_file($merge_from)) {
                $extra_data = unixify_line_format(cms_file_get_contents_safe($merge_from)) . "\n\n";
                $data .= $extra_data;
                if (strpos($extra_data, '"use strict";') === false && strpos($extra_data, "'use strict';") === false) {
                    $all_strict = false;
                }
            } else { // race condition
                $good_to_go = false;
                break;
            }
        }
    }

    if ($good_to_go) {
        if (!$all_strict) {
            $data = str_replace('"use strict";', '', str_replace("'use strict';", '', $data));
        }

        require_code('files');
        cms_file_put_contents_safe($write_path, $data, FILE_WRITE_FIX_PERMISSIONS | FILE_WRITE_SYNC_FILE);

        require_code('web_resources2');
        compress_cms_stub_file($write_path);
    }

    return $good_to_go;
}

/**
 * Take a Tempcode object and run some hackerish code to make it XHTML-strict.
 *
 * @param  object $global Tempcode object
 * @return object Tempcode object (no longer cache safe)
 */
function make_xhtml_strict($global)
{
    $_global = $global->evaluate();
    $_global = str_replace(
        '<!DOCTYPE html>',
        '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">',
        $_global);
    $_global = preg_replace('#(<a\s[^>]*)onclick="([^"]*)"(\s[^>]*)target="_blank"#', '${1}onclick="this.target=\'_blank\'; ${2}"${3}', $_global);
    $_global = preg_replace('#(<a\s[^>]*)target="_blank"(\s[^>]*)onclick="([^"]*)"#', '${1}onclick="this.target=\'_blank\'; ${3}"${2}', $_global);
    $_global = preg_replace('#(<a\s[^>]*)target="_blank"#', '${1}onclick="this.target=\'_blank\';"', $_global);
    $_global = preg_replace('#(<form\s[^>]*)onsubmit="([^"]*)"(\s[^>]*)target="_blank"#', '${1}onsubmit="this.target=\'_blank\'; ${2}"${3}', $_global);
    $_global = preg_replace('#(<form\s[^>]*)target="_blank"(\s[^>]*)onsubmit="([^"]*)"#', '${1}onsubmit="this.target=\'_blank\'; ${3}"${2}', $_global);
    $_global = preg_replace('#(<form\s[^>]*)target="_blank"#', '${1}onsubmit="this.target=\'_blank\';"', $_global);
    $_global = preg_replace('#(<(a|form)\s[^>]*)target="[^"]*"#', '${1}', $_global);
    return make_string_tempcode($_global);
}

/**
 * Get links and details related to a member.
 * Note that this is:
 * 1) Available under all forum drivers
 * 2) Mainly about producing UI elements, not raw data
 * See the cns_read_in_member_profile function for a CNS-specific raw-data retriever.
 *
 * @param  MEMBER $member_id A member ID
 * @return array A tuple: links (Tempcode), eCommerce links (Tempcode), details (Tempcode), number of unread inline personal posts or private topics
 */
function member_personal_links_and_details($member_id)
{
    static $cache = array();
    if (isset($cache[$member_id])) {
        return $cache[$member_id];
    }

    $details = new Tempcode();
    $links = new Tempcode();
    $links_ecommerce = new Tempcode();

    if (get_forum_type() != 'none') {
        // Post count
        if ((!has_no_forum()) && (get_option('forum_show_personal_stats_posts') == '1')) {
            $details->attach(do_template('BLOCK_SIDE_PERSONAL_STATS_LINE', array('_GUID' => '371dfee46e8c40b1b109e0350055f8cc', 'KEY' => do_lang_tempcode('COUNT_POSTSCOUNT'), 'VALUE' => integer_format($GLOBALS['FORUM_DRIVER']->get_post_count($member_id)))));
        }
        // Topic count
        if ((!has_no_forum()) && (get_option('forum_show_personal_stats_topics') == '1')) {
            $details->attach(do_template('BLOCK_SIDE_PERSONAL_STATS_LINE', array('_GUID' => '2dd2a2d30c4ea7144c74ab058239fb23', 'KEY' => do_lang_tempcode('COUNT_TOPICSCOUNT'), 'VALUE' => integer_format($GLOBALS['FORUM_DRIVER']->get_topic_count($member_id)))));
        }

        // Member profile view link
        if (get_option('cns_show_profile_link') == '1') {
            $url = $GLOBALS['FORUM_DRIVER']->member_profile_url($member_id, true);
            $links->attach(do_template('BLOCK_SIDE_PERSONAL_STATS_LINK', array('_GUID' => '2c8648c953c802a9de41c3adeef0e97f', 'NAME' => do_lang_tempcode('MY_PROFILE'), 'URL' => $url, 'REL' => 'me')));
        }
    }

    // Point count
    if (addon_installed('points')) {
        require_lang('points');
        require_code('points');
        if (get_option('points_show_personal_stats_points_left') == '1') {
            $details->attach(do_template('BLOCK_SIDE_PERSONAL_STATS_LINE', array('_GUID' => '6241e58e30457576735f3a2618fd7fff', 'KEY' => do_lang_tempcode('COUNT_POINTS_LEFT'), 'VALUE' => integer_format(available_points($member_id)))));
        }
        if (get_option('points_show_personal_stats_points_used') == '1') {
            $details->attach(do_template('BLOCK_SIDE_PERSONAL_STATS_LINE', array('_GUID' => '6241e58edfdsf735f3a2618fd7fff', 'KEY' => do_lang_tempcode('COUNT_POINTS_USED'), 'VALUE' => integer_format(points_used($member_id)))));
        }
        if (get_option('points_show_personal_stats_total_points') == '1') {
            $details->attach(do_template('BLOCK_SIDE_PERSONAL_STATS_LINE', array('_GUID' => '3e6183abf9054574c0cd292d25a4fe5c', 'KEY' => do_lang_tempcode((get_option('points_show_personal_stats_points_left') == '1') ? 'COUNT_POINTS_EVER' : 'COUNT_POINTS'), 'VALUE' => integer_format(total_points($member_id)))));
        }
        if (get_option('points_show_personal_stats_gift_points_left') == '1') {
            $details->attach(do_template('BLOCK_SIDE_PERSONAL_STATS_LINE', array('_GUID' => '6241e5ssd45ddsdsdsa2618fd7fff', 'KEY' => do_lang_tempcode('COUNT_GIFT_POINTS_LEFT'), 'VALUE' => integer_format(get_gift_points_to_give($member_id)))));
        }
        if (get_option('points_show_personal_stats_gift_points_used') == '1') {
            $details->attach(do_template('BLOCK_SIDE_PERSONAL_STATS_LINE', array('_GUID' => '6241eddsd4sdddssdsa2618fd7fff', 'KEY' => do_lang_tempcode('COUNT_GIFT_POINTS_USED'), 'VALUE' => integer_format(get_gift_points_used($member_id)))));
        }
    }

    // Links to usergroups
    if (get_option('show_personal_usergroup') == '1') {
        $group_id = $GLOBALS['FORUM_DRIVER']->mrow_group($GLOBALS['FORUM_DRIVER']->get_member_row($member_id));
        $usergroups = $GLOBALS['FORUM_DRIVER']->get_usergroup_list();
        if (array_key_exists($group_id, $usergroups)) {
            if (get_forum_type() == 'cns') {
                $group_url = build_url(array('page' => 'groups', 'type' => 'view', 'id' => $group_id), get_module_zone('groups'));
                $hyperlink = hyperlink($group_url, $usergroups[$group_id], false, true);
                $details->attach(do_template('BLOCK_SIDE_PERSONAL_STATS_LINE_COMPLEX', array('_GUID' => 'sas41eddsd4sdddssdsa2618fd7fff', 'KEY' => do_lang_tempcode('USERGROUP'), 'VALUE' => $hyperlink)));
            } else {
                $details->attach(do_template('BLOCK_SIDE_PERSONAL_STATS_LINE', array('_GUID' => '65180134fbc4cf7e227011463d466677', 'KEY' => do_lang_tempcode('USERGROUP'), 'VALUE' => $usergroups[$group_id])));
            }
        }
    }

    // Last visit time
    if (get_option('show_personal_last_visit') == '1') {
        $row = $GLOBALS['FORUM_DRIVER']->get_member_row($member_id);
        $last_visit = $GLOBALS['FORUM_DRIVER']->mrow_lastvisit($row);
        if (get_forum_type() == 'cns') {
            $last_visit = intval(cms_admirecookie('last_visit', strval($GLOBALS['FORUM_DRIVER']->mrow_lastvisit($row))));
        } else {
            $last_visit = $GLOBALS['FORUM_DRIVER']->mrow_lastvisit($row);
        }
        $_last_visit = get_timezoned_date_time($last_visit);
        $details->attach(do_template('BLOCK_SIDE_PERSONAL_STATS_LINE', array('_GUID' => 'sas41eddsdsdsdsdsa2618fd7fff', 'KEY' => do_lang_tempcode('LAST_HERE'), 'RAW_KEY' => strval($last_visit), 'VALUE' => $_last_visit)));
    }

    // Subscription expiry date
    if (addon_installed('ecommerce')) {
        if (get_option('manual_subscription_expiry_notice') != '') {
            $manual_subscription_expiry_notice = intval(get_option('manual_subscription_expiry_notice'));

            require_code('ecommerce_subscriptions');
            $subscriptions = find_member_subscriptions($member_id);
            foreach ($subscriptions as $subscription) {
                $expiry_time = $subscription['expiry_time'];
                if (($expiry_time !== null) && (($expiry_time - time()) < ($manual_subscription_expiry_notice * 24 * 60 * 60)) && ($expiry_time >= time())) {
                    require_lang('ecommerce');
                    $expiry_date = ($expiry_time === null) ? do_lang('INTERNAL_ERROR') : get_timezoned_date($expiry_time, false);
                    $details->attach(do_template('BLOCK_SIDE_PERSONAL_STATS_LINE', array(
                        '_GUID' => '2675d56aa278616aa9f00b051ca084fc',
                        'KEY' => do_lang_tempcode('SUBSCRIPTION_EXPIRY_MESSAGE', escape_html($subscription['item_name'])),
                        'VALUE' => do_lang_tempcode('SUBSCRIPTION_EXPIRY_DATE', escape_html($expiry_date)),
                    )));
                }
            }
        }
    }

    // Subscription links
    if ((get_forum_type() == 'cns') && (addon_installed('ecommerce')) && (get_option('show_personal_sub_links') == '1') && (!has_zone_access($member_id, 'adminzone')) && (has_actual_page_access($member_id, 'purchase'))) {
        require_lang('ecommerce');

        $usergroup_subs = $GLOBALS['FORUM_DB']->query_select('f_usergroup_subs', array('id', 's_title', 's_group_id', 's_price'), array('s_enabled' => 1));
        $in_one = false;
        $members_groups = $GLOBALS['FORUM_DRIVER']->get_members_groups($member_id);
        foreach ($usergroup_subs as $i => $sub) {
            $usergroup_subs[$i]['s_price'] = floatval($sub['s_price']);
            if (in_array($sub['s_group_id'], $members_groups)) {
                $in_one = true;
                break;
            }
        }
        if (!$in_one) {
            sort_maps_by($usergroup_subs, 's_price');
            foreach ($usergroup_subs as $sub) {
                $url = build_url(array('page' => 'purchase', 'type' => 'message', 'type_code' => 'USERGROUP' . strval($sub['id'])), get_module_zone('purchase'));
                $links_ecommerce->attach(do_template('BLOCK_SIDE_PERSONAL_STATS_LINK', array('_GUID' => '5c4a1f300b37722e587fe2f608f1ee3a', 'NAME' => do_lang_tempcode('UPGRADE_TO', escape_html(get_translated_text($sub['s_title'], $GLOBALS[(get_forum_type() == 'cns') ? 'FORUM_DB' : 'SITE_DB']))), 'URL' => $url)));
            }
        }
    }

    // Admin Zone link
    if (get_option('show_personal_adminzone_link') == '1') {
        if (has_zone_access($member_id, 'adminzone')) {
            $url = build_url(array('page' => '', 'keep_theme' => null), 'adminzone');
            $links->attach(do_template('BLOCK_SIDE_PERSONAL_STATS_LINK', array('_GUID' => 'ae243058f780f9528016f7854763a5fa', 'TARGET' => '_blank', 'TITLE' => do_lang_tempcode('LINK_NEW_WINDOW'), 'ACCESSKEY' => 'I', 'NAME' => do_lang_tempcode('ADMIN_ZONE'), 'URL' => $url)));
        } elseif (has_zone_access($member_id, 'cms')) {
            $url = build_url(array('page' => '', 'keep_theme' => null), 'cms');
            $links->attach(do_template('BLOCK_SIDE_PERSONAL_STATS_LINK', array('_GUID' => '3f63dad2645b6c39f68dcfebe7d7a0ab', 'ACCESSKEY' => 'I', 'TARGET' => '_blank', 'TITLE' => do_lang_tempcode('LINK_NEW_WINDOW'), 'NAME' => do_lang_tempcode('CMS'), 'URL' => $url)));
        }
    }

    // Conceded mode link
    if (($GLOBALS['SESSION_CONFIRMED_CACHE']) && (get_option('show_conceded_mode_link') == '1')) {
        $url = build_url(array('page' => 'login', 'type' => 'concede', 'redirect' => protect_url_parameter((get_page_name() == 'login') ? null : SELF_REDIRECT_RIP)), get_module_zone('login'));
        $links->attach(do_template('BLOCK_SIDE_PERSONAL_STATS_LINK_2', array('_GUID' => '81fa81cfd3130e42996bf72b0e03d8aa', 'POST' => true, 'NAME' => do_lang_tempcode('CONCEDED_MODE'), 'DESCRIPTION' => do_lang_tempcode('DESCRIPTION_CONCEDED_MODE'), 'URL' => $url)));
    }

    // Becomes-invisible link
    if (get_option('is_on_invisibility') == '1') {
        if ((array_key_exists(get_session_id(), $GLOBALS['SESSION_CACHE'])) && ($GLOBALS['SESSION_CACHE'][get_session_id()]['session_invisible'] == 0)) {
            $visible = (array_key_exists(get_session_id(), $GLOBALS['SESSION_CACHE'])) && ($GLOBALS['SESSION_CACHE'][get_session_id()]['session_invisible'] == 0);
            $url = build_url(array('page' => 'login', 'type' => 'invisible', 'redirect' => protect_url_parameter((get_page_name() == 'login') ? null : SELF_REDIRECT_RIP)), get_module_zone('login'));
            $links->attach(do_template('BLOCK_SIDE_PERSONAL_STATS_LINK_2', array('_GUID' => '2af618fe39444861c21cf0caec216227', 'NAME' => do_lang_tempcode($visible ? 'INVISIBLE' : 'BE_VISIBLE'), 'DESCRIPTION' => '', 'URL' => $url)));
        }
    }

    // Logout link
    $url = build_url(array('page' => 'login', 'type' => 'logout'), get_module_zone('login'));
    if (!is_httpauth_login()) {
        $links->attach(do_template('BLOCK_SIDE_PERSONAL_STATS_LOGOUT', array('_GUID' => 'd1caacba272a7ee3bf5b2a758e4e54ee', 'NAME' => do_lang_tempcode('LOGOUT'), 'URL' => $url)));
    }

    if (get_forum_type() == 'cns') {
        require_code('cns_notifications');
        list(, $num_unread_pps) = generate_notifications($member_id);
    } else {
        $num_unread_pps = 0;
    }

    $cache[$member_id] = array($links, $links_ecommerce, $details, $num_unread_pps);
    return $cache[$member_id];
}

/**
 * Use the url_title_cache table (a bit of a hack but saved changed the DB structure) to see if a check-op was performed has been performed within the last 30 days.
 *
 * @param  ID_TEXT $id_code Special check code (often a URL but does not need to be)
 * @return boolean Whether the check has happened recently
 */
function handle_has_checked_recently($id_code)
{
    $last_check_test = $GLOBALS['SITE_DB']->query_select_value_if_there('url_title_cache', 't_title', array('t_url' => substr('!' . $id_code, 0, 255)));
    if (($last_check_test === null) || (substr($last_check_test, 0, 1) != '!') || (intval(substr($last_check_test, 1)) + 60 * 60 * 24 * 30 < time())) { // only re-checks every 30 days
        // Record when it was last tested (i.e. it will be tested now, so put this into the DB)
        $GLOBALS['SITE_DB']->query_delete('url_title_cache', array('t_url' => substr('!' . $id_code, 0, 255)), '', 1); // To make sure it can insert below
        $GLOBALS['SITE_DB']->query_insert('url_title_cache', array('t_meta_title' => '', 't_keywords' => '', 't_description' => '', 't_image_url' => '', 't_title' => '!' . strval(time()), 't_mime_type' => '', 't_json_discovery' => '', 't_xml_discovery' => '', 't_url' => substr('!' . $id_code, 0, 255)), false, true); // To stop weird race-like conditions

        return false; // Make sure to test it after getting this result, else the above assumption wouldn't be valid
    }

    return true;
}

/**
 * Convert a string to an array, with utf-8 awareness where possible/required.
 *
 * @param  string $str Input
 * @param  boolean $force Whether to force unicode as on
 * @return array Output
 */
function cms_mb_str_split($str, $force = false)
{
    $len = cms_mb_strlen($str, $force);
    $array = array();
    for ($i = 0; $i < $len; $i++) {
        $array[] = cms_mb_substr($str, $i, 1, $force);
    }
    return $array;
}

/**
 * Split a string into smaller chunks, with utf-8 awareness where possible/required. Can be used to split a string into smaller chunks which is useful for e.g. converting base64_encode output to match RFC 2045 semantics. It inserts end (defaults to "\r\n") every chunklen characters.
 *
 * @param  string $str The input string
 * @param  integer $len The maximum chunking length
 * @param  string $glue Split character
 * @param  boolean $force Whether to force unicode as on
 * @return string The chunked version of the input string
 */
function cms_mb_chunk_split($str, $len = 76, $glue = "\r\n", $force = false)
{
    if ($str == '') {
        return '';
    }
    $array = cms_mb_str_split($str, $force);
    $n = -1;
    $new = '';
    foreach ($array as $char) {
        $n++;
        if ($n < $len) {
            $new .= $char;
        } elseif ($n == $len) {
            $new .= $glue . $char;
            $n = 0;
        }
    }
    return $new;
}

/**
 * Prevent double submission, by reference to recent matching admin log entries by the current member.
 *
 * @param  ID_TEXT $type The type of activity just carried out (a language string ID)
 * @param  ?SHORT_TEXT $a The most important parameter of the activity (e.g. ID) (null: none / cannot match against)
 * @param  ?SHORT_TEXT $b A secondary (perhaps, human readable) parameter of the activity (e.g. caption) (null: none / cannot match against)
 */
function prevent_double_submit($type, $a = null, $b = null)
{
    if (get_mass_import_mode()) {
        return;
    }

    if ($GLOBALS['IN_MINIKERNEL_VERSION']) {
        return;
    }

    if (post_param_integer('confirm_double_post', 0) == 1) {
        return;
    }

    if (strpos($_SERVER['SCRIPT_NAME'], '_tests') !== false) {
        return;
    }

    $where = array(
        'the_type' => $type,
        'member_id' => get_member(),
    );
    if ($a !== null) {
        if ($a == '') {
            return; // Cannot work with this
        }
        $where += array(
            'param_a' => cms_mb_substr($a, 0, 80),
        );
    }
    if ($b !== null) {
        if ($b == '') {
            return; // Cannot work with this
        }
        $where += array(
            'param_b' => cms_mb_substr($b, 0, 80),
        );
    }
    $time_window = 60 * 5; // 5 minutes seems reasonable
    $test = $GLOBALS['SITE_DB']->query_select_value_if_there('actionlogs', 'date_and_time', $where, ' AND date_and_time>' . strval(time() - $time_window));
    if ($test !== null) {
        $title = get_screen_title('ERROR_OCCURRED');
        require_code('templates_confirm_screen');
        $_time_window = display_time_period($time_window);
        $_time_remaining = display_time_period($time_window - (time() - $test));
        $_time_since = display_time_period(time() - $test);
        $preview = do_lang_tempcode('DOUBLE_SUBMISSION_PREVENTED', $_time_window, $_time_remaining, array($_time_since));
        $output = confirm_screen($title, $preview, get_self_url(), null, array('confirm_double_post' => 1));
        $echo = globalise($output, null, '', true);
        $echo->handle_symbol_preprocessing();
        $echo->evaluate_echo();
        exit();
    }
}

/**
 * Log an action.
 *
 * @param  ID_TEXT $type The type of activity just carried out (a language string ID)
 * @param  ?SHORT_TEXT $a The most important parameter of the activity (e.g. ID) (null: none)
 * @param  ?SHORT_TEXT $b A secondary (perhaps, human readable) parameter of the activity (e.g. caption) (null: none)
 * @return ?AUTO_LINK Log ID (null: did not save a log)
 * @ignore
 */
function _log_it($type, $a = null, $b = null)
{
    if (!function_exists('get_member')) {
        return null; // If this is during installation
    }

    // Need to update copyright date?
    if ($GLOBALS['FORUM_DRIVER']->is_staff(get_member()) && $type != 'CONFIGURATION') {
        $matches = array();
        $old_copyright = get_option('copyright');
        if (preg_match('#^(.*\$CURRENT_YEAR=)(\d+)(.*)$#', $old_copyright, $matches) != 0) {
            $new_copyright = $matches[1] . date('Y') . $matches[3];
            if ($old_copyright != $new_copyright) {
                require_code('config2');
                set_option('copyright', $new_copyright);
                require_code('caches3');
                erase_cached_templates(false, array('GLOBAL_HTML_WRAP'));
            }
        }
    }

    // Cache clearing
    static $logged = 0;
    $logged++;
    if ($logged == 1) {
        delete_cache_entry('side_tag_cloud');
        delete_cache_entry('main_staff_actions');
        delete_cache_entry('main_staff_checklist');
        delete_cache_entry('main_awards');
        delete_cache_entry('main_multi_content');
        delete_cache_entry('menu'); // Due to the content counts in the CMS/Admin Zones, and Sitemap menus
    }

    // No more logging if site closed (possibly)
    if ((get_option('site_closed') == '1') && (get_option('stats_when_closed') == '0')) {
        return null;
    }

    // Run hooks, if any exist
    $hooks = find_all_hook_obs('systems', 'upon_action_logging', 'upon_action_logging');
    foreach ($hooks as $ob) {
        $ob->run($type, $a, $b);
    }

    // Add to log
    $log_id = null;
    global $ADMIN_LOGGING_ON;
    if ($ADMIN_LOGGING_ON) {
        $ip = get_ip_address();
        $log_id = $GLOBALS['SITE_DB']->query_insert('actionlogs', array(
            'the_type' => $type,
            'param_a' => ($a === null) ? '' : cms_mb_substr($a, 0, 80),
            'param_b' => ($b === null) ? '' : cms_mb_substr($b, 0, 80),
            'date_and_time' => time(),
            'member_id' => get_member(),
            'ip' => $ip,
        ), true);
    }

    // Tidy up auto-save
    require_code('autosave');
    clear_cms_autosave();

    // Notification
    if ((!get_mass_import_mode()) && ($ADMIN_LOGGING_ON)) {
        if ($logged < 10) { // Be extra sure it's not some kind of import, causing spam
            if (addon_installed('actionlog')) {
                if (do_lang($type, null, null, null, null, false) === null) {
                    require_all_lang();
                }
                if ($a === null) {
                    $a = do_lang('NA');
                }
                if ($a === null) {
                    $a = do_lang('NA');
                }
                require_code('notifications');
                require_lang('actionlog');
                $subject = do_lang('ACTIONLOG_NOTIFICATION_MAIL_SUBJECT', get_site_name(), do_lang($type), array($a, $b));
                $mail = do_notification_lang('ACTIONLOG_NOTIFICATION_MAIL', comcode_escape(get_site_name()), comcode_escape(do_lang($type)), array(($a === null) ? '' : comcode_escape($a), ($b === null) ? '' : comcode_escape($b)));
                dispatch_notification('actionlog', $type, $subject, $mail, null, get_member(), array('use_real_from' => true));
            }
        }
    }

    return $log_id;
}

/**
 * Generate a GUID.
 *
 * @return ID_TEXT A GUID
 */
function generate_guid()
{
    // Calculate hash value
    $hash = md5(uniqid('', true));

    // Based on a comment in the PHP manual
    return sprintf('%08s-%04s-%04x-%04x-%12s',
        // 32 bits for "time_low"
        substr($hash, 0, 8),

        // 16 bits for "time_mid"
        substr($hash, 8, 4),

        // 16 bits for "time_hi_and_version",
        // four most significant bits holds version number 5
        (hexdec(substr($hash, 12, 4)) & 0x0fff) | 0x5000,

        // 16 bits, 8 bits for "clk_seq_hi_res",
        // 8 bits for "clk_seq_low",
        // two most significant bits holds zero and one for variant DCE1.1
        (hexdec(substr($hash, 16, 4)) & 0x3fff) | 0x8000,

        // 48 bits for "node"
        substr($hash, 20, 12)
    );
}

/**
 * Find a percentage performance figure relative to a Late 2014 iMac (at the time of writing this is the lead developers main machine).
 *
 * @return float Performance figure
 */
function find_normative_performance()
{
    static $percentage = null;
    if ($percentage !== null) {
        return $percentage;
    }

    $t = microtime(true);
    for ($i = 0; $i < 10000; $i++) {
        md5(uniqid('', false)); // Some fairly heavy crunching
    }
    $percentage = 100.0 * (0.055 / (microtime(true) - $t));
    return $percentage;
}

/**
 * Get time when the site was started.
 *
 * @return TIME The time
 */
function get_site_start_time()
{
    $time_a = $GLOBALS['SITE_DB']->query_select_value_if_there('actionlogs', 'MIN(date_and_time)');
    if ($time_a === null) {
        $time_a = time();
    }
    $time_b = filectime(get_file_base() . '/_config.php');
    return min($time_a, $time_b);
}
