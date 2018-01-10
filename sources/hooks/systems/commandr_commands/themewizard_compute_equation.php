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
 * @package    themewizard
 */

/**
 * Hook class.
 */
class Hook_commandr_command_themewizard_compute_equation
{
    /**
     * Run function for Commandr hooks.
     *
     * @param  array $options The options with which the command was called
     * @param  array $parameters The parameters with which the command was called
     * @param  object $commandr_fs A reference to the Commandr filesystem object
     * @return array Array of stdcommand, stdhtml, stdout, and stderr responses
     */
    public function run($options, $parameters, &$commandr_fs)
    {
        if ((array_key_exists('h', $options)) || (array_key_exists('help', $options))) {
            return array('', do_command_help('themewizard_compute_equation', array('h'), array(true, true)), '', '');
        } else {
            if (!array_key_exists(0, $parameters)) {
                return array('', '', '', do_lang('MISSING_PARAM', '1', 'themewizard_compute_equation'));
            }

            $equation = $parameters[0];

            $theme = array_key_exists(1, $parameters) ? $parameters[1] : 'default';

            require_code('themewizard');

            $css_path = get_custom_file_base() . '/themes/' . filter_naughty($theme) . '/css_custom/global.css';
            if (!file_exists($css_path)) {
                $css_path = get_file_base() . '/themes/default/css/global.css';
            }
            $css_file_contents = cms_file_get_contents_safe($css_path);

            $seed = find_theme_seed($theme);
            $dark = (strpos($css_file_contents, ',#000000,WB,') !== false);

            $colours = calculate_theme($seed, $theme, 'equations', 'colours', $dark);
            $parsed_equation = parse_css_colour_expression($equation);
            if ($parsed_equation === null) {
                return array('', '', '', '?');
            }
            $answer = execute_css_colour_expression($parsed_equation, $colours[0]);
            if ($answer === null) {
                return array('', '', '', '?');
            }

            return array('', '<span style="padding: 0.5em 0.8em; display: inline-block; background: white"><span style="border: 1px solid black; width: 2em; height: 1em; display: inline-block; background: #' . escape_html($answer) . '"></span></span>', '#' . $answer, '');
        }
    }
}
