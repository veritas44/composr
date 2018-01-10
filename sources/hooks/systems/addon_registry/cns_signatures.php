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
 * @package    cns_signatures
 */

/**
 * Hook class.
 */
class Hook_addon_registry_cns_signatures
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
        return 'Member signatures.';
    }

    /**
     * Get a list of tutorials that apply to this addon.
     *
     * @return array List of tutorials
     */
    public function get_applicable_tutorials()
    {
        return array(
            'tut_members',
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
            'requires' => array(),
            'recommends' => array(),
            'conflicts_with' => array(),
        );
    }

    /**
     * Explicitly say which icon should be used.
     *
     * @return URLPATH Icon
     */
    public function get_default_icon()
    {
        return 'themes/default/images/icons/48x48/tabs/member_account/edit/signature.png';
    }

    /**
     * Get a list of files that belong to this addon.
     *
     * @return array List of files
     */
    public function get_file_list()
    {
        return array(
            'themes/default/images/icons/24x24/tabs/member_account/edit/signature.png',
            'themes/default/images/icons/48x48/tabs/member_account/edit/signature.png',
            'sources/hooks/systems/addon_registry/cns_signatures.php',
            'themes/default/templates/CNS_EDIT_SIGNATURE_TAB.tpl',
            'sources/hooks/systems/attachments/cns_signature.php',
            'sources/hooks/systems/preview/cns_signature.php',
            'sources/hooks/systems/profiles_tabs_edit/signature.php',
            'sources/hooks/systems/notifications/cns_choose_signature.php',
            'sources/hooks/systems/config/enable_skip_sig.php',
            'sources/hooks/systems/config/enable_views_sigs_option.php',
            'themes/default/javascript/cns_signatures.js',
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
            'templates/CNS_EDIT_SIGNATURE_TAB.tpl' => 'cns_edit_signature_tab',
        );
    }

    /**
     * Get a preview(s) of a (group of) template(s), as a full standalone piece of HTML in Tempcode format.
     * Uses sources/lorem.php functions to place appropriate stock-text. Should not hard-code things, as the code is intended to be declaritive.
     * Assumptions: You can assume all Lang/CSS/JavaScript files in this addon have been pre-required.
     *
     * @return array Array of previews, each is Tempcode. Normally we have just one preview, but occasionally it is good to test templates are flexible (e.g. if they use IF_EMPTY, we can test with and without blank data).
     */
    public function tpl_preview__cns_edit_signature_tab()
    {
        require_javascript('plupload');
        require_javascript('checking');
        require_javascript('posting');
        require_lang('comcode');
        require_lang('cns');
        require_css('cns');

        $buttons = new Tempcode();
        $_buttons = array(
            'img',
            'thumb',
            'url',
            'page',
            'code',
            'quote',
            'hide',
            'box',
            'block',
            'list',
            'html'
        );
        foreach ($_buttons as $button) {
            $buttons->attach(do_lorem_template('COMCODE_EDITOR_BUTTON', array(
                'DIVIDER' => true,
                'FIELD_NAME' => lorem_word(),
                'TITLE' => lorem_phrase(),
                'B' => $button,
                'IS_POSTING_FIELD' => false,
            )));
        }

        $micro_buttons = new Tempcode();
        $_micro_buttons = array(
            array(
                't' => 'b',
            ),
            array(
                't' => 'i',
            )
        );

        foreach ($_micro_buttons as $button) {
            $micro_buttons->attach(do_lorem_template('COMCODE_EDITOR_MICRO_BUTTON', array(
                'FIELD_NAME' => lorem_word(),
                'TITLE' => lorem_phrase(),
                'B' => $button['t'],
                'IS_POSTING_FIELD' => false,
            )));
        }

        $comcode_editor = do_lorem_template('COMCODE_EDITOR', array(
            'POSTING_FIELD' => lorem_word(),
            'BUTTONS' => $buttons,
            'MICRO_BUTTONS' => $micro_buttons,
            'IS_POSTING_FIELD' => false,
        ));

        $posting_form = do_lorem_template('POSTING_FORM', array(
            'TABINDEX_PF' => placeholder_number() /*not called TABINDEX due to conflict with FORM_STANDARD_END*/,
            'PREVIEW' => true,
            'COMCODE_EDITOR' => $comcode_editor,
            'COMCODE_EDITOR_SMALL' => $comcode_editor,
            'CLASS' => lorem_word(),
            'COMCODE_URL' => placeholder_url(),
            'EXTRA' => '',
            'POST_COMMENT' => lorem_phrase(),
            'EMOTICON_CHOOSER' => '',
            'SUBMIT_ICON' => 'buttons--save',
            'SUBMIT_NAME' => lorem_word(),
            'HIDDEN_FIELDS' => new Tempcode(),
            'URL' => placeholder_url(),
            'POST' => lorem_sentence(),
            'DEFAULT_PARSED' => lorem_sentence(),
            'CONTINUE_URL' => placeholder_url(),
            'ATTACHMENTS' => lorem_phrase(),
            'SPECIALISATION' => new Tempcode(),
            'SPECIALISATION2' => new Tempcode(),
            'DESCRIPTION' => lorem_paragraph(),
            'REQUIRED' => true,
        ));

        return array(
            lorem_globalise(do_lorem_template('CNS_EDIT_SIGNATURE_TAB', array(
                'SIZE' => placeholder_filesize(),
                'SIGNATURE' => lorem_phrase(),
            )), null, '', true)
        );
    }
}
