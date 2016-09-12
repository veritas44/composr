<?php /*

 Composr
 Copyright (c) ocProducts, 2004-2016

 See text/EN/licence.txt for full licencing information.


 NOTE TO PROGRAMMERS:
   Do not edit this file. If you need to make changes, save your changed file to the appropriate *_custom folder
   **** If you ignore this advice, then your website upgrades (e.g. for bug fixes) will likely kill your changes ****

*/

/**
 * @license    http://opensource.org/licenses/cpal_1.0 Common Public Attribution License
 * @copyright  ocProducts Ltd
 * @package    core_forum_drivers
 */

require_code('forum/shared/vb');

/**
 * Forum driver class.
 *
 * @package    core_forum_drivers
 */
class Forum_driver_vb22 extends forum_driver_vb_shared
{
    /**
     * Find if login cookie is md5-hashed.
     *
     * @return boolean Whether the login cookie is md5-hashed
     */
    public function is_hashed()
    {
        return true;
    }

    /**
     * Get an array of attributes to take in from the installer. Almost all forums require a table prefix, which the requirement there-of is defined through this function.
     * The attributes have 4 values in an array
     * - name, the name of the attribute for _config.php
     * - default, the default value (perhaps obtained through autodetection from forum config)
     * - description, a textual description of the attributes
     * - title, a textual title of the attribute
     *
     * @return array The attributes for the forum
     */
    public function install_specifics()
    {
        global $PROBED_FORUM_CONFIG;
        $a = array();
        $a['name'] = 'vb_table_prefix';
        $a['default'] = array_key_exists('prefix', $PROBED_FORUM_CONFIG) ? $PROBED_FORUM_CONFIG['prefix'] : '';
        $a['description'] = do_lang('MOST_DEFAULT');
        $a['title'] = 'VB ' . do_lang('TABLE_PREFIX');
        return array($a);
    }

    /**
     * Searches for forum auto-config at this path.
     *
     * @param  PATH $path The path in which to search
     * @return boolean Whether the forum auto-config could be found
     */
    public function install_test_load_from($path)
    {
        global $PROBED_FORUM_CONFIG;
        if (@file_exists($path . '/admin/config.php')) {
            $dbname = '';
            $dbusername = '';
            $dbpassword = '';
            @include($path . '/admin/config.php');
            $PROBED_FORUM_CONFIG['sql_database'] = $dbname;
            $PROBED_FORUM_CONFIG['sql_user'] = $dbusername;
            $PROBED_FORUM_CONFIG['sql_pass'] = $dbpassword;
            $PROBED_FORUM_CONFIG['cookie_member_id'] = 'bbuserid';
            $PROBED_FORUM_CONFIG['cookie_member_hash'] = 'bbpassword';
            $PROBED_FORUM_CONFIG['board_url'] = '';
            return true;
        }
        return false;
    }

    /**
     * Get an array of paths to search for config at.
     *
     * @return array The paths in which to search for the forum config
     */
    public function install_get_path_search_list()
    {
        return array(
            0 => 'forums',
            1 => 'forum',
            2 => 'boards',
            3 => 'board',
            4 => 'vb',
            5 => 'vb2',
            6 => 'upload',
            7 => 'uploads',
            8 => 'vbulletin',
            10 => '../forums',
            11 => '../forum',
            12 => '../boards',
            13 => '../board',
            14 => '../vb',
            15 => '../vb2',
            16 => '../upload',
            17 => '../uploads',
            18 => '../vbulletin');
    }

    /**
     * From a member row, get the member's last visit date.
     *
     * @param  array $r The profile-row
     * @return TIME The last visit date
     */
    public function mrow_lastvisit($r)
    {
        return $r['lastvisit'];
    }

    /**
     * Find out if the given member ID is banned.
     *
     * @param  MEMBER $member The member ID
     * @return boolean Whether the member is banned
     */
    public function is_banned($member)
    {
        // Are they banned
        $group = $this->get_member_row_field($member, 'usergroupid');
        $notbanned = $this->connection->query_select_value_if_there('usergroup', 'canview', array('usergroupid' => $group));
        if ($notbanned == 0) {
            return true;
        }

        return false;
    }

    /**
     * Find if the specified member ID is marked as staff or not.
     *
     * @param  MEMBER $member The member ID
     * @return boolean Whether the member is staff
     */
    protected function _is_staff($member)
    {
        $usergroup = $this->get_member_row_field($member, 'usergroupid');
        if ((!is_null($usergroup)) && ($this->connection->query_select_value_if_there('usergroup', 'ismoderator', array('usergroupid' => $usergroup)) == 1)) {
            return true;
        }
        return false;
    }

    /**
     * Find if the specified member ID is marked as a super admin or not.
     *
     * @param  MEMBER $member The member ID
     * @return boolean Whether the member is a super admin
     */
    protected function _is_super_admin($member)
    {
        $usergroup = $this->get_member_row_field($member, 'usergroupid');
        if ((!is_null($usergroup)) && ($this->connection->query_select_value_if_there('usergroup', 'cancontrolpanel', array('usergroupid' => $usergroup)) == 1)) {
            return true;
        }
        return false;
    }

    /**
     * Get the IDs of the admin usergroups.
     *
     * @return array The admin usergroup IDs
     */
    protected function _get_super_admin_groups()
    {
        return collapse_1d_complexity('usergroupid', $this->connection->query_select('usergroup', array('usergroupid'), array('cancontrolpanel' => 1)));
    }

    /**
     * Get the IDs of the moderator usergroups.
     * It should not be assumed that a member only has one usergroup - this depends upon the forum the driver works for. It also does not take the staff site filter into account.
     *
     * @return array The moderator usergroup IDs
     */
    protected function _get_moderator_groups()
    {
        return collapse_1d_complexity('usergroupid', $this->connection->query_select('usergroup', array('usergroupid'), array('cancontrolpanel' => 0, 'ismoderator' => 1)));
    }

    /**
     * Get the forum usergroup list.
     *
     * @return array The usergroup list
     */
    protected function _get_usergroup_list()
    {
        return collapse_2d_complexity('usergroupid', 'title', $this->connection->query_select('usergroup', array('usergroupid', 'title')));
    }

    /**
     * Get the forum usergroup relating to the specified member ID.
     *
     * @param  MEMBER $member The member ID
     * @return array The array of forum usergroups
     */
    protected function _get_members_groups($member)
    {
        if ($member == $this->get_guest_id()) {
            return array(1);
        }

        $group = $this->get_member_row_field($member, 'usergroupid');
        return array($group);
    }

    /**
     * Find if the given member ID and password is valid. If username is null, then the member ID is used instead.
     * All authorisation, cookies, and form-logins, are passed through this function.
     * Some forums do cookie logins differently, so a Boolean is passed in to indicate whether it is a cookie login.
     *
     * @param  ?SHORT_TEXT $username The member username (null: don't use this in the authentication - but look it up using the ID if needed)
     * @param  MEMBER $userid The member ID
     * @param  SHORT_TEXT $password_hashed The md5-hashed password
     * @param  string $password_raw The raw password
     * @param  boolean $cookie_login Whether this is a cookie login
     * @return array A map of 'id' and 'error'. If 'id' is null, an error occurred and 'error' is set
     */
    public function forum_authorise_login($username, $userid, $password_hashed, $password_raw, $cookie_login = false)
    {
        $out = array();
        $out['id'] = null;

        if (is_null($userid)) {
            $rows = $this->connection->query_select('user', array('*'), array('username' => $username), '', 1);
            if (array_key_exists(0, $rows)) {
                $this->MEMBER_ROWS_CACHED[$rows[0]['userid']] = $rows[0];
            }
        } else {
            $rows = array();
            $rows[0] = $this->get_member_row($userid);
        }

        if (!array_key_exists(0, $rows) || $rows[0] === null) { // All hands to lifeboats
            $out['error'] = do_lang_tempcode((get_option('login_error_secrecy') == '1') ? 'MEMBER_INVALID_LOGIN' : '_MEMBER_NO_EXIST', $username);
            return $out;
        }
        $row = $rows[0];
        if ($this->is_banned($row['userid'])) { // All hands to the guns
            $out['error'] = do_lang_tempcode('YOU_ARE_BANNED');
            return $out;
        }
        if ($row['password'] != $password_hashed) {
            $out['error'] = do_lang_tempcode((get_option('login_error_secrecy') == '1') ? 'MEMBER_INVALID_LOGIN' : 'MEMBER_BAD_PASSWORD');
            return $out;
        }

        require_code('users_active_actions');
        cms_eatcookie('sessionhash');

        $out['id'] = $row['userid'];
        return $out;
    }
}
