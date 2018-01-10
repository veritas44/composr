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
 * @package    points
 */

/**
 * Hook class.
 */
class Hook_realtime_rain_points
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

        if (has_actual_page_access(get_member(), 'points')) {
            require_lang('points');

            $rows = $GLOBALS['SITE_DB']->query('SELECT reason,amount,date_and_time AS timestamp,member_id FROM ' . $GLOBALS['SITE_DB']->get_table_prefix() . 'chargelog WHERE date_and_time BETWEEN ' . strval($from) . ' AND ' . strval($to));

            foreach ($rows as $row) {
                $timestamp = $row['timestamp'];
                $member_id = $row['member_id'];

                $drops[] = rain_get_special_icons(null, $timestamp) + array(
                        'TYPE' => 'point_charges',
                        'FROM_MEMBER_ID' => strval($member_id),
                        'TO_MEMBER_ID' => null,
                        'TITLE' => do_lang('MEMBER_CHARGED_POINTS', integer_format($row['amount']), get_translated_text($row['reason'])),
                        'IMAGE' => $GLOBALS['FORUM_DRIVER']->get_member_avatar_url($member_id),
                        'TIMESTAMP' => strval($timestamp),
                        'RELATIVE_TIMESTAMP' => strval($timestamp - $from),
                        'TICKER_TEXT' => null,
                        'URL' => build_url(array('page' => 'points', 'type' => 'member', 'id' => $member_id), get_module_zone('points')),
                        'IS_POSITIVE' => false,
                        'IS_NEGATIVE' => true,

                        // These are for showing connections between drops. They are not discriminated, it's just three slots to give an ID code that may be seen as a commonality with other drops.
                        'FROM_ID' => 'member_' . strval($member_id),
                        'TO_ID' => false,
                        'GROUP_ID' => false,
                    );
            }

            $rows = $GLOBALS['SITE_DB']->query('SELECT reason,amount,gift_from AS member_id,gift_to,date_and_time AS timestamp,anonymous FROM ' . $GLOBALS['SITE_DB']->get_table_prefix() . 'gifts WHERE date_and_time BETWEEN ' . strval($from) . ' AND ' . strval($to));

            foreach ($rows as $row) {
                $timestamp = $row['timestamp'];
                $member_id = $row['member_id'];

                $drops[] = rain_get_special_icons(null, $timestamp) + array(
                        'TYPE' => 'point_gifts',
                        'FROM_MEMBER_ID' => ($row['anonymous'] == 1) ? null : strval($member_id),
                        'TO_MEMBER_ID' => strval($row['gift_to']),
                        'TITLE' => do_lang('MEMBER_GIVEN_POINTS', integer_format($row['amount']), get_translated_text($row['reason'])),
                        'IMAGE' => $GLOBALS['FORUM_DRIVER']->get_member_avatar_url($row['gift_to']),
                        'TIMESTAMP' => strval($timestamp),
                        'RELATIVE_TIMESTAMP' => strval($timestamp - $from),
                        'TICKER_TEXT' => null,
                        'URL' => build_url(array('page' => 'points', 'type' => 'member', 'id' => $row['gift_to']), get_module_zone('points')),
                        'IS_POSITIVE' => false,
                        'IS_NEGATIVE' => false,

                        // These are for showing connections between drops. They are not discriminated, it's just three slots to give an ID code that may be seen as a commonality with other drops.
                        'FROM_ID' => ($row['anonymous'] == 1) ? null : ('member_' . strval($member_id)),
                        'TO_ID' => 'member_' . strval($row['gift_to']),
                        'GROUP_ID' => null,
                    );
            }
        }

        return $drops;
    }
}
