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
 * @package    cns_avatars
 */

/**
 * Hook class.
 */
class Hook_addon_registry_cns_avatars
{
    /**
     * Get a list of file permissions to set.
     *
     * @param  boolean $runtime Whether to include wildcards represented runtime-created chmoddable files
     * @return array File permissions to set
     */
    public function get_chmod_array($runtime = false)
    {
        return array();
    }

    /**
     * Get the version of Composr this addon is for.
     *
     * @return float Version number
     */
    public function get_version()
    {
        return cms_version_number();
    }

    /**
     * Get the description of the addon.
     *
     * @return string Description of the addon
     */
    public function get_description()
    {
        return 'A selection of avatars for Conversr';
    }

    /**
     * Get a list of tutorials that apply to this addon.
     *
     * @return array List of tutorials
     */
    public function get_applicable_tutorials()
    {
        return array(
            'tut_members',
        );
    }

    /**
     * Get a mapping of dependency types.
     *
     * @return array File permissions to set
     */
    public function get_dependencies()
    {
        return array(
            'requires' => array(
                'cns_member_avatars',
            ),
            'recommends' => array(),
            'conflicts_with' => array(),
        );
    }

    /**
     * Explicitly say which icon should be used.
     *
     * @return URLPATH Icon
     */
    public function get_default_icon()
    {
        return 'themes/default/images/icons/48x48/menu/_generic_admin/component.png';
    }

    /**
     * Get a list of files that belong to this addon.
     *
     * @return array List of files
     */
    public function get_file_list()
    {
        return array(
            'sources/hooks/systems/addon_registry/cns_avatars.php',
            'themes/default/images/cns_default_avatars/default_set/airplane.png',
            'themes/default/images/cns_default_avatars/default_set/bird.png',
            'themes/default/images/cns_default_avatars/default_set/bonfire.png',
            'themes/default/images/cns_default_avatars/default_set/cool_flare.png',
            'themes/default/images/cns_default_avatars/default_set/dog.png',
            'themes/default/images/cns_default_avatars/default_set/eagle.png',
            'themes/default/images/cns_default_avatars/default_set/forks.png',
            'themes/default/images/cns_default_avatars/default_set/horse.png',
            'themes/default/images/cns_default_avatars/default_set/index.html',
            'themes/default/images/cns_default_avatars/default_set/music.png',
            'themes/default/images/cns_default_avatars/default_set/trees.png',
            'themes/default/images/cns_default_avatars/default_set/chess.png',
            'themes/default/images/cns_default_avatars/default_set/fireman.png',
            'themes/default/images/cns_default_avatars/default_set/berries.png',
        );
    }
}
