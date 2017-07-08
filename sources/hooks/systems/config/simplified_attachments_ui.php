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
 * @package    core_rich_media
 */

/**
 * Hook class.
 */
class Hook_config_simplified_attachments_ui
{
    /**
     * Gets the details relating to the config option.
     *
     * @return ?array The details (null: disabled)
     */
    public function get_details()
    {
        return array(
            'human_name' => 'SIMPLIFIED_ATTACHMENTS_UI',
            'type' => 'tick',
            'category' => 'FEATURE',
            'group' => '_COMCODE',
            'explanation' => 'CONFIG_OPTION_simplified_attachments_ui',
            'shared_hosting_restricted' => '0',
            'list_options' => '',
            'order_in_category_group' => 6,

            'required' => true,
            'addon' => 'core_rich_media',
        );
    }

    /**
     * Gets the default value for the config option.
     *
     * @return ?string The default value (null: option is disabled)
     */
    public function get_default()
    {
        if (get_option('complex_uploader') == '0') {
            return null;
        }

        return '1';
    }
}
