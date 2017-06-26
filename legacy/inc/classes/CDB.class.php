<?php

abstract class CDB
{
    # Fields
    protected $_Sql = "";
    protected $_SqlApi = null;
    private $_Tagging = false;

    # Methods
    public function _insert()
    {
        return $this->_append("INSERT ", true);
    }
    public function _update($table)
    {
        return $this->_append("UPDATE `$table` ", true);
    }
    public function _delete()
    {
        return $this->_append("DELETE ", true);
    }
    public function _select($argc, array $args)
    {
        $str = "";
        if ($argc === 0) $str = "*";
        else
        {
            if ($argc === 1 && is_array($args[0]))
            {
                foreach ($args[0] as $pseudo => $real) $str .= "`$real` AS `$pseudo`, ";
            }
            else
            {
                foreach ($args as $arg) $str .= "`$arg`, ";
            }
            $str = substr($str, 0, -2);
            $str = str_replace(array(
                ".",
                "`[",
                "]`"), array(
                "`.`",
                "",
                ""), $str);
        }
        return $this->_append("SELECT $str ", true);
    }
    public function _func($func, array $args)
    {
        $params = "";
        foreach ($args as $arg)
        {
            $params .= "'$arg', ";
        }
        $params = substr($params, 0, -2);
        return $this->_append("SELECT `$func` ($params) AS `result`;", true);
    }
    public function inner_join($table)
    {
        return $this->_append("INNER JOIN `$table` ");
    }
    public function left_outer_join($table)
    {
        return $this->_append("LEFT OUTER JOIN `$table` ");
    }
    public function on(array $fields)
    {
        $arg1 = array_keys($fields)[0];
        $arg2 = $fields[$arg1];
        $str = str_replace(".", "`.`", "ON `$arg1` = `$arg2` ");
        return $this->_append($str);
    }
    public function from($table)
    {
        return $this->_append("FROM `$table` ");
    }
    public function as_($pseudo)
    {
        return $this->_append("AS `$pseudo` ");
    }
    public function where($name)
    {
        return $this->_append(str_replace(".", "`.`", "WHERE `$name` "));
    }
    public function opEQ($value)
    {
        $str = "= '$value' ";
        if (strstr($str, "'[") && strstr($str, "]'")) $str = str_replace(array(
                ".",
                "`[",
                "]`"), array(
                "`.`",
                "`",
                "`"), "= `$value` ");
        return $this->_append($str);
    }
    public function opNEQ($value)
    {
        $str = "<> '$value' ";
        if (strstr($str, "'[") && strstr($str, "]'")) $str = str_replace(array(
                ".",
                "`[",
                "]`"), array(
                "`.`",
                "`",
                "`"), "<> `$value` ");
        return $this->_append($str);
    }
    public function opAND($name)
    {
        return $this->_append(str_replace(".", "`.`", "AND `$name` "));
    }
    public function opOR($name)
    {
        return $this->_append(str_replace(".", "`.`", "OR `$name` "));
    }
    public function into($table)
    {
        return $this->_append("INTO `$table` ");
    }
    public function set(array $values)
    {
        $str = "";
        foreach ($values as $name => $value)
        {
            $str .= "`$name` = '$value', ";
        }
        $str = substr($str, 0, -2);
        return $this->_append("SET $str ");
    }
    public function values(array $values)
    {
        $flds = "";
        $data = "";
        foreach ($values as $name => $value)
        {
            $flds .= "`$name`, ";
            $data .= "'$value', ";
        }
        $flds = substr($flds, 0, -2);
        $data = substr($data, 0, -2);
        return $this->_append("($flds) VALUES ($data) ");
    }
    public function opLT($value)
    {
        return $this->_append("< '$value' ");
    }
    public function opGT($value)
    {
        return $this->_append("> '$value' ");
    }
    public function limit($count)
    {
        return $this->_append("LIMIT $count ");
    }
    public function asc()
    {
        return $this->_append("ASC ");
    }
    public function order_by($param)
    {
        return $this->_append("ORDER BY `$param` ");
    }
    public function group_by($param)
    {
        return $this->_append("GROUP BY `$param` ");
    }
    public function get_sql($clear = true)
    {
        $return = $this->_Sql;
        if ($clear) $this->_Sql = "";
        return $return;
    }
    public function tag_to()
    {
        $this->_append("; ");
        $this->_Tagging = true;
        return $this;
    }
    private function _append($str, $clear = false)
    {
        if ($clear && !$this->_Tagging) $this->_Sql = $str;
        else  $this->_Sql .= $str;
        $this->_Tagging = false;
        return $this;
    }
    abstract function exe($query);
    abstract function count();
}
