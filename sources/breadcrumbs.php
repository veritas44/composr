<?php /*

 Composr
 Copyright (c) ocProducts, 2004-2018

 See text/EN/licence.txt for full licencing information.


 NOTE TO PROGRAMMERS:
   Do not edit this file. If you need to make changes, save your changed file to the appropriate *_custom folder
   **** If you ignore this advice, then your website upgrades (e.g. for bug fixes) will likely kill your changes ****

*/

/*EXTRA FUNCTIONS: xml_.**/

/**
 * @license    http://opensource.org/licenses/cpal_1.0 Common Public Attribution License
 * @copyright  ocProducts Ltd
 * @package    breadcrumbs
 */

/**
 * Load all breadcrumb substitutions and return them.
 *
 * @param  array $segments The default breadcrumb segments
 * @return array The adjusted breadcrumb segments
 */
function load_breadcrumb_substitutions($segments)
{
    // Works by going through in left-to-right order, doing multiple sweeps until no more substitutions can be made.
    // Only one substitution per rule is allowed.

    static $substitutions = null;
    if ($substitutions === null) {
        $substitutions = persistent_cache_get('BREADCRUMBS_CACHE');
    }
    if ($substitutions === null) {
        $data = @cms_file_get_contents_safe(get_custom_file_base() . '/data_custom/xml_config/breadcrumbs.xml');
        if ($data === false) {
            $data = @cms_file_get_contents_safe(get_file_base() . '/data/xml_config/breadcrumbs.xml');
        }
        if ($data === false) {
            $data = '';
        }

        if (trim($data) == '') {
            persistent_cache_set('BREADCRUMBS_CACHE', array());

            return $segments;
        }

        $loader = new Breadcrumb_substitution_loader();
        $loader->go($data);
        $substitutions = $loader->substitutions;

        persistent_cache_set('BREADCRUMBS_CACHE', $substitutions);
    }

    $segments_new = array();
    $done_one = false;
    $final = false;

    foreach ($segments as $i => $segment) { // Loop by active breadcrumb segments
        $include_self = true;

        if (!$done_one && $segment[0] !== '') {
            if ($segment[0] === null) {
                list($segment_zone, $segment_attributes, $segment_hash) = array(null, null, null); // active page
            } else {
                list($segment_zone, $segment_attributes, $segment_hash) = page_link_decode($segment[0]);
            }

            foreach ($substitutions as $j => $substitution_details) { // Loop by substitutions
                if ($substitution_details !== null) {
                    list($substitution_match_key, $substitution_label, $substitution_links, $substitution_include_self, $substitution_final) = $substitution_details;

                    if ($segment[0] === null) {
                        $does_match = match_key_match($substitution_match_key, false);
                    } else {
                        if (($substitution_match_key[0][0] == 'site') && ($segment_zone == '') || ($substitution_match_key[0][0] == '') && ($segment_zone == 'site')) {
                            // Special handling, we don't want single public zone option (collapse_user_zones) to be too "smart" and apply a rule intended for when that option is off
                            continue;
                        }

                        $does_match = isset($segment_attributes['page']) && match_key_match($substitution_match_key, false, $segment_attributes, $segment_zone, $segment_attributes['page']);
                    }

                    if ($does_match) {
                        if (!$done_one) {
                            // New stem found
                            $segments_new_bak = $segments_new;
                            $segments_new = array();
                            foreach ($substitution_links as $new_segment) {
                                if ((empty($new_segment[0])) && (empty($new_segment[1]))) { // <link /> indicating to keep existing links on tail, possibly new links on head
                                    $segments_new = array_merge($segments_new, $segments_new_bak);
                                } else {
                                    $segments_new[] = $new_segment;
                                }
                            }

                            $done_one = true;
                        }

                        if ($segment[0] === null) { // New label for active page specified here?
                            if ($substitution_label !== null) {
                                $GLOBALS['BREADCRUMB_SET_SELF'] = $substitution_label;
                            }
                        }

                        $substitutions[$j] = null; // Stop loops when recursing

                        if ($substitution_final) {
                            $final = true;
                        }

                        $include_self = $substitution_include_self;
                    }
                }
            }
        }

        if ($include_self) {
            $segments_new[] = $segment;
        }
    }

    if (($done_one) && (!$final)) {
        return load_breadcrumb_substitutions($segments_new); // Try a new sweep
    }

    return $segments_new;
}

/**
 * Breadcrumb composition class.
 *
 * @package    breadcrumbs
 */
class Breadcrumb_substitution_loader
{
    // Used during parsing
    private $tag_stack, $attribute_stack, $text_so_far;
    private $substitution_current_links;
    public $substitutions; // output

    /**
     * Run the loader, to load up field-restrictions from the XML file.
     *
     * @param  string $data The breadcrumb XML data
     */
    public function go($data)
    {
        $this->tag_stack = array();
        $this->attribute_stack = array();

        $this->substitution_current_match_key = null;
        $this->substitution_current_links = array();

        $this->substitutions = array();

        // Create and setup our parser
        if (function_exists('libxml_disable_entity_loader')) {
            libxml_disable_entity_loader();
        }
        $xml_parser = @xml_parser_create();
        if ($xml_parser === false) {
            return; // PHP5 default build on windows comes with this function disabled, so we need to be able to escape on error
        }
        xml_set_object($xml_parser, $this);
        @xml_parser_set_option($xml_parser, XML_OPTION_TARGET_ENCODING, get_charset());
        @xml_parser_set_option($xml_parser, XML_OPTION_CASE_FOLDING, 0);
        xml_set_element_handler($xml_parser, 'startElement', 'endElement');
        xml_set_character_data_handler($xml_parser, 'startText');

        // Run the parser
        if (@xml_parse($xml_parser, $data, true) == 0) {
            attach_message('breadcrumbs.xml: ' . xml_error_string(xml_get_error_code($xml_parser)), 'warn', false, true);
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
        $tag_attributes = array();
        foreach ($_attributes as $key => $val) {
            $tag_attributes[$key] = $val;
        }
        array_push($this->attribute_stack, $tag_attributes);

        switch ($tag) {
            case 'substitution':
                $this->substitution_current_links = array();
                break;

            case 'link':
                break;
        }
        $this->text_so_far = '';
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

    /**
     * Standard PHP XML parser function.
     *
     * @param  object $parser The parser object (same as 'this')
     */
    public function endElement($parser)
    {
        $tag = array_pop($this->tag_stack);
        $tag_attributes = array_pop($this->attribute_stack);

        switch ($tag) {
            case 'substitution':
                if (isset($tag_attributes['skip_if_single_public_zone']) && $tag_attributes['skip_if_single_public_zone'] == 'true' && get_option('collapse_user_zones') == '1') {
                    break;
                }

                $_substitution_current_match_key = isset($tag_attributes['match_key']) ? $tag_attributes['match_key'] : '_WILD:_WILD';
                //$substitution_current_match_key = page_link_decode($_substitution_current_match_key); match_key_match doesn't actually want it like this
                $substitution_current_match_key = array(explode(':', $_substitution_current_match_key));

                $this->substitutions[] = array(
                    $substitution_current_match_key,
                    isset($tag_attributes['label']) ? $tag_attributes['label'] : null,
                    $this->substitution_current_links,
                    isset($tag_attributes['include_self']) ? ($tag_attributes['include_self'] == 'true') : true,
                    isset($tag_attributes['final']) ? ($tag_attributes['final'] == 'true') : false,
                );
                break;

            case 'link':
                $page_link = trim(str_replace('\n', "\n", $this->text_so_far));
                $this->substitution_current_links[] = array(
                    $page_link,
                    isset($tag_attributes['label']) ? static_evaluate_tempcode(comcode_to_tempcode($tag_attributes['label'])) : ''
                );
                break;
        }
    }
}
