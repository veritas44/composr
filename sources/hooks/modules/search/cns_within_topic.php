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
 * @package    cns_forum
 */

/**
 * Hook class.
 */
class Hook_search_cns_within_topic extends FieldsSearchHook
{
    /**
     * Find details for this search hook.
     *
     * @param  boolean $check_permissions Whether to check permissions
     * @return ?array Map of search hook details (null: hook is disabled)
     */
    public function info($check_permissions = true)
    {
        if (get_forum_type() != 'cns') {
            return null;
        }

        if ($check_permissions) {
            if (!has_actual_page_access(get_member(), 'topicview')) {
                return null;
            }
        }

        if (get_param_string('search_under', '', INPUT_FILTER_GET_COMPLEX) == '') {
            return null;
        }

        require_lang('cns');

        $info = array();
        $info['lang'] = do_lang_tempcode('POSTS_WITHIN_TOPIC');
        $info['default'] = false;
        $info['special_on'] = array();
        $info['special_off'] = array();
        $info['category'] = 'p_topic_id';
        $info['integer_category'] = true;
        $info['advanced_only'] = true;
        $info['extra_sort_fields'] = $this->_get_extra_sort_fields('_topic');

        $info['permissions'] = array(
            array(
                'type' => 'zone',
                'zone_name' => get_module_zone('topicview'),
            ),
            array(
                'type' => 'page',
                'zone_name' => get_module_zone('topicview'),
                'page_name' => 'topicview',
            ),
        );

        return $info;
    }

    /**
     * Get a list of extra fields to ask for.
     *
     * @return ?array A list of maps specifying extra fields (null: no tree)
     */
    public function get_fields()
    {
        return $this->_get_fields('_topic');
    }

    /**
     * Get details for an ajax-tree-list of entries for the content covered by this search hook.
     *
     * @return array A pair: the hook, and the options
     */
    public function ajax_tree()
    {
        return array('choose_topic', array('compound_list' => false));
    }

    /**
     * Run function for search results.
     *
     * @param  string $content Search string
     * @param  boolean $only_search_meta Whether to only do a META (tags) search
     * @param  ID_TEXT $direction Order direction
     * @param  integer $max Start position in total results
     * @param  integer $start Maximum results to return in total
     * @param  boolean $only_titles Whether only to search titles (as opposed to both titles and content)
     * @param  string $content_where Where clause that selects the content according to the main search string (SQL query fragment) (blank: full-text search)
     * @param  SHORT_TEXT $author Username/Author to match for
     * @param  ?MEMBER $author_id Member-ID to match for (null: unknown)
     * @param  mixed $cutoff Cutoff date (TIME or a pair representing the range)
     * @param  string $sort The sort type (gets remapped to a field in this function)
     * @set    title add_date
     * @param  integer $limit_to Limit to this number of results
     * @param  string $boolean_operator What kind of boolean search to do
     * @set    or and
     * @param  string $where_clause Where constraints known by the main search code (SQL query fragment)
     * @param  string $search_under Comma-separated list of categories to search under
     * @param  boolean $boolean_search Whether it is a boolean search
     * @return array List of maps (template, orderer)
     */
    public function run($content, $only_search_meta, $direction, $max, $start, $only_titles, $content_where, $author, $author_id, $cutoff, $sort, $limit_to, $boolean_operator, $where_clause, $search_under, $boolean_search)
    {
        if (get_forum_type() != 'cns') {
            return array();
        }
        require_code('cns_forums');
        require_code('cns_posts');
        require_css('cns');

        $remapped_orderer = '';
        switch ($sort) {
            case 'title':
                $remapped_orderer = 'p_title';
                break;

            case 'add_date':
                $remapped_orderer = 'p_time';
                break;
        }

        require_lang('cns');

        // Calculate our where clause (search)
        $sq = build_search_submitter_clauses('p_poster', $author_id, $author);
        if ($sq === null) {
            return array();
        } else {
            $where_clause .= $sq;
        }
        $this->_handle_date_check($cutoff, 'p_time', $where_clause);

        if ((!has_privilege(get_member(), 'see_unvalidated')) && (addon_installed('unvalidated'))) {
            $where_clause .= ' AND ';
            $where_clause .= 'p_validated=1';
        }
        $where_clause .= ' AND ';
        $where_clause .= 't_forum_id=p_cache_forum_id AND t_forum_id IS NOT NULL AND p_intended_solely_for IS NULL';

        $table = 'f_posts r JOIN ' . $GLOBALS['FORUM_DB']->get_table_prefix() . 'f_topics s ON r.p_topic_id=s.id';
        $trans_fields = array('!' => '!', 'r.p_post' => 'LONG_TRANS__COMCODE');
        $nontrans_fields = array('r.p_title');
        $this->_get_search_parameterisation_advanced_for_content_type('_topic', $table, $where_clause, $trans_fields, $nontrans_fields);

        // Calculate and perform query
        $rows = get_search_rows(null, null, $content, $boolean_search, $boolean_operator, $only_search_meta, $direction, $max, $start, $only_titles, $table, $trans_fields, $where_clause, $content_where, $remapped_orderer, 'r.*,t_forum_id', $nontrans_fields, 'forums', 't_forum_id');

        $out = array();
        foreach ($rows as $i => $row) {
            $out[$i]['data'] = $row;
            unset($rows[$i]);
            if (($remapped_orderer != '') && (array_key_exists($remapped_orderer, $row))) {
                $out[$i]['orderer'] = $row[$remapped_orderer];
            } elseif (strpos($remapped_orderer, '_rating:') !== false) {
                $out[$i]['orderer'] = $row[$remapped_orderer];
            }
        }

        return $out;
    }

    /**
     * Run function for rendering a search result.
     *
     * @param  array $row The data row stored when we retrieved the result
     * @return Tempcode The output
     */
    public function render($row)
    {
        require_code('cns_posts2');
        return render_post_box($row, false, false);
    }
}
