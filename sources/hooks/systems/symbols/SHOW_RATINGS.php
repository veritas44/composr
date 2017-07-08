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
 * @package    core_feedback_features
 */

/**
 * Hook class.
 */
class Hook_symbol_SHOW_RATINGS
{
    /**
     * Run function for symbol hooks. Searches for tasks to perform.
     *
     * @param  array $param Symbol parameters
     * @return string Result
     */
    public function run($param)
    {
        $value = '';

        if (array_key_exists(1, $param)) {
            $rating_type = $param[0];
            $rating_id = $param[1];
            $max = array_key_exists(2, $param) ? intval($param[2]) : 30;

            $ratings = array();
            $_ratings = $GLOBALS['SITE_DB']->query_select('rating', array('rating_member', 'rating_ip', 'rating_time', 'rating'), array('rating_for_type' => $rating_type, 'rating_for_id' => $rating_id), 'ORDER BY rating_time DESC', $max);
            foreach ($_ratings as $rating) {
                $ratings[] = array(
                    'RATING_MEMBER' => strval($rating['rating_member']),
                    'RATING_USERNAME' => is_guest($rating['rating_member']) ? '' : $GLOBALS['FORUM_DRIVER']->get_username($rating['rating_member'], USERNAME_DEFAULT_BLANK),
                    'RATING_IP' => $rating['rating_ip'],
                    'RATING_TIME' => strval($rating['rating_time']),
                    'RATING_TIME_FORMATTED' => get_timezoned_date_time($rating['rating_time']),
                    'RATING' => strval($rating['rating']),
                );
            }
            if (count($_ratings) < $max) {
                $cnt = count($_ratings);
            } else {
                $cnt = $GLOBALS['SITE_DB']->query_select_value('rating', 'COUNT(*)', array('rating_for_type' => $rating_type, 'rating_for_id' => $rating_id));
            }

            $_value = do_template('RATINGS_SHOW', array(
                '_GUID' => 'fda94aa20508a071853e56e14c13fe3c',
                'RATINGS' => $ratings,
                'HAS_MORE' => $cnt > count($_ratings),
                'MAX' => strval($max),
                'CNT' => strval($cnt),
                'CNT_REMAINING' => strval($cnt - count($ratings)),
            ));
            $value = static_evaluate_tempcode($_value);
        }

        return $value;
    }
}
