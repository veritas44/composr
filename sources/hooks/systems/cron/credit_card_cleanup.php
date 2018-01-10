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
class Hook_cron_credit_card_cleanup
{
    /**
     * Run function for Cron hooks. Searches for tasks to perform.
     */
    public function run()
    {
        $credit_card_cleanup_days = get_option('credit_card_cleanup_days');
        if ($credit_card_cleanup_days === null) {
            return;
        }

        $last_time = intval(get_value('credit_card_cleanup_time', null, true));
        if (time() >= $last_time + 60 * 60 * 24) {
            require_code('cns_members');

            $protected_field_changes = array();
            $protected_field_names = array('payment_cardholder_name', 'payment_card_type', 'payment_card_number', 'payment_card_start_date', 'payment_card_expiry_date', 'payment_card_issue_number', 'billing_street_address', 'billing_city', 'billing_post_code', 'billing_country', 'billing_mobile_phone_number', 'billing_county', 'billing_state');
            foreach ($protected_field_names as $cpf) {
                $field_id = find_cms_cpf_field_id('cms_' . $cpf);
                if ($field_id !== null) {
                    $protected_field_changes['field_' . strval($field_id)] = (($cpf == 'payment_card_number' || $cpf == 'payment_card_issue_number') ? null : '');
                }
            }

            $threshold = time() - 60 * 60 * 24 * intval($credit_card_cleanup_days);

            $where = 'm_last_visit_time<' . strval($threshold);
            $GLOBALS['FORUM_DB']->query_update(
                'f_members m JOIN ' . $GLOBALS['FORUM_DB']->get_table_prefix() . 'f_member_custom_fields f ON f.mf_member_id=m.id AND ' . $where,
                $protected_field_changes
            );

            set_value('credit_card_cleanup_time', strval(time()), true);
        }
    }
}
