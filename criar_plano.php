<?php
ini_set('session.cookie_path', '/');
if (!session_id()) session_start();

if (!isset($_SESSION['logado']) || $_SESSION['logado'] !== true || $_SESSION['usuario_nivel'] !== 'diretoria') {
    echo "<script>
            alert('Acesso restrito apenas para membros da diretoria.');
            window.location.href = 'pagamentos.php';
          </script>";
    exit;
}

require_once 'conexao.php';

$id_editar = null;
$plano_editar = ['nome' => '', 'valor' => '', 'descricao' => '', 'imagem' => '', 'link_pagamento' => ''];

if (isset($_GET['editar'])) {
    $id_editar = intval($_GET['editar']);
    $stmt = $pdo->prepare("SELECT * FROM planos WHERE id = :id");
    $stmt->execute([':id' => $id_editar]);
    $res = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($res) {
        $plano_editar = $res;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = trim($_POST['nome']);
    $valor = str_replace(',', '.', trim($_POST['valor']));
    $descricao = trim($_POST['descricao']);
    $link_pagamento = trim($_POST['link_pagamento']);
    $id_plano = isset($_POST['id_plano']) ? intval($_POST['id_plano']) : null;
    
    $imagem_final = isset($_POST['imagem_atual']) ? $_POST['imagem_atual'] : 'img/planos/default.png';

    if (isset($_FILES['imagem']) && $_FILES['imagem']['error'] === UPLOAD_ERR_OK) {
        $extensao = strtolower(pathinfo($_FILES['imagem']['name'], PATHINFO_EXTENSION));
        $extesoes_permitidas = ['jpg', 'jpeg', 'png', 'webp'];

        if (in_array($extensao, $extesoes_permitidas)) {
            $diretorio_destino = 'uploads/planos/';
            if (!is_dir($diretorio_destino)) {
                mkdir($diretorio_destino, 0755, true);
            }

            $novo_nome = md5(uniqid(rand(), true)) . '.' . $extensao;
            $caminho_completo = $diretorio_destino . $novo_nome;

            if (move_uploaded_file($_FILES['imagem']['tmp_name'], $caminho_completo)) {
                $imagem_final = $caminho_completo;
            }
        }
    }

    if (!empty($nome) && !empty($valor) && !empty($link_pagamento)) {
        if ($id_plano) {
            $stmt = $pdo->prepare("UPDATE planos SET nome = :nome, valor = :valor, descricao = :descricao, imagem = :imagem, link_pagamento = :link_pagamento WHERE id = :id");
            $stmt->execute([
                ':nome' => $nome,
                ':valor' => $valor,
                ':descricao' => $descricao,
                ':imagem' => $imagem_final,
                ':link_pagamento' => $link_pagamento,
                ':id' => $id_plano
            ]);
            header("Location: criar_plano.php?sucesso=atualizado");
        } else {
            $stmt = $pdo->prepare("INSERT INTO planos (nome, valor, descricao, imagem, link_pagamento) VALUES (:nome, :valor, :descricao, :imagem, :link_pagamento)");
            $stmt->execute([
                ':nome' => $nome,
                ':valor' => $valor,
                ':descricao' => $descricao,
                ':imagem' => $imagem_final,
                ':link_pagamento' => $link_pagamento
            ]);
            header("Location: criar_plano.php?sucesso=cadastrado");
        }
        exit;
    } else {
        $erro = "Por favor, preencha todos os campos obrigatórios.";
    }
}

if (isset($_GET['deletar'])) {
    $id_deletar = intval($_GET['deletar']);
    
    $stmt_img = $pdo->prepare("SELECT imagem FROM planos WHERE id = :id");
    $stmt_img->execute([':id' => $id_deletar]);
    $img_linha = $stmt_img->fetch(PDO::FETCH_ASSOC);
    if ($img_linha && file_exists($img_linha['imagem']) && $img_linha['imagem'] !== 'img/planos/default.png') {
        unlink($img_linha['imagem']);
    }

    $stmt = $pdo->prepare("DELETE FROM planos WHERE id = :id");
    $stmt->execute([':id' => $id_deletar]);
    header("Location: criar_plano.php?sucesso=deletado");
    exit;
}

$planos = $pdo->query("SELECT * FROM planos ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Planos - Atlética Kernel</title>
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
        body { 
            background-color: var(--dark-bg); 
            color: var(--white-text); 
            font-family: 'Poppins', sans-serif; 
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        .code-font { font-family: 'Fira Code', monospace; }
        .finance-card {
            background-color: var(--dark-card);
            border: 1px solid rgba(0, 255, 102, 0.15);
            border-radius: 12px;
            padding: 2rem;
            margin-bottom: 2rem;
        }
        .form-control {
            background-color: #0D0D0D;
            border: 1px solid #333;
            color: #FFF;
        }
        .form-control:focus {
            background-color: #141414;
            border-color: var(--tech-green);
            color: #FFF;
            box-shadow: 0 0 10px rgba(0, 255, 102, 0.2);
        }
        .table-kernel { background-color: #0D0D0D !important; color: var(--white-text) !important; border-color: #333 !important; }
        .table-kernel th { background-color: #141414 !important; color: var(--tech-green) !important; border-bottom: 2px solid var(--tech-green) !important; }
        
        @media (max-width: 768px) {
            .finance-card {
                padding: 1.25rem 1rem;
            }
            .d-flex.justify-content-between {
                flex-direction: column;
                align-items: flex-start !important;
                gap: 15px;
            }
            .d-flex.justify-content-between a {
                width: 100%;
                text-align: center;
            }
            h2 {
                font-size: 1.4rem;
            }
        }
    </style>
</head>
<body>

    <?php include_once 'navbar.php'; ?>

    <div class="container my-5 pt-4">
        <div class="mb-4 d-flex justify-content-between align-items-center">
            <div>
                <h2 class="fw-bold code-font text-success">&lt;GERENCIAR_PLANOS_KERNEL /&gt;</h2>
                <p class="text-secondary">Faça upload direto de imagens para compor os cards de planos de maneira automatizada.</p>
            </div>
            <a href="pagamentos.php" class="btn btn-outline-secondary code-font">&lt; Voltar para Pagamentos</a>
        </div>

        <?php if (isset($_GET['sucesso'])): ?>
            <div class="alert alert-success bg-dark text-success border-success mb-4">✓ Operação realizada com sucesso!</div>
        <?php endif; ?>

        <div class="finance-card border-warning">
            <h4 class="code-font text-warning mb-4">
                <?php echo $id_editar ? '📝 Editar Plano Selecionado' : '✨ Criar Novo Plano Esportivo'; ?>
            </h4>
            
            <form action="criar_plano.php" method="POST" enctype="multipart/form-data">
                <?php if ($id_editar): ?>
                    <input type="hidden" name="id_plano" value="<?php echo $id_editar; ?>">
                    <input type="hidden" name="imagem_atual" value="<?php echo htmlspecialchars($plano_editar['imagem']); ?>">
                <?php endif; ?>

                <div class="row">
                    <div class="col-md-5 mb-3">
                        <label class="form-label text-secondary small">Nome do Plano / Modalidade *</label>
                        <input type="text" name="nome" class="form-control" placeholder="Ex: Futsal Masculino, Basquete" value="<?php echo htmlspecialchars($plano_editar['nome']); ?>" required>
                    </div>
                    
                    <div class="col-md-3 mb-3">
                        <label class="form-label text-secondary small">Valor Mensal (R$) *</label>
                        <input type="text" name="valor" class="form-control code-font" placeholder="Ex: 35.00" value="<?php echo htmlspecialchars($plano_editar['valor']); ?>" required>
                    </div>
                    
                    <div class="col-md-4 mb-3">
                        <label class="form-label text-muted code-font">upload_imagem_mockup (JPG, PNG, WEBP) *</label>
                        <input type="file" name="imagem" class="form-control" accept="image/*" <?php echo $id_editar ? '' : 'required'; ?>>
                        <?php if ($id_editar && !empty($plano_editar['imagem'])): ?>
                            <small class="text-warning d-block mt-1">Deixe em branco para manter a imagem atual.</small>
                        <?php endif; ?>
                    </div>
                    
                    <div class="col-12 mb-3">
                        <label class="form-label text-secondary small">Link Seguro de Pagamento *</label>
                        <input type="url" name="link_pagamento" class="form-control code-font" placeholder="https://linkdepagamento.com/..." value="<?php echo htmlspecialchars($plano_editar['link_pagamento']); ?>" required>
                    </div>
                    
                    <div class="col-12 mb-4">
                        <label class="form-label text-secondary small">Descrição do que está incluso *</label>
                        <textarea name="descricao" class="form-control" rows="3" placeholder="Descreva os detalhes e benefícios do plano..." required><?php echo htmlspecialchars($plano_editar['descricao']); ?></textarea>
                    </div>
                </div>

                <div class="d-flex flex-wrap gap-2">
                    <button type="submit" class="btn btn-success fw-bold px-4">
                        <?php echo $id_editar ? '💾 Salvar Alterações' : '⚡ Publicar Novo Plano'; ?>
                    </button>
                    <?php if ($id_editar): ?>
                        <a href="criar_plano.php" class="btn btn-outline-secondary">Cancelar Edição</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <div class="finance-card">
            <h5 class="code-font text-success mb-3">// planos_ativos_no_sistema</h5>
            <div class="table-responsive">
                <table class="table table-dark table-hover table-kernel align-middle text-center small">
                    <thead>
                        <tr>
                            <th>Preview</th>
                            <th>Modalidade / Plano</th>
                            <th>Valor</th>
                            <th>Caminho do Arquivo Salvo</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($planos)): ?>
                            <?php foreach ($planos as $p): ?>
                                <tr>
                                    <td>
                                        <div style="width: 50px; height: 50px; background-color: #ECECEC; border-radius: 4px; overflow: hidden; display:inline-block;">
                                            <img src="<?php echo htmlspecialchars($p['imagem']); ?>" style="width:100%; height:100%; object-fit:cover;" onerror="this.src='img/planos/default.png';">
                                        </div>
                                    </td>
                                    <td class="fw-bold text-start"><?php echo htmlspecialchars($p['nome']); ?></td>
                                    <td class="code-font text-success">R$ <?php echo number_format($p['valor'], 2, ',', '.'); ?></td>
                                    <td class="code-font text-secondary text-start"><?php echo htmlspecialchars($p['imagem']); ?></td>
                                    <td>
                                        <div class="d-flex flex-column gap-1 align-items-center">
                                            <a href="criar_plano.php?editar=<?php echo $p['id']; ?>" class="btn btn-sm btn-warning py-1 px-3 code-font w-100" style="font-size:0.75rem; max-width: 80px;">Editar</a>
                                            <a href="criar_plano.php?deletar=<?php echo $p['id']; ?>" class="btn btn-sm btn-outline-danger py-1 px-2 code-font w-100" style="font-size:0.75rem; max-width: 80px;" onclick="return confirm('Tem certeza que deseja excluir este plano?')">Excluir</a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="5" class="text-secondary p-4">// Nenhum plano cadastrado.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>