<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

ini_set('session.cookie_path', '/');
if (!session_id()) session_start();

// Verifica se está logado
if (!isset($_SESSION['logado']) || $_SESSION['logado'] !== true) {
    header('Location: index.php');
    exit;
}

require_once 'conexao.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_user   = $_SESSION['usuario_id'];
    
    // Sanitiza as entradas
    $novo_nome = filter_input(INPUT_POST, 'editar_nome', FILTER_SANITIZE_SPECIAL_CHARS);
    $novo_nome = trim($novo_nome);
    
    $nova_matr = filter_input(INPUT_POST, 'editar_matricula', FILTER_SANITIZE_SPECIAL_CHARS);
    $nova_matr = trim($nova_matr);

    // Captura o nome do curso selecionado no <select> (que vem como texto)
    $nome_curso_selecionado = filter_input(INPUT_POST, 'editar_curso', FILTER_SANITIZE_SPECIAL_CHARS);
    $nome_curso_selecionado = trim($nome_curso_selecionado);

    // Validação simples
    if (empty($novo_nome)) {
        echo "<script>alert('❌ O nome não pode ficar vazio.'); window.history.back();</script>";
        exit;
    }

    try {
        $curso_id_final = NULL;

        // 🔍 Se o usuário escolheu algum curso válido, descobrimos o ID correspondente na tabela 'cursos'
        if (!empty($nome_curso_selecionado)) {
            $stmt_busca_curso = $pdo->prepare("SELECT id FROM cursos WHERE nome = :nome_curso LIMIT 1");
            $stmt_busca_curso->execute([':nome_curso' => $nome_curso_selecionado]);
            $curso_encontrado = $stmt_busca_curso->fetch(PDO::FETCH_ASSOC);

            if ($curso_encontrado) {
                $curso_id_final = $curso_encontrado['id'];
            }
        }

        // 🛠️ ATUALIZADO: Altera a coluna para 'curso_id' salvando a chave estrangeira correta
        $sql = "UPDATE usuarios SET nome = :nome, matricula = :matricula, curso_id = :curso_id WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':nome'      => $novo_nome,
            ':matricula' => !empty($nova_matr) ? $nova_matr : NULL,
            ':curso_id'  => $curso_id_final, // Salva o ID do curso mapeado ou NULL se não informado
            ':id'        => $id_user
        ]);

        // 🔥 CRÍTICO: Atualiza as variáveis de sessão para refletirem na tela imediatamente!
        $_SESSION['usuario_nome'] = $novo_nome;

        echo "<script>
                alert('✅ Perfil atualizado com sucesso!');
                window.location.href = 'painel_integrante.php';
              </script>";
        exit;

    } catch (PDOException $e) {
        die("Erro ao atualizar o perfil: " . $e->getMessage());
    }
} else {
    header('Location: painel_integrante.php');
    exit;
}
?>