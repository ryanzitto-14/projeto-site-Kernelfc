<?php
// 1. FORÇA EXIBIÇÃO DE ERROS
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

ini_set('session.cookie_path', '/');
if (!session_id()) session_start();

// Verifica se está logado de forma geral
if (!isset($_SESSION['logado']) || $_SESSION['logado'] !== true) {
    header('Location: login.php');
    exit;
}

// 🔒 TRAVA DE SEGURANÇA BACKEND: Impede que usuários nível 'integrante' processem o formulário
if (!isset($_SESSION['usuario_nivel']) || !in_array($_SESSION['usuario_nivel'], ['atleta', 'diretoria'])) {
    echo "<script>
            alert('Erro de Permissão: Seu nível de acesso não permite personalizar um manto.');
            window.location.href = 'painel_integrante.php';
          </script>";
    exit;
}

require_once 'conexao.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_user        = $_SESSION['usuario_id'];
    $tipo_membro    = filter_input(INPUT_POST, 'tipo_membro', FILTER_SANITIZE_SPECIAL_CHARS);
    $modelo_camisa  = filter_input(INPUT_POST, 'modelo_camisa', FILTER_SANITIZE_SPECIAL_CHARS);
    
    // 👕 ADICIONADO: Recebe e sanitiza o tamanho do manto
    $camisa_tamanho = filter_input(INPUT_POST, 'camisa_tamanho', FILTER_SANITIZE_SPECIAL_CHARS);
    if ($camisa_tamanho) {
        $camisa_tamanho = strtoupper(trim($camisa_tamanho));
    }
    
    $post_nome      = $_POST['camisa_nome'] ?? '';
    $camisa_nome    = strtoupper(trim(filter_var($post_nome, FILTER_SANITIZE_SPECIAL_CHARS)));
    
    $camisa_num     = filter_input(INPUT_POST, 'camisa_numero', FILTER_VALIDATE_INT);
    $personalizar_check = isset($_POST['personalizar_check']) ? true : false;

    // 👕 ADICIONADO: Validação básica do tamanho (impede valores estranhos se o usuário burlar o HTML)
    if ($tipo_membro !== 'nao_definido' && !in_array($camisa_tamanho, ['P', 'M', 'G', 'GG'])) {
        echo "<script>alert('Por favor, selecione um tamanho válido (P, M, G ou GG).'); window.history.back();</script>";
        exit;
    }

    // Se for apoiador e optou por NÃO personalizar, zeramos os campos de nome e número
    if ($tipo_membro === 'apoiador' && !$personalizar_check) {
        $camisa_nome = NULL;
        $camisa_num  = NULL;
    } else {
        // Validação para os casos que EXIGEM personalização (Atletas ou Apoiadores que ativaram o switch)
        if ($tipo_membro === 'nao_definido' || !$camisa_num || $camisa_num < 1 || $camisa_num > 99 || empty($camisa_nome)) {
            echo "<script>alert('Dados inválidos ou incompletos.'); window.history.back();</script>";
            exit;
        }
    }

    try {
        // Validação de número único APENAS entre quem é atleta de fato
        if ($tipo_membro === 'atleta') {
            $sql_checa = "SELECT id FROM usuarios WHERE tipo_membro = 'atleta' AND camisa_numero = :num AND id != :id_atual";
            $stmt_checa = $pdo->prepare($sql_checa);
            $stmt_checa->execute([':num' => $camisa_num, ':id_atual' => $id_user]);

            if ($stmt_checa->rowCount() > 0) {
                echo "<script>
                        alert('❌ O número $camisa_num já está reservado por outro atleta! Escolha outro.');
                        window.history.back();
                      </script>";
                exit;
            }
        }

        // =======================================================================
        // LÓGICA DE NÍVEL ATUALIZADA: 
        // Não altera o 'nivel_acesso' no banco e nem na $_SESSION, pois o 
        // usuário já possui o nível correto para estar aqui.
        // =======================================================================
        
        // 👕 ATUALIZADO: Adicionado 'camisa_tamanho = :tamanho' na Query SQL
        $sql_update = "UPDATE usuarios SET 
                        tipo_membro = :tipo, 
                        modelo_camisa = :modelo, 
                        camisa_nome = :nome, 
                        camisa_numero = :num,
                        camisa_tamanho = :tamanho
                       WHERE id = :id";
                       
        $stmt_update = $pdo->prepare($sql_update);
        $stmt_update->execute([
            ':tipo'   => $tipo_membro,
            ':modelo' => $modelo_camisa,
            ':nome'   => $camisa_nome,
            ':num'    => $camisa_num,
            ':tamanho'=> $camisa_tamanho, // 👕 Vincula o parâmetro do tamanho
            ':id'     => $id_user
        ]);

        echo "<script>
                alert('👕 Seu manto personalizado foi salvo com sucesso!');
                window.location.href = 'painel_integrante.php';
              </script>";
        exit;

    } catch (PDOException $e) {
        die("Erro ao salvar dados do manto no banco: " . $e->getMessage());
    }
} else {
    header('Location: painel_integrante.php');
    exit;
}
?>