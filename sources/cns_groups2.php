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
 * @package    core_cns
 */

/**
 * Get a count of members in a (or more full details if $non_validated is true).
 *
 * @param  GROUP $group_id The ID of the group.
 * @param  boolean $include_primaries Whether to include those in the as a primary member.
 * @param  boolean $non_validated Whether to include those applied to join the, but not validated in.
 * @param  boolean $include_secondaries Whether to include those in the as a secondary member.
 * @param  boolean $include_unvalidated_members Whether to include those members who are not validated as site members at all yet (parameter currently ignored).
 * @return integer The count.
 */
function cns_get_group_members_raw_count($group_id, $include_primaries = true, $non_validated = false, $include_secondaries = true, $include_unvalidated_members = true)
{
    // Find for conventional members
    $where = array('gm_group_id' => $group_id);
    if (!$non_validated) {
        $where['gm_validated'] = 1;
    }
    $a = $GLOBALS['FORUM_DB']->query_select_value('f_group_members', 'COUNT(*)', $where);
    if ($include_primaries) {
        $map = array('m_primary_group' => $group_id);
        if (!$include_unvalidated_members) {
            //$map['m_validated_confirm_code']=''; Actually we don't want to consider this here
            $map['m_validated'] = 1;
        }
        $b = $GLOBALS['FORUM_DB']->query_select_value('f_members', 'COUNT(*)', $map);
    } else {
        $b = 0;
    }

    // Now implicit usergroup hooks
    if ($include_secondaries) {
        $hooks = find_all_hooks('systems', 'cns_implicit_usergroups');
        foreach (array_keys($hooks) as $hook) {
            require_code('hooks/systems/cns_implicit_usergroups/' . $hook);
            $ob = object_factory('Hook_implicit_usergroups_' . $hook);
            if (in_array($group_id, $ob->get_bound_group_ids())) {
                $c = $ob->get_member_list_count($group_id);
                if (!is_null($c)) {
                    $a += $c;
                }
            }
        }
    }

    // Find for LDAP members
    global $LDAP_CONNECTION;
    if (!is_null($LDAP_CONNECTION)) {
        $members = array();
        cns_get_group_members_raw_ldap($members, $group_id, $include_primaries, $non_validated, $include_secondaries);
        $c = count($members);
    } else {
        $c = 0;
    }

    // Now for probation
    $d = 0;
    if ($include_secondaries) {
        global $PROBATION_GROUP_CACHE;
        if (is_null($PROBATION_GROUP_CACHE)) {
            $probation_group = get_option('probation_usergroup');
            $PROBATION_GROUP_CACHE = $GLOBALS['FORUM_DB']->query_select_value_if_there('f_groups', 'id', array($GLOBALS['FORUM_DB']->translate_field_ref('g_name') => $probation_group));
            if (is_null($PROBATION_GROUP_CACHE)) {
                $PROBATION_GROUP_CACHE = false;
            }
        }
        if ($PROBATION_GROUP_CACHE === $group_id) {
            $d = $GLOBALS['FORUM_DB']->query_value_if_there('SELECT COUNT(*) FROM ' . $GLOBALS['FORUM_DB']->get_table_prefix() . 'f_members WHERE m_on_probation_until>' . strval(time()));
        }
    }

    return $a + $b + $c + $d;
}

/**
 * Get a list of members in a (or more full details if $non_validated is true).
 *
 * @param  GROUP $group_id The ID of the group.
 * @param  boolean $include_primaries Whether to include those in the as a primary member.
 * @param  boolean $non_validated Whether to include those applied to join the, but not validated in (also causes it to return maps that contain this info).
 * @param  boolean $include_secondaries Whether to include those in the as a secondary member.
 * @param  boolean $include_unvalidated_members Whether to include those members who are not validated as site members at all yet (parameter currently ignored).
 * @param  ?integer $max Return up to this many entries for primary members and this many entries for secondary members and all LDAP members (null: no limit, only use no limit if querying very restricted usergroups!)
 * @param  integer $start Return primary members after this offset and secondary members after this offset
 * @return array The list.
 */
function cns_get_group_members_raw($group_id, $include_primaries = true, $non_validated = false, $include_secondaries = true, $include_unvalidated_members = true, $max = null, $start = 0)
{
    // Find for conventional members
    $where = array('gm_group_id' => $group_id);
    if (!$non_validated) {
        $where['gm_validated'] = 1;
    }
    $_members = $GLOBALS['FORUM_DB']->query_select('f_group_members', array('gm_member_id', 'gm_validated'), $where, 'ORDER BY gm_member_id', $max, $start);
    $members = array();
    if ($include_secondaries) {
        foreach ($_members as $member) {
            $members[$member['gm_member_id']] = $non_validated ? ($member + array('implicit' => false)) : $member['gm_member_id'];
        }
    }
    if ($include_primaries) {
        $map = array('m_primary_group' => $group_id);
        if (!$include_unvalidated_members) {
            //$map['m_validated_confirm_code']=''; Actually we don't want to consider this here
            $map['m_validated'] = 1;
        }
        $_members2 = $GLOBALS['FORUM_DB']->query_select('f_members', array('id', 'm_username'), $map, '', $max, $start);
        foreach ($_members2 as $member) {
            $members[$member['id']] = $non_validated ? array('gm_member_id' => $member['id'], 'gm_validated' => 1, 'm_username' => $member['m_username'], 'implicit' => false) : $member['id'];
        }
    }

    // Now implicit usergroup hooks
    if ($include_secondaries) {
        $hooks = find_all_hooks('systems', 'cns_implicit_usergroups');
        foreach (array_keys($hooks) as $hook) {
            require_code('hooks/systems/cns_implicit_usergroups/' . $hook);
            $ob = object_factory('Hook_implicit_usergroups_' . $hook);
            if (in_array($group_id, $ob->get_bound_group_ids())) {
                $c = $ob->get_member_list($group_id);
                if (!is_null($c)) {
                    foreach ($c as $member_id => $member_row) {
                        $members[$member_id] = $non_validated ? array('gm_member_id' => $member_id, 'gm_validated' => 1, 'm_username' => $member_row['m_username'], 'implicit' => true) : $member_id;
                    }
                }
            }
        }
    }

    // Find for LDAP members
    global $LDAP_CONNECTION;
    if (!is_null($LDAP_CONNECTION)) {
        cns_get_group_members_raw_ldap($members, $group_id, $include_primaries, $non_validated, $include_secondaries);
    }

    // Now for probation
    if ($include_secondaries) {
        global $PROBATION_GROUP_CACHE;
        if (is_null($PROBATION_GROUP_CACHE)) {
            $probation_group = get_option('probation_usergroup');
            $PROBATION_GROUP_CACHE = $GLOBALS['FORUM_DB']->query_select_value_if_there('f_groups', 'id', array($GLOBALS['FORUM_DB']->translate_field_ref('g_name') => $probation_group));
            if (is_null($PROBATION_GROUP_CACHE)) {
                $PROBATION_GROUP_CACHE = false;
            }
        }
        if ($PROBATION_GROUP_CACHE === $group_id) {
            $d = $GLOBALS['FORUM_DB']->query('SELECT id,m_username FROM ' . $GLOBALS['FORUM_DB']->get_table_prefix() . 'f_members WHERE m_on_probation_until>' . strval(time()), $max);
            foreach ($d as $member_row) {
                $member_id = $member_row['id'];
                $members[] = $non_validated ? array('gm_member_id' => $member_id, 'gm_validated' => 1, 'm_username' => $member_row['m_username']) : $member_id;
            }
        }
    }

    return array_values($members);
}
