<?php /*

 Composr
 Copyright (c) ocProducts, 2004-2016

 See text/EN/licence.txt for full licencing information.


 NOTE TO PROGRAMMERS:
   Do not edit this file. If you need to make changes, save your changed file to the appropriate *_custom folder
   **** If you ignore this advice, then your website upgrades (e.g. for bug fixes) will likely kill your changes ****

*/

/*EXTRA FUNCTIONS: ftp_.**/

/**
 * @license    http://opensource.org/licenses/cpal_1.0 Common Public Attribution License
 * @copyright  ocProducts Ltd
 * @package    backup
 */

/**
 * Write PHP code for the restoration of database data into file.
 *
 * @param  resource $logfile The logfile to write to
 * @param  ID_TEXT $db_meta The meta tablename
 * @param  ID_TEXT $db_meta_indices The index-meta tablename
 * @param  resource $install_php_file File to write in to
 */
function get_table_backup($logfile, $db_meta, $db_meta_indices, &$install_php_file)
{
    $GLOBALS['NO_DB_SCOPE_CHECK'] = true;

    // Get a list of tables
    $tables = $GLOBALS['SITE_DB']->query_select($db_meta, array('DISTINCT m_table AS m_table'));

    // For each table, build up a Composr table creation command
    foreach ($tables as $_table) {
        $table = $_table['m_table'];

        $fields = $GLOBALS['SITE_DB']->query_select($db_meta, array('*'), array('m_table' => $table));

        fwrite($install_php_file, preg_replace('#^#m', '//', "   \$GLOBALS['SITE_DB']->drop_table_if_exists('$table');\n"));
        $array = '';
        foreach ($fields as $field) {
            $name = $field['m_name'];
            $type = $field['m_type'];

            if ($array != '') {
                $array .= ",\n";
            }
            $array .= "    '" . $name . "' => '" . $type . "'";
        }
        fwrite($install_php_file, preg_replace('#^#m', '//', "   \$GLOBALS['SITE_DB']->create_table('$table',array(\n$array),true,true);\n"));

        require_code('database_relations');
        if (!table_has_purpose_flag($table, TABLE_PURPOSE__NO_BACKUPS)) {
            $start = 0;
            do {
                $data = $GLOBALS['SITE_DB']->query_select($table, array('*'), null, '', 100, $start, false, array());
                foreach ($data as $d) {
                    $list = '';
                    $value = mixed();
                    foreach ($d as $name => $value) {
                        if (multi_lang_content()) {
                            if (($table == 'translate') && ($name == 'text_parsed')) {
                                $value = '';
                            }
                        } else {
                            if (strpos($name, '__text_parsed') !== false) {
                                $value = '';
                            }
                        }

                        if (is_null($value)) {
                            continue;
                        }
                        if ($list != '') {
                            $list .= ',';
                        }
                        $list .= "'" . (is_string($name) ? $name : strval($name)) . "'=>";
                        if (is_integer($value)) {
                            $list .= strval($value);
                        } elseif (is_float($value)) {
                            $list .= float_to_raw_string($value);
                        } else {
                            $list .= '"' . php_addslashes($value) . '"';
                        }
                    }
                    fwrite($install_php_file, preg_replace('#^#m', '//', "   \$GLOBALS['SITE_DB']->query_insert('$table',array($list));\n"));
                }

                $start += 100;
            } while (count($data) != 0);
        }

        fwrite($logfile, 'Backed up table ' . $table . "\n");
    }

    // For each index, build up a Composr index creation command
    $indices = $GLOBALS['SITE_DB']->query_select($db_meta_indices, array('*'));
    foreach ($indices as $index) {
        if (fwrite($install_php_file, preg_replace('#^#m', '//', '   $GLOBALS[\'SITE_DB\']->create_index(\'' . $index['i_table'] . '\',\'' . $index['i_name'] . '\',array(\'' . str_replace(',', '\',\'', $index['i_fields']) . '\'));' . "\n")) == 0) {
            warn_exit(do_lang_tempcode('COULD_NOT_SAVE_FILE'));
        }
    }

    $GLOBALS['NO_DB_SCOPE_CHECK'] = false;
}

/**
 * Backend function to do a backup (meant to be run as a shutdown function - essentially a background task).
 *
 * @param  string $file The filename to backup to
 * @param  string $b_type The type of backup to do
 * @set    full incremental
 * @param  integer $max_size The maximum size of a file to include in the backup
 * @return Tempcode Success message
 */
function make_backup_2($file, $b_type, $max_size) // This is called as a shutdown function and thus cannot script-timeout
{
    if (!file_exists(get_custom_file_base() . '/exports/backups')) {
        require_code('files2');
        make_missing_directory(get_custom_file_base() . '/exports/backups');
    }

    if (php_function_allowed('set_time_limit')) {
        set_time_limit(0);
    }
    $logfile_path = get_custom_file_base() . '/exports/backups/' . $file . '.txt';
    $logfile = @fopen($logfile_path, GOOGLE_APPENGINE ? 'wb' : 'wt') or intelligent_write_error($logfile_path); // .txt file because IIS doesn't allow .log download
    safe_ini_set('log_errors', '1');
    safe_ini_set('error_log', $logfile_path);
    fwrite($logfile, 'This is a log file for a Composr backup. The backup is not complete unless this log terminates with a completion message.' . "\n\n");

    require_code('tar');
    $myfile = tar_open(get_custom_file_base() . '/exports/backups/' . filter_naughty($file) . '.tmp', 'wb');

    // Write readme.txt file
    tar_add_file($myfile, 'readme.txt', do_lang('BACKUP_README', get_timezoned_date(time())), 0664, time());

    // Write restore.php file
    $template = get_custom_file_base() . '/data_custom/modules/admin_backup/restore.php.pre';
    if (!file_exists($template)) {
        $template = get_file_base() . '/data/modules/admin_backup/restore.php.pre';
    }
    $_install_php_file = file_get_contents($template);
    $place = strpos($_install_php_file, '{!!DB!!}');
    $__install_php_file = cms_tempnam();
    $__install_data_php_file = cms_tempnam();
    $install_php_file = fopen($__install_php_file, 'wb');
    $install_data_php_file = fopen($__install_data_php_file, 'wb');
    fwrite($install_php_file, substr($_install_php_file, 0, $place));
    fwrite($install_data_php_file, "<" . "?php

//COMMANDS BEGIN
//\$GLOBALS['SITE_DB']->drop_table_if_exists('db_meta');
//\$GLOBALS['SITE_DB']->create_table('db_meta', array(
// 'm_table' => '*ID_TEXT',
// 'm_name' => '*ID_TEXT',
// 'm_type' => 'ID_TEXT'
//));
//
//\$GLOBALS['SITE_DB']->drop_table_if_exists('db_meta_indices');
//\$GLOBALS['SITE_DB']->create_table('db_meta_indices', array(
// 'i_table' => '*ID_TEXT',
// 'i_name' => '*ID_TEXT',
// 'i_fields' => '*ID_TEXT',
//));
");
    get_table_backup($logfile, 'db_meta', 'db_meta_indices', $install_data_php_file);

    if (fwrite($install_php_file, substr($_install_php_file, $place + 8)) == 0) {
        warn_exit(do_lang_tempcode('COULD_NOT_SAVE_FILE'));
    }
    fclose($install_php_file);
    fclose($install_data_php_file);

    tar_add_file($myfile, 'restore.php', $__install_php_file, 0664, time(), true);
    tar_add_file($myfile, 'restore_data.php', $__install_data_php_file, 0664, time(), true);
    @unlink($__install_php_file);

    if ($b_type == 'full') {
        set_value('last_backup', strval(time()));
        $original_files = (get_param_integer('keep_backup_alien', 0) == 1) ? unserialize(file_get_contents(get_file_base() . '/data/files.dat')) : null;
        $root_only_dirs = array_merge(find_all_zones(false, false, true), array(
            'data', 'data_custom',
            'exports', 'imports',
            'lang', 'lang_custom',
            'caches',
            'pages',
            'safe_mode_temp',
            'sources', 'sources_custom',
            'text', 'text_custom',
            'themes',
            'uploads',

            'site', // In case of collapsed zones blocking in
        ));
        tar_add_folder($myfile, $logfile, get_custom_file_base(), $max_size, '', $original_files, $root_only_dirs, !running_script('cron_bridge'));
    } elseif ($b_type == 'incremental') {
        $threshold = intval(get_value('last_backup'));

        set_value('last_backup', strval(time()));
        $directory = tar_add_folder_incremental($myfile, $logfile, get_custom_file_base(), $threshold, $max_size);
        $_directory = '';
        foreach ($directory as $d) {
            $a = '';
            foreach ($d as $k => $v) {
                if ($a != '') {
                    $a .= ", ";
                }
                $a .= $k . '=' . $v;
            }
            $_directory .= $a . "\n";
        }
        tar_add_file($myfile, 'DIRECTORY', $_directory, 0664, time());
    } else {
        set_value('last_backup', strval(time()));
    }
    tar_close($myfile);
    if (!file_exists(get_custom_file_base() . '/exports/backups/' . filter_naughty($file) . '.tmp')) {
        warn_exit(do_lang_tempcode('INTERNAL_ERROR'));
    }
    rename(get_custom_file_base() . '/exports/backups/' . filter_naughty($file) . '.tmp', get_custom_file_base() . '/exports/backups/' . filter_naughty($file) . '.tar');
    sync_file('exports/backups/' . filter_naughty($file) . '.tar');
    fix_permissions('exports/backups/' . filter_naughty($file) . '.tar');

    $url = get_base_url() . '/exports/backups/' . $file . '.tar';
    if (function_exists('gzopen')) {
        if (fwrite($logfile, "\n" . do_lang('COMPRESSING') . "\n") == 0) {
            warn_exit(do_lang_tempcode('COULD_NOT_SAVE_FILE'));
        }

        $myfile = gzopen(get_custom_file_base() . '/exports/backups/' . $file . '.tar.gz.tmp', 'wb') or intelligent_write_error(get_custom_file_base() . '/exports/backups/' . $file . '.tar.gz.tmp');
        $tar_path = get_custom_file_base() . '/exports/backups/' . filter_naughty($file) . '.tar';

        $fp_in = fopen($tar_path, 'rb');
        while (!feof($fp_in)) {
            $read = fread($fp_in, 8192);
            gzwrite($myfile, $read, strlen($read));
        }
        fclose($fp_in);
        gzclose($myfile);

        rename(get_custom_file_base() . '/exports/backups/' . $file . '.tar.gz.tmp', get_custom_file_base() . '/exports/backups/' . $file . '.tar.gz');

        fix_permissions(get_custom_file_base() . '/exports/backups/' . $file . '.tar.gz');
        sync_file('exports/backups/' . filter_naughty($file) . '.tar.gz');
        $url = get_base_url() . '/exports/backups/' . $file . '.tar.gz';
    }

    if (fwrite($logfile, "\n" . do_lang('SUCCESS') . "\n") == 0) {
        warn_exit(do_lang_tempcode('COULD_NOT_SAVE_FILE'));
    }
    fclose($logfile);
    sync_file($logfile_path);
    fix_permissions($logfile_path);
    sync_file($logfile_path);

    // Remote backup
    $copy_server = get_option('backup_server_hostname');
    if ($copy_server != '') {
        $path_stub = get_custom_file_base() . '/exports/backups/';
        if (file_exists($path_stub . $file . '.tar.gz')) {
            $_file = $file . '.tar.gz';
        } elseif (file_exists($path_stub . $file . '.tar')) {
            $_file = $file . '.tar';
        } else {
            $file = null;
        }

        if (!is_null($file)) { // If the backup was actually made
            $copy_port = get_option('backup_server_port');
            if ($copy_port == '') {
                $copy_port = '21';
            }
            $copy_user = get_option('backup_server_user');
            if ($copy_user == '') {
                $copy_user = 'anonymous';
            }
            $copy_password = get_option('backup_server_password');
            if (($copy_password == '') && ($copy_user == 'anonymous')) {
                $copy_password = get_option('staff_address');
            }
            $copy_path = get_option('backup_server_path');
            if ($copy_path == '') {
                $copy_path = $_file;
            } elseif ((substr($copy_path, -1) == '/') || ($copy_path == '')) {
                $copy_path .= $_file;
            }

            $error = false;
            $ftp_connection = @ftp_connect($copy_server, intval($copy_port));
            if ($ftp_connection !== false) {
                if (@ftp_login($ftp_connection, $copy_user, $copy_password)) {
                    @ftp_delete($ftp_connection, $path_stub . $_file);
                    if (@ftp_put($ftp_connection, $copy_path, $path_stub, FTP_BINARY) === false) {
                        $error = true;
                    }
                } else {
                    $error = true;
                }
                @ftp_close($ftp_connection);
            } else {
                $error = true;
            }

            // If an error occurred, send a notification about it
            if ($error) {
                require_lang('backups');
                require_code('notifications');
                $subject = do_lang('FAILED_TO_UPLOAD_BACKUP_SUBJECT', null, null, null, get_site_default_lang());
                $message = do_notification_lang('FAILED_TO_UPLOAD_BACKUP_BODY', $copy_server, null, null, get_site_default_lang());
                dispatch_notification('error_occurred', null, $subject, $message, null, A_FROM_SYSTEM_PRIVILEGED);
            }
        }
    }

    return do_lang_tempcode('BACKUP_FINISHED', escape_html($url));
}
