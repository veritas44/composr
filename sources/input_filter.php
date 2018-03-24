<?php /*

 Composr
 Copyright (c) ocProducts, 2004-2018

 See text/EN/licence.txt for full licensing information.


 NOTE TO PROGRAMMERS:
   Do not edit this file. If you need to make changes, save your changed file to the appropriate *_custom folder
   **** If you ignore this advice, then your website upgrades (e.g. for bug fixes) will likely kill your changes ****

*/

/*EXTRA FUNCTIONS: xml_.**/

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
function init__input_filter()
{
    global $URL_DEFAULT_PARAMETERS_ENABLED;
    $URL_DEFAULT_PARAMETERS_ENABLED = false;
}

/**
 * Check an input field isn't 'evil'.
 *
 * @param  string $name The name of the parameter
 * @param  string $val The value retrieved
 * @param  ?boolean $posted Whether the parameter is a POST parameter (null: undetermined)
 * @param  integer $filters A bitmask of INPUT_FILTER_* filters
 */
function check_input_field_string($name, &$val, $posted, $filters)
{
    if (preg_match('#^\w*$#', $val) !== 0) {
        return;
    }

    if ((($filters & INPUT_FILTER_JS_URLS) != 0) && (preg_match('#^\s*((((j\s*a\s*v\s*a\s*)|(v\s*b\s*))?s\s*c\s*r\s*i\s*p\s*t)|(d\s*a\s*t\s*a))\s*:#i', $val) !== 0)) {
        log_hack_attack_and_exit('SCRIPT_URL_HACK_2', $val);
    }

    if ((($filters & INPUT_FILTER_VERY_STRICT) != 0) && (preg_match('#\n|\000|<#mi', $val) !== 0)) {
        if ($name === 'page') { // Stop loops
            $_GET[$name] = '';
        }
        log_hack_attack_and_exit('DODGY_GET_HACK', $name, $val);
    }

    if ((($filters & INPUT_FILTER_URL_DESTINATION) != 0) && (!$posted)) { // Don't allow redirections to non-trusted sites
        if (!url_is_local($val)) {
            $bus = array(
                get_base_url(false) . '/',
                get_base_url(true) . '/',
                get_forum_base_url() . '/',
                'https://compo.sr/',
                'https://compo.sr/',
            );
            $trusted_sites = get_trusted_sites(2);
            foreach ($trusted_sites as $allowed) {
                $bus[] = 'http://' . $allowed . '/';
                $bus[] = 'https://' . $allowed . '/';
            }
            $ok = false;
            foreach ($bus as $bu) {
                if (substr($val, 0, strlen($bu)) === $bu) {
                    $ok = true;
                    break;
                }
            }
            if (!$ok) {
                if (function_exists('build_url')) {
                    $val = static_evaluate_tempcode(build_url(array('page' => ''), 'site'));
                } else {
                    $val = get_base_url(false);
                }
            }
        }
    }

    if (($filters & INPUT_FILTER_MODSECURITY_URL_PARAMETER) != 0) {
        if (substr($val, 0, 10) == 'https-cms:') {
            $val = get_base_url(true) . '/' . substr($val, 10);
        } elseif (substr($val, 0, 9) == 'http-cms:') {
            $val = get_base_url(false) . '/' . substr($val, 9);
        }
    }

    if (!$GLOBALS['BOOTSTRAPPING']) {
        // Additional checks for non-privileged users
        if ((function_exists('has_privilege') || !$posted) && $name !== 'page'/*Too early in boot if 'page'*/) {
            if (($filters & INPUT_FILTER_EARLY_XSS) != 0) {
                if (!$posted/*get parameters really shouldn't be so crazy so as for the filter to do anything!*/ || !has_privilege(get_member(), 'unfiltered_input')) {
                    hard_filter_input_data__html($val, true);
                    hard_filter_input_data__filesystem($val);
                }
            }
            if (($filters & INPUT_FILTER_DYNAMIC_FIREWALL) != 0) {
                @hard_filter_input_data__dynamic_firewall($name, $val); // @'d to stop any internal errors taking stuff down
            }
        }
    }
}

/**
 * Check a posted field isn't part of a malicious CSRF attack via referer checking (we do more checks for post fields than get fields).
 *
 * @param  string $name The name of the parameter
 * @param  string $val The value retrieved
 * @param  integer $filters A bitmask of INPUT_FILTER_* filters
 */
function check_posted_field($name, $val, $filters)
{
    $evil = false;

    $referer = $_SERVER['HTTP_REFERER'];
    if ($referer == '') {
        $referer = $_SERVER['HTTP_ORIGIN'];
    }

    $is_true_referer = (substr($referer, 0, 7) === 'http://') || (substr($referer, 0, 8) === 'https://');

    if (($_SERVER['REQUEST_METHOD'] === 'POST') && (!is_guest())) {
        if ($is_true_referer) {
            $canonical_referer_domain = strip_url_to_representative_domain($referer);
            $canonical_baseurl_domain = strip_url_to_representative_domain(get_base_url());
            if ($canonical_referer_domain != $canonical_baseurl_domain) {
                if ((has_interesting_post_fields()) && (($filters & INPUT_FILTER_TRUSTED_SITES) != 0)) {
                    $trusted_sites = get_trusted_sites(2);
                    $found = false;
                    foreach ($trusted_sites as $partner) {
                        $partner = trim($partner);

                        if (($partner != '') && ($canonical_referer_domain === $partner)) {
                            $found = true;
                            break;
                        }
                    }
                    if (!$found) {
                        $evil = true;
                    }
                }
            }
        }
    }

    if ($evil) {
        $_POST = array(); // To stop loops
        log_hack_attack_and_exit('EVIL_POSTED_FORM_HACK', $referer);
    }
}

/**
 * Convert a full URL to a domain name we will consider this a trust on.
 *
 * @param  URLPATH $url The URL
 * @return string The domain
 */
function strip_url_to_representative_domain($url)
{
    return preg_replace('#^www\.#', '', strtolower(parse_url($url, PHP_URL_HOST)));
}

/**
 * Find trusted sites.
 *
 * @param  integer $level Trusted sites level
 * @set 1 2
 * @param  boolean $include_self Include a self reference
 * @return array Trusted domain names
 */
function get_trusted_sites($level, $include_self = true)
{
    global $SITE_INFO;

    if (function_exists('get_option')) {
        $option = '';
        if ($level >= 1) {
            $option .= get_option('trusted_sites_1') . "\n";
        }
        if ($level >= 2) {
            $option .= get_option('trusted_sites_2') . "\n";
        }

        $trusted_sites = array();
        foreach (explode("\n", $option) as $allowed_partner) {
            if (trim($allowed_partner) != '') {
                $trusted_sites[] = $allowed_partner;

                if (substr($allowed_partner, 0, 4) != 'www.') {
                    $trusted_sites[] = 'www.' . $allowed_partner;
                }
            }
        }
    } else {
        $trusted_sites = array();
    }

    $zl = strlen('ZONE_MAPPING_');
    foreach ($SITE_INFO as $key => $_val) {
        if ($key !== '' && $key[0] === 'Z' && substr($key, 0, $zl) === 'ZONE_MAPPING_') {
            $trusted_sites[] = $_val[0];
        }
    }

    if ($include_self) {
        if (isset($SITE_INFO['base_url'])) {
            $base_url = $SITE_INFO['base_url'];
            $trusted_sites[] = parse_url($base_url, PHP_URL_HOST);
        } else {
            $host = get_local_hostname();
            if ($host != '') {
                $trusted_sites[] = $host;
            }
        }
    }

    if (isset($SITE_INFO['custom_base_url'])) {
        $base_url = $SITE_INFO['custom_base_url'];
        $trusted_sites[] = parse_url($base_url, PHP_URL_HOST);
    }

    return $trusted_sites;
}

/**
 * Filter input data for safety within potential filesystem calls.
 * Only called for non-privileged users, filters/alters rather than blocks, due to false-positive likelihood.
 *
 * @param  string $val The data
 */
function hard_filter_input_data__filesystem(&$val)
{
    static $nastiest_path_signals = array(
        '(^|[/\\\\])_config\.php($|\0)',
        '\.\.[/\\\\]',
        '(^|[/\\\\])data_custom[/\\\\].*log.*',
    );
    $matches = array();
    foreach ($nastiest_path_signals as $signal) {
        if (preg_match('#' . $signal . '#', $val, $matches) != 0) {
            $val = str_replace($matches[0], str_replace('.', '&#46;', $matches[0]), $val); // Break the paths
        }
    }
}

/**
 * Filter data according to the dynamic firewall.
 *
 * @param  string $name The name of the parameter
 * @param  string $val The value retrieved
 */
function hard_filter_input_data__dynamic_firewall($name, &$val)
{
    $rules_path = get_custom_file_base() . '/data_custom/firewall_rules.txt';
    if (is_file($rules_path)) {
        $rules = file($rules_path);
        foreach ($rules as $rule) {
            $parts = explode('=', $rule, 2);
            if (count($parts) == 2) {
                list($check_name, $check_val) = $parts;
                $check_name_is_regexp = (isset($check_name[0]) && $check_name[0] == '#' && $check_name[strlen($check_name) - 1] == '#');
                $check_val_is_regexp = (isset($check_val[0]) && $check_val[0] == '#' && $check_val[strlen($check_val) - 1] == '#');
                if ($check_name_is_regexp && preg_match($check_name, $name) != 0 || !$check_name_is_regexp && $check_name == $name) {
                    if ($check_val_is_regexp && preg_match($check_val, $val) == 0 || !$check_val_is_regexp && $check_val != $val) {
                        $val = 'filtered';
                    }
                }
            }
        }
    }
}

/**
 * Used by hard_filter_input_data__html to add rel="nofollow" to links.
 *
 * @param  array $matches Array of matches
 * @return string Substituted text
 *
 * @ignore
 */
function _link_nofollow_callback($matches)
{
    // Remove any existing rel attributes (it's too complex to play nice, e.g. what if a hacker added multiple ones and we altered the wrong one)
    $matches[1] = preg_replace('#\srel="[^"]*"#', '', $matches[1]);
    $matches[1] = preg_replace('#\srel=\'[^"]*\'#', '', $matches[1]);
    $matches[1] = preg_replace('#\srel=[^\s<>\'"]*#', '', $matches[1]);

    // Add in our rel attribute
    return $matches[1] . ' rel="nofollow"' . $matches[2] . $matches[3] . $matches[4];
}

/**
 * Filter input data for safety within frontend markup, taking account of HTML/JavaScript/CSS/embed attacks.
 * Only called for non-privileged users, filters/alters rather than blocks, due to false-positive likelihood.
 *
 * @param  string $val The data
 * @param  boolean $lite Do a lite-check if we're not sure this is even actually HTML
 */
function hard_filter_input_data__html(&$val, $lite = false)
{
    require_code('comcode');

    init_potential_js_naughty_array();

    global $POTENTIAL_JS_NAUGHTY_ARRAY;

    // Null vector
    $val = str_replace(chr(0), '', $val);

    // Comment vector
    $old_val = '';
    do {
        $old_val = $val;
        $val = preg_replace('#/\*.*\*/#Us', '', $val);
    } while ($old_val != $val);

    // Entity vector
    $matches = array();
    do {
        $old_val = $val;
        $count = preg_match_all('#&\#(\d+)#i', $val, $matches); // No one would use this for an html tag unless it was malicious. The ASCII could have been put in directly.
        for ($i = 0; $i < $count; $i++) {
            $matched_entity = intval($matches[1][$i]);
            if (($matched_entity < 127) && (array_key_exists(chr($matched_entity), $POTENTIAL_JS_NAUGHTY_ARRAY))) {
                if ($matched_entity == 0) {
                    $matched_entity = ord(' ');
                }
                $val = str_replace($matches[0][$i] . ';', chr($matched_entity), $val);
                $val = str_replace($matches[0][$i], chr($matched_entity), $val);
            }
        }
        $count = preg_match_all('#&\#x([\da-f]+)#i', $val, $matches); // No one would use this for an html tag unless it was malicious. The ASCII could have been put in directly.
        for ($i = 0; $i < $count; $i++) {
            $matched_entity = intval(base_convert($matches[1][$i], 16, 10));
            if (($matched_entity < 127) && (array_key_exists(chr($matched_entity), $POTENTIAL_JS_NAUGHTY_ARRAY))) {
                if ($matched_entity == 0) {
                    $matched_entity = ord(' ');
                }
                $val = str_replace($matches[0][$i] . ';', chr($matched_entity), $val);
                $val = str_replace($matches[0][$i], chr($matched_entity), $val);
            }
        }
    } while ($old_val != $val);

    // Tag vectors
    $bad_tags = 'noscript|script|link|style|meta|iframe|frame|object|embed|applet|html|xml|body|head|form|base|layer|v:vmlframe|svg';
    $val = preg_replace('#<(' . $bad_tags . ')#i', '<span', $val); // Intentionally does not strip so as to avoid attacks like <<scriptscript --> <script
    $val = preg_replace('#</(' . $bad_tags . ')#i', '</span', $val);

    // CSS attack vectors
    $val = preg_replace('#\\\\(\d+)#i', '${1}', $val); // CSS escaping
    $val = preg_replace('#e\s*(x\s*p\s*r\s*e\s*s\s*s\s*i\s*o\s*n\()#i', '&eacute;${1}', $val); // expression(
    $val = preg_replace('#b\s*(e\s*h\s*a\s*v\s*i\s*o\s*r\s*\()#i', '&szlig;${1}', $val); // behavior(
    $val = preg_replace('#b\s*(i\s*n\s*d\s*i\s*n\s*g\s*\()#i', '&szlig;${1}', $val); // bindings(

    // Script-URL vectors (protocol handlers)
    $val = preg_replace('#((j[\\\\\s]*a[\\\\\s]*v[\\\\\s]*a[\\\\\s]*|v[\\\\\s]*b[\\\\\s]*)s[\\\\\s]*c[\\\\\s]*r[\\\\\s]*i[\\\\\s]*p[\\\\\s]*t[\\\\\s]*):#i', '${1};', $val);

    // Behavior protocol handler
    $val = preg_replace('#(b[\\\\\s]*e[\\\\\s]*h[\\\\\s]*a[\\\\\s]*v[\\\\\s]*i[\\\\\s]*o[\\\\\s]*r[\\\\\s]*):#i', '${1};', $val);

    // Event vectors (anything that *might* have got into a tag context, or out of an attribute context, that looks like it could potentially be a JS attribute -- intentionally broad as invalid-but-working HTML can trick regexps)
    do {
        $before = $val;
        $val = preg_replace('#([<"\'].*\s)o([nN])(.*=)#s', '${1}&#111;${2}${3}', $val);
        $val = preg_replace('#([<"\'].*\s)O([nN])(.*=)#s', '${1}&#79;${2}${3}', $val);
    } while ($before != $val);

    if ($lite) {
        return;
    }

    // nofollow needs applying
    $val = preg_replace_callback('#(<a\s[^<>]*)(>)(.*)(</a>)#Ui', '_link_nofollow_callback', $val);

    // Check tag balancing (we don't want to allow partial tags to compound together against separately checked chunks)
    $len = strlen($val);
    $depth = 0;
    for ($i = 0; $i < $len; $i++) {
        $at = $val[$i];
        if ($at == '<') {
            $depth++;
        } elseif ($at == '>') {
            $depth--;
        }
        if ($depth < 0) {
            break;
        }
    }
    if ($depth >= 1) {
        $val .= '">'; // Ugly way to make sure all is closed off
    }
}

/**
 * Filter to alter form field values based on fields.xml. Usually a no-op.
 *
 * @param  string $name The name of the parameter
 * @param  ?string $val The current value of the parameter (null: none)
 * @param  boolean $live Whether it is running live rather than from some hard-coded value
 * @return string The filtered value of the parameter
 */
function filter_form_field_default($name, $val, $live = false)
{
    // Read in a default parameter from the GET environment, if this feature is enabled.
    global $URL_DEFAULT_PARAMETERS_ENABLED;
    if ($URL_DEFAULT_PARAMETERS_ENABLED) {
        inform_non_canonical_parameter($name);

        $_val = get_param_string($name, null, INPUT_FILTER_GET_COMPLEX);
        if ($_val !== null) {
            $val = $_val;
        }
    }

    global $FIELD_RESTRICTIONS;
    if ($FIELD_RESTRICTIONS === null) {
        $restrictions = load_field_restrictions();
    } else {
        $restrictions = $FIELD_RESTRICTIONS;
    }

    foreach ($restrictions as $_r => $_restrictions) {
        $_r_exp = explode(',', $_r);
        foreach ($_r_exp as $__r) {
            if ((trim($__r) == '') || (simulated_wildcard_match($name, trim($__r), true))) {
                foreach ($_restrictions as $bits) {
                    list($restriction, $attributes) = $bits;

                    if ((isset($attributes['error'])) && (substr($attributes['error'], 0, 1) == '!')) {
                        $attributes['error'] = do_lang(substr($attributes['error'], 1));
                    }

                    switch (strtolower($restriction)) {
                        case 'minlength':
                            if ($live && strlen($val) < intval($attributes['embed'])) {
                                warn_exit(array_key_exists('error', $attributes) ? make_string_tempcode($attributes['error']) : do_lang_tempcode('FXML_FIELD_TOO_SHORT', escape_html($name), strval(intval($attributes['embed']))));
                            }
                            break;

                        case 'maxlength':
                            if ($live && strlen($val) > intval($attributes['embed'])) {
                                warn_exit(array_key_exists('error', $attributes) ? make_string_tempcode($attributes['error']) : do_lang_tempcode('FXML_FIELD_TOO_LONG', escape_html($name), strval(intval($attributes['embed']))));
                            }
                            break;

                        case 'shun':
                            if ($live && simulated_wildcard_match(strtolower($val), strtolower($attributes['embed']), true)) {
                                warn_exit(array_key_exists('error', $attributes) ? make_string_tempcode($attributes['error']) : do_lang_tempcode('FXML_FIELD_SHUNNED', escape_html($name)));
                            }
                            break;

                        case 'pattern':
                            if ($live && preg_match('#' . str_replace('#', '\#', $attributes['embed']) . '#', $val) == 0) {
                                warn_exit(array_key_exists('error', $attributes) ? make_string_tempcode($attributes['error']) : do_lang_tempcode('FXML_FIELD_PATTERN_FAIL', escape_html($name), escape_html($attributes['embed'])));
                            }
                            break;

                        case 'possibilityset':
                            $values = explode(',', $attributes['embed']);
                            $found = false;
                            foreach ($values as $value) {
                                if (($val == trim($value)) || ($val == $value) || (simulated_wildcard_match($val, $value, true))) {
                                    $found = true;
                                }
                            }
                            $secretive = (array_key_exists('secretive', $attributes) && ($attributes['secretive'] == '1'));
                            if (!$found) {
                                if ($live) {
                                    warn_exit(array_key_exists('error', $attributes) ? make_string_tempcode($attributes['error']) : do_lang_tempcode($secretive ? 'FXML_FIELD_NOT_IN_SET_SECRETIVE' : 'FXML_FIELD_NOT_IN_SET', escape_html($name), escape_html($attributes['embed'])));
                                }
                            }
                            break;

                        case 'disallowedsubstring':
                            if ($live && simulated_wildcard_match(strtolower($val), strtolower($attributes['embed']))) {
                                warn_exit(array_key_exists('error', $attributes) ? make_string_tempcode($attributes['error']) : do_lang_tempcode('FXML_FIELD_SHUNNED_SUBSTRING', escape_html($name), escape_html($attributes['embed'])));
                            }
                            break;

                        case 'disallowedword':
                            if (addon_installed('wordfilter')) {
                                global $WORDS_TO_FILTER_CACHE;
                                $temp_remember = $WORDS_TO_FILTER_CACHE;
                                $WORDS_TO_FILTER_CACHE = array($attributes['embed'] => array('word' => $attributes['embed'], 'w_replacement' => '', 'w_substr' => 0));
                                require_code('wordfilter');
                                check_wordfilter($val, $name, true, true, false);
                                $WORDS_TO_FILTER_CACHE = $temp_remember;
                            } else {
                                if (($live) && (strpos($val, $attributes['embed']) !== false)) {
                                    warn_exit_wordfilter($name, do_lang_tempcode('WORDFILTER_YOU', escape_html($attributes['embed']))); // In soviet Russia, words filter you
                                }
                            }
                            break;

                        case 'replace':
                            if (!array_key_exists('from', $attributes)) {
                                $val = $attributes['embed'];
                            } else {
                                $val = str_replace($attributes['from'], $attributes['embed'], $val);
                            }
                            break;

                        case 'deepclean':
                            require_code('deep_clean');
                            $val = deep_clean($val, isset($attributes['title']) ? $attributes['title'] : '');
                            break;

                        case 'removeshout':
                            $val = preg_replace_callback('#[^a-z]*[A-Z]{4}[^a-z]*#', 'deshout_callback', $val);
                            break;

                        case 'sentencecase':
                            if (strlen($val) != 0) {
                                $val = strtolower($val);
                                $val[0] = strtoupper($val); // assumes no leading whitespace
                                $val = preg_replace_callback('#[\.\!\?]\s+[a-z]#m', 'make_sentence_case_callback', $val);
                            }
                            break;

                        case 'titlecase':
                            $val = ucwords(strtolower($val));
                            break;

                        case 'prepend':
                            if (substr($val, 0, strlen($attributes['embed'])) != $attributes['embed']) {
                                $val = $attributes['embed'] . $val;
                            }
                            break;

                        case 'append':
                            if (substr($val, -strlen($attributes['embed'])) != $attributes['embed']) {
                                $val .= $attributes['embed'];
                            }
                            break;
                    }
                }
            }
        }
    }

    return $val;
}

/**
 * preg_replace callback to apply sentence case.
 *
 * @param  array $matches Matches
 * @return string De-shouted string
 */
function make_sentence_case_callback($matches)
{
    return strtoupper($matches[0]);
}

/**
 * preg_replace callback to de-shout text.
 *
 * @param  array $matches Matches
 * @return string De-shouted string
 */
function deshout_callback($matches)
{
    return ucwords(strtolower($matches[0]));
}

/**
 * Find all restrictions that apply to our page/type.
 *
 * @param  ?string $this_page The page name scoped for (null: current page)
 * @param  ?string $this_type The page type scoped for (null: current type)
 * @return array List of fields, each of which is a map (restriction => attributes)
 */
function load_field_restrictions($this_page = null, $this_type = null)
{
    global $FIELD_RESTRICTIONS;
    if ($FIELD_RESTRICTIONS === null) {
        $FIELD_RESTRICTIONS = array();
        if (function_exists('xml_parser_create')) {
            $temp = new Field_restriction_loader();
            if ($this_page === null) {
                $this_page = get_page_name();
            }
            if ($this_type === null) {
                $this_type = get_param_string('type', array_key_exists('type', $_POST) ? $_POST['type'] : 'browse');
            }
            $temp->this_page = $this_page;
            $temp->this_type = $this_type;
            $temp->go();
        }
    }

    return $FIELD_RESTRICTIONS;
}

/**
 * Field restriction loader.
 *
 * @package    core
 */
class Field_restriction_loader
{
    // Used during parsing
    public $tag_stack, $attribute_stack, $text_so_far;
    public $this_page, $this_type;
    public $levels_from_filtered;
    public $field_qualification_stack;

    /**
     * Run the loader, to load up field-restrictions from the XML file.
     */
    public function go()
    {
        if (!addon_installed('xml_fields')) {
            return;
        }
        if (!is_file(get_file_base() . '/data/xml_config/fields.xml') && !is_file(get_custom_file_base() . '/data_custom/xml_config/fields.xml')) {
            return;
        }

        $this->tag_stack = array();
        $this->attribute_stack = array();
        $this->levels_from_filtered = 0;
        $this->field_qualification_stack = array('*');

        // Create and setup our parser
        if (function_exists('libxml_disable_entity_loader')) {
            libxml_disable_entity_loader();
        }
        $xml_parser = @xml_parser_create(get_charset());
        if ($xml_parser === false) {
            return; // PHP5 default build on windows comes with this function disabled, so we need to be able to escape on error
        }
        xml_set_object($xml_parser, $this);
        @xml_parser_set_option($xml_parser, XML_OPTION_TARGET_ENCODING, get_charset());
        xml_set_element_handler($xml_parser, 'startElement', 'endElement');
        xml_set_character_data_handler($xml_parser, 'startText');

        // Run the parser
        $data = cms_file_get_contents_safe(is_file(get_custom_file_base() . '/data_custom/xml_config/fields.xml') ? (get_custom_file_base() . '/data_custom/xml_config/fields.xml') : (get_file_base() . '/data/xml_config/fields.xml'));
        if (trim($data) == '') {
            return;
        }
        if (@xml_parse($xml_parser, $data, true) == 0) {
            attach_message('fields.xml: ' . xml_error_string(xml_get_error_code($xml_parser)), 'warn', false, true);
            return;
        }
        @xml_parser_free($xml_parser);
    }

    /**
     * Standard PHP XML parser function.
     *
     * @param  object $parser The parser object (same as 'this')
     * @param  string $tag The name of the element found
     * @param  array $_attributes Array of attributes of the element
     */
    public function startElement($parser, $tag, $_attributes)
    {
        array_push($this->tag_stack, $tag);
        $attributes = array();
        foreach ($_attributes as $key => $val) {
            $attributes[strtolower($key)] = $val;
        }
        array_push($this->attribute_stack, $attributes);

        switch (strtolower($tag)) {
            case 'qualify':
                if ($this->levels_from_filtered == 0) {
                    $applies = true;
                    if ($applies) {
                        if (array_key_exists('pages', $attributes)) {
                            $applies = false;
                            $pages = explode(',', $attributes['pages']);
                            foreach ($pages as $page) {
                                if (simulated_wildcard_match($this->this_page, trim($page), true)) {
                                    $applies = true;
                                }
                            }
                        }
                    }
                    if ($applies) {
                        if (array_key_exists('types', $attributes)) {
                            $applies = false;
                            $types = explode(',', $attributes['types']);
                            foreach ($types as $type) {
                                if (simulated_wildcard_match($this->this_type, trim($type), true)) {
                                    $applies = true;
                                }
                            }
                        }
                    }

                    if (!array_key_exists('fields', $attributes)) {
                        $attributes['fields'] = '*';
                    }
                    array_push($this->field_qualification_stack, $attributes['fields']);
                    if (!$applies) {
                        $this->levels_from_filtered = 1;
                    }
                } elseif ($this->levels_from_filtered != 0) {
                    $this->levels_from_filtered++;
                }
                break;
            case 'filter':
                if ($this->levels_from_filtered == 0) {
                    $applies = true;
                    if ((array_key_exists('notstaff', $attributes)) && ($attributes['notstaff'] == '1') && (isset($GLOBALS['FORUM_DRIVER'])) && ($GLOBALS['FORUM_DRIVER']->is_staff(get_member()))) {
                        $applies = false;
                    }
                    if ($applies) {
                        if (array_key_exists('groups', $attributes)) {
                            $applies = false;
                            $members_groups = $GLOBALS['FORUM_DRIVER']->get_members_groups(get_member());
                            $groups = explode(',', $attributes['groups']);
                            foreach ($groups as $group) {
                                if (in_array(intval(trim($group)), $members_groups)) {
                                    $applies = true;
                                }
                            }
                        }
                    }
                    if ($applies) {
                        if (array_key_exists('members', $attributes)) {
                            $applies = false;
                            $members = explode(',', $attributes['members']);
                            foreach ($members as $member_id) {
                                if (intval(trim($member_id)) == get_member()) {
                                    $applies = true;
                                }
                            }
                        }
                    }

                    if (!$applies) {
                        $this->levels_from_filtered = 1;
                    }
                } elseif ($this->levels_from_filtered != 0) {
                    $this->levels_from_filtered++;
                }
                break;
            default:
                if ($this->levels_from_filtered != 0) {
                    $this->levels_from_filtered++;
                }
                break;
        }
        $this->text_so_far = '';
    }

    /**
     * Standard PHP XML parser function.
     *
     * @param  object $parser The parser object (same as 'this')
     */
    public function endElement($parser)
    {
        $text = str_replace('\n', "\n", $this->text_so_far);
        $tag = array_pop($this->tag_stack);
        $attributes = array_pop($this->attribute_stack);

        switch (strtolower($tag)) {
            case 'qualify':
                array_pop($this->field_qualification_stack);
                break;
            case 'filter':
                break;
            default:
                if ($this->levels_from_filtered == 0) {
                    global $FIELD_RESTRICTIONS;
                    $qualifier = array_peek($this->field_qualification_stack);
                    if (!array_key_exists($qualifier, $FIELD_RESTRICTIONS)) {
                        $FIELD_RESTRICTIONS[$qualifier] = array();
                    }
                    $FIELD_RESTRICTIONS[$qualifier][] = array($tag, array_merge(array('embed' => $text), $attributes));
                }
                break;
        }

        if ($this->levels_from_filtered != 0) {
            $this->levels_from_filtered--;
        }
    }

    /**
     * Standard PHP XML parser function.
     *
     * @param  object $parser The parser object (same as 'this')
     * @param  string $data The text
     */
    public function startText($parser, $data)
    {
        $this->text_so_far .= $data;
    }
}
