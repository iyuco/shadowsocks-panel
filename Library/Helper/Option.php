<?php
/**
 * shadowsocks-panel
 * Add: 2016/4/8 13:03
 * Author: Sendya <18x@loacg.com>
 */

namespace Helper;

use Core\Database as DB;

class Option
{
    const CONFIG_FILE = DATA_PATH . "Config.php";

    public $k;
    public $v;


    private static $list;

    function __construct()
    {
        if (!self::$list) {
            self::$list = self::init();
        }
        return self::$list;
    }

    public static function get($k)
    {

        if (self::$list) {
            if (self::$list[$k]) {
                return self::$list[$k];
            }
        }
        $querySQL = "SELECT k, v FROM options WHERE k=?";
        $statement = DB::getInstance()->prepare($querySQL);
        $statement->bindValue(1, $k);
        $statement->execute();
        $opt = $statement->fetchObject(__CLASS__);
        return $opt->v;
    }

    /**
     * 模糊查找多个 Option
     * @param $k
     * @return Option
     */
    public static function getLike($k)
    {
        $querySQL = "SELECT k, v FROM options WHERE k LIKE '%?%'";
        $statement = DB::getInstance()->prepare($querySQL);
        $statement->bindValue(1, $k, DB::PARAM_STR);
        $statement->execute();
        return $statement->fetchAll(DB::FETCH_CLASS, __CLASS__);

    }

    public static function set($k, $v)
    {

        $sql = "UPDATE options SET v=:v WHERE k=:k";
        if (Option::get($k) == null) {
            $sql = "INSERT INTO options(k, v) VALUES(:k, :v)";
        }

        $inTransaction = DB::getInstance()->inTransaction();
        if (!$inTransaction) {
            DB::getInstance()->beginTransaction();
        }
        $statement = DB::getInstance()->prepare($sql);
        $statement->bindParam(":k", $k);
        $statement->bindParam(":v", $v);
        $statement->execute();
        if (!$inTransaction) {
            DB::getInstance()->commit();
        }
        self::$list = self::init();
    }

    public static function delete($k)
    {
        $sql = "DELETE FROM options WHERE k=:k";
        $statement = DB::getInstance()->prepare($sql);
        $statement->bindParam(":k", $k);
        $statement->execute();
    }

    public static function init()
    {
        $stn = DB::getInstance()->prepare("SELECT k, v FROM options");
        $stn->execute();
        $opt = $stn->fetchAll(DB::FETCH_UNIQUE | DB::FETCH_COLUMN);
        // $GLOBALS['OPTIONS'] = $opt;
        self::$list = $opt;
        return $opt;
    }

    public static function getOptions()
    {
        if (!self::$list) {
            self::$list = self::init();
        }
        return self::$list;
    }

    public static function createKey($length = 30)
    {
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()-_ []{}<>~`+=,.;:/?|';

        $str = '';
        for ($i = 0; $i < $length; $i++) {
            $str .= $chars[mt_rand(0, strlen($chars) - 1)];
        }
        return $str;
    }

    public static function getConfig($k)
    {
        if (!file_exists(self::CONFIG_FILE)) {
            return false;
        }
        $str = file_get_contents(self::CONFIG_FILE);
        $config = preg_match("/define\\('" . preg_quote($k) . "', '(.*)'\\);/", $str, $res);
        return $res[1];
    }

    public static function setConfig($k, $v)
    {
        if (!file_exists(self::CONFIG_FILE)) {
            return false;
        }
        $str = file_get_contents(self::CONFIG_FILE);

        $str2 = preg_replace("/define\\('" . preg_quote($k) . "', '(.*)'\\);/",
            "define('" . preg_quote($k) . "', '" . $v . "');", $str);

        file_put_contents(self::CONFIG_FILE, $str2);
    }
}