<?php /*

 Composr
 Copyright (c) ocProducts, 2004-2018

 See text/EN/licence.txt for full licensing information.


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
class Hook_cleanup_orphaned_tags
{
    /**
     * Find details about this cleanup hook.
     *
     * @return ?array Map of cleanup hook info (null: hook is disabled)
     */
    public function info()
    {
        $info = array();
        $info['title'] = do_lang_tempcode('ORPHANED_TAGS');
        $info['description'] = do_lang_tempcode('DESCRIPTION_ORPHANED_TAGS');
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
        $hooks = find_all_hook_obs('systems', 'content_meta_aware', 'Hook_content_meta_aware_');
        foreach ($hooks as $hook => $ob) {
            $info = $ob->info();
            if ($info === null) {
                continue;
            }

            $seo_type_code = $info['seo_type_code'];
            if ($seo_type_code !== null) {
                $table = $info['table'];

                $id_field = $info['id_field'];

                if (($table == 'comcode_pages') || (is_array($id_field))) {
                    continue; // Can't handle these cases
                }

                $sql = 'SELECT m.* FROM ' . get_table_prefix() . 'seo_meta m';
                $sql .= ' LEFT JOIN ' . get_table_prefix() . $table . ' r ON ' . db_cast('r.' . $id_field, 'CHAR') . '=m.meta_for_id AND ' . db_string_equal_to('m.meta_for_type', $seo_type_code);
                $sql .= ' WHERE r.' . $id_field . ' IS NULL AND ' . db_string_equal_to('m.meta_for_type', $seo_type_code);
                $db = get_db_for($table);
                $orphaned = $db->query($sql);
                if (count($orphaned) != 0) {
                    foreach ($orphaned as $o) {
                        $keywords = $GLOBALS['SITE_DB']->query_select('seo_meta_keywords', array('meta_keyword'), array('meta_for_type' => $o['meta_for_type'], 'meta_for_id' => $o['meta_for_id']));
                        foreach ($keywords as $k) {
                            delete_lang($k['meta_keyword']);
                        }
                        $GLOBALS['SITE_DB']->query_delete('seo_meta_keywords', array('meta_for_type' => $o['meta_for_type'], 'meta_for_id' => $o['meta_for_id']));

                        delete_lang($o['meta_description']);
                        $GLOBALS['SITE_DB']->query_delete('seo_meta', array('meta_for_type' => $o['meta_for_type'], 'meta_for_id' => $o['meta_for_id']), '', 1);
                    }
                }
            }
        }

        delete_cache_entry('side_tag_cloud');

        return new Tempcode();
    }
}