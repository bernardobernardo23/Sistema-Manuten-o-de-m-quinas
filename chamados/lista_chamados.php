<?php
// chamados/lista_chamados.php
session_start();
require '../db.php';

if (!isset($_SESSION['id_usuario']) || $_SESSION['tipo_perfil'] == 'solicitante') {
    header("Location: ../painel.php");
    exit;
}

$especialidade_user = $_SESSION['especialidade']; // 'eletrica', 'pneumatica', 'todas'
$is_admin = ($especialidade_user == 'todas');

// --- CAPTURA DE FILTROS DA URL ---
$filtro_status = $_GET['status'] ?? 'pendentes'; // Padrão: mostrar ativos
$filtro_ambito = $_GET['ambito'] ?? '';

// --- CONSTRUÇÃO DA QUERY ---
$sql = "SELECT c.*, u.nome as nome_solicitante 
        FROM chamados c
        JOIN usuarios u ON c.id_solicitante = u.id_usuario
        WHERE 1=1"; // Truque para facilitar adicionar ANDs

$params = [];

// 1. APLICA FILTRO DE STATUS
if ($filtro_status == 'pendentes') {
    $sql .= " AND c.status NOT IN ('concluido', 'cancelado')";
} elseif ($filtro_status != 'todos') {
    $sql .= " AND c.status = :status_f";
    $params[':status_f'] = $filtro_status;
}

// 2. APLICA FILTRO DE ÂMBITO
if ($is_admin) {
    // Se for admin, usa o filtro da tela (se houver)
    if (!empty($filtro_ambito)) {
        $sql .= " AND c.ambito = :ambito_f";
        $params[':ambito_f'] = $filtro_ambito;
    }
} else {
    // Se for técnico, FORÇA a especialidade dele
    $sql .= " AND c.ambito = :ambito_user";
    $params[':ambito_user'] = $especialidade_user;
}

// Ordenação
$sql .= " ORDER BY FIELD(c.prioridade, 'alta', 'media', 'baixa'), c.data_abertura ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$chamados = $stmt->fetchAll(PDO::FETCH_ASSOC);

function getCorPrioridade($p) {
    return match($p) {
        'alta' => 'bg-red-100 text-red-800',
        'media' => 'bg-yellow-100 text-yellow-800',
        'baixa' => 'bg-green-100 text-green-800',
        default => 'bg-gray-100 text-gray-800'
    };
}

function getIconeAmbito($a) {
    return match($a) {
        'eletrica' => '⚡ Elétrica',
        'pneumatica' => '💨 Pneumática',
        'geral' => '⚙️ Geral',
        default => $a
    };
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fila de Chamados</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50">

    <div class="max-w-7xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
        
        <div class="bg-white p-4 rounded-lg shadow mb-8 border border-gray-200">
            <div class="flex flex-col md:flex-row justify-between items-center gap-4">
                
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Fila de Atendimento</h1>
                    <p class="text-sm text-gray-500">
                        Especialidade Logada: <span class="font-bold text-blue-600 uppercase"><?php echo $especialidade_user; ?></span>
                    </p>
                </div>

                <form method="GET" class="flex flex-wrap gap-2 items-center">
                    
                    <select name="status" class="border border-gray-300 rounded-md py-2 px-3 text-sm focus:ring-blue-500" onchange="this.form.submit()">
                        <option value="pendentes" <?php echo $filtro_status=='pendentes'?'selected':''; ?>>🔥 Pendentes (Ativos)</option>
                        <option value="aberto" <?php echo $filtro_status=='aberto'?'selected':''; ?>>🆕 Abertos</option>
                        <option value="aguardando_pecas" <?php echo $filtro_status=='aguardando_pecas'?'selected':''; ?>>🧱 Aguardando Peças</option>
                        <option value="concluido" <?php echo $filtro_status=='concluido'?'selected':''; ?>>✅ Concluídos</option>
                        <option value="cancelado" <?php echo $filtro_status=='cancelado'?'selected':''; ?>>🚫 Cancelados</option>
                        <option value="todos" <?php echo $filtro_status=='todos'?'selected':''; ?>>📂 Todos</option>
                    </select>

                    <?php if($is_admin): ?>
                        <select name="ambito" class="border border-gray-300 rounded-md py-2 px-3 text-sm focus:ring-blue-500" onchange="this.form.submit()">
                            <option value="">-- Todos os Setores --</option>
                            <option value="eletrica" <?php echo $filtro_ambito=='eletrica'?'selected':''; ?>>⚡ Elétrica</option>
                            <option value="pneumatica" <?php echo $filtro_ambito=='pneumatica'?'selected':''; ?>>💨 Pneumática</option>
                            <option value="geral" <?php echo $filtro_ambito=='geral'?'selected':''; ?>>⚙️ Geral</option>
                        </select>
                    <?php endif; ?>

                </form>

                <a href="../painel.php" class="text-gray-500 hover:text-gray-700 font-medium whitespace-nowrap">← Voltar Painel</a>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            
            <?php if(count($chamados) == 0): ?>
                <div class="col-span-3 text-center py-10 bg-white rounded-lg shadow">
                    <p class="text-xl text-gray-500">Nenhum chamado encontrado com estes filtros.</p>
                </div>
            <?php endif; ?>

            <?php foreach($chamados as $c): ?>
                <div class="bg-white rounded-lg shadow border border-gray-200 overflow-hidden hover:shadow-lg transition duration-200 flex flex-col <?php echo ($c['status']=='cancelado') ? 'opacity-60 bg-gray-50' : ''; ?>">
                    
                    <div class="p-4 border-b border-gray-100 flex justify-between items-start">
                        <div class="flex space-x-2">
                            <span class="px-2 py-1 text-xs font-bold uppercase rounded-full <?php echo getCorPrioridade($c['prioridade']); ?>">
                                <?php echo $c['prioridade']; ?>
                            </span>
                            <span class="px-2 py-1 text-xs font-bold uppercase rounded-full bg-blue-50 text-blue-700 border border-blue-100">
                                <?php echo getIconeAmbito($c['ambito']); ?>
                            </span>
                        </div>
                        
                        <?php if($c['status'] != 'aberto'): ?>
                            <span class="text-xs font-bold uppercase px-2 py-1 rounded 
                                <?php 
                                    echo match($c['status']) {
                                        'concluido' => 'bg-green-100 text-green-800',
                                        'cancelado' => 'bg-red-100 text-red-800',
                                        'aguardando_pecas' => 'bg-yellow-100 text-yellow-800',
                                        default => 'bg-gray-100'
                                    }; 
                                ?>">
                                <?php echo str_replace('_', ' ', $c['status']); ?>
                            </span>
                        <?php endif; ?>

                        <?php if($c['maquina_parada'] && $c['status'] != 'concluido' && $c['status'] != 'cancelado'): ?>
                            <span class="animate-pulse px-2 py-1 text-xs font-bold text-white bg-red-600 rounded shadow-sm">
                                🚨 PARADA
                            </span>
                        <?php endif; ?>
                    </div>

                    <div class="p-5 flex-grow">
                        <h3 class="text-xl font-bold text-gray-800 mb-1">
                            Máquina #<?php echo $c['n_maquina']; ?>
                        </h3>
                        <p class="text-sm text-gray-600 mb-3 font-medium">
                            "<?php echo htmlspecialchars($c['titulo_motivo']); ?>"
                        </p>
                        <p class="text-sm text-gray-500 line-clamp-3 italic bg-gray-50 p-3 rounded border border-gray-100">
                            <?php echo nl2br(htmlspecialchars($c['descricao_detalhada'])); ?>
                        </p>
                    </div>

                    <div class="px-5 py-3 bg-gray-50 border-t border-gray-100 flex justify-between items-center mt-auto">
                        <div class="text-xs text-gray-500">
                            <span class="block font-medium text-gray-700">👤 <?php echo htmlspecialchars($c['nome_solicitante']); ?></span>
                            <span class="block">📅 <?php echo date('d/m H:i', strtotime($c['data_abertura'])); ?></span>
                        </div>
                        
                        <a href="atender_chamado.php?id=<?php echo $c['id_chamado']; ?>" class="inline-flex items-center px-3 py-1.5 border border-transparent text-xs font-medium rounded text-white bg-blue-600 hover:bg-blue-700 shadow-sm transition">
                            <?php echo ($c['status']=='concluido' || $c['status']=='cancelado') ? 'Ver Detalhes' : 'Atender'; ?> →
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>

        </div>
    </div>
</body>
</html>