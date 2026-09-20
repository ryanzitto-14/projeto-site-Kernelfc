<?php
// Força o cookie da sessão a valer para o site inteiro, evitando sumir no redirecionamento
ini_set('session.cookie_path', '/');
if (!session_id()) session_start();
require_once 'conexao.php';

// =======================================================================
// 🌐 MÉTODO 1: LOGIN VIA BOTÃO DO GOOGLE (Versão Blindada com cURL)
// =======================================================================
if (isset($_POST['google_jwt'])) {
    $jwt = $_POST['google_jwt'];
    $url = "https://oauth2.googleapis.com/tokeninfo?id_token=" . urlencode($jwt);
    
    // Requisição via cURL (totalmente aceita no InfinityFree)
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    $resposta = curl_exec($ch);
    curl_close($ch);
    
    if ($resposta !== false) {
        $payload = json_decode($resposta, true);
        
        if (isset($payload['error']) || !isset($payload['email'])) {
            echo "<script>alert('Erro: Token do Google inválido ou expirado.'); window.location.href='login.php';</script>";
            exit;
        }

        // Força o e-mail vindo do Google a ser estritamente minúsculo e limpo
        $email_google = strtolower(trim($payload['email']));
        $google_validou = $payload['email_verified'];

        if (!$google_validou || $google_validou === "false") {
            echo "<script>alert('Erro: Esta conta Google não possui um e-mail verificado.'); window.location.href='login.php';</script>";
            exit;
        }

        // Busca no banco ignorando case-sensitive com LOWER()
        $stmt = $pdo->prepare("SELECT id, nome, email, nivel_acesso FROM usuarios WHERE LOWER(email) = LOWER(:email)");
        $stmt->execute(['email' => $email_google]);
        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($usuario) {
            // Caso o usuário exista, atualiza a verificação e loga
            $update = $pdo->prepare("UPDATE usuarios SET email_verificado = 1 WHERE id = :id");
            $update->execute(['id' => $usuario['id']]);

            $_SESSION['logado'] = true; 
            $_SESSION['usuario_id'] = $usuario['id'];
            $_SESSION['usuario_nome'] = $usuario['nome'];
            
            // Definição direta e limpa do nível de acesso
            $_SESSION['usuario_nivel'] = !empty($usuario['nivel_acesso']) ? $usuario['nivel_acesso'] : 'integrante';
            
            session_write_close(); 
            // 🔥 CORRIGIDO: Agora redireciona para a index.php assim como o login tradicional
            echo "<script>window.location.href='index.php';</script>";
            exit;
        } else {
            // Caso não exista, salva na sessão para o auto-preenchimento e manda para o cadastro
            $_SESSION['google_email_cadastro'] = $email_google;
            session_write_close();

            echo "<script>
                    alert('Conta Google validada com sucesso! Como você ainda não tem cadastro na Kernel, vamos te redirecionar para preencher seus dados.');
                    window.location.href='cadastro_socio.php';
                  </script>";
            exit;
        }
    } else {
        echo "<script>alert('Erro de comunicação com o Google. Tente novamente.'); window.location.href='login.php';</script>";
        exit;
    }
}

// =======================================================================
// 🔑 MÉTODO 2: LOGIN TRADICIONAL (E-mail + Senha)
// =======================================================================
if (isset($_POST['email']) && isset($_POST['senha'])) {
    $email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
    $senha = $_POST['senha'];

    if (!$email) {
        echo "<script>alert('Formato de e-mail inválido.'); window.location.href='login.php';</script>";
        exit;
    }

    // Busca tradicional também protegida por LOWER() trazendo a coluna correta
    $stmt = $pdo->prepare("SELECT id, nome, senha, nivel_acesso FROM usuarios WHERE LOWER(email) = LOWER(:email)");
    $stmt->execute(['email' => trim($email)]);
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($usuario && password_verify($senha, $usuario['senha'])) {
        // Grava as sessões necessárias
        $_SESSION['logado'] = true;
        $_SESSION['usuario_id'] = $usuario['id'];
        $_SESSION['usuario_nome'] = $usuario['nome'];
        
        // Definição direta e limpa do nível de acesso mapeado com fallback de segurança
        $_SESSION['usuario_nivel'] = !empty($usuario['nivel_acesso']) ? $usuario['nivel_acesso'] : 'integrante';
        
        session_write_close();

        echo "<script>window.location.href='index.php';</script>";
        exit;
    } else {
        echo "<script>
                alert('Erro: E-mail ou chave de acesso incorretos.');
                window.location.href = 'login.php';
              </script>";
        exit;
    }
}

// Redirecionamento de segurança caso acessem o arquivo de forma direta
header('Location: login.php');
exit;
?>