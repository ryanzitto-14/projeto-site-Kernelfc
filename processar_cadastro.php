<?php
if (!session_id()) session_start();

require_once 'conexao.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Recebe e sanitiza os dados enviados pelo formulário
    $nome      = filter_input(INPUT_POST, 'nome', FILTER_SANITIZE_SPECIAL_CHARS);
    $matricula = trim(filter_input(INPUT_POST, 'matricula', FILTER_SANITIZE_SPECIAL_CHARS));
    $email     = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
    $celular   = filter_input(INPUT_POST, 'celular', FILTER_SANITIZE_SPECIAL_CHARS);
    
    // Captura o curso_id enviado pelo formulário
    $curso_raw = $_POST['curso_id']; 
    $senha_pira  = $_POST['senha'];

    // 🚨 Validação Amigável de E-mail
    if (!$email) {
        echo "<script>
                alert('Erro: O formato do e-mail digitado é inválido.');
                window.location.href = 'cadastro_socio.php';
              </script>";
        exit;
    }

    // 🚨 Validação Amigável de Senha
    if (strlen($senha_pira) < 6) {
        echo "<script>
                alert('Erro: A senha precisa ter no mínimo 6 caracteres.');
                window.location.href = 'cadastro_socio.php';
              </script>";
        exit;
    }

    // Cria o hash seguro para a senha
    $senha_cripto = password_hash($senha_pira, PASSWORD_DEFAULT);

    try {
        // 🔒 CAMADA DE SEGURANÇA: Verifica se a matrícula ou e-mail já existem no sistema
        // Nota: A validação só checa matrícula se ela tiver sido preenchida (membros com curso)
        if (!empty($matricula)) {
            $sql_verificar = "SELECT id FROM usuarios WHERE matricula = :matricula OR email = :email";
            $stmt_verificar = $pdo->prepare($sql_verificar);
            $stmt_verificar->execute([
                ':matricula' => $matricula,
                ':email'     => $email
            ]);
        } else {
            $sql_verificar = "SELECT id FROM usuarios WHERE email = :email";
            $stmt_verificar = $pdo->prepare($sql_verificar);
            $stmt_verificar->execute([':email' => $email]);
        }

        if ($stmt_verificar->rowCount() > 0) {
            echo "<script>
                    alert('Erro: Matrícula ou E-mail já cadastrados na Kernel!');
                    window.location.href = 'cadastro_socio.php';
                  </script>";
            exit;
        }

        // Lógica de tratamento para a nova estrutura de curso_id relacional
        $curso_id = null;
        if (!empty($curso_raw) && $curso_raw !== 'Sem Curso') {
            $curso_id = intval($curso_raw);
        }

        // Se o usuário selecionou 'Sem Curso', tentamos encontrar o registro 'Sem Curso' na tabela de cursos para pegar seu ID original
        if ($curso_raw === 'Sem Curso') {
            $stmt_busca_sem_curso = $pdo->prepare("SELECT id FROM cursos WHERE nome = 'Sem Curso' LIMIT 1");
            $stmt_busca_sem_curso->execute();
            $res_sem_curso = $stmt_busca_sem_curso->fetch(PDO::FETCH_ASSOC);
            if ($res_sem_curso) {
                $curso_id = intval($res_sem_curso['id']);
            }
        }

        // 🛡️ ATUALIZADO: Substituído a coluna antiga 'curso' pela nova chave estrangeira 'curso_id'
        $sql_inserir = "INSERT INTO usuarios (nome, matricula, email, senha, celular, curso_id, email_verificado, nivel_acesso) 
                        VALUES (:nome, :matricula, :email, :senha, :celular, :curso_id, 0, 'integrante')";
        
        $stmt_inserir = $pdo->prepare($sql_inserir);
        
        // Vinculação explícita com bindValue para lidar corretamente com inteiros ou nulos
        $stmt_inserir->bindValue(':nome', $nome);
        $stmt_inserir->bindValue(':matricula', !empty($matricula) ? $matricula : null, !empty($matricula) ? PDO::PARAM_STR : PDO::PARAM_NULL);
        $stmt_inserir->bindValue(':email', $email);
        $stmt_inserir->bindValue(':senha', $senha_cripto);
        $stmt_inserir->bindValue(':celular', $celular);
        $stmt_inserir->bindValue(':curso_id', $curso_id, $curso_id === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
        
        $sucesso = $stmt_inserir->execute();

        if ($sucesso) {
            // 🚀 Limpa o e-mail do Google da sessão para os próximos cadastros
            if (isset($_SESSION['google_email_cadastro'])) {
                unset($_SESSION['google_email_cadastro']);
            }

            echo "<script>
                    alert('Cadastro realizado com sucesso! Para sua segurança, faça seu primeiro acesso utilizando o botão do Google para autenticar seu e-mail.');
                    window.location.href = 'login.php';
                  </script>";
            exit;
        }

    } catch (PDOException $e) {
        echo "<script>
                alert('Erro crítico no banco de dados. Entre em contato com o suporte.');
                window.location.href = 'cadastro_socio.php';
              </script>";
        exit;
    }

} else {
    header('Location: cadastro_socio.php');
    exit;
}
?>