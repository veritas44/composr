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
 * @package    ecommerce
 */

/**
 * Module page class.
 */
class Module_subscriptions
{
    /**
     * Find details of the module.
     *
     * @return ?array Map of module info (null: module is disabled).
     */
    public function info()
    {
        $info = array();
        $info['author'] = 'Chris Graham';
        $info['organisation'] = 'ocProducts';
        $info['hacked_by'] = null;
        $info['hack_version'] = null;
        $info['version'] = 7;
        $info['update_require_upgrade'] = true;
        $info['locked'] = false;
        return $info;
    }

    /**
     * Uninstall the module.
     */
    public function uninstall()
    {
        $GLOBALS['SITE_DB']->drop_table_if_exists('ecom_subscriptions');

        $dbs_bak = $GLOBALS['NO_DB_SCOPE_CHECK'];
        $GLOBALS['NO_DB_SCOPE_CHECK'] = true;
        $GLOBALS['SITE_DB']->drop_table_if_exists('f_usergroup_subs');
        $GLOBALS['SITE_DB']->drop_table_if_exists('f_usergroup_sub_mails');
        $GLOBALS['NO_DB_SCOPE_CHECK'] = $dbs_bak;
    }

    /**
     * Install the module.
     *
     * @param  ?integer $upgrade_from What version we're upgrading from (null: new install)
     * @param  ?integer $upgrade_from_hack What hack version we're upgrading from (null: new-install/not-upgrading-from-a-hacked-version)
     */
    public function install($upgrade_from = null, $upgrade_from_hack = null)
    {
        $dbs_bak = $GLOBALS['NO_DB_SCOPE_CHECK'];
        $GLOBALS['NO_DB_SCOPE_CHECK'] = true;

        if ($upgrade_from === null) {
            $GLOBALS['SITE_DB']->create_table('ecom_subscriptions', array(
                'id' => '*AUTO',
                's_type_code' => 'ID_TEXT',
                's_member_id' => 'MEMBER',
                's_state' => 'ID_TEXT', // pending|new|active|cancelled (pending means payment has been requested)
                's_amount' => 'REAL', // can't always find this from s_type_code
                's_tax_code' => 'ID_TEXT',
                's_tax_derivation' => 'LONG_TEXT', // Needs to be stored, as it's locked in time
                's_tax' => 'REAL', // Needs to be stored, as it's locked in time
                's_tax_tracking' => 'LONG_TEXT', // Needs to be stored, as it's locked in time
                's_currency' => 'ID_TEXT',
                's_purchase_id' => 'ID_TEXT',
                's_time' => 'TIME',
                's_auto_fund_source' => 'ID_TEXT', // The payment gateway
                's_auto_fund_key' => 'SHORT_TEXT', // Used by PayPal for nothing much, but is of real use if we need to schedule our own subscription transactions
                's_payment_gateway' => 'ID_TEXT', // An eCommerce hook or 'manual' or 'points'

                // Copied through from what the hook says at setup, in case the hook later changes
                's_length' => 'INTEGER',
                's_length_units' => 'SHORT_TEXT',
            ));

            $GLOBALS['SITE_DB']->create_table('f_usergroup_subs', array(
                'id' => '*AUTO',
                's_title' => 'SHORT_TRANS',
                's_description' => 'LONG_TRANS__COMCODE',
                's_price' => 'REAL',
                's_tax_code' => 'ID_TEXT',
                's_length' => 'INTEGER',
                's_length_units' => 'SHORT_TEXT',
                's_auto_recur' => 'BINARY',
                's_group_id' => 'GROUP',
                's_enabled' => 'BINARY',
                's_mail_start' => 'LONG_TRANS',
                's_mail_end' => 'LONG_TRANS',
                's_mail_uhoh' => 'LONG_TRANS',
                's_uses_primary' => 'BINARY',
            ));
        }

        if (($upgrade_from === null) || ($upgrade_from < 5)) {
            $GLOBALS['SITE_DB']->create_table('f_usergroup_sub_mails', array(
                'id' => '*AUTO',
                'm_usergroup_sub_id' => 'AUTO_LINK',
                'm_ref_point' => 'ID_TEXT', // start|term_start|term_end|expiry
                'm_ref_point_offset' => 'INTEGER',
                'm_subject' => 'SHORT_TRANS',
                'm_body' => 'LONG_TRANS',
            ));
        }

        if (($upgrade_from !== null) && ($upgrade_from < 5)) {
            $GLOBALS['SITE_DB']->alter_table_field('subscriptions', 's_special', 'ID_TEXT', 's_purchase_id');
            $GLOBALS['SITE_DB']->add_table_field('subscriptions', 's_length', 'INTEGER', 1);
            $GLOBALS['SITE_DB']->add_table_field('subscriptions', 's_length_units', 'SHORT_TEXT', 'm');
            $subscriptions = $GLOBALS['SITE_DB']->query_select('subscriptions', array('*'));
            foreach ($subscriptions as $sub) {
                if (substr($sub['s_type_code'], 0, 9) != 'USERGROUP') {
                    continue;
                }

                $usergroup_subscription_id = intval(substr($sub['s_type_code'], 9));
                $usergroup_subscription_rows = $GLOBALS['FORUM_DB']->query_select('f_usergroup_subs', array('*'), array('id' => $usergroup_subscription_id), '', 1);
                if (!array_key_exists(0, $usergroup_subscription_rows)) {
                    continue;
                }
                $usergroup_subscription_row = $usergroup_subscription_rows[0];

                $update_map = array(
                    's_length' => $usergroup_subscription_row['s_length'],
                    's_length_units' => $usergroup_subscription_row['s_length_units'],
                );
                $GLOBALS['SITE_DB']->query_update('subscriptions', $update_map, array('id' => $sub['id']), '', 1);
            }

            $GLOBALS['SITE_DB']->add_table_field('f_usergroup_subs', 's_auto_recur', 'BINARY', 1);
        }

        if (($upgrade_from !== null) && ($upgrade_from < 6)) {
            $GLOBALS['SITE_DB']->alter_table_field('subscriptions', 's_payment_gateway', 'ID_TEXT', 's_payment_gateway');

            $GLOBALS['SITE_DB']->create_index('subscriptions', 's_member_id', array('s_member_id'));
        }

        if (($upgrade_from !== null) && ($upgrade_from < 7)) {
            $GLOBALS['SITE_DB']->rename_table('subscriptions', 'ecom_subscriptions');
            $GLOBALS['SITE_DB']->alter_table_field('ecom_subscriptions', 's_amount', 'REAL');
            $GLOBALS['SITE_DB']->add_table_field('ecom_subscriptions', 's_tax_code', 'ID_TEXT', '0%');
            $GLOBALS['SITE_DB']->add_table_field('ecom_subscriptions', 's_tax_derivation', 'LONG_TEXT', '');
            $GLOBALS['SITE_DB']->add_table_field('ecom_subscriptions', 's_tax', 'REAL', 0.00);
            $GLOBALS['SITE_DB']->add_table_field('ecom_subscriptions', 's_tax_tracking', 'LONG_TEXT', '');
            $GLOBALS['SITE_DB']->add_table_field('ecom_subscriptions', 's_currency', 'ID_TEXT', get_option('currency'));

            $GLOBALS['SITE_DB']->alter_table_field('f_usergroup_subs', 's_cost', 'REAL', 's_price');
            $GLOBALS['SITE_DB']->add_table_field('f_usergroup_subs', 's_tax_code', 'ID_TEXT', '0%');
        }

        $GLOBALS['NO_DB_SCOPE_CHECK'] = $dbs_bak;
    }

    /**
     * Find entry-points available within this module.
     *
     * @param  boolean $check_perms Whether to check permissions.
     * @param  ?MEMBER $member_id The member to check permissions as (null: current user).
     * @param  boolean $support_crosslinks Whether to allow cross links to other modules (identifiable via a full-page-link rather than a screen-name).
     * @param  boolean $be_deferential Whether to avoid any entry-point (or even return null to disable the page in the Sitemap) if we know another module, or page_group, is going to link to that entry-point. Note that "!" and "browse" entry points are automatically merged with container page nodes (likely called by page-groupings) as appropriate.
     * @return ?array A map of entry points (screen-name=>language-code/string or screen-name=>[language-code/string, icon-theme-image]) (null: disabled).
     */
    public function get_entry_points($check_perms = true, $member_id = null, $support_crosslinks = true, $be_deferential = false)
    {
        if ((!$check_perms || !is_guest($member_id)) && ($GLOBALS['SITE_DB']->query_select_value('ecom_subscriptions', 'COUNT(*)') > 0)) {
            return array(
                'browse' => array('MY_SUBSCRIPTIONS', 'menu/adminzone/audit/ecommerce/subscriptions'),
            );
        }
        return array();
    }

    public $title;

    /**
     * Module pre-run function. Allows us to know metadata for <head> before we start streaming output.
     *
     * @return ?Tempcode Tempcode indicating some kind of exceptional output (null: none).
     */
    public function pre_run()
    {
        $type = get_param_string('type', 'browse');

        require_code('ecommerce');

        if ($type == 'browse') {
            $this->title = get_screen_title('MY_SUBSCRIPTIONS');
        }

        if ($type == 'cancel') {
            breadcrumb_set_parents(array(array('_SELF:_SELF:browse', do_lang_tempcode('MY_SUBSCRIPTIONS'))));

            $this->title = get_screen_title('SUBSCRIPTION_CANCEL');
        }

        return null;
    }

    /**
     * Execute the module.
     *
     * @return Tempcode The result of execution.
     */
    public function run()
    {
        require_css('ecommerce');

        // Kill switch
        if ((ecommerce_test_mode()) && (!$GLOBALS['IS_ACTUALLY_ADMIN']) && (!has_privilege(get_member(), 'access_ecommerce_in_test_mode'))) {
            warn_exit(do_lang_tempcode('PURCHASE_DISABLED'));
        }

        if (is_guest()) {
            access_denied('NOT_AS_GUEST');
        }

        $type = get_param_string('type', 'browse');

        if ($type == 'browse') {
            return $this->my();
        }
        if ($type == 'cancel') {
            return $this->cancel();
        }
        return new Tempcode();
    }

    /**
     * Show my subscriptions.
     *
     * @return Tempcode The interface.
     */
    public function my()
    {
        $member_id = get_member();
        if (has_privilege(get_member(), 'assume_any_member')) {
            $member_id = get_param_integer('id', $member_id);
        }

        require_code('ecommerce_subscriptions');
        $_subscriptions = find_member_subscriptions($member_id);

        $subscriptions = array();
        foreach ($_subscriptions as $_subscription) {
            $subscriptions[] = prepare_templated_subscription($_subscription);
        }

        return do_template('ECOM_SUBSCRIPTIONS_SCREEN', array('_GUID' => 'e39cd1883ba7b87599314c1f8b67902d', 'TITLE' => $this->title, 'SUBSCRIPTIONS' => $subscriptions));
    }

    /**
     * Cancel a subscription.
     *
     * @return Tempcode The interface.
     */
    public function cancel()
    {
        $id = get_param_integer('id');
        $payment_gateway = $GLOBALS['SITE_DB']->query_select_value_if_there('ecom_subscriptions', 's_payment_gateway', array('id' => $id));
        if ($payment_gateway === null) {
            warn_exit(do_lang_tempcode('MISSING_RESOURCE'));
        }

        if (!in_array($payment_gateway, array('', 'manual', 'points'))) {
            require_code('hooks/systems/payment_gateway/' . filter_naughty($payment_gateway));
            $payment_gateway_object = object_factory($payment_gateway);
            if ($payment_gateway_object->auto_cancel($id) !== true) {
                // Because we cannot TRIGGER a REMOTE cancellation, we have it so the local user action triggers that notification, informing the staff to manually do a remote cancellation
                require_code('notifications');
                $username = $GLOBALS['FORUM_DRIVER']->get_username(get_member());
                dispatch_notification('subscription_cancelled_staff', null, do_lang('SUBSCRIPTION_CANCELLED_SUBJECT', null, null, null, get_site_default_lang()), do_notification_lang('SUBSCRIPTION_CANCELLED_BODY', strval($id), $username, null, get_site_default_lang()));
            }
        }

        $GLOBALS['SITE_DB']->query_update('ecom_subscriptions', array('s_state' => 'cancelled'), array('id' => $id, 's_member_id' => get_member()), '', 1);

        $url = build_url(array('page' => '_SELF'), '_SELF');
        return redirect_screen($this->title, $url, do_lang_tempcode('SUCCESS'));
    }
}
