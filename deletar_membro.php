<?php
if (!session_id()) session_start();

// Proteção para garantir que apenas a diretoria logada execute a deleção
if (!isset($_SESSION['logado']) || $_SESSION['logado'] !== true || $_SESSION['usuario_nivel'] !== 'diretoria') {
    header('Location: login.php');
    exit;
}

if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    require_once 'conexao.php';
    $usuario_id = $_GET['id'];

    try {
        // Inicia transação para garantir consistência em caso de chaves estrangeiras vinculadas
        $pdo->beginTransaction();

        // 1. Opcional: Remove primeiro as associações de modalidades deste usuário se houver restrição RESTRICT no banco
        $stmt_modalidades = $pdo->prepare("DELETE FROM membros_modalidades WHERE usuario_id = :id");
        $stmt_modalidades->execute([':id' => $usuario_id]);

        // 2. Deleta o usuário principal
        $stmt_usuario = $pdo->prepare("DELETE FROM usuarios WHERE id = :id");
        $stmt_usuario->execute([':id' => $usuario_id]);

        $pdo->commit();
        
        // Redireciona de volta para a dashboard atualizada
        header('Location: dashboard_admin.php?msg=sucesso_deletar');
        exit;

    } catch (PDOException $e) {
        $pdo->rollBack();
        die("Erro crítico ao tentar remover o integrante do banco: " . $e->getMessage());
    }
} else {
    header('Location: dashboard_admin.php');
    exit;
}