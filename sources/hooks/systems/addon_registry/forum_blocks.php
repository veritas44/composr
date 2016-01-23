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
 * @package    forum_blocks
 */

/**
 * Hook class.
 */
class Hook_addon_registry_forum_blocks
{
    /**
     * Get a list of file permissions to set
     *
     * @param  boolean $runtime Whether to include wildcards represented runtime-created chmoddable files
     * @return array File permissions to set
     */
    public function get_chmod_array($runtime = false)
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
        return 'Blocks to draw forum posts and topics into the main website.';
    }

    /**
     * Get a list of tutorials that apply to this addon
     *
     * @return array List of tutorials
     */
    public function get_applicable_tutorials()
    {
        return array(
            'tut_featured',
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
            'requires' => array(
                'news_shared'
            ),
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
        return 'themes/default/images/icons/48x48/menu/_generic_admin/component.png';
    }

    /**
     * Get a list of files that belong to this addon
     *
     * @return array List of files
     */
    public function get_file_list()
    {
        return array(
            'themes/default/templates/BLOCK_MAIN_FORUM_NEWS.tpl',
            'themes/default/templates/BLOCK_MAIN_FORUM_TOPICS.tpl',
            'themes/default/templates/BLOCK_SIDE_FORUM_NEWS.tpl',
            'sources/blocks/bottom_forum_news.php',
            'sources/blocks/main_forum_news.php',
            'sources/blocks/main_forum_topics.php',
            'sources/blocks/side_forum_news.php',
            'sources/hooks/systems/addon_registry/forum_blocks.php',
            'sources/hooks/modules/admin_setupwizard/forum_blocks.php',
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
            'templates/BLOCK_MAIN_FORUM_TOPICS.tpl' => 'block_main_forum_topics',
            'templates/BLOCK_SIDE_FORUM_NEWS.tpl' => 'block_side_forum_news',
            'templates/BLOCK_MAIN_FORUM_NEWS.tpl' => 'block_main_forum_news'
        );
    }

    /**
     * Get a preview(s) of a (group of) template(s), as a full standalone piece of HTML in Tempcode format.
     * Uses sources/lorem.php functions to place appropriate stock-text. Should not hard-code things, as the code is intended to be declaritive.
     * Assumptions: You can assume all Lang/CSS/JavaScript files in this addon have been pre-required.
     *
     * @return array Array of previews, each is Tempcode. Normally we have just one preview, but occasionally it is good to test templates are flexible (e.g. if they use IF_EMPTY, we can test with and without blank data).
     */
    public function tpl_preview__block_main_forum_topics()
    {
        require_lang('cns');
        $topics = array();
        foreach (placeholder_array() as $k => $v) {
            $topics[] = array(
                'POST' => lorem_paragraph(),
                'FORUM_ID' => null,
                'FORUM_NAME' => lorem_word(),
                'TOPIC_URL' => placeholder_url(),
                'TITLE' => lorem_word(),
                'DATE' => placeholder_date(),
                'DATE_RAW' => placeholder_date_raw(),
                'USERNAME' => lorem_word(),
                'MEMBER_ID' => null,
                'NUM_POSTS' => placeholder_number(),
            );
        }

        return array(
            lorem_globalise(do_lorem_template('BLOCK_MAIN_FORUM_TOPICS', array(
                'TITLE' => lorem_word(),
                'TOPICS' => $topics,
                'FORUM_NAME' => lorem_word_html(),
                'SUBMIT_URL' => placeholder_url(),
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
    public function tpl_preview__block_side_forum_news()
    {
        require_lang('news');
        require_lang('cns');

        $news = array();
        foreach (placeholder_array() as $k => $v) {
            $news[] = array(
                'REPLIES' => lorem_word(),
                'FIRSTTIME' => lorem_word(),
                'LASTTIME' => lorem_word(),
                'CLOSED' => lorem_word(),
                'FIRSTUSERNAME' => lorem_word(),
                'LASTUSERNAME' => lorem_word(),
                'FIRSTMEMBERID' => placeholder_random_id(),
                'LASTMEMBERID' => placeholder_random_id(),
                '_DATE' => placeholder_date_raw(),
                'DATE' => placeholder_date(),
                'FULL_URL' => placeholder_url(),
                'NEWS_TITLE' => escape_html(lorem_word()),
            );
        }

        return array(
            lorem_globalise(do_lorem_template('BLOCK_SIDE_FORUM_NEWS', array(
                'FORUM_NAME' => lorem_word_html(),
                'TITLE' => lorem_phrase(),
                'NEWS' => $news,
                'SUBMIT_URL' => placeholder_url(),
                'ARCHIVE_URL' => placeholder_url(),
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
    public function tpl_preview__block_main_forum_news()
    {
        require_lang('news');

        $out = new Tempcode();
        foreach (placeholder_array() as $k => $v) {
            $out->attach(do_lorem_template('NEWS_BOX', array(
                'TRUNCATE' => false,
                'BLOG' => false,
                'FIRSTTIME' => lorem_word(),
                'LASTTIME' => lorem_word(),
                'CLOSED' => lorem_word(),
                'FIRSTUSERNAME' => lorem_word(),
                'LASTUSERNAME' => lorem_word(),
                'FIRSTMEMBERID' => placeholder_random_id(),
                'LASTMEMBERID' => placeholder_random_id(),
                'ID' => placeholder_random_id(),
                'FULL_URL' => placeholder_url(),
                'SUBMITTER' => placeholder_id(),
                'DATE' => placeholder_date(),
                'DATE_RAW' => placeholder_date_raw(),
                'NEWS_TITLE' => lorem_word(),
                'NEWS_TITLE_PLAIN' => lorem_word(),
                'CATEGORY' => '',
                'IMG' => '',
                '_IMG' => '',
                'AUTHOR' => lorem_word(),
                'AUTHOR_URL' => placeholder_url(),
                'NEWS' => lorem_paragraph(),
                'GIVE_CONTEXT' => false,
            )));
        }

        return array(
            lorem_globalise(do_lorem_template('BLOCK_MAIN_FORUM_NEWS', array(
                'TITLE' => lorem_word(),
                'FORUM_NAME' => lorem_word_html(),
                'CONTENT' => $out,
                'BRIEF' => lorem_phrase(),
                'ARCHIVE_URL' => placeholder_url(),
                'SUBMIT_URL' => placeholder_url(),
                'RSS_URL' => placeholder_url(),
                'ATOM_URL' => placeholder_url(),
            )), null, '', true)
        );
    }
}
