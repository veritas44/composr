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
 * @package    redirects_editor
 */

/**
 * Module page class.
 */
class Module_admin_redirects
{
    /**
     * Find details of the module.
     *
     * @return ?array Map of module info (null: module is disabled).
     */
    public function info()
    {
        $info = array();
        $info['author'] = 'Chris Graham';
        $info['organisation'] = 'ocProducts';
        $info['hacked_by'] = null;
        $info['hack_version'] = null;
        $info['version'] = 4;
        $info['locked'] = true;
        $info['update_require_upgrade'] = 1;
        return $info;
    }

    /**
     * Uninstall the module.
     */
    public function uninstall()
    {
        $GLOBALS['SITE_DB']->drop_table_if_exists('redirects');
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
            $GLOBALS['SITE_DB']->create_table('redirects', array(
                'r_from_page' => '*ID_TEXT',
                'r_from_zone' => '*ID_TEXT',
                'r_to_page' => 'ID_TEXT',
                'r_to_zone' => 'ID_TEXT',
                'r_is_transparent' => 'BINARY',
            ));

            $GLOBALS['SITE_DB']->query_insert('redirects', array('r_from_page' => 'rules', 'r_from_zone' => 'site', 'r_to_page' => 'rules', 'r_to_zone' => '', 'r_is_transparent' => 1));
            $GLOBALS['SITE_DB']->query_insert('redirects', array('r_from_page' => 'rules', 'r_from_zone' => 'forum', 'r_to_page' => 'rules', 'r_to_zone' => '', 'r_is_transparent' => 1));
            $GLOBALS['SITE_DB']->query_insert('redirects', array('r_from_page' => 'authors', 'r_from_zone' => 'collaboration', 'r_to_page' => 'authors', 'r_to_zone' => 'site', 'r_is_transparent' => 1));
        }

        if ((is_null($upgrade_from)) || ($upgrade_from < 3)) {
            $zones = find_all_zones();
            if (!in_array('site', $zones)) {
                $zones[] = 'site';
            }
            foreach ($zones as $zone) {
                if (!file_exists(get_file_base() . '/' . $zone . '/pages/comcode/' . fallback_lang() . '/panel_top.txt')) {
                    $GLOBALS['SITE_DB']->query_insert('redirects', array('r_from_page' => 'panel_top', 'r_from_zone' => $zone, 'r_to_page' => 'panel_top', 'r_to_zone' => '', 'r_is_transparent' => 1));
                }
            }
        }

        if ((is_null($upgrade_from)) || ($upgrade_from < 4)) {
            $zones = find_all_zones();
            if (!in_array('site', $zones)) {
                $zones[] = 'site';
            }
            foreach ($zones as $zone) {
                if (!file_exists(get_file_base() . '/' . $zone . '/pages/comcode/' . fallback_lang() . '/panel_bottom.txt')) {
                    $GLOBALS['SITE_DB']->query_insert('redirects', array('r_from_page' => 'panel_bottom', 'r_from_zone' => $zone, 'r_to_page' => 'panel_bottom', 'r_to_zone' => '', 'r_is_transparent' => 1), false, true);
                }
            }
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
        return array(
            'browse' => array('REDIRECTS', 'menu/adminzone/structure/redirects'),
        );
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

        require_lang('redirects');

        $this->title = get_screen_title('REDIRECTS');

        return null;
    }

    /**
     * Execute the module.
     *
     * @return Tempcode The result of execution.
     */
    public function run()
    {
        $type = get_param_string('type', 'browse');

        if ($type == 'browse') {
            return $this->gui();
        }
        if ($type == 'actual') {
            return $this->actual();
        }

        return new Tempcode();
    }

    /**
     * The UI for managing redirects.
     *
     * @return Tempcode The UI
     */
    public function gui()
    {
        require_css('redirects_editor');

        $post_url = build_url(array('page' => '_SELF', 'type' => 'actual'), '_SELF');
        $fields = new Tempcode();
        $rows = $GLOBALS['SITE_DB']->query_select('redirects', array('*'));
        $num_zones = $GLOBALS['SITE_DB']->query_select_value('zones', 'COUNT(*)');
        require_code('zones3');
        foreach ($rows as $i => $row) {
            if ($num_zones > 50) {
                $from_zones = new Tempcode();
            } else {
                $from_zones = create_selection_list_zones($row['r_from_zone']);
                $from_zones->attach(form_input_list_entry('*', $row['r_from_zone'] == '*', do_lang_tempcode('_ALL')));
            }
            $to_zones = ($num_zones > 50) ? new Tempcode() : create_selection_list_zones($row['r_to_zone']);
            $fields->attach(do_template('REDIRECTE_TABLE_REDIRECT', array(
                '_GUID' => 'fd1ea392a98e588bb1f553464d315ef0',
                'I' => strval($i),
                'FROM_ZONE' => $row['r_from_zone'],
                'TO_ZONE' => $row['r_to_zone'],
                'TO_ZONES' => $to_zones,
                'FROM_ZONES' => $from_zones,
                'FROM_PAGE' => $row['r_from_page'],
                'TO_PAGE' => $row['r_to_page'],
                'TICKED' => $row['r_is_transparent'] == 1,
                'NAME' => 'is_transparent_' . strval($i),
            )));
        }
        $default = explode(':', get_param_string('page_link', '*:'), 2);
        if ($num_zones > 50) {
            $to_zones = new Tempcode();
            $from_zones = new Tempcode();
        } else {
            $zones = create_selection_list_zones($default[0]);
            $to_zones = new Tempcode();
            $to_zones->attach($zones);
            $from_zones = new Tempcode();
            $from_zones->attach($zones);
            $from_zones->attach(form_input_list_entry('*', $default[0] == '*', do_lang_tempcode('_ALL')));
        }
        $new = do_template('REDIRECTE_TABLE_REDIRECT', array(
            '_GUID' => 'cbf0eb4f745a6bf7b10e1f7d6d95d10f',
            'I' => 'new',
            'FROM_ZONE' => '',
            'TO_ZONE' => '',
            'TO_ZONES' => $to_zones,
            'FROM_ZONES' => $from_zones,
            'FROM_PAGE' => $default[1],
            'TO_PAGE' => '',
            'TICKED' => false,
            'NAME' => 'is_transparent_new',
        ));

        require_code('form_templates');
        list($warning_details, $ping_url) = handle_conflict_resolution();

        $notes = get_value('notes', null, true);
        if (is_null($notes)) {
            $notes = '';
        }

        return do_template('REDIRECTE_TABLE_SCREEN', array(
            '_GUID' => '2a9add73f6dd0b8288c0c84fc7242763',
            'NOTES' => $notes,
            'PING_URL' => $ping_url,
            'WARNING_DETAILS' => $warning_details,
            'TITLE' => $this->title,
            'FIELDS' => $fields,
            'NEW' => $new,
            'URL' => $post_url,
        ));
    }

    /**
     * The actualiser for managing redirects.
     *
     * @return Tempcode The UI
     */
    public function actual()
    {
        $found = array();
        foreach ($_POST as $key => $val) {
            if (!is_string($val)) {
                continue;
            }

            if (get_magic_quotes_gpc()) {
                $val = stripslashes($val);
            }

            if ((substr($key, 0, 10) == 'from_page_') && ($val != '')) {
                $their_i = array_search($val, $found);
                $i = substr($key, 10);
                if (($their_i !== false) && (post_param_string('from_zone_' . $i) == post_param_string('from_zone_' . strval($their_i)))) {
                    warn_exit(do_lang_tempcode('DUPLICATE_PAGE_REDIRECT', post_param_string('from_zone_' . $i) . ':' . $val));
                }
                $found[$i] = $val;
            }
        }

        $GLOBALS['SITE_DB']->query_delete('redirects');
        erase_persistent_cache();

        foreach ($found as $i => $val) {
            if (!is_string($i)) {
                $i = strval($i);
            }

            if ($val != '') {
                $GLOBALS['SITE_DB']->query_insert('redirects', array(
                    'r_from_page' => post_param_string('from_page_' . $i),
                    'r_from_zone' => post_param_string('from_zone_' . $i),
                    'r_to_page' => post_param_string('to_page_' . $i),
                    'r_to_zone' => post_param_string('to_zone_' . $i),
                    'r_is_transparent' => post_param_integer('is_transparent_' . $i, 0)
                ), false, true); // Avoid problem when same key entered twice
            }
        }

        require_code('caches3');
        erase_block_cache();

        log_it('SET_REDIRECTS');

        // Personal notes
        if (!is_null(post_param_string('notes', null))) {
            $notes = post_param_string('notes');
            set_value('notes', $notes, true);
        }

        // Redirect them back to editing screen
        $url = build_url(array('page' => '_SELF', 'type' => 'browse'), '_SELF');
        return redirect_screen($this->title, $url, do_lang_tempcode('SUCCESS'));
    }
}
