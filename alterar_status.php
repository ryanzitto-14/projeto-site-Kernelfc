<?php
if (!session_id()) session_start();

// 1. Trava de segurança: apenas administradores logados podem rodar esse script
if (!isset($_SESSION['logado']) || $_SESSION['logado'] !== true || $_SESSION['usuario_nivel'] !== 'diretoria') {
    header('Location: login.php');
    exit;
}

// 2. Importa a conexão com o banco
require_once 'conexao.php';

// 3. Pega os parâmetros da URL de forma segura
$id   = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$acao = filter_input(INPUT_GET, 'acao', FILTER_SANITIZE_SPECIAL_CHARS);

if ($id && $acao) {
    
    // Define o novo status baseado na ação clicada
    if ($acao === 'ativar') {
        $novo_status = 'ativo';
    } elseif ($acao === 'suspender') {
        $novo_status = 'pendente'; // Ou 'inativo', dependendo do seu ENUM do banco
    } else {
        header('Location: dashboard_admin.php');
        exit;
    }

    try {
        // 4. Executa a atualização no banco de dados
        $sql = "UPDATE usuarios SET status_socio = :status WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        $sucesso = $stmt->execute([
            ':status' => $novo_status,
            ':id'     => $id
        ]);

        if ($sucesso) {
            // Retorna ao painel administrativo imediatamente após atualizar
            header('Location: dashboard_admin.php');
            exit;
        }

    } catch (PDOException $e) {
        die("Erro ao alterar o status do integrante: " . $e->getMessage());
    }

} else {
    // Se os parâmetros estiverem errados ou ausentes, volta pro painel
    header('Location: dashboard_admin.php');
    exit;
}
?>