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
 * @package    commandr
 */

/**
 * Hook class.
 */
class Hook_commandr_command_find
{
    /**
     * Run function for Commandr hooks.
     *
     * @param  array $options The options with which the command was called
     * @param  array $parameters The parameters with which the command was called
     * @param  object $commandr_fs A reference to the Commandr filesystem object
     * @return array Array of stdcommand, stdhtml, stdout, and stderr responses
     */
    public function run($options, $parameters, &$commandr_fs)
    {
        if ((array_key_exists('h', $options)) || (array_key_exists('help', $options))) {
            return array('', do_command_help('find', array('h', 'p', 'r', 'f', 'd'), array(true, true)), '', '');
        } else {
            if (!array_key_exists(0, $parameters)) {
                return array('', '', '', do_lang('MISSING_PARAM', '1', 'find'));
            }

            if (!((array_key_exists('d', $options)) || (array_key_exists('directories', $options)))) {
                $directories = false;
            } elseif (array_key_exists('d', $options)) {
                $directories = $options['d'] == '1';
            } else {
                $directories = $options['directories'] == '1';
            }

            if (!((array_key_exists('f', $options)) || (array_key_exists('files', $options)))) {
                $files = true;
            } elseif (array_key_exists('f', $options)) {
                $files = $options['f'] == '1';
            } else {
                $files = $options['files'] == '1';
            }

            if (!array_key_exists(1, $parameters)) {
                $parameters[1] = $commandr_fs->print_working_directory(true);
            } else {
                $parameters[1] = $commandr_fs->_pwd_to_array($parameters[1]);
            }

            if (!$commandr_fs->_is_dir($parameters[1])) {
                return array('', '', '', do_lang('NOT_A_DIR', '2'));
            }

            $listing = $commandr_fs->search($parameters[0], ((array_key_exists('p', $options)) || (array_key_exists('preg', $options))), ((array_key_exists('r', $options)) || (array_key_exists('recursive', $options))), $files, $directories, $parameters[1]);

            return array(
                '',
                do_template('COMMANDR_LS', array(
                    '_GUID' => '50336439839279d3d8620d6f2124512a',
                    'DIRECTORY' => $commandr_fs->pwd_to_string($parameters[1]),
                    'DIRECTORIES' => $commandr_fs->prepare_dir_contents_for_listing($listing[0]),
                    'FILES' => $commandr_fs->prepare_dir_contents_for_listing($listing[1]),
                )),
                '',
                ''
            );
        }
    }
}
