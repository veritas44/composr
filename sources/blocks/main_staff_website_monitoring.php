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
 * @package    core_adminzone_dashboard
 */

/*CQC: No API check*/

/**
 * Block class.
 */
class Block_main_staff_website_monitoring
{
    /**
     * Find details of the block.
     *
     * @return ?array Map of block info (null: block is disabled)
     */
    public function info()
    {
        $info = array();
        $info['author'] = 'Jack Franklin';
        $info['organisation'] = 'ocProducts';
        $info['hacked_by'] = null;
        $info['hack_version'] = null;
        $info['version'] = 3;
        $info['locked'] = false;
        $info['parameters'] = array();
        $info['update_require_upgrade'] = true;
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
        $info['cache_on'] = '(count($_POST)>0)?null:array()'; // No cache on POST as this is when we save text data
        $info['ttl'] = (get_value('disable_block_timeout') === '1') ? 60 * 60 * 24 * 365 * 5/*5 year timeout*/ : 60 * 5;
        return $info;
    }

    /**
     * Uninstall the block.
     */
    public function uninstall()
    {
        $GLOBALS['SITE_DB']->drop_table_if_exists('staff_website_monitoring');
    }

    /**
     * Install the block.
     *
     * @param  ?integer $upgrade_from What version we're upgrading from (null: new install)
     * @param  ?integer $upgrade_from_hack What hack version we're upgrading from (null: new-install/not-upgrading-from-a-hacked-version)
     */
    public function install($upgrade_from = null, $upgrade_from_hack = null)
    {
        if (($upgrade_from === null) || ($upgrade_from < 2)) {
            $GLOBALS['SITE_DB']->create_table('staff_website_monitoring', array(
                'id' => '*AUTO',
                'site_url' => 'URLPATH',
                'site_name' => 'SHORT_TEXT',
            ));

            $GLOBALS['SITE_DB']->query_insert('staff_website_monitoring', array(
                'site_url' => get_base_url(),
                'site_name' => get_site_name(),
            ));
        }
    }

    /**
     * Execute the block.
     *
     * @param  array $map A map of parameters
     * @return Tempcode The result of execution
     */
    public function run($map)
    {
        require_lang('staff_checklist');
        require_code('stats');

        $block_id = get_block_id($map);

        $links = post_param_string('website_monitoring_list_edit', null);
        if ($links !== null) {
            $GLOBALS['SITE_DB']->query_delete('staff_website_monitoring');
            $items = explode("\n", $links);
            foreach ($items as $i) {
                $q = trim($i);
                if (!empty($q)) {
                    $bits = explode('=', $q);
                    if (count($bits) >= 2) {
                        $last_bit = array_pop($bits);
                        $bits = array(implode('=', $bits), $last_bit);
                        $link = $bits[0];
                        $site_name = $bits[1];
                    } else {
                        $link = $q;

                        require_code('http');
                        $meta_details = get_webpage_meta_details($link);
                        $site_name = $meta_details['t_title'];
                        if ($site_name == '') {
                            $site_name = $link;
                        }
                    }
                    $GLOBALS['SITE_DB']->query_insert('staff_website_monitoring', array('site_name' => $site_name, 'site_url' => fixup_protocolless_urls($link)));
                }
            }

            delete_cache_entry('main_staff_website_monitoring');

            log_it('SITE_WATCHLIST');
        }

        $rows = $GLOBALS['SITE_DB']->query_select('staff_website_monitoring');

        $sites_being_watched = array();
        $grid_data = array();
        foreach ($rows as $r) {
            list($rank, $links) = get_alexa_rank(($r['site_url']));

            if ($rank == '') {
                $rank = do_lang('NA');
            }
            if ($links == '') {
                $links = '?';
            }

            $sites_being_watched[$r['site_url']] = $r['site_name'];

            $grid_data[] = array(
                'URL' => $r['site_url'],
                'ALEXA_RANKING' => $rank,
                'ALEXA_LINKS' => $links,
                'SITE_NAME' => $r['site_name'],
            );
        }

        $map_comcode = get_block_ajax_submit_map($map);
        return do_template('BLOCK_MAIN_STAFF_WEBSITE_MONITORING', array(
            '_GUID' => '0abf65878c508bf133836589a8cc45da',
            'BLOCK_ID' => $block_id,
            'URL' => get_self_url(),
            'BLOCK_NAME' => 'main_staff_website_monitoring',
            'MAP' => $map_comcode,
            'SITES_BEING_WATCHED' => $sites_being_watched,
            'GRID_DATA' => $grid_data,
        ));
    }
}
