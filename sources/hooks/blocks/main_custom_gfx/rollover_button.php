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
 * @package    custom_comcode
 */

/**
 * Hook class.
 */
class Hook_main_custom_gfx_rollover_button
{
    /**
     * Standard graphic generator function. Creates custom graphics from parameters.
     *
     * @param  array $map Map of hook parameters (relayed from block parameters map)
     * @param  object $block The block itself (contains utility methods)
     * @return Tempcode HTML to output
     */
    public function run($map, &$block)
    {
        if ((!function_exists('imagettftext')) || (!array_key_exists('FreeType Support', gd_info())) || (@imagettfbbox(26.0, 0.0, get_file_base() . '/data/fonts/Vera.ttf', 'test') === false)) {
            return do_lang_tempcode('REQUIRES_TTF');
        }

        if (!array_key_exists('img1', $map)) {
            $map['img1'] = 'button1';
        }
        $img_path_1 = find_theme_image($map['img1'], true, true);
        if ($img_path_1 == '') {
            return do_lang_tempcode('NO_SUCH_THEME_IMAGE', $map['img1']);
        }

        $cache_id_1 = 'rollover1_' . md5(serialize($map));
        $url_1 = $block->_do_image($cache_id_1, $map, $img_path_1);
        if (is_object($url_1)) {
            return $url_1;
        }

        if (!array_key_exists('img2', $map)) {
            $map['img2'] = 'button2';
        }
        $img_path_2 = find_theme_image($map['img2'], true, true);
        if ($img_path_2 == '') {
            return do_lang_tempcode('NO_SUCH_THEME_IMAGE', $map['img2']);
        }

        $cache_id_2 = 'rollover2_' . md5(serialize($map));
        $url_2 = $block->_do_image($cache_id_2, $map, $img_path_2);
        if (is_object($url_2)) {
            return $url_2;
        }

        $comb_id = 'rollover_' . uniqid('', false);

        $ret = '<img data-js-function-calls="[[\'gfxRolloverButton\', \'' . $comb_id . '\', \'' . escape_html(php_addslashes($url_2))  . '\']]" id="' . $comb_id . '" class="gfx_text_overlay" alt="' . str_replace("\n", ' ', escape_html($map['data'])) . '" src="' . escape_html($url_1) . '" />';

        if (function_exists('ocp_mark_as_escaped')) {
            ocp_mark_as_escaped($ret);
        }
        return make_string_tempcode($ret);
    }
}
