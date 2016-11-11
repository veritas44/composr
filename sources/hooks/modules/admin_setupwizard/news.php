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
 * @package    news
 */

/**
 * Hook class.
 */
class Hook_sw_news
{
    /**
     * Run function for features in the setup wizard.
     *
     * @return array Current settings.
     */
    public function get_current_settings()
    {
        $settings = array();

        $keep_news_categories = false;
        $news_cats = $GLOBALS['SITE_DB']->query_select('news_categories', array('id'), array('nc_owner' => null));
        foreach ($news_cats as $news_cat) {
            if (($news_cat['id'] > db_get_first_id()) && ($news_cat['id'] < db_get_first_id() + 7)) {
                $keep_news_categories = true;
                break;
            }
        }
        $settings['keep_news_categories'] = $keep_news_categories ? '1' : '0';

        $test = $GLOBALS['SITE_DB']->query_select_value('group_privileges', 'COUNT(*)', array('privilege' => 'have_personal_category', 'the_page' => 'cms_news'));
        $settings['keep_blogs'] = ($test == 0) ? '0' : '1';

        return $settings;
    }

    /**
     * Run function for features in the setup wizard.
     *
     * @param  array $field_defaults Default values for the fields, from the install-profile.
     * @return Tempcode An input field.
     */
    public function get_fields($field_defaults)
    {
        if (!addon_installed('news') || post_param_integer('addon_news', null) === 0) {
            return new Tempcode();
        }

        $current_settings = $this->get_current_settings();
        $field_defaults += $current_settings; // $field_defaults will take precedence, due to how "+" operator works in PHP

        require_lang('news');
        $fields = new Tempcode();

        $fields->attach(form_input_tick(do_lang_tempcode('KEEP_BLOGS'), do_lang_tempcode('DESCRIPTION_KEEP_BLOGS'), 'keep_blogs', $field_defaults['keep_blogs'] == '1'));

        if ($current_settings['keep_news_categories'] == '1') {
            $fields->attach(form_input_tick(do_lang_tempcode('EXTENDED_NEWS_CATEGORIES_SET'), do_lang_tempcode('DESCRIPTION_KEEP_DEFAULT_NEWS_CATEGORIES'), 'keep_news_categories', $field_defaults['keep_news_categories'] == '1'));
        }

        return $fields;
    }

    /**
     * Run function for setting features from the setup wizard.
     */
    public function set_fields()
    {
        if (!addon_installed('news') || post_param_integer('addon_news', null) === 0) {
            return;
        }

        $admin_groups = $GLOBALS['FORUM_DRIVER']->get_super_admin_groups();
        $groups = $GLOBALS['FORUM_DRIVER']->get_usergroup_list(false, true);
        $GLOBALS['SITE_DB']->query_delete('group_privileges', array('privilege' => 'have_personal_category', 'the_page' => 'cms_news'));
        if (post_param_integer('keep_blogs', 0) == 1) {
            foreach (array_keys($groups) as $group_id) {
                if (!in_array($group_id, $admin_groups)) {
                    $GLOBALS['SITE_DB']->query_insert('group_privileges', array('privilege' => 'have_personal_category', 'group_id' => $group_id, 'module_the_name' => '', 'category_name' => '', 'the_page' => 'cms_news', 'the_value' => 1));
                }
            }
        }
        if (post_param_integer('keep_news_categories', 0) == 0) {
            $news_cats = $GLOBALS['SITE_DB']->query_select('news_categories', array('id'), array('nc_owner' => null));
            foreach ($news_cats as $news_cat) {
                if (($news_cat['id'] > db_get_first_id()) && ($news_cat['id'] < db_get_first_id() + 7)) {
                    require_code('news2');
                    delete_news_category($news_cat['id']);
                }
            }
        }
    }

    /**
     * Run function for blocks in the setup wizard.
     *
     * @return array Map of block names, to display types.
     */
    public function get_blocks()
    {
        return array(array('main_news' => array('NO', 'YES')), array('side_news_archive' => array('PANEL_NONE', 'PANEL_NONE'), 'side_news_categories' => array('PANEL_RIGHT', 'PANEL_RIGHT'), 'side_news' => array('PANEL_NONE', 'PANEL_NONE')));
    }
}
