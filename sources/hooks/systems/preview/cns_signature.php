<?php /*

 Composr
 Copyright (c) ocProducts, 2004-2018

 See text/EN/licence.txt for full licensing information.


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
class Hook_preview_cns_signature
{
    /**
     * Find whether this preview hook applies.
     *
     * @return array Quartet: Whether it applies, the attachment ID type (may be null), whether the forum DB is used [optional], list of fields to limit to [optional]
     */
    public function applies()
    {
        require_lang('cns');

        $member_id = get_param_integer('id', get_member());

        $applies = (addon_installed('cns_signatures')) && (get_page_name() == 'members') && (post_param_string('signature', null) !== null);
        if ($applies) {
            require_code('cns_groups');
            $max_sig_length = cns_get_member_best_group_property($member_id, 'max_sig_length_comcode');
            if (strlen(post_param_string('post', '')) > $max_sig_length) {
                warn_exit(do_lang_tempcode('SIGNATURE_TOO_BIG'));
            }
        }
        return array($applies, 'cns_signature', true, array('post'));
    }
}
