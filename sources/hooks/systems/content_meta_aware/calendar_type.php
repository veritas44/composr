<?php /*

 Composr
 Copyright (c) ocProducts, 2004-2017

 See text/EN/licence.txt for full licencing information.


 NOTE TO PROGRAMMERS:
   Do not edit this file. If you need to make changes, save your changed file to the appropriate *_custom folder
   **** If you ignore this advice, then your website upgrades (e.g. for bug fixes) will likely kill your changes ****

*/

/**
 * @license    http://opensource.org/licenses/cpal_1.0 Common Public Attribution License
 * @copyright  ocProducts Ltd
 * @package    calendar
 */

/**
 * Hook class.
 */
class Hook_content_meta_aware_calendar_type
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
            'support_custom_fields' => false,

            'content_type_label' => 'calendar:EVENT_TYPE',
            'content_type_universal_label' => 'Calendar type',

            'db' => $GLOBALS['SITE_DB'],
            'table' => 'calendar_types',
            'id_field' => 'id',
            'id_field_numeric' => true,
            'parent_category_field' => null,
            'parent_category_meta_aware_type' => null,
            'is_category' => true,
            'is_entry' => false,
            'category_field' => 'id', // For category permissions
            'category_type' => 'calendar', // For category permissions
            'parent_spec__table_name' => null,
            'parent_spec__parent_name' => null,
            'parent_spec__field_name' => null,
            'category_is_string' => false,

            'title_field' => 't_title',
            'title_field_dereference' => true,
            'description_field' => null,
            'description_field_dereference' => true,
            'thumb_field' => 't_logo',
            'thumb_field_is_theme_image' => true,
            'alternate_icon_theme_image' => null,

            'view_page_link_pattern' => '_SEARCH:calendar:browse:_WILD',
            'edit_page_link_pattern' => '_SEARCH:cms_calendar:_edit_category:_WILD',
            'view_category_page_link_pattern' => '_SEARCH:calendar:browse:_WILD',
            'add_url' => (function_exists('has_submit_permission') && has_submit_permission('mid', get_member(), get_ip_address(), 'cms_calendar')) ? (get_module_zone('cms_calendar') . ':cms_calendar:add') : null,
            'archive_url' => (($zone !== null) ? $zone : get_module_zone('calendar')) . ':calendar',

            'support_url_monikers' => false,

            'views_field' => null,
            'order_field' => null,
            'submitter_field' => null,
            'author_field' => null,
            'add_time_field' => null,
            'edit_time_field' => null,
            'date_field' => null,
            'validated_field' => null,

            'seo_type_code' => 'calendar_type',

            'feedback_type_code' => null,

            'permissions_type_code' => 'calendar', // null if has no permissions

            'search_hook' => null,
            'rss_hook' => null,
            'attachment_hook' => null,
            'unvalidated_hook' => null,
            'notification_hook' => null,
            'sitemap_hook' => 'calendar_type',

            'addon_name' => 'calendar',

            'cms_page' => 'cms_calendar',
            'module' => 'calendar',

            'commandr_filesystem_hook' => 'calendar',
            'commandr_filesystem__is_folder' => true,

            'support_revisions' => false,

            'support_privacy' => false,

            'support_content_reviews' => true,

            'support_spam_heuristics' => null,

            'actionlog_regexp' => '\w+_CALENDAR_TYPE',
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
        require_code('calendar');

        return render_calendar_type_box($row, $zone, $give_context, $guid);
    }
}
