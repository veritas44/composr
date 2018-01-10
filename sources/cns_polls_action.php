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
 * @package    core_cns
 */

/**
 * Add a forum poll.
 *
 * @param  AUTO_LINK $topic_id The ID of the topic to add the poll to
 * @param  SHORT_TEXT $question The question
 * @param  BINARY $is_private Whether the result tallies are kept private until the poll is made non-private
 * @param  BINARY $is_open Whether the poll is open for voting
 * @param  integer $minimum_selections The minimum number of selections that may be made
 * @param  integer $maximum_selections The maximum number of selections that may be made
 * @param  BINARY $requires_reply Whether members must have a post in the topic before they made vote
 * @param  array $answers A list of pairs of the potential voteable answers and the number of votes
 * @param  boolean $check_permissions Whether to check there are permissions to make the poll
 * @return AUTO_LINK The ID of the newly created forum poll
 */
function cns_make_poll($topic_id, $question, $is_private, $is_open, $minimum_selections, $maximum_selections, $requires_reply, $answers, $check_permissions = true)
{
    require_code('cns_polls');

    if (($check_permissions) && (!cns_may_attach_poll($topic_id))) {
        access_denied('I_ERROR');
    }

    $poll_id = $GLOBALS['FORUM_DB']->query_insert('f_polls', array(
        'po_question' => $question,
        'po_cache_total_votes' => 0,
        'po_is_private' => $is_private,
        'po_is_open' => $is_open,
        'po_minimum_selections' => $minimum_selections,
        'po_maximum_selections' => $maximum_selections,
        'po_requires_reply' => $requires_reply,
    ), true);

    foreach ($answers as $answer) {
        if (is_array($answer)) {
            list($answer, $num_votes) = $answer;
        } else {
            $num_votes = 0;
        }

        $GLOBALS['FORUM_DB']->query_insert('f_poll_answers', array(
            'pa_poll_id' => $poll_id,
            'pa_answer' => $answer,
            'pa_cache_num_votes' => $num_votes,
        ));
    }

    $map = array('t_poll_id' => $poll_id);

    // Now make the topic validated if this is attaching immediately
    if (get_param_integer('re_validate', 0) == 1) {
        $forum_id = $GLOBALS['FORUM_DB']->query_select_value('f_topics', 't_forum_id', array('id' => $topic_id));

        if (($forum_id === null) || (has_privilege(get_member(), 'bypass_validation_midrange_content', 'topics', array('forums', $forum_id)))) {
            $map['t_validated'] = 1;
        }
    }

    $GLOBALS['FORUM_DB']->query_update('f_topics', $map, array('id' => $topic_id), '', 1);

    return $poll_id;
}
