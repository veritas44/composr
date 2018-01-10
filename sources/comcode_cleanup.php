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
 * @package    core_rich_media
 */

/**
 * Censor some Comcode raw code so that another user can see it.
 * This function isn't designed to be perfectly secure, and we don't guarantee it's always run, but as a rough thing we prefer to do it.
 *
 * @param  string $comcode Comcode
 * @param  ?MEMBER $aggressive Force an HTML-evaluation of the Comcode through this security ID then back to Comcode, as a security technique (null: don't)
 * @return string Censored Comcode
 */
function comcode_censored_raw_code_access($comcode, $aggressive = null)
{
    if ($aggressive !== null) {
        $eval = comcode_to_tempcode($comcode, $aggressive);
        require_code('comcode_from_html');
        $comcode = semihtml_to_comcode($comcode, true);
        return $comcode;
    }

    $comcode = preg_replace('#\[staff_note\].*\[/staff_note\]#Us', '', $comcode);
    return $comcode;
}

/**
 * Filter external media, copying it locally.
 *
 * @param  string $text Comcode / HTML
 */
function download_associated_media(&$text)
{
    $matches = array();
    $num_matches = preg_match_all('#<(img|source)\s[^<>]*src="([^"<>]*)"#i', $text, $matches);
    for ($i = 0; $i < $num_matches; $i++) {
        $old_url = $matches[2][$i];
        _download_associated_media($text, $old_url);
    }
    $num_matches = preg_match_all('#<(img|source)\s[^<>]*src=\'([^\'<>]*)\'#i', $text, $matches);
    for ($i = 0; $i < $num_matches; $i++) {
        $old_url = $matches[2][$i];
        _download_associated_media($text, $old_url);
    }
}

/**
 * Filter external media, copying it locally (helper function).
 *
 * @param  string $text Comcode / HTML
 * @param  string $old_url Old URL to download and replace
 */
function _download_associated_media(&$text, $old_url)
{
    $local_url_1 = parse_url(get_base_url());
    $local_domain_1 = $local_url_1['host'];

    $local_url_2 = parse_url(get_custom_base_url());
    $local_domain_2 = $local_url_2['host'];

    $matches2 = array();
    if ((preg_match('#^https?://([^:/]+)#', $old_url, $matches2) != 0) && ($matches2[1] != $local_domain_1) && ($matches2[1] != $local_domain_2)) {
        require_code('crypt');
        $temp_filename = get_secure_random_string();
        $temp_path = get_custom_file_base() . '/uploads/external_media/' . $temp_filename;

        $write_to_file = fopen($temp_path, 'wb');
        $http_result = cms_http_request($old_url, array('write_to_file' => $write_to_file));
        if ($http_result->data === null) {
            @unlink($temp_path);
            return;
        }

        $mapping = array(
            'image/png' => 'png',
            'image/gif' => 'png',
            'image/jpeg' => 'png',
            'video/mp4' => 'mp4',
            'video/ogg' => 'ogv',
            'video/webm' => 'webm',
            'video/mpeg' => 'mp3',
            'audio/ogg' => 'ogg',
        );
        if (!isset($mapping[$http_result->download_mime_type])) {
            @unlink($temp_path);
            return;
        }

        $new_filename = preg_replace('#\..*#', '', basename($http_result->filename));
        if ($new_filename == '') {
            require_code('crypt');
            $new_filename = get_secure_random_string();
        }
        $new_filename .= '.' . $mapping[$http_result->download_mime_type];
        require_code('urls2');
        list($new_path, $new_url) = find_unique_path('uploads/external_media', $new_filename);

        rename($temp_path, $new_path);
        fix_permissions($new_path);
        sync_file($new_path);

        $new_url = get_custom_base_url() . '/' . $new_url;
        $text = str_replace($old_url, $new_url, $text);
    }
}
