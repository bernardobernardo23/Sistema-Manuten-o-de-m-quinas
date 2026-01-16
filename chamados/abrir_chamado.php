<?php
// chamados/abrir_chamado.php
session_start();
require '../db.php'; // Ajuste o caminho se necessário

if (!isset($_SESSION['id_usuario'])) {
    header("Location: ../index.php");
    exit;
}

$mensagem = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id_usuario = $_SESSION['id_usuario'];
    $n_maquina = $_POST['n_maquina'];
    $ambito = $_POST['ambito']; // Novo campo
    $titulo = $_POST['titulo'];
    $descricao = $_POST['descricao'];
    $prioridade = $_POST['prioridade'];
    $maquina_parada = isset($_POST['maquina_parada']) ? 1 : 0;
    
    // Upload de Mídia
    $caminho_midia = null;
    if (isset($_FILES['midia']) && $_FILES['midia']['error'] == 0) {
        // Salva na pasta uploads que deve estar na raiz ou configurada corretamente
        $extensao = pathinfo($_FILES['midia']['name'], PATHINFO_EXTENSION);
        $novo_nome = uniqid("chamado_") . "." . $extensao;
        // Atenção: Ajuste o caminho de destino conforme sua estrutura de pastas
        $destino = "../uploads/" . $novo_nome; 
        
        if (move_uploaded_file($_FILES['midia']['tmp_name'], $destino)) {
            $caminho_midia = "uploads/" . $novo_nome; // Salva o caminho relativo para o site
        }
    }

    // SQL com o novo campo 'ambito'
    $sql = "INSERT INTO chamados (id_solicitante, n_maquina, ambito, titulo_motivo, descricao_detalhada, caminho_midia, prioridade, maquina_parada, status) 
            VALUES (:id_user, :n_maq, :ambito, :tit, :desc, :midia, :prio, :parada, 'aberto')";
    
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':id_user' => $id_usuario,
            ':n_maq' => $n_maquina,
            ':ambito' => $ambito,
            ':tit' => $titulo,
            ':desc' => $descricao,
            ':midia' => $caminho_midia,
            ':prio' => $prioridade,
            ':parada' => $maquina_parada
        ]);
        $mensagem = "Chamado de " . ucfirst($ambito) . " para Máquina #$n_maquina aberto!";
    } catch (PDOException $e) {
        $mensagem = "Erro: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Novo Chamado</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">
    <div class="max-w-2xl mx-auto py-10 px-4">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-2xl font-bold text-gray-800">Abrir Solicitação</h1>
            <a href="../painel.php" class="text-blue-600 hover:underline">← Voltar</a>
        </div>

        <?php if($mensagem): ?>
            <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded shadow">
                <p><?php echo $mensagem; ?></p>
            </div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data" class="bg-white p-6 rounded-xl shadow-md space-y-6">
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Número da Máquina</label>
                    <input type="number" name="n_maquina" required placeholder="Ex: 45" 
                        class="mt-1 block w-full border border-gray-300 rounded-md py-2 px-3 focus:ring-blue-500 focus:border-blue-500 bg-gray-50">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Natureza do Problema</label>
                    <select name="ambito" required class="mt-1 block w-full border border-gray-300 rounded-md py-2 px-3 focus:ring-blue-500 focus:border-blue-500 bg-gray-50">
                        <option value="">Selecione...</option>
                        <option value="eletrica">⚡ Elétrica</option>
                        <option value="pneumatica">💨 Pneumática</option>
                        <option value="geral">⚙️ Mecânica / Geral</option>
                    </select>
                    <p class="text-xs text-gray-500 mt-1">Isso direciona o chamado para a equipe certa.</p>
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">O que está acontecendo?</label>
                <input type="text" name="titulo" required placeholder="Ex: Motor não liga" class="mt-1 block w-full border border-gray-300 rounded-md py-2 px-3 focus:ring-blue-500 bg-gray-50">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">Detalhes</label>
                <textarea name="descricao" rows="3" class="mt-1 block w-full border border-gray-300 rounded-md py-2 px-3 focus:ring-blue-500 bg-gray-50"></textarea>
            </div>

            <div class="grid grid-cols-1 gap-y-6 gap-x-4 sm:grid-cols-6">
                <div class="sm:col-span-3">
                    <label class="block text-sm font-medium text-gray-700">Prioridade</label>
                    <select name="prioridade" class="mt-1 block w-full border-gray-300 rounded-md bg-gray-50">
                        <option value="baixa">Baixa</option>
                        <option value="media" selected>Média</option>
                        <option value="alta">Alta</option>
                    </select>
                </div>

                <div class="sm:col-span-3 flex items-center h-full pt-6">
                    <div class="flex items-center">
                        <input id="maquina_parada" name="maquina_parada" type="checkbox" class="h-5 w-5 text-red-600 focus:ring-red-500 border-gray-300 rounded">
                        <label for="maquina_parada" class="ml-2 block text-sm font-bold text-red-600">
                            MÁQUINA PARADA?
                        </label>
                    </div>
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">Evidência (Opcional)</label>
                <input type="file" name="midia" class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100"/>
            </div>

            <button type="submit" class="w-full flex justify-center py-3 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-green-600 hover:bg-green-700 transition">
                🚀 Enviar Solicitação
            </button>
        </form>
    </div>
</body>
</html>