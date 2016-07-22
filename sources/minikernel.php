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

// Composr can install basically from the real final code, except for...
// -- global.php
// -- global2.php
// -- users.php
// --  things that depend on functionality of those that hasn't been emulated here
// This file emulates cut-down versions of the code in those files, for the most part.
// Once Composr is installed, this file is never used.

/**
 * Standard code module initialisation function.
 *
 * @ignore
 */
function init__minikernel()
{
    // Fixup some inconsistencies in parameterisation on different PHP platforms. See phpstub.php for info on what environmental data we can rely on.
    if ((!isset($_SERVER['SCRIPT_NAME'])) && (!isset($_ENV['SCRIPT_NAME']))) { // May be missing on GAE
        if (strpos($_SERVER['PHP_SELF'], '.php') !== false) {
            $_SERVER['SCRIPT_NAME'] = preg_replace('#\.php/.*#', '.php', $_SERVER['PHP_SELF']); // Same as PHP_SELF except without path info on the end
        } else {
            $_SERVER['SCRIPT_NAME'] = '/' . $_SERVER['SCRIPT_FILENAME']; // In GAE SCRIPT_FILENAME is actually relative to the app root
        }
    }
    if ((!array_key_exists('REQUEST_URI', $_SERVER)) && (!array_key_exists('REQUEST_URI', $_ENV))) { // May be missing on IIS
        $_SERVER['REQUEST_URI'] = $_SERVER['SCRIPT_NAME'];
        if (count($_GET) > 0) {
            $_SERVER['REQUEST_URI'] .= '?' . http_build_query($_GET);
        }
    }

    global $EXITING;
    $EXITING = null;

    global $MICRO_BOOTUP;
    $MICRO_BOOTUP = false;

    global $EXTERNAL_CALL;
    $EXTERNAL_CALL = false;

    global $IN_SELF_ROUTING_SCRIPT;
    $IN_SELF_ROUTING_SCRIPT = false;

    global $XSS_DETECT;
    $XSS_DETECT = false;

    set_error_handler('composr_error_handler');
    register_shutdown_function('catch_fatal_errors');
    safe_ini_set('track_errors', '1');
    global $SUPPRESS_ERROR_DEATH;
    $SUPPRESS_ERROR_DEATH = array(false);

    safe_ini_set('ocproducts.type_strictness', '1');

    safe_ini_set('date.timezone', 'UTC');

    @header('Expires: Mon, 20 Dec 1998 01:00:00 GMT');
    @header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
    @header('Cache-Control: no-cache, max-age=0');
    @header('Pragma: no-cache'); // for proxies, and also IE
}

/**
 * Add new suppress error death setting. Whether error display is suppressed.
 *
 * @param  boolean $setting New setting
 */
function push_suppress_error_death($setting)
{
    global $SUPPRESS_ERROR_DEATH;
    array_push($SUPPRESS_ERROR_DEATH, $setting);
}

/**
 * Remove last suppress error death setting.
 */
function pop_suppress_error_death()
{
    global $SUPPRESS_ERROR_DEATH;
    array_pop($SUPPRESS_ERROR_DEATH);
}

/**
 * See suppress error death setting.
 *
 * @return boolean Last setting
 */
function peek_suppress_error_death()
{
    global $SUPPRESS_ERROR_DEATH;
    return end($SUPPRESS_ERROR_DEATH);
}

/**
 * Find if we are running on a live Google App Engine application.
 *
 * @return boolean If it is running as a live Google App Engine application
 */
function appengine_is_live()
{
    return false;
}

/**
 * Are we currently running HTTPS.
 *
 * @return boolean If we are
 */
function tacit_https()
{
    $https = isset($_SERVER['HTTPS']) ? $_SERVER['HTTPS'] : '';
    return (($https != '') && ($https != 'off'));
}

/**
 * Provides a hook for file synchronisation between mirrored servers. Called after any file creation, deletion or edit.
 *
 * @param  PATH $filename File/directory name to sync on (full path)
 */
function sync_file($filename)
{
}

/**
 * Find whether a particular PHP function is blocked.
 *
 * @param  string $function Function name.
 * @return boolean Whether it is.
 */
function php_function_allowed($function)
{
    if (!in_array($function, /*These are actually language constructs rather than functions*/array('eval', 'exit', 'include', 'include_once', 'isset', 'require', 'require_once', 'unset', 'empty', 'print',))) {
        if (!function_exists($function)) {
            return false;
        }
    }
    return (@preg_match('#(\s|,|^)' . str_replace('#', '\#', preg_quote($function)) . '(\s|$|,)#', strtolower(@ini_get('disable_functions') . ',' . ini_get('suhosin.executor.func.blacklist') . ',' . ini_get('suhosin.executor.include.blacklist') . ',' . ini_get('suhosin.executor.eval.blacklist'))) == 0);
}

/**
 * Return a debugging back-trace of the current execution stack. Use this for debugging purposes.
 *
 * @return Tempcode Debugging backtrace
 */
function get_html_trace()
{
    $x = @ob_get_contents();
    @ob_end_clean();
    if (is_string($x)) {
        @print($x);
    }

    push_suppress_error_death(true);

    $_trace = debug_backtrace();
    $trace = array();
    foreach ($_trace as $i => $stage) {
        $traces = array();
        //if (in_array($stage['function'], array('get_html_trace', 'composr_error_handler', 'fatal_exit'))) continue;
        $file = '';
        $line = '';
        $__value = mixed();
        foreach ($stage as $key => $__value) {
            if ($key == 'file') {
                $file = str_replace('\'', '', $__value);
            } elseif ($key == 'line') {
                $line = strval($__value);
            }
            if ($key == 'args') {
                $_value = new Tempcode();
                foreach ($__value as $param) {
                    if (!((is_array($param)) && (array_key_exists('GLOBALS', $param)))) { // Some versions of PHP give the full environment as parameters. This will cause a recursive issue when outputting due to GLOBALS->ENV chaining.
                        if ((is_object($param) && (is_a($param, 'Tempcode'))) || ($param === null)) {
                            $__value = gettype($param);
                        } else {
                            @ob_start();
                            var_export($param);
                            $__value = ob_get_clean();
                        }
                        if (strlen($__value) < 3000) {
                            $_value->attach(paragraph(escape_html($__value)));
                        } else {
                            $_value = make_string_tempcode(escape_html('...'));
                        }
                    }
                }
            } else {
                $value = mixed();
                if (is_float($__value)) {
                    $value = float_format($__value);
                } elseif (is_integer($__value)) {
                    $value = integer_format($__value);
                } else {
                    $value = $__value;
                }

                if ((is_object($value) && (is_a($value, 'Tempcode'))) || (is_array($value) && (strlen(serialize($value)) > 100))) {
                    $_value = make_string_tempcode(escape_html(gettype($value)));
                } else {
                    @ob_start();
                    var_export($value);
                    $_value = make_string_tempcode(escape_html(ob_get_contents()));
                    ob_end_clean();
                }
            }
            $traces[] = array('LINE' => $line, 'FILE' => $file, 'KEY' => ucfirst($key), 'VALUE' => $_value);
        }
        $trace[] = array('TRACES' => $traces);
    }

    pop_suppress_error_death();

    return do_template('STACK_TRACE', array('_GUID' => 'da6c0ef0d8d793807d22e51555d73929', 'TRACE' => $trace, 'POST' => ''));
}

/**
 * Do a clean exit, echo the header (if possible) and an error message, followed by a debugging back-trace.
 * It also adds an entry to the error log, for reference.
 *
 * @param  mixed $text The error message
 * @return mixed Never returns (i.e. exits)
 */
function fatal_exit($text)
{
    //if (is_object($text)) $text = $text->evaluate();

    // To break any looping of errors
    global $EXITING;
    if (($EXITING !== null) || (!class_exists('Tempcode'))) {
        die_html_trace($text);
    }
    $EXITING = 1;

    $title = get_screen_title('ERROR_OCCURRED');

    $trace = get_html_trace();
    $echo = new Tempcode();
    $echo->attach(do_template('FATAL_SCREEN', array('_GUID' => '95877d427cf4e785b2f16cc71381e7eb', 'TITLE' => $title, 'TEXT' => $text, 'TRACE' => $trace, 'MAY_SEE_TRACE' => true,)));
    $css_url = 'install.php?type=css';
    $css_url_2 = 'install.php?type=css_2';
    $logo_url = 'install.php?type=logo';
    $version = strval(cms_version());
    $version .= (is_numeric(cms_version_minor()) ? '.' : ' ') . cms_version_minor();
    if (!array_key_exists('step', $_GET)) {
        $_GET['step'] = 1;
    }
    require_code('tempcode_compiler');
    $css_nocache = _do_template('default', '/css/', 'no_cache', 'no_cache', 'EN', '.css');
    $out_final = do_template('INSTALLER_HTML_WRAP', array(
        '_GUID' => '990e78523cee0b6782e1e09d73a700a7',
        'CSS_NOCACHE' => $css_nocache,
        'DEFAULT_FORUM' => '',
        'PASSWORD_PROMPT' => '',
        'CSS_URL' => $css_url,
        'CSS_URL_2' => $css_url_2,
        'LOGO_URL' => $logo_url,
        'STEP' => integer_format(intval($_GET['step'])),
        'CONTENT' => $echo,
        'VERSION' => $version,
    ));
    $out_final->evaluate_echo();

    exit();
}

/**
 * Lookup error on compo.sr, to see if there is more information.
 * (null implementation for minikernel)
 *
 * @param  mixed $error_message The error message (string or Tempcode)
 * @return ?string The result from the web service (null: no result)
 */
function get_webservice_result($error_message)
{
    return null;
}

/**
 * Composr error catcher for fatal versions.
 */
function catch_fatal_errors()
{
    $error = error_get_last();

    if ($error !== null) {
        if (substr($error['message'], 0, 26) == 'Maximum execution time of ') {
            if (function_exists('i_force_refresh')) {
                i_force_refresh();
            }
        }

        switch ($error['type']) {
            case E_ERROR:
            case E_CORE_ERROR:
            case E_COMPILE_ERROR:
            case E_USER_ERROR:
                push_suppress_error_death(false); // We can't recover as we've lost our execution track. Force a nice death rather than trying to display a recoverable error.
                $GLOBALS['DYING_BADLY'] = true; // Does not actually work unfortunately. @'d calls never get here at all.
                composr_error_handler($error['type'], $error['message'], $error['file'], $error['line']);
        }
    }
}

/**
 * Composr error handler (hooked into PHP error system).
 *
 * @param  integer $errno The error code-number
 * @param  PATH $errstr The error message
 * @param  string $errfile The file the error occurred in
 * @param  integer $errline The line the error occurred on
 * @return boolean Always false
 */
function composr_error_handler($errno, $errstr, $errfile, $errline)
{
    if (error_reporting() == 0) {
        return false; // This actually tells if @ was used oddly enough. You wouldn't figure from the PHP docs.
    }

    if ($errno == E_USER_ERROR) {
        $errno = E_ERROR;
    }
    if ($errno == E_PARSE) {
        $errno = E_ERROR;
    }
    if ($errno == E_CORE_ERROR) {
        $errno = E_ERROR;
    }
    if ($errno == E_COMPILE_ERROR) {
        $errno = E_ERROR;
    }
    if ($errno == E_CORE_WARNING) {
        $errno = E_WARNING;
    }
    if ($errno == E_COMPILE_WARNING) {
        $errno = E_WARNING;
    }
    if ($errno == E_USER_WARNING) {
        $errno = E_WARNING;
    }
    if ($errno == E_USER_NOTICE) {
        $errno = E_NOTICE;
    }

    switch ($errno) {
        case E_ERROR:
        case E_WARNING:
        case E_NOTICE:
            @ob_end_clean(); // Emergency output, potentially, so kill off any active buffer
            fatal_exit('PHP [' . strval($errno) . '] ' . $errstr);
    }

    return false;
}

/**
 * Find whether the current member is a guest.
 *
 * @param  ?MEMBER $member_id Member ID to check (null: current user)
 * @return boolean Whether the current member is a guest
 */
function is_guest($member_id = null)
{
    return true;
}

/**
 * Find whether we are running in safe mode.
 *
 * @return boolean Whether we are in safe mode
 */
function in_safe_mode()
{
    return get_param_integer('keep_safe_mode', 0) == 1;
}

/**
 * Find whether a certain script is being run to get here.
 *
 * @param  string $is_this_running Script filename (canonically we want NO .php file type suffix)
 * @return boolean Whether the script is running
 */
function running_script($is_this_running)
{
    if (substr($is_this_running, -4) != '.php') {
        $is_this_running .= '.php';
    }
    $script_name = isset($_SERVER['SCRIPT_NAME']) ? $_SERVER['SCRIPT_NAME'] : (isset($_ENV['SCRIPT_NAME']) ? $_ENV['SCRIPT_NAME'] : '');
    return (basename($script_name) == $is_this_running);
}

/**
 * Get the character set to use. We try and be clever to allow AJAX scripts to avoid loading up language
 *
 * @return string The character set
 */
function get_charset()
{
    if (function_exists('do_lang')) {
        return do_lang('charset');
    }
    global $SITE_INFO;
    $lang = (!empty($SITE_INFO['default_lang'])) ? $SITE_INFO['default_lang'] : 'EN';
    $path = get_file_base() . '/lang_custom/' . $lang . '/global.ini';
    if (!file_exists($path)) {
        $path = get_file_base() . '/lang/' . $lang . '/global.ini';
    }
    $file = fopen($path, GOOGLE_APPENGINE ? 'rb' : 'rt');
    $contents = unixify_line_format(fread($file, 100));
    fclose($file);
    $matches = array();
    if (preg_match('#charset=([\w\-]+)\r?\n#', $contents, $matches) != 0) {
        return strtolower($matches[1]);
    }
    return strtolower('utf-8');
}

/**
 * Echo an error message, and a debug back-trace of the current execution stack. Use this for debugging purposes.
 *
 * @param  string $message An error message
 */
function die_html_trace($message)
{
    critical_error('PASSON', $message);
}

/**
 * This is a less-revealing alternative to fatal_exit, that is used for user-errors/common-corruption-scenarios
 *
 * @param  mixed $text The error message
 * @return mixed Never returns (i.e. exits)
 */
function inform_exit($text)
{
    warn_exit($text);
}

/**
 * This is a less-revealing alternative to fatal_exit, that is used for user-errors/common-corruption-scenarios
 *
 * @param  mixed $text The error message
 * @return mixed Never returns (i.e. exits)
 */
function warn_exit($text)
{
    // To break any looping of errors
    global $EXITING;
    if (($EXITING !== null) || (!class_exists('Tempcode'))) {
        die_html_trace($text);
    }
    $EXITING = 1;

    $title = get_screen_title('ERROR_OCCURRED');

    $echo = new Tempcode();
    $echo->attach(do_template('WARN_SCREEN', array('_GUID' => '723ede24462dfc4cd4485851819786bc', 'TITLE' => $title, 'TEXT' => $text, 'PROVIDE_BACK' => false)));
    $css_url = 'install.php?type=css';
    $css_url_2 = 'install.php?type=css_2';
    $logo_url = 'install.php?type=logo';
    $version = strval(cms_version());
    $version .= (is_numeric(cms_version_minor()) ? '.' : ' ') . cms_version_minor();
    if (!array_key_exists('step', $_GET)) {
        $_GET['step'] = 1;
    }
    require_code('tempcode_compiler');
    $css_nocache = _do_template('default', '/css/', 'no_cache', 'no_cache', 'EN', '.css');
    $out_final = do_template('INSTALLER_HTML_WRAP', array(
        '_GUID' => '710e7ea5c186b4c42bb3a5453dd915ed',
        'CSS_NOCACHE' => $css_nocache,
        'DEFAULT_FORUM' => '',
        'PASSWORD_PROMPT' => '',
        'CSS_URL' => $css_url,
        'CSS_URL_2' => $css_url_2,
        'LOGO_URL' => $logo_url,
        'STEP' => integer_format(intval($_GET['step'])),
        'CONTENT' => $echo,
        'VERSION' => $version,
    ));
    $out_final->evaluate_echo();

    exit();
}

/**
 * Get the major version of your installation.
 *
 * @return integer The major version number of your installation
 */
function cms_version()
{
    return intval(cms_version_number());
}

/**
 * Get the full string version of Composr that you are running.
 *
 * @return string The string saying the full Composr version number
 */
function cms_version_pretty()
{
    return '';
}

/**
 * Get the domain the website is installed on (preferably, without any www). The domain is used for e-mail defaults among other things.
 *
 * @return string The domain of the website
 */
function get_domain()
{
    global $SITE_INFO;
    if (empty($SITE_INFO['domain'])) {
        $SITE_INFO['domain'] = preg_replace('#:.*#', '', cms_srv('HTTP_HOST'));
    }
    return $SITE_INFO['domain'];
}

/**
 * Get the type of forums installed.
 *
 * @return string The type of forum installed
 */
function get_forum_type()
{
    global $SITE_INFO;
    if (empty($SITE_INFO['forum_type'])) {
        return 'none';
    }
    return $SITE_INFO['forum_type'];
}

/**
 * Get the installed forum base URL.
 *
 * @return URLPATH The installed forum base URL
 */
function get_forum_base_url()
{
    if (get_forum_type() == 'none') {
        return '';
    }
    global $SITE_INFO;
    if (empty($SITE_INFO['forum_base_url'])) {
        return get_base_url();
    }
    return $SITE_INFO['forum_base_url'];
}

/**
 * Get the site name.
 *
 * @return string The name of the site
 */
function get_site_name()
{
    return '';
}

/**
 * Get the base URL (the minimum fully qualified URL to our installation).
 *
 * @param  ?boolean $https Whether to get the HTTPS base URL (null: do so only if the current page uses the HTTPS base URL)
 * @param  string $zone_for What zone this is running in
 * @return URLPATH The base-url
 */
function get_base_url($https = null, $zone_for = '')
{
    global $SITE_INFO;
    if (empty($SITE_INFO['base_url'])) {
        $default_base_url = (tacit_https() ? 'https://' : 'http://') . cms_srv('HTTP_HOST') . str_replace('%2F', '/', rawurlencode(str_replace('\\', '/', dirname(cms_srv('SCRIPT_NAME')))));

        $base_url = post_param_string('base_url', $default_base_url);
        if (substr($base_url, -1) == '/') {
            $base_url = substr($base_url, 0, strlen($base_url) - 1);
        }

        return $base_url . (($zone_for == '') ? '' : ('/' . $zone_for));
    }
    return $SITE_INFO['base_url'] . (($zone_for == '') ? '' : ('/' . $zone_for));
}

/**
 * Get the base URL (the minimum fully qualified URL to our personal data installation). For a shared install only, this is different to the base-url.
 *
 * @param  ?boolean $https Whether to get the HTTPS base URL (null: do so only if the current page uses the HTTPS base URL)
 * @return URLPATH The base-url
 */
function get_custom_base_url($https = null)
{
    return get_base_url($https);
}

/**
 * Log a hackattack, then displays an error message. It also attempts to send an e-mail to the staff alerting them of the hackattack.
 *
 * @param  ID_TEXT $reason The reason for the hack attack. This has to be a language string ID
 * @param  SHORT_TEXT $reason_param_a A parameter for the hack attack language string (this should be based on a unique ID, preferably)
 * @param  SHORT_TEXT $reason_param_b A more illustrative parameter, which may be anything (e.g. a title)
 * @return mixed Never returns (i.e. exits)
 */
function log_hack_attack_and_exit($reason, $reason_param_a = '', $reason_param_b = '')
{
    exit('You should not see this message. If you do, contact ocProducts and tell them a \'lhaae\' showed during installation.');
}

/**
 * Check the specified text ($a) for banned words.
 * If any are found, and the member cannot bypass the word filter, an error message is displayed.
 *
 * @param  string $a The sentence to check
 * @param  ?ID_TEXT $name The name of the parameter this is coming from. Certain parameters are not checked, for reasons of efficiency (avoiding loading whole word check list if not needed) (null: don't know param, do not check to avoid)
 * @param  boolean $no_die Whether to avoid dying on fully blocked words (useful if importing, for instance)
 * @param  boolean $try_patterns Whether to try pattern matching (this takes more resources)
 * @param  boolean $perm_check Whether to allow permission-based skipping, and length-based skipping
 * @return string "Fixed" version
 */
function check_wordfilter($a, $name = null, $no_die = false, $try_patterns = false, $perm_check = true)
{
    return $a;
}

/**
 * Get a value (either POST [u]or[/u] GET), or the default if neither can be found.
 *
 * @param  ID_TEXT $name The name of the parameter to get
 * @param  ?string $default The default value to give the parameter if the parameter value is not defined (null: give error on missing parameter)
 * @return ?string The value of the parameter (null: not there, and default was null)
 */
function either_param_string($name, $default = null)
{
    $a = __param($_REQUEST, $name, $default);
    return $a;
}

/**
 * Get the value of the specified POST key, if it is found, or the default otherwise.
 *
 * @param  ID_TEXT $name The name of the parameter to get
 * @param  ?string $default The default value to give the parameter if the parameter value is not defined (null: give error on missing parameter)
 * @return ?string The value of the parameter (null: not there, and default was null)
 */
function post_param_string($name, $default = null)
{
    $a = __param($_POST, $name, $default);
    return $a;
}

/**
 * Get the value of the specified GET key, if it is found, or the default otherwise.
 *
 * @param  ID_TEXT $name The name of the parameter to get
 * @param  ?string $default The default value to give the parameter if the parameter value is not defined (null: give error on missing parameter)
 * @return ?string The value of the parameter (null: not there, and default was null)
 */
function get_param_string($name, $default = null)
{
    $a = __param($_GET, $name, $default);
    return $a;
}

/**
 * Helper function to load up a GET/POST parameter.
 *
 * @param  array $array The array we're extracting parameters from
 * @param  ID_TEXT $name The name of the parameter
 * @param  ?mixed $default The default value to use for the parameter (null: no default)
 * @param  boolean $must_integer Whether the parameter has to be an integer
 * @param  boolean $is_post Whether the parameter is a POST parameter
 * @return ?string The value of the parameter (null: not there, and default was null)
 * @ignore
 */
function __param($array, $name, $default, $must_integer = false, $is_post = false)
{
    if (!array_key_exists($name, $array)) {
        return $default;
    }
    $val = trim($array[$name]);
    if (get_magic_quotes_gpc()) {
        $val = stripslashes($val);
    }

    return $val;
}

/**
 * This function is the integeric partner of either_param_string, as it returns the value as an integer.
 * You should always use integer specified versions when inputting integers, for the added security that type validation allows. If the value is of the wrong type, it indicates a hack attempt and will be logged.
 *
 * @param  ID_TEXT $name The name of the parameter to get
 * @param  ?mixed $default The default value to give the parameter if the parameter value is not defined (null: give error on missing parameter)
 * @return integer The parameter value
 */
function either_param_integer($name, $default = null)
{
    $ret = __param($_REQUEST, $name, ($default === false) ? false : (($default === null) ? null : strval($default)));
    if (($default === null) && (($ret === '') || ($ret === null))) {
        return null;
    }
    return intval($ret);
}

/**
 * This function is the integeric partner of post_param_string, as it returns the value as an integer.
 *
 * @param  ID_TEXT $name The name of the parameter to get
 * @param  ?mixed $default The default value to give the parameter if the parameter value is not defined (null: give error on missing parameter)
 * @return integer The parameter value
 */
function post_param_integer($name, $default = null)
{
    $ret = __param($_POST, $name, ($default === false) ? false : (($default === null) ? null : strval($default)));
    if (($default === null) && (($ret === '') || ($ret === null))) {
        return null;
    }
    return intval($ret);
}

/**
 * This function is the integeric partner of get_param_string, as it returns the value as an integer.
 *
 * @param  ID_TEXT $name The name of the parameter to get
 * @param  ?mixed $default The default value to give the parameter if the parameter value is not defined (null: give error on missing parameter)
 * @return integer The parameter value
 */
function get_param_integer($name, $default = null)
{
    $ret = __param($_GET, $name, ($default === false) ? false : (($default === null) ? null : strval($default)));
    if (($default === null) && (($ret === '') || ($ret === null))) {
        return null;
    }
    return intval($ret);
}

/**
 * Get the file base for your installation of Composr
 *
 * @return PATH The file base, without a trailing slash
 */
function get_file_base()
{
    global $FILE_BASE;
    return $FILE_BASE;
}

/**
 * Get the file base for your installation of Composr.  For a shared install only, this is different to the base-url.
 *
 * @return PATH The file base, without a trailing slash
 */
function get_custom_file_base()
{
    global $FILE_BASE;
    return $FILE_BASE;
}

/**
 * Get the parameter put into it, with no changes. If it detects that the parameter is naughty (i.e malicious, and probably from a hacker), it will log the hack-attack and output an error message.
 * This function is designed to be called on parameters that will be embedded in a path, and defines malicious as trying to reach a parent directory using '..'. All file paths in Composr should be absolute
 *
 * @param  string $in String to test
 * @return string Same as input string
 */
function filter_naughty($in)
{
    if (strpos($in, '..') !== false) {
        exit();
    }
    return $in;
}

/**
 * This function is similar to filter_naughty, except it requires the parameter to be strictly alphanumeric. It is intended for use on text that will be put into an eval.
 *
 * @param  string $in String to test
 * @return string Same as input string
 */
function filter_naughty_harsh($in)
{
    if (preg_match('#^[\w0-9\-]*$#', $in) != 0) {
        return $in;
    }
    exit();
}

/**
 * Make sure that lines are seperated by "\n", with no "\r"'s there at all. For Mac data, this will be a flip scenario. For Linux data this will be a null operation. For windows data this will be change from "\r\n" to just "\n". For a realistic scenario, data could have originated on all kinds of platforms, with some editors converting, some situations being inter-platform, and general confusion. Don't make blind assumptions - use this function to clean data, then write clean code that only considers "\n"'s.
 *
 * @param  string $in The data to clean
 * @return string The cleaned data
 */
function unixify_line_format($in)
{
    $in = str_replace("\r\n", "\n", $in);
    return str_replace("\r", "\n", $in);
}

/**
 * Make sure that the given CSS file is loaded up.
 *
 * @sets_output_state
 *
 * @param  ID_TEXT $css The CSS file required
 */
function require_css($css)
{
}

/**
 * Make sure that the given JavaScript file is loaded up.
 *
 * @sets_output_state
 *
 * @param  ID_TEXT $css The JavaScript file required
 */
function require_javascript($css)
{
}

/**
 * Do a wildcard match by converting to a regular expression.
 *
 * @param  string $context The haystack
 * @param  string $word The needle (a wildcard expression)
 * @param  boolean $full_cover Whether full-coverance is required
 * @return boolean Whether we have a match
 */
function simulated_wildcard_match($context, $word, $full_cover = false)
{
    $rexp = str_replace('%', '.*', str_replace('_', '.', str_replace('\\?', '.', str_replace('\\*', '.*', preg_quote($word)))));
    if ($full_cover) {
        $rexp = '^' . $rexp . '$';
    }

    return preg_match('#' . str_replace('#', '\#', $rexp) . '#i', $context) != 0;
}

/**
 * Get data from the persistent cache.
 *
 * @param  mixed $key Key
 * @param  ?TIME $min_cache_date Minimum timestamp that entries from the cache may hold (null: don't care)
 * @return ?mixed The data (null: not found / null entry)
 */
function persistent_cache_get($key, $min_cache_date = null)
{
    return null;
}

/**
 * Put data into the persistent cache.
 *
 * @param  mixed $key Key
 * @param  mixed $data The data
 * @param  boolean $server_wide Whether it is server-wide data
 * @param  ?integer $expire_secs The expiration time in seconds. (null: Default expiry in 60 minutes, or never if it is server-wide).
 */
function persistent_cache_set($key, $data, $server_wide = false, $expire_secs = null)
{
}

/**
 * Delete data from the persistent cache.
 *
 * @param  mixed $key Key name
 * @param  boolean $substring Whether we are deleting via substring
 */
function persistent_cache_delete($key, $substring = false)
{
}
