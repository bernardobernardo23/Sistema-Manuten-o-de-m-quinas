<?php
session_start();
require 'db.php';

$erro = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'];
    $senha = $_POST['senha'];

    $sql = "SELECT * FROM usuarios WHERE email = :email";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':email' => $email]);
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($usuario && password_verify($senha, $usuario['senha'])) {
        $_SESSION['id_usuario'] = $usuario['id_usuario'];
        $_SESSION['nome'] = $usuario['nome'];
        $_SESSION['tipo_perfil'] = $usuario['tipo_perfil'];
        $_SESSION['especialidade'] = $usuario['especialidade'];
        header("Location: painel.php");
        exit;
    } else {
        $erro = "E-mail ou senha incorretos!";
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Login - Manutenção</title>
    <style>
        body { font-family: Arial, sans-serif; display: flex; justify-content: center; align-items: center; height: 100vh; background-color: #f0f2f5; }
        .login-box { background: white; padding: 2rem; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); width: 300px; }
        input { width: 100%; padding: 10px; margin: 10px 0; box-sizing: border-box; }
        button { width: 100%; padding: 10px; background-color: #007bff; color: white; border: none; border-radius: 4px; cursor: pointer; }
        button:hover { background-color: #0056b3; }
        .erro { color: red; font-size: 0.9em; text-align: center; }
    </style>
</head>
<body>
    <div class="login-box">
        <h2 style="text-align: center;">Sistema Manutenção</h2>
        <?php if($erro): ?>
            <p class="erro"><?php echo $erro; ?></p>
        <?php endif; ?>
        <form method="POST">
            <label>E-mail:</label>
            <input type="email" name="email" required placeholder="admin@empresa.com">
            
            <label>Senha:</label>
            <input type="password" name="senha" required placeholder="******">
            
            <button type="submit">Entrar</button>
        </form>
    </div>
</body>
</html>