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
 * @package    pointstore
 */

/**
 * Module page class.
 */
class Module_pointstore
{
    /**
     * Find details of the module.
     *
     * @return ?array Map of module info (null: module is disabled).
     */
    public function info()
    {
        $info = array();
        $info['author'] = 'Allen Ellis';
        $info['organisation'] = 'ocProducts';
        $info['hacked_by'] = null;
        $info['hack_version'] = null;
        $info['version'] = 6;
        $info['locked'] = false;
        $info['update_require_upgrade'] = true;
        return $info;
    }

    /**
     * Uninstall the module.
     */
    public function uninstall()
    {
        $GLOBALS['SITE_DB']->drop_table_if_exists('prices');
        $GLOBALS['SITE_DB']->drop_table_if_exists('sales');
        $GLOBALS['SITE_DB']->drop_table_if_exists('pstore_customs');
        $GLOBALS['SITE_DB']->drop_table_if_exists('pstore_permissions');
    }

    /**
     * Install the module.
     *
     * @param  ?integer $upgrade_from What version we're upgrading from (null: new install)
     * @param  ?integer $upgrade_from_hack What hack version we're upgrading from (null: new-install/not-upgrading-from-a-hacked-version)
     */
    public function install($upgrade_from = null, $upgrade_from_hack = null)
    {
        if (is_null($upgrade_from)) {
            $GLOBALS['SITE_DB']->create_table('prices', array(
                'name' => '*ID_TEXT',
                'price' => 'INTEGER'
            ));

            $GLOBALS['SITE_DB']->create_table('sales', array(
                'id' => '*AUTO',
                'date_and_time' => 'TIME',
                'memberid' => 'MEMBER',
                'purchasetype' => 'ID_TEXT',
                'details' => 'SHORT_TEXT',
                'details2' => 'SHORT_TEXT'
            ));

            // Custom
            $GLOBALS['SITE_DB']->create_table('pstore_customs', array(
                'id' => '*AUTO',
                'c_title' => 'SHORT_TRANS',
                'c_description' => 'LONG_TRANS__COMCODE',
                'c_mail_subject' => 'SHORT_TRANS',
                'c_mail_body' => 'LONG_TRANS',
                'c_enabled' => 'BINARY',
                'c_cost' => 'INTEGER',
                'c_one_per_member' => 'BINARY',
            ));
            // Permissions
            $GLOBALS['SITE_DB']->create_table('pstore_permissions', array(
                'id' => '*AUTO',
                'p_title' => 'SHORT_TRANS',
                'p_description' => 'LONG_TRANS__COMCODE',
                'p_mail_subject' => 'SHORT_TRANS',
                'p_mail_body' => 'LONG_TRANS',
                'p_enabled' => 'BINARY',
                'p_cost' => 'INTEGER',
                'p_hours' => '?INTEGER',
                'p_type' => 'ID_TEXT', // member_privileges,member_category_access,member_page_access,member_zone_access
                'p_privilege' => 'ID_TEXT', // privilege only
                'p_zone' => 'ID_TEXT', // zone and page only
                'p_page' => 'ID_TEXT', // page and ?privilege only
                'p_module' => 'ID_TEXT', // category and ?privilege only
                'p_category' => 'ID_TEXT', // category and ?privilege only
            ));
        }

        if (($upgrade_from < 5) && (!is_null($upgrade_from))) {
            $GLOBALS['SITE_DB']->add_table_field('pstore_permissions', 'p_mail_subject', 'SHORT_TRANS');
            $GLOBALS['SITE_DB']->add_table_field('pstore_permissions', 'p_mail_body', 'LONG_TRANS');

            $GLOBALS['SITE_DB']->add_table_field('pstore_customs', 'c_mail_subject', 'SHORT_TRANS');
            $GLOBALS['SITE_DB']->add_table_field('pstore_customs', 'c_mail_body', 'LONG_TRANS');
        }

        if (($upgrade_from < 6) && (!is_null($upgrade_from))) {
            rename_config_option('text', 'community_billboard');
            rename_config_option('is_on_flagrant_buy', 'is_on_community_billboard_buy');

            $GLOBALS['SITE_DB']->alter_table_field('pstore_permissions', 'p_hours', '?INTEGER');
        }
    }

    /**
     * Find entry-points available within this module.
     *
     * @param  boolean $check_perms Whether to check permissions.
     * @param  ?MEMBER $member_id The member to check permissions as (null: current user).
     * @param  boolean $support_crosslinks Whether to allow cross links to other modules (identifiable via a full-page-link rather than a screen-name).
     * @param  boolean $be_deferential Whether to avoid any entry-point (or even return null to disable the page in the Sitemap) if we know another module, or page_group, is going to link to that entry-point. Note that "!" and "browse" entry points are automatically merged with container page nodes (likely called by page-groupings) as appropriate.
     * @return ?array A map of entry points (screen-name=>language-code/string or screen-name=>[language-code/string, icon-theme-image]) (null: disabled).
     */
    public function get_entry_points($check_perms = true, $member_id = null, $support_crosslinks = true, $be_deferential = false)
    {
        if (get_forum_type() == 'none') {
            return array();
        }

        if (!$check_perms || !is_guest($member_id)) {
            return array(
                '!' => array('POINTSTORE', 'menu/social/pointstore'),
            );
        }
        return array();
    }

    public $title;

    /**
     * Module pre-run function. Allows us to know metadata for <head> before we start streaming output.
     *
     * @return ?Tempcode Tempcode indicating some kind of exceptional output (null: none).
     */
    public function pre_run()
    {
        $type = get_param_string('type', 'browse');

        require_lang('pointstore');

        breadcrumb_set_parents(array(array('_SELF:_SELF:browse', do_lang_tempcode('POINTSTORE'))));

        $this->title = get_screen_title('POINTSTORE');

        $GLOBALS['OUTPUT_STREAMING'] = false;

        return null;
    }

    /**
     * Execute the module.
     *
     * @return Tempcode The result of execution.
     */
    public function run()
    {
        require_code('pointstore');
        require_lang('points');
        require_code('points');
        require_css('points');

        $type = get_param_string('type', 'browse');
        $hook = get_param_string('id', '');

        // Not logged in
        if (is_guest()) {
            access_denied('NOT_AS_GUEST');
        }

        if (get_forum_type() == 'none') {
            warn_exit(do_lang_tempcode('NO_FORUM_INSTALLED'));
        }

        if ($hook != '') {
            require_code('hooks/modules/pointstore/' . filter_naughty_harsh($hook), true);
            $object = object_factory('Hook_pointstore_' . filter_naughty_harsh($hook));
            $object->init();
            if (method_exists($object, $type)) {
                require_code('form_templates');

                url_default_parameters__enable();
                $ret = call_user_func(array($object, $type));
                url_default_parameters__disable();
                return $ret;
            }
        }

        if ($type == 'browse') {
            return $this->interface_pointstore();
        }
        return new Tempcode();
    }

    /**
     * The UI to choose a section of the Point Store.
     *
     * @return Tempcode The UI
     */
    public function interface_pointstore()
    {
        $points_left = available_points(get_member());

        $items = new Tempcode();

        $_hooks = find_all_hook_obs('modules', 'pointstore', 'Hook_pointstore_');
        foreach ($_hooks as $object) {
            $object->init();
            $tpls = $object->info();
            foreach ($tpls as $tpl) {
                $items->attach($tpl);
            }
        }

        // pop3/imap work from a single box so are handled here rather than in the hooks...

        if (get_option('is_on_forw_buy') == '1') {
            $forwarding_url = build_url(array('page' => '_SELF', 'type' => 'newforwarding', 'id' => 'forwarding'), '_SELF');

            if ($GLOBALS['SITE_DB']->query_value_if_there('SELECT COUNT(*) FROM ' . get_table_prefix() . 'prices WHERE name LIKE \'' . db_encode_like('forw_%') . '\'') > 0) {
                $_pointstore_mail_forwarding_link = $forwarding_url;
            } else {
                $_pointstore_mail_forwarding_link = null;
            }
            $pointstore_mail_forwarding_link = do_template('POINTSTORE_MFORWARDING_LINK', array('_GUID' => 'e93666809dc3e47e3660245711f545ee', 'FORWARDING_URL' => $_pointstore_mail_forwarding_link));
        } else {
            $pointstore_mail_forwarding_link = new Tempcode();
        }
        if (get_option('is_on_pop3_buy') == '1') {
            $pop3_url = build_url(array('page' => '_SELF', 'type' => 'pop3info', 'id' => 'pop3'), '_SELF');

            if ($GLOBALS['SITE_DB']->query_value_if_there('SELECT COUNT(*) FROM ' . get_table_prefix() . 'prices WHERE name LIKE \'' . db_encode_like('pop3_%') . '\'') > 0) {
                $_pointstore_mail_pop3_link = $pop3_url;
            } else {
                $_pointstore_mail_pop3_link = null;
            }
            $pointstore_mail_pop3_link = do_template('POINTSTORE_MPOP3_LINK', array('_GUID' => '42925a17262704450e451ad8502bce0d', 'POP3_URL' => $_pointstore_mail_pop3_link));
        } else {
            $pointstore_mail_pop3_link = new Tempcode();
        }

        if ((!$pointstore_mail_pop3_link->is_empty()) || (!$pointstore_mail_pop3_link->is_empty())) {
            $items->attach(do_template('POINTSTORE_MAIL', array('_GUID' => '4a024f39a4065197b2268ecd2923b8d6', 'POINTSTORE_MAIL_POP3_LINK' => $pointstore_mail_pop3_link, 'POINTSTORE_MAIL_FORWARDING_LINK' => $pointstore_mail_forwarding_link), null, false, null, '.txt', 'text'));
        }

        // --

        $username = $GLOBALS['FORUM_DRIVER']->get_username(get_member());
        return do_template('POINTSTORE_SCREEN', array('_GUID' => '1b66923dd1a3da6afb934a07909b8aa7', 'TITLE' => $this->title, 'ITEMS' => $items, 'POINTS_LEFT' => integer_format($points_left), 'USERNAME' => $username));
    }
}
