<?php /*

 Composr
 Copyright (c) ocProducts, 2004-2015

 See text/EN/licence.txt for full licencing information.

*/

/**
 * @license    http://opensource.org/licenses/cpal_1.0 Common Public Attribution License
 * @copyright  ocProducts Ltd
 * @package    cns_tapatalk
 */

define('TAPATALK_MESSAGE_BOX_INBOX', 1);
define('TAPATALK_MESSAGE_BOX_SENT', 2);

/**
 * Get number of unread private topics in a particular "box type".
 * This is not a normal Composr view, but Tapatalk is designed like this.
 *
 * @param  ?integer $box_type Message box type, a TAPATALK_MESSAGE_BOX_* constant (null: don't care)
 * @return integer Number of topics
 */
function get_num_unread_private_topics($box_type = null)
{
    $member_id = get_member();

    $table_prefix = $GLOBALS['FORUM_DB']->get_table_prefix();

    $sql = 'SELECT COUNT(*) FROM ' . $GLOBALS['FORUM_DB']->get_table_prefix() . 'f_topics t';
    $sql .= ' LEFT JOIN ' . $GLOBALS['FORUM_DB']->get_table_prefix() . 'f_read_logs r ON t.id=r.l_topic_id AND l_member_id=' . strval($member_id);
    $sql .= ' WHERE';
    $sql .= ' t.t_forum_id IS NULL';
    if (addon_installed('unvalidated')) {
        $sql .= ' AND t_validated=1';
    }
    if ($box_type === TAPATALK_MESSAGE_BOX_INBOX) {
        $sql .= ' AND (t_pt_to=' . strval($member_id) . ' OR EXISTS(SELECT * FROM ' . $table_prefix . 'f_special_pt_access WHERE s_topic_id=t.id AND s_member_id=' . strval($member_id) . '))';
    } elseif ($box_type === TAPATALK_MESSAGE_BOX_SENT) {
        $sql .= ' AND t_pt_from=' . strval($member_id);
    } else {
        $sql .= ' AND (t_pt_from=' . strval($member_id) . ' OR t_pt_to=' . strval($member_id) . ' OR EXISTS(SELECT * FROM ' . $table_prefix . 'f_special_pt_access WHERE s_topic_id=t.id AND s_member_id=' . strval($member_id) . '))';
    }
    $sql .= ' AND (l_time IS NULL OR l_time<t_cache_last_time)'; // Cannot get join match OR gets one and it is behind of last post
    $sql .= ' AND t_cache_last_time>' . strval(time() - 60 * 60 * 24 * intval(get_option('post_history_days'))); // Within tracking range

    return $GLOBALS['FORUM_DB']->query_value_null_ok_full($sql);
}

