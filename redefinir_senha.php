<?php
if (!session_id()) session_start();
require_once 'conexao.php';

$mensagem = "";
$status_msg = "";
$token_valido = false;

// 1. VERIFICA SE O TOKEN EXISTE E É VÁLIDO
$token = $_GET['token'] ?? $_POST['token'] ?? null;

if ($token) {
    $agora = date('Y-m-d H:i:s');
    
    // Busca um usuário que tenha esse token e onde o token ainda não expirou
    $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE reset_token = :token AND reset_expiracao > :agora");
    $stmt->execute([':token' => $token, ':agora' => $agora]);
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($usuario) {
        $token_valido = true;
    } else {
        $mensagem = "ERRO_AUTH: Token inválido, corrompido ou expirado.";
        $status_msg = "danger";
    }
} else {
    $mensagem = "ACCESS_DENIED: Token de autenticação ausente.";
    $status_msg = "danger";
}

// 2. PROCESSA A REDEFINIÇÃO DA SENHA
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['alterar_senha']) && $token_valido) {
    $nova_senha = $_POST['nova_senha'];
    $confirmar_senha = $_POST['confirmar_senha'];

    if ($nova_senha === $confirmar_senha) {
        if (strlen($nova_senha) >= 6) { // Validação simples de tamanho
            
            // Cria o hash seguro da nova senha
            $senha_hash = password_hash($nova_senha, PASSWORD_DEFAULT);

            // Atualiza a senha e APAGA o token para que ele não possa ser usado de novo
            $stmt_update = $pdo->prepare("UPDATE usuarios SET senha = :senha, reset_token = NULL, reset_expiracao = NULL WHERE id = :id");
            $stmt_update->execute([
                ':senha' => $senha_hash,
                ':id' => $usuario['id']
            ]);

            $mensagem = "SUCCESS: Senha redefinida no Kernel. Redirecionando...";
            $status_msg = "success";
            
            // Limpa o token para esconder o formulário
            $token_valido = false;
            
            // Redireciona para o login após 3 segundos
            header("Refresh: 3; url=login.php");
        } else {
            $mensagem = "AVISO: A senha deve conter pelo menos 6 caracteres.";
            $status_msg = "warning";
        }
    } else {
        $mensagem = "ERRO: Os campos de senha e confirmação não conferem.";
        $status_msg = "danger";
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Redefinir Credenciais // Kernel</title>
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
                    <h3 class="fw-bold code-font text-white">overwrite_secure_password();</h3>
                    <p class="text-secondary small">Defina sua nova chave de criptografia de acesso.</p>
                </div>

                <?php if(!empty($mensagem)): ?>
                    <div class="alert alert-<?php echo $status_msg; ?> code-font small mb-4" role="alert">
                        <?php echo $mensagem; ?>
                    </div>
                <?php endif; ?>

                <?php if($token_valido): ?>
                    <div class="form-kernel p-4 shadow">
                        <form action="redefinir_senha.php" method="POST">
                            <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">

                            <div class="mb-3">
                                <label class="form-label text-secondary code-font small">new_password</label>
                                <input type="password" name="nova_senha" class="form-control input-kernel code-font small" placeholder="******" required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label text-secondary code-font small">confirm_new_password</label>
                                <input type="password" name="confirmar_senha" class="form-control input-kernel code-font small" placeholder="******" required>
                            </div>

                            <button type="submit" name="alterar_senha" class="btn btn-kernel code-font w-100 mt-2">gravar_nova_senha();</button>
                        </form>
                    </div>
                <?php else: ?>
                    <div class="text-center p-4 border border-dashed border-danger rounded bg-black">
                        <span class="text-danger code-font d-block small mb-2">⚠️ SYSTEM_OVERFLOW: Token Inválido</span>
                        <p class="text-secondary small mb-0">Esta sessão de alteração expirou ou não possui autorização válida.</p>
                        <a href="recuperar_senha.php" class="btn btn-sm btn-outline-danger code-font mt-3">solicitar_novo_token();</a>
                    </div>
                <?php endif; ?>

            </div>
        </div>
    </div>

</body>
</html>