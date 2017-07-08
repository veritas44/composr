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
 * @package    chat
 */

/**
 * Block class.
 */
class Block_side_friends
{
    /**
     * Find details of the block.
     *
     * @return ?array Map of block info (null: block is disabled)
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
        $info['parameters'] = array('max');
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
        if (is_guest()) {
            return new Tempcode(); // Guest has no friends
        }

        if ((get_page_name() == 'chat') && (get_param_string('type', 'browse') == 'browse')) { // Don't want to show if actually on chat lobby, which already has this functionality
            return new Tempcode();
        }

        require_code('chat');
        require_code('chat_lobby');
        require_lang('chat');
        require_css('chat');
        require_javascript('chat');

        $block_id = get_block_id($map);

        $max = array_key_exists('max', $map) ? intval($map['max']) : 15;

        $friends = show_im_contacts(null, true, $max);

        return do_template('BLOCK_SIDE_FRIENDS', array(
            '_GUID' => 'ce94db14f9a212f38d0fce1658866e2c',
            'BLOCK_ID' => $block_id,
            'FRIENDS' => $friends,
        ));
    }
}
