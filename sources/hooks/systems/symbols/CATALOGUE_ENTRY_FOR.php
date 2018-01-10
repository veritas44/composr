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
 * @package    core_fields
 */

/**
 * Hook class.
 */
class Hook_symbol_CATALOGUE_ENTRY_FOR
{
    /**
     * Run function for symbol hooks. Searches for tasks to perform.
     *
     * @param  array $param Symbol parameters
     * @return string Result
     */
    public function run($param)
    {
        $value = '';
        if ((array_key_exists(1, $param)) && ($param[1] != '') && (addon_installed('catalogues'))) {
            static $cache = array();
            if (isset($cache[$param[0]][$param[1]])) {
                return $cache[$param[0]][$param[1]];
            }

            require_code('fields');
            $entry_id = get_bound_content_entry($param[0], $param[1]);
            $value = ($entry_id === null) ? '' : strval($entry_id);
            $cache[$param[0]][$param[1]] = $value;
        }

        return $value;
    }
}
