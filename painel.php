<?php
// painel.php
session_start();
if (!isset($_SESSION['id_usuario'])) {
    header("Location: index.php");
    exit;
}
$nome = $_SESSION['nome'];
$perfil = $_SESSION['tipo_perfil'];
$especialidade = $_SESSION['especialidade'];
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel - Manutenção</title>
    <script src="https://cdn.tailwindcss.com"></script>
    </head>
<body class="bg-gray-50">

    <nav class="bg-white shadow-sm border-b border-gray-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <span class="text-xl font-bold text-gray-800">Manutenção<span class="text-blue-600">Sys</span></span>
                </div>
                <div class="flex items-center space-x-4">
                    <div class="text-right hidden sm:block">
                        <p class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($nome); ?></p>
                        <p class="text-xs text-gray-500 capitalize"><?php echo $perfil; ?></p>
                    </div>
                    <a href="logout.php" class="text-red-600 hover:text-red-800 text-sm font-medium border border-red-200 px-3 py-1 rounded hover:bg-red-50 transition">
                        Sair
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <main class="max-w-7xl mx-auto py-10 px-4 sm:px-6 lg:px-8">
        
        <div class="mb-8">
            <h2 class="text-2xl font-bold text-gray-900">Painel de Controle</h2>
            <p class="text-gray-500">Selecione uma opção abaixo para prosseguir.</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

            <?php if ($perfil == 'solicitante' || $perfil == 'admin'): ?>
                <div class="bg-white overflow-hidden shadow rounded-xl border border-gray-100">
                    <div class="px-4 py-5 sm:p-6">
                        <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4 flex items-center">
                            🏭 Área do Solicitante
                        </h3>
                        <div class="space-y-4">
                            <a href="chamados/abrir_chamado.php" class="group relative w-full flex justify-center py-4 px-4 border border-transparent text-lg font-medium rounded-lg text-white bg-green-600 hover:bg-green-700 focus:outline-none shadow-md transition transform hover:-translate-y-1">
                                <span class="absolute left-0 inset-y-0 flex items-center pl-3">
                                    <svg class="h-6 w-6 text-green-200 group-hover:text-green-100" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                    </svg>
                                </span>
                                Abrir Novo Chamado
                            </a>

                            <a href="meus_chamados.php" class="w-full flex justify-center items-center py-3 px-4 border border-gray-300 text-sm font-medium rounded-lg text-gray-700 bg-white hover:bg-gray-50 transition">
                                Ver Meus Chamados Abertos
                            </a>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ($perfil == 'tecnico' || $perfil == 'admin'): ?>
                <div class="bg-white overflow-hidden shadow rounded-xl border border-blue-100">
                    <div class="px-4 py-5 sm:p-6">
                        <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4 flex items-center">
                            🛠️ Área Técnica
                            <?php if($especialidade != 'todas'): ?>
                                <span class="ml-2 px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800 capitalize">
                                    <?php echo $especialidade; ?>
                                </span>
                            <?php endif; ?>
                        </h3>
                        
                        <div class="space-y-4">
                            <a href="chamados/lista_chamados.php" class="w-full flex justify-center items-center py-4 px-4 border border-transparent text-lg font-medium rounded-lg text-white bg-blue-600 hover:bg-blue-700 shadow-md transition">
                                Visualizar Lista de Tarefas
                            </a>
                            
                            <div class="grid grid-cols-2 gap-4">
                                <div class="bg-gray-50 p-3 rounded-lg text-center border border-gray-200">
                                    <span class="block text-2xl font-bold text-gray-700">0</span>
                                    <span class="text-xs text-gray-500">Pendentes</span>
                                </div>
                                <div class="bg-gray-50 p-3 rounded-lg text-center border border-gray-200">
                                    <span class="block text-2xl font-bold text-green-600">0</span>
                                    <span class="text-xs text-gray-500">Concluídos Hoje</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

        </div>
    </main>

</body>
</html>