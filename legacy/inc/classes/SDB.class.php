<?php

class SDB extends CDB
{
    # Fields
    private static $_Instance = null;

    # Methods
    public function __construct()
    {
        $this->_SqlApi = new mysqli("localhost", "belfry", "belfry", "belfry2");
        $this->_SqlApi->set_charset("utf8");
        /*$queries = file_get_contents(__dir__ . "/SDB.sql");
        $queries = explode(";", $queries);
        foreach ($queries as $query) $this->_SqlApi->query($query);*/
    }
    private static function _getInstance()
    {
        if (self::$_Instance === null) self::$_Instance = new SDB();
        return self::$_Instance;
    }
    public static function select()
    {
        return SDB::_getInstance()->_select(func_num_args(), func_get_args());
    }
    public static function insert()
    {
        return SDB::_getInstance()->_insert();
    }
    public static function update($table)
    {
        return SDB::_getInstance()->_update($table);
    }
    public static function delete()
    {
        return SDB::_getInstance()->_delete();
    }
    public static function func($func_name, array $params)
    {
        return SDB::_getInstance()->_func($func_name, $params);
    }
    public static function funcc()
    {
        if (func_num_args() > 0)
        {
            $args = func_get_args();
            $params = array_slice($args, 1);
            return SDB::_getInstance()->_func($args[0], $params);
        }
    }
    public static function exe_raw($query)
    {
        return SDB::_getInstance()->exe($query);
    }
    public function exe($query = ".empty")
    {
        $this->_Sql .= ";";
        syslog(LOG_DEBUG, "executing SQL: [" . $this->_Sql . "]");
        if ($query != ".empty") return $this->_SqlApi->query($query);
        if (substr($this->_Sql, 0, 6) === "SELECT")
        {
            $return = array();
            $result = $this->_SqlApi->query($this->_Sql);
            if ($result)
                while ($row = $result->fetch_assoc()) $return[] = $row;
            return $return;
        }
        else
        {
            $queries = explode(";", $this->_Sql);
            $result = false;
            for ($i = 0; $i < count($queries); $i++)
            {
                $q = trim($queries[$i]);
                if (strlen($q) > 5) $result = $this->_SqlApi->query($queries[$i]);
            }
            return $result;
        }
    }
    public function count()
    {
        $this->_Sql .= ";";
        syslog(LOG_DEBUG, "counting SQL: [" . $this->_Sql . "]");
        if (substr($this->_Sql, 0, 6) === "SELECT")
        {
            $return = array();
            $result = $this->_SqlApi->query($this->_Sql);
            if ($result)
                while ($row = $result->fetch_assoc()) $return[] = $row;
            return count($return);
        }
        return 0;
    }
}
