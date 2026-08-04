<?php
include './admin/classes/DbConection.php';

try {
    $nombre = 'Administrador General';
    $usuario = 'admin';
    $correo = 'admin@violetasantander.com';
    $clave = 'Violeta2026*';
    $rol = 'Administrador';
    $estado = 'activo';

    $passwordHash = password_hash($clave, PASSWORD_DEFAULT);

    $db = new DbConection();
    $pdo = $db->openConect();

    $sqlVerificar = "SELECT id FROM usuarios_panel WHERE usuario = :usuario OR correo = :correo LIMIT 1";
    $stmtVerificar = $pdo->prepare($sqlVerificar);
    $stmtVerificar->bindValue(':usuario', $usuario, PDO::PARAM_STR);
    $stmtVerificar->bindValue(':correo', $correo, PDO::PARAM_STR);
    $stmtVerificar->execute();

    $existe = $stmtVerificar->fetch(PDO::FETCH_ASSOC);

    if ($existe) {
        echo "Ya existe un usuario con ese usuario o correo.";
        exit;
    }

    $sqlInsert = "INSERT INTO usuarios_panel (
                    nombre_completo,
                    usuario,
                    correo,
                    password_hash,
                    rol,
                    estado
                  ) VALUES (
                    :nombre_completo,
                    :usuario,
                    :correo,
                    :password_hash,
                    :rol,
                    :estado
                  )";

    $stmtInsert = $pdo->prepare($sqlInsert);
    $stmtInsert->bindValue(':nombre_completo', $nombre, PDO::PARAM_STR);
    $stmtInsert->bindValue(':usuario', $usuario, PDO::PARAM_STR);
    $stmtInsert->bindValue(':correo', $correo, PDO::PARAM_STR);
    $stmtInsert->bindValue(':password_hash', $passwordHash, PDO::PARAM_STR);
    $stmtInsert->bindValue(':rol', $rol, PDO::PARAM_STR);
    $stmtInsert->bindValue(':estado', $estado, PDO::PARAM_STR);
    $stmtInsert->execute();

    echo "Usuario administrador creado correctamente.<br>";
    echo "Usuario: admin<br>";
    echo "Correo: admin@violetasantander.com<br>";
    echo "Clave: Violeta2026*<br>";

} catch (Throwable $e) {
    echo "Error al crear el usuario: " . $e->getMessage();
}