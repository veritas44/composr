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
 * @package    core_cleanup_tools
 */

/**
 * Hook class.
 */
class Hook_cleanup_meta_tree_rebuild
{
    /**
     * Find details about this cleanup hook.
     *
     * @return ?array Map of cleanup hook info (null: hook is disabled)
     */
    public function info()
    {
        $info = array();
        $info['title'] = do_lang_tempcode('META_TREE_REBUILD');
        $info['description'] = do_lang_tempcode('DESCRIPTION_META_TREE_REBUILD');
        $info['type'] = 'optimise';

        return $info;
    }

    /**
     * Run the cleanup hook action.
     *
     * @return Tempcode Results
     */
    public function run()
    {
        require_code('tasks');

        call_user_func_array__long_task(do_lang('META_TREE_REBUILD'), get_screen_title('META_TREE_REBUILD'), 'meta_tree_rebuild');

        return new Tempcode();
    }
}
