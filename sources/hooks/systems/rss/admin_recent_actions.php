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
 * @package    actionlog
 */

/**
 * Hook class.
 */
class Hook_rss_admin_recent_actions
{
    /**
     * Run function for RSS hooks.
     *
     * @param  string $_filters A list of categories we accept from
     * @param  TIME $cutoff Cutoff time, before which we do not show results from
     * @param  string $prefix Prefix that represents the template set we use
     * @set    RSS_ ATOM_
     * @param  string $date_string The standard format of date to use for the syndication type represented in the prefix
     * @param  integer $max The maximum number of entries to return, ordering by date
     * @return ?array A pair: The main syndication section, and a title (null: error)
     */
    public function run($_filters, $cutoff, $prefix, $date_string, $max)
    {
        if (!has_actual_page_access(get_member(), 'admin_actionlog')) {
            return null;
        }

        $filters = selectcode_to_sqlfragment($_filters, 'member_id', 'f_members', null, 'member_id', 'id');

        $rows = $GLOBALS['SITE_DB']->query('SELECT * FROM ' . $GLOBALS['SITE_DB']->get_table_prefix() . 'actionlogs WHERE date_and_time>' . strval($cutoff) . ' AND ' . $filters . ' ORDER BY date_and_time DESC', $max);

        require_all_lang();

        $content = new Tempcode();
        foreach ($rows as $row) {
            $id = strval($row['id']);
            $author = $GLOBALS['FORUM_DRIVER']->get_username($row['member_id']);
            $author .= ' / ' . $row['ip'];

            $news_date = date($date_string, $row['date_and_time']);
            $edit_date = escape_html('');

            $type = do_lang($row['the_type'], null, null, null, null, false);
            if ($type === null) {
                $type = $row['the_type'];
            }
            $news_title = xmlentities($type);
            $_summary = $row['param_a'] . (($row['param_b'] == '') ? '' : ' / ') . $row['param_b'];
            $summary = xmlentities($_summary);
            $news = escape_html('');

            $category = $type;
            $category_raw = $type;

            $view_url = build_url(array('page' => 'admin_actionlog', 'type' => 'view', 'id' => $row['id'], 'mode' => 'cms'), get_module_zone('admin_actionlog'));

            if ($prefix == 'RSS_') {
                $if_comments = do_template('RSS_ENTRY_COMMENTS', array('_GUID' => 'c237ee93e6ff879b09eb93048a1f539b', 'COMMENT_URL' => $view_url, 'ID' => $id), null, false, null, '.xml', 'xml');
            } else {
                $if_comments = new Tempcode();
            }

            $content->attach(do_template($prefix . 'ENTRY', array('VIEW_URL' => $view_url, 'SUMMARY' => $summary, 'EDIT_DATE' => $edit_date, 'IF_COMMENTS' => $if_comments, 'TITLE' => $news_title, 'CATEGORY_RAW' => $category_raw, 'CATEGORY' => $category, 'AUTHOR' => $author, 'ID' => $id, 'NEWS' => $news, 'DATE' => $news_date), null, false, null, '.xml', 'xml'));
        }

        return array($content, do_lang('VIEW_ACTIONLOGS'));
    }
}
