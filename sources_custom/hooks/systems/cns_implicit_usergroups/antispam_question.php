<?php /*

 Composr
 Copyright (c) ocProducts, 2004-2016

 See text/EN/licence.txt for full licencing information.

*/

/**
 * @license    http://opensource.org/licenses/cpal_1.0 Common Public Attribution License
 * @copyright  ocProducts Ltd
 * @package    antispam_question
 */

/**
 * Hook class.
 */
class Hook_implicit_usergroups_antispam_question
{
    /*
    Add a CPF something like...

    Name = What is Composr a kind of?
    Description = This question is designed to reduce the number of spammers we have joining the site. It indicates you have at least some interest in what you're signing up to.
    Default value = Commercial Maintenance System|Commercial Management Solution|Contacts Maintenance System|Contacts Management Solution|Content Maintenance Solution|Content Management System
    Owner viewable = no
    Owner settable = no
    Publicly viewable = no
    Type = A value chosen from a list
    Field options = widget=radio
    Required field = yes
    Show on the join form = yes
    */

    protected $field_id = 46;
    protected $expected_answer = 'Content Management System';

    /**
     * Run function for implicit usergroup hooks. Finds the group IDs it is bound to.
     *
     * @return array A list of usergroup IDs.
     */
    public function get_bound_group_ids()
    {
        return array(db_get_first_id() + 9); // Probation on standard install; customise as required
    }

    protected function _where()
    {
        require_code('cns_members');
        $mappings = cns_get_custom_field_mappings($GLOBALS['FORUM_DRIVER']->get_guest_id());
        $f = 'field_' . strval($this->field_id);
        if (!isset($mappings[$f])) {
            // So it does not crash if the CPF does not exist
            return '1=0';
        }

        return '(SELECT field_' . strval($this->field_id) . ' FROM ' . $GLOBALS['FORUM_DB']->get_table_prefix() . 'f_member_custom_fields mcf WHERE mcf.mf_member_id=id) NOT IN (\'\', \'' . db_escape_string($this->expected_answer) . '\')';
    }

    /**
     * Run function for implicit usergroup hooks. Finds all members in the group.
     *
     * @param  GROUP $group_id The group ID to check (if only one group supported by the hook, can be ignored).
     * @return ?array The list of members as a map between member ID and member row (null: unsupported by hook).
     */
    public function get_member_list($group_id)
    {
        return list_to_map('id', $GLOBALS['FORUM_DB']->query('SELECT * FROM ' . $GLOBALS['FORUM_DB']->get_table_prefix() . 'f_members WHERE ' . $this->_where()));
    }

    /**
     * Run function for implicit usergroup hooks. Finds all members in the group.
     *
     * @param  GROUP $group_id The group ID to check (if only one group supported by the hook, can be ignored).
     * @return ?array The list of members (null: unsupported by hook).
     */
    public function get_member_list_count($group_id)
    {
        return $GLOBALS['FORUM_DB']->query_value_if_there('SELECT COUNT(*) FROM ' . $GLOBALS['FORUM_DB']->get_table_prefix() . 'f_members WHERE ' . $this->_where());
    }

    /**
     * Run function for implicit usergroup hooks. Finds whether the member is within the implicit usergroup.
     *
     * @param  MEMBER $member_id The member ID.
     * @param  GROUP $group_id The group ID to check (if only one group supported by the hook, can be ignored).
     * @param  ?boolean $is_exclusive Return-by-reference if the member should *only* be in this usergroup (null: initially unset).
     * @return boolean Whether they are.
     */
    public function is_member_within($member_id, $group_id, &$is_exclusive = null)
    {
        $is_exclusive = true;

        require_code('cns_members');
        $mappings = cns_get_custom_field_mappings($member_id);
        $f = 'field_' . strval($this->field_id);
        if (!isset($mappings[$f])) {
            return false;
        }
        $val = $mappings[$f];
        return ($val != $this->expected_answer) && ($val != '');
    }
}
