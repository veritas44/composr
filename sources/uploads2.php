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
 * @package    core
 */

/**
 * Standard code module initialisation function.
 *
 * @ignore
 */
function init__uploads2()
{
    global $REORGANISE_UPLOADS_ERRORMSGS;
    $REORGANISE_UPLOADS_ERRORMSG = array();
}

/**
 * Reorganise upload paths.
 *
 * @param  string $content_type Content type
 * @param  string $upload_directory Upload directory
 * @param  string $upload_field Upload field
 * @param  ?array $where Limit reorganisation to rows matching this WHERE map (null: none)
 * @param  ?array $cma_info Fake content-meta-aware info (null: load real info from $content_type)
 * @param  boolean $append_content_type_to_upload_dir "$upload_directory" should become "$upload_directory/$content_type"
 * @param  boolean $tolerate_errors Whether to tolerate missing files (false = give an error)
 */
function reorganise_uploads($content_type, $upload_directory, $upload_field, $where = array(), $cma_info = null, $append_content_type_to_upload_dir = false, $tolerate_errors = false)
{
    global $REORGANISE_UPLOADS_ERRORMSGS;

    $reorganise_uploads = get_option('reorganise_uploads');

    if (($reorganise_uploads === null) || ($reorganise_uploads == '0')) {
        $REORGANISE_UPLOADS_ERRORMSGS[] = 'NOTICE: reorganise_uploads option disabled';
        return;
    }
    $flat = ($reorganise_uploads == '1');

    if ($cma_info === null) {
        require_code('content');

        $ob = get_content_object($content_type);
        $cma_info = $ob->info();
    }

    $table = $cma_info['table'];
    $table_extended = isset($cma_info['table_extended']) ? $cma_info['table_extended'] : $table;

    $select = array(
        $cma_info['id_field'],
        $upload_field,
    );
    if ($cma_info['parent_category_field'] !== null) {
        $select[] = $cma_info['parent_category_field'];
    }
    if ($cma_info['title_field'] !== null) {
        $select[] = $cma_info['title_field'];
    };

    $start = 0;
    $max = 100;
    do {
        $rows = $cma_info['db']->query_select($table_extended, $select, $where, '', $max, $start);
        foreach ($rows as $row) {
            $current_upload_url = $row[$upload_field];

            if ($current_upload_url == '') {
                // Empty field
                $REORGANISE_UPLOADS_ERRORMSGS[] = 'NOTICE: Empty upload for ' . serialize($row);
                continue;
            }

            if (strpos($current_upload_url, "\n") !== false) {
                // Multi-line...

                $parts = explode("\n", $current_upload_url);
                $new_upload_url = '';
                foreach ($parts as $current_part) {
                    $new_part = _reorganise_content_row_upload(array($upload_field => $current_part) + $row, $content_type, $upload_directory, $upload_field, $cma_info, $flat, $append_content_type_to_upload_dir, $tolerate_errors);

                    if ($current_part != '') {
                        $new_upload_url .= "\n";
                    }
                    $new_upload_url .= ($new_part === null) ? $current_part : $new_part;
                }
            } else {
                // Single-line...

                $new_upload_url = _reorganise_content_row_upload($row, $content_type, $upload_directory, $upload_field, $cma_info, $flat, $append_content_type_to_upload_dir, $tolerate_errors);
            }

            // Update database
            if (($new_upload_url !== null) && ($new_upload_url != $current_upload_url)) {
                $update = array($upload_field => $new_upload_url);
                $_id_field = preg_replace('#^\w+\.#', '', $cma_info['id_field']);
                $update_where = array($_id_field => $row[$_id_field]);
                $cma_info['db']->query_update($table, $update, $update_where, '', 1);
            }
        }
        $start += $max;
    }
    while (count($rows) > 0);

    // Cleanup
    if (count($where) == 0) {
        clean_empty_upload_directories($upload_directory);
    }
}

/**
 * Reorganise a content row's upload.
 *
 * @param  array $row Row
 * @param  string $content_type Content type
 * @param  string $upload_directory Upload directory
 * @param  string $upload_field Upload field
 * @param  array $cma_info Content-meta-aware info
 * @param  boolean $flat Whether to just have a simple flat organisational scheme
 * @param  boolean $append_content_type_to_upload_dir "$upload_directory" should become "$upload_directory/$content_type"
 * @param  boolean $tolerate_errors Whether to tolerate missing files (false = give an error)
 * @return ?URLPATH New URL (null: no change)
 */
function _reorganise_content_row_upload($row, $content_type, $upload_directory, $upload_field, $cma_info, $flat, $append_content_type_to_upload_dir, $tolerate_errors)
{
    global $REORGANISE_UPLOADS_ERRORMSGS;

    $current_upload_url = $row[$upload_field];

    if (substr($current_upload_url, 0, strlen($upload_directory) + 1) != $upload_directory . '/') {
        $REORGANISE_UPLOADS_ERRORMSGS[] = 'NOTICE: Outside upload directory for ' . serialize($row);
        return null; // Not under our normal uploads directory
    }
    if ($append_content_type_to_upload_dir) {
        $upload_directory .= '/' . $content_type;
    }

    $current_disk_path = get_custom_file_base() . '/' . rawurldecode($current_upload_url);
    if (!is_file($current_disk_path)) {
        if (!$tolerate_errors) {
            warn_exit(do_lang_tempcode('_MISSING_RESOURCE', escape_html($current_disk_path)));
        }

        $REORGANISE_UPLOADS_ERRORMSGS[] = 'WARN: Missing file for ' . serialize($row);
        return null; // Error, could not find the file
    }

    if ($flat) {
        $new_upload_path = $upload_directory; // We have configured for things to go in the root
    } elseif ($cma_info['parent_category_field'] === null) {
        $new_upload_path = $upload_directory; // Not in a category, so goes in root
    } elseif (($row[$cma_info['parent_category_field']] === null) || ($row[$cma_info['parent_category_field']] === '')) {
        $new_upload_path = $upload_directory; // Not in a category, so goes in root
    } else {
        $new_upload_path = _get_upload_tree_path($content_type, $row[$cma_info['parent_category_field']], $cma_info, $upload_directory);
        if ($new_upload_path === null) {
            return null; // Error, failed to derive a full path
        }
    }

    $ext = '.' . get_file_extension(rawurldecode($current_upload_url));
    $optimal_filename_stub = basename(rawurldecode($current_upload_url), $ext);
    $optimal_filename_stub = preg_replace('#\..*$#', '', $optimal_filename_stub); // Strip any secondary file extensions
    if ($cma_info['title_field'] !== null) {
        if ($cma_info['title_field_dereference']) {
            $content_title = get_translated_text($row[$cma_info['title_field']], $cma_info['db']);
            if (get_option('moniker_transliteration') == '1') {
                require_code('character_sets');
                $content_title = transliterate_string($content_title);
            }
        } else {
            $content_title = $row[$cma_info['title_field']];
        }

        // Simplify filename back of its suffixing
        $matches = array();
        if (($content_title != '') && (preg_match('#^\d+(' . preg_quote($content_title, '#') . ')$#', $optimal_filename_stub, $matches) != 0)) {
            $optimal_filename_stub = $matches[1]; // LEGACY: Old style prefixing of what we can see are based on content titles
        } elseif (preg_match('#^(.*)_\d+$#', $optimal_filename_stub, $matches) != 0) {
            $optimal_filename_stub = $matches[1];
        } elseif ((running_script('execute_temp')) && (get_param_integer('hard', 0) == 1) && (preg_match('#^\d+([^\d].*)$#', $optimal_filename_stub, $matches) != 0)) {
            $optimal_filename_stub = $matches[1]; // LEGACY: Old style prefixing of what we can see are based on content titles
        }
    }

    // Find the new URL we can actually use
    $i = 1;
    do {
        $filename = $optimal_filename_stub . (($i == 1) ? '' : ('_' . strval($i))) . $ext;
        $new_upload_url = $new_upload_path . '/' . rawurlencode($filename);
        if (($current_upload_url == $new_upload_url) || (rawurldecode($current_upload_url) == rawurldecode($new_upload_url))) {
            return null; // It's already where it should be
        }
        $new_disk_path = get_custom_file_base() . '/' . rawurldecode($new_upload_url);

        $i++;
    }
    while (is_file($new_disk_path));

    if (strlen($new_upload_url) > 255) {
        $REORGANISE_UPLOADS_ERRORMSGS[] = 'NOTICE: Too long URL, ' . $new_upload_url . ' for ' . serialize($row);
        return null; // Too long, so we'll store in the root
    }

    // Make directory tree
    $_new_disk_path = get_custom_file_base() . '/' . rawurldecode($new_upload_path);
    $_compounded_new_disk_path = get_custom_file_base();
    $parts = explode('/', rawurldecode($new_upload_path));
    foreach ($parts as $part) {
        $_compounded_new_disk_path .= '/' . $part;
        if (!file_exists($_compounded_new_disk_path)) {
            if (running_script('execute_temp')) {
                $REORGANISE_UPLOADS_ERRORMSGS[] = 'INFO: Making new directory, ' . $_compounded_new_disk_path;
            }

            if (@mkdir($_compounded_new_disk_path, 0777, true) === false) {
                $REORGANISE_UPLOADS_ERRORMSGS[] = 'WARN: Failed to make directory ' . $_compounded_new_disk_path;
                return null; // Error, failed to make the directory
            }
            fix_permissions($_compounded_new_disk_path);
            @copy(get_custom_file_base() . '/uploads/index.html', $_compounded_new_disk_path . '/index.html');
            fix_permissions($_compounded_new_disk_path . '/index.html');
            sync_file($_compounded_new_disk_path . '/index.html');
        } else {
            if (running_script('execute_temp')) {
                $REORGANISE_UPLOADS_ERRORMSGS[] = 'INFO: Directory already exists, ' . $_compounded_new_disk_path;
            }
        }
    }

    // Move file
    if (!@rename($current_disk_path, $new_disk_path)) {
        $REORGANISE_UPLOADS_ERRORMSGS[] = 'WARN: Failed to move ' . $current_disk_path . ' to ' . $new_disk_path;
        return null; // Error, failed to move file
    }

    return $new_upload_url;
}

/**
 * Find a tree path for a content item.
 *
 * @param  string $content_type Content type
 * @param  mixed $parent_id Parent ID of our content item
 * @param  array $cma_info Content meta aware details
 * @param  string $upload_directory Upload directory
 * @return ?string Tree path (null: error)
 */
function _get_upload_tree_path($content_type, $parent_id, $cma_info, $upload_directory)
{
    global $REORGANISE_UPLOADS_ERRORMSGS;

    $table = $cma_info['parent_spec__table_name'];

    if ($table === null) {
        // No category tree, it's just an entry in a category, so we'll use that category name
        $this_level = $parent_id;
        $this_level_str = (is_string($parent_id) ? $parent_id : strval($parent_id));
        $path = rawurlencode($this_level_str);
        return cms_rawurlrecode($upload_directory . (($path == '') ? '' : ('/' . $path)));
    }

    $select = array(
        $cma_info['parent_spec__field_name'],
    );
    if ($cma_info['parent_spec__parent_name'] !== null) {
        $select[] = $cma_info['parent_spec__parent_name'];
    }

    $seen = array();

    $path = '';
    do {
        $seen[$parent_id] = true;

        $where = array(
            $cma_info['parent_spec__field_name'] => $parent_id
        );

        $parent_rows = $cma_info['db']->query_select($table, $select, $where, '', 1);
        if (!array_key_exists(0, $parent_rows)) {
            $REORGANISE_UPLOADS_ERRORMSGS[] = 'WARN: Missing parent, ' . serialize($parent_id);
            return null;
        }
        $parent_row = $parent_rows[0];

        $this_level = $parent_row[$cma_info['parent_spec__field_name']];
        $this_level_str = (is_string($this_level) ? $this_level : strval($this_level));
        if (get_option('moniker_transliteration') == '1') {
            require_code('character_sets');
            $this_level_str = transliterate_string($this_level_str);
        }

        $path = rawurlencode($this_level_str) . (($path == '') ? '' : ('/' . $path));

        if ($cma_info['parent_spec__parent_name'] === null) {
            break;
        }

        $previous_parent_id = $parent_id;
        $parent_id = $parent_row[$cma_info['parent_spec__parent_name']];

        if (isset($seen[$parent_id])) {
            $REORGANISE_UPLOADS_ERRORMSGS[] = 'WARN: Looped category structure, ' . serialize($parent_id);
            break; // Error, some kind of loop
        }

        if (($parent_id === '') || ($parent_id === null)) {
            break; // Reached root
        }
    }
    while (true);

    return cms_rawurlrecode($upload_directory . (($path == '') ? '' : ('/' . $path)));
}

/**
 * Delete any empty directories.
 *
 * @param  string $upload_directory Upload directory
 * @param  boolean $top_level Whether this is the top level directory (which will not be deleted)
 * @return boolean Whether this subdirectory has been deleted
 */
function clean_empty_upload_directories($upload_directory, $top_level = true)
{
    require_code('files');

    $dh = @opendir(get_custom_file_base() . '/' . $upload_directory);

    if ($dh === false) {
        return true;
    }

    $ok_to_delete = !$top_level;

    while (($f = readdir($dh)) !== false) {
        if (should_ignore_file(get_custom_file_base() . '/' . $upload_directory . '/' . $f, IGNORE_ACCESS_CONTROLLERS)) {
            continue;
        }

        if (is_dir(get_custom_file_base() . '/' . $upload_directory . '/' . $f)) {
            $ok_to_delete = clean_empty_upload_directories($upload_directory . '/' . $f, false) && $ok_to_delete;
        } else {
            $ok_to_delete = false;
        }
    }

    closedir($dh);

    if ($ok_to_delete) {
        deldir_contents(get_custom_file_base() . '/' . $upload_directory);
        @rmdir(get_custom_file_base() . '/' . $upload_directory);
    }

    return $ok_to_delete;
}
