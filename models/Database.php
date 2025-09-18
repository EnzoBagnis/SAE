<?php
class Database {
    public static function getConnection() {
        // Lire le fichier .env
        $env = parse_ini_file(__DIR__ . '/../config/.env');

        $servername = $env['DB_HOST'];
        $username = $env['DB_USER'];
        $password = $env['DB_PASS'];
        $dbname = $env['DB_NAME'];

        try {
            $bdd = new PDO(
                "mysql:host=$servername;dbname=$dbname;charset=utf8mb4",
                $username,
                $password,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_PERSISTENT => false
                ]
            );
            return $bdd;
        } catch (PDOException $e) {
            die("Erreur de connexion : " . $e->getMessage());
        }
    }
}
?>