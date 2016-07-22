<?php /*

 Composr
 Copyright (c) ocProducts, 2004-2016

 See text/EN/licence.txt for full licencing information.


 NOTE TO PROGRAMMERS:
   Do not edit this file. If you need to make changes, save your changed file to the appropriate *_custom folder
   **** If you ignore this advice, then your website upgrades (e.g. for bug fixes) will likely kill your changes ****

*/

/*EXTRA FUNCTIONS: shell_exec*/

/**
 * @license    http://opensource.org/licenses/cpal_1.0 Common Public Attribution License
 * @copyright  ocProducts Ltd
 * @package    core
 */

/*
This is a basic profiler for Composr, for use on live servers where performance must be maintained yet we need live performance data gathering.
It takes a targeted approach - you must block things out to be profiled that you suspect may be slow.

Enable via the hidden 'enable_profiler' option (documented in the Code Book).

Logging is done to files named per:
data_custom/profiling--<memberID>.<timestamp>.<uniqid>--<requestTimeInSeconds>.log

(requestTimeInSeconds is "in-progress" until the request finishes)
*/

/**
 * Standard code module initialisation function.
 *
 * @ignore
 */
function init__profiler()
{
    global $PROFILING_ALLOWED;
    $PROFILING_ALLOWED = null; // Will be detected later

    global $PROFILING_LINUX_FULL;
    $PROFILING_LINUX_FULL = null; // Will be detected later

    global $PROFILER_PATH;
    $PROFILER_PATH = null; // Will be decided later

    global $PROFILER_FILEHANDLE;
    $PROFILER_FILEHANDLE = null; // Will be opened if we have profiling enabled

    global $PROFILER_DATA;
    $PROFILER_DATA = array();

    register_shutdown_function('_cms_profiler_script_end');
}

/**
 * Find whether profiling is enabled. This may be false due to Composr still starting up, in which case it will be enabled later.
 *
 * @return boolean Whether profiling is enabled.
 */
function cms_profile_is_enabled()
{
    if (!function_exists('get_value')) {
        return false;
    }
    if (!function_exists('get_member')) {
        return false;
    }
    if (!function_exists('get_self_url_easy')) {
        return false;
    }
    if (!function_exists('clean_file_size')) {
        return false;
    }

    global $PROFILING_ALLOWED, $PROFILING_LINUX_FULL;
    if (!isset($PROFILING_ALLOWED)) {
        $val = get_value('enable_profiler');
        $PROFILING_ALLOWED = ($val == '1' || $val == '2') && (cms_is_writable(get_custom_file_base() . '/data_custom'));
        $PROFILING_LINUX_FULL = ($val == '2');
    }
    return $PROFILING_ALLOWED;
}

/**
 * Start a profiling block, for a specified identifier (of your own choosing).
 *
 * @param  ID_TEXT $identifier Identifier
 *
 * @ignore
 */
function _cms_profile_start_for($identifier)
{
    if (!cms_profile_is_enabled()) {
        return;
    }

    global $PROFILER_DATA;

    if (!isset($PROFILER_DATA[$identifier])) {
        $PROFILER_DATA[$identifier] = array();
    }

    $at = array(
        'time_start' => microtime(true),
        'specifics' => null,
    );
    $PROFILER_DATA[$identifier][] = $at;
}

/**
 * End a profiling block, for a specified identifier (of your own choosing - but you must have started it with cms_profile_start_for).
 *
 * @param  ID_TEXT $identifier Identifier
 * @param  ?string $specifics Longer details of what happened (e.g. a specific SQL query that ran) (null: none provided)
 * @ignore
 */
function _cms_profile_end_for($identifier, $specifics = null)
{
    if (!cms_profile_is_enabled()) {
        return;
    }

    global $PROFILER_DATA;

    if (!isset($PROFILER_DATA[$identifier])) {
        return; // Error, should never happen
    }

    end($PROFILER_DATA[$identifier]);
    $key = key($PROFILER_DATA[$identifier]);
    $at = &$PROFILER_DATA[$identifier][$key];
    $time_start = $at['time_start'];
    $time_end = microtime(true);
    $at = array(
              'time_end' => $time_end,
              'time_length' => ($time_end - $time_start),
              'specifics' => $specifics,
          ) + $at;

    _cms_profile_log_line(_cms_profile_generate_line($identifier, $at, $key + 1));
}

/**
 * Generate a line to add to the profiling log, from a recorded signature.
 *
 * @param  ID_TEXT $identifier Identifier
 * @param  array $at The signature for what we just profiled
 * @param  integer $cnt This will be the nth of this identifier to be logged
 * @return string Log line
 *
 * @ignore
 */
function _cms_profile_generate_line($identifier, $at, $cnt)
{
    $line = $identifier;
    $line .= '(x' . strval($cnt) . ')';
    $line .= str_repeat(' ', max(1, 55 - strlen($line))) . float_to_raw_string($at['time_length'], 4) . 's';
    if ($at['specifics'] !== null) {
        $line .= '  ' . $at['specifics'];
    }
    return $line;
}

/**
 * Store a line in the profiling log.
 *
 * @param  string $line Log line
 *
 * @ignore
 */
function _cms_profile_log_line($line)
{
    // Open up unique log file (per-request) if not yet done so
    global $PROFILER_FILEHANDLE, $PROFILER_PATH;
    if (!isset($PROFILER_FILEHANDLE)) {
        if (!isset($PROFILER_PATH)) {
            $PROFILER_PATH = get_custom_file_base() . '/data_custom/profiling';
            if (is_guest()) {
                $PROFILER_PATH .= '--guest';
            } else {
                $PROFILER_PATH .= '--member' . strval(get_member());
            }
            $PROFILER_PATH .= '.timestamp' . strval(time());
            $PROFILER_PATH .= '.rand' . uniqid('', true);
            $PROFILER_PATH .= '--in-progress.log';
        }

        $PROFILER_FILEHANDLE = fopen($PROFILER_PATH, 'at');

        // Pre-logging
        _cms_profile_log_line('URL: ' . get_self_url_easy(true));
        _cms_profiler_generic_logging();
        _cms_profile_log_line(''); // Spacer line
    }

    // Write line
    fwrite($PROFILER_FILEHANDLE, $line . "\n");
}

/**
 * Finish the profiler (automatically run at script termination).
 *
 * @ignore
 */
function _cms_profiler_script_end()
{
    if (!cms_profile_is_enabled()) {
        return;
    }

    global $PAGE_START_TIME, $PROFILER_PATH, $PROFILER_FILEHANDLE;

    if (!isset($PROFILER_FILEHANDLE)) {
        return; // Never started, so don't tail off
    }

    // Lock out further profiling
    global $PROFILING_ALLOWED;
    $PROFILING_ALLOWED = false;

    // Post-logging
    _cms_profile_log_line(''); // Spacer line
    _cms_profiler_generic_logging();
    _cms_profile_log_line('PHP memory usage: ' . clean_file_size(memory_get_usage()));
    _cms_profile_log_line('PHP peak memory usage: ' . clean_file_size(memory_get_peak_usage()));

    // Close down file
    if (isset($PROFILER_FILEHANDLE)) {
        fclose($PROFILER_FILEHANDLE);

        // Rename file to make total time clearer, for easier identification of slow requests
        $scope_time = intval(($PAGE_START_TIME - microtime(true)) * 1000);
        $new_path = preg_replace('#--in-progress\.log$#', '--' . strval($scope_time) . 's.log', $PROFILER_PATH);
        fix_permissions($PROFILER_PATH);
        rename($PROFILER_PATH, $new_path);
    }
}

/**
 * Add in generic logging lines to the profiling log (background/context information). Assumes Linux.
 *
 * @ignore
 */
function _cms_profiler_generic_logging()
{
    global $PROFILING_LINUX_FULL;

    if ($PROFILING_LINUX_FULL) {
        $c = trim(@strval(shell_exec('uptime')));
        if ($c != '') {
            _cms_profile_log_line('uptime: ' . $c);
        }

        $c = trim(@strval(shell_exec('vmstat')));
        if ($c != '') {
            _cms_profile_log_line('vmstat: ' . $c);
        }
    }
}
