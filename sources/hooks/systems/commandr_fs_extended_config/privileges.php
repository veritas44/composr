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
 * @package    commandr
 */

/**
 * Hook class.
 */
class Hook_commandr_fs_extended_config__privileges
{
    /**
     * Standard Commandr-fs date fetch function for resource-fs hooks. Defined when getting an edit date is not easy.
     *
     * @return ?TIME The edit date or add date, whichever is higher (null: could not find one)
     */
    public function get_edit_date()
    {
        $query = 'SELECT MAX(date_and_time) FROM ' . get_table_prefix() . 'actionlogs WHERE ' . db_string_equal_to('the_type', 'PAGE_ACCESS') . ' OR ' . db_string_equal_to('the_type', 'PRIVILEGES');
        return $GLOBALS['SITE_DB']->query_value_if_there($query);
    }

    /**
     * Standard Commandr-fs file reading function for commandr_fs hooks.
     *
     * @param  array $meta_dir The current meta-directory path
     * @param  string $meta_root_node The root node of the current meta-directory
     * @param  string $file_name The file name
     * @param  object $commandr_fs A reference to the Commandr filesystem object
     * @return ~string The file contents (false: failure)
     */
    public function read_file($meta_dir, $meta_root_node, $file_name, &$commandr_fs)
    {
        require_code('resource_fs');

        $tables = array(
            'group_privileges' => array('category_name' => ''),
            'member_privileges' => array('category_name' => ''),
            'group_page_access' => array(),
            'member_page_access' => array(),
        );

        $all = array();
        foreach ($tables as $table => $map) {
            $all[$table] = table_to_portable_rows($table, array(), $map);
        }
        return json_encode($all);
    }

    /**
     * Standard Commandr-fs file writing function for commandr_fs hooks.
     *
     * @param  array $meta_dir The current meta-directory path
     * @param  string $meta_root_node The root node of the current meta-directory
     * @param  string $file_name The file name
     * @param  string $contents The new file contents
     * @param  object $commandr_fs A reference to the Commandr filesystem object
     * @return boolean Success?
     */
    public function write_file($meta_dir, $meta_root_node, $file_name, $contents, &$commandr_fs)
    {
        $all = @json_decode($contents, true);
        if ($all === false) {
            return false;
        }

        $tables = array(
            'group_privileges' => array('category_name' => ''),
            'member_privileges' => array('category_name' => ''),
            'group_page_access' => array(),
            'member_page_access' => array(),
        );

        $ret = true;
        foreach ($all as $table => $rows) {
            $ret = $ret && table_from_portable_rows($table, $rows, $tables[$table], TABLE_REPLACE_MODE_SEVERE);
        }
        return $ret;
    }
}
