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
 * @package    authors
 */

/**
 * Hook class.
 */
class Hook_content_meta_aware_author
{
    /**
     * Get content type details. Provides information to allow task reporting, randomisation, and add-screen linking, to function.
     *
     * @param  ?ID_TEXT $zone The zone to link through to (null: autodetect)
     * @return ?array Map of award content-type info (null: disabled)
     */
    public function info($zone = null)
    {
        return array(
            'support_custom_fields' => true,

            'content_type_label' => 'global:AUTHOR',
            'content_type_universal_label' => 'Author',

            'db' => $GLOBALS['SITE_DB'],
            'table' => 'authors',
            'id_field' => 'author',
            'id_field_numeric' => false,
            'parent_category_field' => null,
            'parent_category_meta_aware_type' => null,
            'is_category' => false,
            'is_entry' => true,
            'category_field' => null, // For category permissions
            'category_type' => null, // For category permissions
            'parent_spec__table_name' => null,
            'parent_spec__parent_name' => null,
            'parent_spec__field_name' => null,
            'category_is_string' => true,

            'title_field' => 'author',
            'title_field_dereference' => false,
            'description_field' => 'description',
            'description_field_dereference' => true,
            'thumb_field' => null,
            'thumb_field_is_theme_image' => false,
            'alternate_icon_theme_image' => 'icons/48x48/menu/rich_content/authors',

            'view_page_link_pattern' => '_SEARCH:authors:browse:_WILD',
            'edit_page_link_pattern' => '_SEARCH:cms_authors:_add:_WILD',
            'view_category_page_link_pattern' => null,
            'add_url' => (function_exists('has_submit_permission') && has_submit_permission('mid', get_member(), get_ip_address(), 'cms_authors')) ? (get_module_zone('cms_authors') . ':cms_authors:_add') : null,
            'archive_url' => (($zone !== null) ? $zone : get_module_zone('authors')) . ':authors',

            'support_url_monikers' => false,

            'views_field' => null,
            'order_field' => null,
            'submitter_field' => null,
            'author_field' => null,
            'add_time_field' => null,
            'edit_time_field' => null,
            'date_field' => null,
            'validated_field' => null,

            'seo_type_code' => null,

            'feedback_type_code' => null,

            'permissions_type_code' => null, // null if has no permissions

            'search_hook' => null,
            'rss_hook' => 'authors',
            'attachment_hook' => 'author',
            'unvalidated_hook' => null,
            'notification_hook' => null,
            'sitemap_hook' => 'author',

            'addon_name' => 'authors',

            'cms_page' => 'cms_authors',
            'module' => 'authors',

            'commandr_filesystem_hook' => 'authors',
            'commandr_filesystem__is_folder' => false,

            'support_revisions' => false,

            'support_privacy' => false,

            'support_content_reviews' => true,

            'support_spam_heuristics' => null,

            'actionlog_regexp' => '\w+_AUTHOR',
        );
    }

    /**
     * Run function for content hooks. Renders a content box for an award/randomisation.
     *
     * @param  array $row The database row for the content
     * @param  ID_TEXT $zone The zone to display in
     * @param  boolean $give_context Whether to include context (i.e. say WHAT this is, not just show the actual content)
     * @param  boolean $include_breadcrumbs Whether to include breadcrumbs (if there are any)
     * @param  ?ID_TEXT $root Virtual root to use (null: none)
     * @param  boolean $attach_to_url_filter Whether to copy through any filter parameters in the URL, under the basis that they are associated with what this box is browsing
     * @param  ID_TEXT $guid Overridden GUID to send to templates (blank: none)
     * @return Tempcode Results
     */
    public function run($row, $zone, $give_context = true, $include_breadcrumbs = true, $root = null, $attach_to_url_filter = false, $guid = '')
    {
        require_code('authors');

        return render_author_box($row, $zone, $give_context, $guid);
    }
}
