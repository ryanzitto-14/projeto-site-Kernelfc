<?php
if (!session_id()) session_start();

require_once 'conexao.php';

// Verifica se é diretoria
$is_diretoria = (isset($_SESSION['logado']) && $_SESSION['logado'] === true && isset($_SESSION['usuario_nivel']) && $_SESSION['usuario_nivel'] === 'diretoria');

$mensagem = "";
$status_msg = "";

// PROCESSAR ADIÇÃO E EXCLUSÃO DIRETAMENTE (SOMENTE DIRETORIA)
if ($is_diretoria && $_SERVER['REQUEST_METHOD'] === 'POST') {

    // OPERAÇÃO EXCLUIR MÍDIA
    if (isset($_POST['excluir_midia'])) {
        $id = intval($_POST['id']);
        try {
            // Busca o caminho do arquivo para apagar do servidor
            $stmt_file = $pdo->prepare("SELECT arquivo FROM galeria WHERE id = :id");
            $stmt_file->execute([':id' => $id]);
            $midia = $stmt_file->fetch(PDO::FETCH_ASSOC);

            if ($midia && file_exists($midia['arquivo'])) {
                unlink($midia['arquivo']);
            }

            $stmt = $pdo->prepare("DELETE FROM galeria WHERE id = :id");
            $stmt->execute([':id' => $id]);
            $mensagem = "MIDIA_REMOVIDA_SUCESSO();";
            $status_msg = "success";
        } catch (PDOException $e) {
            $mensagem = "ERRO_DELETAR: " . $e->getMessage();
            $status_msg = "danger";
        }
    }

    // OPERAÇÃO ADICIONAR MÍDIA
    if (isset($_POST['adicionar_midia'])) {
        $titulo = trim($_POST['titulo']);
        $descricao = trim($_POST['descricao']);

        if (isset($_FILES['arquivo']) && $_FILES['arquivo']['error'] === UPLOAD_ERR_OK) {
            $fileTmpPath = $_FILES['arquivo']['tmp_name'];
            $fileName = $_FILES['arquivo']['name'];
            $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

            $extensoes_imagem = ['jpg', 'jpeg', 'png', 'webp'];
            $extensoes_video = ['mp4', 'webm'];

            $tipo = '';
            if (in_array($fileExtension, $extensoes_imagem)) {
                $tipo = 'imagem';
            } elseif (in_array($fileExtension, $extensoes_video)) {
                $tipo = 'video';
            }

            if ($tipo !== '') {
                // Cria pasta galeria se não existir
                if (!is_dir('img/galeria')) {
                    mkdir('img/galeria', 0777, true);
                }

                $newFileName = md5(time() . $fileName) . '.' . $fileExtension;
                $dest_path = 'img/galeria/' . $newFileName;

                if (move_uploaded_file($fileTmpPath, $dest_path)) {
                    try {
                        $stmt = $pdo->prepare("INSERT INTO galeria (titulo, descricao, tipo, arquivo) VALUES (:titulo, :descricao, :tipo, :arquivo)");
                        $stmt->execute([
                            ':titulo' => $titulo,
                            ':descricao' => $descricao,
                            ':tipo' => $tipo,
                            ':arquivo' => $dest_path
                        ]);
                        $mensagem = "MIDIA_INJETADA_SUCESSO();";
                        $status_msg = "success";
                    } catch (PDOException $e) {
                        $mensagem = "ERRO_BANCO: " . $e->getMessage();
                        $status_msg = "danger";
                    }
                } else {
                    $mensagem = "ERRO_UPLOADING_FILE();";
                    $status_msg = "danger";
                }
            } else {
                $mensagem = "FORMATO_INVALIDO_SOMENTE_IMAGENS_E_VIDEOS();";
                $status_msg = "warning";
            }
        } else {
            $mensagem = "SELECIONE_UM_ARQUIVO_VALIDO();";
            $status_msg = "warning";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Galeria // A.A.A. Kernel</title>
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

        .gallery-card {
            background-color: var(--dark-card);
            border: 1px solid #222;
            border-radius: 12px;
            overflow: hidden;
            transition: all 0.3s ease;
            position: relative;
        }
        .gallery-card:hover {
            border-color: var(--tech-green);
            box-shadow: 0 0 20px rgba(0, 255, 102, 0.1);
            transform: translateY(-5px);
        }
        .media-wrapper {
            height: 260px; width: 100%; position: relative; overflow: hidden; background-color: #000;
        }
        .media-content { width: 100%; height: 100%; object-fit: cover; }

        .btn-kernel { background: transparent; color: var(--tech-green); border: 2px solid var(--tech-green); font-weight: 600; }
        .btn-kernel:hover { background: var(--tech-green); color: var(--dark-bg); }

        .adm-actions-wrapper { position: absolute; top: 15px; left: 15px; z-index: 20; display: flex; gap: 5px; }
        .form-control-kernel, .form-select-kernel { background-color: #0D0D0D !important; border: 1px solid #333 !important; color: var(--white-text) !important; }
        
        .type-badge {
            position: absolute; top: 15px; right: 15px; font-size: 0.7rem; font-weight: 700; padding: 4px 10px; border-radius: 20px; z-index: 10;
            background-color: rgba(0,0,0,0.7); color: var(--tech-green); border: 1px solid var(--tech-green);
        }
    </style>
</head>
<body>

    <?php include 'navbar.php'; ?>

    <div class="container py-5 text-center">
        <h2 class="fw-bold code-font text-white px-2">galeria_midias();</h2>
        <p class="text-secondary mx-auto px-3" style="max-width: 600px;">
            Confira as fotos e vídeos dos eventos e momentos marcantes da nossa atlética.
        </p>
        <?php if($is_diretoria): ?>
            <button class="btn btn-sm btn-kernel code-font mt-2" data-bs-toggle="modal" data-bs-target="#modalAddMidia">[+] injetar_nova_midia();</button>
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
                $stmt = $pdo->query("SELECT * FROM galeria ORDER BY id DESC");
                $midias = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                if (count($midias) > 0) {
                    foreach ($midias as $item) {
                        echo '<div class="col-sm-6 col-lg-4">
                            <div class="gallery-card h-100 d-flex flex-column">';
                                
                                if($is_diretoria) {
                                    echo '<div class="adm-actions-wrapper">
                                        <form action="" method="POST" onsubmit="return confirm(\'Deletar mídia permanentemente?\')">
                                            <input type="hidden" name="id" value="'.$item['id'].'">
                                            <button type="submit" name="excluir_midia" class="btn btn-sm btn-danger py-0 px-2">[X]</button>
                                        </form>
                                    </div>';
                                }

                                echo '<div class="media-wrapper">
                                    <span class="type-badge code-font">' . strtoupper($item['tipo']) . '</span>';
                                    
                                    if($item['tipo'] === 'video') {
                                        echo '<video class="media-content" controls preload="metadata">
                                                <source src="' . htmlspecialchars($item['arquivo']) . '">
                                                Seu navegador não suporta vídeos.
                                              </video>';
                                    } else {
                                        echo '<img src="' . htmlspecialchars($item['arquivo']) . '" alt="' . htmlspecialchars($item['titulo']) . '" class="media-content">';
                                    }

                                echo '</div>
                                <div class="p-3 d-flex flex-column flex-grow-1">
                                    <h5 class="fw-bold text-white mb-1">' . htmlspecialchars($item['titulo']) . '</h5>
                                    ' . (!empty($item['descricao']) ? '<p class="text-secondary small mb-0">' . htmlspecialchars($item['descricao']) . '</p>' : '') . '
                                </div>
                            </div>
                        </div>';
                    }
                } else {
                    echo '<div class="col-12 text-center text-secondary code-font py-5">// nenhuma_midia_encontrada();</div>';
                }
            } catch (PDOException $e) {
                echo '<div class="col-12 text-center text-danger code-font py-5">ERRO_AO_CONECTAR_GALERIA: ' . htmlspecialchars($e->getMessage()) . '</div>';
            }
            ?>
        </div>
    </div>

    <!-- MODAL ADICIONAR MÍDIA (SOMENTE DIRETORIA) -->
    <?php if($is_diretoria): ?>
    <div class="modal fade" id="modalAddMidia" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content bg-dark text-white border-secondary">
                <div class="modal-header border-secondary">
                    <h5 class="modal-title code-font text-success">// inject_new_media();</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form action="" method="POST" enctype="multipart/form-data">
                    <div class="modal-body row g-3">
                        <div class="col-12">
                            <label class="form-label text-secondary small">Título da Mídia</label>
                            <input type="text" name="titulo" class="form-control form-control-kernel" placeholder="Ex: Jogos Universitários 2026" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label text-secondary small">Descrição (Opcional)</label>
                            <textarea name="descricao" rows="2" class="form-control form-control-kernel" placeholder="Breve legenda sobre a foto ou vídeo"></textarea>
                        </div>
                        <div class="col-12">
                            <label class="form-label text-secondary small">Arquivo (Imagem ou Vídeo MP4)</label>
                            <input type="file" name="arquivo" class="form-control form-control-kernel" accept="image/*,video/mp4,video/webm" required>
                        </div>
                    </div>
                    <div class="modal-footer border-secondary">
                        <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" name="adicionar_midia" class="btn btn-sm btn-kernel fw-bold">Enviar Mídia</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>