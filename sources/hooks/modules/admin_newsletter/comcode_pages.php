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
 * @package    core_comcode_pages
 */

/**
 * Hook class.
 */
class Hook_whatsnew_comcode_pages
{
    /**
     * Find selectable (filterable) categories.
     *
     * @param  TIME $updated_since The time that there must be entries found newer than
     * @return ?array Tuple of result details: HTML list of all types that can be choosed, title for selection list (null: disabled)
     */
    public function choose_categories($updated_since)
    {
        require_code('zones3');
        $cats = create_selection_list_zones(null, array(), null, $updated_since);
        return array($cats, do_lang('PAGES'));
    }

    /**
     * Run function for newsletter hooks.
     *
     * @param  TIME $cutoff_time The time that the entries found must be newer than
     * @param  LANGUAGE_NAME $lang The language the entries found must be in
     * @param  string $filter Category filter to apply
     * @return array Tuple of result details
     */
    public function run($cutoff_time, $lang, $filter)
    {
        $max = intval(get_option('max_newsletter_whatsnew'));

        $new = new Tempcode();

        require_code('selectcode');
        if ($filter == '') {
            $filter = ','; // Just welcome zone
        }
        $or_list = selectcode_to_sqlfragment($filter, 'b.the_zone', null, null, null, null, false);

        $_rows = $GLOBALS['SITE_DB']->query('SELECT a.* FROM ' . get_table_prefix() . 'cached_comcode_pages a JOIN ' . get_table_prefix() . 'comcode_pages b ON a.the_page=b.the_page AND a.the_zone=b.the_zone WHERE p_add_date>' . strval($cutoff_time) . ' AND (' . $or_list . ')', $max);
        if (count($_rows) == $max) {
            return array();
        }
        $rows = array();
        foreach ($_rows as $row) {
            $rows[$row['the_zone'] . ':' . $row['the_page']] = $row;
        }
        $_rows2 = $GLOBALS['SITE_DB']->query_select('seo_meta', array('meta_description', 'meta_for_id'), array('meta_for_type' => 'comcode_page'));
        $rows2 = array();
        foreach ($_rows2 as $row) {
            $rows2[$row['meta_for_id']] = $row;
        }
        $zones = explode(',', $filter);//find_all_zones();
        foreach ($zones as $zone) {
            if ($zone == 'cms') {
                continue;
            }
            if ($zone == 'adminzone') {
                continue;
            }

            $pages = find_all_pages($zone, 'comcode_custom/' . get_site_default_lang(), 'txt', false, $cutoff_time);
            foreach (array_keys($pages) as $page) {
                if (!is_string($page)) {
                    $page = strval($page); // PHP can be weird when things like '404' are put in arrays
                }

                if (substr($page, 0, 6) == 'panel_') {
                    continue;
                }

                $id = $zone . ':' . $page;

                $_url = build_url(array('page' => $page), $zone, array(), false, false, true);
                $url = $_url->evaluate();

                list(, , $path) = find_comcode_page($lang, $page, $zone);

                require_code('zones2');
                $name = get_comcode_page_title_from_disk($path);
                if (array_key_exists($id, $rows)) {
                    $_name = get_translated_text($rows[$id]['cc_page_title'], null, null, true);
                    if ($_name !== null) {
                        $name = $_name;
                    }
                }

                $description = '';

                $member_id = null;

                if (array_key_exists($id, $rows2)) {
                    $description = get_translated_text($rows2[$id]['meta_description']);
                }

                $new->attach(do_template('NEWSLETTER_WHATSNEW_RESOURCE_FCOMCODE', array('_GUID' => '67f165847dacd54d2965686d561b57ee', 'MEMBER_ID' => $member_id, 'URL' => $url, 'NAME' => $name, 'DESCRIPTION' => $description, 'CONTENT_TYPE' => 'comcode_page', 'CONTENT_ID' => $zone . ':' . $page), null, false, null, '.txt', 'text'));

                handle_has_checked_recently($url); // We know it works, so mark it valid so as to not waste CPU checking within the generated Comcode
            }
        }

        return array($new, do_lang('PAGES', '', '', '', $lang));
    }
}
