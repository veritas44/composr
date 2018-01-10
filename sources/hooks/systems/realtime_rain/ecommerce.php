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
 * @package    ecommerce
 */

/**
 * Hook class.
 */
class Hook_realtime_rain_ecommerce
{
    /**
     * Run function for realtime-rain hooks.
     *
     * @param  TIME $from Start of time range
     * @param  TIME $to End of time range
     * @return array A list of template parameter sets for rendering a 'drop'
     */
    public function run($from, $to)
    {
        $drops = array();

        if (has_actual_page_access(get_member(), 'admin_ecommerce')) {
            $rows = $GLOBALS['SITE_DB']->query('SELECT t_amount,t_type_code,t_time AS timestamp FROM ' . $GLOBALS['SITE_DB']->get_table_prefix() . 'ecom_transactions WHERE t_time BETWEEN ' . strval($from) . ' AND ' . strval($to));

            foreach ($rows as $row) {
                require_code('ecommerce');
                list($details) = find_product_details($row['t_type_code']);
                if ($details !== null) {
                    $title = $details['item_name'];
                } else {
                    require_lang('ecommerce');
                    $title = do_lang('SALE_MADE');
                }

                $timestamp = $row['t_timestamp'];

                $ticker_text = do_lang('KA_CHING', ecommerce_get_currency_symbol($row['t_currency']), $row['t_amount'], $row['t_currency']);

                $drops[] = rain_get_special_icons(null, $timestamp, null, $ticker_text) + array(
                        'TYPE' => 'ecommerce',
                        'FROM_MEMBER_ID' => null,
                        'TO_MEMBER_ID' => null,
                        'TITLE' => $title,
                        'IMAGE' => find_theme_image('icons/48x48/menu/rich_content/ecommerce/purchase'),
                        'TIMESTAMP' => strval($timestamp),
                        'RELATIVE_TIMESTAMP' => strval($timestamp - $from),
                        'TICKER_TEXT' => $ticker_text,
                        'URL' => null,
                        'IS_POSITIVE' => true,
                        'IS_NEGATIVE' => false,

                        // These are for showing connections between drops. They are not discriminated, it's just three slots to give an ID code that may be seen as a commonality with other drops.
                        'FROM_ID' => null,
                        'TO_ID' => null,
                        'GROUP_ID' => 'product_' . $row['t_type_code'],
                    );
            }
        }

        return $drops;
    }
}
