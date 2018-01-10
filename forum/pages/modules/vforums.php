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
 * @package    cns_forum
 */

/**
 * Module page class.
 */
class Module_vforums
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
        $info['version'] = 2;
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
        if (get_forum_type() != 'cns') {
            return null;
        }
        if ($be_deferential) {
            return array();
        }

        if ($check_perms && is_guest($member_id)) {
            return array(
                'browse' => array('POSTS_SINCE', 'menu/social/forum/vforums/posts_since_last_visit'),
                'unanswered' => array('UNANSWERED_TOPICS', 'menu/social/forum/vforums/unanswered_topics'),
            );
        }

        return array(
            'browse' => array('POSTS_SINCE', 'menu/social/forum/vforums/posts_since_last_visit'),
            'unread' => array('TOPICS_UNREAD', 'menu/social/forum/vforums/unread_topics'),
            'recently_read' => array('RECENTLY_READ', 'menu/social/forum/vforums/recently_read_topics'),
            'unanswered' => array('UNANSWERED_TOPICS', 'menu/social/forum/vforums/unanswered_topics'),
            'involved' => array('INVOLVED_TOPICS', 'menu/social/forum/vforums/involved_topics'),
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

        require_lang('cns');

        if ($type == 'browse') {
            $this->title = get_screen_title('POSTS_SINCE');
        }

        if ($type == 'unanswered') {
            $this->title = get_screen_title('UNANSWERED_TOPICS');
        }

        if ($type == 'involved') {
            $this->title = get_screen_title('INVOLVED_TOPICS');
        }

        if ($type == 'unread') {
            $this->title = get_screen_title('TOPICS_UNREAD');
        }

        if ($type == 'recently_read') {
            $this->title = get_screen_title('RECENTLY_READ');
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
        if (get_forum_type() != 'cns') {
            warn_exit(do_lang_tempcode('NO_CNS'));
        } else {
            cns_require_all_forum_stuff();
        }
        require_code('cns_forumview');

        require_css('cns');

        $type = get_param_string('type', 'browse');
        if ($type == 'browse') {
            $content = $this->new_posts();
        } elseif ($type == 'unread') {
            $content = $this->unread_topics();
        } elseif ($type == 'recently_read') {
            $content = $this->recently_read();
        } elseif ($type == 'unanswered') {
            $content = $this->unanswered_topics();
        } elseif ($type == 'involved') {
            $content = $this->involved_topics();
        } else {
            $content = new Tempcode();
        }

        return do_template('CNS_VFORUM_SCREEN', array('_GUID' => '8dca548982d65500ab1800ceec2ddc61', 'TITLE' => $this->title, 'CONTENT' => $content));
    }

    /**
     * The UI to show topics with new posts since last visit time.
     *
     * @return Tempcode The UI
     */
    public function new_posts()
    {
        $title = do_lang_tempcode('POSTS_SINCE');

        $seconds_back = get_param_integer('seconds_back', null);
        if ($seconds_back === null) {
            if (array_key_exists('last_visit', $_COOKIE)) {
                $last_time = intval($_COOKIE['last_visit']);
            } else {
                $last_time = time() - 60 * 60 * 24 * 7;
                if (!$GLOBALS['DEV_MODE']) {
                    attach_message(do_lang_tempcode('NO_LAST_VISIT'), 'notice');
                }
            }
        } else {
            $last_time = time() - $seconds_back;
        }

        $condition = 't_cache_last_time>' . strval($last_time);

        $extra_tpl_map = array('FILTERING' => do_template('CNS_VFORUM_FILTERING', array()));

        return $this->_vforum($title, $condition, 'last_post', true, $extra_tpl_map);
    }

    /**
     * The UI to show unanswered topics.
     *
     * @return Tempcode The UI
     */
    public function unanswered_topics()
    {
        $title = do_lang_tempcode('UNANSWERED_TOPICS');

        $condition = array(
            '(t_cache_num_posts=1 OR t_cache_num_posts<5 AND (SELECT COUNT(DISTINCT p2.p_poster) FROM ' . $GLOBALS['FORUM_DB']->get_table_prefix() . 'f_posts p2 WHERE p2.p_topic_id=t.id)=1) AND t_cache_last_time>' . strval(time() - 60 * 60 * 24 * 14), // Extra limit, otherwise query can take forever
        );
        // NB: "t_cache_num_posts<5" above is an optimisation, to do accurate detection of "only poster" only if there are a handful of posts (scanning huge topics can be slow considering this is just to make a subquery pass). We assume that a topic is not consisting of a single user posting more than 5 times (and if so we can consider them a spammer so rule it out)

        $initial_table = $GLOBALS['FORUM_DB']->get_table_prefix() . 'f_topics t';

        return $this->_vforum($title, $condition, 'last_post', true, null, $initial_table);
    }

    /**
     * The UI to show topics you're involved with.
     *
     * @return Tempcode The UI
     */
    public function involved_topics()
    {
        if (is_guest()) {
            access_denied('NOT_AS_GUEST');
        }

        $title = do_lang_tempcode('INVOLVED_TOPICS');

        $_condition = 'pos.p_poster=' . strval(get_member());
        if (($GLOBALS['FORUM_DRIVER']->get_post_count(get_member()) > 5000) && (get_value('innodb') !== '1')) { // Too many posts, so make time-sensitive
            $_condition .= ' AND pos.p_time>' . strval(time() - 60 * 60 * 24 * 365);
        }
        $condition = array($_condition);

        $initial_table = $GLOBALS['FORUM_DB']->get_table_prefix() . 'f_posts pos' . $GLOBALS['FORUM_DB']->prefer_index('f_posts', 'posts_by');
        $initial_table .= ' LEFT JOIN ' . $GLOBALS['FORUM_DB']->get_table_prefix() . 'f_topics t ON t.id=pos.p_topic_id';

        if ($GLOBALS['DB_STATIC_OBJECT']->can_arbitrary_groupby()) {
            $extra_select = ',MAX(pos.p_time) AS p_time';
            $order = 'post_time_grouped';
        } else {
            $extra_select = '';
            $order = 'post_time';
        }

        return $this->_vforum($title, $condition, $order, true, null, $initial_table, $extra_select);
    }

    /**
     * The UI to show topics with unread posts.
     *
     * @return Tempcode The UI
     */
    public function unread_topics()
    {
        if (is_guest()) {
            access_denied('NOT_AS_GUEST');
        }

        $title = do_lang_tempcode('TOPICS_UNREAD');
        $condition = array('l_time IS NOT NULL AND l_time<t_cache_last_time', 'l_time IS NULL AND t_cache_last_time>' . strval(time() - 60 * 60 * 24 * intval(get_option('post_read_history_days'))));

        return $this->_vforum($title, $condition, 'last_post', true);
    }

    /**
     * The UI to show topics which have been recently read by the current member.
     *
     * @return Tempcode The UI
     */
    public function recently_read()
    {
        if (is_guest()) {
            access_denied('NOT_AS_GUEST');
        }

        $title = do_lang_tempcode('RECENTLY_READ');
        $condition = 'l_time>' . strval(time() - 60 * 60 * 24 * 2);

        return $this->_vforum($title, $condition, 'read_time', true);
    }

    /**
     * The UI to show a virtual forum.
     *
     * @param  Tempcode $title The title to show for the v-forum
     * @param  mixed $condition The condition (a fragment of an SQL query that gets embedded in the context of a topic selection query). May be string, or array of strings (separate queries to run and merge; done for performance reasons relating to DB indexing)
     * @param  string $order The ordering of the results
     * @param  boolean $no_pin Whether to not show pinning in a separate section
     * @param  array $extra_tpl_map Extra template parameters to pass through
     * @param  ?string $initial_table The table to query (null: topic table)
     * @param  string $extra_select Extra SQL for select clause
     * @return Tempcode The UI
     */
    public function _vforum($title, $condition, $order, $no_pin = false, $extra_tpl_map = array(), $initial_table = null, $extra_select = '')
    {
        require_code('templates_pagination');
        list($max, $start, , $sql_sup, $sql_sup_order_by, $true_start, , $keyset_field_stripped) = get_keyset_pagination_settings('forum_max', intval(get_option('forum_topics_per_page')), 'forum_start', null, null, $order, 'get_forum_sort_order_simplified');

        $_breadcrumbs = cns_forum_breadcrumbs(db_get_first_id(), null, get_param_integer('keep_forum_root', db_get_first_id()), false);
        $_breadcrumbs[] = array('', $title);
        breadcrumb_set_parents($_breadcrumbs);
        $breadcrumbs = breadcrumb_segments_to_tempcode($_breadcrumbs);

        $type = get_param_string('type', 'browse');
        $forum_name = do_lang_tempcode('VIRTUAL_FORUM');

        // Find topics
        $extra = ' AND ';
        if (!has_privilege(get_member(), 'see_unvalidated')) {
            $extra .= 't_validated=1 AND ';
        }
        require_code('cns_forums');
        $extra .= get_forum_access_sql('t.t_forum_id');
        $max_rows = 0;
        $topic_rows = array();
        $keyset_value = null;
        foreach (is_array($condition) ? $condition : array($condition) as $_condition) {
            $query = ' FROM ';
            if ($initial_table !== null) {
                $query .= $initial_table;
            } else {
                $query .= $GLOBALS['FORUM_DB']->get_table_prefix() . 'f_topics t';
            }
            if (!is_guest()) {
                $query .= ' LEFT JOIN ' . $GLOBALS['FORUM_DB']->get_table_prefix() . 'f_read_logs l ON (t.id=l.l_topic_id AND l.l_member_id=' . strval(get_member()) . ')';
            }
            $query_cnt = $query;
            $_query_cnt = $query;
            if (!multi_lang_content()) {
                $query .= ' LEFT JOIN ' . $GLOBALS['FORUM_DB']->get_table_prefix() . 'f_posts p ON p.id=t.t_cache_first_post_id';
            }
            $where = ' WHERE ((' . $_condition . ')' . $extra . ') AND t_forum_id IS NOT NULL';
            $query .= $where;
            $query_cnt .= $where;
            $_query_cnt .= $where;
            $query .= $sql_sup;
            if (($GLOBALS['DB_STATIC_OBJECT']->can_arbitrary_groupby()) && ($initial_table !== null)) {
                $query .= ' GROUP BY t.id';
                $query_cnt .= ' GROUP BY t.id';
            }
            $query .= $sql_sup_order_by;
            $full_query = 'SELECT t.*,' . (is_guest() ? 'NULL as l_time' : 'l_time');
            if (multi_lang_content()) {
                $full_query .= ',t_cache_first_post AS p_post';
            } else {
                $full_query .= ',p.p_post,p.p_post__text_parsed,p.p_post__source_user';
            }
            $full_query .= $extra_select;
            $full_query .= $query;
            if (($start < 200) && ($initial_table === null) && (multi_lang_content())) {
                $topic_rows = array_merge($topic_rows, $GLOBALS['FORUM_DB']->query($full_query, $max, $start, false, false, array('t_cache_first_post' => '?LONG_TRANS__COMCODE')));
            } else {
                $topic_rows = array_merge($topic_rows, $GLOBALS['FORUM_DB']->query($full_query, $max, $start));
            }
            if (($GLOBALS['DB_STATIC_OBJECT']->can_arbitrary_groupby()) && ($initial_table !== null)) {
                $max_rows += $GLOBALS['FORUM_DB']->query_value_if_there('SELECT COUNT(DISTINCT t.id) ' . $_query_cnt);
            } else {
                $max_rows += $GLOBALS['FORUM_DB']->query_value_if_there('SELECT COUNT(*) ' . $query_cnt);
            }
        }
        $hot_topic_definition = intval(get_option('hot_topic_definition'));
        $or_list = '';
        foreach ($topic_rows as $topic_row) {
            if ($or_list != '') {
                $or_list .= ' OR ';
            }
            $or_list .= 'p_topic_id=' . strval($topic_row['id']);
        }
        $involved = array();
        if (($or_list != '') && (!is_guest())) {
            $involved = $GLOBALS['FORUM_DB']->query('SELECT DISTINCT p_topic_id FROM ' . $GLOBALS['FORUM_DB']->get_table_prefix() . 'f_posts WHERE (' . $or_list . ') AND p_poster=' . strval(get_member()), null, 0, false, true);
            $involved = collapse_1d_complexity('p_topic_id', $involved);
        }
        $topics_array = array();
        foreach ($topic_rows as $topic_row) {
            $topics_array[] = cns_get_topic_array($topic_row, get_member(), $hot_topic_definition, in_array($topic_row['id'], $involved)) + $topic_row;
        }

        // Display topics
        $topics = new Tempcode();
        $pinned = false;
        $topic_wrapper = new Tempcode();
        $forum_name_map = collapse_2d_complexity('id', 'f_name', $GLOBALS['FORUM_DB']->query('SELECT id,f_name FROM ' . $GLOBALS['FORUM_DB']->get_table_prefix() . 'f_forums WHERE f_cache_num_posts>0'));
        foreach ($topics_array as $topic) {
            if ((!$no_pin) && ($pinned) && (!in_array('pinned', $topic['modifiers']))) {
                $topics->attach(do_template('CNS_PINNED_DIVIDER'));
            }
            $pinned = in_array('pinned', $topic['modifiers']);
            $forum_id = array_key_exists('forum_id', $topic) ? $topic['forum_id'] : null;
            $_forum_name = array_key_exists($forum_id, $forum_name_map) ? make_string_tempcode(escape_html($forum_name_map[$forum_id])) : do_lang_tempcode('PRIVATE_TOPICS');
            $topics->attach(cns_render_topic($topic, true, false, $_forum_name));

            if ($keyset_field_stripped !== null) {
                $keyset_value = $topic[$keyset_field_stripped]; // We keep overwriting this value until the last loop iteration
            }
        }
        if (!$topics->is_empty()) {
            $pagination = pagination(do_lang_tempcode('FORUM_TOPICS'), $true_start, 'forum_start', $max, 'forum_max', $max_rows, false, 5, null, '', $keyset_value);

            $moderator_actions = '';
            $moderator_actions .= '<option value="mark_topics_read">' . do_lang('MARK_READ') . '</option>';
            if ($title->evaluate() != do_lang('TOPICS_UNREAD')) {
                $moderator_actions .= '<option value="mark_topics_unread">' . do_lang('MARK_UNREAD') . '</option>';
            }
            if ($GLOBALS['XSS_DETECT']) {
                ocp_mark_as_escaped($moderator_actions);
            }

            $action_url = build_url(array('page' => 'topics', 'redirect' => protect_url_parameter(SELF_REDIRECT)), get_module_zone('topics'));
            $topic_wrapper = do_template('CNS_FORUM_TOPIC_WRAPPER', array(
                '_GUID' => '67356b4daacbed3e3d960d89a57d0a4a',
                'MAX' => strval($max),
                'ORDER' => '',
                'MAY_CHANGE_MAX' => false,
                'BREADCRUMBS' => $breadcrumbs,
                'BUTTONS' => '',
                'STARTER_TITLE' => '',
                'PAGINATION' => $pagination,
                'MODERATOR_ACTIONS' => $moderator_actions,
                'ACTION_URL' => $action_url,
                'TOPICS' => $topics,
                'FORUM_NAME' => $forum_name,
            ));
        }

        $_buttons = new Tempcode();
        $archive_url = $GLOBALS['FORUM_DRIVER']->forum_url(db_get_first_id(), true);
        $_buttons->attach(do_template('BUTTON_SCREEN', array('_GUID' => '8c928f1f703e9ba232a7033adee19a31', 'TITLE' => do_lang_tempcode('ROOT_FORUM'), 'IMG' => 'buttons--all', 'IMMEDIATE' => false, 'URL' => $archive_url)));
        if ($title->evaluate() == do_lang('TOPICS_UNREAD')) {
            $mark_read_url = build_url(array('page' => 'topics', 'type' => 'mark_read', 'id' => db_get_first_id()), get_module_zone('topics'));
            $_buttons->attach(do_template('BUTTON_SCREEN', array('_GUID' => 'b96e17e77be6de6faf9eb340d7ba955a', 'TITLE' => do_lang_tempcode('ROOT_FORUM'), 'IMG' => 'buttons--mark-read-forum', 'IMMEDIATE' => false, 'URL' => $mark_read_url)));
        }

        $tpl_map = array(
            '_GUID' => 'd3fa84575727af935eadb2ce2b7c7b3e',
            'FILTERS' => '',
            'FORUM_NAME' => $forum_name,
            'STARTER_TITLE' => '',
            'BUTTONS' => $_buttons,
            'TOPIC_WRAPPER' => $topic_wrapper,
            'FORUM_GROUPINGS' => '',
        );

        if (is_array($extra_tpl_map)) {
            $tpl_map = $tpl_map + $extra_tpl_map;
        }

        return do_template('CNS_FORUM', $tpl_map);
    }
}
