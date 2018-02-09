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
 * @package    chat
 */

/**
 * Block class.
 */
class Block_side_shoutbox
{
    /**
     * Find details of the block.
     *
     * @return ?array Map of block info (null: block is disabled)
     */
    public function info()
    {
        $info = array();
        $info['author'] = 'Philip Withnall';
        $info['organisation'] = 'ocProducts';
        $info['hacked_by'] = null;
        $info['hack_version'] = null;
        $info['version'] = 3;
        $info['locked'] = false;
        $info['parameters'] = array('param', 'max');
        return $info;
    }

    /**
     * Find caching details for the block.
     *
     * @return ?array Map of cache details (cache_on and ttl) (null: block is disabled)
     */
    public function caching_environment()
    {
        $info = array();
        $info['cache_on'] = '(count($_POST)!=0)?null:array(array_key_exists(\'max\',$map)?intval($map[\'max\']):5,array_key_exists(\'param\',$map)?intval($map[\'param\']):null)';
        $info['special_cache_flags'] = CACHE_AGAINST_DEFAULT | CACHE_AGAINST_PERMISSIVE_GROUPS;
        $info['ttl'] = (get_value('disable_block_timeout') === '1') ? 60 * 60 * 24 * 365 * 5/*5 year timeout*/ : 60 * 24;
        return $info;
    }

    /**
     * Execute the block.
     *
     * @param  array $map A map of parameters
     * @return Tempcode The result of execution
     */
    public function run($map)
    {
        require_lang('chat');
        require_css('chat');
        require_code('chat');

        $block_id = get_block_id($map);

        $room_id = empty($map['param']) ? null : intval($map['param']);
        $num_messages = array_key_exists('max', $map) ? intval($map['max']) : 5;

        if ($room_id === null) {
            $room_id = $GLOBALS['SITE_DB']->query_select_value_if_there('chat_rooms', 'MIN(id)', array('is_im' => 0/*, 'room_language' => user_lang()*/));
            if ($room_id === null) {
                return new Tempcode();
            }
        }

        $room_check = $GLOBALS['SITE_DB']->query_select('chat_rooms', array('*'), array('id' => $room_id), '', 1);
        if (!array_key_exists(0, $room_check)) {
            return new Tempcode();
        }
        require_code('chat');
        if (!check_chatroom_access($room_check[0], true)) {
            global $DO_NOT_CACHE_THIS; // We don't cache against access, so we have a problem and can't cache
            $DO_NOT_CACHE_THIS = true;

            return new Tempcode();
        }

        $last_message_id = $GLOBALS['SITE_DB']->query_select_value('chat_messages', 'MAX(id)', array('room_id' => $room_id));
        if ($last_message_id === null) {
            $last_message_id = -1;
        }

        $zone = get_module_zone('chat');

        if ($room_id === null) {
            $room_id = $GLOBALS['SITE_DB']->query_select_value_if_there('chat_rooms', 'MIN(id)', array('is_im' => 0, 'room_language' => user_lang()));
            if ($room_id === null) {
                $room_id = $GLOBALS['SITE_DB']->query_select_value_if_there('chat_rooms', 'MIN(id)', array('is_im' => 0));
            }
            if ($room_id === null) {
                return paragraph(do_lang_tempcode('NONE_EM'), '', 'nothing-here');
            }
        }

        $room_check = $GLOBALS['SITE_DB']->query_select('chat_rooms', array('*'), array('id' => $room_id), '', 1);
        if (!array_key_exists(0, $room_check)) {
            return paragraph(do_lang_tempcode('MISSING_RESOURCE', 'chat'), '', 'red-alert');
        }

        // Did a message get sent last time?
        $shoutbox_message = post_param_string('shoutbox_message', '');
        if ($shoutbox_message != '') {
            if (!chat_post_message($room_id, $shoutbox_message, get_option('chat_default_post_font'), get_option('chat_default_post_colour'))) {
                // Error. But actually we'll get it from below
            }
        }

        $messages = chat_get_room_content($room_id, $room_check, $num_messages * 3, false, false, null, null, -1, $zone, null, true, $shoutbox_message != '');
        $_tpl = array();
        foreach ($messages as $_message) {
            $evaluated = $_message['the_message']->evaluate();

            // We are only interested in private-message system messages and flood-control system messages, no other kinds of system message
            if (($_message['system_message'] == 1) && (strpos($evaluated, '[private') === false) && (preg_match('#' . str_replace('\{1\}', '\d+', preg_quote(do_lang('FLOOD_CONTROL_BLOCKED'))) . '#', $evaluated) == 0)) {
                continue;
            }

            if ((strpos($evaluated, '[private') === false) || (($shoutbox_message != '') && (strpos($evaluated, '[private="' . $GLOBALS['FORUM_DRIVER']->get_username(get_member()) . '"]') !== false))) {
                $member_id = $GLOBALS['FORUM_DRIVER']->get_member_from_username($_message['username']);
                $member_link = $GLOBALS['FORUM_DRIVER']->member_profile_hyperlink($member_id, $_message['username']);
                $_tpl[] = do_template('BLOCK_SIDE_SHOUTBOX_MESSAGE', array(
                    '_GUID' => 'a6f86aa48af7de7ec78423864c82c626',
                    'MEMBER' => $member_link,
                    'MESSAGE' => $_message['the_message'],
                    '_TIME' => strval($_message['date_and_time']),
                    'DATE' => $_message['date_and_time_nice'],
                ));
            }
        }

        $tpl = new Tempcode();
        while (count($_tpl) > $num_messages) {
            array_shift($_tpl);
        }
        foreach ($_tpl as $t) {
            $tpl->attach($t);
        }

        $url = get_self_url(false, false, array('room_id' => $room_id));

        return do_template('BLOCK_SIDE_SHOUTBOX', array(
            '_GUID' => 'dd737145479155961a1252162a43d4ef',
            'BLOCK_ID' => $block_id,
            'LAST_MESSAGE_ID' => strval($last_message_id),
            'MESSAGES' => $tpl,
            'URL' => $url,
            'CHATROOM_ID' => strval($room_id),
            'NUM_MESSAGES' => strval($num_messages),
            'BLOCK_PARAMS' => block_params_arr_to_str($map),
        ));
    }
}
