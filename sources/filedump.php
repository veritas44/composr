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
 * @package    filedump
 */

/**
 * Find broken filedump links, and try and find how to fix it.
 *
 * @return array Filedump broken links, to replacement path (or null)
 */
function find_broken_filedump_links()
{
    $paths_broken = array();

    require_code('files2');
    $all_files = get_directory_contents(get_custom_file_base() . '/uploads/filedump', '', IGNORE_ACCESS_CONTROLLERS, true);

    $paths_used = find_filedump_links();
    foreach ($paths_used as $path => $details) {
        if (!$details['exists']) {
            foreach ($all_files as $file) {
                if (basename($file) == basename($path)) {
                    $paths_broken[$path] = '/' . $file;
                    continue 2;
                }
            }
            $paths_broken[$path] = null;
        }
    }

    return $paths_broken;
}

/**
 * Re-map pre-existing filedump links from one path to another.
 *
 * @param  string $from Old path (give a path relative to uploads/filedump, with leading slash)
 * @param  string $to New path (give a path relative to uploads/filedump, with leading slash)
 */
function update_filedump_links($from, $to)
{
    if ($to == '') {
        warn_exit(do_lang_tempcode('INTERNAL_ERROR'));
    }
    if (substr($to, 0, 1) != '/') {
        $to = '/' . $to;
    }

    $current = find_filedump_links($from);

    $from = str_replace('%2F', '/', rawurlencode($from));
    $to = str_replace('%2F', '/', rawurlencode($to));

    $patterns = array(
        '#"uploads/filedump(' . preg_quote($from, '#') . ')"#' => '"uploads/filedump' . $to . '"',
        '#\]uploads/filedump(' . preg_quote($from, '#') . ')\[#' => ']uploads/filedump' . $to . '[',
        '#\]url_uploads/filedump(' . preg_quote($from, '#') . ')\[#' => ']url_uploads/filedump' . $to . '[',
        '#"' . preg_quote(get_custom_base_url(), '#') . '/uploads/filedump(' . preg_quote($from, '#') . ')"#' => '"' . get_custom_base_url() . '/uploads/filedump' . $to . '"',
        '#\]' . preg_quote(get_custom_base_url(), '#') . '/uploads/filedump(' . preg_quote($from, '#') . ')\[#' => ']' . get_custom_base_url() . '/uploads/filedump' . $to . '[',
        '#\]url_' . preg_quote(get_custom_base_url(), '#') . '/uploads/filedump(' . preg_quote($from, '#') . ')\[#' => ']url_' . get_custom_base_url() . '/uploads/filedump' . $to . '[',
    );

    foreach ($current as $details) {
        foreach ($details['references'] as $ref) {
            if (is_array($ref)) {
                $old_comcode = get_translated_text($ref[0][$ref[1]]);
            } else {
                list($zone, $page, $lang) = explode(':', $ref, 3);
                $path = get_custom_file_base() . (($zone == '') ? '' : '/') . $zone . '/pages/comcode_custom/' . $lang . '/' . $page . '.txt';
                $old_comcode = file_get_contents($path);
            }

            $new_comcode = $old_comcode;
            foreach ($patterns as $pattern_from => $pattern_to) {
                $new_comcode = preg_replace($pattern_from, $pattern_to, $new_comcode);
            }

            if (is_array($ref)) {
                lang_remap_comcode($ref[1], $ref[0][$ref[1]], $new_comcode);
            } else {
                require_code('files');
                cms_file_put_contents_safe($path, $new_comcode, FILE_WRITE_FIX_PERMISSIONS | FILE_WRITE_SYNC_FILE);
            }
        }
    }
}

/**
 * Find all filedump links used.
 *
 * @param  string $focus Focus on a particular filedump file (give a path relative to uploads/filedump, with leading slash) (blank: no filter)
 * @return array Filedump links used, and where
 */
function find_filedump_links($focus = '')
{
    $paths_used = array();

    $_focus = str_replace('%2F', '/', rawurlencode($focus));

    // Comcode
    global $TABLE_LANG_FIELDS_CACHE;
    foreach ($TABLE_LANG_FIELDS_CACHE as $table => $fields) {
        foreach ($fields as $field_name => $field_type) {
            if (strpos($field_type, 'LONG_TRANS__COMCODE') !== false) {
                $query = 'SELECT r.* FROM ' . get_table_prefix() . $table . ' r WHERE 1=1';
                $_field_name = $GLOBALS['SITE_DB']->translate_field_ref($field_name);
                if ($GLOBALS['SITE_DB']->has_full_text()) { // For efficiency, pre-filter via full-text search
                    $index_name = $GLOBALS['SITE_DB']->query_select_value_if_there('db_meta_indices', 'i_name', array('i_table' => $table, 'i_fields' => $field_name), ' AND i_name LIKE \'' . db_encode_like('#%') . '\'');
                    if ($index_name !== null) {
                        $query .= ' AND ' . preg_replace('#\?#', $_field_name, $GLOBALS['SITE_DB']->full_text_assemble('filedump', false));
                    }
                }
                if ($focus == '') {
                    $query .= ' AND ' . $_field_name . ' LIKE \'' . db_encode_like('%uploads/filedump/%') . '\'';
                } else {
                    $query .= ' AND ' . $_field_name . ' LIKE \'' . db_encode_like('%uploads/filedump' . $_focus . '%') . '\'';
                }
                if (substr($table, 0, 2) == 'f_') {
                    $db = $GLOBALS['FORUM_DB'];
                } else {
                    $db = $GLOBALS['SITE_DB'];
                }
                $results = $db->query($query, null, 0, false, false, array($field_name => $field_type));
                foreach ($results as $r) {
                    extract_filedump_links(get_translated_text($r[$field_name]), array($r, $field_name), $focus, $paths_used);
                }
            }
        }
    }

    // Comcode pages
    $zones = find_all_zones(false, false, true);
    $langs = array_keys(find_all_langs());
    foreach ($zones as $zone) {
        $pages = find_all_pages_wrap($zone, false, false, FIND_ALL_PAGES__ALL, 'comcode');
        foreach ($pages as $page => $page_type) {
            if (is_integer($page)) {
                $page = strval($page);
            }
            foreach ($langs as $lang) {
                $path = get_custom_file_base() . (($zone == '') ? '' : '/') . $zone . '/pages/comcode_custom/' . $lang . '/' . $page . '.txt';
                if (is_file($path)) {
                    $comcode = file_get_contents($path);
                    extract_filedump_links($comcode, $zone . ':' . $page . ':' . $lang, $focus, $paths_used);
                }
            }
        }
    }

    return $paths_used;
}

/**
 * Find filedump links within some Comcode (an approximation).
 *
 * @param  string $comcode Comcode to scan
 * @param  mixed $identifier An identifier for where this Comcode was from
 * @param  string $focus Focus on a particular filedump file (give a path relative to uploads/filedump), with leading slash (blank: no filter)
 * @param  array $paths_used Paths found (passed by reference)
 */
function extract_filedump_links($comcode, $identifier, $focus, &$paths_used)
{
    $_focus = str_replace('%2F', '/', rawurlencode($focus));

    if ($focus == '') {
        $patterns = array(
            '#"uploads/filedump(/[^"]+)"#',
            '#\]uploads/filedump(/[^\[\]]+)\[#',
            '#\]url_uploads/filedump(/[^\[\]]+)\[#',
            '#"' . preg_quote(get_custom_base_url(), '#') . '/uploads/filedump(/[^"]+)"#',
            '#\]' . preg_quote(get_custom_base_url(), '#') . '/uploads/filedump(/[^\[\]]+)\[#',
            '#\]url_' . preg_quote(get_custom_base_url(), '#') . '/uploads/filedump(/[^\[\]]+)\[#',
        );
    } else {
        $patterns = array(
            '#"uploads/filedump(' . preg_quote($_focus, '#') . ')"#',
            '#\]uploads/filedump(' . preg_quote($_focus, '#') . ')\[#',
            '#\]url_uploads/filedump(' . preg_quote($_focus, '#') . ')\[#',
            '#"' . preg_quote(get_custom_base_url(), '#') . '/uploads/filedump(' . preg_quote($_focus, '#') . ')"#',
            '#\]' . preg_quote(get_custom_base_url(), '#') . '/uploads/filedump(' . preg_quote($_focus, '#') . ')\[#',
            '#\]url_' . preg_quote(get_custom_base_url(), '#') . '/uploads/filedump(' . preg_quote($_focus, '#') . ')\[#',
        );
    }

    foreach ($patterns as $pattern) {
        $matches = array();
        $num_matches = preg_match_all($pattern, $comcode, $matches);
        for ($i = 0; $i < $num_matches; $i++) {
            $decoded = urldecode(html_entity_decode($matches[1][$i], ENT_QUOTES)); // This is imperfect (raw naming that coincidentally matches entity encoding will break), but good enough

            if (strpos($decoded, '*') !== false) { // False positive, some kind of exemplar test
                continue;
            }

            $path = get_custom_file_base() . '/uploads/filedump' . $decoded;

            if (!isset($paths_used[$decoded])) {
                $paths_used[$decoded] = array(
                    'exists' => is_file($path),
                    'references' => array(),
                );
            }

            if (!in_array($identifier, $paths_used[$decoded]['references'])) {
                $paths_used[$decoded]['references'][] = $identifier;
            }
        }
    }
}
