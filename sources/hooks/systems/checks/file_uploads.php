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
 * Hook class.
 */
class Hook_check_file_uploads
{
    /**
     * Check various input var restrictions.
     *
     * @return array List of warnings
     */
    public function run()
    {
        $warning = array();

        if (ini_get('file_uploads') == '0') {
            $warning[] = do_lang_tempcode('NO_UPLOAD');
        }

        foreach (array('post_max_size', 'upload_max_filesize') as $setting) {
            $bytes = php_return_bytes(ini_get($setting));
            if ($bytes < 8000000) {
                $warning[] = do_lang_tempcode('PHP_UPLOAD_SETTING_VERY_LOW', escape_html($setting), escape_html(ini_get($setting)), escape_html(integer_format($bytes)));
            }
        }

        return $warning;
    }
}
