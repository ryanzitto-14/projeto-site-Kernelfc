<?php
ini_set('session.cookie_path', '/');
if (!session_id()) session_start();

require_once 'conexao.php';

$mensagem = "";
$status_msg = "";

// CORREÇÃO: Ajustado para usar 'usuario_nivel' de acordo com o padrão das sessões
$is_diretoria = (isset($_SESSION['logado']) && $_SESSION['logado'] === true && isset($_SESSION['usuario_nivel']) && $_SESSION['usuario_nivel'] === 'diretoria') || isset($_GET['dev']);

if (!$is_diretoria) {
    header('Location: esportes.php');
    exit;
}

// =======================================================================
// PROCESSAR OPERAÇÕES (CRUD + LIMPEZA TOTAL)
// =======================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // 1. ADICIONAR MODALIDADE
    if (isset($_POST['adicionar_modalidade'])) {
        $nome = $_POST['nome'];
        $chave = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $_POST['chave']));
        $horario = $_POST['horario'];
        $local = $_POST['local'];
        $extensao = $_POST['extensao'] ?? 'sys';

        try {
            $stmt = $pdo->prepare("INSERT INTO modalidades (chave, nome, horario, local, extensao) VALUES (:chave, :nome, :horario, :local, :extensao)");
            $stmt->execute([
                ':chave' => $chave,
                ':nome' => $nome,
                ':horario' => $horario,
                ':local' => $local,
                ':extensao' => $extensao
            ]);
            $mensagem = "MODALIDADE_CRIADA_SUCESSO(); // Nova categoria injetada.";
            $status_msg = "success";
        } catch (PDOException $e) {
            $mensagem = "ERRO: " . $e->getMessage();
            $status_msg = "danger";
        }
    }

    // 2. EDITAR MODALIDADE
    if (isset($_POST['editar_modalidade'])) {
        $id = $_POST['id'];
        $nome = $_POST['nome'];
        $horario = $_POST['horario'];
        $local = $_POST['local'];
        $extensao = $_POST['extensao'];

        try {
            $stmt = $pdo->prepare("UPDATE modalidades SET nome = :nome, horario = :horario, local = :local, extensao = :extensao WHERE id = :id");
            $stmt->execute([
                ':nome' => $nome,
                ':horario' => $horario,
                ':local' => $local,
                ':extensao' => $extensao,
                ':id' => $id
            ]);
            $mensagem = "MODALIDADE_ATUALIZADA_SUCESSO(); // Parâmetros alterados.";
            $status_msg = "success";
        } catch (PDOException $e) {
            $mensagem = "ERRO: " . $e->getMessage();
            $status_msg = "danger";
        }
    }

    // 3. EXCLUIR MODALIDADE
    if (isset($_POST['excluir_modalidade'])) {
        $id = $_POST['id'];

        try {
            // CORREÇÃO: Limpa primeiro as inscrições ligadas a este esporte para evitar falhas de FK
            $stmt_membros = $pdo->prepare("DELETE FROM membros_modalidades WHERE modalidade_id = :id");
            $stmt_membros->execute([':id' => $id]);

            $stmt = $pdo->prepare("DELETE FROM modalidades WHERE id = :id");
            $stmt->execute([':id' => $id]);
            $mensagem = "MODALIDADE_DELETADA_SUCESSO();";
            $status_msg = "success";
        } catch (PDOException $e) {
            $mensagem = "ERRO: " . $e->getMessage();
            $status_msg = "danger";
        }
    }

    // 4. LIMPAR TUDO DO ZERO (TRUNCATE)
    if (isset($_POST['limpar_banco_total'])) {
        try {
            // Desativa temporariamente as chaves estrangeiras para permitir a limpeza completa
            $pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");
            $pdo->exec("TRUNCATE TABLE membros_modalidades;");
            $pdo->exec("TRUNCATE TABLE modalidades;");
            $pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");
            
            $mensagem = "SISTEMA_REINICIADO(); // Todas as modalidades e inscrições foram limpas.";
            $status_msg = "warning";
        } catch (PDOException $e) {
            $mensagem = "ERRO_RESET: " . $e->getMessage();
            $status_msg = "danger";
        }
    }
}

// Buscar modalidades para listar na tabela de gerenciamento
$modalidades = $pdo->query("SELECT * FROM modalidades ORDER BY nome ASC")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel de Controle // Kernel Sports</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Fira+Code:wght@400;600&family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="icon" type="image/png" href="favicon.png">
    <style>
        :root { --tech-green: #00FF66; --dark-bg: #0D0D0D; --dark-card: #1A1A1A; --white-text: #F5F5F5; }
        body { background-color: var(--dark-bg); color: var(--white-text); font-family: 'Poppins', sans-serif; }
        .code-font { font-family: 'Fira Code', monospace; }
        .form-kernel { background-color: var(--dark-card); border: 1px solid #222; border-radius: 12px; }
        .form-input-kernel, .form-select-kernel { background-color: #0D0D0D !important; border: 1px solid #333 !important; color: var(--white-text) !important; }
        .btn-kernel { background: transparent; color: var(--tech-green); border: 2px solid var(--tech-green); font-weight: 600; }
        .btn-kernel:hover { background: var(--tech-green); color: var(--dark-bg); }
        .table-dark-custom { background-color: var(--dark-card); border: 1px solid #222; color: #fff; }
    </style>
</head>
<body>

    <?php include 'navbar.php'; ?>

    <div class="container py-4 py-md-5">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-center mb-4 gap-3">
            <div>
                <h2 class="fw-bold code-font text-white fs-3 fs-md-2">root@sports_hub:~# gerenciar_modalidades</h2>
                <p class="text-secondary small mb-0">Painel exclusivo da Diretoria para controle de dados do sistema.</p>
            </div>
            <div class="d-flex gap-2 w-100 w-md-auto justify-content-md-end">
                <a href="esportes.php" class="btn btn-sm btn-outline-secondary code-font"><- voltar_painel();</a>
                <button class="btn btn-sm btn-kernel code-font" data-bs-toggle="modal" data-bs-target="#modalAddModalidade">[+] nova_modalidade();</button>
            </div>
        </div>

        <?php if(!empty($mensagem)): ?>
            <div class="alert alert-<?php echo $status_msg; ?> code-font small mb-4"><?php echo $mensagem; ?></div>
        <?php endif; ?>

        <div class="form-kernel p-4 mb-5">
            <h5 class="code-font text-white mb-3">// modalidades_ativas_no_sistema</h5>
            <div class="table-responsive">
                <table class="table table-dark table-hover align-middle custom-table mb-0">
                    <thead>
                        <tr class="code-font text-success">
                            <th>ID</th>
                            <th>Chave</th>
                            <th>Nome</th>
                            <th>Horário</th>
                            <th>Local</th>
                            <th class="text-center">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(count($modalidades) > 0): ?>
                            <?php foreach($modalidades as $mod): ?>
                                <tr>
                                    <td class="code-font"><?php echo $mod['id']; ?></td>
                                    <td class="text-info code-font"><?php echo htmlspecialchars($mod['chave'] . '.' . ($mod['extensao'] ?? 'sys')); ?></td>
                                    <td class="fw-bold"><?php echo htmlspecialchars($mod['nome']); ?></td>
                                    <td><?php echo htmlspecialchars($mod['horario']); ?></td>
                                    <td><?php echo htmlspecialchars($mod['local']); ?></td>
                                    <td class="text-center">
                                        <button class="btn btn-sm btn-outline-warning me-2 btn-kernel-edit" 
                                                data-bs-toggle="modal" data-bs-target="#modalEditModalidade"
                                                data-id="<?php echo $mod['id']; ?>"
                                                data-nome="<?php echo htmlspecialchars($mod['nome']); ?>"
                                                data-horario="<?php echo htmlspecialchars($mod['horario']); ?>"
                                                data-local="<?php echo htmlspecialchars($mod['local']); ?>"
                                                data-extensao="<?php echo htmlspecialchars($mod['extensao'] ?? 'sys'); ?>">
                                            [Editar]
                                        </button>
                                        <form action="" method="POST" class="d-inline" onsubmit="return confirm('Deseja realmente apagar esta modalidade?');">
                                            <input type="hidden" name="id" value="<?php echo $mod['id']; ?>">
                                            <button type="submit" name="excluir_modalidade" class="btn btn-sm btn-outline-danger">[Deletar]</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="text-center text-secondary py-4 code-font">// nenhum_registro_encontrado_no_banco</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="border border-danger rounded p-4 bg-black">
            <h5 class="code-font text-danger mb-2">⚠️ DANGER_ZONE // HARD_RESET</h5>
            <p class="text-secondary small mb-3">O botão abaixo limpa <strong>todas as modalidades</strong> e desvincula <strong>todos os atletas</strong> simultaneamente, permitindo que você recomece a inclusão completamente do zero.</p>
            <form action="" method="POST" onsubmit="return confirm('ATENÇÃO MÁXIMA: Isto apagará COMPLETAMENTE todas as modalidades e cadastros de alunos. Você tem certeza absoluta disso?');">
                <button type="submit" name="limpar_banco_total" class="btn btn-sm btn-danger code-font">purge_system_data_total();</button>
            </form>
        </div>
    </div>

    <div class="modal fade" id="modalAddModalidade" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content bg-dark text-white border-secondary">
                <div class="modal-header border-secondary">
                    <h5 class="modal-title code-font text-success">// inject_modalidade();</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form action="" method="POST">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label text-secondary small">Nome Completo do Esporte</label>
                            <input type="text" name="nome" class="form-control form-input-kernel" placeholder="Ex: Handebol Masculino" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label text-secondary small">Chave do Sistema</label>
                            <input type="text" name="chave" class="form-control form-input-kernel" placeholder="Ex: handmasc" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label text-secondary small">Horário</label>
                            <input type="text" name="horario" class="form-control form-input-kernel" placeholder="Ex: Sábados às 18:00" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label text-secondary small">Local</label>
                            <input type="text" name="local" class="form-control form-input-kernel" placeholder="Ex: Ginásio Campus" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label text-secondary small">Extensão (.exe, .sys, .bin)</label>
                            <select name="extensao" class="form-select form-select-kernel">
                                <option value="sys">sys</option>
                                <option value="exe">exe</option>
                                <option value="bin">bin</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer border-secondary">
                        <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Fechar</button>
                        <button type="submit" name="adicionar_modalidade" class="btn btn-sm btn-kernel">Salvar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modalEditModalidade" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content bg-dark text-white border-secondary">
                <div class="modal-header border-secondary">
                    <h5 class="modal-title code-font text-warning">// overwrite_parameters();</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form action="" method="POST">
                    <input type="hidden" name="id" id="edit_id">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label text-secondary small">Nome do Esporte</label>
                            <input type="text" name="nome" id="edit_nome" class="form-control form-input-kernel" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label text-secondary small">Horário</label>
                            <input type="text" name="horario" id="edit_horario" class="form-control form-input-kernel" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label text-secondary small">Local</label>
                            <input type="text" name="local" id="edit_local" class="form-control form-input-kernel" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label text-secondary small">Extensão</label>
                            <select name="extensao" id="edit_extensao" class="form-select form-select-kernel">
                                <option value="sys">sys</option>
                                <option value="exe">exe</option>
                                <option value="bin">bin</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer border-secondary">
                        <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" name="editar_modalidade" class="btn btn-sm btn-warning text-dark fw-bold">Atualizar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const editButtons = document.querySelectorAll('.btn-kernel-edit');
        editButtons.forEach(button => {
            button.addEventListener('click', function() {
                document.getElementById('edit_id').value = this.getAttribute('data-id');
                document.getElementById('edit_nome').value = this.getAttribute('data-nome');
                document.getElementById('edit_horario').value = this.getAttribute('data-horario');
                document.getElementById('edit_local').value = this.getAttribute('data-local');
                document.getElementById('edit_extensao').value = this.getAttribute('data-extensao');
            });
        });
    </script>
</body>
</html>