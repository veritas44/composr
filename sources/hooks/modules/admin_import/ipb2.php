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
 * @package    import
 */

require_code('hooks/modules/admin_import/shared/ipb');

/**
 * Hook class.
 */
class Hook_ipb2 extends Hook_ipb_base
{
    /**
     * Standard importer hook info function.
     *
     * @return ?array Importer handling details, including lists of all the import types covered (import types are not necessarily the same as actual tables) (null: importer is disabled).
     */
    public function info()
    {
        $info = array();
        $info['supports_advanced_import'] = false;
        $info['product'] = 'Invision Board 2.0.x';
        $info['prefix'] = 'ibf_';
        $info['import'] = array(
            'cns_groups',
            'cns_members',
            'cns_member_files',
            'custom_comcode',
            'cns_custom_profile_fields',
            'cns_forum_groupings',
            'cns_forums',
            'cns_topics',
            'cns_posts',
            'cns_post_files',
            'cns_polls_and_votes',
            'cns_multi_moderations',
            'notifications',
            'cns_private_topics',
            'cns_warnings',
            'wordfilter',
            'config',
            'calendar',
        );
        $info['dependencies'] = array( // This dependency tree is overdefined, but I wanted to make it clear what depends on what, rather than having a simplified version
                                       'cns_members' => array('cns_groups'),
                                       'cns_member_files' => array('cns_members'),
                                       'cns_forums' => array('cns_forum_groupings', 'cns_members', 'cns_groups'),
                                       'cns_topics' => array('cns_forums', 'cns_members'),
                                       'cns_polls_and_votes' => array('cns_topics', 'cns_members'),
                                       'cns_posts' => array('custom_comcode', 'cns_topics', 'cns_members'),
                                       'cns_post_files' => array('cns_posts'),
                                       'cns_multi_moderations' => array('cns_forums'),
                                       'notifications' => array('cns_topics', 'cns_members'),
                                       'cns_private_topics' => array('custom_comcode', 'cns_members'),
                                       'cns_warnings' => array('cns_members'),
                                       'calendar' => array('cns_members'),
        );
        $_cleanup_url = build_url(array('page' => 'admin_cleanup'), get_module_zone('admin_cleanup'));
        $cleanup_url = $_cleanup_url->evaluate();
        $info['message'] = (get_param_string('type', 'browse') != 'import' && get_param_string('type', 'browse') != 'hook') ? new Tempcode() : do_lang_tempcode('FORUM_CACHE_CLEAR', escape_html($cleanup_url));

        return $info;
    }

    /**
     * Standard import function.
     *
     * @param  object $db The DB connection to import from
     * @param  string $table_prefix The table prefix the target prefix is using
     * @param  PATH $old_base_dir The base directory we are importing from
     */
    public function import_custom_comcode($db, $table_prefix, $old_base_dir)
    {
        require_code('custom_comcode');
        require_code('comcode_compiler');

        $rows = $db->query('SELECT * FROM ' . $table_prefix . 'custom_bbcode');
        foreach ($rows as $row) {
            if (import_check_if_imported('custom_comcode', strval($row['bbcode_id']))) {
                continue;
            }

            global $VALID_COMCODE_TAGS;
            $test = $GLOBALS['SITE_DB']->query_select_value_if_there('custom_comcode', 'tag_tag', array('tag_tag' => $row['bbcode_tag']));
            if ((array_key_exists($row['bbcode_tag'], $VALID_COMCODE_TAGS)) || (!is_null($test))) {
                import_id_remap_put('custom_comcode', strval($row['bbcode_id']), 1);
                continue;
            }

            $tag = $row['bbcode_tag'];
            $title = $row['bbcode_title'];
            $description = $row['bbcode_desc'];
            $replace = $row['bbcode_replace'];
            $example = $row['bbcode_example'];
            $parameters = '';
            $enabled = 1;
            $dangerous_tag = 0;
            $block_tag = 0;
            $textual_tag = 1;

            add_custom_comcode_tag($tag, $title, $description, $replace, $example, $parameters, $enabled, $dangerous_tag, $block_tag, $textual_tag);

            import_id_remap_put('custom_comcode', strval($row['bbcode_id']), 1);
        }
    }

    /**
     * Standard import function.
     *
     * @param  object $db The DB connection to import from
     * @param  string $table_prefix The table prefix the target prefix is using
     * @param  PATH $old_base_dir The base directory we are importing from
     */
    public function import_cns_forum_groupings($db, $table_prefix, $old_base_dir)
    {
        $rows = $db->query('SELECT * FROM ' . $table_prefix . 'forums WHERE parent_id=-1 ORDER BY id');
        foreach ($rows as $row) {
            if (import_check_if_imported('category', strval($row['id']))) {
                continue;
            }

            if ($row['id'] == -1) {
                continue;
            }

            $title = @html_entity_decode($row['name'], ENT_QUOTES, get_charset());

            $test = $GLOBALS['FORUM_DB']->query_select_value_if_there('f_forum_groupings', 'id', array('c_title' => $title));
            if (!is_null($test)) {
                import_id_remap_put('category', strval($row['id']), $test);
                continue;
            }

            $description = strip_html($row['description']);
            $expanded_by_default = 1;

            $id_new = cns_make_forum_grouping($title, $description, $expanded_by_default);

            import_id_remap_put('category', strval($row['id']), $id_new);
        }
    }

    /**
     * Standard import function.
     *
     * @param  object $db The DB connection to import from
     * @param  string $table_prefix The table prefix the target prefix is using
     * @param  PATH $old_base_dir The base directory we are importing from
     */
    public function import_cns_forums($db, $table_prefix, $old_base_dir)
    {
        require_code('cns_forums_action2');

        $remap_id = array();
        $rows = $db->query('SELECT * FROM ' . $table_prefix . 'forums WHERE parent_id<>-1 ORDER BY id');
        foreach ($rows as $row_number => $row) {
            $remapped = import_id_remap_get('forum', strval($row['id']), true);
            if (!is_null($remapped)) {
                $remap_id[$row['id']] = $remapped;
                $rows[$row_number]['parent_id'] = null;
                continue;
            }

            if ($row['id'] == -1) {
                continue;
            }

            $name = @html_entity_decode($row['name'], ENT_QUOTES, get_charset());
            $description = strip_html($row['description']);

            // To determine whether parent_id specifies category or parent, we must check status of what it is pointing at
            $parent_test = $db->query('SELECT use_ibc,parent_id FROM ' . $table_prefix . 'forums WHERE id=' . strval($row['parent_id']));
            if ($parent_test[0]['parent_id'] != -1) { // Pointing to parent
                $parent_forum = import_id_remap_get('forum', strval($row['parent_id']), true);
                if (!is_null($parent_forum)) {
                    $rows[$row_number]['parent_id'] = null; // Mark it as good (we do not need to fix this parenting)
                }
                $category_id = db_get_first_id();
            } else { // Pointing to category
                $category_id = import_id_remap_get('category', strval($row['parent_id']));
                $parent_forum = db_get_first_id();
                $rows[$row_number]['parent_id'] = null; // Mark it as good (we do not need to fix this parenting)
            }

            $position = $row['position'];
            $post_count_increment = $row['inc_postcount'];

            $permissions = unserialize(stripslashes($row['permission_array']));
            $_all_groups = array_unique(explode(',', $permissions['start_perms'] . ',' . $permissions['reply_perms'] . ',' . $permissions['read_perms']));
            $level2_groups = explode(',', $permissions['read_perms']);
            $level3_groups = explode(',', $permissions['reply_perms']);
            $level4_groups = explode(',', $permissions['start_perms']);
            $access_mapping = array();
            foreach ($_all_groups as $old_group) {
                $new_group = import_id_remap_get('group', $old_group, true);
                if (is_null($new_group)) {
                    continue;
                }

                if (in_array($old_group, $level4_groups)) {
                    $access_mapping[$new_group] = 4;
                } elseif (in_array($old_group, $level3_groups)) {
                    $access_mapping[$new_group] = 3;
                } elseif (in_array($old_group, $level2_groups)) {
                    $access_mapping[$new_group] = 2;
                } else {
                    $access_mapping[$new_group] = 0;
                }
            }

            $id_new = cns_make_forum($name, $description, $category_id, $access_mapping, $parent_forum, $position, $post_count_increment);

            $remap_id[$row['id']] = $id_new;
            import_id_remap_put('forum', strval($row['id']), $id_new);
        }

        // Now we must fix parenting
        foreach ($rows as $row) {
            if (!is_null($row['parent_id'])) {
                $parent_id = $remap_id[$row['parent_id']];
                $GLOBALS['FORUM_DB']->query_update('f_forums', array('f_parent_forum' => $parent_id), array('id' => $remap_id[$row['id']]), '', 1);
            }
        }
    }

    /**
     * Standard import function.
     *
     * @param  object $db The DB connection to import from
     * @param  string $table_prefix The table prefix the target prefix is using
     * @param  PATH $file_base The base directory we are importing from
     */
    public function import_config($db, $table_prefix, $file_base)
    {
        $config_remapping = array(
            'board_offline' => 'site_closed',
            'offline_msg' => 'closed',
            'au_cutoff' => 'users_online_time',
            'email_out' => 'smtp_from_address',
            'email_in' => 'staff_address',
            'smtp_host' => 'smtp_sockets_host',
            'smtp_port' => 'smtp_sockets_port',
            'smtp_user' => 'smtp_sockets_username',
            'smtp_pass' => 'smtp_sockets_password',
            'home_name' => 'site_name',
            'reg_auth_type' => 'require_new_member_validation',
            //'show_max_msg_list' => 'forum_posts_per_page'
        );

        $rows = $db->query('SELECT * FROM ' . $table_prefix . 'conf_settings');
        $PROBED_FORUM_CONFIG = array();
        foreach ($rows as $row) {
            if ($row['conf_value'] == '') {
                $row['conf_value'] = $row['conf_default'];
            }
            if (array_key_exists($row['conf_key'], $config_remapping)) {
                set_option($config_remapping[$row['conf_key']], $row['conf_value']);
            }
            $PROBED_FORUM_CONFIG[$row['conf_key']] = $row['conf_value'];
        }

        set_option('session_expiry_time', strval(60 * intval($PROBED_FORUM_CONFIG['session_expiration'])));
        set_option('gzip_output', strval(1 - intval($PROBED_FORUM_CONFIG['disable_gzip'])));
        set_option('smtp_sockets_use', ($PROBED_FORUM_CONFIG['mail_method'] == 'smtp') ? '1' : '0');
        set_option('session_expiry_time', strval(60 * intval($PROBED_FORUM_CONFIG['session_expiration'])));
        set_value('timezone', $PROBED_FORUM_CONFIG['time_offset']);

        // Now some usergroup options
        $groups = $GLOBALS['CNS_DRIVER']->get_usergroup_list();
        list($width, $height) = explode('x', $PROBED_FORUM_CONFIG['avatar_dims']);
        $GLOBALS['SITE_DB']->query_delete('group_page_access', array('page_name' => 'search', 'zone_name' => get_module_zone('search')));
        $GLOBALS['SITE_DB']->query_delete('group_page_access', array('page_name' => 'join', 'zone_name' => get_module_zone('join')));
        $super_admin_groups = $GLOBALS['CNS_DRIVER']->_get_super_admin_groups();
        foreach (array_keys($groups) as $id) {
            if (in_array($id, $super_admin_groups)) {
                continue;
            }

            if ($PROBED_FORUM_CONFIG['allow_search'] == '0') {
                $GLOBALS['SITE_DB']->query_insert('group_page_access', array('page_name' => 'search', 'zone_name' => get_module_zone('search'), 'group_id' => $id));
            }
            if ($PROBED_FORUM_CONFIG['no_reg'] == '1') {
                $GLOBALS['SITE_DB']->query_insert('group_page_access', array('page_name' => 'join', 'zone_name' => get_module_zone('join'), 'group_id' => $id));
            }

            $GLOBALS['FORUM_DB']->query_update('f_groups', array('g_flood_control_submit_secs' => intval($PROBED_FORUM_CONFIG['flood_control']), 'g_max_avatar_width' => $width, 'g_max_avatar_height' => $height, 'g_max_sig_length_comcode' => $PROBED_FORUM_CONFIG['max_sig_length'], 'g_max_post_length_comcode' => $PROBED_FORUM_CONFIG['max_post_length']), array('id' => $id), '', 1);
        }
    }

    /**
     * Standard import function.
     *
     * @param  object $db The DB connection to import from
     * @param  string $table_prefix The table prefix the target prefix is using
     * @param  PATH $old_base_dir The base directory we are importing from
     */
    public function import_cns_private_topics($db, $table_prefix, $old_base_dir)
    {
        $rows = $db->query('SELECT * FROM ' . $table_prefix . 'message_topics m LEFT JOIN ' . $table_prefix . 'message_text t ON m.mt_msg_id=t.msg_id WHERE mt_vid_folder<>\'sent\' ORDER BY mt_date');

        // Group them up into what will become topics
        $groups = array();
        foreach ($rows as $row) {
            if ($row['mt_from_id'] > $row['mt_to_id']) {
                $a = $row['mt_to_id'];
                $b = $row['mt_from_id'];
            } else {
                $a = $row['mt_from_id'];
                $b = $row['mt_to_id'];
            }
            $title = str_replace('Re: ', '', $row['mt_title']);
            $title = str_replace('RE: ', '', $title);
            $title = str_replace('Re:', '', $title);
            $title = str_replace('RE:', '', $title);
            $groups[strval($a) . ':' . strval($b) . ':' . @html_entity_decode($title, ENT_QUOTES, get_charset())][] = $row;
        }

        // Import topics
        foreach ($groups as $group) {
            $row = $group[0];

            if (import_check_if_imported('pt', strval($row['mt_msg_id']))) {
                continue;
            }

            // Create topic
            $from_id = import_id_remap_get('member', strval($row['mt_from_id']), true);
            if (is_null($from_id)) {
                $from_id = $GLOBALS['CNS_DRIVER']->get_guest_id();
            }
            $to_id = import_id_remap_get('member', strval($row['mt_to_id']), true);
            if (is_null($to_id)) {
                $to_id = $GLOBALS['CNS_DRIVER']->get_guest_id();
            }
            $topic_id = cns_make_topic(null, '', '', 1, 1, 0, 0, 0, $from_id, $to_id, false);

            $first_post = true;
            foreach ($group as $_postdetails) {
                if ($first_post) {
                    $title = @html_entity_decode($row['mt_title'], ENT_QUOTES, get_charset());
                } else {
                    $title = '';
                }

                $post = str_replace('$', '[html]$[/html]', $this->clean_ipb_post($_postdetails['msg_post']));
                $validated = 1;
                $from_id = import_id_remap_get('member', strval($_postdetails['mt_from_id']), true);
                if (is_null($from_id)) {
                    $from_id = $GLOBALS['CNS_DRIVER']->get_guest_id();
                }
                $poster_name_if_guest = $GLOBALS['CNS_DRIVER']->get_member_row_field($from_id, 'm_username');
                $ip_address = $GLOBALS['CNS_DRIVER']->get_member_row_field($from_id, 'm_ip_address');
                $time = $_postdetails['mt_date'];
                $poster = $from_id;
                $last_edit_time = null;
                $last_edit_by = null;

                cns_make_post($topic_id, $title, $post, 0, $first_post, $validated, 0, $poster_name_if_guest, $ip_address, $time, $poster, null, $last_edit_time, $last_edit_by, false, false, null, false);
                $first_post = false;
            }

            import_id_remap_put('pt', strval($row['mt_msg_id']), $topic_id);
        }
    }
}
