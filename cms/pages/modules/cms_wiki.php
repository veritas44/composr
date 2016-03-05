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
 * @package    wiki
 */

/*EXTRA FUNCTIONS: diff_simple_2*/

/**
 * Module page class.
 */
class Module_cms_wiki
{
    /**
     * Find details of the module.
     *
     * @return ?array Map of module info (null: module is disabled).
     */
    public function info()
    {
        $info = array();
        $info['author'] = 'Chris Graham';
        $info['organisation'] = 'ocProducts';
        $info['hacked_by'] = null;
        $info['hack_version'] = null;
        $info['version'] = 4;
        $info['locked'] = false;
        $info['update_require_upgrade'] = true;
        return $info;
    }

    /**
     * Find entry-points available within this module.
     *
     * @param  boolean $check_perms Whether to check permissions.
     * @param  ?MEMBER $member_id The member to check permissions as (null: current user).
     * @param  boolean $support_crosslinks Whether to allow cross links to other modules (identifiable via a full-page-link rather than a screen-name).
     * @param  boolean $be_deferential Whether to avoid any entry-point (or even return null to disable the page in the Sitemap) if we know another module, or page_group, is going to link to that entry-point. Note that "!" and "browse" entry points are automatically merged with container page nodes (likely called by page-groupings) as appropriate.
     * @return ?array A map of entry points (screen-name=>language-code/string or screen-name=>[language-code/string, icon-theme-image]) (null: disabled).
     */
    public function get_entry_points($check_perms = true, $member_id = null, $support_crosslinks = true, $be_deferential = false)
    {
        $ret = array(
            'browse' => array('MANAGE_WIKI', 'menu/rich_content/wiki'),
            'add_page' => array('WIKI_ADD_PAGE', 'menu/rich_content/wiki'),
        );

        if ($support_crosslinks) {
            require_code('fields');
            $ret += manage_custom_fields_entry_points('wiki_post') + manage_custom_fields_entry_points('wiki_page');
        }

        return $ret;
    }

    /**
     * Find privileges defined as overridable by this module.
     *
     * @return array A map of privileges that are overridable; privilege to 0 or 1. 0 means "not category overridable". 1 means "category overridable".
     */
    public function get_privilege_overrides()
    {
        require_lang('wiki');
        return array('edit_cat_lowrange_content' => array(1, 'WIKI_EDIT_PAGE'), 'delete_cat_lowrange_content' => array(1, 'WIKI_DELETE_PAGE'), 'submit_lowrange_content' => array(1, 'WIKI_MAKE_POST'), 'bypass_validation_lowrange_content' => array(1, 'BYPASS_WIKI_VALIDATION'), 'edit_own_lowrange_content' => array(1, 'WIKI_EDIT_OWN_POST'), 'edit_lowrange_content' => array(1, 'WIKI_EDIT_POST'), 'delete_own_lowrange_content' => array(1, 'WIKI_DELETE_OWN_POST'), 'delete_lowrange_content' => array(1, 'WIKI_DELETE_POST'), 'wiki_manage_tree' => 1);
    }

    public $title;
    public $id;
    public $chain;
    public $page;
    public $page_title;

    /**
     * Module pre-run function. Allows us to know metadata for <head> before we start streaming output.
     *
     * @return ?Tempcode Tempcode indicating some kind of exceptional output (null: none).
     */
    public function pre_run()
    {
        $type = get_param_string('type', 'browse');

        require_lang('wiki');
        require_code('wiki');

        set_helper_panel_tutorial('tut_wiki');

        if ($type == 'choose_page_to_edit') {
            set_helper_panel_text(comcode_lang_string('DOC_WIKI'));

            breadcrumb_set_self(do_lang_tempcode('PAGE'));

            $this->title = get_screen_title('WIKI_EDIT_PAGE');
        }

        if ($type == 'add_page') {
            $this->title = get_screen_title('WIKI_ADD_PAGE');
        }

        if ($type == '_add_page') {
            $this->title = get_screen_title('WIKI_ADD_PAGE');
        }

        if ($type == 'edit_page') {
            $breadcrumbs = wiki_breadcrumbs(get_param_string('id', false, true), null, true, true);
            breadcrumb_set_parents($breadcrumbs);
            breadcrumb_set_self(do_lang_tempcode('WIKI_EDIT_PAGE'));

            $this->title = get_screen_title('WIKI_EDIT_PAGE');
        }

        if ($type == '_edit_page') {
            $breadcrumbs = wiki_breadcrumbs(get_param_string('id', false, true), null, true, true);
            breadcrumb_set_parents(array_merge($breadcrumbs, array(array('_SELF:_SELF:edit_page', do_lang_tempcode('PAGE')))));

            if (post_param_integer('delete', 0) == 1) {
                $this->title = get_screen_title('WIKI_DELETE_PAGE');
            } else {
                $this->title = get_screen_title('WIKI_EDIT_PAGE');
            }
        }

        if ($type == 'edit_tree') {
            list($id, $chain) = get_param_wiki_chain('id');
            $breadcrumbs = wiki_breadcrumbs($chain, null, true, true);
            breadcrumb_set_parents($breadcrumbs);
            breadcrumb_set_self(do_lang_tempcode('WIKI_EDIT_TREE'));

            $pages = $GLOBALS['SITE_DB']->query_select('wiki_pages', array('*'), array('id' => $id), '', 1);
            if (!array_key_exists(0, $pages)) {
                warn_exit(do_lang_tempcode('MISSING_RESOURCE', 'wiki_page'));
            }
            $page = $pages[0];

            $page_title = get_translated_text($page['title']);
            $this->title = get_screen_title('_WIKI_EDIT_TREE', true, array(escape_html($page_title)));

            $this->id = $id;
            $this->chain = $chain;
            $this->page = $page;
            $this->page_title = $page_title;
        }

        return null;
    }

    /**
     * Execute the module.
     *
     * @return Tempcode The result of execution.
     */
    public function run()
    {
        $type = get_param_string('type', 'browse');

        require_css('wiki');

        // Decide what to do
        if ($type == 'browse') {
            return $this->browse();
        }
        if ($type == 'choose_page_to_edit') {
            return $this->choose_page_to_edit();
        }
        if ($type == 'add_page') {
            return $this->add_page();
        }
        if ($type == '_add_page') {
            return $this->_add_page();
        }
        if ($type == 'edit_page') {
            return $this->edit_page();
        }
        if ($type == '_edit_page') {
            return $this->_edit_page();
        }
        if ($type == 'edit_tree') {
            return $this->edit_tree();
        }
        if ($type == '_edit_tree') {
            return $this->_edit_tree();
        }

        return new Tempcode();
    }

    /**
     * The do-next manager for before content management.
     *
     * @return Tempcode The UI
     */
    public function browse()
    {
        require_code('templates_donext');
        require_code('fields');
        return do_next_manager(get_screen_title('MANAGE_WIKI'), comcode_lang_string('DOC_WIKI'),
            array_merge(array(
                array('menu/_generic_admin/add_one', array('_SELF', array('type' => 'add_page'), '_SELF'), do_lang('WIKI_ADD_PAGE')),
                array('menu/_generic_admin/edit_one', array('_SELF', array('type' => 'choose_page_to_edit'), '_SELF'), do_lang('WIKI_EDIT_PAGE')),
            ), manage_custom_fields_donext_link('wiki_post'), manage_custom_fields_donext_link('wiki_page')),
            do_lang('MANAGE_WIKI')
        );
    }

    /**
     * Get the fields for adding/editing a Wiki+ page.
     *
     * @param  ?AUTO_LINK $id The page ID (null: new)
     * @param  SHORT_TEXT $title The page title
     * @param  LONG_TEXT $notes Hidden notes pertaining to the page
     * @param  BINARY $hide_posts Whether to hide the posts on the page by default
     * @param  AUTO_LINK $page_id The ID of the page (-1 implies we're adding)
     * @return array The fields, the extra fields, the hidden fields.
     */
    public function get_page_fields($id = null, $title = '', $notes = '', $hide_posts = 0, $page_id = -1)
    {
        $fields = new Tempcode();
        $fields2 = new Tempcode();
        $hidden = new Tempcode();

        require_code('form_templates');
        $fields->attach(form_input_line(do_lang_tempcode('SCREEN_TITLE'), do_lang_tempcode('SCREEN_TITLE_DESC'), 'title', $title, true));
        if (get_option('wiki_enable_content_posts') == '1') {
            $fields2->attach(form_input_tick(do_lang_tempcode('HIDE_POSTS'), do_lang_tempcode('DESCRIPTION_HIDE_POSTS'), 'hide_posts', $hide_posts == 1));
        }

        require_lang('notifications');
        if (addon_installed('actionlog')) {
            require_code('revisions_engine_database');
            $revision_engine = new RevisionEngineDatabase(false);
            $notify = ($page_id == -1) || ($revision_engine->find_most_recent_category_change('wiki_page', strval($page_id)) < time() - 60 * 10);
        } else {
            $notify = true;
        }
        $radios = form_input_radio_entry('send_notification', '0', !$notify, do_lang_tempcode('NO'));
        $radios->attach(form_input_radio_entry('send_notification', '1', $notify, do_lang_tempcode('YES')));
        $fields2->attach(form_input_radio(do_lang_tempcode('SEND_NOTIFICATION'), do_lang_tempcode('DESCRIPTION_SEND_NOTIFICATION'), 'send_notification', $radios));

        require_code('fields');

        if ((get_option('enable_staff_notes') == '1') || (has_tied_catalogue('wiki_page'))) {
            $fields2->attach(do_template('FORM_SCREEN_FIELD_SPACER', array('_GUID' => '5ae885a9f92415498340c41edfb47501', 'SECTION_HIDDEN' => $notes == '', 'TITLE' => do_lang_tempcode('ADVANCED'))));

            if (get_option('enable_staff_notes') == '1') {
                $fields2->attach(form_input_text(do_lang_tempcode('NOTES'), do_lang_tempcode('DESCRIPTION_NOTES'), 'notes', $notes, false));
            }

            if (has_tied_catalogue('wiki_page')) {
                append_form_custom_fields('wiki_page', ($page_id == -1) ? null : strval($page_id), $fields, $hidden);
            }
        }

        require_code('content2');
        $fields2->attach(metadata_get_fields('wiki_page', is_null($id) ? null : strval($id)));

        if (addon_installed('content_reviews')) {
            require_code('content_reviews2');
            $fields2->attach(content_review_get_fields('wiki_page', is_null($id) ? null : strval($id)));
        }

        require_code('permissions2');
        $fields2->attach(get_category_permissions_for_environment('wiki_page', strval($page_id), 'cms_wiki', null, ($page_id == -1)));

        return array($fields, $fields2, $hidden);
    }

    /**
     * The UI for adding a Wiki+ page.
     *
     * @return Tempcode The UI.
     */
    public function add_page()
    {
        check_submit_permission('cat_low');

        $_title = get_param_string('id', '', true);

        $add_url = build_url(array('page' => '_SELF', 'type' => '_add_page', 'redirect' => get_param_string('redirect', null)), '_SELF');

        require_code('form_templates');

        url_default_parameters__enable();

        list($fields, $fields2, $hidden) = $this->get_page_fields(null, $_title);

        require_code('seo2');
        $fields2->attach(seo_get_fields('wiki_page'));

        // Awards?
        if (addon_installed('awards')) {
            require_code('awards');
            $fields2->attach(get_award_fields('wiki_page'));
        }

        $posting_form = get_posting_form(do_lang('WIKI_ADD_PAGE'), 'menu___generic_admin__add_one_category', '', $add_url, $hidden, $fields, null, '', $fields2);

        url_default_parameters__disable();

        return do_template('POSTING_SCREEN', array('_GUID' => 'ea72f10d85ed06b618866f21da515180', 'POSTING_FORM' => $posting_form, 'HIDDEN' => '', 'TITLE' => $this->title, 'TEXT' => paragraph(do_lang_tempcode('WIKI_EDIT_PAGE_TEXT'))));
    }

    /**
     * The actualiser for adding a Wiki+ page.
     *
     * @return Tempcode The UI.
     */
    public function _add_page()
    {
        check_submit_permission('cat_low');

        require_code('content2');
        $metadata = actual_metadata_get_fields('wiki_page', null);

        $id = wiki_add_page(post_param_string('title'), post_param_string('post'), post_param_string('notes', ''), (get_option('wiki_enable_content_posts') == '1') ? post_param_integer('hide_posts', 0) : 1, $metadata['submitter'], $metadata['add_time'], $metadata['views'], post_param_string('meta_keywords', ''), post_param_string('meta_description', ''), null, false);

        set_url_moniker('wiki_page', strval($id));

        require_code('permissions2');
        set_category_permissions_from_environment('wiki_page', strval($id), 'cms_wiki');

        require_code('fields');
        if (has_tied_catalogue('wiki_page')) {
            save_form_custom_fields('wiki_page', strval($id));
        }

        if (addon_installed('awards')) {
            require_code('awards');
            handle_award_setting('wiki_page', strval($id));
        }

        if (addon_installed('content_reviews')) {
            require_code('content_reviews2');
            content_review_set('wiki_page', strval($id));
        }

        // Show it worked / Refresh
        $url = get_param_string('redirect', null);
        if (is_null($url)) {
            $_url = build_url(array('page' => 'wiki', 'type' => 'browse', 'id' => ($id == db_get_first_id()) ? null : $id), get_module_zone('wiki'));
            $url = $_url->evaluate();
        }
        return redirect_screen($this->title, $url, do_lang_tempcode('SUCCESS'));
    }

    /**
     * The UI for choosing a Wiki+ page to edit (not normally used).
     *
     * @return Tempcode The UI.
     */
    public function choose_page_to_edit()
    {
        $list = create_selection_list_wiki_page_tree();
        require_code('form_templates');
        $fields = form_input_list(do_lang_tempcode('_WIKI_PAGE'), '', 'id', $list, null, true);

        $post_url = build_url(array('page' => '_SELF', 'type' => 'edit_page'), '_SELF', null, false, true);
        $submit_name = do_lang_tempcode('PAGE');

        $search_url = build_url(array('page' => 'search', 'id' => 'wiki_pages'), get_module_zone('search'));
        $archive_url = build_url(array('page' => 'wiki'), get_module_zone('wiki'));
        $text = paragraph(do_lang_tempcode('CHOOSE_EDIT_LIST_EXTRA', escape_html($search_url->evaluate()), escape_html($archive_url->evaluate())));

        return do_template('FORM_SCREEN', array('_GUID' => 'e64757db1c77d752d813638f8a80581d', 'GET' => true, 'SKIP_WEBSTANDARDS' => true, 'TITLE' => $this->title, 'HIDDEN' => '', 'SUBMIT_ICON' => 'buttons__proceed', 'SUBMIT_NAME' => $submit_name, 'TEXT' => $text, 'FIELDS' => $fields, 'URL' => $post_url));
    }

    /**
     * The UI for editing a Wiki+ page.
     *
     * @return Tempcode The UI.
     */
    public function edit_page()
    {
        $__id = get_param_string('id', '', true);
        $_id = get_param_wiki_chain('id');
        $id = intval($_id[0]);

        check_edit_permission('cat_low', null, array('wiki_page', $id));

        if (!has_category_access(get_member(), 'wiki_page', strval($id))) {
            access_denied('CATEGORY_ACCESS');
        }

        $pages = $GLOBALS['SITE_DB']->query_select('wiki_pages', array('*'), array('id' => $id), '', 1);
        if (!array_key_exists(0, $pages)) {
            warn_exit(do_lang_tempcode('MISSING_RESOURCE', 'wiki_page'));
        }
        $page = $pages[0];

        $page_title = get_translated_text($page['title']);
        $description = get_translated_text($page['description']);
        $_description = get_translated_tempcode('wiki_pages', $page, 'description');

        $redir_url = get_param_string('redirect', null);
        if (is_null($redir_url)) {
            $_redir_url = build_url(array('page' => 'wiki', 'type' => 'browse', 'id' => get_param_string('id', false, true)), get_module_zone('wiki'));
            $redir_url = $_redir_url->evaluate();
        }
        $edit_url = build_url(array('page' => '_SELF', 'redirect' => $redir_url, 'type' => '_edit_page', 'id' => get_param_string('id', false, true)), '_SELF');

        list($fields, $fields2, $hidden) = $this->get_page_fields($id, $page_title, $page['notes'], $page['hide_posts'], $id);
        require_code('seo2');
        $fields2->attach(seo_get_fields('wiki_page', strval($id)));

        if (addon_installed('awards')) {
            // Awards?
            require_code('awards');
            $fields2->attach(get_award_fields('wiki_page', strval($id)));
        }

        if (has_delete_permission('cat_low', get_member(), null, null, array('wiki_page', $id)) && ($id != db_get_first_id())) {
            $fields2->attach(do_template('FORM_SCREEN_FIELD_SPACER', array('_GUID' => '189cb80853d73ea1f63d5b0463ef7a37', 'TITLE' => do_lang_tempcode('ACTIONS'))));
            $fields2->attach(form_input_tick(do_lang_tempcode('DELETE'), do_lang_tempcode('DESCRIPTION_DELETE'), 'delete', false));
        }

        if (addon_installed('actionlog')) {
            require_code('revisions_engine_database');
            $revision_engine = new RevisionEngineDatabase();
            $revision_loaded = mixed();
            $revisions = $revision_engine->ui_revision_undoer('wiki_page', strval($id), $description, $revision_loaded);
        } else {
            $revisions = new Tempcode();
        }

        $posting_form = get_posting_form(do_lang('SAVE'), 'menu___generic_admin__edit_this_category', $description, $edit_url, new Tempcode(), $fields, do_lang_tempcode('PAGE_TEXT'), '', $fields2, $_description, null, null, false);

        list($warning_details, $ping_url) = handle_conflict_resolution();

        return do_template('POSTING_SCREEN', array(
            '_GUID' => 'de53b8902ab1431e0d2d676f7d5471d3',
            'PING_URL' => $ping_url,
            'WARNING_DETAILS' => $warning_details,
            'REVISIONS' => $revisions,
            'POSTING_FORM' => $posting_form,
            'HIDDEN' => $hidden,
            'TITLE' => $this->title,
            'TEXT' => paragraph(do_lang_tempcode('WIKI_EDIT_PAGE_TEXT')),
        ));
    }

    /**
     * The actualiser for editing a Wiki+ page.
     *
     * @return Tempcode The UI.
     */
    public function _edit_page()
    {
        $_id = get_param_wiki_chain('id');
        $id = intval($_id[0]);

        if (!has_category_access(get_member(), 'wiki_page', strval($id))) {
            access_denied('CATEGORY_ACCESS');
        }

        if (post_param_integer('delete', 0) == 1) {
            check_delete_permission('cat_low', null, array('wiki_page', $id));

            wiki_delete_page($id);

            require_code('fields');
            if (has_tied_catalogue('wiki_page')) {
                delete_form_custom_fields('wiki_page', strval($id));
            }

            $_url = build_url(array('page' => '_SELF', 'type' => 'browse'), '_SELF');
            $url = $_url->evaluate();
        } else {
            check_edit_permission('cat_low', null, array('wiki_page', $id));

            require_code('content2');
            $metadata = actual_metadata_get_fields('wiki_page', strval($id));

            require_code('permissions2');
            set_category_permissions_from_environment('wiki_page', strval($id), 'cms_wiki');
            wiki_edit_page($id, post_param_string('title'), post_param_string('post'), post_param_string('notes', ''), (get_option('wiki_enable_content_posts') == '1') ? post_param_integer('hide_posts', 0) : 1, post_param_string('meta_keywords', ''), post_param_string('meta_description', ''), $metadata['submitter'], $metadata['add_time'], $metadata['views']);

            require_code('fields');
            if (has_tied_catalogue('wiki_page')) {
                save_form_custom_fields('wiki_page', strval($id));
            }

            if (addon_installed('content_reviews')) {
                require_code('content_reviews2');
                content_review_set('wiki_page', strval($id));
            }

            if (addon_installed('awards')) {
                require_code('awards');
                handle_award_setting('wiki_page', strval($id));
            }

            $url = get_param_string('redirect');
        }

        // Show it worked / Refresh
        return redirect_screen($this->title, $url, do_lang_tempcode('SUCCESS'));
    }

    /**
     * The UI for managing the Wiki+ children of a page.
     *
     * @return Tempcode The UI.
     */
    public function edit_tree()
    {
        if (get_option('wiki_enable_children') == '0') {
            warn_exit(do_lang_tempcode('INTERNAL_ERROR'));
        }

        $id = $this->id;
        $chain = $this->chain;
        $page = $this->page;
        $page_title = $this->page_title;

        check_privilege('wiki_manage_tree', array('wiki_page', $id));

        if (!has_category_access(get_member(), 'wiki_page', strval($id))) {
            access_denied('CATEGORY_ACCESS');
        }

        $children_entries = $GLOBALS['SITE_DB']->query_select('wiki_children', array('child_id', 'title'), array('parent_id' => $id), 'ORDER BY the_order');
        $children = '';
        foreach ($children_entries as $entry) {
            $child_id = $entry['child_id'];
            $child_title = $entry['title'];
            $children .= strval($child_id) . '=' . $child_title . "\n";
        }

        $redir_url = get_param_string('redirect', null);
        if (is_null($redir_url)) {
            $_redir_url = build_url(array('page' => 'wiki', 'type' => 'browse', 'id' => get_param_string('id', false, true)), get_module_zone('wiki'));
            $redir_url = $_redir_url->evaluate();
        }
        $post_url = build_url(array('page' => '_SELF', 'type' => '_edit_tree', 'id' => get_param_string('id', false, true), 'redirect' => $redir_url), '_SELF');

        $wiki_tree = create_selection_list_wiki_page_tree($id, null, '', true, false, true);

        require_code('form_templates');
        list($warning_details, $ping_url) = handle_conflict_resolution();

        require_javascript('tree_list');

        $fields = new Tempcode();
        $fields->attach(form_input_text(do_lang_tempcode('CHILD_PAGES'), new Tempcode(), 'children', $children, false, null, true));
        $form = do_template('FORM', array('_GUID' => 'b908438ccfc9be6166cf7c5c81d5de8b', 'FIELDS' => $fields, 'URL' => $post_url, 'HIDDEN' => '', 'TEXT' => '', 'SUBMIT_ICON' => 'buttons__save', 'SUBMIT_NAME' => do_lang_tempcode('SAVE'), 'SUPPORT_AUTOSAVE' => true));

        return do_template('WIKI_MANAGE_TREE_SCREEN', array('_GUID' => '83da3f20799b66b8846eafa4251a5d01', 'PAGE_TITLE' => $page_title, 'PING_URL' => $ping_url, 'WARNING_DETAILS' => $warning_details, 'TITLE' => $this->title, 'FORM' => $form, 'WIKI_TREE' => $wiki_tree));
    }

    /**
     * The actualiser for managing the Wiki+ children of a page.
     *
     * @return Tempcode The UI.
     */
    public function _edit_tree()
    {
        if (get_option('wiki_enable_children') == '0') {
            warn_exit(do_lang_tempcode('INTERNAL_ERROR'));
        }

        $_title = get_screen_title('WIKI_EDIT_TREE');

        $_id = get_param_wiki_chain('id');
        $id = $_id[0];

        if (!has_category_access(get_member(), 'wiki_page', strval($id))) {
            access_denied('CATEGORY_ACCESS');
        }

        $childlinks = post_param_string('children');

        $member = get_member();
        check_privilege('wiki_manage_tree', array('wiki_page', $id));

        $hide_posts = $GLOBALS['SITE_DB']->query_select_value('wiki_pages', 'hide_posts', array('id' => $id));
        $page_title = $GLOBALS['SITE_DB']->query_select_value('wiki_pages', 'title', array('id' => $id));
        if (get_option('wiki_enable_content_posts') == '0') {
            $hide_posts = 1;
        }

        if ((substr($childlinks, -1, 1) != "\n") && (strlen($childlinks) > 0)) {
            $childlinks .= "\n";
        }
        $no_children = substr_count($childlinks, "\n");
        if ($no_children > 300) {
            warn_exit(do_lang_tempcode('TOO_MANY_WIKI_CHILDREN'));
        }
        $start = 0;
        $GLOBALS['SITE_DB']->query_delete('wiki_children', array('parent_id' => $id));
        require_code('seo2');
        for ($i = 0; $i < $no_children; $i++) {
            $length = strpos($childlinks, "\n", $start) - $start;
            $new_link = trim(substr($childlinks, $start, $length));
            $start = $start + $length + 1;
            if ($new_link != '') {
                // Find ID and title
                $q_pos = strpos($new_link, '=');
                $child_id_on_start = (($q_pos !== false) && ($q_pos > 0) && (is_numeric(substr($new_link, 0, $q_pos))));

                if ($child_id_on_start) { // Existing
                    $title = substr($new_link, $q_pos + 1);
                    $child_id = intval(substr($new_link, 0, $q_pos));
                    if ($child_id == $id) {
                        continue;
                    }
                    $title_id = $GLOBALS['SITE_DB']->query_select_value_if_there('wiki_pages', 'title', array('id' => $child_id));
                    if (is_null($title_id)) {
                        attach_message(do_lang_tempcode('BROKEN_WIKI_CHILD_LINK', strval($child_id)), 'warn');
                        continue;
                    }
                    if ($title == '') {
                        $title = get_translated_text($title_id);
                    } else {
                        if (get_translated_text($title_id) != $title) {
                            require_code('urls2');
                            suggest_new_idmoniker_for('wiki', 'browse', strval($child_id), '', $title);
                            $GLOBALS['SITE_DB']->query_update('wiki_pages', lang_remap('title', $title_id, $title), array('id' => $child_id), '', 1);
                        }
                    }
                } else { // New
                    $title = $new_link;
                    $child_id = wiki_add_page($title, '', '', $hide_posts, null, null, 0, '', '', null, false);

                    require_code('permissions2');
                    set_global_category_access('wiki_page', $child_id);

                    require_code('notifications2');
                    copy_notifications_to_new_child('wiki', strval($id), strval($child_id));
                }

                $GLOBALS['SITE_DB']->query_delete('wiki_children', array('parent_id' => $id, 'child_id' => $child_id), '', 1); // Just in case it was repeated
                $GLOBALS['SITE_DB']->query_insert('wiki_children', array('parent_id' => $id, 'child_id' => $child_id, 'the_order' => $i, 'title' => $title));

                require_code('notifications2');
                copy_notifications_to_new_child('wiki', strval($id), strval($child_id));
            }
        }

        log_it('WIKI_EDIT_TREE', strval($id), $page_title);

        // Show it worked / Refresh
        $url = get_param_string('redirect');
        return redirect_screen($_title, $url, do_lang_tempcode('SUCCESS'));
    }
}
