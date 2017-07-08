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
 * @package    banners
 */

/**
 * Hook class.
 */
class Hook_realtime_rain_banners
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

        if (has_actual_page_access(get_member(), 'admin_banners')) {
            $rows = $GLOBALS['SITE_DB']->query('SELECT b.name,img_url,c_ip_address,c_member_id AS member_id,c_date_and_time AS timestamp FROM ' . $GLOBALS['SITE_DB']->get_table_prefix() . 'banner_clicks c LEFT JOIN ' . $GLOBALS['SITE_DB']->get_table_prefix() . 'banners b ON b.name=c.c_banner_id WHERE c_date_and_time BETWEEN ' . strval($from) . ' AND ' . strval($to));

            require_lang('banners');

            foreach ($rows as $row) {
                $timestamp = $row['timestamp'];
                $member_id = $row['member_id'];

                $image = is_guest($member_id) ? rain_get_country_image($row['c_ip_address']) : $GLOBALS['FORUM_DRIVER']->get_member_avatar_url($member_id);
                require_code('images');
                if (is_image($row['img_url'], IMAGE_CRITERIA_WEBSAFE, has_privilege($row['member_id'], 'comcode_dangerous'))) {
                    $image = $row['img_url'];
                }
                if (url_is_local($image)) {
                    $image = get_custom_base_url() . '/' . $image;
                }

                $drops[] = rain_get_special_icons($row['c_ip_address'], $timestamp) + array(
                        'TYPE' => 'banners',
                        'FROM_MEMBER_ID' => strval($member_id),
                        'TO_MEMBER_ID' => null,
                        'TITLE' => do_lang('BANNER_CLICKED'),
                        'IMAGE' => $image,
                        'TIMESTAMP' => strval($timestamp),
                        'RELATIVE_TIMESTAMP' => strval($timestamp - $from),
                        'TICKER_TEXT' => null,
                        'URL' => null,
                        'IS_POSITIVE' => true,
                        'IS_NEGATIVE' => false,

                        // These are for showing connections between drops. They are not discriminated, it's just three slots to give an ID code that may be seen as a commonality with other drops.
                        'FROM_ID' => 'member_' . strval($member_id),
                        'TO_ID' => null,
                        'GROUP_ID' => 'banner_' . $row['name'],
                    );
            }
        }

        return $drops;
    }
}
