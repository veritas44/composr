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
 * @package    downloads
 */

/**
 * Hook class.
 */
class Hook_task_index_download
{
    /**
     * Run the task hook.
     *
     * @param  AUTO_LINK $id The download ID
     * @param  URLPATH $url The download file URL
     * @param  ID_TEXT $original_filename The download filename
     * @return ?array A tuple of at least 2: Return mime-type, content (either Tempcode, or a string, or a filename and file-path pair to a temporary file), map of HTTP headers if transferring immediately, map of ini_set commands if transferring immediately (null: show standard success message)
     */
    public function run($id, $url, $original_filename)
    {
        require_code('downloads');
        require_code('downloads2');

        $data_mash = ($url == '') ? '' : create_data_mash($url, null, get_file_extension($original_filename));

        $update_map = array(
            'download_data_mash' => $data_mash,
        );

        $GLOBALS['SITE_DB']->query_update('download_downloads', $update_map, array('id' => $id), '', 1);

        return null;
    }
}
