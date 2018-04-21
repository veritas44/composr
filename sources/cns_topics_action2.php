<?php /*

 Composr
 Copyright (c) ocProducts, 2004-2018

 See text/EN/licence.txt for full licensing information.


 NOTE TO PROGRAMMERS:
   Do not edit this file. If you need to make changes, save your changed file to the appropriate *_custom folder
   **** If you ignore this advice, then your website upgrades (e.g. for bug fixes) will likely kill your changes ****

*/

/**
 * @license    http://opensource.org/licenses/cpal_1.0 Common Public Attribution License
 * @copyright  ocProducts Ltd
 * @package    core_cns
 */

/**
 * Edit a topic.
 *
 * @param  ?AUTO_LINK $topic_id The ID of the topic to edit (null: Private Topic)
 * @param  ?SHORT_TEXT $description Description of the topic (null: do not change)
 * @param  ?SHORT_TEXT $emoticon The image code of the emoticon for the topic (null: do not change)
 * @param  ?BINARY $validated Whether the topic is validated (null: do not change)
 * @param  ?BINARY $open Whether the topic is open (null: do not change)
 * @param  ?BINARY $pinned Whether the topic is pinned (null: do not change)
 * @param  ?BINARY $cascading Whether the topic is cascading (null: do not change)
 * @param  LONG_TEXT $reason The reason for this action
 * @param  ?string $title New title for the topic (null: do not change)
 * @param  ?SHORT_TEXT $description_link Link related to the topic (e.g. link to view a ticket) (null: do not change).
 * @param  boolean $check_perms Whether to check permissions
 * @param  ?integer $views Number of views (null: do not change)
 * @param  boolean $null_is_literal Determines whether some nulls passed mean 'use a default' or literally mean 'set to null'
 */
function cns_edit_topic($topic_id, $description = null, $emoticon = null, $validated = null, $open = null, $pinned = null, $cascading = null, $reason = '', $title = null, $description_link = null, $check_perms = true, $views = null, $null_is_literal = false)
{
    $info = $GLOBALS['FORUM_DB']->query_select('f_topics', array('*'), array('id' => $topic_id), '', 1);
    if (!array_key_exists(0, $info)) {
        warn_exit(do_lang_tempcode('MISSING_RESOURCE', 'topic'));
    }
    $name = $info[0]['t_cache_first_title'];
    $forum_id = $info[0]['t_forum_id'];

    require_code('cns_forums');

    if ($check_perms) {
        if (!cns_may_moderate_forum($forum_id)) {
            $pinned = 0;
            if (($info[0]['t_cache_first_member_id'] != get_member()) || (!has_privilege(get_member(), 'close_own_topics'))) {
                $open = 1;
            }
            $cascading = 0;
        }

        if (!(($info[0]['t_cache_first_member_id'] == get_member()) && (has_privilege(get_member(), 'close_own_topics')))) {
            require_code('cns_topics');
            if ((!cns_may_edit_topics_by($forum_id, get_member(), $info[0]['t_cache_first_member_id'])) || ((($info[0]['t_pt_from'] != get_member()) && ($info[0]['t_pt_to'] != get_member())) && (!cns_has_special_pt_access($topic_id)) && (!has_privilege(get_member(), 'view_other_pt')) && ($forum_id === null))) {
                access_denied('I_ERROR');
            }
        }

        if (($forum_id !== null) && (!has_privilege(get_member(), 'bypass_validation_midrange_content', 'topics', array('forums', $forum_id)))) {
            $validated = null;
        }
    }

    require_code('cns_general_action2');
    $log_id = cns_mod_log_it('EDIT_TOPIC', strval($topic_id), $name, $reason);
    if (addon_installed('actionlog')) {
        require_code('revisions_engine_database');
        $revision_engine = new RevisionEngineDatabase();
        $revision_engine->add_revision(
            'topic',
            strval($topic_id),
            strval($topic_id),
            $name,
            $info[0]['t_description'],
            $info[0]['t_cache_first_member_id'],
            $info[0]['t_cache_first_time'],
            $log_id
        );
    }

    if ($title !== null) {
        require_code('urls2');
        suggest_new_idmoniker_for('topicview', 'browse', strval($topic_id), '', $title);
    }

    $update = array();
    if ($description !== null) {
        $update['t_description'] = substr($description, 0, 255);
    }
    if ($description_link !== null) {
        $update['t_description_link'] = substr($description_link, 0, 255);
    }
    if ($emoticon !== null) {
        $update['t_emoticon'] = $emoticon;
    }
    if (!addon_installed('unvalidated')) {
        $validated = 1;
    }
    if ($validated !== null) {
        $update['t_validated'] = $validated;
    }
    if ($pinned !== null) {
        $update['t_pinned'] = $pinned;
    }
    if ($cascading !== null) {
        $update['t_cascading'] = $cascading;
    }
    if ($open !== null) {
        $update['t_is_open'] = $open;
    }
    if ($views !== null) {
        $update['t_num_views'] = $views;
    }

    if (($title !== null) && ($title != '')) {
        $update['t_cache_first_title'] = $title;
        $GLOBALS['FORUM_DB']->query_update('f_posts', array('p_title' => $title), array('id' => $info[0]['t_cache_first_post_id']), '', 1);
    }

    if (($validated !== null) && ($validated == 1)) {
        $GLOBALS['FORUM_DB']->query_update('f_posts', array('p_validated' => 1), array('id' => $info[0]['t_cache_first_post_id']), '', 1); // Auto-validate first post, if topic validated
    }

    require_code('submit');
    $just_validated = (!content_validated('topic', strval($topic_id))) && ($validated == 1);
    if ($just_validated) {
        send_content_validated_notification('topic', strval($topic_id));
    }

    $GLOBALS['FORUM_DB']->query_update('f_topics', $update, array('id' => $topic_id), '', 1);

    if (($title !== null) && ($title != '') && ($forum_id !== null)) {
        require_code('cns_posts_action2');
        cns_force_update_forum_caching($forum_id, 0, 0);
    }

    if ((addon_installed('commandr')) && (!running_script('install'))) {
        require_code('resource_fs');
        generate_resource_fs_moniker('topic', strval($topic_id));
    }

    if ($forum_id !== null) {
        require_code('cns_posts_action');
        cns_decache_cms_blocks($forum_id);
    } else {
        decache_private_topics($info[0]['t_pt_from']);
        decache_private_topics($info[0]['t_pt_to']);
    }

    if ($forum_id !== null) {
        require_code('sitemap_xml');
        notify_sitemap_node_edit('SEARCH:topicview:id=' . strval($topic_id), has_category_access($GLOBALS['FORUM_DRIVER']->get_guest_id(), 'forums', strval($forum_id)));
    }
}

/**
 * Delete a topic.
 *
 * @param  AUTO_LINK $topic_id The ID of the topic to delete
 * @param  LONG_TEXT $reason The reason for this action
 * @param  ?AUTO_LINK $post_target_topic_id Where topic to move posts in this topic to (null: delete the posts)
 * @param  boolean $check_perms Whether to check permissions
 * @return AUTO_LINK The forum ID the topic is in (could be found without calling the function, but as we've looked it up, it is worth keeping)
 */
function cns_delete_topic($topic_id, $reason = '', $post_target_topic_id = null, $check_perms = true)
{
    // Info about source
    $info = $GLOBALS['FORUM_DB']->query_select('f_topics', array('*'), array('id' => $topic_id), '', 1);
    if (!array_key_exists(0, $info)) {
        warn_exit(do_lang_tempcode('MISSING_RESOURCE', 'topic'));
    }
    $name = $info[0]['t_cache_first_title'];
    $poll_id = $info[0]['t_poll_id'];
    $forum_id = $info[0]['t_forum_id'];
    $num_posts = $info[0]['t_cache_num_posts'];
    $validated = $info[0]['t_validated'];

    require_code('cns_topics');
    if ($check_perms) {
        if (
            (!cns_may_delete_topics_by($forum_id, get_member(), $info[0]['t_cache_first_member_id'])) ||
            (
                (
                    (($info[0]['t_pt_from'] !== null) && ($info[0]['t_pt_from'] != get_member())) &&
                    (($info[0]['t_pt_to'] !== null) && ($info[0]['t_pt_to'] != get_member()))
                ) &&
                (!cns_has_special_pt_access($topic_id)) &&
                (!has_privilege(get_member(), 'view_other_pt')) &&
                ($forum_id === null)
            )
        ) {
            access_denied('I_ERROR');
        }
    }

    if ($post_target_topic_id !== null) {
        $to = $GLOBALS['FORUM_DB']->query_select_value_if_there('f_topics', 't_forum_id', array('id' => $post_target_topic_id));
        if ($to === null) {
            warn_exit(do_lang_tempcode('MISSING_RESOURCE', 'topic'));
        }
    }

    require_code('cns_general_action2');
    $log_id = cns_mod_log_it('DELETE_TOPIC', strval($topic_id), $name, $reason);
    if (addon_installed('actionlog')) {
        require_code('revisions_engine_database');
        $revision_engine = new RevisionEngineDatabase();
        $revision_engine->add_revision(
            'topic',
            strval($topic_id),
            strval($topic_id),
            $name,
            $info[0]['t_description'],
            $info[0]['t_cache_first_member_id'],
            $info[0]['t_cache_first_time'],
            $log_id
        );
    }

    if ($forum_id !== null) {
        // Update member post counts if we've switched between post-count countable forums
        $post_count_info = $GLOBALS['FORUM_DB']->query('SELECT id,f_post_count_increment FROM ' . $GLOBALS['FORUM_DB']->get_table_prefix() . 'f_forums WHERE id=' . strval($forum_id) . (($post_target_topic_id !== null) ? (' OR id=' . strval($to)) : ''), 2, 0, false, true);
        if (!array_key_exists(0, $post_count_info)) {
            $post_count_info = array(array('id' => $forum_id, 'f_post_count_increment' => 1));
        }
        if ($post_count_info[0]['id'] == $forum_id) {
            $from_cnt = $post_count_info[0]['f_post_count_increment'];
            $to_cnt = (array_key_exists(1, $post_count_info)) ? $post_count_info[1]['f_post_count_increment'] : 0;
        } else {
            $from_cnt = $post_count_info[1]['f_post_count_increment'];
            $to_cnt = $post_count_info[0]['f_post_count_increment'];
        }
        require_code('cns_posts_action');
        if ($from_cnt != $to_cnt) {
            $where = array('p_topic_id' => $topic_id);
            if (addon_installed('unvalidated')) {
                $where['p_validated'] = 1;
            }
            $_member_post_counts = collapse_1d_complexity('p_poster', $GLOBALS['FORUM_DB']->query_select('f_posts', array('p_poster'), $where));
            $member_post_counts = array_count_values($_member_post_counts);

            foreach ($member_post_counts as $member_id => $member_post_count) {
                if ($to_cnt == 0) {
                    $member_post_count = -$member_post_count;
                }
                cns_force_update_member_post_count($member_id, $member_post_count);
            }
        }
    }

    // What to do with our posts
    if ($post_target_topic_id !== null) { // If we were asked to move the posts into another topic
        $GLOBALS['FORUM_DB']->query_update('f_posts', array('p_cache_forum_id' => $to, 'p_topic_id' => $post_target_topic_id), array('p_topic_id' => $topic_id));

        require_code('cns_posts_action2');

        cns_force_update_topic_caching($post_target_topic_id);

        if ($forum_id !== null) {
            cns_force_update_forum_caching($forum_id, $to, 1, $num_posts);
        }
    } else {
        $_postdetails = array();
        do {
            $_postdetails = $GLOBALS['FORUM_DB']->query_select('f_posts', array('p_post', 'id'), array('p_topic_id' => $topic_id), '', 200);
            foreach ($_postdetails as $post) {
                delete_lang($post['p_post'], $GLOBALS['FORUM_DB']);
                $GLOBALS['FORUM_DB']->query_delete('f_posts', array('id' => $post['id']), '', 1);
            }
        } while (count($_postdetails) != 0);
    }

    // Delete stuff
    if (($poll_id !== null) && (addon_installed('polls'))) {
        require_code('cns_polls_action');
        require_code('cns_polls_action2');
        cns_delete_poll($poll_id, '', false);
    }
    $GLOBALS['FORUM_DB']->query_delete('f_topics', array('id' => $topic_id), '', 1);
    $GLOBALS['FORUM_DB']->query_delete('f_read_logs', array('l_topic_id' => $topic_id));
    require_code('notifications');
    delete_all_notifications_on('cns_topic', strval($topic_id));

    // Delete the ticket row if it's a ticket
    if (addon_installed('tickets')) {
        require_code('tickets');
        if (($forum_id !== null) && (is_ticket_forum($forum_id))) {
            require_lang('tickets');
            require_code('tickets');
            require_code('tickets2');
            delete_ticket_by_topic_id($topic_id);
        }
    }

    // Update forum view caching
    if ($forum_id !== null) {
        require_code('cns_posts_action2');
        cns_force_update_forum_caching($forum_id, ($validated == 0) ? 0 : -1, -$num_posts);
    }

    if (addon_installed('catalogues')) {
        update_catalogue_content_ref('topic', strval($topic_id), '');
    }

    if ($forum_id !== null) {
        require_code('cns_posts_action');
        cns_decache_cms_blocks($forum_id);
    } else {
        decache_private_topics($info[0]['t_pt_from']);
        decache_private_topics($info[0]['t_pt_to']);
    }

    if ((addon_installed('commandr')) && (!running_script('install'))) {
        require_code('resource_fs');
        expunge_resource_fs_moniker('topic', strval($topic_id));
    }

    $GLOBALS['SITE_DB']->query_update('url_id_monikers', array('m_deprecated' => 1), array('m_resource_page' => 'topicview', 'm_resource_type' => 'browse', 'm_resource_id' => strval($topic_id)));

    require_code('sitemap_xml');
    notify_sitemap_node_delete('SEARCH:topicview:id=' . strval($topic_id));

    return $forum_id;
}

/**
 * Move some topics.
 *
 * @param  AUTO_LINK $from The forum the topics are currently in
 * @param  AUTO_LINK $to The forum the topics are being moved to
 * @param  ?array $topics A list of the topic IDs to move (null: move all topics from source forum)
 * @param  boolean $check_perms Whether to check permissions
 */
function cns_move_topics($from, $to, $topics = null, $check_perms = true) // NB: From is good to add a additional security/integrity. We'll never move from more than one forum. Extra constraints that cause no harm are good in a situation that doesn't govern general efficiency.
{
    if ($from == $to) {
        return; // That would be nuts, and interfere with our logic
    }

    require_code('notifications');
    require_code('cns_topics');
    require_code('cns_forums_action2');

    $forum_name = cns_ensure_forum_exists($to);

    if ($check_perms) {
        require_code('cns_forums');
        if (!cns_may_moderate_forum($from)) {
            access_denied('I_ERROR');
        }
    }

    $topic_count = 0;

    if ($topics === null) { // All of them
        if ($from === null) {
            access_denied('I_ERROR');
        }

        $all_topics = $GLOBALS['FORUM_DB']->query_select('f_topics', array('id', 't_cache_num_posts', 't_validated'), array('t_forum_id' => $from));
        $or_list = '';
        $post_count = 0;
        $topics = array();
        foreach ($all_topics as $topic_info) {
            $topics[] = $topic_info['id'];
            if ($or_list != '') {
                $or_list .= ' OR ';
            }
            $or_list .= 'id=' . strval($topic_info['id']);
            $post_count += $topic_info['t_cache_num_posts'];
            if ($topic_info['t_validated'] == 1) {
                $topic_count++;
            }
        }

        $GLOBALS['FORUM_DB']->query_update('f_topics', array('t_forum_id' => $to), array('t_forum_id' => $from));

        // Update forum IDs' for posts
        $GLOBALS['FORUM_DB']->query_update('f_posts', array('p_cache_forum_id' => $to), array('p_cache_forum_id' => $from));

        $or_list_2 = str_replace('id', 'p_topic_id', $or_list);
        if ($or_list_2 == '') {
            return;
        }
    } elseif (count($topics) == 1) { // Just one
        $topic_info = $GLOBALS['FORUM_DB']->query_select('f_topics', array('t_forum_id', 't_pt_from', 't_pt_to', 't_cache_first_title', 't_cache_num_posts', 't_validated'), array('id' => $topics[0]));
        if (!array_key_exists(0, $topic_info)) {
            warn_exit(do_lang_tempcode('MISSING_RESOURCE', 'topic'));
        }
        if (($topic_info[0]['t_forum_id'] != $from) || ((($topic_info[0]['t_pt_from'] != get_member()) && ($topic_info[0]['t_pt_to'] != get_member())) && (!cns_has_special_pt_access($topics[0])) && (!has_privilege(get_member(), 'view_other_pt')) && ($topic_info[0]['t_forum_id'] === null))) {
            access_denied('I_ERROR');
        }
        if ($topic_info[0]['t_validated'] == 1) {
            $topic_count++;
        }
        $topic_title = $topic_info[0]['t_cache_first_title'];
        $post_count = $topic_info[0]['t_cache_num_posts'];
        $GLOBALS['FORUM_DB']->query_update('f_topics', array('t_pt_from' => null, 't_pt_to' => null, 't_forum_id' => $to), array('t_forum_id' => $from, 'id' => $topics[0]), '', 1); // Extra where constraint for added security
        log_it('MOVE_TOPICS', $topic_title, strval($topics[0]));
        $or_list = 'id=' . strval($topics[0]);
        $or_list_2 = 'p_topic_id=' . strval($topics[0]);

        // Update forum IDs' for posts
        $GLOBALS['FORUM_DB']->query_update('f_posts', array('p_cache_forum_id' => $to), array('p_topic_id' => $topics[0]));
    } else { // Unknown number
        if (count($topics) == 0) {
            return; // Nuts, lol
        }

        $or_list = '';
        foreach ($topics as $topic_id) {
            if ($or_list != '') {
                $or_list .= ' OR ';
            }
            $or_list .= 'id=' . strval($topic_id);

            if ($from === null) {
                $topic_info = $GLOBALS['FORUM_DB']->query_select('f_topics', array('t_forum_id', 't_pt_from', 't_pt_to'), array('id' => $topic_id));
                if (array_key_exists(0, $topic_info)) {
                    if ($topic_info[0]['t_validated'] == 1) {
                        $topic_count++;
                    }

                    if (($topic_info[0]['t_forum_id'] != $from) || ((($topic_info[0]['t_pt_from'] != get_member()) && ($topic_info[0]['t_pt_to'] != get_member())) && (!cns_has_special_pt_access($topic_id)) && (!has_privilege(get_member(), 'view_other_pt')))) {
                        access_denied('I_ERROR');
                    }
                }
            } else {
                $topic_count++; // Might not be validated, which means technically we shouldn't do this, but it's low chance, low impact, and the indicator is only a cache thing anyway
            }
        }

        $GLOBALS['FORUM_DB']->query('UPDATE ' . $GLOBALS['FORUM_DB']->get_table_prefix() . 'f_topics SET t_forum_id=' . strval($to) . ',t_pt_from=NULL,t_pt_to=NULL WHERE t_forum_id' . (($from === null) ? ' IS NULL' : ('=' . strval($from))) . ' AND (' . $or_list . ')', null, 0, false, true);
        log_it('MOVE_TOPICS', do_lang('MULTIPLE'));

        $post_count = $GLOBALS['FORUM_DB']->query_value_if_there('SELECT SUM(t_cache_num_posts) FROM ' . $GLOBALS['FORUM_DB']->get_table_prefix() . 'f_topics WHERE ' . $or_list, false, true);

        // Update forum IDs' for posts
        $or_list_2 = str_replace('id', 'p_topic_id', $or_list);
        $GLOBALS['FORUM_DB']->query('UPDATE ' . $GLOBALS['FORUM_DB']->get_table_prefix() . 'f_posts SET p_cache_forum_id=' . strval($to) . ' WHERE ' . $or_list_2, null, 0, false, true);
    }

    require_code('cns_posts_action2');

    // Update source forum cache view
    if ($from !== null) {
        cns_force_update_forum_caching($from, -$topic_count, -$post_count);
    }

    // Update dest forum cache view
    cns_force_update_forum_caching($to, $topic_count, $post_count);

    if ($from !== null) {
        // Update member post counts if we've switched between post-count countable forums
        $post_count_info = $GLOBALS['FORUM_DB']->query('SELECT id,f_post_count_increment FROM ' . $GLOBALS['FORUM_DB']->get_table_prefix() . 'f_forums WHERE id=' . strval($from) . ' OR id=' . strval($to), 2);
        if ($post_count_info[0]['id'] == $from) {
            $from_cnt = $post_count_info[0]['f_post_count_increment'];
            $to_cnt = $post_count_info[1]['f_post_count_increment'];
        } else {
            $from_cnt = $post_count_info[1]['f_post_count_increment'];
            $to_cnt = $post_count_info[0]['f_post_count_increment'];
        }
        require_code('cns_posts_action');
        if ($from_cnt != $to_cnt) {
            $sql = 'SELECT p_poster FROM ' . $GLOBALS['FORUM_DB']->get_table_prefix() . 'f_posts WHERE (' . $or_list_2 . ')';
            if (addon_installed('unvalidated')) {
                $sql .= ' AND p_validated=1';
            }
            $_member_post_counts = collapse_1d_complexity('p_poster', $GLOBALS['FORUM_DB']->query($sql, null, 0, false, true));
            $member_post_counts = array_count_values($_member_post_counts);

            foreach ($member_post_counts as $member_id => $member_post_count) {
                if ($to == 0) {
                    $member_post_count = -$member_post_count;
                }
                cns_force_update_member_post_count($member_id, $member_post_count);
            }
        }
    }

    require_code('cns_posts_action');
    if ($from !== null) {
        cns_decache_cms_blocks($from);
    } else {
        if (count($topics) == 1) {
            decache_private_topics($topic_info[0]['t_pt_from']);
            decache_private_topics($topic_info[0]['t_pt_to']);
        } else {
            decache_private_topics();
        }
    }
    cns_decache_cms_blocks($to, $forum_name);

    require_code('tasks');
    call_user_func_array__long_task(do_lang('MOVE_TOPICS'), get_screen_title('MOVE_TOPICS'), 'notify_topics_moved', array($or_list, $forum_name), false, false, false);
}

/**
 * Invite a member to a PT.
 *
 * @param  MEMBER $member_id Member getting access
 * @param  AUTO_LINK $topic_id The topic
 */
function cns_invite_to_pt($member_id, $topic_id)
{
    $topic_info = $GLOBALS['FORUM_DB']->query_select('f_topics', array('*'), array('id' => $topic_id), '', 1);
    if (!array_key_exists(0, $topic_info)) {
        warn_exit(do_lang_tempcode('MISSING_RESOURCE', 'topic'));
    }

    if (($topic_info[0]['t_pt_from'] != get_member()) && ($topic_info[0]['t_pt_to'] != get_member()) && (!has_privilege(get_member(), 'view_other_pt'))) {
        warn_exit(do_lang_tempcode('INTERNAL_ERROR'));
    }

    if (($topic_info[0]['t_pt_from'] == $member_id) || ($topic_info[0]['t_pt_to'] == $member_id)) {
        warn_exit(do_lang_tempcode('NO_INVITE_SENSE'));
    }

    $test = $GLOBALS['FORUM_DB']->query_select_value_if_there('f_special_pt_access', 's_member_id', array(
        's_member_id' => $member_id,
        's_topic_id' => $topic_id,
    ));
    if ($test !== null) {
        warn_exit(do_lang_tempcode('NO_INVITE_SENSE_ALREADY'));
    }
    $GLOBALS['FORUM_DB']->query_insert('f_special_pt_access', array(
        's_member_id' => $member_id,
        's_topic_id' => $topic_id,
    ));

    $current_displayname = $GLOBALS['FORUM_DRIVER']->get_username(get_member(), true);
    $current_username = $GLOBALS['FORUM_DRIVER']->get_username(get_member());
    $displayname = $GLOBALS['FORUM_DRIVER']->get_username($member_id, true);
    $username = $GLOBALS['FORUM_DRIVER']->get_username($member_id);

    $_topic_url = build_url(array('page' => 'topicview', 'type' => 'view', 'id' => $topic_id), get_module_zone('topicview'), array(), false, false, true);
    $topic_url = $_topic_url->evaluate();
    $topic_title = $topic_info[0]['t_cache_first_title'];

    require_code('cns_posts_action');
    $post = do_lang('INVITED_TO_PT', $username, $current_displayname, $current_username, $displayname);
    cns_make_post($topic_id, '', $post, 0, false, 1, 1, do_lang('SYSTEM'), null, null, db_get_first_id(), null, null, null, false);

    require_code('notifications');
    $subject = do_lang('INVITED_TO_TOPIC_SUBJECT', get_site_name(), $topic_title, get_lang($member_id));
    $mail = do_notification_lang('INVITED_TO_TOPIC_BODY', get_site_name(), comcode_escape($topic_title), array(comcode_escape($current_username), $topic_url), get_lang($member_id));
    dispatch_notification('cns_topic_invite', null, $subject, $mail, array($member_id));
}

/**
 * Send a new-PT notification.
 *
 * @param  AUTO_LINK $post_id The ID of the post made
 * @param  SHORT_TEXT $subject PT title
 * @param  AUTO_LINK $topic_id ID of the topic
 * @param  MEMBER $to_id Member getting the PT
 * @param  ?MEMBER $from_id Member posting the PT (null: current member)
 * @param  ?string $post_comcode Post text (null: unknown, lookup from $post_id)
 * @param  boolean $mark_unread Whether to also mark the topic as unread
 */
function send_pt_notification($post_id, $subject, $topic_id, $to_id, $from_id = null, $post_comcode = null, $mark_unread = false)
{
    if ($from_id === null) {
        $from_id = get_member();
    }

    if ($post_comcode === null) {
        $post_comcode = get_translated_text($GLOBALS['FORUM_DB']->query_select_value('f_posts', 'p_post', array('id' => $post_id)), $GLOBALS['FORUM_DB']);
    }

    $emphasised = ($GLOBALS['FORUM_DB']->query_select_value('f_posts', 'p_is_emphasised', array('id' => $post_id)) == 1);

    require_code('notifications');
    $msubject = do_lang('NEW_PRIVATE_TOPIC_SUBJECT', $subject, null, null, get_lang($to_id));
    $mmessage = do_notification_lang('NEW_PRIVATE_TOPIC_MESSAGE', comcode_escape($GLOBALS['FORUM_DRIVER']->get_username($from_id, true)), comcode_escape($subject), array(comcode_escape($GLOBALS['FORUM_DRIVER']->topic_url($topic_id)), $post_comcode, strval($from_id)), get_lang($to_id));
    dispatch_notification('cns_new_pt', null, $msubject, $mmessage, array($to_id), $from_id, array('priority' => $emphasised ? 1 : 3));

    if ($mark_unread) {
        $GLOBALS['FORUM_DB']->query_delete('f_read_logs', array('l_topic_id' => $topic_id, 'l_member_id' => $to_id), '', 1);
    }
}

/**
 * If necessary, send out a support ticket reply.
 *
 * @param  ?AUTO_LINK $forum_id Forum ID (null: private topics)
 * @param  AUTO_LINK $topic_id Topic ID
 * @param  SHORT_TEXT $topic_title Topic title
 * @param  LONG_TEXT $post Post made
 */
function handle_topic_ticket_reply($forum_id, $topic_id, $topic_title, $post)
{
    // E-mail the user or staff if the post is a new one in a support ticket
    if (addon_installed('tickets')) {
        require_lang('tickets');
        require_code('tickets');
        require_code('tickets2');
        require_code('feedback');
        if (is_ticket_forum($forum_id)) {
            $topic_info = $GLOBALS['FORUM_DB']->query_select('f_topics', array('t_cache_first_title', 't_forum_id', 't_is_open', 't_description'), array('id' => $topic_id), '', 1);
            if (!array_key_exists(0, $topic_info)) {
                warn_exit(do_lang_tempcode('MISSING_RESOURCE', 'topic'));
            }

            $topic_description = $topic_info[0]['t_description'];
            $ticket_id = extract_topic_identifier($topic_description);
            $ticket_url = ticket_url($ticket_id);

            send_ticket_email($ticket_id, $topic_title, $post, $ticket_url);
        }
    }
}
