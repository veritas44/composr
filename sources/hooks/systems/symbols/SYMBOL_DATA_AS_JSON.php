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
        global $ZONE;

        require_code('global2');
        require_code('symbols');

        $lang = user_lang();
        $value = array(
            'PAGE'              => ecv_PAGE($lang, [], []),
            'ZONE'              => ecv_ZONE($lang, [], []),
            'MEMBER'            => ecv_MEMBER($lang, [], []),
            'IS_GUEST'          => ecv_IS_GUEST($lang, [], []),
            'USERNAME'          => ecv_USERNAME($lang, [], []),
            'HIDE_HELP_PANEL'   => ecv_HIDE_HELP_PANEL($lang, [], []),
            'MOBILE'            => ecv_MOBILE($lang, [], []),
            'THEME'             => ecv_THEME($lang, [], []),
            'JS_ON'             => ecv_JS_ON($lang, [], []),
            'LANG'              => ecv_LANG($lang, [], []),
            'DEV_MODE'          => ecv_DEV_MODE($lang, [], []),
            'HTTP_STATUS_CODE'  => ecv_HTTP_STATUS_CODE($lang, [], []),
            'FORCE_PREVIEWS'    => ecv_FORCE_PREVIEWS($lang, [], []),
            'SITE_NAME'         => ecv_SITE_NAME($lang, [], []),
            'BRAND_NAME'        => ecv_BRAND_NAME($lang, [], []),
            'IS_STAFF'          => ecv_IS_STAFF($lang, [], []),
            'IS_ADMIN'          => ecv_IS_ADMIN($lang, [], []),
            'IS_HTTPAUTH_LOGIN' => ecv_IS_HTTPAUTH_LOGIN($lang, [], []),
            'IS_A_COOKIE_LOGIN' => ecv_IS_A_COOKIE_LOGIN($lang, [], []),
            'INLINE_STATS'      => ecv_INLINE_STATS($lang, [], []),
            'CSP_NONCE'         => ecv_CSP_NONCE($lang, [], []),
            'RUNNING_SCRIPT'    => current_script(),
        );

        require_code('urls');

        $value['page_type'] = get_param_string('type', '', INPUT_FILTER_GET_COMPLEX);
        $value['zone_default_page'] = ($ZONE !== null) ? $ZONE['zone_default_page'] : '';
        $value['sees_javascript_error_alerts'] = has_privilege(get_member(), 'sees_javascript_error_alerts');
        $value['can_try_url_schemes'] = can_try_url_schemes();

        return json_encode($value, JSON_UNESCAPED_SLASHES | JSON_NUMERIC_CHECK);
    }
}
