<?php /*

 Composr
 Copyright (c) ocProducts, 2004-2015

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
 * Revisions via database.
 */
class RevisionEngineDatabase
{
    protected $db;
    protected $is_log_mod;

    /**
     * Constructor.
     *
     * @param  boolean $is_log_mod Whether the logs are done via the forum moderator's log.
     * @param  ?object $db Database connection to use (null: work out using norms for $is_log_mod value).
     */
    public function __construct($is_log_mod = false, $db = null)
    {
        $this->is_log_mod = $is_log_mod;

        if ($db === null) {
            $this->db = $is_log_mod ? $GLOBALS['FORUM_DB'] : $GLOBALS['SITE_DB'];
        } else {
            $this->db = $db;
        }
    }

    /**
     * Find whether revisions are enabled for the current user.
     *
     * @param  boolean $check_privilege Whether to check privileges.
     * @return boolean Whether revisions are enabled.
     */
    public function enabled($check_privilege)
    {
        if (get_option('store_revisions') == '0') {
            return false;
        }

        if ($check_privilege) {
            if (!has_privilege(get_member(), 'view_revisions')) {
                return false;
            }
        }

        return true;
    }

    /**
     * Add a revision.
     *
     * @param  string $resource_type Resource type.
     * @param  string $resource_id Resource ID.
     * @param  string $category_id Category ID (e.g. a page or a topic). May be the same as $resource_id if the revision is for the category itself.
     * @param  string $original_title Title before revision (of the resource being edited, not the category) (blank: very common, no title).
     * @param  string $original_text Text before revision.
     * @param  MEMBER $original_content_owner Owner of the content (gathered so if deleted we can still see some meta context for this resource).
     * @param  TIME $original_content_timestamp Original timestamp of the content (gathered so if deleted we can still see some meta context for this resource).
     * @param  ?AUTO_LINK $log_id Log ID (null: no ID, meaning actually we cannot save a revision at all).
     */
    public function add_revision($resource_type, $resource_id, $category_id, $original_title, $original_text, $original_content_owner, $original_content_timestamp, $log_id)
    {
        if (!$this->enabled(false)) {
            return;
        }

        if (is_null($log_id)) {
            return;
        }

        require_code('resource_fs');

        $test = get_resourcefs_record($resource_type, $resource_id);
        if (is_null($test)) {
            return; // It's gone already, somehow
        }
        list($original_data_resource_fs_record, $original_data_resource_fs_path) = $test;

        $this->db->query_insert('revisions', array(
            'r_resource_type' => $resource_type,
            'r_resource_id' => $resource_id,
            'r_category_id' => $category_id,
            'r_original_title' => $original_title,
            'r_original_text' => $original_text,
            'r_original_content_owner' => $original_content_owner,
            'r_original_content_timestamp' => $original_content_timestamp,
            'r_original_resource_fs_path' => $original_data_resource_fs_record,
            'r_original_resource_fs_record' => $original_data_resource_fs_path,
            'r_actionlog_id' => $this->is_log_mod ? null : $log_id,
            'r_moderatorlog_id' => $this->is_log_mod ? $log_id : null,
        ));
    }

    /**
     * Retrieve revisions of something.
     *
     * @param  ?array $resource_types Allowed resource types (null: no filter).
     * @param  ?string $resource_id Resource ID (null: no filter).
     * @param  ?string $category_id Category ID (null: no filter).
     * @param  ?MEMBER $member_id Member ID (null: no filter).
     * @param  ?AUTO_LINK $revision_id The ID for a particular revision to retrieve (null: no filter).
     * @param  ?integer $max Maximum to return (null: no limit).
     * @param  integer $start Start offset.
     * @param  boolean $limited_data Whether to only collect IDs and other simple low-bandwidth data.
     * @return array List of revision maps.
     */
    public function find_revisions($resource_types = null, $resource_id = null, $category_id = null, $member_id = null, $revision_id = null, $max = 100, $start = 0, $limited_data = false)
    {
        if (!$this->enabled(true)) {
            return array();
        }

        if ((count($resource_types) == 0) && (is_null($revision_id))) {
            return array();
        }

        $extra_where = '1=1';

        if (!is_null($resource_id)) {
            $extra_where .= ' AND ';
            $extra_where .= db_string_equal_to('r_resource_id', $resource_id);
        }

        if (!is_null($category_id)) {
            $extra_where .= ' AND ';
            $extra_where .= db_string_equal_to('r_category_id', $category_id);
        }

        if (!is_null($revision_id)) {
            $extra_where .= ' AND ';
            $extra_where .= 'id=' . strval($revision_id);
        }

        if (!is_null($resource_types)) {
            $or_list = '';
            foreach ($resource_types as $resource_type) {
                if ($or_list != '') {
                    $or_list .= ' OR ';
                }
                $or_list .= db_string_equal_to('r_resource_type', $resource_type);
            }
            if ($or_list != '') {
                $extra_where .= ' AND ';
                $extra_where .= '(' . $or_list . ')';
            }
        }

        $combined_query = '';

        if ($this->is_log_mod || !is_on_multi_site_network()) {
            $where = $extra_where;

            if (!is_null($member_id)) {
                $where .= ' AND ';
                $where .= 'l_by=' . strval($member_id);
            }

            $select = 'r.id,l_the_type AS log_action,l_param_a AS log_param_a,l_param_b AS log_param_b,l_by AS log_member_id,\'\' AS log_ip,l_date_and_time AS log_time,l_reason AS log_reason';
            if (!$limited_data) {
                $select .= ',r.*';
            }
            $table = $this->db->get_table_prefix() . 'revisions r JOIN ' . $this->db->get_table_prefix() . 'f_moderator_logs l ON r.r_moderatorlog_id=l.id';
            $query = 'SELECT ' . $select . ' FROM ' . $table . ' WHERE ' . $where;

            if ($combined_query != '') {
                $combined_query .= ' UNION ';
            }
            $combined_query .= $query;
        }

        if (!$this->is_log_mod || !is_on_multi_site_network()) {
            $where = $extra_where;

            if (!is_null($member_id)) {
                $where .= ' AND ';
                $where .= 'member_id=' . strval($member_id);
            }

            $select = 'r.id,the_type AS log_action,param_a AS log_param_a,param_b AS log_param_b,member_id AS log_member_id,ip AS log_ip,date_and_time AS log_time,\'\' AS log_reason';
            if (!$limited_data) {
                $select .= ',r.*';
            }
            $table = $this->db->get_table_prefix() . 'revisions r JOIN ' . $this->db->get_table_prefix() . 'actionlogs l ON r.r_actionlog_id=l.id';
            $query = 'SELECT ' . $select . ' FROM ' . $table . ' WHERE ' . $where;

            if ($combined_query != '') {
                $combined_query .= ' UNION ';
            }
            $combined_query .= $query;
        }

        $combined_query .= ' ORDER BY log_time DESC';
        return $this->db->query($combined_query, $max, $start, false, true);
    }

    /**
     * Find if there are revisions of something.
     *
     * @param  array $resource_types Allowed resource types.
     * @param  ?string $resource_id Resource ID (null: no filter).
     * @param  ?string $category_id Category ID (null: no filter).
     * @param  ?MEMBER $member_id Member ID (null: no filter).
     * @return boolean Whether there are revisions.
     */
    public function has_revisions($resource_types, $resource_id = null, $category_id = null, $member_id = null)
    {
        if (!$this->enabled(true)) {
            return false;
        }

        return count($this->find_revisions($resource_types, $resource_id, $category_id, $member_id, null, 1, 0, true)) > 0;
    }

    /**
     * Find number of revisions of something.
     *
     * @param  array $resource_types Allowed resource types.
     * @param  ?string $resource_id Resource ID (null: no filter).
     * @param  ?string $category_id Category ID (null: no filter).
     * @param  ?MEMBER $member_id Member ID (null: no filter).
     * @return integer Total revisions.
     */
    public function total_revisions($resource_types, $resource_id = null, $category_id = null, $member_id = null)
    {
        return count($this->find_revisions($resource_types, $resource_id, $category_id, $member_id, null, null, 0, true));
    }

    /**
     * Retrieve revisions for a particular action log entry.
     *
     * @param  AUTO_LINK $log_id The action log entry's ID.
     * @return ?array A revision map (null: not found).
     */
    public function find_revision_for_log($log_id)
    {
        if (!$this->enabled(true)) {
            return null;
        }

        $map = array();
        if (!is_null($this->is_log_mod)) {
            $map['r_moderatorlog_id'] = $log_id;
        } else {
            $map['r_actionlog_id'] = $log_id;
        }

        $revision_id = $this->db->query_select_value_if_there('revisions', 'id', $map);
        if (is_null($revision_id)) {
            return null;
        }

        $logs = $this->find_revisions(null, null, null, null, $revision_id);
        if (!array_key_exists(0, $logs)) {
            return null;
        }
        return $logs[0];
    }

    /**
     * Find most recent revision in a category.
     *
     * @param  string $resource_type Resource type.
     * @param  string $category_id Category ID.
     * @return TIME Last revision (0 if no revisions ever).
     */
    public function find_most_recent_category_change($resource_type, $category_id)
    {
        $join_table = ($this->is_log_mod) ? 'f_moderator_logs' : 'actionlogs';
        $join_field = ($this->is_log_mod) ? 'r_moderatorlog_id' : 'r_actionlog_id';
        $time_field = ($this->is_log_mod) ? 'l_date_and_time' : 'date_and_time';
        $test = $this->db->query_select_value_if_there('revisions r JOIN ' . $this->db->get_table_prefix() . $join_table . ' l ON l.id=r.' . $join_field, 'MAX(' . $time_field . ')', array('r_category_id' => $category_id));
        if (is_null($test)) {
            $test = 0;
        }
        return $test;
    }

    /**
     * Move some revisions to a different category.
     * Typically this is when we are moving posts and we want the revisions to show up for the new topic they are in.
     *
     * @param  string $resource_type Resource type.
     * @param  string $resource_id Resource ID.
     * @param  string $new_category_id Category ID.
     */
    public function recategorise_old_revisions($resource_type, $resource_id, $new_category_id)
    {
        $GLOBALS['SITE_DB']->query_update('revisions', array('r_category_id' => $new_category_id), array('r_resource_type' => $resource_type, 'r_resource_id' => $resource_id));
    }

    /**
     * Show a revisions browsing UI for particular resource types.
     * Intended as a simple front-end browsing UI. Full details are in action-log, and restoration details are via ui_revision_undoer.
     * Does not check permissions, assumes only low-privilege data is revealed.
     * More details are shown in the actionlog, which is linked from here.
     *
     * @param  ?Tempcode $title Screen title (null: default).
     * @param  array $_fields_titles List of field titles (i.e. columns).
     * @param  ?array $resource_types List of resource types (null: no filter).
     * @param  mixed $row_renderer Callback for rendering out rows.
     * @param  ?string $resource_id Resource ID (null: no filter).
     * @param  ?string $category_id Category ID (null: no filter).
     * @param  ?MEMBER $member_id Member ID (null: no filter).
     * @param  ?string $category_permission_type Category permission type (null: no checks).
     * @param  boolean $include_filter_form Include a form for filtering revisions.
     * @return Tempcode Revision UI.
     */
    public function ui_browse_revisions($title, $_fields_titles, $resource_types, $row_renderer, $resource_id = null, $category_id = null, $member_id = null, $category_permission_type = null, $include_filter_form = false)
    {
        if (!$this->enabled(false)) {
            return new Tempcode();
        }

        require_lang('actionlog');

        if (is_null($title)) {
            $title = get_screen_title('REVISIONS');
        }

        $start = get_param_integer('revisions_start', 0);
        $max = get_param_integer('revisions_max', 25);

        $sortables = array('log_time' => do_lang_tempcode('DATE'));
        $test = explode(' ', get_param_string('revisions_sort', 'log_time DESC'), 2);
        if (count($test) == 1) {
            $test[1] = 'DESC';
        }
        list($sortable, $sort_order) = $test;
        if (((strtoupper($sort_order) != 'ASC') && (strtoupper($sort_order) != 'DESC')) || (!array_key_exists($sortable, $sortables))) {
            log_hack_attack_and_exit('ORDERBY_HACK');
        }

        $max_rows = $this->total_revisions($resource_types, $resource_id, $category_id, $member_id);
        $revisions = $this->find_revisions($resource_types, $resource_id, $category_id, $member_id, null, $max, $start);

        require_code('templates_results_table');

        $field_rows = new Tempcode();
        foreach ($revisions as $revision) {
            if ((!is_null($category_permission_type)) && (!has_category_access(get_member(), $category_permission_type, $revision['r_category_id']))) {
                continue;
            }

            $field_row = call_user_func($row_renderer, $revision);
            if (!is_null($field_row)) {
                $field_rows->attach($field_row);
            }
        }
        if ($field_rows->is_empty()) {
            return inform_screen($title, do_lang_tempcode('NO_ENTRIES'));
        }

        $fields_titles = results_field_title($_fields_titles, $sortables, 'revisions_sort', $sortable . ' ' . $sort_order);
        $results = results_table(
            do_lang_tempcode('REVISIONS'),
            $start,
            'revisions_start',
            $max,
            'revisions_max',
            $max_rows,
            $fields_titles,
            $field_rows,
            $sortables,
            $sortable,
            $sort_order,
            'revisions_sort'
        );

        $tpl = do_template('REVISIONS_SCREEN', array(
            '_GUID' => '0dea1ed9d31a818cba60f56fc1c8f68f',
            'TITLE' => $title,
            'RESULTS' => $results,
            'INCLUDE_FILTER_FORM' => $include_filter_form,
            'RESOURCE_TYPES' => array_keys(find_all_hooks('systems', 'content_meta_aware') + find_all_hooks('systems', 'resource_meta_aware')),
        ));

        require_code('templates_internalise_screen');
        return internalise_own_screen($tpl);
    }

    /**
     * Browse revisions to undo one.
     * More details are shown in the actionlog, which is linked from here.
     *
     * @param  string $resource_type Resource type.
     * @param  string $resource_id Resource ID.
     * @param  string $text Current resource text (may be altered by reference).
     * @return Tempcode UI.
     */
    public function ui_revision_undoer($resource_type, $resource_id, &$text)
    {
        if (!$this->enabled(true)) {
            return new Tempcode();
        }

        require_lang('actionlog');

        // Revisions
        $undo_revision = get_param_integer('undo_revision', null);
        if ($undo_revision === null) {
            require_code('files');
            require_code('diff');
            require_code('templates_results_table');

            $start = get_param_integer('revisions_start', 0);
            $max = get_param_integer('revisions_max', 25);

            $sortables = array('log_time' => do_lang_tempcode('DATE'));
            $test = explode(' ', get_param_string('revisions_sort', 'log_time DESC'), 2);
            if (count($test) == 1) {
                $test[1] = 'DESC';
            }
            list($sortable, $sort_order) = $test;
            if (((strtoupper($sort_order) != 'ASC') && (strtoupper($sort_order) != 'DESC')) || (!array_key_exists($sortable, $sortables))) {
                log_hack_attack_and_exit('ORDERBY_HACK');
            }

            $max_rows = $this->total_revisions(array($resource_type), $resource_id);
            if (!has_js()) {
                $max = $max_rows; // No AJAX pagination if no JS
            }
            $revisions = $this->find_revisions(array($resource_type), $resource_id, null, null, null, $max, $start);

            $do_actionlog = has_actual_page_access(get_member(), 'admin_actionlog');

            $_fields_titles = array(
                do_lang_tempcode('DATE_TIME'),
                do_lang_tempcode('MEMBER'),
                do_lang_tempcode('SIZE_CHANGE'),
                do_lang_tempcode('CHANGE_MICRO'),
                do_lang_tempcode('UNDO'),
            );
            if ($do_actionlog) {
                $_fields_titles[] = do_lang_tempcode('LOG');
            }

            $more_recent_text = $text;
            $field_rows = new Tempcode();
            foreach ($revisions as $revision) {
                $date = get_timezoned_date($revision['log_time']);

                $size_change = strlen($more_recent_text) - strlen($revision['r_original_text']);

                $member_link = $GLOBALS['FORUM_DRIVER']->member_profile_hyperlink($revision['log_member_id']);

                if (function_exists('diff_simple_2')) {
                    $rendered_diff = diff_simple_2($revision['r_original_text'], $more_recent_text);
                    $diff_icon = do_template('REVISIONS_DIFF_ICON', array(
                        'RENDERED_DIFF' => $rendered_diff,
                    ));
                } else {
                    $diff_icon = do_lang_tempcode('NA_EM');
                }

                $undo_url = get_self_url(false, false, array('undo_revision' => $revision['id']));
                $undo_link = hyperlink($undo_url, do_lang_tempcode('UNDO'), false, false, $date);

                if (is_null($revision['r_moderatorlog_id'])) {
                    $actionlog_url = build_url(array('page' => 'admin_actionlog', 'type' => 'view', 'id' => $revision['r_actionlog_id']), get_module_zone('admin_actionlog'));
                    $actionlog_link = hyperlink($actionlog_url, do_lang_tempcode('LOG'), false, false, strval($revision['r_actionlog_id']));
                } else {
                    $actionlog_url = build_url(array('page' => 'admin_actionlog', 'type' => 'view', 'id' => $revision['r_moderatorlog_id'], 'mode' => 'cns'), get_module_zone('admin_actionlog'));
                    $actionlog_link = hyperlink($actionlog_url, do_lang_tempcode('LOG'), false, false, strval($revision['r_moderatorlog_id']));
                }

                $_revision = array(
                    escape_html($date),
                    escape_html($size_change),
                    $member_link,
                    $diff_icon,
                    $undo_link,
                );
                if ($do_actionlog) {
                    $_revision[] = $actionlog_link;
                }
                $field_rows->attach(results_entry($_revision, false));

                $more_recent_text = $revision['r_original_text']; // For next iteration
            }

            $fields_titles = results_field_title($_fields_titles, $sortables, 'revisions_sort', $sortable . ' ' . $sort_order);
            $results = results_table(
                do_lang_tempcode('REVISIONS'),
                $start,
                'revisions_start',
                $max,
                'revisions_max',
                $max_rows,
                $fields_titles,
                $field_rows,
                $sortables,
                $sortable,
                $sort_order,
                'revisions_sort'
            );

            $revisions = do_template('REVISIONS_WRAP', array(
                '_GUID' => '1fc38d9d7ec57af110759352446e533d',
                'RESULTS' => $results,
            ));

        } else {
            $_text = $GLOBALS['SITE_DB']->query_select_value_if_there('revisions', 'r_original_text', array('id' => $undo_revision));
            if (!is_null($_text)) {
                $text = $_text;

                $revisions = do_template('REVISION_UNDO');
            } else {
                return new Tempcode();
            }
        }

        return $revisions;
    }
}
