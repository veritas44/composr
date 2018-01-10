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
 * @package    polls
 */

/**
 * Add a new poll to the database, then return the ID of the new entry.
 *
 * @param  SHORT_TEXT $question The question
 * @param  SHORT_TEXT $a1 The first choice
 * @range  1 max
 * @param  SHORT_TEXT $a2 The second choice
 * @range  1 max
 * @param  SHORT_TEXT $a3 The third choice (blank means not a choice)
 * @param  SHORT_TEXT $a4 The fourth choice (blank means not a choice)
 * @param  SHORT_TEXT $a5 The fifth choice (blank means not a choice)
 * @param  SHORT_TEXT $a6 The sixth choice (blank means not a choice)
 * @param  SHORT_TEXT $a7 The seventh choice (blank means not a choice)
 * @param  SHORT_TEXT $a8 The eighth choice (blank means not a choice)
 * @param  SHORT_TEXT $a9 The ninth choice (blank means not a choice)
 * @param  SHORT_TEXT $a10 The tenth choice (blank means not a choice)
 * @param  ?integer $num_options The number of choices (null: calculate)
 * @range  2 5
 * @param  BINARY $current Whether the poll is the current poll
 * @param  BINARY $allow_rating Whether to allow rating of this poll
 * @param  SHORT_INTEGER $allow_comments Whether comments are allowed (0=no, 1=yes, 2=review style)
 * @param  BINARY $allow_trackbacks Whether to allow trackbacking on this poll
 * @param  LONG_TEXT $notes Notes about this poll
 * @param  ?TIME $time The time the poll was submitted (null: now)
 * @param  ?MEMBER $submitter The member who submitted (null: the current member)
 * @param  ?TIME $use_time The time the poll was put to use (null: not put to use yet)
 * @param  integer $v1 How many have voted for option 1
 * @range  0 max
 * @param  integer $v2 How many have voted for option 2
 * @range  0 max
 * @param  integer $v3 How many have voted for option 3
 * @range  0 max
 * @param  integer $v4 How many have voted for option 4
 * @range  0 max
 * @param  integer $v5 How many have voted for option 5
 * @range  0 max
 * @param  integer $v6 How many have voted for option 6
 * @range  0 max
 * @param  integer $v7 How many have voted for option 7
 * @range  0 max
 * @param  integer $v8 How many have voted for option 8
 * @range  0 max
 * @param  integer $v9 How many have voted for option 9
 * @range  0 max
 * @param  integer $v10 How many have voted for option 10
 * @range  0 max
 * @param  integer $views The number of views had
 * @param  ?TIME $edit_date The edit date (null: never)
 * @return AUTO_LINK The poll ID of our new poll
 */
function add_poll($question, $a1, $a2, $a3 = '', $a4 = '', $a5 = '', $a6 = '', $a7 = '', $a8 = '', $a9 = '', $a10 = '', $num_options = null, $current = 0, $allow_rating = 1, $allow_comments = 1, $allow_trackbacks = 1, $notes = '', $time = null, $submitter = null, $use_time = null, $v1 = 0, $v2 = 0, $v3 = 0, $v4 = 0, $v5 = 0, $v6 = 0, $v7 = 0, $v8 = 0, $v9 = 0, $v10 = 0, $views = 0, $edit_date = null)
{
    require_code('global4');
    prevent_double_submit('ADD_POLL', null, $question);

    if ($num_options === null) {
        $num_options = 2;
        if ($a3 != '') {
            $num_options++;
        }
        if ($a4 != '') {
            $num_options++;
        }
        if ($a5 != '') {
            $num_options++;
        }
        if ($a6 != '') {
            $num_options++;
        }
        if ($a7 != '') {
            $num_options++;
        }
        if ($a8 != '') {
            $num_options++;
        }
        if ($a9 != '') {
            $num_options++;
        }
        if ($a10 != '') {
            $num_options++;
        }
    }

    if ($current == 1) {
        persistent_cache_delete('POLL');
        $GLOBALS['SITE_DB']->query_update('poll', array('is_current' => 0), array('is_current' => 1), '', 1);
    }

    if ($time === null) {
        $time = time();
    }
    if ($submitter === null) {
        $submitter = get_member();
    }

    $map = array(
        'edit_date' => $edit_date,
        'poll_views' => $views,
        'add_time' => $time,
        'allow_trackbacks' => $allow_trackbacks,
        'allow_rating' => $allow_rating,
        'allow_comments' => $allow_comments,
        'notes' => $notes,
        'submitter' => $submitter,
        'date_and_time' => $use_time,
        'votes1' => $v1,
        'votes2' => $v2,
        'votes3' => $v3,
        'votes4' => $v4,
        'votes5' => $v5,
        'votes6' => $v6,
        'votes7' => $v7,
        'votes8' => $v8,
        'votes9' => $v9,
        'votes10' => $v10,
        'num_options' => $num_options,
        'is_current' => $current,
    );
    $map += insert_lang_comcode('question', $question, 1);
    $map += insert_lang_comcode('option1', $a1, 1);
    $map += insert_lang_comcode('option2', $a2, 1);
    $map += insert_lang_comcode('option3', $a3, 1);
    $map += insert_lang_comcode('option4', $a4, 1);
    $map += insert_lang_comcode('option5', $a5, 1);
    $map += insert_lang_comcode('option6', $a6, 1);
    $map += insert_lang_comcode('option7', $a7, 1);
    $map += insert_lang_comcode('option8', $a8, 1);
    $map += insert_lang_comcode('option9', $a9, 1);
    $map += insert_lang_comcode('option10', $a10, 1);
    $id = $GLOBALS['SITE_DB']->query_insert('poll', $map, true);

    log_it('ADD_POLL', strval($id), $question);

    require_code('member_mentions');
    dispatch_member_mention_notifications('poll', strval($id), $submitter);

    if ((addon_installed('commandr')) && (!running_script('install'))) {
        require_code('resource_fs');
        generate_resource_fs_moniker('poll', strval($id), null, null, true);
    }

    require_code('sitemap_xml');
    notify_sitemap_node_add('SEARCH:polls:view:' . strval($id), $time, $edit_date, SITEMAP_IMPORTANCE_LOW, 'yearly', true);

    return $id;
}

/**
 * Edit a poll.
 *
 * @param  AUTO_LINK $id The ID of the poll to edit
 * @param  SHORT_TEXT $question The question
 * @param  SHORT_TEXT $a1 The first choice
 * @range  1 max
 * @param  SHORT_TEXT $a2 The second choice
 * @range  1 max
 * @param  SHORT_TEXT $a3 The third choice (blank means not a choice)
 * @param  SHORT_TEXT $a4 The fourth choice (blank means not a choice)
 * @param  SHORT_TEXT $a5 The fifth choice (blank means not a choice)
 * @param  SHORT_TEXT $a6 The sixth choice (blank means not a choice)
 * @param  SHORT_TEXT $a7 The seventh choice (blank means not a choice)
 * @param  SHORT_TEXT $a8 The eighth choice (blank means not a choice)
 * @param  SHORT_TEXT $a9 The ninth choice (blank means not a choice)
 * @param  SHORT_TEXT $a10 The tenth choice (blank means not a choice)
 * @param  integer $num_options The number of choices
 * @param  BINARY $allow_rating Whether to allow rating of this poll
 * @param  SHORT_INTEGER $allow_comments Whether comments are allowed (0=no, 1=yes, 2=review style)
 * @param  BINARY $allow_trackbacks Whether to allow trackbacking on this poll
 * @param  LONG_TEXT $notes Notes about this poll
 * @param  ?TIME $edit_time Edit time (null: either means current time, or if $null_is_literal, means reset to to null)
 * @param  ?TIME $add_time Add time (null: do not change)
 * @param  ?integer $views Number of views (null: do not change)
 * @param  ?MEMBER $submitter Submitter (null: do not change)
 * @param  boolean $null_is_literal Determines whether some nulls passed mean 'use a default' or literally mean 'set to null'
 */
function edit_poll($id, $question, $a1, $a2, $a3, $a4, $a5, $a6, $a7, $a8, $a9, $a10, $num_options, $allow_rating, $allow_comments, $allow_trackbacks, $notes, $edit_time = null, $add_time = null, $views = null, $submitter = null, $null_is_literal = false)
{
    if ($edit_time === null) {
        $edit_time = $null_is_literal ? null : time();
    }

    log_it('EDIT_POLL', strval($id), $question);

    if ((addon_installed('commandr')) && (!running_script('install'))) {
        require_code('resource_fs');
        generate_resource_fs_moniker('poll', strval($id));
    }

    persistent_cache_delete('POLL');

    $rows = $GLOBALS['SITE_DB']->query_select('poll', array('*'), array('id' => $id), '', 1);
    $_question = $rows[0]['question'];
    $_a1 = $rows[0]['option1'];
    $_a2 = $rows[0]['option2'];
    $_a3 = $rows[0]['option3'];
    $_a4 = $rows[0]['option4'];
    $_a5 = $rows[0]['option5'];
    $_a6 = $rows[0]['option6'];
    $_a7 = $rows[0]['option7'];
    $_a8 = $rows[0]['option8'];
    $_a9 = $rows[0]['option9'];
    $_a10 = $rows[0]['option10'];

    $update_map = array(
        'allow_rating' => $allow_rating,
        'allow_comments' => $allow_comments,
        'allow_trackbacks' => $allow_trackbacks,
        'notes' => $notes,
        'num_options' => $num_options,
    );
    $update_map += lang_remap_comcode('question', $_question, $question);
    $update_map += lang_remap_comcode('option1', $_a1, $a1);
    $update_map += lang_remap_comcode('option2', $_a2, $a2);
    $update_map += lang_remap_comcode('option3', $_a3, $a3);
    $update_map += lang_remap_comcode('option4', $_a4, $a4);
    $update_map += lang_remap_comcode('option5', $_a5, $a5);
    $update_map += lang_remap_comcode('option6', $_a6, $a6);
    $update_map += lang_remap_comcode('option7', $_a7, $a7);
    $update_map += lang_remap_comcode('option8', $_a8, $a8);
    $update_map += lang_remap_comcode('option9', $_a9, $a9);
    $update_map += lang_remap_comcode('option10', $_a10, $a10);

    $update_map['edit_date'] = $edit_time;
    if ($add_time !== null) {
        $update_map['add_time'] = $add_time;
    }
    if ($views !== null) {
        $update_map['poll_views'] = $views;
    }
    if ($submitter !== null) {
        $update_map['submitter'] = $submitter;
    }

    $GLOBALS['SITE_DB']->query_update('poll', $update_map, array('id' => $id), '', 1);
    persistent_cache_delete('POLL');
    delete_cache_entry('main_poll');

    require_code('urls2');
    suggest_new_idmoniker_for('polls', 'view', strval($id), '', $question);

    require_code('feedback');
    update_spacer_post(
        $allow_comments != 0,
        'polls',
        strval($id),
        build_url(array('page' => 'polls', 'type' => 'view', 'id' => $id), get_module_zone('polls'), array(), false, false, true),
        $question,
        find_overridden_comment_forum('polls')
    );

    require_code('sitemap_xml');
    notify_sitemap_node_edit('SEARCH:polls:view:' . strval($id), true);
}

/**
 * Delete a poll.
 *
 * @param  AUTO_LINK $id The ID of the poll to delete
 */
function delete_poll($id)
{
    $rows = $GLOBALS['SITE_DB']->query_select('poll', array('*'), array('id' => $id), '', 1);

    persistent_cache_delete('POLL');

    if (addon_installed('catalogues')) {
        update_catalogue_content_ref('poll', strval($id), '');
    }

    $question = get_translated_text($rows[0]['question']);

    delete_lang($rows[0]['question']);
    for ($i = 1; $i <= 10; $i++) {
        delete_lang($rows[0]['option' . strval($i)]);
    }

    $GLOBALS['SITE_DB']->query_delete('rating', array('rating_for_type' => 'polls', 'rating_for_id' => strval($id)));
    $GLOBALS['SITE_DB']->query_delete('trackbacks', array('trackback_for_type' => 'polls', 'trackback_for_id' => strval($id)));
    require_code('notifications');
    delete_all_notifications_on('comment_posted', 'polls_' . strval($id));

    $GLOBALS['SITE_DB']->query_delete('poll', array('id' => $id), '', 1);

    log_it('DELETE_POLL', strval($id), $question);

    if ((addon_installed('commandr')) && (!running_script('install'))) {
        require_code('resource_fs');
        expunge_resource_fs_moniker('poll', strval($id));
    }

    require_code('sitemap_xml');
    notify_sitemap_node_delete('SEARCH:polls:view:' . strval($id));
}

/**
 * Set the poll.
 *
 * @param  AUTO_LINK $id The poll ID to set
 */
function set_poll($id)
{
    $rows = $GLOBALS['SITE_DB']->query_select('poll', array('question', 'submitter'), array('id' => $id));
    $question = $rows[0]['question'];
    $submitter = $rows[0]['submitter'];

    log_it('CHOOSE_POLL', strval($id), get_translated_text($question));

    require_code('users2');
    if (has_actual_page_access(get_modal_user(), 'polls')) {
        require_code('activities');
        syndicate_described_activity('polls:ACTIVITY_CHOOSE_POLL', get_translated_text($question), '', '', '_SEARCH:polls:view:' . strval($id), '', '', 'polls');
    }

    if ((!is_guest($submitter)) && (addon_installed('points'))) {
        require_code('points2');
        $points_chosen = intval(get_option('points_CHOOSE_POLL'));
        if ($points_chosen != 0) {
            system_gift_transfer(do_lang('POLL'), $points_chosen, $submitter);
        }
    }

    $GLOBALS['SITE_DB']->query_update('poll', array('is_current' => 0), array('is_current' => 1));
    $GLOBALS['SITE_DB']->query_update('poll', array('is_current' => 1, 'date_and_time' => time()), array('id' => $id), '', 1);

    delete_cache_entry('main_poll');
    persistent_cache_delete('POLL');

    require_lang('polls');
    require_code('notifications');
    $subject = do_lang('POLL_CHOSEN_NOTIFICATION_MAIL_SUBJECT', get_site_name(), $question);
    $poll_url = build_url(array('page' => 'polls', 'type' => 'view', 'id' => $id), get_module_zone('polls'), array(), false, false, true);
    $mail = do_notification_lang('POLL_CHOSEN_NOTIFICATION_MAIL', comcode_escape(get_site_name()), comcode_escape(get_translated_text($question)), $poll_url->evaluate());
    dispatch_notification('poll_chosen', null, $subject, $mail);
}
