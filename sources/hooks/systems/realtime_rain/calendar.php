<?php /*

 Composr
 Copyright (c) ocProducts, 2004-2017

 See text/EN/licence.txt for full licencing information.


 NOTE TO PROGRAMMERS:
   Do not edit this file. If you need to make changes, save your changed file to the appropriate *_custom folder
   **** If you ignore this advice, then your website upgrades (e.g. for bug fixes) will likely kill your changes ****

*/

/**
 * @license    http://opensource.org/licenses/cpal_1.0 Common Public Attribution License
 * @copyright  ocProducts Ltd
 * @package    calendar
 */

/**
 * Hook class.
 */
class Hook_realtime_rain_calendar
{
    /**
     * Run function for realtime-rain hooks.
     *
     * @param  TIME $from Start of time range
     * @param  TIME $to End of time range
     * @return array A list of template parameter sets for rendering a 'drop'
     */
    public function run($from, $to)
    {
        $drops = array();

        if (has_actual_page_access(get_member(), 'calendar')) {
            require_code('calendar');

            $rows = calendar_matches(get_member(), get_member(), !has_privilege(get_member(), 'assume_any_member'), $from, $to); // NOTE: We also show (automatically) any RSS items the user has overlayed onto the calendar

            foreach ($rows as $row) {
                $timestamp = $row[2];
                $member_id = $row[1]['e_submitter'];

                if ($timestamp[2] < $timestamp) {
                    continue;
                }

                $drops[] = rain_get_special_icons(null, $timestamp) + array(
                        'TYPE' => 'calendar',
                        'FROM_MEMBER_ID' => strval($member_id),
                        'TO_MEMBER_ID' => null,
                        'TITLE' => rain_truncate_for_title(get_translated_text($row[1]['e_title'])),
                        'IMAGE' => $row[1]['t_logo'],
                        'TIMESTAMP' => strval($timestamp),
                        'RELATIVE_TIMESTAMP' => strval($timestamp - $from),
                        'TICKER_TEXT' => null,
                        'URL' => build_url(array('page' => 'calendar', 'type' => 'event', 'id' => $row[1]['id']), get_module_zone('calendar')),
                        'IS_POSITIVE' => false,
                        'IS_NEGATIVE' => false,

                        // These are for showing connections between drops. They are not discriminated, it's just three slots to give an ID code that may be seen as a commonality with other drops.
                        'FROM_ID' => 'member_' . strval($member_id),
                        'TO_ID' => null,
                        'GROUP_ID' => 'event_' . strval($row[1]['id']),
                    );
            }
        }

        return $drops;
    }
}
