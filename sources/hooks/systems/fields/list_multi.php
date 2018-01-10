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
 * @package    core_fields
 */

/**
 * Hook class.
 */
class Hook_fields_list_multi extends ListFieldHook
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
        $special->attach(form_input_list_entry('', get_param_string('option_' . strval($field['id']), '', INPUT_FILTER_GET_COMPLEX) == '', '---'));
        $display = array_key_exists('trans_name', $field) ? $field['trans_name'] : get_translated_text($field['cf_name']); // 'trans_name' may have been set in CPF retrieval API, might not correspond to DB lookup if is an internal field
        $list = $this->get_input_list_map($field, true);
        foreach ($list as $l) {
            if (is_integer($l)) {
                $l = strval($l);
            }

            $special->attach(form_input_list_entry($l, get_param_string('option_' . strval($field['id']), '', INPUT_FILTER_GET_COMPLEX) == $l));
        }
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
        return nl_delim_match_sql($field, $i, 'long');
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
            if ((($default == '') && ($required)) || ($default == $field['cf_default'])) {
                $default = $field['cf_default'];
                if ($required) {
                    $default = preg_replace('#^(=.*)?\|#U', '', $default); // Get key of blank option
                }
                $default = preg_replace('#\|.*$#', '', $default); // Remove all the non-first list options
                $default = preg_replace('#=.*$#', '', $default); // Get key of first
            }
        }
        return array('long_unescaped', $default, 'long');
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
        if ($ev == $field['cf_default']) {
            return '';
        }

        if (is_object($ev)) {
            return $ev;
        }

        if ($ev == '') {
            return '';
        }

        $exploded_inbuilt = ($field['cf_default'] == '') ? array() : array_flip(explode('|', $field['cf_default']));
        $exploded_chosen = ($ev == '') ? array() : array_flip(explode("\n", $ev));

        $show_unset_values = (option_value_from_field_array($field, 'show_unset_values', 'off') == 'on');

        $custom_values = option_value_from_field_array($field, 'custom_values', 'off');

        $all = array();
        foreach (array_keys($exploded_inbuilt) as $option) {
            $has = isset($exploded_chosen[$option]);
            if ($has || $show_unset_values) {
                $all[] = array('OPTION' => $option, 'HAS' => $has, 'IS_OTHER' => false);
            }
        }
        if ($custom_values != 'off') {
            foreach (array_keys($exploded_chosen) as $chosen) {
                if (!isset($exploded_inbuilt[$chosen])) {
                    $all[] = array('OPTION' => $chosen, 'HAS' => true, 'IS_OTHER' => true);
                }
            }
        }

        $auto_sort = option_value_from_field_array($field, 'auto_sort', 'off');
        if ($auto_sort == 'frontend' || $auto_sort == 'both') {
            sort_maps_by($all, 'OPTION');
        }

        if (isset($field['c_name'])) {
            $template = 'CATALOGUE_' . $field['c_name'] . '_FIELD_MULTILIST';
        } else {
            $template = 'CATALOGUE_other_FIELD_MULTILIST';
        }

        return do_template($template, array('_GUID' => 'x28e21cdbc38a3037d083f619bb31dae', 'SHOW_UNSET_VALUES' => $show_unset_values, 'ALL' => $all, 'FIELD_ID' => strval($field['id'])), null, false, 'CATALOGUE_DEFAULT_FIELD_MULTILIST');
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
     * @return ?Tempcode The Tempcode for the input field (null: skip the field - it's not input)
     */
    public function get_field_inputter($_cf_name, $_cf_description, $field, $actual_value)
    {
        $default = $field['cf_default'];

        $list = $this->get_input_list_map($field);

        $input_name = empty($field['cf_input_name']) ? ('field_' . strval($field['id'])) : $field['cf_input_name'];

        $custom_values = option_value_from_field_array($field, 'custom_values', 'off');

        $exploded_chosen = ($actual_value == $default) ? array() : explode("\n", $actual_value);

        $auto_sort = option_value_from_field_array($field, 'auto_sort', 'off');
        if ($auto_sort == 'backend' || $auto_sort == 'both') {
            sort($list, SORT_NATURAL | SORT_FLAG_CASE);
        }

        $custom_name = $input_name . '_other';
        $custom_value = mixed();
        $custom_value = array();
        foreach ($exploded_chosen as $chosen) {
            if (!in_array($chosen, $list)) {
                $custom_value[] = $chosen;
            }
        }
        switch ($custom_values) {
            case 'off':
                $custom_name = null;
                $custom_value = null;
                break;

            case 'single':
                $custom_value = implode(', ', $custom_value);
                break;

            case 'multiple':
                break;
        }

        $widget = option_value_from_field_array($field, 'widget', 'multilist');

        $input_size = max(1, intval(option_value_from_field_array($field, 'input_size', '5')));

        switch ($widget)
        {
            case 'vertical_checkboxes':
            case 'horizontal_checkboxes':
                $_list = array();
                foreach ($list as $i => $l) {
                    $_list[] = array(protect_from_escaping(comcode_to_tempcode($l, null, true)), $input_name . '_' . strval($i), in_array($l, $exploded_chosen), '');
                }
                return form_input_various_ticks($_list, $_cf_description, null, $_cf_name, ($widget == 'vertical_checkboxes'), $custom_name, $custom_value);

            case 'multilist':
            default:
                $list_tpl = new Tempcode();
                foreach ($list as $l) {
                    $list_tpl->attach(form_input_list_entry(protect_from_escaping(comcode_to_tempcode($l, null, true)), in_array($l, $exploded_chosen)));
                }
                return form_input_multi_list($_cf_name, $_cf_description, $input_name, $list_tpl, null, $input_size, $field['cf_required'] == 1, $custom_name, $custom_value);
        }
    }

    /**
     * Find the posted value from the get_field_inputter field.
     *
     * @param  boolean $editing Whether we were editing (because on edit, it could be a fractional edit)
     * @param  array $field The field details
     * @param  ?string $upload_dir Where the files will be uploaded to (null: do not store an upload, return null if we would need to do so)
     * @param  ?array $old_value Former value of field (null: none)
     * @return ?string The value (null: could not process)
     */
    public function inputted_to_field_value($editing, $field, $upload_dir = 'uploads/catalogues', $old_value = null)
    {
        $ret = array();

        $tmp_name = 'field_' . strval($field['id']);

        if ((fractional_edit()) && ((post_param_string('require__' . $tmp_name, null) === null))) {
            return STRING_MAGIC_NULL; // Was not on UI
        }

        $widget = option_value_from_field_array($field, 'widget', 'multilist');
        switch ($widget)
        {
            case 'vertical_checkboxes':
            case 'horizontal_checkboxes':
                $i = 0;
                do {
                    $_tmp_name = $tmp_name . '_' . strval($i);
                    if (post_param_integer($_tmp_name, 0) == 1) {
                        $ret[] = post_param_string('label_for__' . $_tmp_name);
                    }
                    $i++;
                }
                while (post_param_string('tick_on_form__' . $_tmp_name, null) !== null);
                break;

            case 'multilist':
            default:
                if (isset($_POST[$tmp_name])) {
                    if (is_array($_POST[$tmp_name])) {
                        $retx = $_POST[$tmp_name];
                        $ret = array_merge($ret, $retx);
                    }
                }
                break;
        }

        $custom_values = option_value_from_field_array($field, 'custom_values', 'off');
        switch ($custom_values) {
            case 'multiple':
                if (isset($_POST[$tmp_name . '_other_value'])) {
                    if (is_array($_POST[$tmp_name . '_other_value'])) {
                        $retx = $_POST[$tmp_name . '_other_value'];
                        foreach ($retx as $other_value) {
                            if ($other_value != '') {
                                $ret[] = $other_value;
                            }
                        }
                    }
                }
                break;

            case 'single':
                $other_value = post_param_string($tmp_name . '_other_value', '');
                if ($other_value != '') {
                    $ret[] = $other_value;
                }
                break;
        }

        $value = implode("\n", $ret);

        return $value;
    }
}
