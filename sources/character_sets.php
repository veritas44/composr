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

/*EXTRA FUNCTIONS: iconv\_.+*/

/**
 * Performs lots of magic to make sure data encodings are converted correctly. Input, and output too (as often stores internally in UTF or performs automatic dynamic conversions from internal to external charsets).
 *
 * @param  boolean $known_utf8 Whether we know we are working in utf-8. This is the case for AJAX calls.
 *
 * @ignore
 */
function _convert_request_data_encodings($known_utf8 = false)
{
    global $VALID_ENCODING, $CONVERTED_ENCODING;

    if ((array_key_exists('KNOWN_UTF8', $GLOBALS)) && ($GLOBALS['KNOWN_UTF8'])) {
        $known_utf8 = true;
    }

    $internal_charset = get_charset();

    $done_something = false;

    // Conversion of parameters that might be in the wrong character encoding (e.g. JavaScript uses UTF to make requests regardless of document encoding, so the stuff needs converting)
    //  If we don't have any PHP extensions (mbstring etc) that can perform the detection/conversion, our code will take this into account and use utf8_decode at points where it knows that it's being communicated with by JavaScript.

    $input_charset = $known_utf8 ? 'utf-8' : get_charset();

    if ((strtolower($input_charset) == 'utf-8') && /*test method works...*/(_will_be_successfully_unicode_neutered(serialize($_GET) . serialize($_POST))) && (in_array(strtoupper($internal_charset), array('ISO-8859-1', 'ISO-8859-15', 'KOI8-R', 'BIG5', 'GB2312', 'BIG5-HKSCS', 'SHIFT_JIS', 'EUC-JP')))) {
        // Inbuilt PHP option, but only supports certain character sets.
        // Preferred as it will sub entities where there's no equivalent character.

        foreach ($_GET as $key => $val) {
            if (is_string($val)) {
                $test = entity_utf8_decode($val, $input_charset);
                if ($test !== false) {
                    $_GET[$key] = $test;
                }
            } elseif (is_array($val)) {
                foreach ($val as $i => $v) {
                    $test = entity_utf8_decode($v, $input_charset);
                    if ($test !== false) {
                        $_GET[$key][$i] = $test;
                    }
                }
            }
        }
        foreach ($_POST as $key => $val) {
            if (is_string($val)) {
                $test = entity_utf8_decode($val, $input_charset);
                if ($test !== false) {
                    $_POST[$key] = $test;
                }
            } elseif (is_array($val)) {
                foreach ($val as $i => $v) {
                    $test = entity_utf8_decode($v, $input_charset);
                    if ($test !== false) {
                        $_POST[$key][$i] = $test;
                    }
                }
            }
        }
        foreach ($_FILES as $key => $val) {
            $test = entity_utf8_decode($val['name'], $input_charset);
            if ($test !== false) {
                $_FILES[$key]['name'] = $test;
            }
        }

        $CONVERTED_ENCODING = true;
        return;
    }

    if ((strtolower($input_charset) == 'utf-8') && (strtoupper($internal_charset) == 'ISO-8859-1')) {
        // utf8_decode (mail extension) option. Imperfect as it needs utf-8 vs ISO-8859-1.

        foreach ($_GET as $key => $val) {
            if (is_string($val)) {
                $_GET[$key] = utf8_decode($val);
            } elseif (is_array($val)) {
                foreach ($val as $i => $v) {
                    $_GET[$key][$i] = utf8_decode($v);
                }
            }
        }
        foreach ($_POST as $key => $val) {
            if (is_string($val)) {
                $_POST[$key] = utf8_decode($val);
            } elseif (is_array($val)) {
                foreach ($val as $i => $v) {
                    $_POST[$key][$i] = utf8_decode($v);
                }
            }
        }
        foreach ($_FILES as $key => $val) {
            $_FILES[$key]['name'] = utf8_decode($val['name']);
        }

        $CONVERTED_ENCODING = true;
        return;
    }

    if ((strtoupper($input_charset) == 'ISO-8859-1') && ($internal_charset == 'utf-8')) {
        // utf8_encode (mail extension) option. Imperfect as it needs utf-8 vs ISO-8859-1.

        foreach ($_GET as $key => $val) {
            if (is_string($val)) {
                $_GET[$key] = utf8_encode($val);
            } elseif (is_array($val)) {
                foreach ($val as $i => $v) {
                    $_GET[$key][$i] = utf8_encode($v);
                }
            }
        }
        foreach ($_POST as $key => $val) {
            if (is_string($val)) {
                $_POST[$key] = utf8_encode($val);
            } elseif (is_array($val)) {
                foreach ($val as $i => $v) {
                    $_POST[$key][$i] = utf8_encode($v);
                }
            }
        }
        foreach ($_FILES as $key => $val) {
            $_FILES[$key]['name'] = utf8_encode($val['name']);
        }

        $CONVERTED_ENCODING = true;
        return;
    }

    if ((function_exists('iconv')) && (get_value('disable_iconv') !== '1')) {
        // iconv option

        if (!function_exists('iconv_set_encoding') || @iconv_set_encoding('input_encoding', $input_charset)) {
            if (function_exists('iconv_set_encoding')) {
                @iconv_set_encoding('output_encoding', $internal_charset);
                @iconv_set_encoding('internal_encoding', $internal_charset);
            }

            foreach ($_GET as $key => $val) {
                if (is_string($val)) {
                    $val = @iconv($input_charset, $internal_charset . '//TRANSLIT', $val);
                    if ($val === false) {
                        $val = @iconv($input_charset, $internal_charset . '//IGNORE', $val);
                    }
                    $_GET[$key] = $val;
                } elseif (is_array($val)) {
                    foreach ($val as $i => $v) {
                        $v = @iconv($input_charset, $internal_charset . '//TRANSLIT', $v);
                        if ($v === false) {
                            $v = @iconv($input_charset, $internal_charset . '//IGNORE', $v);
                        }
                        $_GET[$key][$i] = $v;
                    }
                }
            }
            foreach ($_POST as $key => $val) {
                if (is_string($val)) {
                    $val = @iconv($input_charset, $internal_charset . '//TRANSLIT', $val);
                    if ($val === false) {
                        $val = @iconv($input_charset, $internal_charset . '//IGNORE', $val);
                    }
                    $_POST[$key] = $val;
                } elseif (is_array($val)) {
                    foreach ($val as $i => $v) {
                        $v = @iconv($input_charset, $internal_charset . '//TRANSLIT', $v);
                        if ($v === false) {
                            $v = @iconv($input_charset, $internal_charset . '//IGNORE', $v);
                        }
                        $_POST[$key][$i] = $v;
                    }
                }
            }
        } else {
            $VALID_ENCODING = false;
        }

        $CONVERTED_ENCODING = true;
        return;
    }

    if ((function_exists('mb_convert_encoding')) && (get_value('disable_mbstring') !== '1')) {
        // mbstring option

        if (function_exists('mb_list_encodings')) {
            $VALID_ENCODING = in_array(strtolower($internal_charset), array_map('strtolower', mb_list_encodings()));
        } else {
            $VALID_ENCODING = true;
        }

        if ($VALID_ENCODING) {
            $input_charset = $known_utf8 ? 'utf-8' : '';
            if ((function_exists('mb_http_input')) && ($input_charset == '')) {
                if (count($_POST) != 0) {
                    $input_charset = mb_http_input('P');
                    if ((!is_string($input_charset)) || ($input_charset == 'pass')) {
                        $input_charset = '';
                    }
                }
            }
            if ((function_exists('mb_http_input')) && ($input_charset == '')) {
                $input_charset = mb_http_input('G');
                if ((!is_string($input_charset)) || ($input_charset == 'pass')) {
                    $input_charset = '';
                }
                if ((function_exists('mb_detect_encoding')) && ($input_charset == '') && ($_SERVER['REQUEST_URI'] != '')) {
                    $input_charset = mb_detect_encoding(urldecode($_SERVER['REQUEST_URI']), $internal_charset . ',utf-8,ISO-8859-1');
                    if ((!is_string($input_charset)) || ($input_charset == 'pass')) {
                        $input_charset = '';
                    }
                }
            }

            if ($input_charset != '') {
                foreach ($_GET as $key => $val) {
                    if (is_string($val)) {
                        $_GET[$key] = mb_convert_encoding($val, $internal_charset, $input_charset);
                    } elseif (is_array($val)) {
                        foreach ($val as $i => $v) {
                            $_GET[$key][$i] = mb_convert_encoding($v, $internal_charset, $input_charset);
                        }
                    }
                }
                foreach ($_POST as $key => $val) {
                    if (is_string($val)) {
                        $_POST[$key] = mb_convert_encoding($val, $internal_charset, $input_charset);
                    } elseif (is_array($val)) {
                        foreach ($val as $i => $v) {
                            $_POST[$key][$i] = mb_convert_encoding($v, $internal_charset, $input_charset);
                        }
                    }
                }
                foreach ($_FILES as $key => $val) {
                    $_FILES[$key]['name'] = mb_convert_encoding($val['name'], $internal_charset, $input_charset);
                }
            }

            if (function_exists('mb_http_output')) {
                mb_http_output($internal_charset);
            }
        }

        $CONVERTED_ENCODING = true;
        return;
    }
}

/**
 * Convert some data from one encoding to the internal encoding.
 *
 * @param  string $data Data to convert
 * @param  string $input_charset Charset to convert from
 * @param  ?string $internal_charset Charset to convert to (null: current encoding)
 * @return string Converted data
 */
function convert_to_internal_encoding($data, $input_charset, $internal_charset = null)
{
    if ($internal_charset === null) {
        $internal_charset = get_charset();
    }

    if (preg_match('#^[\x00-\x7f]$#', $data) != 0) { // All ASCII
        return $data;
    }

    if (($input_charset === '') || ($input_charset === null)) {
        // Unknown, can't process
        return $data;
    }

    if (strtolower($input_charset) == strtolower($internal_charset)) {
        // No change needed
        return $data;
    }

    if ($data == '') {
        // No change needed
        return $data;
    }

    global $VALID_ENCODING;

    convert_request_data_encodings(); // In case it hasn't run yet. We need $VALID_ENCODING to be set.

    if ((strtolower($input_charset) == 'utf-8') && /*test method works...*/(_will_be_successfully_unicode_neutered($data)) && (in_array(strtoupper($internal_charset), array('ISO-8859-1', 'ISO-8859-15', 'KOI8-R', 'BIG5', 'GB2312', 'BIG5-HKSCS', 'SHIFT_JIS', 'EUC-JP')))) { // Preferred as it will use sub entities where there's no equivalent character
        // Inbuilt PHP option, but only supports certain character sets.
        // Preferred as it will sub entities where there's no equivalent character.

        $test = entity_utf8_decode($data, $internal_charset);
        if ($test !== false) {
            return $test;
        }
    }

    if ((strtolower($input_charset) == 'utf-8') && (strtoupper($internal_charset) == 'ISO-8859-1')) {
        // utf8_decode (mail extension) option. Imperfect as it needs utf-8 vs ISO-8859-1.

        $test = @utf8_decode($data);
        if ($test !== false) {
            return $test;
        }
    }

    if ((strtoupper($input_charset) == 'ISO-8859-1') && (strtolower($internal_charset) == 'utf-8')) {
        // utf8_encode (mail extension) option. Imperfect as it needs utf-8 vs ISO-8859-1.

        $test = @utf8_encode($data);
        if ($test !== false) {
            return $test;
        }
    }

    if ((function_exists('iconv')) && ($VALID_ENCODING) && (get_value('disable_iconv') !== '1')) {
        // iconv option

        $test = @iconv($input_charset, $internal_charset . '//TRANSLIT', $data);
        if (empty($test)) {
            $test = @iconv($input_charset, $internal_charset . '//IGNORE', $data);
        }
        if (!empty($test)) {
            return $test;
        }
    }

    if ((function_exists('mb_convert_encoding')) && ($VALID_ENCODING) && (get_value('disable_mbstring') !== '1')) {
        // mbstring option

        if (function_exists('mb_list_encodings')) {
            static $good_encodings = array();
            if (!isset($good_encodings[$input_charset])) {
                $good_encodings[$input_charset] = (in_array(strtolower($input_charset), array_map('strtolower', mb_list_encodings())));
            }
            $good_encoding = $good_encodings[$input_charset];
        } else {
            $good_encoding = true;
        }

        if ($good_encoding) {
            $test = @mb_convert_encoding($data, $internal_charset, $input_charset);
            if ($test !== false) {
                return $test;
            }
        }
    }

    return $data;
}

/**
 * Guard for before calling entity_utf8_decode.
 * Checks that the data can be stripped so there is no unicode left. Either the htmlentities function must convert mechanically to entity-characters or all higher ascii character codes (which are actually unicode control codes in a unicode interpretation) that are used happen to be linked to named entities.
 * PHP's utf-8 support may not be great. For example, we have seen emoji characters not converting.
 *
 * @param  string $data Data to check
 * @return boolean Whether we are good to execute entity_utf8_decode
 */
function _will_be_successfully_unicode_neutered($data)
{
    $data = @htmlentities($data, ENT_COMPAT, 'utf-8');
    if ($data == '') {
        return false; // Some servers fail at the first step
    }
    for ($i = 0; $i < strlen($data); $i++) {
        if (ord($data[$i]) > 0x7F) {
            return false;
        }
    }
    return true;
}

/**
 * Convert some data from utf-8 to a character set PHP supports, using HTML entities where there's no direct match.
 *
 * @param  string $data Data to convert
 * @param  string $internal_charset Charset to convert to
 * @return ~string Converted data (false: could not convert)
 */
function entity_utf8_decode($data, $internal_charset)
{
    // Encode to create entities for difficult characters
    $encoded = htmlentities($data, ENT_COMPAT, 'utf-8'); // Only works on some servers, which is why we test the utility of it before running this function. NB: It is fine that this will double encode any pre-existing entities- as the double encoding will trivially be undone again later (amp can always decode to a lower ascii character)
    if ((strlen($encoded) == 0) && ($data != '')) {
        $encoded = htmlentities($data, ENT_COMPAT);
    }

    // Decode so any non-difficult characters come back
    $test = mixed();
    $test = @html_entity_decode($encoded, ENT_COMPAT, $internal_charset); // this is nice because it will leave equivalent entities where it can't get a character match; Comcode supports those entities
    if ((strlen($test) == 0) && ($data != '')) {
        $test = false;
    }

    if ($test === false) {
        // Alternative decoding method, character-by-character substitution...

        $test = preg_replace_callback('/&#x([0-9a-f]+);/i', '_unichrm_hex', $encoded); // imperfect as it can only translate lower ascii back, but better than nothing. htmlentities would have encoded key other ones as named entities though which get_html_translation_table can handle
        $test = preg_replace_callback('/&#([0-9]+);/', '_unichrm', $test); // imperfect as it can only translate lower ascii back, but better than nothing. htmlentities would have encoded key other ones as named entities though which get_html_translation_table can handle

        if (strtoupper($internal_charset) == 'ISO-8859-1') { // trans table only valid for this charset. Else we just need to live with things getting turned into named entities. However we don't allow this function to be called if this code branch would be skipped here.
            require_code('xml');
            $test2 = convert_bad_entities($test, $internal_charset);
            if ((strlen($test2) != 0) || ($data == '')) {
                $test = $test2;
            }
        }
    }

    if ($test === false) {
        return false;
    }

    $data = $test;

    // We'd rather have text-code than entities
    $shortcuts = array('(EUR-)' => '&euro;', '{f.}' => '&fnof;', '-|-' => '&dagger;', '=|=' => '&Dagger;', '{%o}' => '&permil;', '{~S}' => '&Scaron;', '{~Z}' => '&#x17D;', '(TM)' => '&trade;', '{~s}' => '&scaron;', '{~z}' => '&#x17E;', '{.Y.}' => '&Yuml;', '(c)' => '&copy;', '(r)' => '&reg;', '---' => '&mdash;', '--' => '&ndash;', '...' => '&hellip;', '==>' => '&rarr;', '<==' => '&larr;');
    foreach ($shortcuts as $to => $from) {
        $data = str_replace($from, $to, $data);
    }

    return $data;
}

/**
 * Convert a unicode character number to a utf-8 HTML-entity enabled string. Callback for preg_replace.
 *
 * @param  array $matches Regular expression match array
 * @return ~string Converted data (false: could not convert)
 */
function _unichrm_hex($matches)
{
    return _unichr(hexdec($matches[1]));
}

/**
 * Convert a unicode character number to a utf-8 HTML-entity enabled string. Callback for preg_replace.
 *
 * @param  array $matches Regular expression match array
 * @return ~string Converted data (false: could not convert)
 */
function _unichrm($matches)
{
    return _unichr(intval($matches[1]));
}

/**
 * Convert a unicode character number to a HTML-entity enabled string, using lower ASCII characters where possible.
 *
 * @param  integer $c Character number
 * @return ~string Converted data (false: could not convert)
 */
function _unichr($c)
{
    if ($c <= 0x7F) {
        return chr($c);
    } else {
        return '#&' . strval($c) . ';';
    }
}

/**
 * Turn character set characters into HTML entities. Useful as GD truetype functions need this as they can only process ASCII + entities. Based on function in PHP code comments.
 * Don't use this for stuff other than GD as it is very severe in its use of HTML entities.
 *
 * @param  string $text Input
 * @return string Output
 */
function convert_to_html_encoding($text)
{
    $text = convert_to_internal_encoding($text, get_charset(), 'utf-8');

    $result = '';
    $len = strlen($text);
    for ($i = 0; $i < $len; $i++) {
        $char = $text[$i];
        $ascii = ord($char);
        if ($ascii < 128) {
            // one-byte character
            $result .= $char;
        } elseif ($ascii < 192) {
            // non-utf8 character or not a start byte
        } elseif ($ascii < 224) {
            // two-byte character
            $ascii1 = ord($text[$i + 1]);
            $unicode = (63 & $ascii) * 64 +
                       (63 & $ascii1);
            $result .= '&#' . strval($unicode) . ';';
            $i += 1;
        } elseif ($ascii < 240) {
            // three-byte character
            $ascii1 = ord($text[$i + 1]);
            $ascii2 = ord($text[$i + 2]);
            $unicode = (15 & $ascii) * 4096 +
                       (63 & $ascii1) * 64 +
                       (63 & $ascii2);
            $result .= '&#' . strval($unicode) . ';';
            $i += 2;
        } elseif ($ascii < 248) {
            // four-byte character
            $ascii1 = ord($text[$i + 1]);
            $ascii2 = ord($text[$i + 2]);
            $ascii3 = ord($text[$i + 3]);
            $unicode = (15 & $ascii) * 262144 +
                       (63 & $ascii1) * 4096 +
                       (63 & $ascii2) * 64 +
                       (63 & $ascii3);
            $result .= '&#' . strval($unicode) . ';';
            $i += 3;
        }
    }
    return $result;
}
