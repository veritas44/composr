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
 * @package    core
 */

/**
 * Syndicate human-intended descriptions of activities performed to the internal wall, and external listeners.
 *
 * @param  string $a_language_string_code Language string ID
 * @param  string $a_label_1 Label 1 (given as a parameter to the language string ID)
 * @param  string $a_label_2 Label 2 (given as a parameter to the language string ID)
 * @param  string $a_label_3 Label 3 (given as a parameter to the language string ID)
 * @param  string $a_page_link_1 Page-link 1
 * @param  string $a_page_link_2 Page-link 2
 * @param  string $a_page_link_3 Page-link 3
 * @param  string $a_addon Addon that caused the event
 * @param  BINARY $a_is_public Whether this post should be public or friends-only
 * @param  ?MEMBER $a_member_id Member being written for (null: current member)
 * @param  boolean $sitewide_too Whether to push this out as a site event if user requested
 * @param  ?MEMBER $a_also_involving Member also 'intimately' involved, such as a content submitter who is a friend (null: none)
 */
function syndicate_described_activity($a_language_string_code = '', $a_label_1 = '', $a_label_2 = '', $a_label_3 = '', $a_page_link_1 = '', $a_page_link_2 = '', $a_page_link_3 = '', $a_addon = '', $a_is_public = 1, $a_member_id = null, $sitewide_too = false, $a_also_involving = null)
{
    if (running_script('install')) {
        return;
    }
    $hooks = find_all_hook_obs('systems', 'activities', 'Hook_activities_');
    foreach ($hooks as $ob) { // We only expect only one actually
        if ((get_param_integer('keep_debug_notifications', 0) == 1) || (get_value('avoid_register_shutdown_function') === '1')) {
            $ob->syndicate_described_activity($a_language_string_code, $a_label_1, $a_label_2, $a_label_3, $a_page_link_1, $a_page_link_2, $a_page_link_3, $a_addon, $a_is_public, $a_member_id, $sitewide_too, $a_also_involving);
        } else {
            register_shutdown_function(array($ob, 'syndicate_described_activity'), $a_language_string_code, $a_label_1, $a_label_2, $a_label_3, $a_page_link_1, $a_page_link_2, $a_page_link_3, $a_addon, $a_is_public, $a_member_id, $sitewide_too, $a_also_involving);
        }
    }
}

/**
 * Detect whether we have external site-wide syndication support somewhere.
 *
 * @return boolean Whether we do
 */
function has_external_site_wide_syndication()
{
    $ret = false;
    $hooks = find_all_hook_obs('systems', 'activities', 'Hook_activities_');
    foreach ($hooks as $ob) { // We only expect only one actually
        $ret = $ret || $ob->has_external_site_wide_syndication();
    }
    return $ret;
}

/**
 * Get syndication field UI.
 *
 * @param  string $content_type The content type this is for
 * @return Tempcode Syndication fields (or empty)
 */
function get_syndication_option_fields($content_type)
{
    $ret = new Tempcode();
    $hooks = find_all_hook_obs('systems', 'activities', 'Hook_activities_');
    foreach ($hooks as $ob) { // We only expect only one actually
        $ret->attach($ob->get_syndication_option_fields($content_type));
    }
    return $ret;
}
