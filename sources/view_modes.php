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
 * @package    core
 */

/**
 * Initialise state variables for the special page type being requested.
 *
 * @param  ID_TEXT $special_page_type The special page type
 * @set    query templates tree lang
 */
function initialise_special_page_types($special_page_type)
{
    disable_php_memory_limit();

    // NB: These modes will typically be used together with &cache_blocks=0&cache_comcode_pages=0. This is what unsets caching, not special_page_type directly.
    //  The footer options do this.

    if ($special_page_type == 'templates' || $special_page_type == 'tree') {
        global $RECORD_TEMPLATES_USED;
        $RECORD_TEMPLATES_USED = true;
    } elseif (substr($special_page_type, 0, 12) == 'lang_content') {
        global $RECORD_LANG_STRINGS_CONTENT;
        /** A marker indicating whether all referenced content language strings need to be collected, so that the contextual editor knows what was used to generate the screen.
         *
         * @global boolean $RECORD_LANG_STRINGS_CONTENT
         */
        $RECORD_LANG_STRINGS_CONTENT = true;
    } elseif (substr($special_page_type, 0, 4) == 'lang') {
        global $RECORD_LANG_STRINGS;
        $RECORD_LANG_STRINGS = true;
    } elseif ($special_page_type == 'theme_images') {
        global $RECORD_THEME_IMAGES_CACHE;
        $RECORD_THEME_IMAGES_CACHE = true;
    } elseif ($special_page_type == 'ide_linkage') {
        global $RECORD_TEMPLATES_USED;
        $RECORD_TEMPLATES_USED = true;
    }
}

/**
 * Handle special page type output.
 *
 * @param  ID_TEXT $special_page_type The special page type
 * @set    query templates tree lang
 * @param  Tempcode $out The normal script Tempcode output
 * @param  string $out_evaluated The normal script evaluated output
 */
function special_page_types($special_page_type, &$out, $out_evaluated)
{
    global $RECORDED_TEMPLATES_USED;

    if (php_function_allowed('set_time_limit')) {
        @set_time_limit(280);
    }

    $middle_spt = new Tempcode();

    if ($out_evaluated === null) {
        $out_evaluated = $out->evaluate(); // False evaluation
    }

    // FUDGE: Yuck. We have to after-the-fact make it wide (as we needed the template tree to be collected in full), and empty lots of internal caching to reset the state.
    $GLOBALS['PANELS_CACHE'] = array();
    $GLOBALS['IS_WIDE_HIGH_CACHE'] = 1;
    $GLOBALS['IS_WIDE_CACHE'] = 1;
    $GLOBALS['TEMPCODE_SETGET'] = array();
    $GLOBALS['LOADED_TPL_CACHE'] = array();
    $GLOBALS['HELPER_PANEL_TEXT'] = null;
    $GLOBALS['HELPER_PANEL_TUTORIAL'] = null;

    // CSS
    if (substr($special_page_type, -4) == '.css') {
        $url_map = array(
            'page' => 'admin_themes',
            'type' => 'edit_templates',
            'live_preview_url' => protect_url_parameter(get_self_url(true, false, array('special_page_type' => null))),
            'theme' => $GLOBALS['FORUM_DRIVER']->get_theme(),
            'f0file' => 'css/' . $special_page_type,
            'keep_wide_high' => 1,
        );
        $url = build_url($url_map, get_module_zone('admin_themes'));

        require_code('site2');
        smart_redirect($url->evaluate());
    }

    // Sitemap Editor
    if ($special_page_type == 'sitemap') {
        $url = build_url(array('page' => 'admin_sitemap', 'type' => 'browse', 'id' => get_zone_name() . ':' . get_page_name()), get_module_zone('admin_sitemap'));

        require_code('site2');
        smart_redirect($url->evaluate());
    }

    // IDE linkage
    if ($special_page_type == 'ide_linkage') {
        $title = get_screen_title('IDE_LINKAGE');

        $file_links = new Tempcode();

        global $JAVASCRIPTS, $CSSS, $REQUIRED_CODE, $LANGS_REQUESTED;

        /*foreach (array_keys($JAVASCRIPTS) as $name) Already in list of templates
        {
            $txtmte_url = 'txmt://open?url=file://'.$name;
            $file_links->attach(do_template('INDEX_SCREEN_ENTRY', array('_GUID' => 'ef68ed85bfc07b45e1fe2d94bd2672f2', 'URL' = >$txtmte_url, 'NAME' => $name)));
        }*/

        foreach (array_keys($CSSS) as $name) {
            $search = find_template_place($name, get_site_default_lang(), $GLOBALS['FORUM_DRIVER']->get_theme(), '.css', 'css');
            if ($search !== null) {
                list($theme, $type, $suffix) = $search;
                $txtmte_url = 'txmt://open?url=file://' . get_file_base() . '/themes/' . $theme . '/' . $type . '/' . $name . $suffix;
                $file_links->attach(do_template('INDEX_SCREEN_ENTRY', array(
                    '_GUID' => 'c3d6bdf723918aae23541d91ebf09f0b',
                    'DISPLAY_STRING' => '(CSS)',
                    'URL' => $txtmte_url,
                    'NAME' => $name . $suffix,
                )));
            }
        }

        foreach (array_keys($REQUIRED_CODE) as $name) {
            $path_a = get_file_base() . '/' . ((strpos($name, '.php') === false) ? ('/sources_custom/' . $name . '.php') : $name);
            $path_b = get_file_base() . '/' . ((strpos($name, '.php') === false) ? ('/sources/' . $name . '.php') : str_replace('_custom', '', $name));

            if (file_exists($path_a)) {
                $txtmte_url = 'txmt://open?url=file://' . $path_a;
                $file_links->attach(do_template('INDEX_SCREEN_ENTRY', array(
                    '_GUID' => '3d99e7c51959f12cb1a935d302e0fac2',
                    'DISPLAY_STRING' => '(PHP)',
                    'URL' => $txtmte_url,
                    'NAME' => $name . (((strpos($name, '.php') === false) ? '.php' : '')),
                )));
            }
            if (file_exists($path_b)) {
                $txtmte_url = 'txmt://open?url=file://' . $path_b;
                $file_links->attach(do_template('INDEX_SCREEN_ENTRY', array(
                    '_GUID' => '6c9fbce894cc841776123781906ebd88',
                    'DISPLAY_STRING' => '(PHP)',
                    'URL' => $txtmte_url,
                    'NAME' => $name . (((strpos($name, '.php') === false) ? '.php' : '')),
                )));
            }
        }

        foreach (array_keys($LANGS_REQUESTED) as $name) {
            if (file_exists(get_file_base() . '/lang_custom/' . fallback_lang() . '/' . $name . '.ini')) {
                $txtmte_url = 'txmt://open?url=file://' . get_file_base() . '/lang_custom/' . fallback_lang() . '/' . $name . '.ini';
                $file_links->attach(do_template('INDEX_SCREEN_ENTRY', array(
                    '_GUID' => 'f04c9d10f87f7a728b8a347992340ee4',
                    'DISPLAY_STRING' => '(Language)',
                    'URL' => $txtmte_url,
                    'NAME' => $name . '.ini',
                )));
            }
            if (file_exists(get_file_base() . '/lang/' . fallback_lang() . '/' . $name . '.ini')) {
                $txtmte_url = 'txmt://open?url=file://' . get_file_base() . '/lang/' . fallback_lang() . '/' . $name . '.ini';
                $file_links->attach(do_template('INDEX_SCREEN_ENTRY', array(
                    '_GUID' => 'b41dfcb41b4fea5a12c25e880f7bccfd',
                    'DISPLAY_STRING' => '(Language)',
                    'URL' => $txtmte_url,
                    'NAME' => $name . '.ini',
                )));
            }
        }

        foreach (array_keys($RECORDED_TEMPLATES_USED) as $name) {
            $search = find_template_place(
                basename($name, '.' . get_file_extension($name)),
                get_site_default_lang(),
                $GLOBALS['FORUM_DRIVER']->get_theme(),
                '.' . get_file_extension($name),
                dirname($name)
            );
            if ($search !== null) {
                list($theme, $type) = $search;
                $txtmte_url = 'txmt://open?url=file://' . get_file_base() . '/themes/' . $theme . '/' . $type . '/' . basename($name);
                $file_links->attach(do_template('INDEX_SCREEN_ENTRY', array(
                    '_GUID' => 'c2a5f66b9d6564b30c506afafd59b676',
                    'DISPLAY_STRING' => '(Templates)',
                    'URL' => $txtmte_url,
                    'NAME' => $name . $suffix,
                )));
            }
        }

        $middle_spt = do_template('INDEX_SCREEN', array(
            '_GUID' => '7722ab1c391c86adccde04dbc0ef7ba9',
            'TITLE' => $title,
            'CONTENT' => $file_links,
            'PRE' => do_lang_tempcode('TXMT_PROTOCOL_EXPLAIN'),
            'POST' => '',
        ));
    }

    // Theme images mode
    if ($special_page_type == 'theme_images') {
        $title = get_screen_title('THEME_IMAGE_EDITING');

        $theme_images = new Tempcode();

        global $RECORDED_THEME_IMAGES;
        foreach (array_keys($RECORDED_THEME_IMAGES) as $theme_image_details) {
            list($id, $theme, $lang) = unserialize($theme_image_details);

            $url_map = array(
                'page' => 'admin_themes',
                'type' => 'edit_image',
                'theme' => ($theme === null) ? $GLOBALS['FORUM_DRIVER']->get_theme() : $theme,
                'lang' => $lang,
                'id' => $id,
            );
            $url = build_url($url_map, 'adminzone');

            $image = find_theme_image($id, false, false, $theme, $lang);
            if ($image == '') {
                continue;
            }

            $theme_images->attach(do_template('INDEX_SCREEN_FANCIER_ENTRY', array(
                '_GUID' => '65ea324fb12a488adae780915624a268',
                'IMG' => $image,
                'DESCRIPTION' => '',
                'URL' => $url,
                'NAME' => $id,
                'TARGET' => '_blank',
                'TITLE' => '',
            )));
        }

        $middle_spt = do_template('INDEX_SCREEN_FANCIER_SCREEN', array(
            '_GUID' => 'b16d40ad36f209b1a3559df6f1ebac55',
            'TITLE' => $title,
            'CONTENT' => $theme_images,
            'PRE' => do_lang_tempcode('CONTEXTUAL_EDITING_SCREEN'),
            'POST' => '',
        ));
    }

    // Content translation mode
    if (substr($special_page_type, 0, 12) == 'lang_content') {
        require_code('input_filter_2');
        if (get_value('disable_modsecurity_workaround') !== '1') {
            modsecurity_workaround_enable();
        }

        require_javascript('editing');

        $map_a = get_file_base() . '/lang/langs.ini';
        $map_b = get_custom_file_base() . '/lang_custom/langs.ini';
        if (!file_exists($map_b)) {
            $map_b = $map_a;
        }
        require_code('files');
        $map = better_parse_ini_file($map_b);

        $lang_name = user_lang();
        if (array_key_exists($lang_name, $map)) {
            $lang_name = $map[$lang_name];
        }

        global $RECORDED_LANG_STRINGS_CONTENT;

        require_lang('lang');
        require_code('form_templates');
        $GLOBALS['NO_DEV_MODE_FULLSTOP_CHECK'] = true;
        require_code('lang2');

        $fields = new Tempcode();

        $names = find_lang_content_names(array_keys($RECORDED_LANG_STRINGS_CONTENT));

        foreach ($RECORDED_LANG_STRINGS_CONTENT as $key => $forum_db) {
            $value_found = get_translated_text($key, $forum_db ? $GLOBALS['FORUM_DB'] : $GLOBALS['SITE_DB']);
            if ($value_found != '') {
                $description = make_string_tempcode(escape_html($value_found));
                if ((get_option('google_translate_api_key') == '') || (user_lang() == get_site_default_lang())) {
                    $actions = new Tempcode();
                } else {
                    require_javascript('translate');
                    $actions = do_template('TRANSLATE_ACTION', array(
                        '_GUID' => '441cd96588b2a4f74e94003643262833',
                        'LANG_FROM' => get_site_default_lang(),
                        'LANG_TO' => user_lang(),
                        'NAME' => 'trans_' . strval($key),
                        'OLD' => $value_found,
                    ));
                }
                $description->attach($actions);
                $fields->attach(form_input_text(($names[$key] === null) ? ('#' . strval($key)) : $names[$key], $description, 'trans_' . strval($key), $value_found, false));
            }
        }

        if ($fields->is_empty()) {
            inform_exit(do_lang_tempcode('NOTHING_TO_TRANSLATE'));
        }

        $title = get_screen_title('__TRANSLATE_CONTENT', true, array(escape_html($lang_name)));

        $post_url = build_url(array('page' => 'admin_lang', 'type' => '_content', 'contextual' => 1), 'adminzone');

        $hidden = form_input_hidden('redirect', static_evaluate_tempcode(protect_url_parameter(SELF_REDIRECT_RIP)));
        $hidden = form_input_hidden('lang', user_lang());

        $middle_spt = do_template('FORM_SCREEN', array(
            '_GUID' => '0d4dd16b023d0a7960f3eac85f54ddc4',
            'SKIP_WEBSTANDARDS' => true,
            'TITLE' => $title,
            'HIDDEN' => $hidden,
            'FIELDS' => $fields,
            'URL' => $post_url,
            'TEXT' => do_lang_tempcode('CONTEXTUAL_EDITING_SCREEN'),
            'SUBMIT_ICON' => 'buttons__save',
            'SUBMIT_NAME' => do_lang_tempcode('SAVE'),
            'MODSECURITY_WORKAROUND' => true,
        ));

    } // Language mode
    elseif (substr($special_page_type, 0, 4) == 'lang') {
        require_code('input_filter_2');
        if (get_value('disable_modsecurity_workaround') !== '1') {
            modsecurity_workaround_enable();
        }

        require_javascript('editing');

        $map_a = get_file_base() . '/lang/langs.ini';
        $map_b = get_custom_file_base() . '/lang_custom/langs.ini';
        if (!file_exists($map_b)) {
            $map_b = $map_a;
        }
        require_code('files');
        $map = better_parse_ini_file($map_b);

        $lang_name = user_lang();
        if (array_key_exists($lang_name, $map)) {
            $lang_name = $map[$lang_name];
        }

        global $RECORDED_LANG_STRINGS, $LANGS_REQUESTED;
        require_lang('lang');
        require_code('form_templates');
        require_code('lang2');
        $GLOBALS['NO_DEV_MODE_FULLSTOP_CHECK'] = true;
        require_code('lang_compile');
        $fields = new Tempcode();
        $descriptions = get_lang_file_section(fallback_lang());
        //ksort($RECORDED_LANG_STRINGS); Best to leave them in occurrence order
        foreach (array_keys($RECORDED_LANG_STRINGS) as $key) {
            $value_found = do_lang($key, null, null, null, null, false);
            $description = array_key_exists($key, $descriptions) ? make_string_tempcode($descriptions[$key]) : new Tempcode();
            if ($value_found !== null) {
                if ((get_option('google_translate_api_key') == '') || (user_lang() == get_site_default_lang())) {
                    $actions = new Tempcode();
                } else {
                    require_javascript('translate');
                    $actions = do_template('TRANSLATE_ACTION', array(
                        '_GUID' => '031eb918cb3bcaf4339130b46f8b1b8a',
                        'LANG_FROM' => get_site_default_lang(),
                        'LANG_TO' => user_lang(),
                        'NAME' => 'l_' . $key,
                        'OLD' => str_replace('\n', "\n", $value_found),
                    ));
                }
                $description->attach($actions);

                $key_extended = $key;
                foreach (array_keys(get_lang_files(fallback_lang())) as $lang_file) {
                    $tmp_path = get_file_base() . '/lang/' . fallback_lang() . '/' . $lang_file . '.ini';
                    if (!is_file($tmp_path)) {
                        $tmp_path = get_file_base() . '/lang_custom/' . fallback_lang() . '/' . $lang_file . '.ini';
                    }
                    if (is_file($tmp_path) && strpos(file_get_contents($tmp_path), "\n{$key}=") !== false) {
                        $key_extended .= ' (' . $lang_file . ')';
                        break;
                    }
                }

                $fields->attach(form_input_text($key_extended, $description, 'l_' . $key, str_replace('\n', "\n", $value_found), false));
            }
        }

        $title = get_screen_title('__TRANSLATE_CODE', true, array(escape_html($lang_name)));

        $post_url = build_url(array('page' => 'admin_lang', 'type' => '_code2'), 'adminzone');

        $hidden = form_input_hidden('redirect', static_evaluate_tempcode(protect_url_parameter(SELF_REDIRECT_RIP)));
        $hidden = form_input_hidden('lang', user_lang());

        $middle_spt = do_template('FORM_SCREEN', array(
            '_GUID' => '47a2934eaec30ed5eea635d4c462cee0',
            'SKIP_WEBSTANDARDS' => true,
            'TITLE' => $title,
            'HIDDEN' => $hidden,
            'FIELDS' => $fields,
            'URL' => $post_url,
            'TEXT' => do_lang_tempcode('CONTEXTUAL_EDITING_SCREEN'),
            'SUBMIT_ICON' => 'buttons__save',
            'SUBMIT_NAME' => do_lang_tempcode('SAVE'),
            'MODSECURITY_WORKAROUND' => true,
        ));
    }

    // Template mode?
    if ($special_page_type == 'templates' || $special_page_type == 'tree'/*not directly linked, but could potentially be handy*/) {
        global $RECORD_TEMPLATES_USED;
        $RECORD_TEMPLATES_USED = false;

        // Render template tree...

        require_code('themes_meta_tree');
        if (!isset($out->metadata)) {
            fatal_exit(do_lang_tempcode('INTERNAL_ERROR'));
        }
        $collected_templates = array(
            'templates/HTML_HEAD.tpl' => true, // FUDGE. Due to the Tempcode inlining optimisation, this might not be picked up on, but is an important template - so we'll force it into here
        );
        $_tree = find_template_tree_nice($out->metadata, $collected_templates);

        $edit_url_map = array(
            'page' => 'admin_themes',
            'type' => 'edit_templates',
            'live_preview_url' => protect_url_parameter(get_self_url(true, false, array('special_page_type' => null))),
            'theme' => $GLOBALS['FORUM_DRIVER']->get_theme(),
        );
        $edit_url = build_url($edit_url_map, get_module_zone('admin_themes'));

        $tree = do_template('TEMPLATE_TREE', array(
            '_GUID' => 'ff2a2233b8b4045ba4d8777595ef64c7',
            'HIDDEN' => '',
            'EDIT_URL' => $edit_url,
            'TREE' => $_tree,
        ));

        $middle_spt = do_template('TEMPLATE_TREE_SCREEN', array(
            '_GUID' => 'ab859f67dcb635fcb4d1747d3c6a2c17',
            'TITLE' => get_screen_title('TEMPLATE_TREE'),
            'TREE' => $tree,
        ));

        if ($special_page_type == 'templates') {
            // There's some templates we won't open in the editor...

            $boring_templates = array(
                'templates/CSS_NEED_INLINE.tpl' => true,
                'templates/CSS_NEED.tpl' => true,
                'templates/JAVASCRIPT_NEED.tpl' => true,
                'templates/MENU_STAFF_LINK.tpl' => true,
                'templates/HYPERLINK.tpl' => true,
                'templates/COMCODE_SURROUND.tpl' => true,
                'templates/PARAGRAPH.tpl' => true,
            );
            $collected_templates = array_diff_key($collected_templates, $boring_templates);
            foreach ($collected_templates as $template => $_) {
                if (substr($template, -3) == '.js') {
                    unset($collected_templates[$template]);
                }
            }

            // We redirect straight into the template editor...

            $url_map = array(
                'page' => 'admin_themes',
                'type' => 'edit_templates',
                'live_preview_url' => protect_url_parameter(get_self_url(true, false, array('special_page_type' => null))),
                'theme' => $GLOBALS['FORUM_DRIVER']->get_theme(),
                'default_theme_files_location' => get_zone_name() . ':' . get_page_name(),
            );
            foreach (array_keys($collected_templates) as $i => $template_file) {
                $url_map['f' . strval($i) . 'file'] = $template_file;
            }
            $url_map['keep_wide_high'] = 1;
            $url = build_url($url_map, get_module_zone('admin_themes'));

            require_code('site2');
            smart_redirect($url->evaluate());
        }
    }

    // Query mode?
    if ($special_page_type == 'query') {
        require_lang('profiling');

        global $QUERY_LIST;
        $queries = new Tempcode();
        $total_time = 0.0;
        $sort_order = get_param_string('query_sort', 'time');
        switch ($sort_order) {
            case 'time':
                sort_maps_by($QUERY_LIST, 'time');
                break;
            case 'text':
                sort_maps_by($QUERY_LIST, 'text');
                break;
        }
        $QUERY_LIST = array_reverse($QUERY_LIST);
        foreach ($QUERY_LIST as $query) {
            $queries->attach(do_template('QUERY_LOG', array(
                '_GUID' => 'ab88e1e92609136229ad920c30647647',
                'TIME' => float_format($query['time'], 3),
                'ROWS' => ($query['rows'] === null) ? '' : integer_format($query['rows']),
                'TEXT' => $query['text'],
            )));
            $total_time += $query['time'];
        }
        $title = get_screen_title('VIEW_PAGE_QUERIES');
        $total = count($QUERY_LIST);
        $middle_spt = do_template('QUERY_SCREEN', array(
            '_GUID' => '5f679c8f657b4e4ae94ae2d0ed4843fa',
            'TITLE' => $title,
            'TOTAL' => integer_format($total),
            'TOTAL_TIME' => float_format($total_time, 3),
            'QUERIES' => $queries,
        ));
    }

    $echo = globalise($middle_spt, null, '', true);
    $echo->evaluate_echo();

    exit();
}

/**
 * Takes the output from the scripts, and check the XHTML for conformance, then echoes the page, plus the webstandards checking results.
 *
 * @param  string $out The XHTML to check
 * @param  boolean $display_regardless Display XHTML output regardless of whether there was an error or not
 * @param  integer $preview_mode Whether we are opening up an XHTML-fragment in a preview box (0 means no, 1 means yes, 2 means we are asking for additional manual check information)
 * @set 0 1 2
 * @param  boolean $ret Whether to return Tempcode
 * @return string Returned result (won't return it $ret is false)
 */
function check_xhtml_webstandards($out, $display_regardless = false, $preview_mode = 0, $ret = false)
{
    if ((!$display_regardless) && ($preview_mode == 0)) {
        $hash = md5($out);
        $test = $GLOBALS['SITE_DB']->query_select_value_if_there('webstandards_checked_once', 'hash', array('hash' => $hash));
        if ($test !== null) {
            return '';
        }
    }

    require_lang('webstandards');
    require_css('webstandards');
    require_code('crypt');
    require_code('webstandards');

    global $EXTRA_CHECK;
    $show = false;
    do {
        $error = check_xhtml(
            $out,
            get_option('webstandards_xhtml') != '1',
            $preview_mode != 0,
            get_option('webstandards_javascript') == '1',
            get_option('webstandards_css') == '1',
            get_option('webstandards_wcag') == '1',
            get_option('webstandards_compat') == '1',
            get_option('webstandards_ext_files') == '1',
            $display_regardless || ($preview_mode == 2),
            get_option('csp_enabled') != '0'
        );
        $show = (count($error['errors']) != 0) || ($display_regardless);
        if ((!$show) && (get_option('webstandards_ext_files') == '1')) {
            $out = array_pop($EXTRA_CHECK);
        }
    } while ((!$show) && ($out !== null) && (get_option('webstandards_ext_files') == '1'));

    if ($show) {
        return display_webstandards_results($out, $error, $preview_mode != 0, $ret);
    } elseif ($preview_mode == 0) {
        $GLOBALS['SITE_DB']->query_insert('webstandards_checked_once', array('hash' => $hash), false, true);
    }
    return '';
}

/**
 * Show results of running a webstandards checking function.
 *
 * @param  string $out The data that was checked
 * @param  array $error Error information
 * @param  boolean $preview_mode Whether we are opening up an XHTML-fragment in a preview box
 * @param  boolean $ret Whether to return Tempcode
 * @return string Returned result (won't return it $ret is false)
 */
function display_webstandards_results($out, $error, $preview_mode = false, $ret = false)
{
    global $KEEP_MARKERS, $SHOW_EDIT_LINKS;
    $KEEP_MARKERS = false;
    $SHOW_EDIT_LINKS = false;

    global $XHTML_SPIT_OUT;
    $XHTML_SPIT_OUT = true;

    if (php_function_allowed('set_time_limit')) {
        @set_time_limit(280);
    }

    require_css('webstandards');

    ob_start();

    $title = get_screen_title('WEBSTANDARDS_ERROR');

    // Escape and colourfy
    $i = 0;

    // Output header
    if (!has_interesting_post_fields()) {
        if (get_param_integer('keep_markers', 0) == 1) {
            $messy_url = new Tempcode();
        } else {
            $messy_url = build_url(array('page' => '_SELF', 'special_page_type' => 'code', 'keep_markers' => 1), '_SELF', array(), true);
        }
        $ignore_url = build_url(array('page' => '_SELF', 'keep_webstandards_check' => 0), '_SELF', array(), true);
        $ignore_url_2 = build_url(array('page' => '_SELF', 'webstandards_check' => 0), '_SELF', array(), true);
    } else {
        $messy_url = new Tempcode();
        $ignore_url = new Tempcode();
        $ignore_url_2 = new Tempcode();
    }
    $error_lines = array();
    $return_url = new Tempcode();
    if (count($error['errors']) != 0) {
        $errorst = new Tempcode();
        foreach ($error['errors'] as $j => $_error) {
            $errorst->attach(do_template('WEBSTANDARDS_ERROR', array(
                '_GUID' => '2239470f4b9bd38fcb570689cecaedd2',
                'I' => strval($j),
                'LINE' => integer_format($_error['line']),
                'POS' => integer_format($_error['pos']),
                'ERROR' => $_error['error'],
            )));
            $error_lines[$_error['line']] = 1;
        }
        $errors = $errorst->evaluate();
        $echo = do_template('WEBSTANDARDS_ERROR_SCREEN', array(
            '_GUID' => 'db6c362632471e7c856380d32da91054',
            'MSG' => do_lang_tempcode('NEXT_ITEM_BACK'),
            'RETURN_URL' => $return_url,
            'TITLE' => $title,
            'IGNORE_URL_2' => $ignore_url_2,
            'IGNORE_URL' => $ignore_url,
            'MESSY_URL' => $messy_url,
            'ERRORS' => $errorst,
            'RET' => $ret,
        ));
        unset($errorst);
        $echo->evaluate_echo();
    } else {
        $echo = do_template('WEBSTANDARDS_SCREEN', array(
            '_GUID' => 'd8de848803287e4c592418d57450b7db',
            'MSG' => do_lang_tempcode('NEXT_ITEM_BACK'),
            'RETURN_URL' => $return_url,
            'TITLE' => get_screen_title('VIEWING_SOURCE'),
            'MESSY_URL' => $messy_url,
            'RET' => $ret,
        ));
        $echo->evaluate_echo();
    }

    $level_ranges = $error['level_ranges'];
    $tag_ranges = $error['tag_ranges'];
    $value_ranges = $error['value_ranges'];

    $current_range = 0;
    $current_tag = 0;
    $current_value = 0;
    $number = 1;
    $in_at = false;

    for ($i = 0; $i < strlen($out); ++$i) {
        if (isset($level_ranges[$current_range])) {
            $level = $level_ranges[$current_range][0];
            $start = $level_ranges[$current_range][1];
            if ($start == 0) {
                $start = 1; // Hack for when error starts before a line, messing up our output
            }

            if ($i == $start) { // Add in a font tag
                $x = 8;
                if ($level % $x == 0) {
                    $colour = 'teal';
                }
                if ($level % $x == 1) {
                    $colour = 'blue';
                }
                if ($level % $x == 2) {
                    $colour = 'purple';
                }
                if ($level % $x == 3) {
                    $colour = 'gray';
                }
                if ($level % $x == 4) {
                    $colour = 'red';
                }
                if ($level % $x == 5) {
                    $colour = 'maroon';
                }
                if ($level % $x == 6) {
                    $colour = 'navy';
                }
                if ($level % $x == 7) {
                    $colour = 'olive';
                }
                $previous = ($i == 0) ? '' : $out[$i - 1];
                $string = new Tempcode();
                if (($previous == ' ') || ($previous == "\n") || ($previous == "\r")) {
                    $string->attach(str_pad('', $level * 3 * 6, '&nbsp;'));
                }
                $string->attach(do_template('WEBSTANDARDS_TAG_START', array('_GUID' => '3a4c99283d32006143fc688ce8f2cadc', 'COLOUR' => $colour)));
                $string->evaluate_echo();
            }
        }

        if (isset($tag_ranges[$current_tag])) {
            $start = $tag_ranges[$current_tag][0];

            if ($i == $start) { // Add in a strong tag
                $string = do_template('WEBSTANDARDS_TAG_NAME_START');
                $string->evaluate_echo();
            }
        }

        if (isset($value_ranges[$current_value])) {
            $start = $value_ranges[$current_value][0];

            if ($i == $start) { // Add in a em tag
                $in_at = true;
                $string = do_template('WEBSTANDARDS_ATTRIBUTE_START');
                $string->evaluate_echo();
            }
        }

        $char = $out[$i];

        if (($char == "\n") || ($i == 0)) {
            if ($number > 1) {
                $escaped_code = do_template('WEBSTANDARDS_LINE_END');
                $escaped_code->evaluate_echo();
            }
            $matches = array();
            if (preg_match('#\s*\n#A', $out, $matches, 0, $i + 1) != 0) {
                // Do not show blank lines
                ++$number;
                continue;
            }
            if (isset($error_lines[$number])) {
                $markers = new Tempcode();
                foreach ($error['errors'] as $j => $_error) {
                    if ($number == $_error['line']) {
                        $markers->attach(do_template('WEBSTANDARDS_MARKER', array('_GUID' => '4b1898d5f1e0f56d18a47561659da3bb', 'I' => strval($j), 'ERROR' => $_error['error'])));
                    }
                }
                $escaped_code = do_template('WEBSTANDARDS_LINE_ERROR', array('_GUID' => '2ffa5c26090d3d814206e3a9e46c7b4e', 'MARKERS' => $markers, 'NUMBER' => integer_format($number)));
                $escaped_code->evaluate_echo();
            } else {
                $escaped_code = do_template('WEBSTANDARDS_LINE_START', array('_GUID' => '4994f4748c3cd0cbf4e9278ca0e9b1fc', 'NUMBER' => integer_format($number)));
                $escaped_code->evaluate_echo();
            }
            ++$number;
        }

        // Marker
        $end_markers = new Tempcode();
        if (isset($error_lines[$number])) {
            foreach ($error['errors'] as $_error) {
                if ($i == $_error['global_pos']) {
                    $_text = do_template('WEBSTANDARDS_MARKER_START');
                    $_text->evaluate_echo();
                    if ($char == "\r" || ($char == "\n")) {
                        $__text = '!' . do_lang('HERE') . '!';
                        if (function_exists('ocp_mark_as_escaped')) {
                            ocp_mark_as_escaped($__text);
                        }
                        echo $__text;
                    }

                    $end_markers->attach(do_template('WEBSTANDARDS_MARKER_END'));
                }
            }
        }

        // Escaping
        if ($char == '&') {
            $char = '&amp;';
        }
        elseif ($char == '<') {
            $char = '&lt;';
        }
        elseif ($char == '>') {
            $char = '&gt;';
        }
        elseif ($char == '"') {
            $char = '&quot;';
        }
        elseif ($char == '\'') {
            $char = '&#039;';
        }
        if (($level_ranges === null) && ($char == ' ')) {
            $char = '&nbsp;';
        }
        if (($level_ranges === null) && ($char == "\t")) {
            $char = '&nbsp;&nbsp;&nbsp;';
        }
        //if ($char == ' ') $char = '&nbsp;';
        if (function_exists('ocp_mark_as_escaped')) {
            ocp_mark_as_escaped($char);
        }
        echo $char;

        // Marker
        $end_markers->evaluate_echo();

        if (isset($value_ranges[$current_value])) {
            $end = $value_ranges[$current_value][1];

            if ($i == $end - 1) {
                if ($in_at) {
                    $text = do_template('WEBSTANDARDS_ATTRIBUTE_END');
                    $text->evaluate_echo();
                }
                $in_at = false;
                ++$current_value;
            }
        }

        if (isset($level_ranges[$current_range])) {
            $end = $level_ranges[$current_range][2];

            if ($i == $end - 1) {
                $string = do_template('WEBSTANDARDS_TAG_END');
                $string->evaluate_echo();
                ++$current_range;
                while ((isset($level_ranges[$current_range])) && ($level_ranges[$current_range][1] <= $i)) {
                    ++$current_range;
                }
            }
        }

        if (isset($tag_ranges[$current_tag])) {
            $end = $tag_ranges[$current_tag][1];

            if ($i == $end - 1) {
                $string = do_template('WEBSTANDARDS_TAG_NAME_END');
                $string->evaluate_echo();
                ++$current_tag;
            }
        }
    }

    if ($number > 1) {
        $escaped_code = do_template('WEBSTANDARDS_LINE_END');
        $escaped_code->evaluate_echo();
    }

    $echo = do_template('WEBSTANDARDS_SCREEN_END', array('_GUID' => '739514a06ae65252293fc62b1c7cec40', 'RET' => $ret));
    $echo->evaluate_echo();
    if (!$ret) {
        $echo = globalise(make_string_tempcode(ob_get_contents()), null, '', true);
        ob_end_clean();
        $echo->evaluate_echo();
        exit();
    }
    $out = ob_get_clean();
    return $out;
}

/**
 * Attach a message showing memory usage.
 *
 * @param  Tempcode $messages_bottom Where to place the message
 */
function attach_message_memory_usage(&$messages_bottom)
{
    $memory_usage = memory_get_peak_usage();
    $messages_bottom->attach(do_template('MESSAGE', array(
        '_GUID' => 'd605c0d111742a8cd2d4ef270a1e5fe1',
        'TYPE' => 'inform',
        'MESSAGE' => do_lang_tempcode('MEMORY_USAGE', escape_html(float_format(floatval($memory_usage) / 1024.0 / 1024.0, 2))),
    )));
}
