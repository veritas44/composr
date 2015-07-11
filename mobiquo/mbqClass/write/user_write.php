<?php /*

 Composr
 Copyright (c) ocProducts, 2004-2015

 See text/EN/licence.txt for full licencing information.

*/

/**
 * @license    http://opensource.org/licenses/cpal_1.0 Common Public Attribution License
 * @copyright  ocProducts Ltd
 * @package    cns_tapatalk
 */
class CMSUserWrite
{
    /**
     * Update current member's signature.
     *
     * @param  string            New signature
     * @return string New signature, after any filtering
     */
    function update_signature($signature)
    {
        cms_verify_parameters_phpdoc();

        if (is_guest()) {
            access_denied('NOT_AS_GUEST');
        }

        require_code('cns_members_action2');
        cns_member_choose_signature($signature, get_member());

        $_signature = $GLOBALS['FORUM_DRIVER']->get_member_row_field(get_member(), 'm_signature');
        return get_translated_text($_signature, $GLOBALS['FORUM_DB']);
    }

    /**
     * Make current member ignore another member
     *
     * @param  MEMBER            Member to ignore
     * @param  boolean        If the block is being added
     */
    function ignore_user($user_id, $adding)
    {
        cms_verify_parameters_phpdoc();

        if (is_guest()) {
            access_denied('NOT_AS_GUEST');
        }

        require_code('chat2');
        if ($adding) {
            blocking_add(get_member(), $user_id);
        } else {
            blocking_remove(get_member(), $user_id);
        }
    }
}
