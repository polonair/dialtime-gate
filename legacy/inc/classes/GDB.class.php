<?php

class GDB extends CDB
{
    # Fields
    private static $_Instance = null;

    # Methods
    public function __construct()
    {
        $exists = file_exists(BELFRY2_STOREDIR . "/belfry.db");
        $this->_SqlApi = new SQLite3(BELFRY2_STOREDIR . "/belfry.db");
        $this->_SqlApi->busyTimeout(30000);
        if (!$exists)
        {
            $this->_SqlApi->exec(file_get_contents(__dir__ . "/GDB.sql"));
            exec("chmod -c 0777 " . BELFRY2_STOREDIR . "/belfry.db");
        }
    }
    private static function _getInstance()
    {
        if (self::$_Instance === null) self::$_Instance = new GDB();
        return self::$_Instance;
    }
    public static function select()
    {
        return GDB::_getInstance()->_select(func_num_args(), func_get_args());
    }
    public static function insert()
    {
        return GDB::_getInstance()->_insert();
    }
    public static function update($table)
    {
        return GDB::_getInstance()->_update($table);
    }
    public static function delete()
    {
        return GDB::_getInstance()->_delete();
    }
    public static function exe_raw($query)
    {
        GDB::_getInstance()->exe($query);
    }
    public function exe($query = ".empty")
    {
        if ($query != ".empty")
        {
            syslog(LOG_DEBUG, "executing RAW SQL: [$query]");
            return $this->_SqlApi->exec($query);
        }
        $this->_Sql .= ";";
        syslog(LOG_DEBUG, "executing SQL: [" . $this->_Sql . "]");
        if (substr($this->_Sql, 0, 6) === "SELECT")
        {
            $return = array();
            $result = $this->_SqlApi->query($this->_Sql);
            while ($row = $result->fetchArray(SQLITE3_ASSOC)) $return[] = $row;
            return $return;
        }
        return $this->_SqlApi->exec($this->_Sql);
    }
    public function count()
    {
        if (substr($this->_Sql, 0, 6) === "SELECT")
        {
            $return = array();
            $result = $this->_SqlApi->query($this->_Sql);
            while ($row = $result->fetchArray(SQLITE3_ASSOC)) $return[] = $row;
            return count($return);
        }
        return 0;
    }
}
