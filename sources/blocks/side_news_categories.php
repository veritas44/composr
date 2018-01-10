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
 * @package    news
 */

/**
 * Block class.
 */
class Block_side_news_categories
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
        $info['parameters'] = array('select');
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
        $info['cache_on'] = 'array(array_key_exists(\'select\', $map) ? $map[\'select\'] : \'\')';
        $info['special_cache_flags'] = CACHE_AGAINST_DEFAULT | CACHE_AGAINST_PERMISSIVE_GROUPS;
        $info['ttl'] = (get_value('no_block_timeout') === '1') ? 60 * 60 * 24 * 365 * 5/*5 year timeout*/ : 60 * 24;
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
        require_lang('news');

        $block_id = get_block_id($map);

        $select = array_key_exists('select', $map) ? $map['select'] : '';
        if ($select == '') {
            $q_filter = '1=1';
        } else {
            require_code('selectcode');
            $q_filter = selectcode_to_sqlfragment($select, 'r.id', 'news_categories', null, 'r.id', 'id');
        }

        $cnt = $GLOBALS['SITE_DB']->query_select_value('news_categories', 'COUNT(*)', array('nc_owner' => null));
        if ($cnt > 100) {
            $categories = $GLOBALS['SITE_DB']->query('SELECT r.* FROM ' . get_table_prefix() . 'news_categories r WHERE nc_owner IS NULL AND EXISTS (SELECT * FROM ' . get_table_prefix() . 'news n WHERE n.news_category=r.id AND n.validated=1) AND ' . $q_filter);
        } else {
            $categories = $GLOBALS['SITE_DB']->query_select('news_categories r', array('*'), array('nc_owner' => null), ' AND ' . $q_filter);
        }

        $categories2 = array();
        foreach ($categories as $category) {
            if (has_category_access(get_member(), 'news', strval($category['id']))) {
                $join = ' LEFT JOIN ' . get_table_prefix() . 'news_category_entries d ON d.news_entry=p.id';
                $count = $GLOBALS['SITE_DB']->query_value_if_there('SELECT COUNT(*) FROM ' . get_table_prefix() . 'news p' . $join . ' WHERE validated=1 AND (news_entry_category=' . strval($category['id']) . ' OR news_category=' . strval($category['id']) . ')');
                if ($count > 0) {
                    $category['_nc_title'] = get_translated_text($category['nc_title']);
                    $categories2[] = $category;
                }
            }
        }
        if (count($categories2) == 0) {
            foreach ($categories as $category) {
                if (has_category_access(get_member(), 'news', strval($category['id']))) {
                    $category['_nc_title'] = get_translated_text($category['nc_title']);
                    $categories2[] = $category;
                }
            }
        }

        sort_maps_by($categories2, '_nc_title');

        $_categories = array();
        foreach ($categories2 as $category) {
            $url = build_url(array('page' => 'news', 'type' => 'browse', 'id' => $category['id']), get_module_zone('news'));
            $name = $category['_nc_title'];
            $_categories[] = array('URL' => $url, 'NAME' => $name, 'COUNT' => integer_format($count));
        }

        return do_template('BLOCK_SIDE_NEWS_CATEGORIES', array(
            '_GUID' => 'b47a0047247096373e5aa626348c4ebb',
            'BLOCK_ID' => $block_id,
            'CATEGORIES' => $_categories,
            'PRE' => '',
            'POST' => '',
        ));
    }
}
