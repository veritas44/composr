<?php /*

 Composr
 Copyright (c) ocProducts, 2004-2015

 See text/EN/licence.txt for full licencing information.


 NOTE TO PROGRAMMERS:
   Do not edit this file. If you need to make changes, save your changed file to the appropriate *_custom folder
   **** If you ignore this advice, then your website upgrades (e.g. for bug fixes) will likely kill your changes ****

*/

/**
 * @license    http://opensource.org/licenses/cpal_1.0 Common Public Attribution License
 * @copyright  ocProducts Ltd
 * @package    core
 */

/**
 * Returns a list of keywords for all databases we might some day support.
 *
 * @return array List of pairs
 */
function get_db_keywords()
{
    $words = array(
        'ABSOLUTE', 'ACCESS', 'ACCESSIBLE', 'ACTION', 'ACTIVE', 'ADA', 'ADD', 'ADMIN',
        'AFTER', 'ALIAS', 'ALL', 'ALLOCATE', 'ALLOW', 'ALPHANUMERIC', 'ALTER', 'ANALYSE',
        'ANALYZE', 'AND', 'ANY', 'APPLICATION', 'ARE', 'ARITH_OVERFLOW', 'ARRAY', 'AS',
        'ASC', 'ASCENDING', 'ASENSITIVE', 'ASSERTION', 'ASSISTANT', 'ASSOCIATE', 'ASUTIME', 'ASYMMETRIC',
        'ASYNC', 'AT', 'ATOMIC', 'AUDIT', 'AUTHORIZATION', 'AUTO', 'AUTODDL', 'AUTOINCREMENT',
        'AUX', 'AUXILIARY', 'AVG', 'BACKUP', 'BASED', 'BASENAME', 'BASE_NAME', 'BEFORE',
        'BEGIN', 'BETWEEN', 'BIGINT', 'BINARY', 'BIT', 'BIT_LENGTH', 'BLOB', 'BLOBEDIT',
        'BOOLEAN', 'BOTH', 'BOTTOM', 'BREADTH', 'BREAK', 'BROWSE', 'BUFFER', 'BUFFERPOOL',
        'BULK', 'BY', 'BYTE', 'CACHE', 'CALL', 'CALLED', 'CAPABILITY', 'CAPTURE',
        'CASCADE', 'CASCADED', 'CASE', 'CAST', 'CATALOG', 'CCSID', 'CHANGE', 'CHAR',
        'CHARACTER', 'CHARACTER_LENGTH', 'CHAR_CONVERT', 'CHAR_LENGTH', 'CHECK', 'CHECKPOINT', 'CHECK_POINT_LEN', 'CHECK_POINT_LENGTH',
        'CLOB', 'CLOSE', 'CLUSTER', 'CLUSTERED', 'COALESCE', 'COLLATE', 'COLLATION', 'COLLECTION',
        'COLLID', 'COLUMN', 'COLUMNS', 'COMMENT', 'COMMIT', 'COMMITTED', 'COMPACTDATABASE', 'COMPILETIME',
        'COMPLETION', 'COMPRESS', 'COMPUTE', 'COMPUTED', 'CONCAT', 'CONDITION', 'CONDITIONAL', 'CONFIRM',
        'CONFLICT', 'CONNECT', 'CONNECTION', 'CONSTRAINT', 'CONSTRAINTS', 'CONSTRUCTOR', 'CONTAINER', 'CONTAINING',
        'CONTAINS', 'CONTAINSTABLE', 'CONTINUE', 'CONTROLROW', 'CONVERT', 'CORRESPONDING', 'COUNT', 'COUNTER',
        'CREATE', 'CREATEDATABASE', 'CREATEFIELD', 'CREATEGROUP', 'CREATEINDEX', 'CREATEOBJECT', 'CREATEPROPERTY', 'CREATERELATION',
        'CREATETABLEDEF', 'CREATEUSER', 'CREATEWORKSPACE', 'CROSS', 'CSTRING', 'CUBE', 'CURRENCY', 'CURRENT',
        'CURRENTUSER', 'CURRENT_DATE', 'CURRENT_DEFAULT_TRANSFORM_GROUP', 'CURRENT_LC_CTYPE', 'CURRENT_PATH', 'CURRENT_ROLE', 'CURRENT_TIME', 'CURRENT_TIMESTAMP',
        'CURRENT_TRANSFORM_GROUP_FOR_TYPE', 'CURRENT_USER', 'CURSOR', 'CYCLE', 'DATA', 'DATABASE', 'DATABASES', 'DATA_PGS',
        'DATE', 'DATETIME', 'DAY',/*'DAYS',*/
        'DAY_HOUR', 'DAY_MICROSECOND', 'DAY_MINUTE', 'DAY_SECOND',
        'DB2SQL', 'DBCC', 'DBINFO', 'DBSPACE', 'DB_KEY', 'DEALLOCATE', 'DEBUG', 'DEC',
        'DECIMAL', 'DECLARE', 'DEFAULT', 'DEFERRABLE', 'DEFERRED', 'DELAYED', 'DELETE', 'DELETING',
        'DENY', 'DEPTH', 'DEREF', 'DESC', 'DESCENDING', 'DESCRIBE',/*'DESCRIPTION',*/
        'DESCRIPTOR',
        'DETERMINISTIC', 'DIAGNOSTICS', 'DICTIONARY', 'DISALLOW', 'DISCONNECT', 'DISK', 'DISPLAY', 'DISTINCT',
        'DISTINCTROW', 'DISTRIBUTED', 'DIV', 'DO', 'DOCUMENT', 'DOMAIN', 'DOUBLE', 'DROP',
        'DSNHATTR', 'DSSIZE', 'DUAL', 'DUMMY', 'DUMP', 'DYNAMIC', 'EACH', 'ECHO',
        'EDIT', 'EDITPROC', 'ELEMENT', 'ELSE', 'ELSEIF', 'ENCLOSED', 'ENCODING', 'ENCRYPTED',
        'ENCRYPTION', 'END', 'END-EXEC', 'ENDIF', 'ENDING', 'ENDTRAN', 'ENTRY_POINT', 'EQUALS',
        'EQV', 'ERASE', 'ERRLVL', 'ERROR', 'ERROREXIT', 'ESCAPE', 'ESCAPED', 'EVENT',
        'EXCEPT', 'EXCEPTION', 'EXCLUSIVE', 'EXEC', 'EXECUTE', 'EXISTING', 'EXISTS', 'EXIT',
        'EXPLAIN', 'EXTERN', 'EXTERNAL', 'EXTERNLOGIN', 'EXTRACT', 'FALSE', 'FENCED', 'FETCH',
        'FIELD', 'FIELDPROC', 'FIELDS', 'FILE', 'FILLCACHE', 'FILLFACTOR', 'FILTER', 'FINAL',
        'FIRST', 'FLOAT', 'FLOAT4', 'FLOAT8', 'FLOPPY', 'FOR', 'FORCE', 'FOREIGN',
        'FORM', 'FORMS', 'FORTRAN', 'FORWARD', 'FOUND', 'FREE', 'FREETEXT', 'FREETEXTTABLE',
        'FREEZE', 'FREE_IT', 'FROM', 'FULL', 'FULLTEXT', 'FUNCTION', 'GDSCODE', 'GENERAL',
        'GENERATED', 'GENERATOR', 'GEN_ID',/*'GET',*/
        'GETOBJECT', 'GETOPTION', 'GLOB', 'GLOBAL',
        'GO', 'GOTO', 'GOTOPAGE', 'GRANT', 'GROUP', 'GROUPING', 'GROUP_COMMIT_WAIT', 'GROUP_COMMIT_WAIT_TIME',
        'GUID', 'HANDLER', 'HAVING', 'HELP', 'HIGH_PRIORITY', 'HOLD', 'HOLDLOCK', 'HOUR',
        'HOURS', 'HOUR_MICROSECOND', 'HOUR_MINUTE', 'HOUR_SECOND', 'IDENTIFIED', 'IDENTITY', 'IDENTITYCOL', 'IDENTITY_INSERT',
        'IDLE', 'IEEEDOUBLE', 'IEEESINGLE', 'IF', 'IGNORE', 'ILIKE', 'IMMEDIATE', 'IMP',
        'IN', 'INACTIVE', 'INCLUDE', 'INCLUSIVE', 'INCREMENT', 'INDEX', 'INDEXES', 'INDEX_LPAREN',
        'INDICATOR', 'INFILE', 'INHERIT', 'INIT', 'INITIAL', 'INITIALLY', 'INNER', 'INOUT',
        'INPUT', 'INPUT_TYPE', 'INSENSITIVE', 'INSERT', 'INSERTING', 'INSERTTEXT', 'INSTALL', 'INSTEAD',
        'INT', 'INT1', 'INT2', 'INT3', 'INT4', 'INT8', 'INTEGER', 'INTEGER1',
        'INTEGER2', 'INTEGER4', 'INTEGRATED', 'INTERSECT', 'INTERVAL', 'INTO', 'IQ', 'IS',
        'ISNULL', 'ISOBID', 'ISOLATION', 'ISQL', 'ITERATE', 'JAR', 'JAVA', 'JOIN',
        'KEY', 'KEYS', 'KILL', 'LABEL',/*'LANGUAGE',*/
        'LARGE', 'LAST', 'LASTMODIFIED',
        'LATERAL', 'LC_CTYPE', 'LC_MESSAGES', 'LC_TYPE', 'LEADING', 'LEAVE', 'LEFT', 'LENGTH',
        'LESS', 'LEV', 'LEVEL', 'LIKE', 'LIMIT', 'LINEAR', 'LINENO', 'LINES',
        'LOAD', 'LOCAL', 'LOCALE', 'LOCALTIME', 'LOCALTIMESTAMP', 'LOCATOR', 'LOCATORS', 'LOCK',
        'LOCKMAX', 'LOCKSIZE', 'LOGFILE', 'LOGICAL', 'LOGICAL1', 'LOGIN', 'LOG_BUFFER_SIZE', 'LOG_BUF_SIZE',
        'LONG', 'LONGBINARY', 'LONGBLOB', 'LONGTEXT', 'LOOP', 'LOWER', 'LOW_PRIORITY', 'MACRO',
        'MAINTAINED', 'MANUAL', 'MAP', 'MATCH', 'MATERIALIZED', 'MAX', 'MAXEXTENTS', 'MAXIMUM',
        'MAXIMUM_SEGMENT', 'MAX_SEGMENT', 'MEDIUMBLOB', 'MEDIUMINT', 'MEDIUMTEXT', 'MEMBER', 'MEMBERSHIP', 'MEMO',
        'MERGE', 'MESSAGE', 'METHOD', 'MICROSECOND', 'MICROSECONDS', 'MIDDLEINT', 'MIN', 'MINIMUM',
        'MINUS', 'MINUTE', 'MINUTES', 'MINUTE_MICROSECOND', 'MINUTE_SECOND', 'MIRROR', 'MIRROREXIT', 'MLSLABEL',
        'MOD', 'MODE', 'MODIFIES', 'MODIFY', 'MODULE', 'MODULE_NAME', 'MONEY', 'MONTH',
        'MONTHS', 'MOVE', 'MULTISET',/*'NAME',*/
        'NAMES', 'NATIONAL', 'NATURAL', 'NCHAR',
        'NCLOB', 'NEW', 'NEWPASSWORD', 'NEXT', 'NEXTVAL', 'NO', 'NOAUDIT', 'NOAUTO',
        'NOCHECK', 'NOCOMPRESS', 'NOHOLDLOCK', 'NONCLUSTERED', 'NONE', 'NOT', 'NOTIFY', 'NOTNULL',
        'NOWAIT', 'NO_WRITE_TO_BINLOG', 'NULL', 'NULLIF', 'NULLS', 'NUMBER', 'NUMERIC', 'NUMERIC_TRUNCATION',
        'NUMPARTS', 'NUM_LOG_BUFFERS', 'NUM_LOG_BUFS', 'OBID', 'OBJECT', 'OCTET_LENGTH', 'OF', 'OFF',
        'OFFLINE', 'OFFSET', 'OFFSETS', 'OID', 'OLD', 'OLEOBJECT', 'ON', 'ONCE',
        'ONLINE', 'ONLY', 'OPEN', 'OPENDATASOURCE', 'OPENQUERY', 'OPENRECORDSET', 'OPENROWSET', 'OPENXML',
        'OPERATION', 'OPERATORS', 'OPTIMIZATION', 'OPTIMIZE', 'OPTION', 'OPTIONALLY', 'OPTIONS', 'OR',
        'ORDER', 'ORDINALITY', 'OTHERS', 'OUT', 'OUTER', 'OUTFILE', 'OUTPUT', 'OUTPUT_TYPE',
        'OVER', 'OVERFLOW', 'OVERLAPS', 'OWNERACCESS', 'PACKAGE', 'PAD', 'PADDED', 'PAGE',
        'PAGELENGTH',/*'PAGES',*/
        'PAGE_SIZE', 'PARAMETER', 'PARAMETERS', 'PART', 'PARTIAL', 'PARTITION',
        'PARTITIONED', 'PARTITIONING', 'PASCAL', 'PASSTHROUGH', 'PASSWORD',/*'PATH',*/
        'PCTFREE', 'PENDANT',
        'PERCENT', 'PERM', 'PERMANENT', 'PIECESIZE', 'PIPE', 'PIVOT', 'PLACING', 'PLAN',
        'POSITION', 'POST_EVENT', 'PRECISION', 'PREORDER', 'PREPARE', 'PRESERVE', 'PREVVAL', 'PRIMARY',
        'PRINT', 'PRIOR', 'PRIQTY', 'PRIVATE', 'PRIVILEGES', 'PROC', 'PROCEDURE', 'PROCESSEXIT',
        'PROGRAM', 'PROPERTY', 'PROTECTED', 'PSID', 'PUBLIC', 'PUBLICATION', 'PURGE', 'QUERIES',
        'QUERY', 'QUERYNO', 'QUIT', 'RAID0', 'RAISERROR', 'RANGE', 'RAW', 'RAW_PARTITIONS',
        'READ', 'READS', 'READTEXT', 'READ_ONLY', 'READ_WRITE', 'REAL', 'RECALC', 'RECONFIGURE',
        'RECORDSET', 'RECORD_VERSION', 'RECURSIVE', 'REF', 'REFERENCE', 'REFERENCES', 'REFERENCING', 'REFRESH',
        'REFRESHLINK', 'REGEXP', 'REGISTERDATABASE', 'RELATION', 'RELATIVE', 'RELEASE', 'REMOTE', 'REMOVE',
        'RENAME', 'REORGANIZE', 'REPAINT', 'REPAIRDATABASE', 'REPEAT', 'REPEATABLE', 'REPLACE', 'REPLICATION',
        'REPORT', 'REPORTS', 'REQUERY', 'REQUIRE', 'RESERV', 'RESERVED_PGS', 'RESERVING', 'RESIGNAL',
        'RESOURCE', 'RESTORE', 'RESTRICT', 'RESULT', 'RESULT_SET_LOCATOR', 'RETAIN', 'RETURN', 'RETURNING_VALUES',
        'RETURNS', 'REVOKE', 'RIGHT', 'RLIKE', 'ROLE', 'ROLLBACK', 'ROLLUP', 'ROUTINE',
        'ROW', 'ROWCNT', 'ROWCOUNT', 'ROWGUIDCOL', 'ROWID', 'ROWLABEL', 'ROWNUM', 'ROWS',
        'ROWSET', 'RULE', 'RUN', 'RUNTIME', 'SAVE', 'SAVEPOINT', 'SCHEMA', 'SCHEMAS',
        'SCOPE', 'SCRATCHPAD', 'SCREEN', 'SCROLL', 'SEARCH', 'SECOND', 'SECONDS', 'SECOND_MICROSECOND',
        'SECQTY',/*'SECTION',*/
        'SECURITY', 'SELECT', 'SENSITIVE', 'SEPARATOR', 'SEQUENCE', 'SERIALIZABLE',
        'SESSION', 'SESSION_USER', 'SET', 'SETFOCUS', 'SETOPTION', 'SETS', 'SETUSER', 'SHADOW',
        'SHARE', 'SHARED', 'SHELL', 'SHORT', 'SHOW', 'SHUTDOWN', 'SIGNAL', 'SIMILAR',
        'SIMPLE', 'SINGLE', 'SINGULAR', 'SIZE', 'SMALLINT', 'SNAPSHOT', 'SOME', 'SONAME',
        'SORT', 'SOURCE', 'SPACE', 'SPATIAL', 'SPECIFIC', 'SPECIFICTYPE', 'SQL', 'SQLCA',
        'SQLCODE', 'SQLERROR', 'SQLEXCEPTION', 'SQLSTATE', 'SQLWARNING', 'SQL_BIG_RESULT', 'SQL_CALC_FOUND_ROWS', 'SQL_SMALL_RESULT',
        'SSL', 'STABILITY', 'STANDARD', 'START', 'STARTING', 'STARTS', 'STATE', 'STATEMENT',
        'STATIC', 'STATISTICS', 'STAY', 'STDEV', 'STDEVP', 'STOGROUP', 'STOP', 'STORES',
        'STRAIGHT_JOIN', 'STRING', 'STRIPE', 'STRUCTURE', 'STYLE', 'SUBMULTISET', 'SUBPAGES', 'SUBSTRING',
        'SUBTRANS', 'SUBTRANSACTION', 'SUB_TYPE', 'SUCCESSFUL', 'SUM', 'SUMMARY', 'SUSPEND', 'SYB_IDENTITY',
        'SYB_RESTREE', 'SYMMETRIC', 'SYNCHRONIZE', 'SYNONYM', 'SYNTAX_ERROR', 'SYSDATE', 'SYSFUN', 'SYSIBM',
        'SYSPROC', 'SYSTEM', 'SYSTEM_USER', 'TABLE', 'TABLEDEF', 'TABLEDEFS', 'TABLEID', 'TABLES',
        'TABLESAMPLE', 'TABLESPACE', 'TAPE', 'TEMP', 'TEMPORARY', 'TERMINATED', 'TERMINATOR', 'TEST',
        'TEXT', 'TEXTSIZE', 'THEN', 'THERE', 'TIME', 'TIMESTAMP', 'TIMEZONE_HOUR', 'TIMEZONE_MINUTE',
        'TINYBLOB', 'TINYINT', 'TINYTEXT', 'TO', 'TOP', 'TRAILING', 'TRAN', 'TRANSACTION',
        'TRANSFORM', 'TRANSLATE', 'TRANSLATION', 'TREAT', 'TRIGGER', 'TRIM', 'TRUE', 'TRUNCATE',
        'TSEQUAL', 'TYPE', 'UID', 'UNBOUNDED', 'UNCOMMITTED', 'UNDER', 'UNDO', 'UNION',
        'UNIQUE', 'UNIQUEIDENTIFIER', 'UNKNOWN', 'UNLOCK', 'UNNEST', 'UNSIGNED', 'UNTIL', 'UPDATE',
        'UPDATETEXT', 'UPDATING', 'UPGRADE', 'UPPER', 'USAGE', 'USE', 'USED_PGS', 'USER',
        'USER_OPTION', 'USING', 'UTC_DATE', 'UTC_TIME', 'UTC_TIMESTAMP', 'VALIDATE', 'VALIDPROC', 'VALUE',
        'VALUES', 'VAR', 'VARBINARY', 'VARCHAR', 'VARCHAR2', 'VARCHARACTER', 'VARIABLE', 'VARIANT',
        'VARP', 'VARYING', 'VCAT', 'VERBOSE', 'VERSION', 'VIEW', 'VIRTUAL', 'VISIBLE',
        'VOLATILE', 'VOLUMES', 'WAIT', 'WAITFOR', 'WEEKDAY', 'WHEN', 'WHENEVER', 'WHERE',
        'WHILE', 'WINDOW', 'WITH', 'WITHIN', 'WITHOUT', 'WITH_CUBE', 'WITH_LPAREN', 'WITH_ROLLUP',
        'WLM', 'WORK', 'WORKSPACE', 'WRITE', 'WRITETEXT', 'X509', 'XMLELEMENT', 'XOR',
        'YEAR', 'YEARDAY', 'YEARS', 'YEAR_MONTH', 'YES', 'YESNO', 'ZEROFILL', 'ZONE', 'GET',
    );
    return $words;
}

/**
 * Returns a list of pairs, for which permissions are false by default for ordinary usergroups.
 *
 * @return array List of pairs
 */
function get_false_permissions()
{
    return array(
        array('_COMCODE', 'allow_html'),
        array('_COMCODE', 'comcode_dangerous'),
        array('_COMCODE', 'comcode_nuisance'),
        array('_COMCODE', 'use_very_dangerous_comcode'),
        array('STAFF_ACTIONS', 'access_closed_site'),
        array('STAFF_ACTIONS', 'bypass_bandwidth_restriction'),
        array('STAFF_ACTIONS', 'see_php_errors'),
        array('STAFF_ACTIONS', 'see_stack_dump'),
        array('STAFF_ACTIONS', 'view_profiling_modes'),
        array('STAFF_ACTIONS', 'access_overrun_site'),
        array('STAFF_ACTIONS', 'view_content_history'),
        array('STAFF_ACTIONS', 'restore_content_history'),
        array('STAFF_ACTIONS', 'delete_content_history'),
        array('SUBMISSION', 'bypass_validation_highrange_content'),
        array('SUBMISSION', 'bypass_validation_midrange_content'),
        array('SUBMISSION', 'edit_highrange_content'),
        array('SUBMISSION', 'edit_midrange_content'),
        array('SUBMISSION', 'edit_lowrange_content'),
        array('SUBMISSION', 'edit_own_highrange_content'),
        array('SUBMISSION', 'edit_own_midrange_content'),
        array('SUBMISSION', 'delete_highrange_content'),
        array('SUBMISSION', 'delete_midrange_content'),
        array('SUBMISSION', 'delete_lowrange_content'),
        array('SUBMISSION', 'delete_own_highrange_content'),
        array('SUBMISSION', 'delete_own_midrange_content'),
        array('SUBMISSION', 'delete_own_lowrange_content'),
        array('SUBMISSION', 'can_submit_to_others_categories'),
        array('SUBMISSION', 'search_engine_links'),
        array('SUBMISSION', 'submit_cat_highrange_content'),
        array('SUBMISSION', 'submit_cat_midrange_content'),
        array('SUBMISSION', 'submit_cat_lowrange_content'),
        array('SUBMISSION', 'edit_cat_highrange_content'),
        array('SUBMISSION', 'edit_cat_midrange_content'),
        array('SUBMISSION', 'edit_cat_lowrange_content'),
        array('SUBMISSION', 'delete_cat_highrange_content'),
        array('SUBMISSION', 'delete_cat_midrange_content'),
        array('SUBMISSION', 'delete_cat_lowrange_content'),
        array('SUBMISSION', 'edit_own_cat_highrange_content'),
        array('SUBMISSION', 'edit_own_cat_midrange_content'),
        array('SUBMISSION', 'edit_own_cat_lowrange_content'),
        array('SUBMISSION', 'delete_own_cat_highrange_content'),
        array('SUBMISSION', 'delete_own_cat_midrange_content'),
        array('SUBMISSION', 'delete_own_cat_lowrange_content'),
        array('SUBMISSION', 'mass_import'),
        array('SUBMISSION', 'scheduled_publication_times'),
        array('SUBMISSION', 'mass_delete_from_ip'),
        array('SUBMISSION', 'exceed_filesize_limit'),
        array('SUBMISSION', 'draw_to_server'),
        array('GENERAL_SETTINGS', 'open_virtual_roots'),
        array('GENERAL_SETTINGS', 'view_revision_history'),
        array('GENERAL_SETTINGS', 'sees_javascript_error_alerts'),
        array('GENERAL_SETTINGS', 'see_software_docs'),
        array('GENERAL_SETTINGS', 'see_unvalidated'),
        array('GENERAL_SETTINGS', 'may_enable_staff_notifications'),
        array('GENERAL_SETTINGS', 'bypass_flood_control'),
        array('GENERAL_SETTINGS', 'remove_page_split'),
        array('GENERAL_SETTINGS', 'bypass_word_filter'),
        array('SUBMISSION', 'perform_keyword_check'),
        array('SUBMISSION', 'have_personal_category'),
    );
}

/**
 * Returns a list of pairs, for which permissions are true by default for ordinary usergroups.
 *
 * @return array List of pairs
 */
function get_true_permissions()
{
    return array(
        array('SUBMISSION', 'edit_own_lowrange_content'),
        array('SUBMISSION', 'submit_highrange_content'),
        array('SUBMISSION', 'submit_midrange_content'),
        array('SUBMISSION', 'submit_lowrange_content'),
        array('SUBMISSION', 'bypass_validation_lowrange_content'),
        array('SUBMISSION', 'set_own_author_profile'),
        array('_FEEDBACK', 'rate'),
        array('_FEEDBACK', 'comment'),
        array('VOTE', 'vote_in_polls'),
        array('GENERAL_SETTINGS', 'jump_to_unvalidated'),
        array('_COMCODE', 'reuse_others_attachments'),
    );
}

/**
 * Check if a privilege exists.
 *
 * @param  ID_TEXT $name The name of the option
 * @return boolean Whether it exists
 */
function permission_exists($name)
{
    $test = $GLOBALS['SITE_DB']->query_select_value_if_there('privilege_list', 'the_name', array('the_name' => $name));
    return !is_null($test);
}

/**
 * Add a privilege, and apply it to every usergroup.
 *
 * @param  ID_TEXT $section The section the privilege is filled under
 * @param  ID_TEXT $name The codename for the privilege
 * @param  boolean $default Whether this permission is granted to all usergroups by default
 * @param  boolean $not_even_mods Whether this permission is not granted to supermoderators by default (something very sensitive)
 */
function add_privilege($section, $name, $default = false, $not_even_mods = false)
{
    if (!$not_even_mods) { // NB: Don't actually need to explicitly give admins privileges
        $usergroups = $GLOBALS['FORUM_DRIVER']->get_usergroup_list(false, true);
        $admin_groups = array_merge($GLOBALS['FORUM_DRIVER']->get_super_admin_groups(), $GLOBALS['FORUM_DRIVER']->get_moderator_groups());
        foreach (array_keys($usergroups) as $id) {
            if (($default) || (in_array($id, $admin_groups))) {
                $GLOBALS['SITE_DB']->query_insert('group_privileges', array('privilege' => $name, 'group_id' => $id, 'the_page' => '', 'module_the_name' => '', 'category_name' => '', 'the_value' => 1));
            }
        }
    }

    $GLOBALS['SITE_DB']->query_insert('privilege_list', array('p_section' => $section, 'the_name' => $name, 'the_default' => ($default ? 1 : 0)));
}

/**
 * Sets the privilege of a usergroup
 *
 * @param  GROUP $group_id The usergroup having the permission set
 * @param  ID_TEXT $permission The codename of the permission
 * @param  boolean $value Whether the usergroup has the permission
 * @param  ?ID_TEXT $page The ID code for the page being checked (null: current page)
 * @param  ?ID_TEXT $category_type The category-type for the permission (null: none required)
 * @param  ?ID_TEXT $category_name The category-name/value for the permission (null: none required)
 */
function set_privilege($group_id, $permission, $value, $page = null, $category_type = null, $category_name = null)
{
    if (is_null($page)) {
        $page = '';
    }
    if (is_null($category_type)) {
        $category_type = '';
    }
    if (is_null($category_name)) {
        $category_name = '';
    }

    $GLOBALS['SITE_DB']->query_delete('group_privileges', array('privilege' => $permission, 'group_id' => $group_id, 'the_page' => $page, 'module_the_name' => $category_type, 'category_name' => $category_name), '', 1);
    $GLOBALS['SITE_DB']->query_insert('group_privileges', array('privilege' => $permission, 'group_id' => $group_id, 'the_page' => $page, 'module_the_name' => $category_type, 'category_name' => $category_name, 'the_value' => $value ? 1 : 0));

    global $PRIVILEGE_CACHE;
    $PRIVILEGE_CACHE = array();
}

/**
 * Rename a privilege.
 *
 * @param  ID_TEXT $old The old name
 * @param  ID_TEXT $new The new name
 */
function rename_privilege($old, $new)
{
    $GLOBALS['SITE_DB']->query_update('privilege_list', array('the_name' => $new), array('the_name' => $old), '', 1);
    $GLOBALS['SITE_DB']->query_update('group_privileges', array('privilege' => $new), array('privilege' => $old), '', 1);
    $GLOBALS['SITE_DB']->query_update('member_privileges', array('privilege' => $new), array('privilege' => $old), '', 1);
}

/**
 * Delete a privilege, and every usergroup is then relaxed from the restrictions of this permission.
 *
 * @param  ID_TEXT $name The codename of the permission
 */
function delete_privilege($name)
{
    $GLOBALS['SITE_DB']->query_delete('privilege_list', array('the_name' => $name), '', 1);
    $GLOBALS['SITE_DB']->query('DELETE FROM ' . get_table_prefix() . 'group_privileges WHERE ' . db_string_not_equal_to('module_the_name', 'forums') . ' AND ' . db_string_equal_to('privilege', $name));
}

/**
 * Delete attachments solely used by the specified hook.
 *
 * @param  ID_TEXT $type The hook
 * @param  ?object $connection The database connection to use (null: standard site connection)
 */
function delete_attachments($type, $connection = null)
{
    if (get_option('attachment_cleanup') == '0') {
        return;
    }

    if (is_null($connection)) {
        $connection = $GLOBALS['SITE_DB'];
    }

    require_code('attachments2');
    require_code('attachments3');

    // Clear any de-referenced attachments
    $before = $connection->query_select('attachment_refs', array('a_id', 'id'), array('r_referer_type' => $type));
    foreach ($before as $ref) {
        // Delete reference (as it's not actually in the new comcode!)
        $connection->query_delete('attachment_refs', array('id' => $ref['id']), '', 1);

        // Was that the last reference to this attachment? (if so -- delete attachment)
        $test = $connection->query_select_value_if_there('attachment_refs', 'id', array('a_id' => $ref['a_id']));
        if (is_null($test)) {
            _delete_attachment($ref['a_id'], $connection);
        }
    }
}

/**
 * Deletes all language codes linked to by the specified table and attribute identifiers, if they exist.
 *
 * @param  ID_TEXT $table The table
 * @param  array $attrs The attributes
 * @param  ?object $connection The database connection to use (null: standard site connection)
 */
function mass_delete_lang($table, $attrs, $connection)
{
    if (count($attrs) == 0) {
        return;
    }

    if (is_null($connection)) {
        $connection = $GLOBALS['SITE_DB'];
    }

    $start = 0;
    do {
        $rows = $connection->query_select($table, $attrs, null, '', 1000, $start, true);
        if (!is_null($rows)) {
            foreach ($rows as $row) {
                foreach ($attrs as $attr) {
                    if (!is_null($row[$attr])) {
                        delete_lang($row[$attr], $connection);
                    }
                }
            }
        }
        $start += 1000;
    } while ((!is_null($rows)) && (count($rows) > 0));
}
