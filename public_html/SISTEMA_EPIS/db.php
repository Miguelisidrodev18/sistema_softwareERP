<?php
// ============================================================
// CLASE DE CONEXION A BASE DE DATOS (PDO Singleton)
// ============================================================

class DB {
    private static ?PDO $instance = null;

    public static function conn(): PDO {
        if (self::$instance === null) {
            $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ];
            self::$instance = new PDO($dsn, DB_USER, DB_PASS, $options);
        }
        return self::$instance;
    }

    // Ejecuta una consulta con parametros y retorna el statement
    public static function query(string $sql, array $params = []): PDOStatement {
        $stmt = self::conn()->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    // Retorna todos los registros
    public static function fetchAll(string $sql, array $params = []): array {
        return self::query($sql, $params)->fetchAll();
    }

    // Retorna un solo registro
    public static function fetchOne(string $sql, array $params = []): array|false {
        return self::query($sql, $params)->fetch();
    }

    // Retorna un valor escalar
    public static function fetchColumn(string $sql, array $params = []): mixed {
        return self::query($sql, $params)->fetchColumn();
    }

    // Inserta un registro y retorna el ID insertado
    public static function insert(string $table, array $data): int {
        $cols    = implode(', ', array_keys($data));
        $holders = implode(', ', array_fill(0, count($data), '?'));
        self::query("INSERT INTO `$table` ($cols) VALUES ($holders)", array_values($data));
        return (int) self::conn()->lastInsertId();
    }

    // Actualiza registros
    public static function update(string $table, array $data, string $where, array $whereParams = []): int {
        $set    = implode(', ', array_map(fn($k) => "`$k` = ?", array_keys($data)));
        $params = array_merge(array_values($data), $whereParams);
        $stmt   = self::query("UPDATE `$table` SET $set WHERE $where", $params);
        return $stmt->rowCount();
    }

    // Elimina registros
    public static function delete(string $table, string $where, array $params = []): int {
        $stmt = self::query("DELETE FROM `$table` WHERE $where", $params);
        return $stmt->rowCount();
    }

    // Obtiene la configuracion del sistema
    public static function config(string $key, string $default = ''): string {
        $val = self::fetchColumn("SELECT `valor` FROM `configuracion` WHERE `clave` = ?", [$key]);
        return $val !== false ? $val : $default;
    }
}
