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
 * @package    banners
 */

/**
 * Hook class.
 */
class Hook_reorganise_uploads_banners
{
    /**
     * Run function for reorganise_uploads hooks.
     */
    public function run()
    {
        if (!addon_installed('banners')) {
            return;
        }

        require_code('banners2');
        reorganise_uploads__banners(null, true);
    }
}
