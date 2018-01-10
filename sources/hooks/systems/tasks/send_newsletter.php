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
 * @package    newsletter
 */

/**
 * Hook class.
 */
class Hook_task_send_newsletter
{
    /**
     * Run the task hook.
     *
     * @param  integer $message_id The newsletter message in the newsletter archive
     * @param  LONG_TEXT $message The newsletter message
     * @param  SHORT_TEXT $subject The newsletter subject
     * @param  LANGUAGE_NAME $lang The language
     * @param  array $send_details A map describing what newsletters the newsletter is being sent to
     * @param  BINARY $html_only Whether to only send in HTML format
     * @param  string $from_email Override the email address the mail is sent from (blank: staff address)
     * @param  string $from_name Override the name the mail is sent from (blank: site name)
     * @param  integer $priority The message priority (1=urgent, 3=normal, 5=low)
     * @range  1 5
     * @param  string $csv_data CSV data of extra subscribers (blank: none). This is in the same Composr newsletter CSV format that we export elsewhere.
     * @param  ID_TEXT $mail_template The template used to show the email
     * @return ?array A tuple of at least 2: Return mime-type, content (either Tempcode, or a string, or a filename and file-path pair to a temporary file), map of HTTP headers if transferring immediately, map of ini_set commands if transferring immediately (null: show standard success message)
     */
    public function run($message_id, $message, $subject, $lang, $send_details, $html_only, $from_email, $from_name, $priority, $csv_data, $mail_template)
    {
        $auto_pause = (get_option('newsletter_auto_pause') == '1');

        if ($auto_pause) {
            require_code('config2');
            set_option('newsletter_paused', '1');
        }

        require_code('newsletter');
        require_lang('newsletter');
        require_code('mail');

        $last_cron = get_value('last_cron');

        $blocked = newsletter_block_list();

        disable_php_memory_limit();

        $count = 0;

        $using_drip_queue = ($last_cron !== null) || (get_option('newsletter_paused') == '1');

        $in_html = false;
        if (stripos(trim($message), '<') === 0) {
            $in_html = true;
        } else {
            if ($html_only == 1) {
                $in_html = true;
            }
        }

        $max = 300;

        $already_queued = collapse_2d_complexity('d_to_email', 'tmp', $GLOBALS['SITE_DB']->query_select('newsletter_drip_send', array('d_to_email', '1 AS tmp'), array('d_message_id' => $message_id)));

        $start = 0;
        do {
            list($addresses, $hashes, $usernames, $forenames, $surnames, $ids,) = newsletter_who_send_to($send_details, $lang, $start, $max, false, $csv_data);

            $insert_maps = array( // We will do very efficient mass-inserts (making index maintenance and disk access much more efficient)
                'd_inject_time' => array(),
                'd_message_id' => array(),
                'd_message_binding' => array(),
                'd_to_email' => array(),
                'd_to_name' => array(),
            );

            // Send to all
            foreach ($addresses as $i => $email_address) {
                if (isset($blocked[$email_address])) {
                    continue;
                }

                if ($using_drip_queue) {
                    if (!isset($already_queued[$email_address])) {
                        $insert_map = array(
                            'd_inject_time' => time(),
                            'd_message_id' => $message_id,
                            'd_message_binding' => json_encode(array($forenames[$i], $surnames[$i], $usernames[$i], $ids[$i], $hashes[$i])), // Assortment of message binding details, could grow as Composr evolves, so we'll use JSON - and more efficient anyway, in terms of SQL performance (they don't need querying)
                            'd_to_email' => $email_address,
                            'd_to_name' => $usernames[$i],
                        );
                        foreach ($insert_map as $key => $val) {
                            $insert_maps[$key][] = $val;
                        }

                        $already_queued[$email_address] = 1;
                    }
                } else { // Unlikely to use this code path, but we should support operation without Cron in those rare cases. Code path not optimised
                    $newsletter_message_substituted = newsletter_variable_substitution($message, $subject, $forenames[$i], $surnames[$i], $usernames[$i], $email_address, $ids[$i], $hashes[$i]);
                    if (stripos(trim($message), '<') === 0) { // HTML
                        require_code('tempcode_compiler');
                        $_m = template_to_tempcode($newsletter_message_substituted);
                        $newsletter_message_substituted = $_m->evaluate($lang);
                    } else { // Comcode
                        if ($html_only == 1) {
                            $_m = comcode_to_tempcode($newsletter_message_substituted, get_member(), true);
                            $newsletter_message_substituted = $_m->evaluate($lang);
                        }
                    }

                    dispatch_mail(
                        $subject,
                        $newsletter_message_substituted,
                        array($email_address),
                        array($usernames[$i]),
                        $from_email,
                        $from_name,
                        array(
                            'priority' => $priority,
                            'no_cc' => true,
                            'as_admin' => true,
                            'in_html' => $in_html,
                            'mail_template' => $mail_template,
                            'bypass_queue' => true,
                            'smtp_sockets_use' => (get_option('newsletter_smtp_sockets_use') == '1'),
                            'smtp_sockets_host' => get_option('newsletter_smtp_sockets_host'),
                            'smtp_sockets_port' => intval(get_option('newsletter_smtp_sockets_port')),
                            'smtp_sockets_username' => get_option('newsletter_smtp_sockets_username'),
                            'smtp_sockets_password' => get_option('newsletter_smtp_sockets_password'),
                            'smtp_from_address' => get_option('newsletter_smtp_from_address'),
                            'enveloper_override' => (get_option('newsletter_enveloper_override') == '1'),
                            'allow_ext_images' => (get_option('newsletter_allow_ext_images') == '1'),
                            'website_email' => get_option('newsletter_website_email'),
                        )
                    );
                }

                $count++;

                if (function_exists('gc_collect_cycles')) {
                    gc_collect_cycles(); // Stop problem with PHP leaking memory
                }
            }
            $start += $max;

            if ($using_drip_queue && count($insert_maps['d_to_email']) > 0) {
                $GLOBALS['SITE_DB']->query_insert('newsletter_drip_send', $insert_maps);
            }
        } while (array_key_exists(0, $addresses));

        if ($count == 0) {
            return array('text/html', do_lang_tempcode('NEWSLETTER_NO_TARGET'));
        }

        if ($auto_pause) {
            require_code('notifications');
            $subject = do_lang('NEWSLETTER_PAUSED_SUBJECT');
            $newsletter_manage_url = build_url(array('page' => 'admin_newsletter'), get_module_zone('admin_newsletter'), array(), false, false, true);
            $message = do_lang('NEWSLETTER_PAUSED_BODY', escape_html($newsletter_manage_url->evaluate()));
            dispatch_notification('newsletter_paused', 'newsletter_' . strval(time()), $subject, $message, null, null, array('priority' => 4, 'create_ticket' => true));
        }

        $newsletter_manage_url = build_url(array('page' => 'admin_newsletter'), get_module_zone('admin_newsletter'));
        return array('text/html', do_lang_tempcode($auto_pause ? 'SENDING_NEWSLETTER_TO_QUEUE' : 'SENDING_NEWSLETTER', escape_html($newsletter_manage_url)));
    }
}
