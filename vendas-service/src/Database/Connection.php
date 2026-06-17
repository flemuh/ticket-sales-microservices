<?php

namespace App\Database;

use PDO;
use PDOException;

class Connection
{
    public static function getPDO(): PDO
    {
        $host = getenv('DB_HOST') ?: 'mysql';
        $db   = getenv('DB_NAME') ?: 'vendas_db';
        $user = getenv('DB_USER') ?: 'root';
        $pass = getenv('DB_PASSWORD') ?: 'root';

        $dsn = "mysql:host={$host};dbname={$db};charset=utf8mb4";

        try {
            return new PDO($dsn, $user, $pass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);
        } catch (PDOException $e) {
            throw new PDOException('Erro ao conectar no banco: ' . $e->getMessage());
        }
    }
}