<?php
namespace App\BD;

class BD
{
    protected static $bd = null;
    
    private function __construct(string $host, string $database, string $username, string $password)
    {
        try {
            self::$bd = new \PDO("mysql:host=" . $host . ";dbname=" . $database, $username, $password);
            self::$bd->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            throw new Exception($e->getMessage(), $e->getCode());
        }
    }

    public static function getConexion(string $host, string $database, string $username, string $password)
    {
        if (!self::$bd) {
            new BD($host, $database, $username, $password);
        }
        return self::$bd;
    }
}
