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

/**
 * Block class.
 */
class Block_top_login
{
    /**
     * Find details of the block.
     *
     * @return ?array Map of block info (null: block is disabled)
     */
    public function info()
    {
        $info = array();
        $info['author'] = 'Chris Graham';
        $info['organisation'] = 'ocProducts';
        $info['hacked_by'] = null;
        $info['hack_version'] = null;
        $info['version'] = 2;
        $info['locked'] = false;
        $info['parameters'] = array();
        return $info;
    }

    /**
     * Find caching details for the block.
     *
     * @return ?array Map of cache details (cache_on and ttl) (null: block is disabled)
     */
    public function caching_environment()
    {
        $info = array();
        $info['cache_on'] = 'is_guest() ? null : array()'; // No caching for guests due to self URL redirect
        $info['ttl'] = (get_value('disable_block_timeout') === '1') ? 60 * 60 * 24 * 365 * 5/*5 year timeout*/ : 60 * 24;
        return $info;
    }

    /**
     * Execute the block.
     *
     * @param  array $map A map of parameters
     * @return Tempcode The result of execution
     */
    public function run($map)
    {
        if (!is_guest()) {
            return new Tempcode();
        }

        if (get_forum_type() == 'none') {
            return new Tempcode();
        }

        require_css('personal_stats');

        $block_id = get_block_id($map);

        $title = do_lang_tempcode('NOT_LOGGED_IN');

        list($full_url, $login_url, $join_url) = get_login_url();
        return do_template('BLOCK_TOP_LOGIN', array(
            '_GUID' => '9d1547632875ecd466ced4f90a866df9',
            'BLOCK_ID' => $block_id,
            'TITLE' => $title,
            'FULL_LOGIN_URL' => $full_url,
            'JOIN_URL' => $join_url,
            'LOGIN_URL' => $login_url,
        ));
    }
}
