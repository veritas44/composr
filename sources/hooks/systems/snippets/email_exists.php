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
 * @package    core_cns
 */

/**
 * Hook class.
 */
class Hook_snippet_email_exists
{
    /**
     * Run function for snippet hooks. Generates XHTML to insert into a page using AJAX.
     *
     * @return Tempcode The snippet
     */
    public function run()
    {
        $val = get_param_string('name');

        $test = $GLOBALS['FORUM_DB']->query_select_value_if_there('f_members', 'm_username', array('m_email_address' => $val));
        if (is_null($test)) {
            return new Tempcode();
        }

        require_lang('cns');

        return make_string_tempcode(strip_html(do_lang('EMAIL_ADDRESS_IN_USE', escape_html(get_site_name()))));
    }
}
