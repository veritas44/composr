<?php /*

 Composr
 Copyright (c) ocProducts, 2004-2017

 See text/EN/licence.txt for full licencing information.


 NOTE TO PROGRAMMERS:
   Do not edit this file. If you need to make changes, save your changed file to the appropriate *_custom folder
   **** If you ignore this advice, then your website upgrades (e.g. for bug fixes) will likely kill your changes ****

*/

/**
 * @license    http://opensource.org/licenses/cpal_1.0 Common Public Attribution License
 * @copyright  ocProducts Ltd
 * @package    page_management
 */

/**
 * Module page class.
 */
class Module_admin_sitemap
{
    /**
     * Find details of the module.
     *
     * @return ?array Map of module info (null: module is disabled)
     */
    public function info()
    {
        $info = array();
        $info['author'] = 'Chris Graham';
        $info['organisation'] = 'ocProducts';
        $info['hacked_by'] = null;
        $info['hack_version'] = null;
        $info['version'] = 4;
        $info['locked'] = false;
        return $info;
    }

    /**
     * Find entry-points available within this module.
     *
     * @param  boolean $check_perms Whether to check permissions
     * @param  ?MEMBER $member_id The member to check permissions as (null: current user)
     * @param  boolean $support_crosslinks Whether to allow cross links to other modules (identifiable via a full-page-link rather than a screen-name)
     * @param  boolean $be_deferential Whether to avoid any entry-point (or even return null to disable the page in the Sitemap) if we know another module, or page_group, is going to link to that entry-point. Note that "!" and "browse" entry points are automatically merged with container page nodes (likely called by page-groupings) as appropriate.
     * @return ?array A map of entry points (screen-name=>language-code/string or screen-name=>[language-code/string, icon-theme-image]) (null: disabled)
     */
    public function get_entry_points($check_perms = true, $member_id = null, $support_crosslinks = true, $be_deferential = false)
    {
        return array(
            'browse' => array('SITEMAP_EDITOR', 'menu/adminzone/structure/sitemap/sitemap_editor'),
        );
    }

    public $title;

    /**
     * Module pre-run function. Allows us to know metadata for <head> before we start streaming output.
     *
     * @return ?Tempcode Tempcode indicating some kind of exceptional output (null: none)
     */
    public function pre_run()
    {
        $type = get_param_string('type', 'browse');

        require_lang('zones');

        if ($type == 'browse') {
            breadcrumb_set_parents(array(array('_SELF:_SELF:browse', do_lang_tempcode('PAGES'))));

            $this->title = get_screen_title('SITEMAP_EDITOR');
        }

        if ($type == 'delete') {
            breadcrumb_set_self(do_lang_tempcode('CONFIRM'));
            breadcrumb_set_parents(array(array('_SELF:_SELF:browse', do_lang_tempcode('PAGES')), array('_SELF:_SELF:delete', do_lang_tempcode('DELETE_PAGES'))));

            $this->title = get_screen_title('DELETE_PAGES');
        }

        if ($type == '_delete') {
            breadcrumb_set_self(do_lang_tempcode('DONE'));
            breadcrumb_set_parents(array(array('_SELF:_SELF:browse', do_lang_tempcode('PAGES')), array('_SELF:_SELF:delete', do_lang_tempcode('DELETE_PAGES'))));

            $this->title = get_screen_title('DELETE_PAGES');
        }

        if ($type == 'move') {
            breadcrumb_set_parents(array(array('_SELF:_SELF:browse', do_lang_tempcode('PAGES'))));

            $this->title = get_screen_title('MOVE_PAGES');
        }

        if ($type == '_move') {
            breadcrumb_set_self(do_lang_tempcode('DONE'));
            breadcrumb_set_parents(array(array('_SELF:_SELF:browse', do_lang_tempcode('PAGES')), array('_SELF:_SELF:move', do_lang_tempcode('MOVE_PAGES'))));

            $this->title = get_screen_title('MOVE_PAGES');
        }

        return null;
    }

    /**
     * Execute the module.
     *
     * @return Tempcode The result of execution
     */
    public function run()
    {
        require_code('zones2');
        require_code('zones3');

        $type = get_param_string('type', 'browse');

        if ($type == 'browse') {
            return $this->sitemap();
        }

        if ($type == 'delete') {
            return $this->delete();
        }
        if ($type == '_delete') {
            return $this->_delete();
        }

        if ($type == 'move') {
            return $this->move();
        }
        if ($type == '_move') {
            return $this->_move();
        }

        return new Tempcode();
    }

    /**
     * The UI for the sitemap editor.
     *
     * @return Tempcode The UI
     */
    public function sitemap()
    {
        require_css('sitemap_editor');

        if (count($GLOBALS['SITE_DB']->query_select_value('zones', 'COUNT(*)')) >= 300) {
            attach_message(do_lang_tempcode('TOO_MUCH_CHOOSE__ALPHABETICAL', escape_html(integer_format(50))), 'warn');
        }

        require_javascript('tree_list');

        return do_template('SITEMAP_EDITOR_SCREEN', array('_GUID' => '2d42cb71e03d31c855a6b6467d2082d2', 'TITLE' => $this->title));
    }

    /**
     * The UI to confirm deletion of a page.
     *
     * @return Tempcode The UI
     */
    public function delete()
    {
        $hidden = new Tempcode();

        $file = new Tempcode();
        $zone = either_param_string('zone');
        $pages = array();
        foreach ($_REQUEST as $key => $val) {
            if ((substr($key, 0, 6) == 'page__') && ($val === '1')) {
                $page = substr($key, 6);
                $page_details = _request_page($page, $zone, null, null, true);
                if ($page_details === false) {
                    warn_exit(do_lang_tempcode('MISSING_RESOURCE', do_lang_tempcode('PAGE')));
                }
                $pages[$page] = strtolower($page_details[0]);
            }
        }
        foreach ($pages as $page => $type) {
            if (is_integer($page)) {
                $page = strval($page);
            }

            if (either_param_integer('page__' . $page, 0) == 1) {
                $hidden->attach(form_input_hidden('page__' . $page, '1'));

                if (!$file->is_empty()) {
                    $file->attach(do_lang_tempcode('LIST_SEP'));
                }
                $file->attach(do_lang_tempcode('ZONE_WRITE', escape_html($zone), escape_html($page)));

                if ((get_file_base() != get_custom_file_base()) && ($type != 'comcode_custom')) {
                    warn_exit(do_lang_tempcode('SHARED_INSTALL_PROHIBIT'));
                }
            }
        }

        $url = build_url(array('page' => '_SELF', 'type' => '_delete'), '_SELF');
        $text = do_lang_tempcode('CONFIRM_DELETE', escape_html($file));

        $hidden->attach(form_input_hidden('zone', $zone));

        return do_template('CONFIRM_SCREEN', array('_GUID' => 'f732bb10942759c6ca5771d2d446c333', 'TITLE' => $this->title, 'HIDDEN' => $hidden, 'TEXT' => $text, 'URL' => $url, 'FIELDS' => ''));
    }

    /**
     * The actualiser to delete a page.
     *
     * @return Tempcode The UI
     */
    public function _delete()
    {
        $zone = post_param_string('zone', null);

        $afm_needed = false;
        $pages = find_all_pages_wrap($zone);
        foreach ($pages as $page => $type) {
            if (is_integer($page)) {
                $page = strval($page);
            }

            if (post_param_integer('page__' . $page, 0) == 1) {
                if ((get_file_base() != get_custom_file_base()) && (strpos($type, 'comcode_custom') !== false)) {
                    warn_exit(do_lang_tempcode('SHARED_INSTALL_PROHIBIT'));
                }

                if ($type != 'comcode_custom') {
                    $afm_needed = true;
                }
            }
        }

        if ($afm_needed) {
            appengine_live_guard();

            require_code('abstract_file_manager');
            force_have_afm_details();
        }

        foreach ($pages as $page => $type) {
            if (is_integer($page)) {
                $page = strval($page);
            }

            if (post_param_integer('page__' . $page, 0) == 1) {
                require_code('zones3');
                delete_cms_page($zone, $page, $type, $afm_needed);
            }
        }

        erase_persistent_cache();

        delete_cache_entry('menu');

        return inform_screen($this->title, do_lang_tempcode('SUCCESS'));
    }

    /**
     * The actualiser to move a page.
     *
     * @return Tempcode The UI
     */
    public function _move()
    {
        if ($GLOBALS['CURRENT_SHARE_USER'] !== null) {
            warn_exit(do_lang_tempcode('SHARED_INSTALL_PROHIBIT'));
        }

        $zone = post_param_string('zone', null);

        if ($zone === null) {
            $post_url = build_url(array('page' => '_SELF', 'type' => get_param_string('type')), '_SELF', array(), true);
            $hidden = build_keep_form_fields('', true);

            $from = $GLOBALS['SITE_DB']->query_select_value('zones', 'zone_title', array('zone_name' => get_param_string('zone')));
            $to = $GLOBALS['SITE_DB']->query_select_value('zones', 'zone_title', array('zone_name' => get_param_string('destination_zone')));

            return do_template('CONFIRM_SCREEN', array(
                '_GUID' => 'c6e872cc62bdc7cf1c5157fbfdb2dfd6',
                'TITLE' => $this->title,
                'TEXT' => do_lang_tempcode('Q_SURE_MOVE', escape_html($from), escape_html($to)),
                'URL' => $post_url,
                'HIDDEN' => $hidden,
                'FIELDS' => '',
            ));
        }

        $new_zone = post_param_string('destination_zone', ''/*Could be welcome zone so need to imply '' is valid*/);
        if (substr($new_zone, -1) == ':') {
            $new_zone = substr($new_zone, 0, strlen($new_zone) - 1);
        }

        $pages = array();
        foreach ($_POST as $key => $val) {
            if ((substr($key, 0, 6) == 'page__') && ($val === '1')) {
                $page = substr($key, 6);
                $page_details = _request_page($page, $zone, null, null, true);
                if ($page_details === false) {
                    warn_exit(do_lang_tempcode('MISSING_RESOURCE', do_lang_tempcode('PAGE')));
                }
                $pages[$page] = strtolower($page_details[0]);
                if (array_key_exists(3, $page_details)) {
                    $pages[$page] .= '/' . $page_details[3];
                }
            }
        }

        $afm_needed = false;
        foreach ($pages as $page => $type) {
            if (is_integer($page)) {
                $page = strval($page);
            }

            if (post_param_integer('page__' . $page, 0) == 1) {
                if ($type != 'comcode_custom') {
                    $afm_needed = true;
                }
            }
        }

        if ($afm_needed) {
            appengine_live_guard();

            require_code('abstract_file_manager');
            force_have_afm_details();
        }
        $cannot_move = new Tempcode();
        foreach ($pages as $page => $type) {
            if (!is_string($page)) {
                $page = strval($page);
            }

            if (post_param_integer('page__' . $page, 0) == 1) {
                if (substr($type, 0, 7) == 'modules') {
                    $_page = $page . '.php';
                } elseif (substr($type, 0, 7) == 'comcode') {
                    $_page = $page . '.txt';
                } elseif (substr($type, 0, 4) == 'html') {
                    $_page = $page . '.htm';
                }
                if (file_exists(zone_black_magic_filterer(get_custom_file_base() . '/' . filter_naughty($new_zone) . (($new_zone != '') ? '/' : '') . 'pages/' . filter_naughty($type) . '/' . $_page))) {
                    if (!$cannot_move->is_empty()) {
                        $cannot_move->attach(do_lang_tempcode('LIST_SEP'));
                    }
                    $cannot_move->attach(do_lang_tempcode('PAGE_WRITE', escape_html($page)));
                    continue;
                }
            }
        }

        $moved_something = null;
        foreach ($pages as $page => $type) {
            if (!is_string($page)) {
                $page = strval($page);
            }

            if (post_param_integer('page__' . $page, 0) == 1) {
                $moved_something = $page;

                if (substr($type, 0, 7) == 'modules') {
                    $_page = $page . '.php';
                } elseif (substr($type, 0, 7) == 'comcode') {
                    $_page = $page . '.txt';
                } elseif (substr($type, 0, 4) == 'html') {
                    $_page = $page . '.htm';
                }
                if (file_exists(zone_black_magic_filterer(get_custom_file_base() . '/' . filter_naughty($new_zone) . (($new_zone != '') ? '/' : '') . 'pages/' . filter_naughty($type) . '/' . $_page))) {
                    continue;
                }

                if (file_exists(zone_black_magic_filterer(get_custom_file_base() . '/' . filter_naughty($zone) . (($zone == '') ? '' : '/') . 'pages/' . filter_naughty($type) . '/' . $_page))) {
                    if ($afm_needed) {
                        afm_move(
                            zone_black_magic_filterer(filter_naughty($zone) . (($zone == '') ? '' : '/') . 'pages/' . filter_naughty($type) . '/' . $_page, true),
                            zone_black_magic_filterer(filter_naughty($new_zone) . (($new_zone != '') ? '/' : '') . 'pages/' . filter_naughty($type) . '/' . $_page, true)
                        );
                    } else {
                        $old_path = zone_black_magic_filterer(get_custom_file_base() . '/' . filter_naughty($zone) . (($zone == '') ? '' : '/') . 'pages/' . filter_naughty($type) . '/' . $_page);
                        $new_path = zone_black_magic_filterer(get_custom_file_base() . '/' . filter_naughty($new_zone) . (($new_zone != '') ? '/' : '') . 'pages/' . filter_naughty($type) . '/' . $_page);
                        rename($old_path, $new_path);
                        sync_file_move($old_path, $new_path);
                    }
                }

                // If a non-overridden one is there too, need to move that too
                if ((strpos($type, '_custom') !== false) && (file_exists(zone_black_magic_filterer(get_custom_file_base() . '/' . filter_naughty($zone) . (($zone == '') ? '' : '/') . 'pages/' . filter_naughty(str_replace('_custom', '', $type)) . '/' . $_page))) && (!file_exists(zone_black_magic_filterer(get_custom_file_base() . '/' . filter_naughty($new_zone) . (($new_zone != '') ? '/' : '') . 'pages/' . filter_naughty(str_replace('_custom', '', $type)) . '/' . $_page)))) {
                    if ($afm_needed) {
                        afm_move(
                            zone_black_magic_filterer(filter_naughty($zone) . (($zone == '') ? '' : '/') . 'pages/' . filter_naughty(str_replace('_custom', '', $type)) . '/' . $_page, true),
                            zone_black_magic_filterer(filter_naughty($new_zone) . (($new_zone != '') ? '/' : '') . 'pages/' . filter_naughty(str_replace('_custom', '', $type)) . '/' . $_page, true)
                        );
                    } else {
                        $old_path = zone_black_magic_filterer(get_custom_file_base() . '/' . filter_naughty($zone) . (($zone == '') ? '' : '/') . 'pages/' . filter_naughty(str_replace('_custom', '', $type)) . '/' . $_page);
                        $new_path = zone_black_magic_filterer(get_custom_file_base() . '/' . filter_naughty($new_zone) . (($new_zone != '') ? '/' : '') . 'pages/' . filter_naughty(str_replace('_custom', '', $type)) . '/' . $_page);
                        rename($old_path, $new_path);
                        sync_file_move($old_path, $new_path);
                    }
                }

                log_it('MOVE_PAGES', $page);
            }
        }
        if ($moved_something === null) {
            warn_exit(do_lang_tempcode('NOTHING_SELECTED'));
        }

        erase_persistent_cache();

        require_lang('addons');
        if ($cannot_move->is_empty()) {
            $message = do_lang_tempcode('SUCCESS');
        } else {
            $message = do_lang_tempcode('WOULD_NOT_OVERWRITE_BUT_SUCCESS', $cannot_move);
        }

        delete_cache_entry('menu');

        return inform_screen($this->title, $message);
    }
}
