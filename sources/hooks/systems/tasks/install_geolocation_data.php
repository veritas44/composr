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
 * @package    stats
 */

/**
 * Hook class.
 */
class Hook_task_install_geolocation_data
{
    /**
     * Run the task hook.
     *
     * @return ?array A tuple of at least 2: Return mime-type, content (either Tempcode, or a string, or a filename and file-path pair to a temporary file), map of HTTP headers if transferring immediately, map of ini_set commands if transferring immediately (null: show standard success message)
     */
    public function run()
    {
        push_query_limiting(false);

        $test = $GLOBALS['SITE_DB']->query_select_value_if_there('ip_country', 'id');
        if ($test !== null) {
            return null;
        }

        // We need to read in IP_Country.txt, line-by-line...

        $path = get_file_base() . '/data/modules/admin_stats/IP_Country.txt';
        $file = @fopen($path, 'rb');
        if ($file === false) {
            warn_exit(do_lang_tempcode('READ_ERROR', escape_html($path)), false, true);
        }
        $to_insert = array('begin_num' => array(), 'end_num' => array(), 'country' => array());
        while (!feof($file)) {
            $data = fgets($file);
            if ($data === false) {
                continue;
            }

            $_data = explode(',', $data);
            if (count($_data) == 3) {
                $to_insert['begin_num'][] = $_data[0]; // FUDGE. Intentionally passes in as strings, to workaround problem in PHP integer sizes (can't store unsigned data type)
                $to_insert['end_num'][] = $_data[1];
                $to_insert['country'][] = substr($_data[2], 0, 2);

                if (count($to_insert['begin_num']) == 100) { // Batches of 100
                    $GLOBALS['SITE_DB']->query_insert('ip_country', $to_insert);
                    $to_insert = array('begin_num' => array(), 'end_num' => array(), 'country' => array());
                }
            }
        }
        fclose($file);

        if (count($to_insert['begin_num']) != 0) { // Final batch, if there is one
            $GLOBALS['SITE_DB']->query_insert('ip_country', $to_insert);
        }

        return null;
    }
}
