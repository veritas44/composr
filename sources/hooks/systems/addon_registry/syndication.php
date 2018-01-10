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
 * @package    syndication
 */

/**
 * Hook class.
 */
class Hook_addon_registry_syndication
{
    /**
     * Get a list of file permissions to set.
     *
     * @param  boolean $runtime Whether to include wildcards represented runtime-created chmoddable files
     * @return array File permissions to set
     */
    public function get_chmod_array($runtime = false)
    {
        return array();
    }

    /**
     * Get the version of Composr this addon is for.
     *
     * @return float Version number
     */
    public function get_version()
    {
        return cms_version_number();
    }

    /**
     * Get the description of the addon.
     *
     * @return string Description of the addon
     */
    public function get_description()
    {
        return 'Syndicate RSS/Atom feeds of your content.';
    }

    /**
     * Get a list of tutorials that apply to this addon.
     *
     * @return array List of tutorials
     */
    public function get_applicable_tutorials()
    {
        return array(
            'tut_integration',
            'tut_news',
            'tut_adv_news',
        );
    }

    /**
     * Get a mapping of dependency types.
     *
     * @return array File permissions to set
     */
    public function get_dependencies()
    {
        return array(
            'requires' => array(
                'syndication_blocks',
            ),
            'recommends' => array(),
            'conflicts_with' => array(),
            'previously_in_addon' => array('core_syndication'),
        );
    }

    /**
     * Explicitly say which icon should be used.
     *
     * @return URLPATH Icon
     */
    public function get_default_icon()
    {
        return 'themes/default/images/icons/48x48/links/rss.png';
    }

    /**
     * Get a list of files that belong to this addon.
     *
     * @return array List of files
     */
    public function get_file_list()
    {
        return array(
            'sources/hooks/systems/addon_registry/syndication.php',
            'themes/default/templates/RSS_HEADER.tpl',
            'themes/default/xml/ATOM_ENTRY.xml',
            'themes/default/xml/ATOM_WRAPPER.xml',
            'themes/default/xml/RSS_CLOUD.xml',
            'themes/default/xml/RSS_ENTRY.xml',
            'themes/default/xml/RSS_ENTRY_COMMENTS.xml',
            'themes/default/xml/RSS_WRAPPER.xml',
            'themes/default/xml/ATOM_XSLT.xml',
            'themes/default/xml/RSS_ABBR.xml',
            'themes/default/xml/RSS_XSLT.xml',
            'themes/default/xml/OPML_WRAPPER.xml',
            'themes/default/xml/OPML_XSLT.xml',
            'backend.php',
            'data/backend_cloud.php',
            'sources/rss2.php',
            'sources/hooks/systems/rss/.htaccess',
            'sources_custom/hooks/systems/rss/.htaccess',
            'sources/hooks/systems/rss/index.html',
            'sources_custom/hooks/systems/rss/index.html',
            'sources/hooks/systems/non_active_urls/news_rss_cloud.php',
        );
    }

    /**
     * Get mapping between template names and the method of this class that can render a preview of them.
     *
     * @return array The mapping
     */
    public function tpl_previews()
    {
        return array(
            'templates/RSS_HEADER.tpl' => 'rss_header',
            'xml/RSS_ENTRY_COMMENTS.xml' => 'rss_wrapper',
            'xml/RSS_XSLT.xml' => 'rss_xslt',
            'xml/ATOM_XSLT.xml' => 'atom_xslt',
            'xml/OPML_XSLT.xml' => 'opml_xslt',
            'xml/OPML_WRAPPER.xml' => 'opml_wrapper',
            'xml/RSS_CLOUD.xml' => 'rss_wrapper',
            'xml/ATOM_ENTRY.xml' => 'atom_wrapper',
            'xml/ATOM_WRAPPER.xml' => 'atom_wrapper',
            'xml/RSS_WRAPPER.xml' => 'rss_wrapper',
            'xml/RSS_ENTRY.xml' => 'rss_wrapper',
            'xml/RSS_ABBR.xml' => 'rss_wrapper',
        );
    }

    /**
     * Get a preview(s) of a (group of) template(s), as a full standalone piece of HTML in Tempcode format.
     * Uses sources/lorem.php functions to place appropriate stock-text. Should not hard-code things, as the code is intended to be declaritive.
     * Assumptions: You can assume all Lang/CSS/JavaScript files in this addon have been pre-required.
     *
     * @return array Array of previews, each is Tempcode. Normally we have just one preview, but occasionally it is good to test templates are flexible (e.g. if they use IF_EMPTY, we can test with and without blank data).
     */
    public function tpl_preview__rss_wrapper()
    {
        $comments = do_lorem_template('RSS_ENTRY_COMMENTS', array('COMMENT_URL' => placeholder_url(), 'ID' => placeholder_id()), null, false, null, '.xml', 'xml');

        $content = do_lorem_template('RSS_ABBR', array(), null, false, null, '.xml', 'xml');
        $content->attach(do_lorem_template('RSS_ENTRY', array(
            'TITLE' => lorem_phrase(),
            'SUMMARY' => lorem_paragraph(),
            'VIEW_URL' => placeholder_url(),
            'AUTHOR' => lorem_word(),
            'CATEGORY' => lorem_word(),
            'IF_COMMENTS' => $comments,
            'DATE' => placeholder_date(),
        ), null, false, null, '.xml', 'xml'));

        $cloud = do_lorem_template('RSS_CLOUD', array(
            'TYPE' => 'news',
            'PORT' => '80',
            'LOCAL_BASE_URL' => placeholder_url(),
        ), null, false, null, '.xml', 'xml');

        return array(
            do_lorem_template('RSS_WRAPPER', array(
                'MODE' => 'rss',
                'MODE_NICE' => lorem_word(),
                'COPYRIGHT' => lorem_phrase(),
                'ABOUT' => lorem_paragraph(),
                'RSS_CLOUD' => $cloud,
                'LOGO_URL' => placeholder_image_url(),
                'DATE' => placeholder_date(),
                'CONTENT' => $content,
            ), null, false, null, '.xml', 'xml'),
        );
    }

    /**
     * Get a preview(s) of a (group of) template(s), as a full standalone piece of HTML in Tempcode format.
     * Uses sources/lorem.php functions to place appropriate stock-text. Should not hard-code things, as the code is intended to be declaritive.
     * Assumptions: You can assume all Lang/CSS/JavaScript files in this addon have been pre-required.
     *
     * @return array Array of previews, each is Tempcode. Normally we have just one preview, but occasionally it is good to test templates are flexible (e.g. if they use IF_EMPTY, we can test with and without blank data).
     */
    public function tpl_preview__atom_wrapper()
    {
        $content = do_lorem_template('ATOM_ENTRY', array(
            'TITLE' => lorem_phrase(),
            'VIEW_URL' => placeholder_url(),
            'DATE' => placeholder_date(),
            'EDIT_DATE' => placeholder_date(),
            'CATEGORY_RAW' => lorem_word(),
            'CATEGORY' => lorem_word(),
            'AUTHOR' => lorem_word(),
            'SUMMARY' => lorem_word(),
            'NEWS' => lorem_word(),
        ), null, false, null, '.xml', 'xml');

        return array(
            do_lorem_template('ATOM_WRAPPER', array(
                'MODE' => lorem_word(),
                'MODE_NICE' => lorem_word(),
                'SELECT' => lorem_word_2(),
                'DATE' => placeholder_date(),
                'LOGO_URL' => placeholder_image_url(),
                'CONTENT' => $content,
                'CUTOFF' => placeholder_number(),
                'ABOUT' => lorem_paragraph(),
                'VERSION' => lorem_word(),
                'COPYRIGHT' => lorem_phrase(),
            ), null, false, null, '.xml', 'xml'),
        );
    }

    /**
     * Get a preview(s) of a (group of) template(s), as a full standalone piece of HTML in Tempcode format.
     * Uses sources/lorem.php functions to place appropriate stock-text. Should not hard-code things, as the code is intended to be declaritive.
     * Assumptions: You can assume all Lang/CSS/JavaScript files in this addon have been pre-required.
     *
     * @return array Array of previews, each is Tempcode. Normally we have just one preview, but occasionally it is good to test templates are flexible (e.g. if they use IF_EMPTY, we can test with and without blank data).
     */
    public function tpl_preview__rss_xslt()
    {
        require_lang('rss');

        return array(
            do_lorem_template('RSS_XSLT', array(
                'JAVASCRIPT_XSL_MOPUP' => '',
            ), null, false, null, '.xml', 'xml'),
        );
    }

    /**
     * Get a preview(s) of a (group of) template(s), as a full standalone piece of HTML in Tempcode format.
     * Uses sources/lorem.php functions to place appropriate stock-text. Should not hard-code things, as the code is intended to be declaritive.
     * Assumptions: You can assume all Lang/CSS/JavaScript files in this addon have been pre-required.
     *
     * @return array Array of previews, each is Tempcode. Normally we have just one preview, but occasionally it is good to test templates are flexible (e.g. if they use IF_EMPTY, we can test with and without blank data).
     */
    public function tpl_preview__atom_xslt()
    {
        require_lang('rss');

        return array(
            do_lorem_template('ATOM_XSLT', array(
                'JAVASCRIPT_XSL_MOPUP' => '',
            ), null, false, null, '.xml', 'xml'),
        );
    }

    /**
     * Get a preview(s) of a (group of) template(s), as a full standalone piece of HTML in Tempcode format.
     * Uses sources/lorem.php functions to place appropriate stock-text. Should not hard-code things, as the code is intended to be declaritive.
     * Assumptions: You can assume all Lang/CSS/JavaScript files in this addon have been pre-required.
     *
     * @return array Array of previews, each is Tempcode. Normally we have just one preview, but occasionally it is good to test templates are flexible (e.g. if they use IF_EMPTY, we can test with and without blank data).
     */
    public function tpl_preview__opml_xslt()
    {
        require_lang('rss');

        return array(
            do_lorem_template('OPML_XSLT', array(
                'JAVASCRIPT_XSL_MOPUP' => '',
            ), null, false, null, '.xml', 'xml'),
        );
    }

    /**
     * Get a preview(s) of a (group of) template(s), as a full standalone piece of HTML in Tempcode format.
     * Uses sources/lorem.php functions to place appropriate stock-text. Should not hard-code things, as the code is intended to be declaritive.
     * Assumptions: You can assume all Lang/CSS/JavaScript files in this addon have been pre-required.
     *
     * @return array Array of previews, each is Tempcode. Normally we have just one preview, but occasionally it is good to test templates are flexible (e.g. if they use IF_EMPTY, we can test with and without blank data).
     */
    public function tpl_preview__opml_wrapper()
    {
        require_lang('rss');

        return array(
            do_lorem_template('OPML_WRAPPER', array(
                'FEEDS' => placeholder_array(),
                'ABOUT' => lorem_phrase(),
                'DATE' => placeholder_date(),
                'TITLE' => lorem_phrase(),
                'MODE' => lorem_word(),
            ), null, false, null, '.xml', 'xml'),
        );
    }

    /**
     * Get a preview(s) of a (group of) template(s), as a full standalone piece of HTML in Tempcode format.
     * Uses sources/lorem.php functions to place appropriate stock-text. Should not hard-code things, as the code is intended to be declaritive.
     * Assumptions: You can assume all Lang/CSS/JavaScript files in this addon have been pre-required.
     *
     * @return array Array of previews, each is Tempcode. Normally we have just one preview, but occasionally it is good to test templates are flexible (e.g. if they use IF_EMPTY, we can test with and without blank data).
     */
    public function tpl_preview__rss_header()
    {
        return array(
            do_lorem_template('RSS_HEADER', array(
                'FEED_URL' => placeholder_url(),
            )),
        );
    }
}
