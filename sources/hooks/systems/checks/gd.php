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
class Hook_check_gd
{
    /**
     * Check various input var restrictions.
     *
     * @return array List of warnings
     */
    public function run()
    {
        $warning = array();
        if (!function_exists('imagecreatefromstring')) {
            $warning[] = do_lang_tempcode('NO_GD_ON_SERVER');
        } else {
            if ($this->get_gd_version() < 2.0) {
                $warning[] = do_lang_tempcode('OLD_GD_ON_SERVER');
            }

            if (!function_exists('imagepng')) {
                $warning[] = do_lang_tempcode('NO_GD_ON_SERVER_PNG');
            }
            if (!function_exists('imagejpeg')) {
                $warning[] = do_lang_tempcode('NO_GD_ON_SERVER_JPEG');
            }
            if (!function_exists('imagettfbbox')) {
                $warning[] = do_lang_tempcode('NO_GD_ON_SERVER_TTF');
            }
        }
        return $warning;
    }

    /**
     * Get the version number of GD on the system. It should only be called if GD is known to be on the system, and in use
     *
     * @return float The version of GD installed
     */
    private function get_gd_version()
    {
        $info = gd_info();
        $matches = array();
        if (preg_match('#(\d(\.|))+#', $info['GD Version'], $matches) != 0) {
            $version = $matches[0];
        } else {
            $version = $info['version'];
        }
        return floatval($version);
    }
}
