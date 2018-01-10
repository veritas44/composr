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

/*
This script defines the rewrite rules from Composr's end.

Also see build_rewrite_rules.php for web-server script file generation [creates files like recommended.htaccess] (and to a lesser extent, urls.php and urls2.php).
build_rewrite_rules.php is in git / the composr_release_build addon.
*/

/**
 * Find the list of URL remappings.
 *
 * @param  ID_TEXT $url_scheme The URL scheme to use
 * @return array The list of URL remappings
 */
function get_remappings($url_scheme)
{
    // The target mapping... upper case means variable substitution, lower case means constant-string
    // The source mapping... null means 'anything' (we'll use it in a variable substitution), else we require a certain value
    // These have to be in longest to shortest number of bindings order, to reduce the potential for &'d attributes

    $rules = array();
    switch ($url_scheme) {
        case 'PG':
            if (addon_installed('wiki')) {
                $rules[] = array(array('page' => 'wiki', 'type' => 'browse', 'id' => null), 'pg/s/ID', false);
            }
            $rules[] = array(array('page' => null, 'type' => null, 'id' => null), 'pg/PAGE/TYPE/ID', false);
            $rules[] = array(array('page' => null, 'type' => null), 'pg/PAGE/TYPE', false);
            $rules[] = array(array('page' => null), 'pg/PAGE', false);
            $rules[] = array(array('page' => ''), 'pg', false);
            $rules[] = array(array(), 'pg', true);
            break;

        case 'HTM':
            if (addon_installed('wiki')) {
                $rules[] = array(array('page' => 'wiki', 'type' => 'browse', 'id' => null), 's/ID.htm', false);
            }
            $rules[] = array(array('page' => null, 'type' => null, 'id' => null), 'PAGE/TYPE/ID.htm', false);
            $rules[] = array(array('page' => null, 'type' => null), 'PAGE/TYPE.htm', false);
            $rules[] = array(array('page' => null), 'PAGE.htm', false);
            $rules[] = array(array('page' => ''), '', false);
            $rules[] = array(array(), '', false);
            break;

        case 'SIMPLE':
            if (addon_installed('wiki')) {
                $rules[] = array(array('page' => 'wiki', 'type' => 'browse', 'id' => null), 's/ID', false);
            }
            $rules[] = array(array('page' => null, 'type' => null, 'id' => null), 'PAGE/TYPE/ID', false);
            $rules[] = array(array('page' => null, 'type' => 'browse'), 'PAGE', false);
            $rules[] = array(array('page' => null, 'type' => null), 'PAGE/TYPE', false);
            $rules[] = array(array('page' => null), 'PAGE', false);
            $rules[] = array(array('page' => ''), '', false);
            $rules[] = array(array(), '', false);
            break;
    }

    return $rules;
}
