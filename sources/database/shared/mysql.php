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
 * @package    core_database_drivers
 */

/**
 * Base class for MySQL database drivers.
 *
 * @package    core_database_drivers
 */
class Database_super_mysql extends DatabaseDriver
{
    /**
     * Get the default user for making db connections (used by the installer as a default).
     *
     * @return string The default user for db connections
     */
    public function default_user()
    {
        return 'root';
    }

    /**
     * Get the default password for making db connections (used by the installer as a default).
     *
     * @return string The default password for db connections
     */
    public function default_password()
    {
        return '';
    }

    /**
     * Find whether the database may run GROUP BY unfettered with restrictions on the SELECT'd fields having to be represented in it or aggregate functions
     *
     * @return boolean Whether it can
     */
    public function can_arbitrary_groupby()
    {
        return true;
    }

    /**
     * Find whether expression ordering support is present
     *
     * @return boolean Whether it is
     */
    public function has_expression_ordering()
    {
        return true;
    }

    /**
     * Find whether collate support is present
     *
     * @return boolean Whether it is
     */
    public function has_collate_settings()
    {
        return true;
    }

    /**
     * Find whether update queries may have joins
     *
     * @return boolean Whether it is
     */
    public function has_update_joins()
    {
        return true;
    }

    /**
     * Find whether text fields can/should have default values.
     *
     * @return boolean Whether they do
     */
    public function has_default_for_text_fields()
    {
        return false;
    }

    /**
     * Get the character used to surround fields to protect from keyword status.
     *
     * @return string Character (blank: has none defined)
     */
    public function get_field_encapsulator()
    {
        return '`';
    }

    /**
     * Create an SQL cast.
     *
     * @param  string $field The field identifier
     * @param  string $type The type wanted
     * @set CHAR INT
     * @return string The database type
     */
    public function cast($field, $type)
    {
        switch ($type) {
            case 'CHAR':
                $_type = $type;
                break;

            case 'INT':
                $_type = 'SIGNED';
                break;

            default:
                fatal_exit(do_lang_tempcode('INTERNAL_ERROR'));
        }

        return 'CAST(' . $field . ' AS ' . $_type . ')';
    }

    /**
     * Get queries needed to initialise the DB connection.
     *
     * @return array List of queries
     */
    protected function get_init_queries()
    {
        global $SITE_INFO;
        if (empty($SITE_INFO['database_charset'])) {
            $SITE_INFO['database_charset'] = (get_charset() == 'utf-8') ? 'utf8mb4' : 'latin1';
        }

        $queries = array();

        $queries[] = 'SET wait_timeout=28800';
        $queries[] = 'SET sql_big_selects=1';
        $queries[] = 'SET max_allowed_packet=104857600';

        $queries[] = $this->strict_mode_query(true);
        // NB: Can add ,ONLY_FULL_GROUP_BY for testing on what other DBs will do, but can_arbitrary_groupby() would need to be made to return false

        return $queries;
    }

    /**
     * Get a strict mode set query. Takes into account configuration also.
     *
     * @param  boolean $setting Whether it is on (may be overridden be configuration)
     * @return ?string The query (null: none)
     */
    public function strict_mode_query($setting)
    {
        if ((get_forum_type() == 'cns') && (!$GLOBALS['IN_MINIKERNEL_VERSION'])) {
            $query = 'SET sql_mode=\'STRICT_ALL_TABLES\'';
        } else {
            $query = 'SET sql_mode=\'MYSQL40\'';
        }
        // NB: Can add ,ONLY_FULL_GROUP_BY for testing on what other DBs will do, but can_arbitrary_groupby() would need to be made to return false

        return $query;
    }

    /**
     * Find if a database query may run, showing errors if it cannot
     *
     * @param  string $query The complete SQL query
     * @param  mixed $connection The DB connection
     * @param  boolean $get_insert_id Whether to get the autoincrement ID created for an insert query
     * @return boolean Whether it can
     */
    public function query_may_run($query, $connection, $get_insert_id)
    {
        if (isset($query[500000])) { // Let's hope we can fail on this, because it's a huge query. We can only allow it if MySQL can.
            $test_result = $this->query('SHOW VARIABLES LIKE \'max_allowed_packet\'', $connection, null, null, true);

            if (!is_array($test_result)) {
                return false;
            }
            if (intval($test_result[0]['Value']) < intval(strlen($query) * 1.2)) {
                /*@mysql_query('SET max_allowed_packet=' . strval(intval(strlen($query) * 1.3)), $connection); Does not work well, as MySQL server has gone away error will likely just happen instead */

                if ($get_insert_id) {
                    $this->failed_query_exit(do_lang_tempcode('QUERY_FAILED_TOO_BIG', escape_html($query), escape_html(integer_format(strlen($query))), escape_html(integer_format(intval($test_result[0]['Value'])))));
                } else {
                    $this->failed_query_message(do_lang_tempcode('QUERY_FAILED_TOO_BIG', escape_html(substr($query, 0, 300)) . '...', escape_html(integer_format(strlen($query))), escape_html(integer_format(intval($test_result[0]['Value'])))));
                }
                return false;
            }
        }

        return true;
    }

    /**
     * Set a time limit on future queries.
     * Not all database drivers support this.
     *
     * @param  integer $seconds The time limit in seconds
     * @param  mixed $connection The DB connection
     */
    public function set_query_time_limit($seconds, $connection)
    {
        $this->query('SET SESSION MAX_EXECUTION_TIME=' . strval($seconds * 1000), $connection, null, null, true); // Only works in MySQL 5.7+
    }

    /**
     * Encode a LIKE string comparision fragement for the database system. The pattern is a mixture of characters and ? and % wildcard symbols.
     *
     * @param  string $pattern The pattern
     * @return string The encoded pattern
     */
    public function encode_like($pattern)
    {
        return str_replace('\\\\_'/*MySQL escaped underscores*/, '\\_', $this->escape_string($pattern));
    }

    /**
     * Handle messaging for a failed query.
     *
     * @param  string $query The complete SQL query
     * @param  string $err The error message
     * @param  mixed $connection The DB connection
     */
    protected function handle_failed_query($query, $err, $connection)
    {
        if (function_exists('ocp_mark_as_escaped')) {
            ocp_mark_as_escaped($err);
        }
        if ((!running_script('upgrader')) && ((!get_mass_import_mode()) || (get_param_integer('keep_fatalistic', 0) == 1)) && (strpos($err, 'Duplicate entry') === false)) {
            $matches = array();
            if (preg_match('#/(\w+)\' is marked as crashed and should be repaired#U', $err, $matches) !== 0) {
                $this->query('REPAIR TABLE ' . $matches[1], $connection);
            }

            if ((!function_exists('do_lang')) || (do_lang('QUERY_FAILED', null, null, null, null, false) === null)) {
                $this->failed_query_exit(htmlentities('Query failed: ' . $query . ' : ' . $err));
            }
            $this->failed_query_exit(do_lang_tempcode('QUERY_FAILED', escape_html($query), ($err)));
        } else {
            $this->failed_query_echo(htmlentities('Database query failed: ' . $query . ' [') . ($err) . htmlentities(']'));
        }
    }

    /**
     * Get a map of Composr field types, to actual database types.
     *
     * @param  boolean $for_alter Whether this is for adding a table field
     * @return array The map
     */
    public function get_type_remap($for_alter = false)
    {
        $type_remap = array(
            'AUTO' => $for_alter ? 'integer unsigned PRIMARY KEY auto_increment' : 'integer unsigned auto_increment',
            'AUTO_LINK' => 'integer', // not unsigned because it's useful to have -ve for temporary usage while importing (NB: *_TRANS is signed, so trans fields are not perfectly AUTO_LINK compatible and can have double the positive range -- in the real world it will not matter though)
            'INTEGER' => 'integer',
            'UINTEGER' => 'integer unsigned',
            'SHORT_INTEGER' => 'tinyint',
            'REAL' => 'real',
            'BINARY' => 'tinyint(1)',
            'MEMBER' => 'integer', // not unsigned because it's useful to have -ve for temporary usage while importing
            'GROUP' => 'integer', // not unsigned because it's useful to have -ve for temporary usage while importing
            'TIME' => 'integer unsigned',
            'LONG_TRANS' => 'integer unsigned',
            'SHORT_TRANS' => 'integer unsigned',
            'LONG_TRANS__COMCODE' => 'integer',
            'SHORT_TRANS__COMCODE' => 'integer',
            'SHORT_TEXT' => 'varchar(255)',
            'LONG_TEXT' => 'longtext',
            'ID_TEXT' => 'varchar(80)',
            'MINIID_TEXT' => 'varchar(40)',
            'IP' => 'varchar(40)', // 15 for ip4, but we now support ip6
            'LANGUAGE_NAME' => 'varchar(5)',
            'URLPATH' => 'varchar(255) BINARY',
        );
        return $type_remap;
    }

    /**
     * Get SQL for creating a new table.
     *
     * @param  ID_TEXT $table_name The table name
     * @param  array $fields A map of field names to Composr field types (with *#? encodings)
     * @param  mixed $connection The DB connection to make on
     * @param  ID_TEXT $raw_table_name The table name with no table prefix
     * @param  boolean $save_bytes Whether to use lower-byte table storage, with tradeoffs of not being able to support all unicode characters; use this if key length is an issue
     * @return array List of SQL queries to run
     */
    public function create_table($table_name, $fields, $connection, $raw_table_name, $save_bytes = false)
    {
        $type_remap = $this->get_type_remap();

        $_fields = '';
        $keys = '';
        foreach ($fields as $name => $type) {
            if ($type[0] == '*') { // Is a key
                $type = substr($type, 1);
                if ($keys !== '') {
                    $keys .= ', ';
                }
                $keys .= $name;
            }

            if ($type[0] == '?') { // Is perhaps null
                $type = substr($type, 1);
                $perhaps_null = 'NULL';
            } else {
                $perhaps_null = 'NOT NULL';
            }

            $type = isset($type_remap[$type]) ? $type_remap[$type] : $type;

            $_fields .= '    ' . $name . ' ' . $type;
            /*if (substr($name, -13) == '__text_parsed') {    BLOB/TEXT column 'description__text_parsed' can't have a default value
                $_fields .= ' DEFAULT \'\'';
            } else*/
            if (substr($name, -13) == '__source_user') {
                $_fields .= ' DEFAULT ' . strval(db_get_first_id());
            }
            $_fields .= ' ' . $perhaps_null . ',' . "\n";
        }

        $innodb = ((function_exists('get_value')) && (get_value('innodb') == '1'));
        $table_type = ($innodb ? 'INNODB' : 'MyISAM');
        $type_key = 'engine';
        /*if ($raw_table_name == 'sessions') {
            $table_type = 'HEAP';   Some MySQL servers are very regularly reset
        }*/

        $query = 'CREATE TABLE ' . $table_name . ' (' . "\n" . $_fields . '    PRIMARY KEY (' . $keys . ")\n)";

        global $SITE_INFO;
        if (empty($SITE_INFO['database_charset'])) {
            $SITE_INFO['database_charset'] = (get_charset() == 'utf-8') ? 'utf8mb4' : 'latin1';
        }
        $charset = $SITE_INFO['database_charset'];
        if ($charset == 'utf8mb4' && $save_bytes) {
            $charset = 'utf8';
        }

        $query .= ' CHARACTER SET=' . preg_replace('#\_.*$#', '', $charset);

        $query .= ' ' . $type_key . '=' . $table_type;

        return array($query);
    }

    /**
     * Find whether drop table "if exists" is present
     *
     * @return boolean Whether it is
     */
    public function supports_drop_table_if_exists()
    {
        return true;
    }

    /**
     * Change the primary key of a table.
     *
     * @param  ID_TEXT $table_name The name of the table to create the index on
     * @param  array $new_key A list of fields to put in the new key
     * @param  mixed $connection The DB connection to make on
     */
    public function change_primary_key($table_name, $new_key, $connection)
    {
        $this->query('ALTER TABLE ' . $table_name . ' DROP PRIMARY KEY, ADD PRIMARY KEY (' . implode(',', $new_key) . ')', $connection);
    }

    /**
     * Get the number of rows in a table, with approximation support for performance (if necessary on the particular database backend).
     *
     * @param  string $table The table name
     * @param  mixed $connection The DB connection
     * @return ?integer The count (null: do it normally)
     */
    public function get_table_count_approx($table, $connection)
    {
        if (get_value('slow_counts') === '1') {
            $sql = 'SELECT TABLE_ROWS FROM information_schema.tables WHERE table_schema=DATABASE() AND TABLE_NAME=\'' . $this->escape_string($table) . '\'';
            $values = $this->query($sql, $connection, null, null, true);
            if (!isset($values[0])) {
                return null; // No result found
            }
            $first = $values[0];
            $v = current($first); // Result found
            return $v;
        }

        return null;
    }

    /**
     * Get minimum search length.
     * This is broadly MySQL-specific. For other databases we will usually return 4, although there may truly not be a limit on it.
     *
     * @param  mixed $connection The DB connection
     * @return integer Search length
     */
    public function get_minimum_search_length($connection)
    {
        static $min_word_length = null;
        if (is_null($min_word_length)) {
            $min_word_length = 4;
            $_min_word_length = $this->query('SHOW VARIABLES LIKE \'ft_min_word_len\'', $connection, null, null, true);
            if (isset($_min_word_length[0])) {
                $min_word_length = intval($_min_word_length[0]['Value']);
            }
        }
        return $min_word_length;
    }

    /**
     * Get SQL for creating a table index.
     *
     * @param  ID_TEXT $table_name The name of the table to create the index on
     * @param  ID_TEXT $index_name The index name (not really important at all)
     * @param  string $_fields Part of the SQL query: a comma-separated list of fields to use on the index
     * @param  mixed $connection The DB connection to make on
     * @param  ID_TEXT $raw_table_name The table name with no table prefix
     * @param  string $unique_key_fields The name of the unique key field for the table
     * @param  string $table_prefix The table prefix
     * @return array List of SQL queries to run
     */
    public function create_index($table_name, $index_name, $_fields, $connection, $raw_table_name, $unique_key_fields, $table_prefix)
    {
        if ($index_name[0] == '#') {
            $index_name = substr($index_name, 1);
            $type = 'FULLTEXT';
        } else {
            $type = 'INDEX';
        }
        return array('ALTER TABLE ' . $table_name . ' ADD ' . $type . ' ' . $index_name . ' (' . $_fields . ')');
    }

    /**
     * Find whether table truncation support is present
     *
     * @return boolean Whether it is
     */
    public function supports_truncate_table()
    {
        return true;
    }

    /**
     * Find whether full-text-search is present
     *
     * @param  mixed $connection The DB connection
     * @return boolean Whether it is
     */
    public function has_full_text($connection)
    {
        return true;
    }

    /**
     * Find whether full-text-boolean-search is present
     *
     * @return boolean Whether it is
     */
    public function has_full_text_boolean()
    {
        return true;
    }

    /**
     * Assemble part of a WHERE clause for doing full-text search
     *
     * @param  string $content Our match string (assumes "?" has been stripped already)
     * @param  boolean $boolean Whether to do a boolean full text search
     * @return string Part of a WHERE clause for doing full-text search
     */
    public function full_text_assemble($content, $boolean)
    {
        static $stopwords = null;
        if ($stopwords === null) {
            require_code('database_search');
            $stopwords = get_stopwords_list();
        }
        if (isset($stopwords[trim(strtolower($content), '"')])) {
            // This is an imperfect solution for searching for a stop-word
            // It will not cover the case where the stop-word is within the wider text. But we can't handle that case efficiently anyway
            return db_string_equal_to('?', trim($content, '"'));
        }

        if (!$boolean) {
            $content = str_replace('"', '', $content);
            if ((strtoupper($content) == $content) && (!is_numeric($content))) {
                return 'MATCH (?) AGAINST (_latin1\'' . $this->escape_string($content) . '\' COLLATE latin1_general_cs)';
            }
            return 'MATCH (?) AGAINST (\'' . $this->escape_string($content) . '\')';
        }

        return 'MATCH (?) AGAINST (\'' . $this->escape_string($content) . '\' IN BOOLEAN MODE)';
    }

    /**
     * Close the database connections. We don't really need to close them (will close at exit), just disassociate so we can refresh them.
     */
    public function close_connections()
    {
        $this->cache_db = array();
        $this->last_select_db = null;
    }
}
