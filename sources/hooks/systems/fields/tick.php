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
 * @package    core_fields
 */

/**
 * Hook class.
 */
class Hook_fields_tick
{
    // ==============
    // Module: search
    // ==============

    /**
     * Get special Tempcode for inputting this field.
     *
     * @param  array $field The field details
     * @return ?array Specially encoded input detail rows (null: nothing special)
     */
    public function get_search_inputter($field)
    {
        $fields = array();
        $type = '_LIST';
        $special = new Tempcode();
        $special->attach(form_input_list_entry('', get_param_string('option_' . strval($field['id']), '') == '', do_lang_tempcode('NA_EM')));
        $special->attach(form_input_list_entry('0', get_param_string('option_' . strval($field['id']), '') == '0', do_lang_tempcode('NO')));
        $special->attach(form_input_list_entry('1', get_param_string('option_' . strval($field['id']), '') == '1', do_lang_tempcode('YES')));
        $display = array_key_exists('trans_name', $field) ? $field['trans_name'] : get_translated_text($field['cf_name']); // 'trans_name' may have been set in CPF retrieval API, might not correspond to DB lookup if is an internal field
        return array('NAME' => strval($field['id']), 'DISPLAY' => $display, 'TYPE' => $type, 'SPECIAL' => $special);
    }

    /**
     * Get special SQL from POSTed parameters for this field.
     *
     * @param  array $field The field details
     * @param  integer $i We're processing for the ith row
     * @return ?array Tuple of SQL details (array: extra trans fields to search, array: extra plain fields to search, string: an extra table segment for a join, string: the name of the field to use as a title, if this is the title, extra WHERE clause stuff) (null: nothing special)
     */
    public function inputted_to_sql_for_search($field, $i)
    {
        return null;
    }

    // ===================
    // Backend: fields API
    // ===================

    /**
     * Get some info bits relating to our field type, that helps us look it up / set defaults.
     *
     * @param  ?array $field The field details (null: new field)
     * @param  ?boolean $required Whether a default value cannot be blank (null: don't "lock in" a new default value) (may be passed as false also if we want to avoid "lock in" of a new default value, but in this case possible cleanup of $default may still happen where appropriate)
     * @param  ?string $default The given default value as a string (null: don't "lock in" a new default value) (blank: only "lock in" a new default value if $required is true)
     * @return array Tuple of details (row-type,default-value-to-use,db row-type)
     */
    public function get_field_value_row_bits($field, $required = null, $default = null)
    {
        if ($required !== null) {
            if (($required) && ($default == '')) {
                $default = '0';
            }
        }
        return array('integer_unescaped', $default, 'integer');
    }

    /**
     * Convert a field value to something renderable.
     *
     * @param  array $field The field details
     * @param  mixed $ev The raw value
     * @return mixed Rendered field (Tempcode or string)
     */
    public function render_field_value($field, $ev)
    {
        if (is_object($ev)) {
            if ($ev->evaluate() != do_lang('NA_EM')) {
                return $ev;
            }

            $ev = '';
        }

        if ($ev == '' && $field['cf_required'] == 0) {
            return '';
        }

        return ($ev == '1') ? do_lang_tempcode('YES') : do_lang_tempcode('NO');
    }

    // ======================
    // Frontend: fields input
    // ======================

    /**
     * Get form inputter.
     *
     * @param  string $_cf_name The field name
     * @param  string $_cf_description The field description
     * @param  array $field The field details
     * @param  ?string $actual_value The actual current value of the field (null: none)
     * @param  boolean $new Whether this is for a new entry
     * @return ?Tempcode The Tempcode for the input field (null: skip the field - it's not input)
     */
    public function get_field_inputter($_cf_name, $_cf_description, $field, $actual_value, $new)
    {
        if ($actual_value === do_lang('NA')) {
            $actual_value = null;
        }

        $input_name = empty($field['cf_input_name']) ? ('field_' . strval($field['id'])) : $field['cf_input_name'];
        if ($field['cf_required'] == 1) {
            return form_input_tick($_cf_name, $_cf_description, $input_name, $actual_value == '1');
        }
        $_list = new Tempcode();
        $_list->attach(form_input_list_entry('', is_null($actual_value) || ($actual_value === ''), do_lang_tempcode('NA_EM')));
        $_list->attach(form_input_list_entry('0', $actual_value === '0', do_lang_tempcode('NO')));
        $_list->attach(form_input_list_entry('1', $actual_value === '1', do_lang_tempcode('YES')));
        return form_input_list($_cf_name, $_cf_description, $input_name, $_list, null, false, $field['cf_required'] == 1);
    }

    /**
     * Find the posted value from the get_field_inputter field
     *
     * @param  boolean $editing Whether we were editing (because on edit, it could be a fractional edit)
     * @param  array $field The field details
     * @param  ?string $upload_dir Where the files will be uploaded to (null: do not store an upload, return null if we would need to do so)
     * @param  ?array $old_value Former value of field (null: none)
     * @return ?string The value (null: could not process)
     */
    public function inputted_to_field_value($editing, $field, $upload_dir = 'uploads/catalogues', $old_value = null)
    {
        $id = $field['id'];
        $tmp_name = 'field_' . strval($id);
        return post_param_string($tmp_name, ($editing && is_null(post_param_string('tick_on_form__' . $tmp_name, null))) ? STRING_MAGIC_NULL : '');
    }
}
