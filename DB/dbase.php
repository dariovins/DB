<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 foldmethod=marker: */
// +----------------------------------------------------------------------+
// | PHP Version 4                                                        |
// +----------------------------------------------------------------------+
// | Copyright (c) 1997-2004 The PHP Group                                |
// +----------------------------------------------------------------------+
// | This source file is subject to version 2.02 of the PHP license,      |
// | that is bundled with this package in the file LICENSE, and is        |
// | available at through the world-wide-web at                           |
// | http://www.php.net/license/2_02.txt.                                 |
// | If you did not receive a copy of the PHP license and are unable to   |
// | obtain it through the world-wide-web, please send a note to          |
// | license@php.net so we can mail you a copy immediately.               |
// +----------------------------------------------------------------------+
// | Author: Tomas V.V.Cox <cox@idecnet.com>                              |
// +----------------------------------------------------------------------+
//
// $Id$


// XXX legend:
//  You have to compile your PHP with the --enable-dbase option


require_once 'DB/common.php';

/**
 * Database independent query interface definition for PHP's dbase
 * extension.
 *
 * @package  DB
 * @version  $Id$
 * @category Database
 * @author   Stig Bakken <ssb@php.net>
 */
class DB_dbase extends DB_common
{
    // {{{ properties

    var $connection;
    var $phptype, $dbsyntax;
    var $prepare_tokens = array();
    var $prepare_types = array();
    var $transaction_opcount = 0;
    var $res_row = array();
    var $result = 0;

    // }}}
    // {{{ constructor

    /**
     * DB_mysql constructor.
     *
     * @access public
     */
    function DB_dbase()
    {
        $this->DB_common();
        $this->phptype = 'dbase';
        $this->dbsyntax = 'dbase';
        $this->features = array(
            'prepare'       => false,
            'pconnect'      => false,
            'transactions'  => false,
            'limit'         => false
        );
        $this->errorcode_map = array();
    }

    // }}}
    // {{{ connect()

    function connect($dsninfo, $persistent = false)
    {
        if (!DB::assertExtension('dbase')) {
            return $this->raiseError(DB_ERROR_EXTENSION_NOT_FOUND);
        }
        $this->dsn = $dsninfo;
        ob_start();
        $conn  = dbase_open($dsninfo['database'], 0);
        $error = ob_get_contents();
        ob_end_clean();
        if (!$conn) {
            return $this->raiseError(DB_ERROR_CONNECT_FAILED, null,
                                     null, null, strip_tags($error));
        }
        $this->connection = $conn;
        return DB_OK;
    }

    // }}}
    // {{{ disconnect()

    function disconnect()
    {
        $ret = dbase_close($this->connection);
        $this->connection = null;
        return $ret;
    }

    // }}}
    // {{{ &query()

    function &query($query = null)
    {
        // emulate result resources
        $this->res_row[$this->result] = 0;
        $tmp =& new DB_result($this, $this->result++);
        return $tmp;
    }

    // }}}
    // {{{ fetchInto()

    /**
     * Fetch a row and insert the data into an existing array.
     *
     * The array's keys will be converted to lower case if
     * <var>$options['optimize']</var> is set to <samp>portability</samp>
     * AND <var>$fetchmode</var> is set to <samp>DB_FETCHMODE_ASSOC</samp>.
     *
     * <var>$options['optimize']</var> can be set when instantiating the
     * DB class via DB::connect(), but can be changed using
     * DB_common::setOption.
     *
     * <var>$fetchmode</var> is usually set via DB_common::setFetchMode().
     *
     * @param $result    PostgreSQL result identifier
     * @param $row       (reference) array where data from the row is stored
     * @param $fetchmode how the resulting array should be indexed
     * @param $rownum    the row number to fetch
     *
     * @return mixed DB_OK on success, NULL when end of result set is
     *               reached or on failure
     *
     * @see DB::connect()
     * @see DB_common::setOption
     * @see DB_common::$options
     * @see DB_common::setFetchMode()
     * @access public
     */
    function fetchInto($res, &$row, $fetchmode, $rownum = null)
    {
        if ($rownum === null) {
            $rownum = $this->res_row[$res]++;
        }
        if ($fetchmode & DB_FETCHMODE_ASSOC) {
            $row = @dbase_get_record_with_names($this->connection, $rownum);
            if ($this->options['optimize'] == 'portability' && $row) {
                $row = array_change_key_case($row, CASE_LOWER);
            }
        } else {
            $row = @dbase_get_record($this->connection, $rownum);
        }
        if (!$row) {
            return null;
        }
        return DB_OK;
    }

    // }}}
    // {{{ numCols()

    function numCols($foo)
    {
        return dbase_numfields($this->connection);
    }

    // }}}
    // {{{ numRows()

    function numRows($foo)
    {
        return dbase_numrecords($this->connection);
    }

    // }}}

}

/*
 * Local variables:
 * tab-width: 4
 * c-basic-offset: 4
 * End:
 */

?>
