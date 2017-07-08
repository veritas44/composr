<?php /*

 Composr
 Copyright (c) ocProducts, 2004-2017

 See text/EN/licence.txt for full licencing information.


 NOTE TO PROGRAMMERS:
   Do not edit this file. If you need to make changes, save your changed file to the appropriate *_custom folder
   **** If you ignore this advice, then your website upgrades (e.g. for bug fixes) will likely kill your changes ****

*/

/**
 * @license    http://opensource.org/licenses/cpal_1.0 Common Public Attribution License
 * @copyright  ocProducts Ltd
 * @package    cns_contact_member
 */

/**
 * Module page class.
 */
class Module_contact_member
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
        $info['version'] = 2;
        $info['locked'] = false;
        return $info;
    }

    public $title;
    public $member_id;
    public $username;
    public $to_name;

    /**
     * Uninstall the module.
     */
    public function uninstall()
    {
        $GLOBALS['SITE_DB']->query_delete('group_page_access', array('page_name' => 'contact_member'));
    }

    /**
     * Install the module.
     *
     * @param  ?integer $upgrade_from What version we're upgrading from (null: new install)
     * @param  ?integer $upgrade_from_hack What hack version we're upgrading from (null: new-install/not-upgrading-from-a-hacked-version)
     */
    public function install($upgrade_from = null, $upgrade_from_hack = null)
    {
        // Deny non-staff/Guest access to contact_member (as non-Guests can just use private topics and contact_member may be abused by spammers)
        $staff_groups = $GLOBALS['FORUM_DRIVER']->get_moderator_groups();
        $usergroups = $GLOBALS['FORUM_DRIVER']->get_usergroup_list(false, true);
        foreach (array_keys($usergroups) as $id) {
            if ((!isset($staff_groups[$id])) && $id != (db_get_first_id())) {
                $GLOBALS['SITE_DB']->query_delete('group_page_access', array('page_name' => 'contact_member', 'zone_name' => 'site', 'group_id' => $id), '', 1); // in case already exists
                $GLOBALS['SITE_DB']->query_insert('group_page_access', array('page_name' => 'contact_member', 'zone_name' => 'site', 'group_id' => $id));
            }
        }
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
        return array();
    }

    /**
     * Module pre-run function. Allows us to know metadata for <head> before we start streaming output.
     *
     * @return ?Tempcode Tempcode indicating some kind of exceptional output (null: none).
     */
    public function pre_run()
    {
        $type = get_param_string('type', 'browse');

        if (get_forum_type() != 'cns') {
            warn_exit(do_lang_tempcode('NO_CNS'));
        } else {
            cns_require_all_forum_stuff();
        }
        require_lang('cns');

        if ($type == 'browse') {
            attach_to_screen_header('<meta name="robots" content="noindex" />'); // XHTMLXHTML

            $member_id = get_param_integer('id');
            $username = $GLOBALS['FORUM_DRIVER']->get_username($member_id, true, USERNAME_DEFAULT_ERROR);

            $this->title = get_screen_title('EMAIL_MEMBER', true, array(escape_html($username)));

            $this->member_id = $member_id;
            $this->username = $username;
        }

        if ($type == 'actual') {
            $member_id = get_param_integer('id');
            $to_name = $GLOBALS['FORUM_DRIVER']->get_username($member_id, true);

            breadcrumb_set_parents(array(array('_SELF:_SELF:browse', do_lang_tempcode('EMAIL_MEMBER', escape_html($to_name)))));
            breadcrumb_set_self(do_lang_tempcode('DONE'));

            $this->title = get_screen_title('EMAIL_MEMBER', true, array(escape_html($GLOBALS['FORUM_DRIVER']->get_username($member_id, true))));

            $this->member_id = $member_id;
            $this->to_name = $to_name;
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
        require_lang('mail');
        require_lang('comcode');

        $type = get_param_string('type', 'browse');

        $member_id = get_param_integer('id');
        if (($GLOBALS['FORUM_DRIVER']->get_member_row_field($member_id, 'm_email_address') == '') || ((get_option('member_email_receipt_configurability') != '0') && ($GLOBALS['FORUM_DRIVER']->get_member_row_field($member_id, 'm_allow_emails') == 0)) || (is_guest($member_id)) || ($GLOBALS['FORUM_DRIVER']->is_banned($member_id))) {
            warn_exit(do_lang_tempcode('NO_ACCEPT_EMAILS'));
        }

        if ($type == 'browse') {
            return $this->gui();
        }
        if ($type == 'actual') {
            return $this->actual();
        }

        return new Tempcode();
    }

    /**
     * The UI to contact a member.
     *
     * @return Tempcode The UI
     */
    public function gui()
    {
        $member_id = $this->member_id;
        $username = $this->username;

        $text = do_lang_tempcode('EMAIL_MEMBER_TEXT');

        $fields = new Tempcode();
        require_code('form_templates');
        $default_email = (is_guest()) ? '' : $GLOBALS['FORUM_DRIVER']->get_member_row_field(get_member(), 'm_email_address');
        $default_name = (is_guest()) ? '' : $GLOBALS['FORUM_DRIVER']->get_username(get_member(), true);
        $name_field = form_input_line(do_lang_tempcode('NAME'), do_lang_tempcode('_DESCRIPTION_NAME'), 'name', $default_name, true);
        if ($default_name == '') {
            $fields->attach($name_field);
        }
        $email_field = form_input_email(do_lang_tempcode('EMAIL_ADDRESS'), do_lang_tempcode('YOUR_ADDRESS'), 'email_address', $default_email, true);
        if ($default_email == '') {
            $fields->attach($email_field);
        }
        $fields->attach(form_input_line(do_lang_tempcode('SUBJECT'), '', 'subject', get_param_string('subject', '', INPUT_FILTER_GET_COMPLEX), true));
        $fields->attach(form_input_text(do_lang_tempcode('MESSAGE'), '', 'message', get_param_string('message', '', INPUT_FILTER_GET_COMPLEX), true));
        if (addon_installed('captcha')) {
            require_code('captcha');
            if (use_captcha()) {
                $fields->attach(form_input_captcha());
                $text->attach(' ');
                $text->attach(do_lang_tempcode('FORM_TIME_SECURITY'));
            }
        }
        $size = $GLOBALS['FORUM_DRIVER']->get_member_row_field($member_id, 'm_max_email_attach_size_mb');
        $hidden = new Tempcode();
        if ($size != 0) {
            handle_max_file_size($hidden);
            $fields->attach(form_input_upload_multi(do_lang_tempcode('_ATTACHMENT'), do_lang_tempcode('EMAIL_ATTACHMENTS', escape_html(integer_format($size))), 'attachment', false));
        }
        if (!is_guest()) {
            if (ini_get('suhosin.mail.protect') !== '2') {
                $fields->attach(do_template('FORM_SCREEN_FIELD_SPACER', array('_GUID' => '7f7e5aa2fa469ebbca9ca61e9f869882', 'TITLE' => do_lang_tempcode('ADVANCED'), 'SECTION_HIDDEN' => true)));
                if ($default_name != '') {
                    $fields->attach($name_field);
                }
                if ($default_email != '') {
                    $fields->attach($email_field);
                }
                $fields->attach(form_input_username_multi(do_lang_tempcode('EMAIL_CC_ADDRESS'), do_lang_tempcode('DESCRIPTION_EMAIL_CC_ADDRESS'), 'cc_', array(), 0, false));
                $fields->attach(form_input_username_multi(do_lang_tempcode('EMAIL_BCC_ADDRESS'), do_lang_tempcode('DESCRIPTION_EMAIL_BCC_ADDRESS'), 'bcc_', array(), 0, false));
            }
        }
        $submit_name = do_lang_tempcode('SEND');
        $redirect = mixed();
        $redirect = get_param_string('redirect', '', INPUT_FILTER_URL_INTERNAL);
        if ($redirect == '') {
            $redirect = $GLOBALS['FORUM_DRIVER']->member_profile_url($member_id, true);
            if (is_object($redirect)) {
                $redirect = $redirect->evaluate();
            }
        }
        $post_url = build_url(array('page' => '_SELF', 'type' => 'actual', 'id' => $member_id, 'redirect' => protect_url_parameter($redirect)), '_SELF');

        return do_template('FORM_SCREEN', array(
            '_GUID' => 'e06557e6eceacf1f46ee930c99ac5bb5',
            'TITLE' => $this->title,
            'HIDDEN' => $hidden,
            'JS_FUNCTION_CALLS' => ((function_exists('captcha_ajax_check_function')) && (captcha_ajax_check_function() != '')) ? array(captcha_ajax_check_function()) : array(),
            'FIELDS' => $fields,
            'TEXT' => $text,
            'SUBMIT_ICON' => 'buttons__send',
            'SUBMIT_NAME' => $submit_name,
            'URL' => $post_url,
            'SUPPORT_AUTOSAVE' => true,
        ));
    }

    /**
     * The actualiser to contact a member.
     *
     * @return Tempcode The UI
     */
    public function actual()
    {
        if (addon_installed('captcha')) {
            require_code('captcha');
            enforce_captcha();
        }

        $member_id = $this->member_id;
        $to_name = $this->to_name;

        $email_address = $GLOBALS['FORUM_DRIVER']->get_member_row_field($member_id, 'm_email_address');
        if ($email_address === null) {
            fatal_exit(do_lang_tempcode('INTERNAL_ERROR'));
        }

        $join_time = $GLOBALS['FORUM_DRIVER']->get_member_row_field($member_id, 'm_join_time');

        if ($to_name === null) {
            warn_exit(do_lang_tempcode('MEMBER_NO_EXIST'));
        }

        $from_email = trim(post_param_string('email_address'));
        require_code('type_sanitisation');
        if (!is_email_address($from_email)) {
            warn_exit(do_lang_tempcode('INVALID_EMAIL_ADDRESS'));
        }
        $from_name = post_param_string('name');

        require_code('antispam');
        inject_action_spamcheck(null, $from_email);

        $extra_cc_addresses = array();
        $extra_bcc_addresses = array();
        if (!is_guest()) {
            foreach ($_POST as $key => $val) {
                if (($val != '') && ((substr($key, 0, 3) == 'cc_') || (substr($key, 0, 4) == 'bcc_'))) {
                    $address = post_param_string($key);
                    if (!is_email_address($address)) {
                        $address = $GLOBALS['FORUM_DB']->query_select_value_if_there('f_members', 'm_email_address', array('m_username' => $address));
                        if ($address === null) {
                            warn_exit(do_lang_tempcode('MEMBER_NO_EXIST'));
                        }
                        if (!is_email_address($address)) {
                            warn_exit(do_lang_tempcode('INVALID_EMAIL_ADDRESS'));
                        }
                    }
                    if (substr($key, 0, 3) == 'cc_') {
                        $extra_cc_addresses[] = $address;
                    }
                    if (substr($key, 0, 4) == 'bcc_') {
                        $extra_bcc_addresses[] = $address;
                    }
                }
            }
        }

        require_code('mail');
        $attachments = array();
        $size_so_far = 0;
        require_code('uploads');
        is_plupload(true);
        foreach ($_FILES as $file) {
            if ((is_plupload()) || (is_uploaded_file($file['tmp_name']))) {
                $attachments[$file['tmp_name']] = $file['name'];
                $size_so_far += $file['size'];
            } else {
                if ((defined('UPLOAD_ERR_NO_FILE')) && (array_key_exists('error', $file)) && ($file['error'] != UPLOAD_ERR_NO_FILE)) {
                    warn_exit(do_lang_tempcode('ERROR_UPLOADING_ATTACHMENTS'));
                }
            }
        }
        $size = $GLOBALS['FORUM_DRIVER']->get_member_row_field($member_id, 'm_max_email_attach_size_mb');
        if ($size_so_far > $size * 1024 * 1024) {
            warn_exit(do_lang_tempcode('EXCEEDED_ATTACHMENT_SIZE', escape_html(integer_format($size))));
        }
        dispatch_mail(
            do_lang('EMAIL_MEMBER_SUBJECT', get_site_name(), post_param_string('subject'), null, get_lang($member_id)),
            post_param_string('message'),
            array($email_address),
            $to_name,
            $from_email,
            $from_name,
            array(
                'attachments' => $attachments,
                'as' => get_member(),
                'bypass_queue' => (count($attachments) != 0),
                'extra_cc_addresses' => $extra_cc_addresses,
                'extra_bcc_addresses' => $extra_bcc_addresses,
                'require_recipient_valid_since' => $join_time,
            )
        );

        log_it('EMAIL', strval($member_id), $to_name);

        $url = get_param_string('redirect', false, INPUT_FILTER_URL_INTERNAL);
        return redirect_screen($this->title, $url, do_lang_tempcode('SUCCESS'));
    }
}
