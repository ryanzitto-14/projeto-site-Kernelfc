<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Atlética Kernel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Fira+Code:wght@400;600&family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="icon" type="image/png" href="favicon.png">
    
    <script src="https://accounts.google.com/gsi/client" async defer></script>
    
    <style>
        :root {
            --tech-green: #00FF66; /* Verde Kernel */
            --dark-bg: #0D0D0D;    /* Preto Fundo */
            --dark-card: #1A1A1A;  /* Preto Componentes */
            --white-text: #F5F5F5;
        }

        body {
            background-color: var(--dark-bg);
            color: var(--white-text);
            font-family: 'Poppins', sans-serif;
            min-height: 100vh;
            display: flex;
            flex-direction: column; /* Garante empilhamento correto se necessário */
            align-items: center;
            justify-content: center;
            padding: 20px 12px; /* Margem de segurança para o corpo do site */
        }

        .code-font {
            font-family: 'Fira Code', monospace;
        }

        /* Card de Login */
        .login-card {
            background-color: var(--dark-card);
            border: 1px solid rgba(0, 255, 102, 0.2);
            border-radius: 12px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.7);
            width: 100%;
            max-width: 420px;
            padding: 2.5rem 2rem;
            transition: border-color 0.3s ease;
        }

        .login-card:hover {
            border-color: rgba(0, 255, 102, 0.4);
        }

        /* Customizações dos Inputs */
        .form-control-kernel {
            background-color: #0D0D0D !important;
            border: 1px solid #333 !important;
            color: var(--white-text) !important;
            border-radius: 6px;
            padding: 0.75rem 1rem;
            transition: all 0.3s ease;
        }

        .form-control-kernel:focus {
            border-color: var(--tech-green) !important;
            box-shadow: 0 0 10px rgba(0, 255, 102, 0.2) !important;
            outline: none;
        }

        .form-label-kernel {
            color: #aaa;
            font-size: 0.9rem;
            margin-bottom: 0.5rem;
            font-weight: 500;
        }

        /* Botão de Enviar */
        .btn-kernel-block {
            background-color: transparent;
            color: var(--tech-green);
            border: 2px solid var(--tech-green);
            font-weight: 600;
            padding: 0.75rem;
            border-radius: 6px;
            width: 100%;
            transition: all 0.3s ease;
        }

        .btn-kernel-block:hover {
            background-color: var(--tech-green);
            color: var(--dark-bg);
            box-shadow: 0 0 15px rgba(0, 255, 102, 0.4);
        }

        /* Links adicionais */
        .login-links a {
            color: #888;
            font-size: 0.85rem;
            text-decoration: none;
            transition: color 0.3s ease;
        }

        .login-links a:hover {
            color: var(--tech-green);
        }

        /* Posicionamento Inteligente do Voltar */
        .back-home {
            position: absolute;
            top: 20px;
            left: 20px;
            z-index: 10;
        }

        /* Ajustes específicos para telas mobile compactas */
        @media (max-width: 575.98px) {
            .back-home {
                position: relative;
                top: 0;
                left: 0;
                margin-bottom: 20px;
                width: 100%;
                max-width: 420px;
                text-align: left;
            }
            .login-card {
                padding: 2rem 1.25rem; /* Menos espaço interno para dar área útil ao form */
            }
        }
    </style>
</head>
<body>

    <!-- Botão Voltar responsivo -->
    <div class="back-home">
        <a href="index.php" class="btn btn-sm code-font" style="color: var(--tech-green); border: 1px solid var(--tech-green); text-decoration: none; padding: 6px 15px; border-radius: 4px; display: inline-block;">
            &lt; voltar_home /&gt;
        </a>
    </div>

    <div class="container d-flex justify-content-center align-items-center flex-grow-1 p-0">
        <div class="login-card text-center">
            
            <div class="mb-4">
                <h4 class="mt-2 fw-bold code-font mb-0" style="color: var(--tech-green); font-size: 1.3rem;">
                    &lt;AUTH_INTEGRANTE /&gt;
                </h4>
            </div>

            <form action="validar_login.php" method="POST" class="text-start">
                
                <div class="mb-3">
                    <label for="email" class="form-label form-label-kernel code-font">// endereco_email</label>
                    <input type="email" name="email" id="email" class="form-control form-control-kernel" placeholder="Ex: integrante@kernel.com" required autocomplete="off">
                </div>

                <div class="mb-4">
                    <label for="senha" class="form-label form-label-kernel code-font">// chave_acesso</label>
                    <input type="password" name="senha" id="senha" class="form-control form-control-kernel" placeholder="••••••••" required>
                </div>

                <button type="submit" class="btn btn-kernel-block code-font mb-2">
                    executar_login();
                </button>
            </form>

            <div class="text-center my-3 text-muted small code-font">
                <span>// ou_autenticar_via_api</span>
            </div>

            <!-- API de login do Google configurada de forma fluida -->
            <div id="g_id_onload"
                 data-client_id="783243326403-d92ol3g8m196viem1iois3u193p8ukrp.apps.googleusercontent.com"
                 data-context="signin"
                 data-ux_mode="popup"
                 data-callback="handleCredentialResponse"
                 data-auto_prompt="false">
            </div>

            <div class="d-flex justify-content-center mb-3">
                <!-- Removido o data-width fixo para herdar o comportamento responsivo do container do botão -->
                <div class="g_id_signin"
                     data-type="standard"
                     data-shape="rounded"
                     data-theme="dark"
                     data-text="signin_with"
                     data-size="large"
                     data-logo_alignment="left">
                </div>
            </div>

            <div class="login-links d-flex justify-content-between mt-4 px-1">
                <a href="recuperar_senha.php">esqueci_a_senha</a>
                <a href="cadastro_socio.php" style="color: var(--tech-green);">novo_cadastro &gt;</a>
            </div>

        </div>
    </div>

    <script>
    function handleCredentialResponse(response) {
        var form = document.createElement('form');
        form.method = 'POST';
        form.action = 'validar_login.php';

        var input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'google_jwt';
        input.value = response.credential;

        form.appendChild(input);
        document.body.appendChild(form);
        form.submit();
    }
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>