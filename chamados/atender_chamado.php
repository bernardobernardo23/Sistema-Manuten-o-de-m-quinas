<?php
// chamados/atender_chamado.php
session_start();
require '../db.php';

if (!isset($_SESSION['id_usuario']) || $_SESSION['tipo_perfil'] == 'solicitante') {
    header("Location: ../painel.php");
    exit;
}

$id_chamado = $_GET['id'] ?? null;
$id_usuario_logado = $_SESSION['id_usuario'];

if (!$id_chamado) die("Chamado não identificado.");

$mensagem = "";

// ==========================================================
// 1. AÇÕES DO SISTEMA (POST)
// ==========================================================

// AÇÃO A: Assumir Responsabilidade
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['acao']) && $_POST['acao'] == 'assumir') {
    $stmt = $pdo->prepare("UPDATE chamados SET id_tecnico_responsavel = :tec, status = 'em_andamento' WHERE id_chamado = :id");
    $stmt->execute([':tec' => $id_usuario_logado, ':id' => $id_chamado]);
    $mensagem = "Você assumiu este chamado! O status mudou para Em Andamento.";
}

// AÇÃO B: Atualizar Status de uma Peça Específica (COM LOG AUTOMÁTICO)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['acao']) && $_POST['acao'] == 'atualizar_item') {
    $id_item = $_POST['id_item'];
    $novo_status_item = $_POST['novo_status_item'];
    
    // 1. Busca nome da peça para o log
    $item = $pdo->query("SELECT descricao_item, id_chamado FROM itens_compra WHERE id_item = $id_item")->fetch();
    
    // 2. Atualiza o status da peça
    $stmt = $pdo->prepare("UPDATE itens_compra SET status_item = :st WHERE id_item = :id_it");
    $stmt->execute([':st' => $novo_status_item, ':id_it' => $id_item]);
    
    // 3. INSERE LOG NA DESCRIÇÃO DO CHAMADO
    $log_msg = "[" . date('d/m H:i') . "] SISTEMA: Peça '{$item['descricao_item']}' marcada como " . strtoupper($novo_status_item);
    $stmt_log = $pdo->prepare("UPDATE chamados SET descricao_detalhada = CONCAT(descricao_detalhada, '\n---\n', :log) WHERE id_chamado = :id");
    $stmt_log->execute([':log' => $log_msg, ':id' => $id_chamado]);

    $mensagem = "Status do item atualizado e registrado no histórico.";
}

// AÇÃO C: Atualizar Chamado (Relatório, Status Geral, Adicionar Peça)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['acao']) && $_POST['acao'] == 'atualizar_chamado') {
    $novo_status = $_POST['status_geral'];
    $relatorio = $_POST['relatorio'];
    $peca_nome = $_POST['peca_nome'] ?? '';
    $peca_qtd = $_POST['peca_qtd'] ?? 1;

    // Busca dados atuais
    $dados_atuais = $pdo->query("SELECT descricao_detalhada, id_solicitante, n_maquina FROM chamados WHERE id_chamado = $id_chamado")->fetch();
    
    // Concatena relatório
    $relatorio_formatado = ($relatorio) ? "[" . date('d/m H:i') . "] Técnico: " . $relatorio : "";
    
    // Atualiza Chamado
    $sql_up = "UPDATE chamados SET status = :st, descricao_detalhada = CONCAT(descricao_detalhada, '\n---\n', :relat) WHERE id_chamado = :id";
    // Se for Cancelado ou Concluido, marca data de fechamento
    if ($novo_status == 'concluido' || $novo_status == 'cancelado') {
        $sql_up = "UPDATE chamados SET status = :st, data_fechamento = NOW(), descricao_detalhada = CONCAT(descricao_detalhada, '\n---\n', :relat) WHERE id_chamado = :id";
    }
    
    $stmt = $pdo->prepare($sql_up);
    $stmt->execute([':st' => $novo_status, ':relat' => $relatorio_formatado, ':id' => $id_chamado]);

    // Insere Nova Peça (com Log)
    if (!empty($peca_nome)) {
        $pdo->prepare("INSERT INTO itens_compra (id_chamado, descricao_item, quantidade, status_item) VALUES (?, ?, ?, 'solicitado')")
            ->execute([$id_chamado, $peca_nome, $peca_qtd]);
        
        // Log da solicitação
        $log_msg = "[" . date('d/m H:i') . "] SISTEMA: Nova peça solicitada: $peca_nome (x$peca_qtd)";
        $pdo->prepare("UPDATE chamados SET status = 'aguardando_pecas', descricao_detalhada = CONCAT(descricao_detalhada, '\n---\n', ?) WHERE id_chamado = ?")
            ->execute([$log_msg, $id_chamado]);
        
        header("Refresh:0"); 
    } else {
        // Se não inseriu peça, envia email normal
        enviarNotificacaoEmail($pdo, $dados_atuais['id_solicitante'], $dados_atuais['n_maquina'], $novo_status);
    }
    
    $mensagem = "Atualizações salvas com sucesso!";
}

// ==========================================================
// 2. BUSCA DE DADOS
// ==========================================================
$sql = "SELECT c.*, u.nome as solicitante, t.nome as tecnico_nome 
        FROM chamados c 
        LEFT JOIN usuarios u ON c.id_solicitante = u.id_usuario
        LEFT JOIN usuarios t ON c.id_tecnico_responsavel = t.id_usuario
        WHERE c.id_chamado = :id";
$stmt = $pdo->prepare($sql);
$stmt->execute([':id' => $id_chamado]);
$chamado = $stmt->fetch(PDO::FETCH_ASSOC);

$pecas = $pdo->query("SELECT * FROM itens_compra WHERE id_chamado = $id_chamado")->fetchAll(PDO::FETCH_ASSOC);

// Definições visuais de Status (Com Cancelado)
$coresStatus = [
    'aberto' => 'bg-gray-500',
    'em_andamento' => 'bg-blue-600',
    'aguardando_pecas' => 'bg-yellow-500',
    'concluido' => 'bg-green-600',
    'cancelado' => 'bg-red-800' // Vermelho escuro para cancelado
];
$statusAtual = $chamado['status'];
$corBadge = $coresStatus[$statusAtual] ?? 'bg-gray-500';

$souResponsavel = ($chamado['id_tecnico_responsavel'] == $id_usuario_logado);
$temResponsavel = !empty($chamado['id_tecnico_responsavel']);
$chamadoFechado = ($statusAtual == 'concluido' || $statusAtual == 'cancelado');

// ==========================================================
// 3. FUNÇÃO EMAIL (Placeholder)
// ==========================================================
function enviarNotificacaoEmail($pdo, $id_user, $maq, $status) {
    // Implementar PHPMailer aqui futuramente
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Atendimento #<?php echo $id_chamado; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 pb-10">

    <div class="w-full <?php echo $corBadge; ?> text-white text-center py-4 shadow-md mb-6">
        <h1 class="text-3xl font-bold uppercase tracking-wider">
            <?php echo str_replace('_', ' ', $statusAtual); ?>
        </h1>
        <p class="text-sm opacity-90">Chamado #<?php echo $id_chamado; ?> - Máquina <?php echo $chamado['n_maquina']; ?></p>
    </div>

    <div class="max-w-6xl mx-auto px-4">
        
        <div class="mb-4 flex justify-between items-center">
            <a href="lista_chamados.php" class="text-gray-600 hover:text-blue-600 flex items-center">← Voltar para a Lista</a>
            <?php if($mensagem): ?>
                <span class="bg-green-100 text-green-800 px-4 py-2 rounded shadow text-sm font-bold animate-bounce"><?php echo $mensagem; ?></span>
            <?php endif; ?>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

            <div class="space-y-6">
                <div class="bg-white rounded-lg shadow p-6 border-t-4 border-blue-500">
                    <h3 class="text-gray-500 text-xs font-bold uppercase mb-1">Solicitante</h3>
                    <p class="text-gray-900 font-medium mb-4"><?php echo htmlspecialchars($chamado['solicitante']); ?></p>

                    <h3 class="text-gray-500 text-xs font-bold uppercase mb-1">Responsável Técnico</h3>
                    <p class="text-gray-900 font-medium mb-4">
                        <?php echo $chamado['tecnico_nome'] ? htmlspecialchars($chamado['tecnico_nome']) : '<span class="text-red-500 italic">Não atribuído</span>'; ?>
                    </p>

                    <h3 class="text-gray-500 text-xs font-bold uppercase mb-1">Problema (<?php echo ucfirst($chamado['ambito']); ?>)</h3>
                    <p class="text-red-600 font-bold text-lg mb-2"><?php echo htmlspecialchars($chamado['titulo_motivo']); ?></p>
                    
                    <?php if($chamado['caminho_midia']): ?>
                        <div class="mt-4 border p-2 rounded">
                            <a href="../<?php echo $chamado['caminho_midia']; ?>" target="_blank">
                                <span class="text-blue-600 text-xs flex items-center gap-1">📎 Ver Anexo</span>
                            </a>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="bg-white rounded-lg shadow p-6">
                    <h3 class="font-bold text-gray-800 border-b pb-2 mb-4">Gestão de Peças</h3>
                    <?php if(count($pecas) == 0): echo '<p class="text-gray-400 text-sm italic">Nenhuma peça solicitada.</p>'; endif; ?>

                    <div class="space-y-3">
                        <?php foreach($pecas as $p): ?>
                            <div class="bg-gray-50 p-3 rounded border flex flex-col gap-2">
                                <div class="flex justify-between">
                                    <span class="font-bold text-gray-700"><?php echo $p['quantidade']; ?>x <?php echo htmlspecialchars($p['descricao_item']); ?></span>
                                    
                                    <?php 
                                        $corItem = match($p['status_item']) {
                                            'solicitado' => 'text-orange-600',
                                            'comprado' => 'text-blue-600',
                                            'instalado' => 'text-green-600',
                                            default => 'text-gray-600'
                                        };
                                    ?>
                                    <span class="text-xs font-bold uppercase <?php echo $corItem; ?>"><?php echo $p['status_item']; ?></span>
                                </div>

                                <?php if(!$chamadoFechado && ($souResponsavel || $_SESSION['tipo_perfil'] == 'admin')): ?>
                                    <form method="POST" class="flex gap-1 justify-end">
                                        <input type="hidden" name="acao" value="atualizar_item">
                                        <input type="hidden" name="id_item" value="<?php echo $p['id_item']; ?>">
                                        
                                        <?php if($p['status_item'] == 'solicitado'): ?>
                                            <button type="submit" name="novo_status_item" value="comprado" class="px-2 py-1 bg-blue-100 text-blue-700 text-xs rounded hover:bg-blue-200">Marcar Comprado</button>
                                        <?php endif; ?>
                                        
                                        <?php if($p['status_item'] == 'comprado'): ?>
                                            <button type="submit" name="novo_status_item" value="instalado" class="px-2 py-1 bg-green-100 text-green-700 text-xs rounded hover:bg-green-200">Marcar Instalado</button>
                                        <?php endif; ?>
                                    </form>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <div class="lg:col-span-2">
                <?php if ($chamadoFechado): ?>
                    <div class="bg-gray-200 p-10 rounded-lg shadow text-center border-2 border-gray-300">
                        <h2 class="text-xl font-bold text-gray-600">Chamado Encerrado</h2>
                        <p class="text-gray-500">Este chamado foi <?php echo $statusAtual; ?> e não pode mais ser editado.</p>
                        <div class="bg-white mt-4 p-4 text-left h-48 overflow-y-auto rounded text-sm text-gray-600 font-mono">
                            <?php echo nl2br(htmlspecialchars($chamado['descricao_detalhada'])); ?>
                        </div>
                    </div>

                <?php elseif (!$temResponsavel): ?>
                    <div class="bg-white p-10 rounded-lg shadow text-center border-2 border-dashed border-gray-300">
                        <h2 class="text-xl font-bold text-gray-700 mb-2">Este chamado está livre</h2>
                        <p class="text-gray-500 mb-6">Assuma a responsabilidade para trabalhar nele.</p>
                        <form method="POST">
                            <input type="hidden" name="acao" value="assumir">
                            <button type="submit" class="bg-blue-600 text-white text-lg font-bold py-3 px-8 rounded shadow hover:bg-blue-700">✋ Assumir Responsabilidade</button>
                        </form>
                    </div>

                <?php elseif (!$souResponsavel): ?>
                    <div class="bg-yellow-50 p-6 rounded-lg shadow text-center"><p class="text-yellow-700">Modo Observador: <strong><?php echo $chamado['tecnico_nome']; ?></strong> é o responsável.</p></div>

                <?php else: ?>
                    <div class="bg-white rounded-lg shadow overflow-hidden">
                        <div class="bg-gray-50 px-6 py-4 border-b border-gray-200"><h3 class="text-lg font-bold text-gray-800">Painel de Execução</h3></div>
                        
                        <form method="POST" class="p-6 space-y-6">
                            <input type="hidden" name="acao" value="atualizar_chamado">

                            <div>
                                <label class="block text-sm font-bold text-gray-700 mb-2">Histórico (Log do Sistema e Técnicos)</label>
                                <div class="bg-gray-100 p-4 h-48 overflow-y-auto rounded text-sm text-gray-800 border font-mono">
                                    <?php echo $chamado['descricao_detalhada'] ? nl2br(htmlspecialchars($chamado['descricao_detalhada'])) : 'Nenhum registro.'; ?>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-bold text-gray-700 mb-1">Mudar Status Geral</label>
                                    <select name="status_geral" class="w-full border p-2 rounded focus:ring-2 focus:ring-blue-500">
                                        <option value="em_andamento" <?php echo $statusAtual=='em_andamento'?'selected':''; ?>>🔧 Em Andamento</option>
                                        <option value="aguardando_pecas" <?php echo $statusAtual=='aguardando_pecas'?'selected':''; ?>>🧱 Aguardando Peças</option>
                                        <option value="concluido" <?php echo $statusAtual=='concluido'?'selected':''; ?>>✅ Concluído</option>
                                        <option value="cancelado" <?php echo $statusAtual=='cancelado'?'selected':''; ?>>🚫 Cancelado</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-sm font-bold text-gray-700 mb-1">Novo Relatório / Obs</label>
                                    <textarea name="relatorio" rows="1" class="w-full border p-2 rounded" placeholder="Observação técnica..."></textarea>
                                </div>
                            </div>

                            <div class="bg-orange-50 border border-orange-200 p-4 rounded">
                                <h4 class="text-sm font-bold text-orange-800 mb-2">+ Solicitar Nova Peça</h4>
                                <div class="flex gap-2">
                                    <input type="text" name="peca_nome" placeholder="Nome da peça" class="flex-1 border p-2 rounded text-sm">
                                    <input type="number" name="peca_qtd" value="1" class="w-20 border p-2 rounded text-sm">
                                </div>
                            </div>

                            <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 rounded shadow transition">💾 Salvar Atualizações</button>
                        </form>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>