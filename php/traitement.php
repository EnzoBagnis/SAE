<?php
$servername = getenv('DB_HOST');
$username   = getenv('DB_USER');
$password   = getenv('DB_PASS');
$dbname     = getenv('DB_NAME');

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
} catch (PDOException $e) {
    die("Erreur de connexion : " . $e->getMessage());
}


if(isset($_POST['ok'])){
    extract($_POST);

    $hashedPassword = password_hash($mdp, PASSWORD_DEFAULT);
    $requete = $bdd->prepare("INSERT INTO utilisateurs VALUES(0, :nom, :prenom, :mdp, :mail)");
    $requete->execute(array(
        'nom' => $nom,
        'prenom' => $prenom,
        'mdp' => $hashedPassword,
        'mail' => $mail
    ));
    echo "Inscription réussie !";
}


?>
