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
 * @package    core_cleanup_tools
 */

/**
 * Hook class.
 */
class Hook_cleanup_comcode
{
    /**
     * Find details about this cleanup hook.
     *
     * @return ?array Map of cleanup hook info (null: hook is disabled)
     */
    public function info()
    {
        if (multi_lang_content()) {
            if ($GLOBALS['SITE_DB']->query_select_value('translate', 'COUNT(*)') > 100000) {
                return null; // Too much work. Can be done from upgrader, but people won't go in there so much. People don't really need to go emptying this cache on real sites.
            }
        }

        $info = array();
        $info['title'] = do_lang_tempcode('COMCODE_CACHE');
        $info['description'] = do_lang_tempcode('DESCRIPTION_COMCODE_CACHE');
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
        erase_comcode_cache();

        return new Tempcode();
    }
}
