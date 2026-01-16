<?php
session_start();

if (!isset($_SESSION['id_usuario'])) {
    header("Location: index.php");
    exit;
}

$nome = $_SESSION['nome'];
$perfil = $_SESSION['tipo_perfil'];
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Painel - Manutenção</title>
    <style>
        body { font-family: sans-serif; padding: 20px; }
        .header { display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #ccc; padding-bottom: 10px; }
        .menu { margin-top: 20px; }
        .btn { padding: 15px 30px; text-decoration: none; color: white; border-radius: 5px; margin-right: 10px; display: inline-block; }
        .btn-abrir { background-color: #28a745; }
        .btn-lista { background-color: #007bff; }
        .btn-sair { background-color: #dc3545; padding: 5px 15px; font-size: 0.9em; }
    </style>
</head>
<body>

    <div class="header">
        <div>
            <h1>Olá, <?php echo htmlspecialchars($nome); ?></h1>
            <p>Perfil: <strong><?php echo ucfirst($perfil); ?></strong></p>
        </div>
        <a href="logout.php" class="btn btn-sair">Sair</a>
    </div>

    <div class="menu">
        <h2>O que você deseja fazer?</h2>

        <?php if ($perfil == 'solicitante' || $perfil == 'admin'): ?>
            <div style="margin-bottom: 20px;">
                <p>Precisa de manutenção?</p>
                <a href="abrir_chamado.php" class="btn btn-abrir">⚡ Abrir Novo Chamado</a>
                <a href="meus_chamados.php" class="btn">Meus Chamados</a>
            </div>
        <?php endif; ?>

        <?php if ($perfil == 'tecnico' || $perfil == 'admin'): ?>
            <hr>
            <div style="margin-bottom: 20px;">
                <p>Área da Manutenção</p>
                <a href="lista_chamados.php" class="btn btn-lista">📋 Ver Lista de Tarefas</a>
                <?php if($_SESSION['especialidade'] == 'eletrica'): ?>
                    <p><small>Filtrando apenas chamados de Elétrica</small></p>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>

</body>
</html>