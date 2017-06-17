<?php /*

 Composr
 Copyright (c) ocProducts, 2004-2016

 See text/EN/licence.txt for full licencing information.


 NOTE TO PROGRAMMERS:
   Do not edit this file. If you need to make changes, save your changed file to the appropriate *_custom folder
   **** If you ignore this advice, then your website upgrades (e.g. for bug fixes) will likely kill your changes ****

*/

/*EXTRA FUNCTIONS: pg\_.+|get_current_user*/

/**
 * @license    http://opensource.org/licenses/cpal_1.0 Common Public Attribution License
 * @copyright  ocProducts Ltd
 * @package    core_database_drivers
 */

// See sup_postgresql tutorial for documentation on using PostgreSQL.

/**
 * Database Driver.
 *
 * @package    core_database_drivers
 */
class Database_Static_postgresql extends DatabaseDriver
{
    public $cache_db = array();

    /**
     * Get the default user for making db connections (used by the installer as a default).
     *
     * @return string The default user for db connections
     */
    public function default_user()
    {
        if ((php_function_allowed('get_current_user'))) {
            //$_ret = posix_getpwuid(posix_getuid()); $ret = $_ret['name'];
            //$ret = posix_getlogin();
            $ret = get_current_user();
            if (!in_array($ret, array('apache', 'nobody', 'www', '_www'))) {
                return $ret;
            }
        }
        return 'postgres';
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
     * Get a database connection. This function shouldn't be used by you, as a connection to the database is established automatically.
     *
     * @param  boolean $persistent Whether to create a persistent connection
     * @param  string $db_name The database name
     * @param  string $db_host The database host (the server)
     * @param  string $db_user The database connection username
     * @param  string $db_password The database connection password
     * @param  boolean $fail_ok Whether to on error echo an error and return with a null, rather than giving a critical error
     * @return ?array A database connection (null: failed)
     */
    public function get_connection($persistent, $db_name, $db_host, $db_user, $db_password, $fail_ok = false)
    {
        // Potential caching
        if (isset($this->cache_db[$db_name][$db_host])) {
            return $this->cache_db[$db_name][$db_host];
        }

        if (!function_exists('pg_pconnect')) {
            $error = 'The postgreSQL PHP extension not installed (anymore?). You need to contact the system administrator of this server.';
            if ($fail_ok) {
                echo ((running_script('install')) && (get_param_string('type', '') == 'ajax_db_details')) ? strip_html($error) : $error;
                return null;
            }
            critical_error('PASSON', $error);
        }

        $connection = $persistent ? @pg_pconnect('host=' . $db_host . ' dbname=' . $db_name . ' user=' . $db_user . ' password=' . $db_password) : @pg_connect('host=' . $db_host . ' dbname=' . $db_name . ' user=' . $db_user . ' password=' . $db_password);
        if ($connection === false) {
            $error = 'Could not connect to database-server (' . @pg_last_error() . ')';
            if ($fail_ok) {
                echo ((running_script('install')) && (get_param_string('type', '') == 'ajax_db_details')) ? strip_html($error) : $error;
                return null;
            }
            critical_error('PASSON', $error); //warn_exit(do_lang_tempcode('CONNECT_DB_ERROR'));
        }

        if (!$connection) {
            fatal_exit(do_lang('CONNECT_DB_ERROR'));
        }
        $this->cache_db[$db_name][$db_host] = $connection;
        return $connection;
    }

    /**
     * This function is a very basic query executor. It shouldn't usually be used by you, as there are abstracted versions available.
     *
     * @param  string $query The complete SQL query
     * @param  mixed $connection The DB connection
     * @param  ?integer $max The maximum number of rows to affect (null: no limit)
     * @param  ?integer $start The start row to affect (null: no specification)
     * @param  boolean $fail_ok Whether to output an error on failure
     * @param  boolean $get_insert_id Whether to get the autoincrement ID created for an insert query
     * @return ?mixed The results (null: no results), or the insert ID
     */
    public function query($query, $connection, $max = null, $start = null, $fail_ok = false, $get_insert_id = false)
    {
        if ((strtoupper(substr(ltrim($query), 0, 7)) == 'SELECT ') || (strtoupper(substr(ltrim($query), 0, 8)) == '(SELECT ')) {
            if (($max !== null) && ($start !== null)) {
                $query .= ' LIMIT ' . strval(intval($max)) . ' OFFSET ' . strval(intval($start));
            } elseif ($max !== null) {
                $query .= ' LIMIT ' . strval(intval($max));
            } elseif ($start !== null) {
                $query .= ' OFFSET ' . strval(intval($start));
            }
        }

        $sub = substr(ltrim($query), 0, 4);
        $has_results = (($sub === '(SEL') || ($sub === 'SELE') || ($sub === 'sele') || ($sub === 'CHEC') || ($sub === 'EXPL') || ($sub === 'REPA') || ($sub === 'DESC') || ($sub === 'SHOW'));

        $results = @pg_query($connection, $query);
        if ((($results === false) || (($has_results) && ($results === true))) && (!$fail_ok)) {
            $err = pg_last_error($connection);
            if (function_exists('ocp_mark_as_escaped')) {
                ocp_mark_as_escaped($err);
            }
            if ((!running_script('upgrader')) && ((!get_mass_import_mode()) || (get_param_integer('keep_fatalistic', 0) == 1))) {
                if ((!function_exists('do_lang')) || (do_lang('QUERY_FAILED', null, null, null, null, false) === null)) {
                    $this->failed_query_exit(htmlentities('Query failed: ' . $query . ' : ' . $err));
                }

                $this->failed_query_exit(do_lang_tempcode('QUERY_FAILED', escape_html($query), ($err)));
            } else {
                $this->failed_query_echo(htmlentities('Database query failed: ' . $query . ' [') . ($err) . htmlentities(']'));
                return null;
            }
        }

        if (($results !== true) && ($has_results) && ($results !== false)) {
            return $this->get_query_rows($results);
        }

        if ($get_insert_id) {
            if (strtoupper(substr(ltrim($query), 0, 7)) == 'UPDATE ') {
                return null;
            }

            // Inefficient :(
            $pos = strpos($query, '(');
            $table_name = substr($query, 12, $pos - 13);

            $r3 = @pg_query($connection, 'SELECT last_value FROM ' . $table_name . '_id_seq');
            if ($r3) {
                $seq_array = pg_fetch_row($r3, 0);
                return intval($seq_array[0]);
            }
        }

        return null;
    }

    /**
     * Get the rows returned from a SELECT query.
     *
     * @param  resource $results The query result pointer
     * @param  ?integer $start Whether to start reading from (null: irrelevant for this forum driver)
     * @return array A list of row maps
     */
    public function get_query_rows($results, $start = null)
    {
        $num_fields = pg_num_fields($results);
        $types = array();
        $names = array();
        for ($x = 1; $x <= $num_fields; $x++) {
            $types[$x - 1] = pg_field_type($results, $x - 1);
            $names[$x - 1] = strtolower(pg_field_name($results, $x - 1));
        }

        $out = array();
        $i = 0;
        while (($row = pg_fetch_row($results)) !== false) {
            $j = 0;
            $newrow = array();
            foreach ($row as $v) {
                $name = $names[$j];
                $type = $types[$j];

                if (($type == 'INTEGER') || ($type == 'SMALLINT') || ($type == 'SERIAL') || ($type == 'UINTEGER')) {
                    if ($v !== null) {
                        $newrow[$name] = intval($v);
                    } else {
                        $newrow[$name] = null;
                    }
                } elseif (substr($type, 0, 5) == 'FLOAT') {
                        $newrow[$name] = floatval($v);
                } else {
                    $newrow[$name] = $v;
                }

                $j++;
            }

            $out[] = $newrow;

            $i++;
        }
        pg_free_result($results);
        return $out;
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
            'AUTO' => 'serial',
            'AUTO_LINK' => 'integer',
            'INTEGER' => 'integer',
            'UINTEGER' => 'bigint',
            'SHORT_INTEGER' => 'smallint',
            'REAL' => 'real',
            'BINARY' => 'smallint',
            'MEMBER' => 'integer',
            'GROUP' => 'integer',
            'TIME' => 'bigint',
            'LONG_TRANS' => 'bigint',
            'SHORT_TRANS' => 'bigint',
            'LONG_TRANS__COMCODE' => 'integer',
            'SHORT_TRANS__COMCODE' => 'integer',
            'SHORT_TEXT' => 'text',
            'LONG_TEXT' => 'text',
            'ID_TEXT' => 'varchar(80)',
            'MINIID_TEXT' => 'varchar(40)',
            'IP' => 'varchar(40)',
            'LANGUAGE_NAME' => 'varchar(5)',
            'URLPATH' => 'varchar(255)',
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
                if ($keys != '') {
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
            if (substr($name, -13) == '__text_parsed') {
                $_fields .= ' DEFAULT \'\'';
            } elseif (substr($name, -13) == '__source_user') {
                $_fields .= ' DEFAULT ' . strval(db_get_first_id());
            }
            $_fields .= ' ' . $perhaps_null . ',' . "\n";
        }

        $query = 'CREATE TABLE ' . $table_name . ' (' . "\n" . $_fields . '    PRIMARY KEY (' . $keys . ")\n)";
        return array($query);
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

            $postgres_fulltext_language = function_exists('get_value') ? get_value('postgres_fulltext_language') : null/*backup restore?*/;
            if ($postgres_fulltext_language === null) {
                $postgres_fulltext_language = 'english';
            }

            $aggregation = '';
            foreach (explode(',', $_fields) as $_field) {
                if ($aggregation != '') {
                    $aggregation .= ' || \' \' || ';
                }
                $aggregation .= '\'' . $this->db_escape_string($_field) . '\'';
            }

            return array('CREATE INDEX ' . $index_name . '__' . $table_name . ' ON ' . $table_name . ' USING gin(to_tsvector(\'pg_catalog.' . $postgres_fulltext_language . '\', ' . $aggregation . '))');
        }

        $_fields = preg_replace('#\(\d+\)#', '', $_fields);

        $fields = explode(',', $_fields);
        foreach ($fields as $field) {
            $sql = 'SELECT m_type FROM ' . $table_prefix . 'db_meta WHERE m_table=\'' . $this->escape_string($raw_table_name) . '\' AND m_name=\'' . $this->escape_string($field) . '\'';
            $values = $this->query($sql, $connection, null, null, true);
            if (!isset($values[0])) {
                continue; // No result found
            }
            $first = $values[0];
            $field_type = current($first); // Result found

            if (strpos($field_type, 'LONG') !== false) {
                // We can't support this in PostgreSQL, too much data will give an error when inserting into the index
                return array();
            }
        }

        return array('CREATE INDEX ' . $index_name . '__' . $table_name . ' ON ' . $table_name . '(' . $_fields . ')');
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
        $this->query('ALTER TABLE ' . $table_name . ' DROP PRIMARY KEY', $connection);
        $this->query('ALTER TABLE ' . $table_name . ' ADD PRIMARY KEY (' . implode(',', $new_key) . ')', $connection);
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
        $sql = 'SELECT n_live_tup FROM pg_stat_all_tables WHERE relname=\'' . $this->escape_string($table) . '\'';
        $values = $this->query($sql, $connection, null, null, true);
        if (!isset($values[0])) {
            return null; // No result found
        }
        $first = $values[0];
        $v = current($first); // Result found
        return $v;
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
        return 1;
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
        return true; // Actually it is always boolean for PostgreSQL
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

        $postgres_fulltext_language = get_value('postgres_fulltext_language');
        if ($postgres_fulltext_language === null) {
            $postgres_fulltext_language = 'english';
        }

        return 'to_tsvector(?) @@ plainto_tsquery(\'pg_catalog.' . $postgres_fulltext_language . '\', \'' . $this->db_escape_string($content) . '\')';
    }

    /**
     * Whether 'OFFSET' syntax is used on limit clauses.
     *
     * @return boolean Whether it is
     */
    public function uses_offset_syntax()
    {
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
        $this->query('SET statement_timeout TO ' . strval($seconds * 1000), $connection, null, null, true);
    }

    /**
     * Encode an SQL statement fragment for a conditional to see if two strings are equal.
     *
     * @param  ID_TEXT $attribute The attribute
     * @param  string $compare The comparison
     * @return string The SQL
     */
    public function string_equal_to($attribute, $compare)
    {
        return $attribute . "='" . $this->escape_string($compare) . "'";
    }

    /**
     * Escape a string so it may be inserted into a query. If SQL statements are being built up and passed using db_query then it is essential that this is used for security reasons. Otherwise, the abstraction layer deals with the situation.
     *
     * @param  string $string The string
     * @return string The escaped string
     */
    public function escape_string($string)
    {
        $string = fix_bad_unicode($string);

        return pg_escape_string($string);
    }

    /**
     * Close the database connections. We don't really need to close them (will close at exit), just disassociate so we can refresh them.
     */
    public function close_connections()
    {
        $this->cache_db = array();
    }
}
