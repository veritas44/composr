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
 * Hook class.
 */
class Hook_cleanup_cns_members
{
    /**
     * Find details about this cleanup hook.
     *
     * @return ?array Map of cleanup hook info (null: hook is disabled)
     */
    public function info()
    {
        if (get_forum_type() != 'cns') {
            return null;
        } else {
            cns_require_all_forum_stuff();
        }

        if (($GLOBALS['FORUM_DB']->query_select_value('f_members', 'COUNT(*)') > 5000) && ($GLOBALS['FORUM_DB']->query_select_value('f_members', 'MAX(m_cache_num_posts)') > 50)) { // Too much work, unless we have due to an obvious issue
            return null;
        }

        require_lang('cns');

        $info = array();
        $info['title'] = do_lang_tempcode('MEMBERS');
        $info['description'] = do_lang_tempcode('DESCRIPTION_CACHE_MEMBERS');
        $info['type'] = 'cache';

        return $info;
    }

    /**
     * Run the cleanup hook action.
     *
     * @return Tempcode Results
     */
    public function run()
    {
        if (get_forum_type() != 'cns') {
            return new Tempcode();
        }

        require_lang('cns');

        require_code('tasks');
        return call_user_func_array__long_task(do_lang('CACHE_MEMBERS'), null, 'cns_members_recache');
    }
}
