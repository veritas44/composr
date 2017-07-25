<?php /*

 Composr
 Copyright (c) ocProducts, 2004-2017

 See text/EN/licence.txt for full licencing information.


 NOTE TO PROGRAMMERS:
   Do not edit this file. If you need to make changes, save your changed file to the appropriate *_custom folder
   **** If you ignore this advice, then your website upgrades (e.g. for bug fixes) will likely kill your changes ****

*/

/**
 * @license    http://opensource.org/licenses/cpal_1.0 Common Public Attribution License
 * @copyright  ocProducts Ltd
 * @package    ecommerce
 */

/**
 * Hook class.
 */
class Hook_config_download_cat_buy_max_emailed_size
{
    /**
     * Gets the details relating to the config option.
     *
     * @return ?array The details (null: disabled)
     */
    public function get_details()
    {
        return array(
            'human_name' => 'DOWNLOAD_CAT_BUY_MAX_EMAILED_SIZE',
            'type' => 'integer',
            'category' => 'ECOMMERCE',
            'group' => 'PERMISSION_PRODUCT_DOWNLOAD_CATEGORY',
            'explanation' => 'CONFIG_OPTION_download_cat_buy_max_emailed_size',
            'shared_hosting_restricted' => '0',
            'list_options' => '',
            'required' => true,

            'addon' => 'ecommerce',
        );
    }

    /**
     * Gets the default value for the config option.
     *
     * @return ?string The default value (null: option is disabled)
     */
    public function get_default()
    {
        if (!addon_installed('downloads')) {
            return null;
        }
        return '5000';
    }
}