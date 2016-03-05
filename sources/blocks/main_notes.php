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
 * @package    core
 */

/**
 * Block class.
 */
class Block_main_notes
{
    /**
     * Find details of the block.
     *
     * @return ?array Map of block info (null: block is disabled).
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
        $info['parameters'] = array('param', 'title', 'lang_none', 'scrolls');
        return $info;
    }

    /**
     * Execute the block.
     *
     * @param  array $map A map of parameters.
     * @return Tempcode The result of execution.
     */
    public function run($map)
    {
        require_code('textfiles');

        $file = array_key_exists('param', $map) ? $map['param'] : 'admin_notes';
        $title = array_key_exists('title', $map) ? $map['title'] : do_lang('NOTES');
        $lang_none = array_key_exists('lang_none', $map) ? $map['lang_none'] : '0';
        $scrolls = array_key_exists('scrolls', $map) ? $map['scrolls'] : '0';
        $lang = ($lang_none == '1') ? null : '';

        $file = filter_naughty($file, true);

        $new = post_param_string('new', null);
        if (!is_null($new)) {
            $hooks = find_all_hooks('blocks', 'main_notes');
            foreach (array_keys($hooks) as $hook) {
                require_code('hooks/blocks/main_notes/' . filter_naughty_harsh($hook));
                $ob = object_factory('Hook_notes_' . filter_naughty_harsh($hook), true);
                if (is_null($ob)) {
                    continue;
                }
                $ob->run($file);
            }
            write_text_file($file, $lang, $new);
            log_it('NOTES', $file);

            attach_message(do_lang_tempcode('SUCCESS'), 'inform');
        }

        $contents = read_text_file($file, $lang, true);
        $post_url = get_self_url();

        $map_comcode = get_block_ajax_submit_map($map);
        return do_template('BLOCK_MAIN_NOTES', array('_GUID' => 'f737053505de3bd8ccfe806ec014b8fb', 'TITLE' => $title, 'BLOCK_NAME' => 'main_notes', 'MAP' => $map_comcode, 'CONTENTS' => $contents, 'SCROLLS' => array_key_exists('scrolls', $map) && ($map['scrolls'] == '1'), 'URL' => $post_url));
    }
}
