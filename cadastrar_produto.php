<?php
if (!session_id()) session_start();

// TRAVA DE SEGURANÇA ALINHADA PERFEITAMENTE COM O SEU PAINEL ADM
if (!isset($_SESSION['logado']) || $_SESSION['logado'] !== true || $_SESSION['usuario_nivel'] !== 'diretoria') {
    header('Location: login.php');
    exit;
}

require_once 'conexao.php';

$mensagem = "";
$status_msg = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = trim($_POST['nome']);
    $descricao = trim($_POST['descricao']);
    $categoria = $_POST['categoria'];
    $status_produto = $_POST['status_produto'];
    $botao_texto = trim($_POST['botao_texto']);
    $preco = floatval($_POST['preco']);
    $estoque = intval($_POST['estoque']);
    
    if (isset($_FILES['imagem']) && $_FILES['imagem']['error'] === UPLOAD_ERR_OK) {
        $fileTmpPath = $_FILES['imagem']['tmp_name'];
        $fileName = $_FILES['imagem']['name'];
        $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp'];
        
        if (in_array($fileExtension, $allowedExtensions)) {
            $newFileName = md5(time() . $fileName) . '.' . $fileExtension;
            $uploadFileDir = 'img/';
            $dest_path = $uploadFileDir . $newFileName;
            
            if (!is_dir($uploadFileDir)) {
                mkdir($uploadFileDir, 0755, true);
            }
            
            if (move_uploaded_file($fileTmpPath, $dest_path)) {
                try {
                    $stmt = $pdo->prepare("INSERT INTO produtos (nome, descricao, categoria, imagem, status_produto, botao_texto, preco, estoque) VALUES (:nome, :descricao, :categoria, :imagem, :status_produto, :botao_texto, :preco, :estoque)");
                    $stmt->execute([
                        ':nome' => $nome,
                        ':descricao' => $descricao,
                        ':categoria' => $categoria,
                        ':imagem' => $dest_path, 
                        ':status_produto' => $status_produto,
                        ':botao_texto' => $botao_texto,
                        ':preco' => $preco,
                        ':estoque' => $estoque
                    ]);
                    
                    $mensagem = "PRODUTO_CADASTRADO_COM_SUCESSO();";
                    $status_msg = "success";
                } catch (PDOException $e) {
                    $mensagem = "ERRO_BANCO_DADOS: " . $e->getMessage();
                    $status_msg = "danger";
                }
            } else {
                $mensagem = "ERRO_AO_MOVER_ARQUIVO();";
                $status_msg = "danger";
            }
        } else {
            $mensagem = "EXTENSAO_NAO_PERMITIDA (Apenas JPG, JPEG, PNG e WEBP);";
            $status_msg = "danger";
        }
    } else {
        $mensagem = "POR_FAVOR_SELECIONE_UMA_IMAGEM();";
        $status_msg = "danger";
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ADM // Cadastrar Produto</title>
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
        
        .form-kernel {
            background-color: var(--dark-card);
            border: 1px solid #222;
            border-radius: 12px;
            padding: 2rem;
            box-shadow: 0 0 25px rgba(0, 0, 0, 0.5);
            width: 100%;
        }
        .form-control-kernel, .form-select-kernel {
            background-color: #0D0D0D !important;
            border: 1px solid #333 !important;
            color: var(--white-text) !important;
        }
        .form-control-kernel:focus, .form-select-kernel:focus {
            border-color: var(--tech-green) !important;
            box-shadow: 0 0 10px rgba(0, 255, 102, 0.2) !important;
        }
        .btn-kernel {
            background: transparent; color: var(--tech-green); border: 2px solid var(--tech-green); font-weight: 600;
        }
        .btn-kernel:hover { background: var(--tech-green); color: var(--dark-bg); }

        @media (max-width: 767.98px) {
            .form-kernel { padding: 1.25rem; }
            h3.fw-bold { font-size: 1.25rem; word-break: break-word; }
        }
    </style>
</head>
<body>

    <?php include 'navbar.php'; ?>

    <div class="container py-4 py-md-5">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h3 class="fw-bold code-font mb-0">// adicionar_novo_produto();</h3>
                    <a href="loja.php" class="btn btn-sm btn-outline-secondary code-font"><- voltar_loja();</a>
                </div>
                
                <?php if(!empty($mensagem)): ?>
                    <div class="alert alert-<?php echo $status_msg; ?> code-font small" role="alert">
                        <?php echo $mensagem; ?>
                    </div>
                <?php endif; ?>

                <form action="cadastrar_produto.php" method="POST" enctype="multipart/form-data" class="form-kernel">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label text-secondary code-font">nome_produto</label>
                            <input type="text" name="nome" class="form-control form-control-kernel" placeholder="Ex: Manto Oficial - Linha" required autocomplete="off">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label text-secondary code-font">categoria</label>
                            <select name="categoria" class="form-select form-select-kernel" required>
                                <option value="vestuário">// vestuário</option>
                                <option value="acessórios">// acessórios</option>
                                <option value="extras">// extras</option>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label text-secondary code-font">valor_venda (R$)</label>
                            <input type="number" name="preco" step="0.01" min="0" class="form-control form-control-kernel" placeholder="0.00" required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label text-secondary code-font">quantidade_estoque</label>
                            <input type="number" name="estoque" min="0" class="form-control form-control-kernel" placeholder="0" required>
                        </div>

                        <div class="col-12">
                            <label class="form-label text-secondary code-font">descrição_detalhada</label>
                            <textarea name="descricao" rows="3" class="form-control form-control-kernel" placeholder="Descreva os detalhes do produto..." required></textarea>
                        </div>

                        <div class="col-12">
                            <label class="form-label text-secondary code-font">upload_imagem_mockup (JPG, PNG, WEBP)</label>
                            <input type="file" name="imagem" class="form-control form-control-kernel" accept="image/*" required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label text-secondary code-font">status_tag</label>
                            <select name="status_produto" class="form-select form-select-kernel" required>
                                <option value="available">DISPONÍVEL (Verde)</option>
                                <option value="soon">EM BREVE (Amarelo)</option>
                                <option value="unavailable">INDISPONÍVEL (Vermelho)</option>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label text-secondary code-font">texto_do_botão</label>
                            <input type="text" name="botao_texto" class="form-control form-control-kernel" placeholder="Ex: comprar_agora();" required autocomplete="off">
                        </div>

                        <div class="col-12 mt-4">
                            <button type="submit" class="btn btn-kernel code-font w-100">salvar_e_publicar();</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>