<?php /*

 Composr
 Copyright (c) ocProducts, 2004-2016

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
 * Hook class.
 */
class Hook_rss_filedump
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
        if (!addon_installed('filedump')) {
            return null;
        }

        if (!has_actual_page_access(get_member(), 'filedump')) {
            return null;
        }

        if (!file_exists(get_custom_file_base() . '/uploads/filedump/')) {
            return array();
        }

        $filters = explode(',', $_filters);

        require_code('files2');

        $content = new Tempcode();
        $files = get_directory_contents(get_custom_file_base() . '/uploads/filedump/');
        $_rows = $GLOBALS['SITE_DB']->query_select('filedump', null, null, '', null, null, true);
        if (is_null($_rows)) {
            return null;
        }
        $rows = array();
        foreach ($_rows as $row) {
            $rows[$row['path']] = $row;
        }
        foreach ($files as $i => $file) {
            if ($i == $max) {
                break;
            }

            if ($filters != array('*')) {
                $ok = false;
                foreach ($filters as $filter) {
                    if (substr($file, 0, strlen($filter)) == $filter) {
                        $ok = true;
                    }
                }
                if (!$ok) {
                    continue;
                }
            }

            $id = $file;

            $mtime = filemtime(get_custom_file_base() . '/uploads/filedump/' . $file);
            if ($mtime < $cutoff) {
                continue;
            }
            $news_date = date($date_string, filectime(get_custom_file_base() . '/uploads/filedump/' . $file));
            $edit_date = date($date_string, $mtime);
            if ($news_date == $edit_date) {
                $edit_date = '';
            }

            $summary = '';
            $news = '';
            $author = '';
            if (array_key_exists($file, $rows)) {
                $summary = get_translated_text($rows[$file]['description']);
                $author = $GLOBALS['FORUM_DRIVER']->get_username($rows['the_member']);
            }

            $bits = explode('/', $file, 2);
            $news_title = xmlentities(escape_html($bits[0]));
            $category = array_key_exists(1, $bits) ? $bits[1] : '';
            $category_raw = $category;

            $view_url = get_custom_base_url() . '/uploads/filedump/' . $file;

            $if_comments = new Tempcode();

            $content->attach(do_template($prefix . 'ENTRY', array('VIEW_URL' => $view_url, 'SUMMARY' => $summary, 'EDIT_DATE' => $edit_date, 'IF_COMMENTS' => $if_comments, 'TITLE' => $news_title, 'CATEGORY_RAW' => $category_raw, 'CATEGORY' => $category, 'AUTHOR' => $author, 'ID' => $id, 'NEWS' => $news, 'DATE' => $news_date), null, false, null, '.xml', 'xml'));
        }

        require_lang('filedump');
        return array($content, do_lang('FILEDUMP'));
    }
}
