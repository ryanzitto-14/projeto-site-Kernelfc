<?php
if (!session_id()) session_start();

require_once 'conexao.php';

$is_diretoria = (isset($_SESSION['logado']) && $_SESSION['logado'] === true && isset($_SESSION['usuario_nivel']) && $_SESSION['usuario_nivel'] === 'diretoria');

$mensagem = "";
$status_msg = "";

// PROCESSAR EDICÃO E EXCLUSÃO DIRETAMENTE
if ($is_diretoria && $_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // OPERAÇÃO EXCLUIR
    if (isset($_POST['excluir_produto'])) {
        $id = intval($_POST['id']);
        try {
            // Busca o caminho da imagem antiga para apagá-la do servidor físico
            $stmt_img = $pdo->prepare("SELECT imagem FROM produtos WHERE id = :id");
            $stmt_img->execute([':id' => $id]);
            $prod_img = $stmt_img->fetch(PDO::FETCH_ASSOC);
            if ($prod_img && file_exists($prod_img['imagem'])) {
                unlink($prod_img['imagem']);
            }

            $stmt = $pdo->prepare("DELETE FROM produtos WHERE id = :id");
            $stmt->execute([':id' => $id]);
            $mensagem = "PRODUTO_REMOVIDO_SUCESSO();";
            $status_msg = "success";
        } catch (PDOException $e) {
            $mensagem = "ERRO_DELETAR: " . $e->getMessage();
            $status_msg = "danger";
        }
    }

    // OPERAÇÃO EDITAR
    if (isset($_POST['editar_produto'])) {
        $id = intval($_POST['id']);
        $nome = trim($_POST['nome']);
        $descricao = trim($_POST['descricao']);
        $categoria = $_POST['categoria'];
        $status_produto = $_POST['status_produto'];
        $botao_texto = trim($_POST['botao_texto']);
        $preco = floatval($_POST['preco']);
        $estoque = intval($_POST['estoque']);

        try {
            // Se uma nova imagem foi enviada
            if (isset($_FILES['imagem']) && $_FILES['imagem']['error'] === UPLOAD_ERR_OK) {
                $fileTmpPath = $_FILES['imagem']['tmp_name'];
                $fileName = $_FILES['imagem']['name'];
                $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
                
                if (in_array($fileExtension, ['jpg', 'jpeg', 'png', 'webp'])) {
                    $newFileName = md5(time() . $fileName) . '.' . $fileExtension;
                    $dest_path = 'img/' . $newFileName;
                    
                    if (move_uploaded_file($fileTmpPath, $dest_path)) {
                        // Remove a imagem antiga
                        $stmt_old = $pdo->prepare("SELECT imagem FROM produtos WHERE id = :id");
                        $stmt_old->execute([':id' => $id]);
                        $old_img = $stmt_old->fetch(PDO::FETCH_ASSOC);
                        if ($old_img && file_exists($old_img['imagem'])) {
                            unlink($old_img['imagem']);
                        }

                        $stmt = $pdo->prepare("UPDATE produtos SET nome=:nome, descricao=:descricao, categoria=:categoria, status_produto=:status_produto, botao_texto=:botao_texto, preco=:preco, estoque=:estoque, imagem=:imagem WHERE id=:id");
                        $stmt->execute([
                            ':nome' => $nome, ':descricao' => $descricao, ':categoria' => $categoria,
                            ':status_produto' => $status_produto, ':botao_texto' => $botao_texto,
                            ':preco' => $preco, ':estoque' => $estoque, ':imagem' => $dest_path, ':id' => $id
                        ]);
                    }
                }
            } else {
                // Atualiza sem mexer na imagem existente
                $stmt = $pdo->prepare("UPDATE produtos SET nome=:nome, descricao=:descricao, categoria=:categoria, status_produto=:status_produto, botao_texto=:botao_texto, preco=:preco, estoque=:estoque WHERE id=:id");
                $stmt->execute([
                    ':nome' => $nome, ':descricao' => $descricao, ':categoria' => $categoria,
                    ':status_produto' => $status_produto, ':botao_texto' => $botao_texto,
                    ':preco' => $preco, ':estoque' => $estoque, ':id' => $id
                ]);
            }
            $mensagem = "PRODUTO_ATUALIZADO_SUCESSO();";
            $status_msg = "success";
        } catch (PDOException $e) {
            $mensagem = "ERRO_ATUALIZAR: " . $e->getMessage();
            $status_msg = "danger";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Store // A.A.A. Kernel</title>
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

        .product-card {
            background-color: var(--dark-card);
            border: 1px solid #222;
            border-radius: 12px;
            overflow: hidden;
            transition: all 0.3s ease;
            position: relative;
        }
        .product-card:hover {
            border-color: var(--tech-green);
            box-shadow: 0 0 20px rgba(0, 255, 102, 0.1);
            transform: translateY(-5px);
        }
        .product-image-wrapper {
            height: 280px; width: 100%; position: relative; overflow: hidden; background-color: #111; 
        }
        .product-img { width: 100%; height: 100%; object-fit: cover; object-position: center; transition: transform 0.5s ease; }
        .product-card:hover .product-img { transform: scale(1.05); }

        .status-badge-store {
            position: absolute; top: 15px; right: 15px; font-size: 0.75rem; font-weight: 700; padding: 4px 12px; border-radius: 20px; letter-spacing: 1px; z-index: 10; box-shadow: 0 4px 10px rgba(0,0,0,0.5);
        }
        .badge-available { background-color: var(--tech-green); color: #000; border: 1px solid var(--tech-green); }
        .badge-soon { background-color: #ffc107; color: #000; border: 1px solid #ffc107; }
        .badge-unavailable { background-color: #dc3545; color: #fff; border: 1px solid #dc3545; }

        .btn-kernel { background: transparent; color: var(--tech-green); border: 2px solid var(--tech-green); font-weight: 600; }
        .btn-kernel:hover { background: var(--tech-green); color: var(--dark-bg); }
        .btn-disabled-kernel { background: #151515; color: #555; border: 1px solid #222; cursor: not-allowed; font-weight: 600; }

        .adm-actions-wrapper { position: absolute; top: 15px; left: 15px; z-index: 20; display: flex; gap: 5px; }
        .form-control-kernel, .form-select-kernel { background-color: #0D0D0D !important; border: 1px solid #333 !important; color: var(--white-text) !important; }
        
        .product-card h5, .product-card p { word-break: break-word; overflow-wrap: break-word; }
        @media (max-width: 575.98px) { .product-image-wrapper { height: 240px; } }
    </style>
</head>
<body>

    <?php include 'navbar.php'; ?>

    <div class="container py-5 text-center">
        <h2 class="fw-bold code-font text-white px-2">catálogo_produtos();</h2>
        <p class="text-secondary mx-auto px-3" style="max-width: 600px;">
            Dê uma olhada nos mantos oficiais e acessórios da nossa comunidade.
        </p>
        <?php if($is_diretoria): ?>
            <a href="cadastrar_produto.php" class="btn btn-sm btn-kernel code-font mt-2">[+] injetar_novo_produto();</a>
        <?php endif; ?>
        <hr class="border-secondary my-4 mx-3">
    </div>

    <div class="container pb-5">
        <?php if(!empty($mensagem)): ?>
            <div class="alert alert-<?php echo $status_msg; ?> code-font small mb-4 mx-3"><?php echo $mensagem; ?></div>
        <?php endif; ?>

        <div class="row g-4 justify-content-center">
            <?php
            try {
                $stmt = $pdo->query("SELECT * FROM produtos ORDER BY id DESC");
                $produtos = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                if (count($produtos) > 0) {
                    foreach ($produtos as $prod) {
                        
                        if ($prod['status_produto'] === 'available') {
                            $badge_class = 'badge-available';
                            $badge_nome  = 'DISPONÍVEL';
                            $btn_html = '<button class="btn btn-kernel w-100 code-font mt-auto">' . htmlspecialchars($prod['botao_texto']) . '</button>';
                        } elseif ($prod['status_produto'] === 'soon') {
                            $badge_class = 'badge-soon';
                            $badge_nome  = 'EM BREVE';
                            $btn_html = '<button class="btn btn-disabled-kernel w-100 code-font mt-auto" disabled>' . htmlspecialchars($prod['botao_texto']) . '</button>';
                        } else {
                            $badge_class = 'badge-unavailable';
                            $badge_nome  = 'INDISPONÍVEL';
                            $btn_html = '<button class="btn btn-disabled-kernel w-100 code-font mt-auto" disabled>' . htmlspecialchars($prod['botao_texto']) . '</button>';
                        }

                        echo '<div class="col-sm-6 col-lg-4">
                            <div class="product-card h-100 d-flex flex-column">';
                                
                                if($is_diretoria) {
                                    echo '<div class="adm-actions-wrapper">
                                        <button class="btn btn-sm btn-warning py-0 px-2 btn-edit-prod" data-bs-toggle="modal" data-bs-target="#modalEditProd" 
                                            data-id="'.$prod['id'].'"
                                            data-nome="'.htmlspecialchars($prod['nome']).'"
                                            data-desc="'.htmlspecialchars($prod['descricao']).'"
                                            data-cat="'.htmlspecialchars($prod['categoria']).'"
                                            data-status="'.$prod['status_produto'].'"
                                            data-btn="'.htmlspecialchars($prod['botao_texto']).'"
                                            data-preco="'.$prod['preco'].'"
                                            data-estoque="'.$prod['estoque'].'">[E]</button>
                                        <form action="" method="POST" onsubmit="return confirm(\'Deletar produto permanentemente?\')">
                                            <input type="hidden" name="id" value="'.$prod['id'].'">
                                            <button type="submit" name="excluir_produto" class="btn btn-sm btn-danger py-0 px-2">[X]</button>
                                        </form>
                                    </div>';
                                }

                                echo '<div class="product-image-wrapper">
                                    <span class="status-badge-store ' . $badge_class . '">' . $badge_nome . '</span>
                                    <img src="' . htmlspecialchars($prod['imagem']) . '" alt="' . htmlspecialchars($prod['nome']) . '" class="product-img">
                                </div>
                                <div class="p-4 d-flex flex-column flex-grow-1">
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <span class="code-font text-success small">// ' . htmlspecialchars($prod['categoria']) . '</span>
                                        <span class="text-white small code-font fw-bold">Qtd: ' . intval($prod['estoque']) . '</span>
                                    </div>
                                    <h5 class="fw-bold text-white mb-2">' . htmlspecialchars($prod['nome']) . '</h5>
                                    <p class="text-secondary small flex-grow-1 mb-3">' . htmlspecialchars($prod['descricao']) . '</p>
                                    
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <span class="text-secondary small">Valor total:</span>
                                        <span class="text-success fw-bold fs-5 code-font">R$ ' . number_format($prod['preco'], 2, ',', '.') . '</span>
                                    </div>

                                    ' . $btn_html . '
                                </div>
                            </div>
                        </div>';
                    }
                } else {
                    echo '<div class="col-12 text-center text-secondary code-font py-5">// nenhum_produto_encontrado_no_banco_de_dados();</div>';
                }
            } catch (PDOException $e) {
                echo '<div class="col-12 text-center text-danger code-font py-5">ERRO_AO_CONECTAR_LOJA: ' . htmlspecialchars($e->getMessage()) . '</div>';
            }
            ?>
        </div>
    </div>

    <?php if($is_diretoria): ?>
    <div class="modal fade" id="modalEditProd" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content bg-dark text-white border-secondary">
                <div class="modal-header border-secondary">
                    <h5 class="modal-title code-font text-warning">// overwrite_product_data();</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form action="" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="id" id="edit_id">
                    <div class="modal-body row g-3">
                        <div class="col-12">
                            <label class="form-label text-secondary small">Nome do Produto</label>
                            <input type="text" name="nome" id="edit_nome" class="form-control form-control-kernel" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-secondary small">Categoria</label>
                            <select name="categoria" id="edit_cat" class="form-select form-select-kernel" required>
                                <option value="vestuário">vestuário</option>
                                <option value="acessórios">acessórios</option>
                                <option value="extras">extras</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-secondary small">Status</label>
                            <select name="status_produto" id="edit_status" class="form-select form-select-kernel" required>
                                <option value="available">DISPONÍVEL</option>
                                <option value="soon">EM BREVE</option>
                                <option value="unavailable">INDISPONÍVEL</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-secondary small">Preço (R$)</label>
                            <input type="number" name="preco" id="edit_preco" step="0.01" min="0" class="form-control form-control-kernel" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-secondary small">Estoque</label>
                            <input type="number" name="estoque" id="edit_estoque" min="0" class="form-control form-control-kernel" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label text-secondary small">Texto do Botão</label>
                            <input type="text" name="botao_texto" id="edit_btn" class="form-control form-control-kernel" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label text-secondary small">Descrição</label>
                            <textarea name="descricao" id="edit_desc" rows="2" class="form-control form-control-kernel" required></textarea>
                        </div>
                        <div class="col-12">
                            <label class="form-label text-secondary small">Substituir Imagem (Opcional)</label>
                            <input type="file" name="imagem" class="form-control form-control-kernel" accept="image/*">
                        </div>
                    </div>
                    <div class="modal-footer border-secondary">
                        <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Fechar</button>
                        <button type="submit" name="editar_produto" class="btn btn-sm btn-warning text-dark fw-bold">Atualizar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <script>
        const editButtons = document.querySelectorAll('.btn-edit-prod');
        editButtons.forEach(button => {
            button.addEventListener('click', function() {
                document.getElementById('edit_id').value = this.getAttribute('data-id');
                document.getElementById('edit_nome').value = this.getAttribute('data-nome');
                document.getElementById('edit_desc').value = this.getAttribute('data-desc');
                document.getElementById('edit_cat').value = this.getAttribute('data-cat');
                document.getElementById('edit_status').value = this.getAttribute('data-status');
                document.getElementById('edit_btn').value = this.getAttribute('data-btn');
                document.getElementById('edit_preco').value = this.getAttribute('data-preco');
                document.getElementById('edit_estoque').value = this.getAttribute('data-estoque');
            });
        });
    </script>
    <?php endif; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>