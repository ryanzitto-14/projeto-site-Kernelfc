<?php
ini_set('session.cookie_path', '/');
if (!session_id()) session_start();

// Verificação básica de login
if (!isset($_SESSION['logado']) || $_SESSION['logado'] !== true) {
    header('Location: index.php');
    exit;
}

require_once 'conexao.php';

// Inicializa variáveis padrão para evitar erros no HTML
$dados_user = [
    'nome' => 'Usuário Kernel',
    'matricula' => 'Não informada',
    'curso' => 'Não informado',
    'status_socio' => 'pendente',
    'tipo_membro' => 'nao_definido',
    'modelo_camisa' => 'linha',
    'camisa_nome' => '',
    'camisa_numero' => '',
    'camisa_tamanho' => '', // 👕 ADICIONADO: Inicialização do tamanho padrão
    'nivel_acesso' => 'atleta/apoiador'
];

try {
    // 🚀 ATUALIZADO COM LEFT JOIN: Busca os dados do usuário e junta com a tabela de cursos usando o curso_id
    $stmt = $pdo->prepare("
        SELECT 
            u.nome, 
            u.matricula, 
            u.status_socio, 
            u.tipo_membro, 
            u.modelo_camisa, 
            u.camisa_nome, 
            u.camisa_numero, 
            u.camisa_tamanho, -- 👕 ADICIONADO: Busca o campo de tamanho no Banco de Dados
            u.nivel_acesso,
            c.nome AS curso
        FROM usuarios u
        LEFT JOIN cursos c ON u.curso_id = c.id
        WHERE u.id = :id
    ");
    $stmt->execute([':id' => $_SESSION['usuario_id']]);
    $resultado_banco = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($resultado_banco) {
        // Se o curso vier nulo (caso o curso_id seja nulo ou inválido), define um texto amigável
        if (empty($resultado_banco['curso'])) {
            $resultado_banco['curso'] = "Não informado";
        }
        // Mescla os dados encontrados sobrepondo os valores padrão
        $dados_user = array_merge($dados_user, $resultado_banco);
    } else {
        header('Location: logout.php');
        exit;
    }

    // Ajustes de sessão
    $_SESSION['usuario_nivel'] = $dados_user['nivel_acesso'];
    $_SESSION['usuario_nome']  = $dados_user['nome'];

    // Query para buscar o nome real da modalidade
    $stmt_esportes = $pdo->prepare("
        SELECT m.nome AS nome_modalidade 
        FROM membros_modalidades mm
        JOIN modalidades m ON mm.modalidade_id = m.id
        WHERE mm.usuario_id = :usuario_id
    ");
    $stmt_esportes->execute([':usuario_id' => $_SESSION['usuario_id']]);
    $modalidades_linhas = $stmt_esportes->fetchAll(PDO::FETCH_ASSOC);
    $modalidades_usuario = array_column($modalidades_linhas, 'nome_modalidade');

    // Busca opções de todos os cursos para listar dentro do Modal de Edição
    try {
        $stmt_cursos = $pdo->query("SELECT nome FROM cursos ORDER BY (nome = 'Outros') ASC, nome ASC");
        $lista_cursos = $stmt_cursos->fetchAll(PDO::FETCH_COLUMN);
    } catch (PDOException $e) {
        $lista_cursos = [];
    }

} catch (PDOException $e) {
    // Evita Erro 500 exibindo o erro na tela caso algo na sintaxe falhe
    echo "<div style='color:red; background:#fff; padding:10px;'>Erro na consulta: " . $e->getMessage() . "</div>";
    $lista_cursos = [];
}

// Backup de segurança para a lista do modal de edição
if (empty($lista_cursos)) {
    $lista_cursos = ['ADS (Análise e Des. de Sistemas)', 'Administração', 'Direito', 'Engenharia', 'Matemática', 'Pedagogia', 'Serviços Sociais', 'Outros'];
}

$tem_personalizacao = (!empty($dados_user['camisa_nome']) || !empty($dados_user['camisa_numero'])) ? true : false;

// Tratamento do nível de acesso
$nivel_limpo = isset($dados_user['nivel_acesso']) ? strtolower(trim($dados_user['nivel_acesso'])) : '';

if (empty($nivel_limpo)) {
    $tem_acesso_manto = true; // Força exibição se estiver vazio no banco local
} else {
    $tem_acesso_manto = in_array($nivel_limpo, ['atleta', 'diretoria', 'atleta/apoiador', '']);
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel do Integrante - Kernel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Fira+Code:wght@400;600&family=Poppins:wght@300;400;600;700&family=Rajdhani:wght@600;700&display=swap" rel="stylesheet">
    <link rel="icon" type="image/png" href="favicon.png">
    
    <style>
        :root {
            --tech-green: #00FF66;
            --forest-green: #006633;
            --dark-bg: #0D0D0D;
            --dark-card: #1A1A1A;
            --white-text: #F5F5F5;
        }

        body { background-color: var(--dark-bg); color: var(--white-text); font-family: 'Poppins', sans-serif; }
        .code-font { font-family: 'Fira Code', monospace; }

        .card-atletica {
            background: linear-gradient(135deg, #151515 0%, #222222 100%);
            border: 2px solid var(--tech-green);
            border-radius: 16px;
            width: 100%;
            max-width: 400px;
            margin: 0 auto 2rem auto;
            box-shadow: 0 0 25px rgba(0, 255, 102, 0.15);
        }

        .status-badge { font-size: 0.8rem; padding: 3px 10px; border-radius: 20px; font-weight: 600; }
        .status-ativo { background-color: rgba(0, 255, 102, 0.2); color: var(--tech-green); border: 1px solid var(--tech-green); }
        .status-pendente { background-color: rgba(255, 193, 7, 0.2); color: #ffc107; border: 1px solid #ffc107; }

        .manto-bloqueado-container {
            position: relative;
            background-color: var(--dark-card);
            border: 1px dashed #444;
            border-radius: 8px;
            overflow: hidden;
        }

        .jersey-preview-container {
            position: relative;
            width: 100%;
            max-width: 260px;
            height: 260px;
            margin: 0 auto;
            background-size: contain;
            background-position: center;
            background-repeat: no-repeat;
            transition: background-image 0.3s ease;
            background-color: transparent;
        }

        .jersey-text-name {
            position: absolute;
            top: 25%;
            left: 50%;
            transform: translateX(-50%);
            font-family: 'Rajdhani', sans-serif;
            font-weight: 700;
            text-transform: uppercase;
            font-size: 1rem;
            letter-spacing: 2px;
            text-align: center;
            width: 75%;
            text-shadow: -1px -1px 0 #000, 1px -1px 0 #000, -1px 1px 0 #000, 1px 1px 0 #000;
        }

        .jersey-text-number {
            position: absolute;
            top: 33%;
            left: 50%;
            transform: translateX(-50%);
            font-family: 'Rajdhani', sans-serif;
            font-weight: 700;
            font-size: 4rem;
            line-height: 0.9;
            text-align: center;
            width: 80%;
            text-shadow: -2px -2px 0 #000, 2px -2px 0 #000, -2px 2px 0 #000, 2px 2px 0 #000;
        }

        .form-control-kernel, .form-select-kernel {
            background-color: #0D0D0D !important;
            border: 1px solid #333 !important;
            color: var(--white-text) !important;
        }
        
        .btn-kernel {
            background: transparent; color: var(--tech-green); border: 2px solid var(--tech-green); font-weight: 600;
        }
        .btn-kernel:hover { background: var(--tech-green); color: var(--dark-bg); }

        .modal-content-kernel {
            background-color: #151515;
            border: 2px solid var(--tech-green);
            color: var(--white-text);
        }
        .modal-header-kernel { border-bottom: 1px solid #333; }
        .modal-footer-kernel { border-top: 1px solid #333; }

        @media (max-width: 991.98px) {
            .jersey-preview-container { max-width: 220px; height: 220px; }
            .jersey-text-number { font-size: 3.2rem; }
            .jersey-text-name { font-size: 0.85rem; }
        }
    </style>
</head>
<body>

    <?php include 'navbar.php'; ?>

    <div class="container py-4 py-md-5">
        <div class="row g-4">
            
            <div class="col-lg-4">
                <div class="d-flex align-items-center justify-content-between">
                    <h3 class="fw-bold mb-0">Seu Perfil</h3>
                    <button class="btn btn-sm btn-kernel code-font" data-bs-toggle="modal" data-bs-target="#modalEditarPerfil">editar_perfil();</button>
                </div>
                <hr class="border-secondary my-3">
                
                <div class="card-atletica">
                    <div class="p-3 bg-black d-flex align-items-center justify-content-between rounded-top">
                        <div class="d-flex align-items-center">
                            <img src="img/logos (1).png" alt="Kernel Logo" height="40" class="me-2">
                            <span class="code-font map-label fw-bold text-white small">A.A.A. KERNEL</span>
                        </div>
                        <span class="status-badge <?php echo (isset($dados_user['status_socio']) && $dados_user['status_socio'] === 'ativo') ? 'status-ativo' : 'status-pendente'; ?>">
                            <?php echo strtoupper($dados_user['status_socio'] ?? 'PENDENTE'); ?>
                        </span>
                    </div>
                    <div class="p-4">
                        <div class="mb-3">
                            <label class="text-success small d-block code-font">// nome</label>
                            <span class="fs-5 fw-bold text-white text-break"><?php echo htmlspecialchars($dados_user['nome'] ?? 'Membro'); ?></span>
                        </div>

                        <div class="mb-3">
                            <label class="text-success small d-block code-font">// curso</label>
                            <span class="text-white fw-semibold text-break"><?php echo htmlspecialchars($dados_user['curso']); ?></span>
                        </div>

                        <div class="row g-2">
                            <div class="col-6">
                                <label class="text-success small d-block code-font">// matricula</label>
                                <span class="text-white fw-semibold text-break"><?php echo htmlspecialchars($dados_user['matricula'] ?? 'Não informada'); ?></span>
                            </div>
                            <div class="col-6">
                                <label class="text-success small d-block code-font">// modalidades</label>
                                <span class="text-white fw-semibold d-block text-break">
                                    <?php 
                                    if (!empty($modalidades_usuario)) {
                                        $modalidades_formatadas = array_map(function($m) {
                                            if (strtolower($m) === 'fut7') return 'FUT7';
                                            return ucfirst($m);
                                        }, $modalidades_usuario);
                                        echo htmlspecialchars(implode(', ', $modalidades_formatadas));
                                    } else {
                                        echo "Nenhuma";
                                    }
                                    ?>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-8">
                <h3 class="fw-bold">Monte seu Manto Oficial 👕</h3>
                <p class="text-muted small mb-0">Defina abaixo sua função, modelo de manto e numeração.</p>
                <hr class="border-secondary my-3">

                <div class="p-3 p-md-4 manto-bloqueado-container">
                    <?php if (!$tem_acesso_manto): ?>
                        <div class="manto-overlay-locked text-center py-5">
                            <h4 class="text-warning fw-bold mb-2">🔒 Funcionalidade Bloqueada</h4>
                            <p class="text-secondary small" style="max-width: 450px; margin: 0 auto;">
                                A personalização de mantos está disponível apenas para membros ativos com nível de **Atleta/apoiador** ou **Diretoria**.
                            </p>
                            <span class="code-font text-danger small d-block mt-3">// status: acesso_negado_para_nivel_<?php echo htmlspecialchars($nivel_limpo); ?></span>
                        </div>
                    <?php else: ?>
                        <form action="salvar_manto.php" method="POST">
                            <div class="row g-4">
                                <div class="col-md-7 order-2 order-md-1">
                                    <div class="mb-3">
                                        <label class="form-label text-success code-font">// tipo_vínculo</label>
                                        <select name="tipo_membro" id="tipo_membro" class="form-select form-select-kernel" required>
                                            <option value="nao_definido" <?php echo ($dados_user['tipo_membro'] == 'nao_definido')?'selected':''; ?>>Selecione uma option...</option>
                                            <option value="atleta" <?php echo ($dados_user['tipo_membro'] == 'atleta')?'selected':''; ?>>Atleta (Número Exclusivo)</option>
                                            <option value="apoiador" <?php echo ($dados_user['tipo_membro'] == 'apoiador')?'selected':''; ?>>Apoiador / Torcedor</option>
                                        </select>
                                        <small id="regra_texto" class="text-info d-block mt-2 font-monospace" style="font-size:0.8rem;"></small>
                                    </div>

                                    <div class="mb-3" id="container_modelo" style="display: none;">
                                        <label class="form-label text-success code-font">// modelo_manto</label>
                                        <select name="modelo_camisa" id="modelo_camisa" class="form-select form-select-kernel">
                                            <option value="linha" <?php echo ($dados_user['modelo_camisa'] == 'linha')?'selected':''; ?>>Jogador de Linha (Manto Verde)</option>
                                            <option value="goleiro" <?php echo ($dados_user['modelo_camisa'] == 'goleiro')?'selected':''; ?>>Goleiro (Manto Preto)</option>
                                        </select>
                                    </div>

                                    <div class="mb-3" id="container_tamanho" style="display: none;">
                                        <label class="form-label text-success code-font">// tamanho_manto</label>
                                        <select name="camisa_tamanho" id="camisa_tamanho" class="form-select form-select-kernel" required>
                                            <option value="" disabled <?php echo empty($dados_user['camisa_tamanho']) ? 'selected' : ''; ?>>Escolha o tamanho...</option>
                                            <option value="P" <?php echo ($dados_user['camisa_tamanho'] == 'P')?'selected':''; ?>>P (Pequeno)</option>
                                            <option value="M" <?php echo ($dados_user['camisa_tamanho'] == 'M')?'selected':''; ?>>M (Médio)</option>
                                            <option value="G" <?php echo ($dados_user['camisa_tamanho'] == 'G')?'selected':''; ?>>G (Grande)</option>
                                            <option value="GG" <?php echo ($dados_user['camisa_tamanho'] == 'GG')?'selected':''; ?>>GG (Extra Grande)</option>
                                        </select>
                                    </div>

                                    <div id="container_personalizar" class="form-check form-switch mb-3" style="display:none;">
                                        <input class="form-check-input" type="checkbox" role="switch" id="personalizar_check" name="personalizar_check" <?php echo ($dados_user['tipo_membro'] === 'atleta' || $tem_personalizacao || $dados_user['tipo_membro'] == 'nao_definido') ? 'checked' : ''; ?>>
                                        <label class="form-check-label text-white small" for="personalizar_check">Quero personalizar com Nome e Número</label>
                                    </div>

                                    <div class="mb-3" id="box_nome">
                                        <label class="form-label text-success code-font">// nome_na_camisa</label>
                                        <input type="text" name="camisa_nome" id="camisa_nome" class="form-control form-control-kernel" maxlength="15" placeholder="Ex: RYAN" value="<?php echo htmlspecialchars($dados_user['camisa_nome'] ?? ''); ?>" autocomplete="off">
                                    </div>

                                    <div class="mb-4" id="box_numero">
                                        <label class="form-label text-success code-font">// número_1_a_99</label>
                                        <input type="number" name="camisa_numero" id="camisa_numero" class="form-control form-control-kernel" min="1" max="99" placeholder="Ex: 10" value="<?php echo htmlspecialchars($dados_user['camisa_numero'] ?? ''); ?>">
                                    </div>

                                    <button type="submit" class="btn btn-kernel code-font w-100">salvar_manto();</button>
                                </div>

                                <div class="col-md-5 d-flex flex-column align-items-center justify-content-center order-1 order-md-2 mb-3 mb-md-0">
                                    <label class="form-label text-success code-font d-block mb-2 mb-md-3">// preview_costas</label>
                                    <div id="jersey_preview" class="jersey-preview-container">
                                        <div id="view_nome" class="jersey-text-name">KERNEL</div>
                                        <div id="view_numero" class="jersey-text-number">00</div>
                                    </div>
                                </div>
                            </div>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
            
        </div>
    </div>

    <div class="modal fade" id="modalEditarPerfil" tabindex="-1" aria-labelledby="modalEditarPerfilLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content modal-content-kernel">
                <div class="modal-header modal-header-kernel">
                    <h5 class="modal-title fw-bold text-white" id="modalEditarPerfilLabel">🔧 Ajustar Credenciais</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="atualizar_perfil.php" method="POST">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="text-success small d-block code-font">// nome_completo</label>
                            <input type="text" class="form-control form-control-kernel" id="editar_nome" name="editar_nome" value="<?php echo htmlspecialchars($dados_user['nome'] ?? ''); ?>" required autocomplete="off">
                        </div>
                        
                        <div class="mb-3">
                            <label class="text-success small d-block code-font">// selecionar_curso</label>
                            <select class="form-select form-select-kernel" id="editar_curso" name="editar_curso">
                                <option value="Sem Curso" <?php echo ($dados_user['curso'] === 'Sem Curso') ? 'selected' : ''; ?>>Sem curso / Externo</option>
                                <?php foreach ($lista_cursos as $curso_opcao): ?>
                                    <?php 
                                    if ($curso_opcao === 'Sem Curso' || $curso_opcao === 'Outros') continue; 
                                    $selected = ($dados_user['curso'] === $curso_opcao) ? 'selected' : ''; 
                                    ?>
                                    <option value="<?php echo htmlspecialchars($curso_opcao); ?>" <?php echo $selected; ?>>
                                        <?php echo htmlspecialchars($curso_opcao); ?>
                                    </option>
                                <?php endforeach; ?>
                                <option value="Outros" <?php echo ($dados_user['curso'] === 'Outros') ? 'selected' : ''; ?>>Outros (Não listado)</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="text-success small d-block code-font">// número_matrícula</label>
                            <input type="text" class="form-control form-control-kernel" id="editar_matricula" name="editar_matricula" value="<?php echo htmlspecialchars($dados_user['matricula'] ?? ''); ?>" placeholder="Ex: 2023XXXXX" autocomplete="off">
                        </div>
                    </div>
                    <div class="modal-footer modal-footer-kernel">
                        <button type="button" class="btn btn-secondary btn-sm code-font" data-bs-dismiss="modal">cancelar();</button>
                        <button type="submit" class="btn btn-kernel btn-sm code-font">salvar_alteracoes();</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <?php if ($tem_acesso_manto): ?>
    <script>
        const tipoMembro = document.getElementById('tipo_membro');
        const containerModelo = document.getElementById('container_modelo');
        const containerTamanho = document.getElementById('container_tamanho'); // 👕 ADICIONADO
        const modeloCamisa = document.getElementById('modelo_camisa');
        const containerPersonalizar = document.getElementById('container_personalizar');
        const personalizarCheck = document.getElementById('personalizar_check');
        const boxNome = document.getElementById('box_nome');
        const boxNumero = document.getElementById('box_numero');
        const regraTexto = document.getElementById('regra_texto');
        const inputNome = document.getElementById('camisa_nome');
        const inputNumero = document.getElementById('camisa_numero');
        const jerseyPreview = document.getElementById('jersey_preview');
        const viewNome = document.getElementById('view_nome');
        const viewNumero = document.getElementById('view_numero');

        function atualizarMockup() {
            if(!modeloCamisa || !jerseyPreview) return;
            
            const modelo = modeloCamisa.value;
            if (modelo === 'goleiro') {
                jerseyPreview.style.backgroundImage = "url('camisa_preta.png')";
                viewNome.style.color = "var(--forest-green)";
                viewNumero.style.color = "var(--forest-green)";
            } else {
                jerseyPreview.style.backgroundImage = "url('camisa_verde.png')";
                viewNome.style.color = "#FFFFFF";
                viewNumero.style.color = "#FFFFFF";
            }

            if (personalizarCheck && !personalizarCheck.checked && tipoMembro.value === 'apoiador') {
                viewNome.style.display = "none";
                viewNumero.style.display = "none";
            } else {
                viewNome.style.display = "block";
                viewNumero.style.display = "block";
            }
        }

        if(personalizarCheck) {
            personalizarCheck.addEventListener('change', function() {
                if (!this.checked) {
                    inputNome.disabled = true;
                    inputNumero.disabled = true;
                    inputNome.value = "";
                    inputNumero.value = "";
                    inputNome.removeAttribute('required');
                    inputNumero.removeAttribute('required');
                } else {
                    inputNome.disabled = false;
                    inputNumero.disabled = false;
                    inputNome.setAttribute('required', 'required');
                    inputNumero.setAttribute('required', 'required');
                }
                atualizarMockup();
            });
        }

        if(tipoMembro) {
            tipoMembro.addEventListener('change', function() {
                if(this.value === 'atleta') {
                    regraTexto.innerHTML = "// REGRA: Número ÚNICO de atleta.";
                    regraTexto.style.color = "var(--tech-green)";
                    containerModelo.style.display = "block";
                    containerTamanho.style.display = "block"; // 👕 ADICIONADO: Exibe os tamanhos
                    containerPersonalizar.style.display = "none"; 
                    if(personalizarCheck) personalizarCheck.checked = true;
                    inputNome.disabled = false;
                    inputNumero.disabled = false;
                    inputNome.setAttribute('required', 'required');
                    inputNumero.setAttribute('required', 'required');
                } else if(this.value === 'apoiador') {
                    regraTexto.innerHTML = "// REGRA: Números livres para apoiadores.";
                    regraTexto.style.color = "#4499FF";
                    containerModelo.style.display = "block";
                    containerTamanho.style.display = "block"; // 👕 ADICIONADO: Exibe os tamanhos
                    containerPersonalizar.style.display = "block"; 
                    if(personalizarCheck) personalizarCheck.dispatchEvent(new Event('change'));
                } else {
                    regraTexto.innerHTML = "";
                    containerModelo.style.display = "none";
                    containerTamanho.style.display = "none"; // 👕 ADICIONADO: Oculta se não selecionado
                    containerPersonalizar.style.display = "none";
                }
                atualizarMockup();
            });
        }

        if(modeloCamisa) modeloCamisa.addEventListener('change', atualizarMockup);
        if(inputNome) inputNome.addEventListener('input', function() { viewNome.innerText = this.value.toUpperCase() || "KERNEL"; });
        if(inputNumero) inputNumero.addEventListener('input', function() { viewNumero.innerText = this.value || "00"; });

        window.addEventListener('load', () => {
            if (tipoMembro && tipoMembro.value === 'apoiador' && !inputNome.value && !inputNumero.value) {
                if(personalizarCheck) personalizarCheck.checked = false;
            }
            if(inputNome && inputNome.value) viewNome.innerText = inputNome.value.toUpperCase();
            if(inputNumero && inputNumero.value) viewNumero.innerText = inputNumero.value;
            if(tipoMembro) tipoMembro.dispatchEvent(new Event('change'));
            atualizarMockup();
        });
    </script>
    <?php endif; ?>
</body>
</html>