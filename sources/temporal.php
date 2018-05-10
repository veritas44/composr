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
 * Standard code module initialisation function.
 *
 * @ignore
 */
function init__temporal()
{
    global $TIMEZONE_MEMBER_CACHE;
    $TIMEZONE_MEMBER_CACHE = array();
}

/**
 * Display a time period of seconds in a tidy human-readable way.
 *
 * @param  integer $seconds Number of seconds
 * @return string Human-readable period.
 */
function display_seconds_period($seconds)
{
    $hours = intval(floor(floatval($seconds) / 60.0 / 60.0));
    $minutes = intval(floor(floatval($seconds) / 60.0)) - 60 * $hours;
    $seconds = $seconds - 60 * $minutes;

    $out = '';
    if ($hours != 0) {
        $out .= str_pad(strval($hours), 2, '0', STR_PAD_LEFT) . ':';
    }
    /*Expected if (($hours!=0) || ($minutes!=0)) */
    $out .= str_pad(strval($minutes), 2, '0', STR_PAD_LEFT) . ':';
    $out .= str_pad(strval($seconds), 2, '0', STR_PAD_LEFT);
    return $out;
}

/**
 * Display a time period in a tidy human-readable way.
 *
 * @param  integer $seconds Number of seconds
 * @return string Human-readable period.
 */
function display_time_period($seconds)
{
    if ($seconds < 0) {
        return '-' . display_time_period(-$seconds);
    }

    if (($seconds <= 3 * 60) && (($seconds % (60) != 0) || ($seconds == 0))) {
        return do_lang('SECONDS', integer_format($seconds));
    }
    if (($seconds <= 3 * 60 * 60) && ($seconds % (60 * 60) != 0)) {
        return do_lang('MINUTES', integer_format(intval(round(floatval($seconds) / 60.0))));
    }
    if (($seconds <= 3 * 60 * 60 * 24) && ($seconds % (60 * 60 * 24) != 0)) {
        return do_lang('HOURS', integer_format(intval(round(floatval($seconds) / 60.0 / 60.0))));
    }
    return do_lang('DAYS', integer_format(intval(round(floatval($seconds) / 60.0 / 60.0 / 24.0))));
}

/**
 * Set up the locale filter array from the terse language string specifying it.
 */
function make_locale_filter()
{
    global $LOCALE_FILTER_CACHE;
    $LOCALE_FILTER_CACHE = explode(',', trim(do_lang('locale_subst')));
    foreach ($LOCALE_FILTER_CACHE as $i => $filter) {
        if ($filter == '') {
            unset($LOCALE_FILTER_CACHE[$i]);
        } else {
            $LOCALE_FILTER_CACHE[$i] = explode('=', $filter);
        }
    }
}

/**
 * Get the timezone the server is configured with.
 *
 * @return string Server timezone in "boring" format.
 */
function get_server_timezone()
{
    global $SERVER_TIMEZONE_CACHE;
    if (is_string($SERVER_TIMEZONE_CACHE)) {
        if ($SERVER_TIMEZONE_CACHE != '') {
            return $SERVER_TIMEZONE_CACHE;
        }
    }

    return 'UTC';
}

/**
 * Get the timezone the site is running on.
 *
 * @return string Site timezone in "boring" format.
 */
function get_site_timezone()
{
    $_timezone_site = get_value('timezone');
    if ($_timezone_site === null) {
        $timezone_site = get_server_timezone();
    } else {
        $timezone_site = $_timezone_site;
    }
    return $timezone_site;
}

/**
 * Get a user's timezone.
 *
 * @param  ?MEMBER $member Member for which the date is being rendered (null: current user)
 * @return string Users timezone in "boring" format.
 */
function get_users_timezone($member = null)
{
    if ($member === null) {
        $member = get_member();
    }

    global $TIMEZONE_MEMBER_CACHE;
    if (isset($TIMEZONE_MEMBER_CACHE[$member])) {
        return $TIMEZONE_MEMBER_CACHE[$member];
    }

    $timezone = get_param_string('keep_timezone', null);
    if ($timezone !== null) {
        $TIMEZONE_MEMBER_CACHE[$member] = $timezone;
        return $timezone;
    }

    // Get user timezone
    if ((get_forum_type() == 'cns') && (!is_guest($member)) && (get_option('allow_international') !== '0')) {
        $timezone_member = $GLOBALS['FORUM_DRIVER']->get_member_row_field($member, 'm_timezone_offset');
    } elseif ((function_exists('cms_admirecookie')) && (get_option('is_on_timezone_detection') === '1') && (get_option('allow_international') !== '0')) {
        $client_time = cms_admirecookie('client_time');
        $client_time_ref = cms_admirecookie('client_time_ref');

        if (($client_time !== null) && ($client_time_ref !== null)) { // If the client-end has set a time cookie (only available on 2ND request) then we can auto-work-out the timezone
            $client_time = preg_replace('# ([A-Z]{3})([\+\-]\d+)?( \([\w\s]+\))?( \d{4})?$#', '${4}', $client_time);
            $timezone_dif = (floatval(strtotime($client_time)) - (floatval($client_time_ref))) / 60.0 / 60.0;

            $timezone_numeric = round($timezone_dif, 1);
            if (abs($timezone_numeric) > 100.0) {
                $timezone_numeric = 0.0;
            }
            $timezone_member = convert_timezone_offset_to_formal_timezone($timezone_numeric);
        } else {
            $timezone_member = get_site_timezone();
        }
    } else { // Ah, simple: site's default
        $timezone_member = get_site_timezone();
    }

    $TIMEZONE_MEMBER_CACHE[$member] = $timezone_member;

    return $timezone_member;
}

/**
 * Given a timezone offset, make it into a formal timezone.
 *
 * @param  float $offset Timezone offset.
 * @return string Users timezone in "boring" format.
 */
function convert_timezone_offset_to_formal_timezone($offset)
{
    $time_now = time();
    $expected = $time_now + intval(60 * 60 * $offset);

    $zones = get_timezone_list();
    foreach (array_keys($zones) as $zone) {
        $converted = tz_time($time_now, $zone);
        if ($converted == $expected) {
            if (tz_time($time_now, get_server_timezone()) == $converted) {
                return get_server_timezone(); // Prefer to set the site timezone if it is currently the same
            }
            return $zone;
        }
    }

    // Could not find one

    if (!is_numeric(get_value('timezone'))) {
        return get_site_timezone();
    }
    return get_server_timezone();
}

/**
 * Convert a UTC timestamp to a user timestamp. The user timestamp should not be pumped through get_timezoned_date as this already performs the conversions internally.
 * What complicate understanding of matters is that "user time" is not the timestamp that would exist on a user's PC, as all timestamps are meant to be stored in UTC. "user time" is offsetted to compensate, a virtual construct.
 *
 * @param  ?TIME $timestamp Input timestamp (null: now)
 * @param  ?MEMBER $member Member for which the date is being rendered (null: current member)
 * @return TIME Output timestamp
 */
function utctime_to_usertime($timestamp = null, $member = null)
{
    if ($timestamp === null) {
        $timestamp = time();
    }

    $timezone = get_users_timezone($member);

    return tz_time($timestamp, $timezone);
}

/**
 * Convert a user timestamp to a UTC timestamp. This is not a function to use much- you probably want utctime_to_usertime.
 * What complicate understanding of matters is that "user time" is not the timestamp that would exist on a user's PC, as all timestamps are meant to be stored in UTC. "user time" is offsetted to compensate, a virtual construct.
 *
 * @param  ?TIME $timestamp Input timestamp (null: now)
 * @param  ?MEMBER $member Member for which the date is being rendered (null: current member)
 * @return TIME Output timestamp
 */
function usertime_to_utctime($timestamp = null, $member = null)
{
    if ($timestamp === null) {
        $timestamp = time();
    }

    $timezone = get_users_timezone($member);

    $amount_forward = tz_time($timestamp, $timezone) - $timestamp;
    return $timestamp - $amount_forward;
}

/**
 * Format a local time/date according to locale settings. Combines best features of 'strftime' and 'date'.
 * %l is 'g' in date
 * %o is 'S' in date
 *
 * @param  string $format The formatting string.
 * @param  ?TIME $timestamp The timestamp (null: now). Assumed to already be timezone-shifted as required
 * @return string The formatted string.
 */
function cms_strftime($format, $timestamp = null)
{
    if ($timestamp === null) {
        $timestamp = time();
    }

    static $is_windows = null;
    if ($is_windows === null) {
        $is_windows = (stripos(PHP_OS, 'WIN') === 0);
    }
    if ($is_windows) {
        $format = str_replace('%e', '%#d', $format);
        $format = str_replace('%l', '%#I', $format);
    } elseif (PHP_OS == 'SunOS') {
        $format = str_replace('%e', '{{%e}}', $format);
        $format = str_replace('%l', '{{%l}}', $format);
    } else {
        $format = str_replace('%e', '%-d', $format);
        $format = str_replace('%l', '%-I', $format);
    }
    $format = str_replace('%o', date('S'/*English ordinal suffix for the day of the month, 2 characters*/, $timestamp), $format);
    $ret = @strftime($format, $timestamp);
    if ($ret === false) {
        $ret = '';
    }
    if (PHP_OS == 'SunOS') {
        $ret = preg_replace('#\{\{[ 0]?([^\{\}]+)\}\}#', '${1}', $ret);
    }
    return trim(locale_filter($ret)); // Needed as %e comes with a leading space
}

/**
 * Similar to get_timezoned_date, except works via Tempcode so is cache-safe for relative date display.
 *
 * @param  TIME $timestamp Input timestamp
 * @param  boolean $include_time Whether to include the time in the output
 * @return Tempcode Formatted time
 */
function get_timezoned_date_tempcode($timestamp, $include_time = true)
{
    if (!$include_time) {
        return symbol_tempcode('DATE', array('0', '0', '0', strval($timestamp)));
    }

    return symbol_tempcode('DATE_AND_TIME', array('0', '0', '0', strval($timestamp)));
}

/**
 * Get a nice formatted date from the specified Unix timestamp.
 *
 * @param  TIME $timestamp Input timestamp
 * @param  boolean $include_time Whether to include the time in the output
 * @param  boolean $verbose Whether to make this a verbose date (longer than usual)
 * @param  boolean $utc_time Whether to work in UTC time
 * @param  boolean $avoid_contextual_dates Whether contextual dates will be avoided
 * @param  ?MEMBER $member Member for which the date is being rendered (null: current member)
 * @return string Formatted time
 */
function get_timezoned_date($timestamp, $include_time = true, $verbose = false, $utc_time = false, $avoid_contextual_dates = false, $member = null)
{
    if ($member === null) {
        $member = get_member();
    }

    if (!$avoid_contextual_dates && gmdate('H:i', $timestamp) == '00:00') {
        $include_time = false; // Probably means no time is known
    }

    // Work out timezone
    $usered_timestamp = $utc_time ? $timestamp : utctime_to_usertime($timestamp, $member);
    $usered_now_timestamp = $utc_time ? time() : utctime_to_usertime(time(), $member);

    if ($usered_timestamp < 0) {
        if (@strftime('%Y', @mktime(0, 0, 0, 1, 1, 1963)) != '1963') {
            return 'pre-1970';
        }
    }

    // Render basic date
    $date_string1 = ($verbose) ? do_lang('date_verbose_date') : do_lang('date_regular_date'); // The date renderer string
    $joiner = ($verbose) ? do_lang('date_verbose_joiner') : do_lang('date_regular_joiner');
    $date_string2 = ($include_time) ? ($verbose ? do_lang('date_verbose_time') : do_lang('date_regular_time')) : ''; // The time renderer string
    $ret1 = cms_strftime($date_string1, $usered_timestamp);
    $ret2 = ($date_string2 == '') ? '' : cms_strftime($date_string2, $usered_timestamp);
    $ret = $ret1 . (($ret2 == '') ? '' : ($joiner . $ret2));

    // If we can do contextual dates, have our shot
    if (get_option('use_contextual_dates') == '0') {
        $avoid_contextual_dates = true;
    }
    if (!$avoid_contextual_dates) {
        $today = cms_strftime($date_string1, $usered_now_timestamp);

        if ($ret1 == $today) { // It is/was today
            $ret = /*Today is obvious do_lang('TODAY').$joiner.*/$ret2;
            if ($ret == '') {
                $ret = do_lang('TODAY'); // it'll be because avoid contextual dates is not on
            }
        } else {
            $yesterday = cms_strftime($date_string1, $usered_now_timestamp - 24 * 60 * 60);
            if ($ret1 == $yesterday) { // It is/was yesterday
                $ret = do_lang('YESTERDAY') . (($ret2 == '') ? '' : ($joiner . $ret2));
            } else {
                $week = cms_strftime('%U %Y', $usered_timestamp);
                $now_week = cms_strftime('%U %Y', $usered_now_timestamp);
                if ($week == $now_week) { // It is/was this week
                    $date_string1 = do_lang('date_withinweek_date'); // The date renderer string
                    $joiner = do_lang('date_withinweek_joiner');
                    $date_string2 = ($include_time) ? do_lang('date_regular_time') : ''; // The time renderer string
                    $ret1 = cms_strftime($date_string1, $usered_timestamp);
                    $ret2 = ($date_string2 == '') ? '' : cms_strftime($date_string2, $usered_timestamp);
                    $ret = $ret1 . (($ret2 == '') ? '' : ($joiner . $ret2));
                } // We could go on, and check for month, and year, but it would serve little value - probably would make the user think more than help.
            }
        }
    }

    return $ret;
}

/**
 * Filter locale-tainted strings through the locale filter.
 * Let's pretend a user's operating system doesn't fully support they're locale. They have a nice language pack, but whenever the O.S. is asked for dates in the chosen locale, it puts month names in English instead. The locale_filter function is used to cleanup these problems. It does a simple set of string replaces, as defined by the 'locale_subst' language string.
 *
 * @param  string $ret Tainted string
 * @return string Filtered string
 */
function locale_filter($ret)
{
    global $LOCALE_FILTER_CACHE;
    if ($LOCALE_FILTER_CACHE === null) {
        make_locale_filter();
    }
    foreach ($LOCALE_FILTER_CACHE as $filter) {
        if (count($filter) == 2) {
            $ret = str_replace($filter[0], $filter[1], $ret);
        }
    }
    return $ret;
}

/**
 * Get a nice formatted time from the specified Unix timestamp.
 *
 * @param  TIME $timestamp Input timestamp
 * @param  boolean $avoid_contextual_dates Whether contextual times will be avoided. Note that we don't currently use contextual (relative) times. This parameter may be used in the future.
 * @param  ?MEMBER $member Member for which the time is being rendered (null: current member)
 * @param  boolean $utc_time Whether to work in UTC time
 * @return string Formatted time
 */
function get_timezoned_time($timestamp, $avoid_contextual_dates = false, $member = null, $utc_time = false)
{
    if ($member === null) {
        $member = get_member();
    }

    if (get_option('use_contextual_dates') == '0') {
        $avoid_contextual_dates = true;
    }

    $date_string = do_lang('date_regular_time');
    $usered_timestamp = $utc_time ? $timestamp : utctime_to_usertime($timestamp, $member);
    return cms_strftime($date_string, $usered_timestamp);
}

/**
 * Sanitise a POST inputted date, and get the Unix timestamp for the inputted date.
 *
 * @param  ID_TEXT $stub The stub of the parameter name (stub_year, stub_month, stub_day, stub_hour, stub_minute)
 * @param  boolean $get_also Whether to allow over get parameters also
 * @param  boolean $do_timezone_conversion Whether to do timezone conversion
 * @return ?TIME The timestamp of the date (null: no input date was chosen)
 */
function post_param_date($stub, $get_also = false, $do_timezone_conversion = true)
{
    require_code('temporal2');
    return _post_param_date($stub, $get_also, $do_timezone_conversion);
}

/**
 * For a UTC timestamp, find the equivalent virtualised local timestamp.
 *
 * @param  TIME $time UTC time
 * @param  string $zone Timezone
 * @return TIME Virtualised local time
 */
function tz_time($time, $zone)
{
    return $time + find_timezone_offset($time, $zone);
}

/**
 * For a UTC timestamp and timezone, find the timezone offset.
 *
 * @param  TIME $time UTC time
 * @param  string $zone Timezone
 * @return integer Timezone offset in seconds
 */
function find_timezone_offset($time, $zone)
{
    if ($zone == '') {
        $zone = get_server_timezone();
    }

    @date_default_timezone_set($zone);
    $offset = intval(60.0 * 60.0 * floatval(date('O', $time)) / 100.0);
    date_default_timezone_set('UTC');
    return $offset;
}

/**
 * Get a list of timezones.
 *
 * @return array Timezone (map between boring-style and human-readable name). Sorted in offset order then likelihood orde.
 */
function get_timezone_list()
{
    require_code('temporal2');
    return _get_timezone_list();
}
