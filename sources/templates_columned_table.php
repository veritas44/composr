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
 * @package    core_abstract_interfaces
 */

/**
 * Get the Tempcode for a table header row.
 *
 * @param  array $values The array of field titles that define the entries in the table table
 * @return Tempcode The generated header
 */
function columned_table_header_row($values)
{
    $cells = new Tempcode();
    foreach ($values as $value) {
        $cells->attach(do_template('COLUMNED_TABLE_HEADER_ROW_CELL', array('_GUID' => '5002f54ccddf7259f3460d8c0759fd1a', 'VALUE' => $value)));
    }

    return do_template('COLUMNED_TABLE_HEADER_ROW', array('_GUID' => '2f4095b8d30f50f34fdd6acf8dd566b1', 'CELLS' => $cells));
}

/**
 * Get the Tempcode for a table row.
 *
 * @param  array $values The array of values that make up this row
 * @param  boolean $escape Whether to add escaping
 * @return Tempcode The generated row
 */
function columned_table_row($values, $escape)
{
    $cells = new Tempcode();
    foreach ($values as $value) {
        if (($escape) && (!is_object($value)) ) {
            $value = make_string_tempcode(escape_html(is_object($value) ? $value->evaluate() : $value));
        }

        $cells->attach(do_template('COLUMNED_TABLE_ROW_CELL', array('_GUID' => '700a982eb2262149295816ddee91b0e7', 'VALUE' => $value)));
    }

    return do_template('COLUMNED_TABLE_ROW', array('_GUID' => 'a4efacc07ecb165e37c355559f476ae9', 'CELLS' => $cells));
}
