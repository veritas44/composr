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

/*
Use the Enterprise Manager to get things set up.
You need to go into your server properties and turn the security to "SQL Server and Windows"
*/

/**
 * Database Driver.
 *
 * @package    core_database_drivers
 */
class Database_super_sqlserver
{
    /**
     * Adjust an SQL query to apply offset/limit restriction.
     *
     * @param  string $query The complete SQL query
     * @param  ?integer $max The maximum number of rows to affect (null: no limit)
     * @param  ?integer $start The start row to affect (null: no specification)
     */
    public function apply_sql_limit_clause(&$query, $max = null, $start = 0)
    {
        if ($max !== null) {
            if ($start !== null) {
                $max += $start;
            }

            // Unfortunately we can't apply to DELETE FROM and update :(. But its not too important, LIMIT'ing them was unnecessarily anyway
            if (strtoupper(substr(ltrim($query), 0, 7)) == 'SELECT ') {
                $query = 'SELECT TOP ' . strval(intval($max)) . substr(ltrim($query), 6);
            }
            if (strtoupper(substr(ltrim($query), 0, 8)) == '(SELECT ') {
                $query = '(SELECT TOP ' . strval(intval($max)) . substr(ltrim($query), 7);
            }
        }
    }

    /**
     * Adjust an SQL query to use T-SQL's unique Unicode syntax.
     *
     * @param  string $query The complete SQL query
     */
    protected function rewrite_to_unicode_syntax(&$query)
    {
        if (get_charset() != 'utf-8') {
            return;
        }

        if (strpos($query, "'") === false) {
            return;
        }

        $new_query = '';
        $len = strlen($query);
        $in_string = false;
        for ($i = 0; $i < $len; $i++) {
            $char = $query[$i];

            if ($in_string) {
                if ($char == "'") {
                    if (($i < $len - 1) && ($query[$i + 1] == "'")) {
                        // Escaped, so put it out and jump ahead a bit
                        $new_query .= "''";
                        $i++;
                        continue;
                    } else {
                        // End of string section
                        $in_string = false;
                    }
                }
            } else {
                if ($char == "'") {
                    // Start of string section
                    $in_string = true;
                    if (($i == 0) || ($new_query[$i - 1] != 'N')) {
                        $new_query .= 'N';
                    }
                }
            }

            $new_query .= $char;
        }

        $query = $new_query;
    }

    /**
     * Get the default user for making db connections (used by the installer as a default).
     *
     * @return string The default user for db connections
     */
    public function db_default_user()
    {
        return 'sa';
    }

    /**
     * Get the default password for making db connections (used by the installer as a default).
     *
     * @return string The default password for db connections
     */
    public function db_default_password()
    {
        return '';
    }

    /**
     * Get SQL for creating a table index.
     *
     * @param  ID_TEXT $table_name The name of the table to create the index on
     * @param  ID_TEXT $index_name The index name (not really important at all)
     * @param  string $_fields Part of the SQL query: a comma-separated list of fields to use on the index
     * @param  array $db The DB connection to make on
     * @param  ID_TEXT $raw_table_name The table name with no table prefix
     * @param  string $unique_key_fields The name of the unique key field for the table
     * @return array List of SQL queries to run
     */
    public function db_create_index($table_name, $index_name, $_fields, $db, $raw_table_name, $unique_key_fields)
    {
        if ($index_name[0] == '#') {
            $ret = array();
            if (db_has_full_text($db)) {
                $index_name = substr($index_name, 1);

                // Only allowed one index per table, so we need to merge in any existing indices
                $existing_index_fields = $GLOBALS['SITE_DB']->query_select('db_meta_indices', array('i_name', 'i_fields'), array('i_table' => $raw_table_name));
                foreach ($existing_index_fields as $existing_index_field) {
                    if (substr($existing_index_field['i_name'], 0, 1) == '#') {
                        $_fields .= ',' . $existing_index_field['i_fields'];
                    }
                }
                $_fields = implode(',', array_unique(explode(',', $_fields)));

                // Full-text catalogue needed
                $ret[] = 'CREATE FULLTEXT CATALOG ft AS DEFAULT'; // Will fail if already exists, but that's ok

                // Create unique index on primary key (needed for full-text to function)
                $unique_index_name = 'unique__' . $table_name;
                $ret[] = 'DROP INDEX ' . $unique_index_name . ' ON ' . $table_name; // Just in case already there. Will fail if does not already exist, but that's ok
                $ret[] = 'CREATE UNIQUE INDEX ' . $unique_index_name . ' ON ' . $table_name . '(' . $unique_key_fields . ')';

                // Create full-text index on all fields that need it
                $ret[] = 'DROP FULLTEXT INDEX ON ' . $table_name; // Just in case already there. Will fail if does not already exist, but that's ok
                $ret[] = 'CREATE FULLTEXT INDEX ON ' . $table_name . '(' . $_fields . ') KEY INDEX ' . $unique_index_name;
            }
            return $ret;
        }

        $_fields = preg_replace('#\(\d+\)#', '', $_fields);

        $fields = explode(',', $_fields);
        foreach ($fields as $field) {
            $db_type = $GLOBALS['SITE_DB']->query_select_value_if_there('db_meta', 'm_type', array('m_table' => $raw_table_name, 'm_name' => $field));
            if ((strpos($db_type, 'LONG') !== false) || ((!multi_lang_content()) && (strpos($db_type, 'SHORT_TRANS') !== false))) {
                // We can't support this in SQL Server https://blogs.msdn.microsoft.com/bartd/2011/01/06/living-with-sqls-900-byte-index-key-length-limit/.
                // We assume shorter numbers than 250 are only being used on short columns anyway, which will index perfectly fine without any constraint.
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
     * @param  array $db The DB connection to make on
     */
    public function db_change_primary_key($table_name, $new_key, $db)
    {
        $this->db_query('ALTER TABLE ' . $table_name . ' DROP PRIMARY KEY', $db);
        $this->db_query('ALTER TABLE ' . $table_name . ' ADD PRIMARY KEY (' . implode(',', $new_key) . ')', $db);
    }

    /**
     * Assemble part of a WHERE clause for doing full-text search
     *
     * @param  string $content Our match string (assumes "?" has been stripped already)
     * @param  boolean $boolean Whether to do a boolean full text search
     * @return string Part of a WHERE clause for doing full-text search
     */
    public function db_full_text_assemble($content, $boolean)
    {
        $content = str_replace('"', '', $content);
        return 'CONTAINS ((?),\'' . $this->db_escape_string($content) . '\')';
    }

    /**
     * Get the ID of the first row in an auto-increment table (used whenever we need to reference the first).
     *
     * @return integer First ID used
     */
    public function db_get_first_id()
    {
        return 1;
    }

    /**
     * Get a map of Composr field types, to actual database types.
     *
     * @return array The map
     */
    public function db_get_type_remap()
    {
        $type_remap = array(
            'AUTO' => 'integer identity',
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
            'LONG_TRANS__COMCODE' => 'bigint',
            'SHORT_TRANS__COMCODE' => 'bigint',
            'SHORT_TEXT' => 'nvarchar(255)',
            'LONG_TEXT' => 'nvarchar(MAX)', // 'TEXT' cannot be indexed.
            'ID_TEXT' => 'nvarchar(80)',
            'MINIID_TEXT' => 'nvarchar(40)',
            'IP' => 'nvarchar(40)',
            'LANGUAGE_NAME' => 'nvarchar(5)',
            'URLPATH' => 'nvarchar(255)',
        );
        return $type_remap;
    }

    /**
     * Get SQL for creating a new table.
     *
     * @param  ID_TEXT $table_name The table name
     * @param  array $fields A map of field names to Composr field types (with *#? encodings)
     * @param  array $db The DB connection to make on
     * @param  ID_TEXT $raw_table_name The table name with no table prefix
     * @param  boolean $save_bytes Whether to use lower-byte table storage, with tradeoffs of not being able to support all unicode characters; use this if key length is an issue
     * @return array List of SQL queries to run
     */
    public function db_create_table($table_name, $fields, $db, $raw_table_name, $save_bytes = false)
    {
        $type_remap = $this->db_get_type_remap();

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
     * Encode an SQL statement fragment for a conditional to see if two strings are equal.
     *
     * @param  ID_TEXT $attribute The attribute
     * @param  string $compare The comparison
     * @return string The SQL
     */
    public function db_string_equal_to($attribute, $compare)
    {
        return $attribute . " LIKE '" . $this->db_escape_string($compare) . "'";
    }

    /**
     * Encode an SQL statement fragment for a conditional to see if two strings are not equal.
     *
     * @param  ID_TEXT $attribute The attribute
     * @param  string $compare The comparison
     * @return string The SQL
     */
    public function db_string_not_equal_to($attribute, $compare)
    {
        return $attribute . "<>'" . $this->db_escape_string($compare) . "'";
    }

    /**
     * This function is internal to the database system, allowing SQL statements to be build up appropriately. Some databases require IS NULL to be used to check for blank strings.
     *
     * @return boolean Whether a blank string IS NULL
     */
    public function db_empty_is_null()
    {
        return false;
    }

    /**
     * Find whether table truncation support is present
     *
     * @return boolean Whether it is
     */
    public function db_supports_truncate_table()
    {
        return false;
    }

    /**
     * Delete a table.
     *
     * @param  ID_TEXT $table The table name
     * @param  array $db The DB connection to delete on
     * @return array List of SQL queries to run
     */
    public function db_drop_table_if_exists($table, $db)
    {
        return array('DROP TABLE ' . $table);
    }

    /**
     * Determine whether the database is a flat file database, and thus not have a meaningful connect username and password.
     *
     * @return boolean Whether the database is a flat file database
     */
    public function db_is_flat_file_simple()
    {
        return false;
    }

    /**
     * Encode a LIKE string comparision fragement for the database system. The pattern is a mixture of characters and ? and % wildcard symbols.
     *
     * @param  string $pattern The pattern
     * @return string The encoded pattern
     */
    public function db_encode_like($pattern)
    {
        return $this->db_escape_string($pattern);
    }

    /**
     * Get the number of rows in a table, with approximation support for performance (if necessary on the particular database backend).
     *
     * @param string $table The table name
     * @param array $where WHERE clauses if it will help get a more reliable number when we're not approximating in map form
     * @param string $where_clause WHERE clauses if it will help get a more reliable number when we're not approximating in SQL form
     * @param object $db The DB connection to check against
     * @return ?integer The count (null: do it normally)
     */
    public function get_table_count_approx($table, $where, $where_clause, $db)
    {
        $sql = 'SELECT SUM(p.rows) FROM sys.partitions AS p
            INNER JOIN sys.tables AS t
            ON p.[object_id] = t.[object_id]
            INNER JOIN sys.schemas AS s
            ON s.[schema_id] = t.[schema_id]
            WHERE t.name = N\'' . $db->get_table_prefix() . $table . '\'
            AND s.name = N\'dbo\'
            AND p.index_id IN (0,1)';
        return $db->query_value_if_there($sql, false, true);
    }

    /**
     * Find whether full-text-search is present
     *
     * @param  array $db A DB connection
     * @return boolean Whether it is
     */
    public function db_has_full_text($db)
    {
        return (get_value('skip_fulltext_sqlserver') !== '1');
    }

    /**
     * Find whether full-text-boolean-search is present
     *
     * @return boolean Whether it is
     */
    public function db_has_full_text_boolean()
    {
        return false;
    }

    /**
     * Escape a string so it may be inserted into a query. If SQL statements are being built up and passed using db_query then it is essential that this is used for security reasons. Otherwise, the abstraction layer deals with the situation.
     *
     * @param  string $string The string
     * @return string The escaped string
     */
    public function db_escape_string($string)
    {
        $string = fix_bad_unicode($string);

        $new_str = '';
        $len = strlen($string);
        for ($i = 0; $i < $len; $i++) {
            $char = $string[$i];
            if ($char == "'") {
                $char = "''";
            }
            $new_str .= $char;
        }

        return $new_str;
    }
}
