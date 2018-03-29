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
 * @package    custom_comcode
 */

/**
 * Hook class.
 */
class Hook_preview_custom_comcode
{
    /**
     * Find whether this preview hook applies.
     *
     * @return array Triplet: Whether it applies, the attachment ID type (may be null), whether the forum DB is used [optional]
     */
    public function applies()
    {
        $applies = (addon_installed('custom_comcode')) && (get_page_name() == 'admin_custom_comcode');
        return array($applies, null, false);
    }

    /**
     * Run function for preview hooks.
     *
     * @return array A pair: The preview, the updated post Comcode (may be null)
     */
    public function run()
    {
        require_code('comcode_compiler');

        $tag = post_param_string('tag');

        $replace = post_param_string('replace');

        $parameters = '';
        foreach ($_POST as $key => $val) {
            if (substr($key, 0, 11) != 'parameters_') {
                continue;
            }
            if ($val == '') {
                continue;
            }
            if ($parameters != '') {
                $parameters .= ',';
            }
            $parameters .= $val;
        }
        $_parameters = ($parameters == '') ? array() : explode(',', $parameters);

        $example = post_param_string('example');

        $content = do_lang_tempcode('EXAMPLE');

        $matches = array();
        if (preg_match('#\](.*)\[#', $example, $matches) != 0) {
            $content = make_string_tempcode($matches[1]);
        }
        $binding = array('CONTENT' => $content);
        foreach ($_parameters as $parameter) {
            $parameter = trim($parameter);
            $parts = explode('=', $parameter);
            if (count($parts) == 1) {
                $parts[] = '';
            }
            if (count($parts) != 2) {
                continue;
            }
            list($parameter, $default) = $parts;
            $binding[strtoupper($parameter)] = $default;
            $replace = str_replace('{' . $parameter . '}', '{' . strtoupper($parameter) . '*}', $replace);
        }

        require_code('tempcode_compiler');
        $replace = str_replace('{content}', array_key_exists($tag, $GLOBALS['TEXTUAL_TAGS']) ? '{CONTENT}' : '{CONTENT*}', $replace);
        $temp_tpl = template_to_tempcode($replace);
        $temp_tpl = $temp_tpl->bind($binding, '(custom comcode: ' . $tag . ')');

        return array($temp_tpl, null);
    }
}
