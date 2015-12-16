<?php /*

 Composr
 Copyright (c) ocProducts, 2004-2015

 See text/EN/licence.txt for full licencing information.


 NOTE TO PROGRAMMERS:
   Do not edit this file. If you need to make changes, save your changed file to the appropriate *_custom folder
   **** If you ignore this advice, then your website upgrades (e.g. for bug fixes) will likely kill your changes ****

*/

/**
 * @license    http://opensource.org/licenses/cpal_1.0 Common Public Attribution License
 * @copyright  ocProducts Ltd
 * @package    chat
 */

require_code('resource_fs');

/**
 * Hook class.
 */
class Hook_commandr_fs_chat extends Resource_fs_base
{
    public $file_resource_type = 'chat';

    /**
     * Standard Commandr-fs function for seeing how many resources are. Useful for determining whether to do a full rebuild.
     *
     * @param  ID_TEXT $resource_type The resource type
     * @return integer How many resources there are
     */
    public function get_resources_count($resource_type)
    {
        return $GLOBALS['SITE_DB']->query_select_value('chat_rooms', 'COUNT(*)');
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
        $_ret = $GLOBALS['SITE_DB']->query_select('chat_rooms', array('id'), array('room_name' => $label));
        $ret = array();
        foreach ($_ret as $r) {
            $ret[] = strval($r['id']);
        }
        return $ret;
    }

    /**
     * Standard Commandr-fs date fetch function for resource-fs hooks. Defined when getting an edit date is not easy.
     *
     * @param  array $row Resource row (not full, but does contain the ID)
     * @return ?TIME The edit date or add date, whichever is higher (null: could not find one)
     */
    protected function _get_file_edit_date($row)
    {
        $query = 'SELECT MAX(date_and_time) FROM ' . get_table_prefix() . 'actionlogs WHERE ' . db_string_equal_to('param_a', strval($row['id'])) . ' AND  (' . db_string_equal_to('the_type', 'ADD_CHATROOM') . ' OR ' . db_string_equal_to('the_type', 'EDIT_CHATROOM') . ')';
        return $GLOBALS['SITE_DB']->query_value_if_there($query);
    }

    /**
     * Convert properties to variables for adding/editing rooms.
     *
     * @param  string $path The path (blank: root / not applicable)
     * @param  array $properties Properties (may be empty, properties given are open to interpretation by the hook but generally correspond to database fields)
     * @return array Properties
     */
    protected function __file_read_in_properties($path, $properties)
    {
        $welcome = $this->_default_property_str($properties, 'welcome_message');

        $room_owner = $this->_default_property_member_null($properties, 'room_owner');

        $_allow = array();
        if (!empty($properties['allow'])) {
            foreach ($properties['allow'] as $x) {
                $_x = remap_portable_as_resource_id('member', $x);
                if (!is_null($_x)) {
                    $_allow[] = $_x;
                }
            }
        }
        $allow = implode(',', array_map('strval', $_allow));

        $_allow_groups = array();
        if (!empty($properties['allow_groups'])) {
            foreach ($properties['allow_groups'] as $x) {
                $_x = remap_portable_as_resource_id('group', $x);
                if (!is_null($_x)) {
                    $_allow_groups[] = $_x;
                }
            }
        }
        $allow_groups = implode(',', array_map('strval', $_allow_groups));

        $_disallow = array();
        if (!empty($properties['disallow'])) {
            foreach ($properties['disallow'] as $x) {
                $_x = remap_portable_as_resource_id('member', $x);
                if (!is_null($_x)) {
                    $_disallow[] = $_x;
                }
            }
        }
        $disallow = implode(',', array_map('strval', $_disallow));

        $_disallow_groups = array();
        if (!empty($properties['disallow_groups'])) {
            foreach ($properties['disallow_groups'] as $x) {
                $_x = remap_portable_as_resource_id('group', $x);
                if (!is_null($_x)) {
                    $_disallow_groups[] = $_x;
                }
            }
        }
        $disallow_groups = implode(',', array_map('strval', $_disallow_groups));

        $roomlang = $this->_default_property_str($properties, 'room_lang');
        if ($roomlang == '') {
            $roomlang = get_site_default_lang();
        }

        $is_im = $this->_default_property_int($properties, 'is_im');

        return array($welcome, $room_owner, $allow, $allow_groups, $disallow, $disallow_groups, $roomlang, $is_im);
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
        list($properties, $label) = $this->_file_magic_filter($filename, $path, $properties);

        require_code('chat2');

        list($welcome, $room_owner, $allow, $allow_groups, $disallow, $disallow_groups, $roomlang, $is_im) = $this->__file_read_in_properties($path, $properties);

        $id = add_chatroom($welcome, $label, $room_owner, $allow, $allow_groups, $disallow, $disallow_groups, $roomlang, $is_im);
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

        $rows = $GLOBALS['SITE_DB']->query_select('chat_rooms', array('*'), array('id' => intval($resource_id)), '', 1);
        if (!array_key_exists(0, $rows)) {
            return false;
        }
        $row = $rows[0];

        $allow = array();
        if (!empty($row['allow_list'])) {
            foreach (explode(',', $row['allow_list']) as $x) {
                $_x = remap_resource_id_as_portable('member', intval($x));
                if (!is_null($_x)) {
                    $allow[] = $_x;
                }
            }
        }

        $allow_groups = array();
        if (!empty($row['allow_list_groups'])) {
            foreach (explode(',', $row['allow_list_groups']) as $x) {
                $_x = remap_resource_id_as_portable('group', intval($x));
                if (!is_null($_x)) {
                    $allow_groups[] = $_x;
                }
            }
        }

        $disallow = array();
        if (!empty($row['disallow_list'])) {
            foreach (explode(',', $row['disallow_list']) as $x) {
                $_x = remap_resource_id_as_portable('member', intval($x));
                if (!is_null($_x)) {
                    $disallow[] = $_x;
                }
            }
        }

        $disallow_groups = array();
        if (!empty($row['disallow_list_groups'])) {
            foreach (explode(',', $row['disallow_list_groups']) as $x) {
                $_x = remap_resource_id_as_portable('group', intval($x));
                if (!is_null($_x)) {
                    $disallow_groups[] = $_x;
                }
            }
        }

        return array(
            'label' => $row['room_name'],
            'welcome_message' => $row['c_welcome'],
            'room_owner' => remap_resource_id_as_portable('member', $row['room_owner']),
            'allow' => $allow,
            'allow_groups' => $allow_groups,
            'disallow' => $disallow,
            'disallow_groups' => $disallow_groups,
            'room_lang' => $row['room_language'],
            'is_im' => $row['is_im'],
        );
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
        list($properties,) = $this->_file_magic_filter($filename, $path, $properties);

        require_code('chat2');

        $label = $this->_default_property_str($properties, 'label');
        list($welcome, $room_owner, $allow, $allow_groups, $disallow, $disallow_groups, $roomlang, $is_im) = $this->__file_read_in_properties($path, $properties);

        edit_chatroom(intval($resource_id), $welcome, $label, $room_owner, $allow, $allow_groups, $disallow, $disallow_groups, $roomlang);

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

        require_code('chat2');
        delete_chatroom(intval($resource_id));

        return true;
    }
}
