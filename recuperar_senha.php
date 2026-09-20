<?php
if (!session_id()) session_start();
require_once 'conexao.php';

$mensagem = "";
$status_msg = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['solicitar_reset'])) {
    $email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);

    if ($email) {
        // Verifica se o e-mail existe no sistema
        $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE email = :email");
        $stmt->execute([':email' => $email]);
        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($usuario) {
            // Gera um token criptográfico seguro de 32 bytes (64 caracteres hexadecimais)
            $token = bin2hex(random_bytes(32));
            
            // Define o tempo de expiração (1 hora a partir de agora)
            $expiracao = date('Y-m-d H:i:s', strtotime('+1 hour'));

            // Salva o token e a expiração no registro do usuário
            $stmt_update = $pdo->prepare("UPDATE usuarios SET reset_token = :token, reset_expiracao = :expiracao WHERE id = :id");
            $stmt_update->execute([
                ':token' => $token,
                ':expiracao' => $expiracao,
                ':id' => $usuario['id']
            ]);

            // Monta o link que seria enviado por e-mail
            $link_recuperacao = "http://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . "/redefinir_senha.php?token=" . $token;

            // MENSAGEM SIMULADA (Para testes locais rápidos. No futuro, você pode integrar o PHPMailer aqui)
            $mensagem = "CHAVE_GERADA: Link de recuperação criado com sucesso!<br><a href='$link_recuperacao' class='text-decoration-none text-info fw-bold' target='_blank'>&gt;_ CLIQUE_AQUI_PARA_REDEFINIR</a>";
            $status_msg = "success";

        } else {
            // Por segurança contra engenharia reversa de e-mails, dizemos que enviamos mesmo se não existir, ou avisamos explicitamente se preferir:
            $mensagem = "AVISO: Se o e-mail digitado estiver no banco de dados, as instruções de recuperação foram geradas.";
            $status_msg = "warning";
        }
    } else {
        $mensagem = "ERRO: Digite um formato de e-mail válido.";
        $status_msg = "danger";
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recuperar Acesso // Kernel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Fira+Code:wght@400;600&family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="icon" type="image/png" href="favicon.png">
    <style>
        :root {
            --tech-green: #00FF66;
            --dark-bg: #0D0D0D;
            --dark-card: #1A1A1A;
            --white-text: #F5F5F5;
        }
        body { background-color: var(--dark-bg); color: var(--white-text); font-family: 'Poppins', sans-serif; }
        .code-font { font-family: 'Fira Code', monospace; }
        .form-kernel { background-color: var(--dark-card); border: 1px solid #222; border-radius: 12px; }
        .input-kernel { background-color: #0D0D0D !important; border: 1px solid #333 !important; color: var(--white-text) !important; }
        .input-kernel:focus { border-color: var(--tech-green) !important; box-shadow: 0 0 10px rgba(0, 255, 102, 0.2) !important; }
        .btn-kernel { background: transparent; color: var(--tech-green); border: 2px solid var(--tech-green); font-weight: 600; }
        .btn-kernel:hover { background: var(--tech-green); color: var(--dark-bg); }
    </style>
</head>
<body class="d-flex align-items-center min-vh-100">

    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-5">
                
                <div class="text-center mb-4">
                    <h3 class="fw-bold code-font text-white">auth_recovery();</h3>
                    <p class="text-secondary small">Insira seu e-mail para descriptografar e redefinir suas credenciais.</p>
                </div>

                <?php if(!empty($mensagem)): ?>
                    <div class="alert alert-<?php echo $status_msg; ?> code-font small mb-4" role="alert">
                        <?php echo $mensagem; ?>
                    </div>
                <?php endif; ?>

                <div class="form-kernel p-4 shadow">
                    <form action="recuperar_senha.php" method="POST">
                        <div class="mb-3">
                            <label class="form-label text-secondary code-font small">user_email_address</label>
                            <input type="email" name="email" class="form-control input-kernel code-font small" placeholder="exemplo@email.com" required>
                        </div>
                        <button type="submit" name="solicitar_reset" class="btn btn-kernel code-font w-100 mt-2">injetar_requisicao_reset();</button>
                    </form>
                    
                    <div class="text-center mt-3">
                        <a href="login.php" class="text-secondary small code-font text-decoration-none">&lt; voltar_ao_login();</a>
                    </div>
                </div>

            </div>
        </div>
    </div>

</body>
</html>