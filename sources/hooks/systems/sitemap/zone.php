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
 * Hook class.
 */
class Hook_sitemap_zone extends Hook_sitemap_base
{
    /**
     * Get the permission page that nodes matching $page_link in this hook are tied to.
     * The permission page is where privileges may be overridden against.
     *
     * @param  string $page_link The page-link
     * @return ?ID_TEXT The permission page (null: none)
     */
    public function get_privilege_page($page_link)
    {
        return 'cms_comcode_pages';
    }

    /**
     * Find if a page-link will be covered by this node.
     *
     * @param  ID_TEXT $page_link The page-link
     * @return integer A SITEMAP_NODE_* constant
     */
    public function handles_page_link($page_link)
    {
        if (get_option('collapse_user_zones') == '0') {
            if ($page_link == ':') {
                return SITEMAP_NODE_NOT_HANDLED;
            }
        }

        if (preg_match('#^([^:]*):$#', $page_link) != 0) {
            return SITEMAP_NODE_HANDLED;
        }
        return SITEMAP_NODE_NOT_HANDLED;
    }

    /**
     * Convert a page-link to a category ID and category permission module type.
     *
     * @param  ID_TEXT $page_link The page-link
     * @return ?array The pair (null: permission modules not handled)
     */
    public function extract_child_page_link_permission_pair($page_link)
    {
        $matches = array();
        preg_match('#^([^:]*):$#', $page_link, $matches);
        $zone = $matches[1];

        return array($zone, 'zone_page');
    }

    /**
     * Find details of a position in the Sitemap.
     *
     * @param  ID_TEXT $page_link The page-link we are finding
     * @param  ?string $callback Callback function to send discovered page-links to (null: return)
     * @param  ?array $valid_node_types List of node types we will return/recurse-through (null: no limit)
     * @param  ?integer $child_cutoff Maximum number of children before we cut off all children (null: no limit)
     * @param  ?integer $max_recurse_depth How deep to go from the Sitemap root (null: no limit)
     * @param  integer $recurse_level Our recursion depth (used to limit recursion, or to calculate importance of page-link, used for instance by XML Sitemap [deeper is typically less important])
     * @param  integer $options A bitmask of SITEMAP_GEN_* options
     * @param  ID_TEXT $zone The zone we will consider ourselves to be operating in (needed due to transparent redirects feature)
     * @param  integer $meta_gather A bitmask of SITEMAP_GATHER_* constants, of extra data to include
     * @param  ?array $row Database row (null: lookup)
     * @param  boolean $return_anyway Whether to return the structure even if there was a callback. Do not pass this setting through via recursion due to memory concerns, it is used only to gather information to detect and prevent parent/child duplication of default entry points.
     * @return ?array Node structure (null: working via callback / error)
     */
    public function get_node($page_link, $callback = null, $valid_node_types = null, $child_cutoff = null, $max_recurse_depth = null, $recurse_level = 0, $options = 0, $zone = '_SEARCH', $meta_gather = 0, $row = null, $return_anyway = false)
    {
        $matches = array();
        preg_match('#^([^:]*):#', $page_link, $matches);
        $zone = $matches[1]; // overrides $zone which we must replace

        $no_self_pages = false;
        if ($zone == 'site' && get_option('collapse_user_zones') == '1') {
            $zone = '';
            $no_self_pages = true;
        }

        if (!isset($row)) {
            $rows = $GLOBALS['SITE_DB']->query_select('zones', array('zone_title', 'zone_default_page'), array('zone_name' => $zone), '', 1);
            if (!isset($rows[0])) {
                return null;
            }
            $row = array($zone, get_translated_text($rows[0]['zone_title']), $rows[0]['zone_default_page']);
        }
        $title = $row[1];
        $zone_default_page = $row[2];

        $path = get_custom_file_base() . '/' . $zone . '/index.php';
        if (!is_file($path)) {
            $path = get_file_base() . '/' . $zone . '/index.php';
        }

        $icon = mixed();
        switch ($zone) {
            case '':
            case 'site':
                $icon = 'menu/home';
                break;
            case 'adminzone':
                $icon = 'menu/adminzone/adminzone';
                break;
            case 'cms':
                $icon = 'menu/cms/cms';
                if (($options & SITEMAP_GEN_USE_PAGE_GROUPINGS) != 0) {
                    $title = do_lang('CONTENT');
                }
                break;
            case 'forum':
                $icon = 'menu/social/forum/forums';
                break;
            case 'docs':
                $icon = 'menu/pages/help';
                break;
        }

        $struct = array(
            'title' => make_string_tempcode($title),
            'content_type' => 'zone',
            'content_id' => $zone,
            'modifiers' => array(),
            'only_on_page' => '',
            'page_link' => $page_link,
            'url' => null,
            'extra_meta' => array(
                'description' => null,
                'image' => ($icon === null) ? null : find_theme_image('icons/24x24/' . $icon),
                'image_2x' => ($icon === null) ? null : find_theme_image('icons/48x48/' . $icon),
                'add_date' => (($meta_gather & SITEMAP_GATHER_TIMES) != 0 && file_exists($path)) ? filectime($path) : null,
                'edit_date' => (($meta_gather & SITEMAP_GATHER_TIMES) != 0 && file_exists($path)) ? filemtime($path) : null,
                'submitter' => null,
                'views' => null,
                'rating' => null,
                'meta_keywords' => null,
                'meta_description' => null,
                'categories' => null,
                'validated' => null,
                'db_row' => (($meta_gather & SITEMAP_GATHER_DB_ROW) != 0) ? $row : null,
            ),
            'permissions' => array(
                array(
                    'type' => 'zone',
                    'zone_name' => $zone,
                    'is_owned_at_this_level' => true,
                ),
            ),
            'children' => null,
            'has_possible_children' => true,

            // These are likely to be changed in individual hooks
            'sitemap_priority' => SITEMAP_IMPORTANCE_ULTRA,
            'sitemap_refreshfreq' => 'daily',

            'privilege_page' => $this->get_privilege_page($page_link),

            'edit_url' => build_url(array('page' => 'admin_zones', 'type' => '_edit', 'id' => $zone), get_module_zone('admin_zones')),
        );

        if (($options & SITEMAP_GEN_LABEL_CONTENT_TYPES) != 0) {
            $struct['title'] = make_string_tempcode(do_lang('zones:ZONE') . ': ' . $title);
        }

        $comcode_page_sitemap_ob = $this->_get_sitemap_object('comcode_page');
        $page_sitemap_ob = $this->_get_sitemap_object('page');

        $children = array();
        $children_orphaned = array();

        // Get more details from default page? (which isn't linked as a child)
        $page_details = _request_page($zone_default_page, $zone);
        if ($page_details !== false) {
            $page_type = $page_details[0];

            if (strpos($page_type, 'COMCODE') !== false) {
                $child_node = $comcode_page_sitemap_ob->get_node($page_link . $zone_default_page, $callback, $valid_node_types, $child_cutoff, $max_recurse_depth, $recurse_level + 1, $options, $zone, $meta_gather);
            } else {
                $child_node = $page_sitemap_ob->get_node($page_link . $zone_default_page, $callback, $valid_node_types, $child_cutoff, $max_recurse_depth, $recurse_level + 1, $options, $zone, $meta_gather);
            }

            if ($child_node !== null) {
                //$struct['title']=$child_node['title'];
                foreach (array('description', 'image', 'image_2x', 'submitter', 'views', 'meta_keywords', 'meta_description', 'validated') as $key) {
                    if ($child_node['extra_meta'][$key] !== null) {
                        if (($struct['extra_meta'][$key] === null) || (!in_array($key, array('image', 'image_2x')))) {
                            $struct['extra_meta'][$key] = $child_node['extra_meta'][$key];
                        }
                    }
                }
                $struct['permissions'] = array_merge($struct['permissions'], $child_node['permissions']);

                if (($options & SITEMAP_GEN_KEEP_FULL_STRUCTURE) == 0) {
                    if ($child_node['children'] !== null) {
                        $children = array_merge($children, $child_node['children']);
                    }
                } else {
                    $children[] = $child_node;
                }
            }
        }

        if (!$this->_check_node_permissions($struct)) {
            return null;
        }

        // What page groupings may apply in what zones? (in display order)
        $applicable_page_groupings = array();
        if (($options & SITEMAP_GEN_USE_PAGE_GROUPINGS) != 0) {
            switch ($zone) {
                case 'adminzone':
                    $applicable_page_groupings = array(
                        'audit',
                        'security',
                        'structure',
                        'style',
                        'setup',
                        'tools',
                    );
                    break;

                case '':
                    if (get_option('collapse_user_zones') == '0') {
                        $applicable_page_groupings = array();
                        break;
                    } // else flow on...

                case 'site':
                    $applicable_page_groupings = array(
                        'pages',
                        'rich_content',
                        'social',
                        'site_meta',
                    );
                    break;

                case 'cms':
                    $applicable_page_groupings = array(
                        'cms',
                    );
                    break;
            }
        }

        $call_struct = true;

        // Categories done after node callback, to ensure sensible ordering
        if (($max_recurse_depth === null) || ($recurse_level < $max_recurse_depth)) {
            $root_comcode_pages = get_root_comcode_pages($zone, true);
            if (($zone == 'site') && (($options & SITEMAP_GEN_COLLAPSE_ZONES) != 0)) {
                $root_comcode_pages += get_root_comcode_pages('', true);
            }

            // Locate all page groupings and pages in them
            $page_groupings = array();
            foreach ($applicable_page_groupings as $page_grouping) {
                $page_groupings[$page_grouping] = array();
            }
            $pages_found = array();
            $links = get_page_grouping_links();
            foreach ($links as $link) {
                list($page_grouping) = $link;

                if ((is_array($link[2])) && (is_string($link[2][2]))) {
                    if (($page_grouping == '') || (in_array($page_grouping, $applicable_page_groupings))) {
                        $pages_found[$link[2][2] . ':' . $link[2][0]] = true;
                    }
                }

                // In a page grouping that is explicitly included
                if (($page_grouping != '') && (in_array($page_grouping, $applicable_page_groupings))) {
                    $page_groupings[$page_grouping][] = $link;
                }
            }
            $pages_found[':' . get_zone_default_page('')] = true;
            $pages_found[$zone . ':' . $zone_default_page] = true;

            // Any left-behind pages?
            // NB: Code largely repeated in page_grouping.php
            $orphaned_pages = array(); // Will be merged into pages/tools/cms groups if they exist, otherwise will go into this level
            foreach ((($zone == 'site') && (($options & SITEMAP_GEN_COLLAPSE_ZONES) != 0)) ? array('site', '') : array($zone) as $_zone) {
                $pages = $no_self_pages ? array() : find_all_pages_wrap($_zone, false, /*$consider_redirects=*/true, /*$show_method = */0, /*$page_type = */($zone != $_zone) ? 'comcode' : null);
                foreach ($pages as $page => $page_type) {
                    if (is_integer($page)) {
                        $page = strval($page);
                    }

                    if (preg_match('#^redirect:#', $page_type) != 0) {
                        $details = $this->_request_page_details($page, $_zone);
                        if ($details === false) {
                            continue;
                        }
                        $page_type = strtolower($details[0]);
                        $pages[$page] = $page_type;
                    }

                    if ((!isset($pages_found[$_zone . ':' . $page])) && ($page != 'recommend_help'/*Special case*/) && ((strpos($page_type, 'comcode') === false/*not a Comcode page*/) || (isset($root_comcode_pages[$_zone . ':' . $page])))) {
                        if ($this->_is_page_omitted_from_sitemap($_zone, $page)) {
                            continue;
                        }
                        $orphaned_pages[$_zone . ':' . $page] = $page_type;
                    }
                }
            }

            $consider_validation = (($options & SITEMAP_GEN_CONSIDER_VALIDATION) != 0);

            // Do page-groupings
            if (count($page_groupings) != 1) { // 0 or more than 1 page groupings
                $page_grouping_sitemap_xml_ob = $this->_get_sitemap_object('page_grouping');

                foreach ($page_groupings as $page_grouping => $page_grouping_pages) {
                    if ($zone == 'cms') {
                        $child_page_link = 'cms:cms:' . $page_grouping;
                    } else {
                        $child_page_link = 'adminzone:admin:' . $page_grouping; // We don't actually link to this, unless it's one of the ones held in the Admin Zone
                    }
                    $row = array(); // We may put extra nodes in here, beyond what the page_group knows
                    if ($page_grouping == 'pages' || $page_grouping == 'tools' || $page_grouping == 'cms') {
                        $row = $orphaned_pages;
                        $orphaned_pages = array();
                    }

                    if ((count($page_grouping_pages) == 0) && (count($row) == 0)) {
                        continue;
                    }

                    if (($valid_node_types !== null) && (!in_array('page_grouping', $valid_node_types))) {
                        continue;
                    }

                    $child_node = $page_grouping_sitemap_xml_ob->get_node($child_page_link, $callback, $valid_node_types, $child_cutoff, $max_recurse_depth, $recurse_level + 1, $options, $zone, $meta_gather, $row);
                    if ($child_node !== null) {
                        $children[] = $child_node;
                    }
                }

                // Any remaining orphaned pages (we have to tag these on as there was no catch-all page grouping in this zone)
                foreach ($orphaned_pages as $page => $page_type) {
                    if (is_integer($page)) {
                        $page = strval($page);
                    }

                    if ($page == $zone_default_page) {
                        continue;
                    }

                    if (strpos($page, ':') !== false) {
                        list($_zone, $page) = explode(':', $page, 2);
                    } else {
                        $_zone = $zone;
                    }

                    $child_page_link = $_zone . ':' . $page;

                    if (strpos($page_type, 'comcode') !== false) {
                        if (($valid_node_types !== null) && (!in_array('comcode_page', $valid_node_types))) {
                            continue;
                        }

                        if (($consider_validation) && (isset($root_comcode_pages[$child_page_link])) && ($root_comcode_pages[$child_page_link] == 0)) {
                            continue;
                        }

                        $child_node = $comcode_page_sitemap_ob->get_node($child_page_link, $callback, $valid_node_types, $child_cutoff, $max_recurse_depth, $recurse_level + 1, $options, $_zone, $meta_gather);
                    } else {
                        if (($valid_node_types !== null) && (!in_array('page', $valid_node_types))) {
                            continue;
                        }

                        $child_node = $page_sitemap_ob->get_node($child_page_link, $callback, $valid_node_types, $child_cutoff, $max_recurse_depth, $recurse_level + 1, $options, $_zone, $meta_gather);
                    }

                    if ($child_node !== null) {
                        if (preg_match('#^redirect:#', $page_type) != 0) {
                            if (($options & SITEMAP_GEN_LABEL_CONTENT_TYPES) != 0) {
                                list(, $redir_zone, $redir_page) = explode(':', $page_type);
                                require_code('xml');
                                $struct['title'] = make_string_tempcode(strip_html(str_replace(array('<kbd>', '</kbd>'), array('"', '"'), do_lang('zones:REDIRECT_PAGE_TO', xmlentities($redir_zone), xmlentities($redir_page)))) . ': ' . (is_string($page) ? $page : strval($page)));
                            }
                        }

                        if (($_zone == 'site' || $_zone == 'adminzone') && (($options & SITEMAP_GEN_USE_PAGE_GROUPINGS) != 0)) {
                            $child_node['is_unexpected_orphan'] = true; // This should never be set, it indicates a page not in a page grouping
                        }

                        $children_orphaned[] = $child_node;
                    }
                }
            } else { // 1 page group exactly
                // Show contents of group directly...

                $comcode_page_sitemap_ob = $this->_get_sitemap_object('comcode_page');
                $page_sitemap_ob = $this->_get_sitemap_object('page');

                foreach ($page_groupings as $links) { // Will only be 1 loop iteration, but this finds us that one easily
                    $child_links = array();

                    foreach ($links as $link) {
                        $title = $link[3];
                        $icon = $link[1];

                        $_zone = $link[2][2];
                        $page = $link[2][0];

                        $child_page_link = $_zone . ':' . $page;
                        foreach ($link[2][1] as $key => $val) {
                            if (!is_string($val)) {
                                $val = strval($val);
                            }

                            if ($key == 'type' || $key == 'id') {
                                $child_page_link .= ':' . urlencode($val);
                            } else {
                                $child_page_link .= ':' . urlencode($key) . '=' . urlencode($val);
                            }
                        }

                        $child_description = null;
                        if (isset($link[4])) {
                            $child_description = (is_object($link[4])) ? $link[4] : comcode_lang_string($link[4]);
                        }

                        $child_links[] = array($title, $child_page_link, $icon, null/*unknown/irrelevant $page_type*/, $child_description);
                    }

                    foreach ($orphaned_pages as $page => $page_type) {
                        if (is_integer($page)) {
                            $page = strval($page);
                        }

                        if (strpos($page, ':') !== false) {
                            list($_zone, $page) = explode(':', $page, 2);
                        } else {
                            $_zone = $zone;
                        }

                        $child_page_link = $_zone . ':' . $page;

                        $child_links[] = array(titleify($page), $child_page_link, null, $page_type, null);
                    }

                    // Render children, in title order
                    foreach ($child_links as $child_link) {
                        $title = $child_link[0];
                        $description = $child_link[4];
                        $icon = $child_link[2];
                        $child_page_link = $child_link[1];
                        $page_type = $child_link[3];

                        $child_row = ($icon === null) ? null/*we know nothing of relevance*/ : array($title, $icon, $description);

                        if (($page_type !== null) && (strpos($page_type, 'comcode') !== false)) {
                            if (($valid_node_types !== null) && (!in_array('comcode_page', $valid_node_types))) {
                                continue;
                            }

                            if (($consider_validation) && (isset($root_comcode_pages[$zone . ':' . $page])) && ($root_comcode_pages[$zone . ':' . $page] == 0)) {
                                continue;
                            }

                            $child_node = $comcode_page_sitemap_ob->get_node($child_page_link, $callback, $valid_node_types, $child_cutoff, $max_recurse_depth, $recurse_level + 1, $options, $zone, $meta_gather, $child_row);
                        } else {
                            if (($valid_node_types !== null) && (!in_array('page', $valid_node_types))) {
                                continue;
                            }

                            $child_node = $page_sitemap_ob->get_node($child_page_link, $callback, $valid_node_types, $child_cutoff, $max_recurse_depth, $recurse_level + 1, $options, $zone, $meta_gather, $child_row);
                        }
                        if ($child_node !== null) {
                            $children_orphaned[] = $child_node;
                        }
                    }
                }
            }

            sort_maps_by($children_orphaned, 'title');
            $children = array_merge($children, $children_orphaned);

            $struct['children'] = $children;
        }

        if ($callback !== null && $call_struct) {
            call_user_func($callback, $struct);
        }

        return ($callback === null || $return_anyway) ? $struct : null;
    }
}
