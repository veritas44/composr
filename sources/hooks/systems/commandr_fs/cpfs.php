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
 * @package    cns_cpfs
 */

require_code('resource_fs');

/**
 * Hook class.
 */
class Hook_commandr_fs_cpfs extends Resource_fs_base
{
    public $file_resource_type = 'cpf';

    /**
     * Standard Commandr-fs function for seeing how many resources are. Useful for determining whether to do a full rebuild.
     *
     * @param  ID_TEXT $resource_type The resource type
     * @return integer How many resources there are
     */
    public function get_resources_count($resource_type)
    {
        return $GLOBALS['FORUM_DB']->query_select_value('f_custom_fields', 'COUNT(*)');
    }

    /**
     * Standard Commandr-fs function for searching for a resource by label.
     *
     * @param  ID_TEXT $resource_type The resource type
     * @param  LONG_TEXT $label The resource label
     * @return array A list of resource IDs
     */
    public function find_resource_by_label($resource_type, $label)
    {
        $_ret = $GLOBALS['FORUM_DB']->query_select('f_custom_fields', array('id'), array($GLOBALS['FORUM_DB']->translate_field_ref('cf_name') => $label), 'ORDER BY id');
        $ret = array();
        foreach ($_ret as $r) {
            $ret[] = strval($r['id']);
        }
        return $ret;
    }

    /**
     * Whether the filesystem hook is active.
     *
     * @return boolean Whether it is
     */
    protected function _is_active()
    {
        return (get_forum_type() == 'cns') && (!is_cns_satellite_site());
    }

    /**
     * Standard Commandr-fs date fetch function for resource-fs hooks. Defined when getting an edit date is not easy.
     *
     * @param  array $row Resource row (not full, but does contain the ID)
     * @return ?TIME The edit date or add date, whichever is higher (null: could not find one)
     */
    protected function _get_file_edit_date($row)
    {
        $query = 'SELECT MAX(date_and_time) FROM ' . get_table_prefix() . 'actionlogs WHERE ' . db_string_equal_to('param_a', strval($row['id'])) . ' AND  (' . db_string_equal_to('the_type', 'ADD_CUSTOM_PROFILE_FIELD') . ' OR ' . db_string_equal_to('the_type', 'EDIT_CUSTOM_PROFILE_FIELD') . ')';
        return $GLOBALS['SITE_DB']->query_value_if_there($query);
    }

    /**
     * Standard Commandr-fs add function for resource-fs hooks. Adds some resource with the given label and properties.
     *
     * @param  LONG_TEXT $filename Filename OR Resource label
     * @param  string $path The path (blank: root / not applicable)
     * @param  array $properties Properties (may be empty, properties given are open to interpretation by the hook but generally correspond to database fields)
     * @return ~ID_TEXT The resource ID (false: error, could not create via these properties / here)
     */
    public function file_add($filename, $path, $properties)
    {
        list($properties, $label) = $this->_file_magic_filter($filename, $path, $properties, $this->file_resource_type);

        require_code('cns_members_action');

        $description = $this->_default_property_str($properties, 'description');
        $locked = $this->_default_property_int($properties, 'locked');
        $default = $this->_default_property_int($properties, 'default');
        $public_view = $this->_default_property_int($properties, 'public_view');
        $owner_view = $this->_default_property_int($properties, 'owner_view');
        $owner_set = $this->_default_property_int($properties, 'owner_set');
        require_code('encryption');
        if (is_encryption_enabled()) {
            $encrypted = $this->_default_property_int($properties, 'encrypted');
        } else {
            $encrypted = 0;
        }
        $type = $this->_default_property_str($properties, 'type');
        if ($type == '') {
            $type = 'short_text';
        }
        $required = $this->_default_property_int($properties, 'required');
        $show_in_posts = $this->_default_property_int($properties, 'show_in_posts');
        $show_in_post_previews = $this->_default_property_int($properties, 'show_in_post_previews');
        $order = $this->_default_property_int($properties, 'order');
        $only_group = $this->_default_property_str($properties, 'only_group');
        $show_on_join_form = $this->_default_property_int($properties, 'show_on_join_form');
        $options = $this->_default_property_str($properties, 'options');

        $id = cns_make_custom_field($label, $locked, $description, $default, $public_view, $owner_view, $owner_set, $encrypted, $type, $required, $show_in_posts, $show_in_post_previews, $order, $only_group, $show_on_join_form, $options, false);

        $this->_resource_save_extend($this->file_resource_type, strval($id), $filename, $label, $properties);

        return strval($id);
    }

    /**
     * Standard Commandr-fs load function for resource-fs hooks. Finds the properties for some resource.
     *
     * @param  SHORT_TEXT $filename Filename
     * @param  string $path The path (blank: root / not applicable). It may be a wildcarded path, as the path is used for content-type identification only. Filenames are globally unique across a hook; you can calculate the path using ->search.
     * @return ~array Details of the resource (false: error)
     */
    public function file_load($filename, $path)
    {
        list($resource_type, $resource_id) = $this->file_convert_filename_to_id($filename);

        $rows = $GLOBALS['FORUM_DB']->query_select('f_custom_fields', array('*'), array('id' => intval($resource_id)), '', 1);
        if (!array_key_exists(0, $rows)) {
            return false;
        }
        $row = $rows[0];

        $properties = array(
            'label' => $row['cf_name'],
            'description' => $row['cf_description'],
            'locked' => $row['cf_locked'],
            'default' => $row['cf_default'],
            'public_view' => $row['cf_public_view'],
            'owner_view' => $row['cf_owner_view'],
            'owner_set' => $row['cf_owner_set'],
            'type' => $row['cf_type'],
            'required' => $row['cf_required'],
            'show_in_posts' => $row['cf_show_in_posts'],
            'show_in_post_previews' => $row['cf_show_in_post_previews'],
            'order' => $row['cf_order'],
            'only_group' => $row['cf_only_group'],
            'show_on_join_form' => $row['cf_show_on_join_form'],
            'options' => $row['cf_options'],
        );

        require_code('encryption');
        if (is_encryption_enabled()) {
            $properties['encryption'] = $row['cf_encryption'];
        }

        $this->_resource_load_extend($resource_type, $resource_id, $properties, $filename, $path);

        return $properties;
    }

    /**
     * Standard Commandr-fs edit function for resource-fs hooks. Edits the resource to the given properties.
     *
     * @param  ID_TEXT $filename The filename
     * @param  string $path The path (blank: root / not applicable)
     * @param  array $properties Properties (may be empty, properties given are open to interpretation by the hook but generally correspond to database fields)
     * @return ~ID_TEXT The resource ID (false: error, could not create via these properties / here)
     */
    public function file_edit($filename, $path, $properties)
    {
        list($resource_type, $resource_id) = $this->file_convert_filename_to_id($filename);
        list($properties,) = $this->_file_magic_filter($filename, $path, $properties, $this->file_resource_type);

        require_code('cns_members_action2');

        $label = $this->_default_property_str($properties, 'label');
        $description = $this->_default_property_str($properties, 'description');
        $locked = $this->_default_property_int($properties, 'locked');
        $default = $this->_default_property_int($properties, 'default');
        $public_view = $this->_default_property_int($properties, 'public_view');
        $owner_view = $this->_default_property_int($properties, 'owner_view');
        $owner_set = $this->_default_property_int($properties, 'owner_set');
        require_code('encryption');
        if (is_encryption_enabled()) {
            $encrypted = $this->_default_property_int($properties, 'encrypted');
        } else {
            $encrypted = 0;
        }
        $type = $this->_default_property_str($properties, 'type');
        if ($type == '') {
            $type = 'short_text';
        }
        $required = $this->_default_property_int($properties, 'required');
        $show_in_posts = $this->_default_property_int($properties, 'show_in_posts');
        $show_in_post_previews = $this->_default_property_int($properties, 'show_in_post_previews');
        $order = $this->_default_property_int($properties, 'order');
        $only_group = $this->_default_property_str($properties, 'only_group');
        $show_on_join_form = $this->_default_property_int($properties, 'show_on_join_form');
        $options = $this->_default_property_str($properties, 'options');

        cns_edit_custom_field(intval($resource_id), $label, $description, $default, $public_view, $owner_view, $owner_set, $encrypted, $required, $show_in_posts, $show_in_post_previews, $order, $only_group, $type, $show_on_join_form, $options);

        $this->_resource_save_extend($this->file_resource_type, $resource_id, $filename, $label, $properties);

        return $resource_id;
    }

    /**
     * Standard Commandr-fs delete function for resource-fs hooks. Deletes the resource.
     *
     * @param  ID_TEXT $filename The filename
     * @param  string $path The path (blank: root / not applicable)
     * @return boolean Success status
     */
    public function file_delete($filename, $path)
    {
        list($resource_type, $resource_id) = $this->file_convert_filename_to_id($filename);

        require_code('cns_members_action2');
        cns_delete_custom_field(intval($resource_id));

        return true;
    }
}
