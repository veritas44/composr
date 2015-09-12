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
 * @package    catalogues
 */

/**
 * Hook class.
 */
class Hook_addon_registry_catalogues
{
    /**
     * Get a list of file permissions to set
     *
     * @return array File permissions to set
     */
    public function get_chmod_array()
    {
        return array();
    }

    /**
     * Get the version of Composr this addon is for
     *
     * @return float Version number
     */
    public function get_version()
    {
        return cms_version_number();
    }

    /**
     * Get the description of the addon
     *
     * @return string Description of the addon
     */
    public function get_description()
    {
        return 'Describe your own custom data record types (by choosing and configuring fields) and populate with records. Supports tree structures, and most standard Composr features (e.g. ratings).';
    }

    /**
     * Get a list of tutorials that apply to this addon
     *
     * @return array List of tutorials
     */
    public function get_applicable_tutorials()
    {
        return array(
            'tut_catalogues',
            'tut_information',
            'tut_fields',
        );
    }

    /**
     * Get a mapping of dependency types
     *
     * @return array File permissions to set
     */
    public function get_dependencies()
    {
        return array(
            'requires' => array(),
            'recommends' => array(),
            'conflicts_with' => array(),
        );
    }

    /**
     * Explicitly say which icon should be used
     *
     * @return URLPATH Icon
     */
    public function get_default_icon()
    {
        return 'themes/default/images/icons/48x48/menu/rich_content/catalogues/catalogues.png';
    }

    /**
     * Get a list of files that belong to this addon
     *
     * @return array List of files
     */
    public function get_file_list()
    {
        return array(
            'themes/default/images/icons/24x24/menu/rich_content/catalogues/catalogues.png',
            'themes/default/images/icons/48x48/menu/rich_content/catalogues/catalogues.png',
            'themes/default/images/icons/24x24/menu/cms/catalogues/add_one_catalogue.png',
            'themes/default/images/icons/24x24/menu/cms/catalogues/edit_one_catalogue.png',
            'themes/default/images/icons/48x48/menu/cms/catalogues/add_one_catalogue.png',
            'themes/default/images/icons/48x48/menu/cms/catalogues/edit_one_catalogue.png',
            'themes/default/images/icons/48x48/menu/cms/catalogues/edit_this_catalogue.png',
            'themes/default/images/icons/48x48/menu/cms/catalogues/index.html',
            'themes/default/images/icons/24x24/menu/cms/catalogues/edit_this_catalogue.png',
            'themes/default/images/icons/24x24/menu/cms/catalogues/index.html',
            'themes/default/images/icons/24x24/menu/rich_content/catalogues/classifieds.png',
            'themes/default/images/icons/24x24/menu/rich_content/catalogues/contacts.png',
            'themes/default/images/icons/24x24/menu/rich_content/catalogues/faqs.png',
            'themes/default/images/icons/24x24/menu/rich_content/catalogues/index.html',
            'themes/default/images/icons/24x24/menu/rich_content/catalogues/links.png',
            'themes/default/images/icons/24x24/menu/rich_content/catalogues/products.png',
            'themes/default/images/icons/24x24/menu/rich_content/catalogues/projects.png',
            'themes/default/images/icons/48x48/menu/rich_content/catalogues/classifieds.png',
            'themes/default/images/icons/48x48/menu/rich_content/catalogues/contacts.png',
            'themes/default/images/icons/48x48/menu/rich_content/catalogues/faqs.png',
            'themes/default/images/icons/48x48/menu/rich_content/catalogues/index.html',
            'themes/default/images/icons/48x48/menu/rich_content/catalogues/links.png',
            'themes/default/images/icons/48x48/menu/rich_content/catalogues/products.png',
            'themes/default/images/icons/48x48/menu/rich_content/catalogues/projects.png',
            'sources/hooks/systems/snippets/exists_catalogue.php',
            'sources/hooks/systems/module_permissions/catalogues_catalogue.php',
            'sources/hooks/systems/module_permissions/catalogues_category.php',
            'sources/hooks/systems/rss/catalogues.php',
            'sources/hooks/systems/page_groupings/catalogues.php',
            'sources/hooks/systems/trackback/catalogues.php',
            'sources/hooks/modules/search/catalogue_categories.php',
            'sources/hooks/modules/search/catalogue_entries.php',
            'sources/hooks/systems/ajax_tree/choose_catalogue_category.php',
            'sources/hooks/systems/ajax_tree/choose_catalogue_entry.php',
            'sources/hooks/systems/cron/catalogue_entry_timeouts.php',
            'sources/hooks/systems/cron/catalogue_view_reports.php',
            'sources/hooks/systems/meta/catalogue_category.php',
            'sources/hooks/systems/meta/catalogue_entry.php',
            'themes/default/javascript/catalogues.js',
            'sources/hooks/modules/admin_import_types/catalogues.php',
            'sources/hooks/systems/content_meta_aware/catalogue.php',
            'sources/hooks/systems/content_meta_aware/catalogue_category.php',
            'sources/hooks/systems/content_meta_aware/catalogue_entry.php',
            'sources/hooks/systems/commandr_fs/catalogues.php',
            'sources/hooks/systems/addon_registry/catalogues.php',
            'themes/default/templates/CATALOGUE_ADDING_SCREEN.tpl',
            'themes/default/templates/CATALOGUE_EDITING_SCREEN.tpl',
            'themes/default/templates/CATALOGUE_CATEGORIES_LIST_LINE.tpl',
            'themes/default/templates/CATALOGUE_DEFAULT_CATEGORY_EMBED.tpl',
            'themes/default/templates/CATALOGUE_DEFAULT_CATEGORY_SCREEN.tpl',
            'themes/default/templates/CATALOGUE_DEFAULT_FIELDMAP_ENTRY_WRAP.tpl',
            'themes/default/templates/CATALOGUE_DEFAULT_FIELDMAP_ENTRY_FIELD.tpl',
            'themes/default/templates/CATALOGUE_DEFAULT_GRID_ENTRY_WRAP.tpl',
            'themes/default/templates/CATALOGUE_DEFAULT_GRID_ENTRY_FIELD.tpl',
            'themes/default/templates/CATALOGUE_DEFAULT_ENTRY_SCREEN.tpl',
            'themes/default/templates/CATALOGUE_DEFAULT_TITLELIST_ENTRY.tpl',
            'themes/default/templates/CATALOGUE_DEFAULT_TITLELIST_WRAP.tpl',
            'themes/default/templates/CATALOGUE_ENTRIES_LIST_LINE.tpl',
            'themes/default/templates/SEARCH_RESULT_CATALOGUE_ENTRIES.tpl',
            'themes/default/templates/CATALOGUE_DEFAULT_TABULAR_ENTRY_WRAP.tpl',
            'themes/default/templates/CATALOGUE_DEFAULT_TABULAR_ENTRY_FIELD.tpl',
            'themes/default/templates/CATALOGUE_DEFAULT_TABULAR_HEADCELL.tpl',
            'themes/default/templates/CATALOGUE_DEFAULT_TABULAR_WRAP.tpl',
            'themes/default/templates/CATALOGUE_links_TABULAR_ENTRY_WRAP.tpl',
            'themes/default/templates/CATALOGUE_links_TABULAR_ENTRY_FIELD.tpl',
            'themes/default/templates/CATALOGUE_links_TABULAR_HEADCELL.tpl',
            'themes/default/templates/CATALOGUE_links_TABULAR_WRAP.tpl',
            'themes/default/templates/CATALOGUE_CATEGORY_HEADING.tpl',
            'sources/hooks/systems/sitemap/catalogue.php',
            'sources/hooks/systems/sitemap/catalogue_category.php',
            'sources/hooks/systems/sitemap/catalogue_entry.php',
            'uploads/catalogues/index.html',
            'uploads/catalogues/.htaccess',
            'cms/pages/modules/cms_catalogues.php',
            'lang/EN/catalogues.ini',
            'site/pages/modules/catalogues.php',
            'sources/hooks/systems/notifications/catalogue_view_reports.php',
            'sources/hooks/systems/notifications/catalogue_entry.php',
            'sources/catalogues.php',
            'sources/hooks/modules/admin_import/catalogues.php',
            'sources/catalogues2.php',
            'sources/hooks/modules/admin_newsletter/catalogues.php',
            'sources/hooks/modules/admin_setupwizard/catalogues.php',
            'sources/hooks/modules/admin_unvalidated/catalogue_entry.php',
            'sources/hooks/systems/attachments/catalogue_entry.php',
            'sources/blocks/main_cc_embed.php',
            'themes/default/css/catalogues.css',
            'sources/hooks/systems/symbols/CATALOGUE_ENTRY_BACKREFS.php',
            'sources/hooks/systems/symbols/CATALOGUE_ENTRY_FIELD_VALUE.php',
            'sources/hooks/systems/symbols/CATALOGUE_ENTRY_FIELD_VALUE_PLAIN.php',
            'sources/blocks/main_contact_catalogues.php',
            'sources/hooks/systems/symbols/CATALOGUE_ENTRY_ALL_FIELD_VALUES.php',
            'sources/hooks/systems/block_ui_renderers/catalogues.php',
            'sources/hooks/systems/config/catalogue_entries_per_page.php',
            'sources/hooks/systems/config/catalogue_subcats_per_page.php',
            'sources/hooks/systems/config/catalogues_subcat_narrowin.php',
            'sources/hooks/systems/tasks/export_catalogue.php',
            'sources/hooks/systems/tasks/import_catalogue.php',
        );
    }

    /**
     * Get mapping between template names and the method of this class that can render a preview of them
     *
     * @return array The mapping
     */
    public function tpl_previews()
    {
        return array(
            'templates/CATALOGUE_ADDING_SCREEN.tpl' => 'administrative__catalogue_adding_screen',
            'templates/CATALOGUE_EDITING_SCREEN.tpl' => 'administrative__catalogue_editing_screen',
            'templates/CATALOGUE_ENTRIES_LIST_LINE.tpl' => 'catalogue_entries_list_line',
            'templates/CATALOGUE_CATEGORIES_LIST_LINE.tpl' => 'catalogue_categories_list_line',
            'templates/SEARCH_RESULT_CATALOGUE_ENTRIES.tpl' => 'search_result_catalogue_entries',
            'templates/CATALOGUE_DEFAULT_CATEGORY_EMBED.tpl' => 'fieldmap_category_screen',

            'templates/CATALOGUE_CATEGORY_HEADING.tpl' => 'fieldmap_category_screen',
            'templates/CATALOGUE_DEFAULT_CATEGORY_SCREEN.tpl' => 'fieldmap_category_screen',

            'templates/CATALOGUE_DEFAULT_TABULAR_WRAP.tpl' => 'tabular_category_screen',
            'templates/CATALOGUE_DEFAULT_TABULAR_HEADCELL.tpl' => 'tabular_category_screen',
            'templates/CATALOGUE_DEFAULT_TABULAR_ENTRY_WRAP.tpl' => 'tabular_category_screen',
            'templates/CATALOGUE_DEFAULT_TABULAR_ENTRY_FIELD.tpl' => 'tabular_category_screen',

            'templates/CATALOGUE_DEFAULT_GRID_ENTRY_WRAP.tpl' => 'grid_category_screen',
            'templates/CATALOGUE_DEFAULT_GRID_ENTRY_FIELD.tpl' => 'grid_category_screen',

            'templates/CATALOGUE_links_TABULAR_WRAP.tpl' => 'tabular_category_screen__links',
            'templates/CATALOGUE_links_TABULAR_HEADCELL.tpl' => 'tabular_category_screen__links',
            'templates/CATALOGUE_links_TABULAR_ENTRY_WRAP.tpl' => 'tabular_category_screen__links',
            'templates/CATALOGUE_links_TABULAR_ENTRY_FIELD.tpl' => 'tabular_category_screen__links',

            'templates/CATALOGUE_DEFAULT_TITLELIST_ENTRY.tpl' => 'list_category_screen',
            'templates/CATALOGUE_DEFAULT_TITLELIST_WRAP.tpl' => 'list_category_screen',

            'templates/CATALOGUE_DEFAULT_FIELDMAP_ENTRY_WRAP.tpl' => 'entry_screen',
            'templates/CATALOGUE_DEFAULT_FIELDMAP_ENTRY_FIELD.tpl' => 'entry_screen',

            'templates/CATALOGUE_DEFAULT_ENTRY_SCREEN.tpl' => 'entry_screen'
        );
    }

    /**
     * Get a preview(s) of a (group of) template(s), as a full standalone piece of HTML in Tempcode format.
     * Uses sources/lorem.php functions to place appropriate stock-text. Should not hard-code things, as the code is intended to be declaritive.
     * Assumptions: You can assume all Lang/CSS/JavaScript files in this addon have been pre-required.
     *
     * @return array Array of previews, each is Tempcode. Normally we have just one preview, but occasionally it is good to test templates are flexible (e.g. if they use IF_EMPTY, we can test with and without blank data).
     */
    public function tpl_preview__grid_category_screen()
    {
        $subcategories = new Tempcode();
        $subcategories->attach(do_lorem_template('SIMPLE_PREVIEW_BOX', array(
            'TITLE' => lorem_phrase(),
            'SUMMARY' => lorem_paragraph_html(),
            'URL' => placeholder_url(),
        )));
        $tags = do_lorem_template('TAGS', array(
            'TAGS' => placeholder_array(),
            'TYPE' => null,
            'LINK_FULLSCOPE' => placeholder_url(),
            'TAG' => lorem_word(),
        ));

        $entries = new Tempcode();
        $fields = new Tempcode();
        foreach (placeholder_array() as $v) {
            $fields->attach(do_lorem_template('CATALOGUE_DEFAULT_GRID_ENTRY_FIELD', array(
                'ENTRYID' => placeholder_random_id(),
                'CATALOGUE' => lorem_phrase(),
                'TYPE' => lorem_word(),
                'FIELD' => lorem_word(),
                'FIELDID' => placeholder_random_id(),
                '_FIELDID' => placeholder_id(),
                'FIELDTYPE' => lorem_word(),
                'VALUE_PLAIN' => lorem_phrase(),
                'VALUE' => lorem_phrase(),
            )));
        }
        $content = do_lorem_template('CATALOGUE_DEFAULT_GRID_ENTRY_WRAP', array(
            'FIELDS' => $fields,
            'VIEW_URL' => placeholder_url(),
            'FIELD_0' => lorem_word(),
        ));

        $entries = do_lorem_template('CATALOGUE_DEFAULT_CATEGORY_EMBED', array(
            'DISPLAY_TYPE' => 'GRID',
            'ENTRIES' => $entries,
            'ROOT' => placeholder_id(),
            'BLOCK_PARAMS' => '',
            'SORTING' => '',
            'PAGINATION' => '',

            'CART_LINK' => new Tempcode(),

            'START' => '0',
            'MAX' => '10',
            'START_PARAM' => 'x_start',
            'MAX_PARAM' => 'x_max',
        ));

        return array(
            lorem_globalise(do_lorem_template('CATALOGUE_DEFAULT_CATEGORY_SCREEN', array(
                'ID' => placeholder_id(),
                'ADD_DATE_RAW' => placeholder_time(),
                'TITLE' => lorem_title(),
                '_TITLE' => lorem_phrase(),
                'TAGS' => $tags,
                'CATALOGUE' => lorem_word_2(),
                'ADD_ENTRY_URL' => placeholder_url(),
                'ADD_CAT_URL' => placeholder_url(),
                'EDIT_CAT_URL' => placeholder_url(),
                'EDIT_CATALOGUE_URL' => placeholder_url(),
                'ENTRIES' => $entries,
                'SUBCATEGORIES' => $subcategories,
                'DESCRIPTION' => lorem_sentence(),
                'CART_LINK' => placeholder_link(),
                'DISPLAY_TYPE' => '0',
            )), null, '', true)
        );
    }

    /**
     * Get a preview(s) of a (group of) template(s), as a full standalone piece of HTML in Tempcode format.
     * Uses sources/lorem.php functions to place appropriate stock-text. Should not hard-code things, as the code is intended to be declaritive.
     * Assumptions: You can assume all Lang/CSS/JavaScript files in this addon have been pre-required.
     *
     * @return array Array of previews, each is Tempcode. Normally we have just one preview, but occasionally it is good to test templates are flexible (e.g. if they use IF_EMPTY, we can test with and without blank data).
     */
    public function tpl_preview__fieldmap_category_screen()
    {
        $subcategories = new Tempcode();
        $tags = do_lorem_template('TAGS', array(
            'TAGS' => placeholder_array(),
            'TYPE' => null,
            'LINK_FULLSCOPE' => placeholder_url(),
            'TAG' => lorem_word(),
        ));

        $entries = new Tempcode();
        $fields = new Tempcode();
        foreach (placeholder_array() as $v) {
            $fields->attach(do_lorem_template('CATALOGUE_DEFAULT_FIELDMAP_ENTRY_FIELD', array(
                'ENTRYID' => placeholder_random_id(),
                'CATALOGUE' => lorem_phrase(),
                'TYPE' => lorem_word(),
                'FIELD' => lorem_word(),
                'FIELDID' => placeholder_random_id(),
                '_FIELDID' => placeholder_id(),
                'FIELDTYPE' => lorem_word(),
                'VALUE_PLAIN' => lorem_phrase(),
                'VALUE' => lorem_phrase(),
            )));
        }
        $content = do_lorem_template('CATALOGUE_DEFAULT_FIELDMAP_ENTRY_WRAP', array(
            'ID' => placeholder_id(),
            'FIELDS' => $fields,
            'VIEW_URL' => placeholder_url(),
            'FIELD_0' => lorem_word(),
            'GIVE_CONTEXT' => false,
        ));
        foreach (placeholder_array(2) as $v) {
            $entries->attach(do_lorem_template('CATALOGUE_CATEGORY_HEADING', array(
                'LETTER' => lorem_phrase(),
                'ENTRIES' => $content,
            )));
        }

        $entries = do_lorem_template('CATALOGUE_DEFAULT_CATEGORY_EMBED', array(
            'DISPLAY_TYPE' => 'FIELDMAPS',
            'ENTRIES' => $entries,
            'ROOT' => placeholder_id(),
            'BLOCK_PARAMS' => '',
            'SORTING' => '',
            'PAGINATION' => '',

            'CART_LINK' => new Tempcode(),

            'START' => '0',
            'MAX' => '10',
            'START_PARAM' => 'x_start',
            'MAX_PARAM' => 'x_max',
        ));

        return array(
            lorem_globalise(do_lorem_template('CATALOGUE_DEFAULT_CATEGORY_SCREEN', array(
                'ID' => placeholder_id(),
                'ADD_DATE_RAW' => placeholder_time(),
                'TITLE' => lorem_title(),
                '_TITLE' => lorem_phrase(),
                'TAGS' => $tags,
                'CATALOGUE' => lorem_word_2(),
                'ADD_ENTRY_URL' => placeholder_url(),
                'ADD_CAT_URL' => placeholder_url(),
                'EDIT_CAT_URL' => placeholder_url(),
                'EDIT_CATALOGUE_URL' => placeholder_url(),
                'ENTRIES' => $entries,
                'SUBCATEGORIES' => $subcategories,
                'DESCRIPTION' => lorem_sentence(),
                'CART_LINK' => placeholder_link(),
                'DISPLAY_TYPE' => '0',
            )), null, '', true)
        );
    }

    /**
     * Get a preview(s) of a (group of) template(s), as a full standalone piece of HTML in Tempcode format.
     * Uses sources/lorem.php functions to place appropriate stock-text. Should not hard-code things, as the code is intended to be declaritive.
     * Assumptions: You can assume all Lang/CSS/JavaScript files in this addon have been pre-required.
     *
     * @return array Array of previews, each is Tempcode. Normally we have just one preview, but occasionally it is good to test templates are flexible (e.g. if they use IF_EMPTY, we can test with and without blank data).
     */
    public function tpl_preview__list_category_screen()
    {
        $type = 'default';
        $content = new Tempcode();
        foreach (placeholder_array() as $v) {
            $content->attach(do_lorem_template('CATALOGUE_DEFAULT_TITLELIST_ENTRY', array(
                'VIEW_URL' => placeholder_url(),
                'ID' => placeholder_url(),
                'FIELD_0' => lorem_word_2(),
                'FIELD_0_PLAIN' => lorem_word(),
            )));
        }
        $entries = do_lorem_template('CATALOGUE_DEFAULT_TITLELIST_WRAP', array(
            'CATALOGUE' => lorem_word(),
            'CONTENT' => $content,
        ));

        $tags = do_lorem_template('TAGS', array(
            'TAGS' => placeholder_array(),
            'TYPE' => null,
            'LINK_FULLSCOPE' => placeholder_url(),
            'TAG' => lorem_word(),
        ));

        $entries = do_lorem_template('CATALOGUE_DEFAULT_CATEGORY_EMBED', array(
            'DISPLAY_TYPE' => 'TITLELIST',
            'ENTRIES' => $entries,
            'ROOT' => placeholder_id(),
            'BLOCK_PARAMS' => '',
            'SORTING' => '',
            'PAGINATION' => '',

            'CART_LINK' => new Tempcode(),

            'START' => '0',
            'MAX' => '10',
            'START_PARAM' => 'x_start',
            'MAX_PARAM' => 'x_max',
        ));

        return array(
            lorem_globalise(do_lorem_template('CATALOGUE_DEFAULT_CATEGORY_SCREEN', array(
                'ID' => placeholder_id(),
                'ADD_DATE_RAW' => placeholder_time(),
                'TITLE' => lorem_title(),
                '_TITLE' => lorem_phrase(),
                'TAGS' => $tags,
                'CATALOGUE' => lorem_word_2(),
                'ADD_ENTRY_URL' => placeholder_url(),
                'ADD_CAT_URL' => placeholder_url(),
                'EDIT_CAT_URL' => placeholder_url(),
                'EDIT_CATALOGUE_URL' => placeholder_url(),
                'ENTRIES' => $entries,
                'SUBCATEGORIES' => '',
                'DESCRIPTION' => lorem_sentence(),
                'CART_LINK' => placeholder_link(),
                'DISPLAY_TYPE' => '0',
            )), null, '', true)
        );
    }

    /**
     * Get a preview(s) of a (group of) template(s), as a full standalone piece of HTML in Tempcode format.
     * Uses sources/lorem.php functions to place appropriate stock-text. Should not hard-code things, as the code is intended to be declaritive.
     * Assumptions: You can assume all Lang/CSS/JavaScript files in this addon have been pre-required.
     *
     * @return array Array of previews, each is Tempcode. Normally we have just one preview, but occasionally it is good to test templates are flexible (e.g. if they use IF_EMPTY, we can test with and without blank data).
     */
    public function tpl_preview__tabular_category_screen__links()
    {
        $subcategories = new Tempcode();
        $tags = do_lorem_template('TAGS', array(
            'TAGS' => placeholder_array(),
            'TYPE' => null,
            'LINK_FULLSCOPE' => placeholder_url(),
            'TAG' => lorem_word(),
        ));

        $row = new Tempcode();
        $entry_fields = new Tempcode();
        $head = new Tempcode();
        foreach (placeholder_array() as $v) {
            $head->attach(do_lorem_template('CATALOGUE_links_TABULAR_HEADCELL', array(
                'SORT_ASC_SELECTED' => true,
                'SORT_DESC_SELECTED' => false,
                'SORT_URL_ASC' => placeholder_url(),
                'SORT_URL_DESC' => placeholder_url(),
                'CATALOGUE' => lorem_word(),
                'FIELDID' => placeholder_random_id(),
                '_FIELDID' => placeholder_random_id(),
                'FIELD' => $v,
                'FIELDTYPE' => 'text',
            )));
            $entry_fields->attach(do_lorem_template('CATALOGUE_links_TABULAR_ENTRY_FIELD', array(
                'FIELDID' => placeholder_random_id(),
                'ENTRYID' => placeholder_random_id(),
                'VALUE' => lorem_phrase(),
            )));
        }
        $row->attach(do_lorem_template('CATALOGUE_links_TABULAR_ENTRY_WRAP', array(
            'FIELDS_TABULAR' => $entry_fields,
            'VIEW_URL' => placeholder_url(),
            'EDIT_URL' => placeholder_url(),
            'FIELD_1_PLAIN' => lorem_phrase(),
        )));
        $content = do_lorem_template('CATALOGUE_links_TABULAR_WRAP', array(
            'CATALOGUE' => lorem_word(),
            'HEAD' => $head,
            'CONTENT' => $row,
            'FIELD_COUNT' => '3',
        ));

        $entries = do_lorem_template('CATALOGUE_DEFAULT_CATEGORY_EMBED', array(
            'DISPLAY_TYPE' => 'TABULAR',
            'ENTRIES' => $content,
            'ROOT' => placeholder_id(),
            'BLOCK_PARAMS' => '',
            'SORTING' => '',
            'PAGINATION' => '',

            'CART_LINK' => new Tempcode(),

            'START' => '0',
            'MAX' => '10',
            'START_PARAM' => 'x_start',
            'MAX_PARAM' => 'x_max',
        ));

        return array(
            lorem_globalise(do_lorem_template('CATALOGUE_DEFAULT_CATEGORY_SCREEN', array(
                'ID' => placeholder_id(),
                'ADD_DATE_RAW' => placeholder_time(),
                'TITLE' => lorem_title(),
                '_TITLE' => lorem_phrase(),
                'TAGS' => $tags,
                'CATALOGUE' => lorem_word_2(),
                'ADD_ENTRY_URL' => placeholder_url(),
                'ADD_CAT_URL' => placeholder_url(),
                'EDIT_CAT_URL' => placeholder_url(),
                'EDIT_CATALOGUE_URL' => placeholder_url(),
                'ENTRIES' => $entries,
                'SUBCATEGORIES' => $subcategories,
                'DESCRIPTION' => lorem_sentence(),
                'CART_LINK' => placeholder_link(),
                'DISPLAY_TYPE' => '0',
            )), null, '', true)
        );
    }

    /**
     * Get a preview(s) of a (group of) template(s), as a full standalone piece of HTML in Tempcode format.
     * Uses sources/lorem.php functions to place appropriate stock-text. Should not hard-code things, as the code is intended to be declaritive.
     * Assumptions: You can assume all Lang/CSS/JavaScript files in this addon have been pre-required.
     *
     * @return array Array of previews, each is Tempcode. Normally we have just one preview, but occasionally it is good to test templates are flexible (e.g. if they use IF_EMPTY, we can test with and without blank data).
     */
    public function tpl_preview__tabular_category_screen()
    {
        $subcategories = new Tempcode();
        $tags = do_lorem_template('TAGS', array(
            'TAGS' => placeholder_array(),
            'TYPE' => null,
            'LINK_FULLSCOPE' => placeholder_url(),
            'TAG' => lorem_word(),
        ));

        $entries = new Tempcode();
        $head = do_lorem_template('CATALOGUE_DEFAULT_TABULAR_HEADCELL', array(
            'SORT_ASC_SELECTED' => true,
            'SORT_DESC_SELECTED' => false,
            'SORT_URL_ASC' => placeholder_url(),
            'SORT_URL_DESC' => placeholder_url(),
            'CATALOGUE' => lorem_word(),
            'FIELDID' => placeholder_id(),
            '_FIELDID' => placeholder_id(),
            'FIELD' => lorem_word(),
            'FIELDTYPE' => 'text',
        ));
        $fields = new Tempcode();
        $fields->attach(do_lorem_template('CATALOGUE_DEFAULT_TABULAR_ENTRY_FIELD', array(
            'FIELDID' => placeholder_id(),
            'ENTRYID' => placeholder_id(),
            'VALUE' => lorem_phrase(),
        )));
        $entries->attach(do_lorem_template('CATALOGUE_DEFAULT_TABULAR_ENTRY_WRAP', array(
            'FIELDS_TABULAR' => $fields,
            'EDIT_URL' => placeholder_url(),
            'VIEW_URL' => placeholder_url(),
        )));
        $content = do_lorem_template('CATALOGUE_DEFAULT_TABULAR_WRAP', array(
            'CATALOGUE' => lorem_word(),
            'HEAD' => $head,
            'CONTENT' => $entries,
            'FIELD_COUNT' => '1',
        ));

        $entries = do_lorem_template('CATALOGUE_DEFAULT_CATEGORY_EMBED', array(
            'DISPLAY_TYPE' => 'TABULAR',
            'ENTRIES' => $content,
            'ROOT' => placeholder_id(),
            'BLOCK_PARAMS' => '',
            'SORTING' => '',
            'PAGINATION' => '',

            'CART_LINK' => new Tempcode(),

            'START' => '0',
            'MAX' => '10',
            'START_PARAM' => 'x_start',
            'MAX_PARAM' => 'x_max',
        ));

        return array(
            lorem_globalise(do_lorem_template('CATALOGUE_DEFAULT_CATEGORY_SCREEN', array(
                'ID' => placeholder_id(),
                'ADD_DATE_RAW' => placeholder_time(),
                'TITLE' => lorem_title(),
                '_TITLE' => lorem_phrase(),
                'TAGS' => $tags,
                'CATALOGUE' => lorem_word_2(),
                'ADD_ENTRY_URL' => placeholder_url(),
                'ADD_CAT_URL' => placeholder_url(),
                'EDIT_CAT_URL' => placeholder_url(),
                'EDIT_CATALOGUE_URL' => placeholder_url(),
                'ENTRIES' => $entries,
                'SUBCATEGORIES' => $subcategories,
                'DESCRIPTION' => lorem_sentence(),
                'CART_LINK' => placeholder_link(),
                'DISPLAY_TYPE' => '0',
            )), null, '', true)
        );
    }

    /**
     * Get a preview(s) of a (group of) template(s), as a full standalone piece of HTML in Tempcode format.
     * Uses sources/lorem.php functions to place appropriate stock-text. Should not hard-code things, as the code is intended to be declaritive.
     * Assumptions: You can assume all Lang/CSS/JavaScript files in this addon have been pre-required.
     *
     * @return array Array of previews, each is Tempcode. Normally we have just one preview, but occasionally it is good to test templates are flexible (e.g. if they use IF_EMPTY, we can test with and without blank data).
     */
    public function tpl_preview__entry_screen()
    {
        $tags = do_lorem_template('TAGS', array(
            'TAGS' => placeholder_array(),
            'TYPE' => null,
            'LINK_FULLSCOPE' => placeholder_url(),
            'TAG' => lorem_word(),
        ));

        $fields = new Tempcode();
        foreach (placeholder_array() as $v) {
            $fields->attach(do_lorem_template('CATALOGUE_DEFAULT_FIELDMAP_ENTRY_FIELD', array(
                'ENTRYID' => placeholder_id(),
                'CATALOGUE' => lorem_phrase(),
                'TYPE' => lorem_word(),
                'FIELD' => lorem_word(),
                'FIELDID' => placeholder_id(),
                '_FIELDID' => placeholder_id(),
                'FIELDTYPE' => lorem_word(),
                'VALUE_PLAIN' => lorem_phrase(),
                'VALUE' => lorem_phrase(),
            )));
        }

        $entry = do_lorem_template('CATALOGUE_DEFAULT_FIELDMAP_ENTRY_WRAP', array(
            'ID' => placeholder_id(),
            'FIELDS' => $fields,
            'VIEW_URL' => placeholder_url(),
            'FIELD_0' => lorem_word(),
            'ENTRY_SCREEN' => true,
            'GIVE_CONTEXT' => false,
        ));

        return array(
            lorem_globalise(do_lorem_template('CATALOGUE_DEFAULT_ENTRY_SCREEN', array(
                'TITLE' => lorem_title(),
                'WARNINGS' => '',
                'ID' => placeholder_id(),
                'ENTRY' => $entry,
                'EDIT_URL' => placeholder_url(),
                'TRACKBACK_DETAILS' => lorem_phrase(),
                'RATING_DETAILS' => lorem_phrase(),
                'COMMENT_DETAILS' => lorem_phrase(),
                'ADD_DATE' => placeholder_time(),
                'ADD_DATE_RAW' => placeholder_date_raw(),
                'EDIT_DATE_RAW' => placeholder_date_raw(),
                'VIEWS' => placeholder_number(),
                'TAGS' => $tags,
                'SUBMITTER' => placeholder_id(),
                'FIELD_1' => lorem_word(),
            )), null, '', true)
        );
    }

    /**
     * Get a preview(s) of a (group of) template(s), as a full standalone piece of HTML in Tempcode format.
     * Uses sources/lorem.php functions to place appropriate stock-text. Should not hard-code things, as the code is intended to be declaritive.
     * Assumptions: You can assume all Lang/CSS/JavaScript files in this addon have been pre-required.
     *
     * @return array Array of previews, each is Tempcode. Normally we have just one preview, but occasionally it is good to test templates are flexible (e.g. if they use IF_EMPTY, we can test with and without blank data).
     */
    public function tpl_preview__administrative__catalogue_adding_screen()
    {
        require_javascript('checking');

        return array(
            lorem_globalise(do_lorem_template('CATALOGUE_ADDING_SCREEN', array(
                'HIDDEN' => '',
                'TITLE' => lorem_title(),
                'TEXT' => lorem_sentence_html(),
                'URL' => placeholder_url(),
                'FIELDS' => placeholder_fields(),
                'FIELDS_NEW' => placeholder_form(),
                'SUBMIT_ICON' => 'menu___generic_admin__add_one',
                'SUBMIT_NAME' => lorem_word(),
            )), null, '', true)
        );
    }

    /**
     * Get a preview(s) of a (group of) template(s), as a full standalone piece of HTML in Tempcode format.
     * Uses sources/lorem.php functions to place appropriate stock-text. Should not hard-code things, as the code is intended to be declaritive.
     * Assumptions: You can assume all Lang/CSS/JavaScript files in this addon have been pre-required.
     *
     * @return array Array of previews, each is Tempcode. Normally we have just one preview, but occasionally it is good to test templates are flexible (e.g. if they use IF_EMPTY, we can test with and without blank data).
     */
    public function tpl_preview__administrative__catalogue_editing_screen()
    {
        require_javascript('checking');

        return array(
            lorem_globalise(do_lorem_template('CATALOGUE_EDITING_SCREEN', array(
                'HIDDEN' => '',
                'TITLE' => lorem_title(),
                'TEXT' => lorem_sentence_html(),
                'URL' => placeholder_url(),
                'FIELDS' => placeholder_fields(),
                'FIELDS_EXISTING' => placeholder_form(),
                'FIELDS_NEW' => placeholder_form(),
                'SUBMIT_ICON' => 'menu___generic_admin__edit_this',
                'SUBMIT_NAME' => lorem_word(),
            )), null, '', true)
        );
    }

    /**
     * Get a preview(s) of a (group of) template(s), as a full standalone piece of HTML in Tempcode format.
     * Uses sources/lorem.php functions to place appropriate stock-text. Should not hard-code things, as the code is intended to be declaritive.
     * Assumptions: You can assume all Lang/CSS/JavaScript files in this addon have been pre-required.
     *
     * @return array Array of previews, each is Tempcode. Normally we have just one preview, but occasionally it is good to test templates are flexible (e.g. if they use IF_EMPTY, we can test with and without blank data).
     */
    public function tpl_preview__catalogue_entries_list_line()
    {
        return array(
            lorem_globalise(do_lorem_template('CATALOGUE_ENTRIES_LIST_LINE', array(
                'BREADCRUMBS' => lorem_phrase(),
                'NAME' => lorem_word(),
            )), null, '', true)
        );
    }

    /**
     * Get a preview(s) of a (group of) template(s), as a full standalone piece of HTML in Tempcode format.
     * Uses sources/lorem.php functions to place appropriate stock-text. Should not hard-code things, as the code is intended to be declaritive.
     * Assumptions: You can assume all Lang/CSS/JavaScript files in this addon have been pre-required.
     *
     * @return array Array of previews, each is Tempcode. Normally we have just one preview, but occasionally it is good to test templates are flexible (e.g. if they use IF_EMPTY, we can test with and without blank data).
     */
    public function tpl_preview__catalogue_categories_list_line()
    {
        return array(
            lorem_globalise(do_lorem_template('CATALOGUE_CATEGORIES_LIST_LINE', array(
                'BREADCRUMBS' => lorem_phrase(),
                'COUNT' => placeholder_number(),
            )), null, '', true)
        );
    }

    /**
     * Get a preview(s) of a (group of) template(s), as a full standalone piece of HTML in Tempcode format.
     * Uses sources/lorem.php functions to place appropriate stock-text. Should not hard-code things, as the code is intended to be declaritive.
     * Assumptions: You can assume all Lang/CSS/JavaScript files in this addon have been pre-required.
     *
     * @return array Array of previews, each is Tempcode. Normally we have just one preview, but occasionally it is good to test templates are flexible (e.g. if they use IF_EMPTY, we can test with and without blank data).
     */
    public function tpl_preview__search_result_catalogue_entries()
    {
        return array(
            lorem_globalise(do_lorem_template('SEARCH_RESULT_CATALOGUE_ENTRIES', array(
                'BUILDUP' => lorem_phrase(),
                'NAME' => lorem_word_html(),
                'TITLE' => lorem_word(),
            )), null, '', true)
        );
    }
}
