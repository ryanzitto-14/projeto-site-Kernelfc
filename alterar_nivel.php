<?php
ini_set('session.cookie_path', '/');
if (!session_id()) session_start();

require_once 'conexao.php';

// Proteção de segurança: só a diretoria logada pode executar esse script
if (!isset($_SESSION['logado']) || $_SESSION['logado'] !== true || $_SESSION['usuario_nivel'] !== 'diretoria') {
    header('Location: login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario_id = filter_input(INPUT_POST, 'usuario_id', FILTER_VALIDATE_INT);
    $novo_nivel = trim($_POST['novo_nivel']);

    // Níveis válidos da coluna nivel_acesso
    $niveis_permitidos = ['integrante', 'atleta', 'diretoria'];

    if ($usuario_id && in_array($novo_nivel, $niveis_permitidos)) {
        try {
            // Alterado de tipo_membro para nivel_acesso!
            $stmt = $pdo->prepare("UPDATE usuarios SET nivel_acesso = :nivel WHERE id = :id");
            $stmt->execute([
                ':nivel' => $novo_nivel,
                ':id' => $usuario_id
            ]);

            echo "<script>
                    alert('Nível de acesso do usuário atualizado com sucesso!');
                    window.location.href = 'dashboard_admin.php';
                  </script>";
            exit;
        } catch (PDOException $e) {
            echo "<script>
                    alert('Erro no banco de dados ao atualizar o nível de acesso.');
                    window.location.href = 'dashboard_admin.php';
                  </script>";
            exit;
        }
    }
}

header('Location: dashboard_admin.php');
exit;