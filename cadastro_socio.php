<?php
ini_set('session.cookie_path', '/');
if (!session_id()) session_start();

$email_preenchido = isset($_SESSION['google_email_cadastro']) ? $_SESSION['google_email_cadastro'] : '';

// Conexão e carregamento dinâmico buscando ID e Nome
require_once 'conexao.php';
try {
    $stmt_cursos = $pdo->query("SELECT id, nome FROM cursos ORDER BY (nome = 'Outros') ASC, nome ASC");
    $lista_cursos = $stmt_cursos->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Fallback seguro caso o banco falhe temporariamente
    $lista_cursos = [];
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastro de Sócio - Atlética Kernel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Fira+Code:wght@400;600&family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link class="js-favicon" rel="icon" type="image/png" href="favicon.png">

    <style>
        :root {
            --tech-green: #00FF66; 
            --dark-bg: #0D0D0D;    
            --dark-card: #1A1A1A;  
            --white-text: #F5F5F5;
        }

        body {
            background-color: var(--dark-bg);
            color: var(--white-text);
            font-family: 'Poppins', sans-serif;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 80px 12px 40px 12px;
            position: relative;
        }

        .code-font {
            font-family: 'Fira Code', monospace;
        }

        .register-card {
            background-color: var(--dark-card);
            border: 1px solid rgba(0, 255, 102, 0.2);
            border-radius: 12px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.7);
            width: 100%;
            max-width: 550px;
            padding: 2.5rem 2rem;
            transition: border-color 0.3s ease;
        }

        .register-card:hover {
            border-color: rgba(0, 255, 102, 0.4);
        }

        .form-control-kernel, .form-select-kernel {
            background-color: #0D0D0D !important;
            border: 1px solid #333 !important;
            color: var(--white-text) !important;
            border-radius: 6px;
            padding: 0.75rem 1rem;
            transition: all 0.3s ease;
        }

        .form-control-kernel:focus, .form-select-kernel:focus {
            border-color: var(--tech-green) !important;
            box-shadow: 0 0 10px rgba(0, 255, 102, 0.2) !important;
            outline: none;
        }

        .form-select-kernel option {
            background-color: var(--dark-card);
            color: var(--white-text);
        }

        .form-label-kernel {
            color: #aaa;
            font-size: 0.85rem;
            margin-bottom: 0.4rem;
            font-weight: 500;
        }

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

        .back-home {
            position: absolute;
            top: 20px;
            left: 20px;
            z-index: 10;
        }

        @media (max-width: 767.98px) {
            body {
                padding-top: 75px;
            }
            .back-home {
                width: calc(100% - 24px);
                left: 12px;
                top: 15px;
            }
            .back-home a {
                display: block;
                text-align: center;
                width: 100%;
            }
            .register-card {
                padding: 1.5rem 1.25rem;
            }
            .register-card img {
                height: 70px;
            }
            .register-card h4 {
                font-size: 1.1rem !important;
            }
        }
    </style>
</head>
<body>

    <div class="back-home">
        <a href="index.php" class="btn btn-sm btn-kernel code-font" style="color: var(--tech-green); border: 1px solid var(--tech-green); text-decoration: none; padding: 5px 15px; border-radius: 4px;">
            &lt; voltar_home /&gt;
        </a>
    </div>

    <div class="container d-flex justify-content-center align-items-center p-0">
        <div class="register-card text-center">
            <div class="mb-4">
                <img src="img/logos (1).png" alt="Logo Kernel" height="90" style="filter: drop-shadow(0 0 10px rgba(0, 255, 102, 0.15));">
                <h4 class="mt-3 fw-bold code-font mb-0" style="color: var(--tech-green); font-size: 1.3rem;">
                    &lt;REGISTRAR_INTEGRANTE /&gt;
                </h4>
                <p class="text-muted small mt-1">Inscreva-se para fazer parte da comunidade da Kernel</p>
            </div>

            <form action="processar_cadastro.php" method="POST" class="text-start">
                
                <div class="mb-3">
                    <label for="nome" class="form-label form-label-kernel code-font">// nome_completo</label>
                    <input type="text" name="nome" id="nome" class="form-control form-control-kernel" placeholder="Ex: Ryan Lucas Sales" required>
                </div>

                <div class="mb-3">
                    <label for="curso_id" class="form-label form-label-kernel code-font">// selecionar_curso</label>
                    <select name="curso_id" id="curso_id" class="form-select form-select-kernel" required>
                        <option value="" disabled selected>Escolha o seu curso...</option>
                        <option value="Sem Curso">Sem curso / Externo / Outro</option>
                        
                        <?php foreach ($lista_cursos as $curso_item): ?>
                            <?php if ($curso_item['nome'] === 'Sem Curso' || $curso_item['nome'] === 'Outros') continue; ?>
                            <option value="<?php echo $curso_item['id']; ?>">
                                <?php echo htmlspecialchars($curso_item['nome']); ?>
                            </option>
                        <?php endforeach; ?>

                        <!-- Caso a tabela do banco possua o ID do registro 'Outros', você pode capturá-lo dinamicamente ou deixar o valor de texto para tratamento posterior -->
                        <?php 
                        $id_outros = 'Outros';
                        foreach($lista_cursos as $c) { if($c['nome'] === 'Outros') { $id_outros = $c['id']; } }
                        ?>
                        <option value="<?php echo $id_outros; ?>">Outros (Não listado)</option>
                    </select>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3" id="container-matricula">
                        <label for="matricula" class="form-label form-label-kernel code-font">// matricula_facam</label>
                        <input type="text" name="matricula" id="matricula" class="form-control form-control-kernel" placeholder="Ex: 202610123" required>
                    </div>
                    <div class="col-md-6 mb-3" id="container-whatsapp">
                        <label for="celular" class="form-label form-label-kernel code-font">// whatsapp</label>
                        <input type="tel" name="celular" id="celular" class="form-control form-control-kernel" placeholder="(98) 99999-9999" required>
                    </div>
                </div>

                <div class="mb-3">
                    <label for="email" class="form-label text-success code-font">// e-mail</label>
                    <input type="email" 
                        name="email" 
                        id="email" 
                        class="form-control form-control-kernel" 
                        value="<?php echo htmlspecialchars($email_preenchido); ?>" 
                        required 
                        <?php echo !empty($email_preenchido) ? 'readonly style="background-color: #1a1a1a; color: #888; cursor: not-allowed;"' : ''; ?>>
                    
                    <?php if (!empty($email_preenchido)): ?>
                        <small class="text-info d-block mt-1 font-monospace" style="font-size: 0.8rem;">// E-mail preenchido via autenticação segura.</small>
                    <?php endif; ?>
                </div>

                <div class="mb-3">
                    <label for="senha" class="form-label form-label-kernel code-font">// definir_senha</label>
                    <input type="password" name="senha" id="senha" class="form-control form-control-kernel" placeholder="Mínimo de 6 caracteres" minlength="6" required>
                </div>

                <div class="mt-4">
                    <button type="submit" class="btn-kernel-block code-font">cadastrar_membro();</button>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.getElementById('curso_id').addEventListener('change', function() {
            const containerMatricula = document.getElementById('container-matricula');
            const containerWhatsapp = document.getElementById('container-whatsapp');
            const inputMatricula = document.getElementById('matricula');
            
            // O gatilho de ocultar matrícula permanece ativo caso a string selecionada seja 'Sem Curso'
            if (this.value === 'Sem Curso') {
                containerMatricula.style.display = 'none';
                inputMatricula.removeAttribute('required');
                inputMatricula.value = ''; 
                
                containerWhatsapp.classList.remove('col-md-6');
                containerWhatsapp.classList.add('col-md-12');
            } else {
                containerMatricula.style.display = 'block';
                inputMatricula.setAttribute('required', 'required');
                inputMatricula.placeholder = "Ex: 202610123";
                
                containerWhatsapp.classList.remove('col-md-12');
                containerWhatsapp.classList.add('col-md-6');
            }
        });
    </script>
</body>
</html>