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
 * @package    banners
 */

/**
 * Hook class.
 */
class Hook_block_ui_renderers_banners
{
    /**
     * See if a particular block parameter's UI input can be rendered by this.
     *
     * @param  ID_TEXT $block The block
     * @param  ID_TEXT $parameter The parameter of the block
     * @param  boolean $has_default Whether there is a default value for the field, due to this being an edit
     * @param  string $default Default value for field
     * @param  Tempcode $description Field description
     * @return ?Tempcode Rendered field (null: not handled)
     */
    public function render_block_ui($block, $parameter, $has_default, $default, $description)
    {
        if (($parameter == 'param') && (in_array($block, array('main_banner_wave', 'main_top_sites')))) { // banner type list
            require_code('banners2');
            $list = create_selection_list_banner_types($default);
            return form_input_list(titleify($parameter), escape_html($description), $parameter, $list, null, false, false);
        }

        if (($parameter == 'name') && (in_array($block, array('main_banner_wave')))) { // banner list
            require_code('banners2');
            $list = new Tempcode();
            $list->attach(form_input_list_entry('', false));
            $list->attach(create_selection_list_banners($default));
            return form_input_list(titleify($parameter), escape_html($description), $parameter, $list, null, false, false);
        }

        if (($parameter == 'region') && (in_array($block, array('main_banner_wave')))) { // region list
            require_code('locations');
            $continents_and_countries = find_continents_and_countries();

            $list_groups = new Tempcode();
            $list_groups->attach(form_input_list_entry('', false));
            foreach ($continents_and_countries as $continent => $countries) {
                $list = new Tempcode();
                foreach ($countries as $country_code => $country_name) {
                    $list->attach(form_input_list_entry($country_code, $country_code == $default, $country_name));
                }
                $list_groups->attach(form_input_list_group($continent, $list));
            }

            return form_input_list(titleify($parameter), escape_html($description), $parameter, $list_groups, null, false, false);
        }

        return null;
    }
}
