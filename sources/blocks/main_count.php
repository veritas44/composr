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
 * @package    counting_blocks
 */

/**
 * Block class.
 */
class Block_main_count
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
        $info['parameters'] = array('param', 'start', 'hit_count');
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
        require_css('counting_blocks');

        $block_id = get_block_id($map);

        // The counter we're using
        $name = array_key_exists('param', $map) ? $map['param'] : '';
        if ($name == '-') {
            $name = get_page_name() . ':' . get_param_string('type', 'browse') . ':' . get_param_string('id', '');
        }
        if ($name == '') {
            $name = 'hits';
        }

        $start = array_key_exists('start', $map) ? intval($map['start']) : 0;

        // Set it if it's not already
        $_current_value = get_value($name);
        if ($_current_value === null) {
            set_value($name, strval($start));
            $current_value = $start;
        } else {
            $current_value = intval($_current_value);
            if ($start > $current_value) {
                $current_value = $start;
                set_value($name, strval($current_value));
            }
        }

        // Hit counter?
        $hit_count = array_key_exists('hit_count', $map) ? intval($map['hit_count']) : 1;
        $update = mixed();
        if ($hit_count == 1) {
            //update_stat($name, 1); Actually, use AJAX
            $update = $name;
        }

        return do_template('BLOCK_MAIN_COUNT', array(
            '_GUID' => '49d3ba8fb5b5544ac817f9a7d18f9d35',
            'BLOCK_ID' => $block_id,
            'NAME' => $name,
            'UPDATE' => $update,
            'VALUE' => strval($current_value + 1),
        ));
    }
}
