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
 * @package    core
 */

/**
 * Block class.
 */
class Block_main_comcode_page_children
{
    /**
     * Find details of the block.
     *
     * @return ?array Map of block info (null: block is disabled)
     */
    public function info()
    {
        $info = array();
        $info['author'] = 'Chris Graham';
        $info['organisation'] = 'ocProducts';
        $info['hacked_by'] = null;
        $info['hack_version'] = null;
        $info['version'] = 2;
        $info['locked'] = false;
        $info['parameters'] = array('param', 'zone');
        return $info;
    }

    /**
     * Find caching details for the block.
     *
     * @return ?array Map of cache details (cache_on and ttl) (null: block is disabled)
     */
    public function caching_environment()
    {
        $info = array();
        $info['cache_on'] = 'array(((array_key_exists(\'param\',$map)) && ($map[\'param\']!=\'\'))?$map[\'param\']:get_page_name(),array_key_exists(\'zone\',$map)?$map[\'zone\']:post_param_string(\'zone\',get_comcode_zone(((array_key_exists(\'param\',$map)) && ($map[\'param\']!=\'\'))?$map[\'param\']:get_page_name(),false)))';
        $info['special_cache_flags'] = CACHE_AGAINST_DEFAULT | CACHE_AGAINST_PERMISSIVE_GROUPS; // Due to see_unvalidated privilege
        $info['ttl'] = (get_value('disable_block_timeout') === '1') ? 60 * 60 * 24 * 365 * 5/*5 year timeout*/ : 60 * 24 * 7;
        return $info;
    }

    /**
     * Execute the block.
     *
     * @param  array $map A map of parameters
     * @return Tempcode The result of execution
     */
    public function run($map)
    {
        $block_id = get_block_id($map);

        $page = ((array_key_exists('param', $map)) && ($map['param'] != '')) ? $map['param'] : get_page_name();

        $zone = array_key_exists('zone', $map) ? $map['zone'] : post_param_string('zone', get_comcode_zone($page, false));
        if ($zone == '_SEARCH') {
            $zone = null;
        }
        $qmap = array('p_parent_page' => $page);
        if ($zone !== null) {
            $qmap['the_zone'] = $zone;
        }
        if ((!has_privilege(get_member(), 'see_unvalidated')) && (addon_installed('unvalidated'))) {
            $qmap['p_validated'] = 1;
        }
        $children = $GLOBALS['SITE_DB']->query_select('comcode_pages', array('the_page', 'the_zone', 'p_order'), $qmap, 'ORDER BY p_order,the_page');
        foreach ($children as $i => $child) {
            if (($child['the_page'] == $page) && ($child['the_zone'] == $zone)) {
                unset($children[$i]);
                continue; // Be safe
            }

            $_title = $GLOBALS['SITE_DB']->query_select_value_if_there('cached_comcode_pages', 'cc_page_title', array('the_page' => $child['the_page'], 'the_zone' => $child['the_zone']));
            if ($_title !== null) {
                $title = get_translated_text($_title, null, null, true);
                if ($title === null) {
                    $title = '';
                }
            } else {
                $title = '';

                if (get_option('is_on_comcode_page_cache') == '1') { // Try and force a parse of the page
                    // Virtualised state, so that any nested main_comcode_page_children blocks execute correctly
                    require_code('urls2');
                    list($old_get, $old_zone, $old_current_script) = set_execution_context(
                        array('page' => $child['the_page']),
                        $child['the_zone']
                    );

                    // Execute child page and get its title
                    push_output_state();
                    request_page($child['the_page'], false, $child['the_zone'], null, true);
                    restore_output_state();
                    $_title = $GLOBALS['SITE_DB']->query_select_value_if_there('cached_comcode_pages', 'cc_page_title', array('the_page' => $child['the_page'], 'the_zone' => $child['the_zone']));
                    if ($_title !== null) {
                        $title = get_translated_text($_title);
                    }

                    // Get things back to prior state
                    set_execution_context(
                        $old_get,
                        $old_zone,
                        $old_current_script,
                        false
                    );
                }
            }

            if ($title == '') {
                $title = titleify($child['the_page']);
            }

            $child['TITLE'] = $title;
            $child['PAGE'] = $child['the_page'];
            $child['ZONE'] = get_comcode_zone($child['the_page'], false);
            $child['ORDER'] = $child['p_order'];

            if (($child['ZONE'] === null) || ($child['PAGE'] === null)) {
                unset($children[$i]);
                continue;
            }

            $children[$i] = $child;
        }

        sort_maps_by($children, 'ORDER,TITLE');

        return do_template('BLOCK_MAIN_COMCODE_PAGE_CHILDREN', array(
            '_GUID' => '375aa1907fc6b2ca6b23ab5b5139aaef',
            'BLOCK_ID' => $block_id,
            'CHILDREN' => $children,
            'THE_PAGE' => $page,
            'THE_ZONE' => $zone,
        ));
    }
}
