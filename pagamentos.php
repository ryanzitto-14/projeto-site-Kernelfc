<?php
ini_set('session.cookie_path', '/');
if (!session_id()) session_start();

// 🛡️ TRAVA DE SEGURANÇA: Só atletas e diretoria podem ver essa página
if (!isset($_SESSION['logado']) || $_SESSION['logado'] !== true || !in_array($_SESSION['usuario_nivel'], ['atleta', 'diretoria'])) {
    echo "<script>
            alert('Acesso restrito. Apenas atletas oficiais ou membros da diretoria têm acesso às finanças e mensalidades.');
            window.location.href = 'painel_integrante.php';
          </script>";
    exit;
}

require_once 'conexao.php';

$usuario_id = $_SESSION['usuario_id'];
$nivel_usuario = $_SESSION['usuario_nivel'];

// 🛡️ Gerar Token CSRF para operações da diretoria
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// =======================================================================
// 📊 LÓGICA DE BACKEND: SE FOR DIRETOR, PROCESSA AÇÕES FINANCEIRAS
// =======================================================================
if ($nivel_usuario === 'diretoria' && isset($_GET['acao']) && isset($_GET['id_fatura'])) {
    // Validação do Token CSRF para evitar cliques maliciosos externos
    if (!isset($_GET['token']) || $_GET['token'] !== $_SESSION['csrf_token']) {
        die('Ação inválida ou expirada (Falha de segurança CSRF).');
    }

    $id_fatura = intval($_GET['id_fatura']);
    
    if ($_GET['acao'] === 'confirmar') {
        $stmt_baixa = $pdo->prepare("UPDATE mensalidades SET status_pagamento = 'pago', data_pago = NOW() WHERE id = :id");
        $stmt_baixa->execute([':id' => $id_fatura]);
    } elseif ($_GET['acao'] === 'reverter') {
        $stmt_baixa = $pdo->prepare("UPDATE mensalidades SET status_pagamento = 'pendente', data_pago = NULL WHERE id = :id");
        $stmt_baixa->execute([':id' => $id_fatura]);
    } 
    // 🔥 NOVA AÇÃO: EXCLUIR FATURA INCORRETA
    elseif ($_GET['acao'] === 'excluir') {
        $stmt_excluir = $pdo->prepare("DELETE FROM mensalidades WHERE id = :id");
        $stmt_excluir->execute([':id' => $id_fatura]);
    }
    
    header("Location: pagamentos.php?sucesso=1");
    exit;
}

// =======================================================================
// 🔍 CONSULTAS AO BANCO DE DADOS
// =======================================================================
try {
    $stmt_planos = $pdo->query("SELECT * FROM planos ORDER BY id ASC");
    $planos_cadastrados = $stmt_planos->fetchAll(PDO::FETCH_ASSOC);

    $stmt_historico = $pdo->prepare("SELECT * FROM mensalidades WHERE usuario_id = :id ORDER BY mes_referencia DESC");
    $stmt_historico->execute([':id' => $usuario_id]);
    $minhas_mensalidades = $stmt_historico->fetchAll(PDO::FETCH_ASSOC);

    $todas_faturas = [];
    if ($nivel_usuario === 'diretoria') {
        $stmt_global = $pdo->query("SELECT m.*, u.nome as atleta_nome, u.matricula FROM mensalidades m 
                                    JOIN usuarios u ON m.usuario_id = u.id 
                                    ORDER BY m.status_pagamento DESC, m.mes_referencia DESC");
        $todas_faturas = $stmt_global->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (PDOException $e) {
    $planos_cadastrados = [];
    $minhas_mensalidades = [];
    $todas_faturas = [];
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Planos e Pagamentos - Atlética Kernel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Fira+Code:wght@400;600&family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link class="rounded-circle" rel="icon" type="image/png" href="favicon.png">

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
        }

        .code-font {
            font-family: 'Fira Code', monospace;
            word-break: break-word;
        }

        .finance-card {
            background-color: var(--dark-card);
            border: 1px solid rgba(0, 255, 102, 0.15);
            border-radius: 12px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.5);
            padding: 2rem;
            margin-bottom: 2rem;
        }

        .plan-card {
            background-color: #141414;
            border: 2px solid #222;
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.3s ease;
            height: 100%;
            overflow: hidden;
            display: flex;
            flex-direction: column;
        }

        .plan-card-img-container {
            width: 100%;
            height: 220px;
            background-color: #ECECEC;
            display: flex;
            justify-content: center;
            align-items: center;
            overflow: hidden;
        }

        .plan-card-img-container img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.3s ease;
        }

        .plan-card:hover .plan-card-img-container img {
            transform: scale(1.03);
        }

        .plan-card-body {
            padding: 1.5rem;
            flex-grow: 1;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            background-color: #141414;
        }

        .plan-card:hover {
            border-color: rgba(0, 255, 102, 0.5);
            transform: translateY(-4px);
        }

        .plan-card.selected {
            border-color: var(--tech-green);
            box-shadow: 0 0 20px rgba(0, 255, 102, 0.2);
        }

        .table-kernel {
            background-color: #0D0D0D !important;
            color: var(--white-text) !important;
            border-color: #333 !important;
        }

        .table-kernel th {
            background-color: #141414 !important;
            color: var(--tech-green) !important;
            border-bottom: 2px solid var(--tech-green) !important;
        }

        .status-badge-pago {
            background-color: rgba(0, 255, 102, 0.15);
            color: #00FF66;
            border: 1px solid #00FF66;
            padding: 4px 10px;
            border-radius: 6px;
            font-size: 0.8rem;
            white-space: nowrap;
        }

        .status-badge-pendente {
            background-color: rgba(255, 193, 7, 0.15);
            color: #ffc107;
            border: 1px solid #ffc107;
            padding: 4px 10px;
            border-radius: 6px;
            font-size: 0.8rem;
            white-space: nowrap;
        }

        .btn-pay {
            background-color: var(--tech-green);
            color: var(--dark-bg);
            font-weight: 600;
            border: none;
            transition: all 0.3s ease;
        }

        .btn-pay:hover {
            background-color: #00CC52;
            box-shadow: 0 0 20px rgba(0, 255, 102, 0.4);
            color: var(--dark-bg);
        }

        @media (max-width: 767.98px) {
            .plan-card-img-container { height: 180px; }
            .finance-card { padding: 1.25rem; }
            h2.fw-bold { font-size: 1.5rem !important; }
            #display-valor { font-size: 1.75rem !important; }
        }
    </style>
</head>
<body>

    <?php include_once 'navbar.php'; ?>

    <div class="container my-5 pt-4">
        
        <?php if (isset($_GET['sucesso'])): ?>
            <div class="alert alert-success bg-dark text-success border-success code-font mb-4" role="alert">
                ✓ Operação realizada e atualizada no banco de dados com sucesso!
            </div>
        <?php endif; ?>
        
        <div class="mb-5 d-flex justify-content-between align-items-center flex-wrap gap-3">
            <div>
                <h2 class="fw-bold code-font text-success">&lt;FINANCEIRO_KERNEL /&gt;</h2>
                <p class="text-secondary mb-0">Selecione sua modalidade esportiva, assine o plano e acesse o checkout direto de pagamento.</p>
            </div>
            <?php if ($nivel_usuario === 'diretoria'): ?>
                <a href="criar_plano.php" class="btn btn-warning code-font fw-bold">⚙️ Gerenciar Planos (Diretoria)</a>
            <?php endif; ?>
        </div>

        <?php if ($nivel_usuario === 'diretoria'): ?>
            <div class="finance-card border-warning">
                <h4 class="code-font text-warning mb-3">🛠️ [DIRETORIA] Controle de Faturas Globais</h4>
                <div class="table-responsive">
                    <table class="table table-dark table-hover table-kernel text-center align-middle small">
                        <thead>
                            <tr>
                                <th>Atleta</th>
                                <th>Matrícula</th>
                                <th>Mês Ref.</th>
                                <th>Valor</th>
                                <th>Status</th>
                                <th>Ações de Baixa</th>
                                <th>Gestão Interna</th> </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($todas_faturas)): ?>
                                <?php foreach ($todas_faturas as $fatura): ?>
                                    <tr>
                                        <td class="text-start fw-bold"><?php echo htmlspecialchars($fatura['atleta_nome']); ?></td>
                                        <td><?php echo htmlspecialchars($fatura['matricula']); ?></td>
                                        <td class="code-font"><?php echo htmlspecialchars($fatura['mes_referencia']); ?></td>
                                        <td>R$ <?php echo number_format($fatura['valor'], 2, ',', '.'); ?></td>
                                        <td><span class="<?php echo ($fatura['status_pagamento'] === 'pago') ? 'status-badge-pago' : 'status-badge-pendente'; ?>"><?php echo strtoupper($fatura['status_pagamento']); ?></span></td>
                                        <td>
                                            <?php if ($fatura['status_pagamento'] === 'pendente'): ?>
                                                <a href="pagamentos.php?acao=confirmar&id_fatura=<?php echo $fatura['id']; ?>&token=<?php echo $_SESSION['csrf_token']; ?>" class="btn btn-sm btn-success py-1 px-3 code-font" style="font-size: 0.75rem;">✓ Confirmar Pago</a>
                                            <?php else: ?>
                                                <a href="pagamentos.php?acao=reverter&id_fatura=<?php echo $fatura['id']; ?>&token=<?php echo $_SESSION['csrf_token']; ?>" class="btn btn-sm btn-outline-secondary py-1 px-2 code-font" style="font-size: 0.75rem;">↩ Reverter</a>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <a href="pagamentos.php?acao=excluir&id_fatura=<?php echo $fatura['id']; ?>&token=<?php echo $_SESSION['csrf_token']; ?>" 
                                               class="btn btn-sm btn-danger py-1 px-2 code-font" 
                                               style="font-size: 0.75rem;" 
                                               onclick="return confirm('⚠️ ATENÇÃO: Tem certeza absoluta que deseja DELETAR esta fatura do sistema? Esta ação não pode ser desfeita.');">
                                                🗑️ Excluir
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr><td colspan="7" class="text-secondary p-4">// Nenhuma fatura global registrada.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>

        <div class="row mb-5">
            <div class="col-12 mb-3">
                <h5 class="code-font text-success">// 01.selecionar_modalidade_esportiva</h5>
            </div>
            
            <?php if (!empty($planos_cadastrados)): ?>
                <?php $primeiro = true; foreach ($planos_cadastrados as $plano): ?>
                    <div class="col-md-4 mb-4">
                        <div class="plan-card <?php echo $primeiro ? 'selected' : ''; ?>" 
                             data-nome="<?php echo htmlspecialchars($plano['nome']); ?>" 
                             data-valor="<?php echo number_format($plano['valor'], 2, ',', '.'); ?>" 
                             data-url="<?php echo htmlspecialchars($plano['link_pagamento']); ?>">
                            
                            <div class="plan-card-img-container">
                                <img src="<?php echo htmlspecialchars($plano['imagem']); ?>" alt="<?php echo htmlspecialchars($plano['nome']); ?>" onerror="this.src='img/planos/default.png';">
                            </div>
                            
                            <div class="plan-card-body">
                                <div>
                                    <small class="code-font text-success d-block mb-1">// modalidade</small>
                                    <h5 class="fw-bold text-white mb-2"><?php echo htmlspecialchars($plano['nome']); ?></h5>
                                    <p class="text-secondary small mb-3" style="min-height: 40px;"><?php echo htmlspecialchars($plano['descricao']); ?></p>
                                </div>
                                <div class="border-top border-secondary pt-3 mt-auto">
                                    <span class="code-font text-success fw-bold fs-5">
                                        R$ <?php echo number_format($plano['valor'], 2, ',', '.'); ?><small class="text-muted" style="font-size:0.75rem;">/mês</small>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php $primeiro = false; endforeach; ?>
            <?php else: ?>
                <div class="col-12 text-center text-secondary py-5 border border-secondary border-dashed rounded">
                    // Nenhum plano ativo cadastrado no sistema neste momento.
                </div>
            <?php endif; ?>
        </div>

        <div class="row">
            <div class="col-lg-5 mb-4">
                <div class="finance-card text-center d-flex flex-column justify-content-center py-5">
                    <h5 class="code-font text-success mb-4">// 02.executar_checkout</h5>
                    <p class="mb-1 text-secondary small">Você está assinando o plano:</p>
                    <h4 id="display-nome" class="fw-bold text-white mb-2">---</h4>
                    <h2 id="display-valor" class="code-font text-success fw-bold mb-4">R$ 0,00</h2>
                    
                    <form action="gerar_fatura.php" method="POST" id="form-checkout">
                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                        <input type="hidden" name="nome_plano" id="input-nome">
                        <input type="hidden" name="valor_plano" id="input-valor">
                        <input type="hidden" name="link_pagamento" id="input-link">
                        
                        <button type="submit" id="btn-checkout" class="btn btn-pay btn-lg code-font w-100 py-3 disabled">
                            ⚡ IR PARA O PAGAMENTO DIRETO
                        </button>
                    </form>
                    
                    <small class="text-secondary d-block mt-3" style="font-size:0.75rem;">🔒 Link seguro de valor fixo associado à conta bancária da Kernel.</small>
                </div>
            </div>

            <div class="col-lg-7 mb-4">
                <div class="finance-card">
                    <h5 class="code-font text-success border-bottom border-secondary pb-2 mb-3">// historico_de_mensalidades</h5>
                    <div class="table-responsive">
                        <table class="table table-dark table-hover table-kernel text-center align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Mês Ref.</th>
                                    <th>Valor</th>
                                    <th>Data de Pago</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($minhas_mensalidades)): ?>
                                    <?php foreach ($minhas_mensalidades as $minha): ?>
                                        <tr>
                                            <td class="code-font"><?php echo htmlspecialchars($minha['mes_referencia']); ?></td>
                                            <td>R$ <?php echo number_format($minha['valor'], 2, ',', '.'); ?></td>
                                            <td class="small text-secondary"><?php echo !empty($minha['data_pago']) ? date('d/m/Y H:i', strtotime($minha['data_pago'])) : '--'; ?></td>
                                            <td><span class="<?php echo ($minha['status_pagamento'] === 'pago') ? 'status-badge-pago' : 'status-badge-pendente'; ?>"><?php echo strtoupper($minha['status_pagamento']); ?></span></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr><td colspan="4" class="text-secondary p-4">// Nenhuma fatura vinculada ao seu ID de atleta.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
    function atualizarCheckout(card) {
        if (!card) return;
        document.querySelectorAll('.plan-card').forEach(c => c.classList.remove('selected'));
        card.classList.add('selected');
        
        const nomePlano = card.getAttribute('data-nome');
        const valorPlano = card.getAttribute('data-valor');
        const urlPlano = card.getAttribute('data-url');
        
        document.getElementById('display-nome').innerText = nomePlano;
        document.getElementById('display-valor').innerText = 'R$ ' + valorPlano;
        
        document.getElementById('input-nome').value = nomePlano;
        document.getElementById('input-valor').value = valorPlano;
        document.getElementById('input-link').value = urlPlano;
        
        const btnCheckout = document.getElementById('btn-checkout');
        btnCheckout.classList.remove('disabled');
    }

    window.addEventListener('DOMContentLoaded', () => {
        const primeiroCard = document.querySelector('.plan-card');
        if (primeiroCard) {
            atualizarCheckout(primeiroCard);
        }
    });

    document.querySelectorAll('.plan-card').forEach(card => {
        card.addEventListener('click', function() {
            atualizarCheckout(this);
        });
    });
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>