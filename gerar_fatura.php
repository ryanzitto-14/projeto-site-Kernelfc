<?php
ini_set('session.cookie_path', '/');
if (!session_id()) session_start();

// 🛡️ Trava de segurança básica
if (!isset($_SESSION['logado']) || $_SESSION['logado'] !== true) {
    header("Location: index.php");
    exit;
}

require_once 'conexao.php';

// Pega os dados enviados por quem clicou no botão
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['nome_plano']) && isset($_POST['valor_plano']) && isset($_POST['link_pagamento'])) {
    
    $usuario_id = $_SESSION['usuario_id'];
    $nome_plano = trim($_POST['nome_plano']);
    $valor = floatval(str_replace(['.', ','], ['', '.'], $_POST['valor_plano'])); // Converte o valor limpo para decimal
    $link_externo = trim($_POST['link_pagamento']);
    $mes_referencia = date('Y-m'); // Define o mês atual automaticamente (Ex: 2026-06)

    try {
        // 🔍 Verifica se já não existe uma fatura gerada para esse mesmo atleta no mês atual para evitar duplicados ao clicar várias vezes
        $stmt_check = $pdo->prepare("SELECT id FROM mensalidades WHERE usuario_id = :uid AND mes_referencia = :mes AND status_pagamento = 'pendente'");
        $stmt_check->execute([':uid' => $usuario_id, ':mes' => $mes_referencia]);
        
        if ($stmt_check->rowCount() == 0) {
            // Se não existir, insere como PENDENTE para o controle da diretoria
            $stmt = $pdo->prepare("INSERT INTO mensalidades (usuario_id, mes_referencia, valor, status_pagamento, data_pago) 
                                   VALUES (:usuario_id, :mes_referencia, :valor, 'pendente', NULL)");
            $stmt->execute([
                ':usuario_id' => $usuario_id,
                ':mes_referencia' => $mes_referencia,
                ':valor' => $valor
            ]);
        }
    } catch (PDOException $e) {
        // Silencia erros de banco para não quebrar o redirecionamento
    }

    // 🚀 Redireciona o atleta imediatamente para o link externo (Google ou Mercado Pago)
    header("Location: " . $link_externo);
    exit;
} else {
    header("Location: pagamentos.php");
    exit;
}