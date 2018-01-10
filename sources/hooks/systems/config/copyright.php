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
 * @package    core_configuration
 */

/**
 * Hook class.
 */
class Hook_config_copyright
{
    /**
     * Gets the details relating to the config option.
     *
     * @return ?array The details (null: disabled)
     */
    public function get_details()
    {
        return array(
            'human_name' => 'COPYRIGHT',
            'type' => 'transline',
            'category' => 'SITE',
            'group' => 'GENERAL',
            'explanation' => 'CONFIG_OPTION_copyright',
            'shared_hosting_restricted' => '0',
            'list_options' => '',
            'order_in_category_group' => 3,
            'required' => false,

            'public' => false,

            'addon' => 'core_configuration',
        );
    }

    /**
     * Gets the default value for the config option.
     *
     * @return ?string The default value (null: option is disabled)
     */
    public function get_default()
    {
        return do_lang('COPYRIGHTED') . ' &copy; $CURRENT_YEAR=' . date('Y') . ' ' . get_site_name();
    }
}
