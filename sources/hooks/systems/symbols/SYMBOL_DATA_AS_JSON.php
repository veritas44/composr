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
 * @package    core
 */

/**
 * Hook class.
 */
class Hook_symbol_SYMBOL_DATA_AS_JSON
{
    /**
     * Run function for symbol hooks. Searches for tasks to perform.
     *
     * @param  array $param Symbol parameters
     * @return string Result
     */
    public function run($param)
    {
        require_code('symbols');
        require_code('symbols2');

        $lang = user_lang();
        $value = array(
            'PAGE_TITLE'          => ecv_PAGE_TITLE($lang, [], []),
            'MEMBER'              => ecv_MEMBER($lang, [], []),
            'IS_GUEST'            => ecv_IS_GUEST($lang, [], []) === '1',
            'USERNAME'            => ecv_USERNAME($lang, [], []),
            'AVATAR'              => ecv_AVATAR($lang, [], []),
            'MEMBER_EMAIL'        => ecv_MEMBER_EMAIL($lang, [], []),
            'PHOTO'               => ecv_PHOTO($lang, [], []),
            'MEMBER_PROFILE_URL'  => ecv_MEMBER_PROFILE_URL($lang, [], []),
            'DATE_AND_TIME'       => ecv_DATE_TIME($lang, [], []),
            'DATE'                => ecv_DATE($lang, [], []),
            'TIME'                => ecv_TIME($lang, [], []),
            'FROM_TIMESTAMP'      => ecv_FROM_TIMESTAMP($lang, [], []),
            'MOBILE'              => ecv2_MOBILE($lang, [], []),
            'THEME'               => ecv2_THEME($lang, [], []),
            'JS_ON'               => ecv_JS_ON($lang, [], []),
            'LANG'                => ecv2_LANG($lang, [], []),
            'BROWSER_UA'          => ecv2_BROWSER_UA($lang, [], []),
            'OS'                  => ecv2_OS($lang, [], []),
            'DEV_MODE'            => ecv_DEV_MODE($lang, [], []) === '1',
            'USER_AGENT'          => ecv2_USER_AGENT($lang, [], []),
            'IP_ADDRESS'          => ecv2_IP_ADDRESS($lang, [], []),
            'TIMEZONE'            => ecv2_TIMEZONE($lang, [], []),
            'HTTP_STATUS_CODE'    => ecv2_HTTP_STATUS_CODE($lang, [], []),
            'CHARSET'             => ecv2_CHARSET($lang, [], []),
            'KEEP'                => ecv_KEEP($lang, [], []),
            'SITE_NAME'           => ecv2_SITE_NAME($lang, [], []),
            'COPYRIGHT'           => ecv2_COPYRIGHT($lang, [], []),
            'DOMAIN'              => ecv2_DOMAIN($lang, [], []),
            'FORUM_BASE_URL'      => ecv2_FORUM_BASE_URL($lang, [], []),
            'BASE_URL'            => ecv2_BASE_URL($lang, [], []),
            'BRAND_NAME'          => ecv2_BRAND_NAME($lang, [], []),
            'IS_STAFF'            => ecv_IS_STAFF($lang, [], []) === '1',
            'IS_ADMIN'            => ecv_IS_ADMIN($lang, [], []) === '1',
            'VERSION'             => ecv2_VERSION($lang, [], []),
            'COOKIE_PATH'         => ecv2_COOKIE_PATH($lang, [], []),
            'COOKIE_DOMAIN'       => ecv2_COOKIE_DOMAIN($lang, [], []),
            'IS_HTTPAUTH_LOGIN'   => ecv_IS_HTTPAUTH_LOGIN($lang, [], []) === '1',
            'IS_A_COOKIE_LOGIN'   => ecv2_IS_A_COOKIE_LOGIN($lang, [], []) === '1',
            'SESSION_COOKIE_NAME' => ecv2_SESSION_COOKIE_NAME($lang, [], []),
            'GROUP_ID'            => ecv2_GROUP_ID($lang, [], []),
        );

        require_code('config');
        $value['CONFIG_OPTION'] = [
            'thumbWidth'        => get_option('thumb_width'),
            'jsOverlays'        => get_option('js_overlays'),
            'jsCaptcha'         => get_option('js_captcha'),
            'googleAnalytics'   => get_option('google_analytics'),
            'longGoogleCookies' => get_option('long_google_cookies'),
        ];

        require_code('urls');
        $value['EXTRA'] = [
            'canTryUrlSchemes' => can_try_url_schemes()
        ];

        return json_encode($value);
    }
}
