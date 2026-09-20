<?php
// ATIVAR BUFFER DE SAÍDA: Evita que qualquer espaço ou caractere quebre o header()
ob_start();

ini_set('session.cookie_path', '/');
if (!session_id()) session_start();

require_once 'conexao.php';

$mensagem = "";
$status_msg = "";

// Alinhado para usar 'usuario_nivel', idêntico ao painel administrativo
$is_diretoria = isset($_SESSION['logado']) && $_SESSION['logado'] === true && isset($_SESSION['usuario_nivel']) && $_SESSION['usuario_nivel'] === 'diretoria';

$usuario_id = $_SESSION['usuario_id'] ?? $_SESSION['id'] ?? null;
$usuario_nivel = $_SESSION['usuario_nivel'] ?? 'membro'; // Captura o nível do usuário logado

// =======================================================================
// 1. PROCESSAR INSCRIÇÃO DIRETA PELO SITE
// =======================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['inscrever_esporte'])) {
    if (!isset($_SESSION['logado']) || $_SESSION['logado'] !== true) {
        echo "<script>window.location.href='login.php';</script>";
        exit;
    }

    // 🛡️ NOVA TRAVA DE SEGURANÇA: Bloqueia quem não é atleta ou diretoria
    if (!in_array($usuario_nivel, ['atleta', 'diretoria'])) {
        header('Location: esportes.php?status=danger&msg=nivel_insuficiente');
        echo "<script>window.location.href='esportes.php?status=danger&msg=nivel_insuficiente';</script>";
        exit;
    }

    $modalidade_id = intval($_POST['modalidade_id']);

    if ($usuario_id) {
        try {
            $stmt_check = $pdo->prepare("SELECT COUNT(*) FROM membros_modalidades WHERE usuario_id = :usuario_id AND modalidade_id = :modalidade_id");
            $stmt_check->execute([
                ':usuario_id' => $usuario_id,
                ':modalidade_id' => $modalidade_id
            ]);
            $ja_cadastrado = $stmt_check->fetchColumn();

            if ($ja_cadastrado > 0) {
                header('Location: esportes.php?status=warning&msg=ja_cadastrado');
                echo "<script>window.location.href='esportes.php?status=warning&msg=ja_cadastrado';</script>";
                exit;
            } else {
                $stmt = $pdo->prepare("INSERT INTO membros_modalidades (usuario_id, modalidade_id) VALUES (:usuario_id, :modalidade_id)");
                $stmt->execute([
                    ':usuario_id' => $usuario_id,
                    ':modalidade_id' => $modalidade_id
                ]);
                header('Location: esportes.php?status=success&msg=sucesso_inscricao');
                echo "<script>window.location.href='esportes.php?status=success&msg=sucesso_inscricao';</script>";
                exit;
            }
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) {
                header('Location: esportes.php?status=danger&msg=erro_banco');
                echo "<script>window.location.href='esportes.php?status=danger&msg=erro_banco';</script>";
                exit;
            } else {
                header('Location: esportes.php?status=danger&msg=erro_sistema');
                echo "<script>window.location.href='esportes.php?status=danger&msg=erro_sistema';</script>";
                exit;
            }
        }
    } else {
        header('Location: esportes.php?status=danger&msg=erro_auth');
        echo "<script>window.location.href='esportes.php?status=danger&msg=erro_auth';</script>";
        exit;
    }
}

// =======================================================================
// 2. PROCESSAR CANCELAMENTO DE INSCRIÇÃO (DESVINCULAR)
// =======================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancelar_inscricao'])) {
    if (!isset($_SESSION['logado']) || $_SESSION['logado'] !== true) {
        echo "<script>window.location.href='login.php';</script>";
        exit;
    }

    $modalidade_id = intval($_POST['modalidade_id']);

    if ($usuario_id) {
        try {
            $stmt_delete = $pdo->prepare("DELETE FROM membros_modalidades WHERE usuario_id = :usuario_id AND modalidade_id = :modalidade_id");
            $stmt_delete->execute([
                ':usuario_id' => $usuario_id,
                ':modalidade_id' => $modalidade_id
            ]);
            header('Location: esportes.php?status=success&msg=sucesso_cancelamento');
            echo "<script>window.location.href='esportes.php?status=success&msg=sucesso_cancelamento';</script>";
            exit;
        } catch (PDOException $e) {
            header('Location: esportes.php?status=danger&msg=erro_sistema');
            echo "<script>window.location.href='esportes.php?status=danger&msg=erro_sistema';</script>";
            exit;
        }
    }
}

// =======================================================================
// 2.5 CAPTURAR MENSAGENS TRANSMITIDAS PELA URL
// =======================================================================
if (isset($_GET['msg'])) {
    $status_msg = $_GET['status'] ?? 'info';
    
    switch ($_GET['msg']) {
        case 'nivel_insuficiente':
            $mensagem = "ACESSO_NEGADO: A vinculação a modalidades é exclusiva para usuários com o nível Atleta, concedido pela diretoria.";
            break;
        case 'ja_cadastrado':
            $mensagem = "AVISO: Voce ja esta cadastrado nesta modalidade!";
            break;
        case 'sucesso_inscricao':
            $mensagem = "INSCRICAO_REALIZADA_COM_SUCESSO(); // Boa sorte nos treinos!";
            break;
        case 'sucesso_cancelamento':
            $mensagem = "DESVINCULO_EFETUADO_COM_SUCESSO(); // Registro removido.";
            break;
        case 'erro_banco':
            $mensagem = "ERRO_BANCO: O sistema não permite múltiplos esportes por usuário. Verifique as chaves da tabela.";
            break;
        case 'erro_auth':
            $mensagem = "ERRO_AUTENTICACAO: ID de usuario nao encontrado na sessao.";
            break;
        case 'erro_sistema':
            $mensagem = "ERRO_SISTEMA: Ocorreu uma falha no processamento do banco de dados.";
            break;
        default:
            $mensagem = "";
    }
}

// =======================================================================
// 3. BUSCAR MODALIDADES E ATLETAS DINAMICAMENTE
// =======================================================================
try {
    $modalidades = $pdo->query("SELECT * FROM modalidades ORDER BY nome ASC")->fetchAll(PDO::FETCH_ASSOC);

    $sql_membros = "SELECT u.nome as atleta_nome, m.usuario_id, m.modalidade_id 
                    FROM membros_modalidades m 
                    JOIN usuarios u ON m.usuario_id = u.id 
                    ORDER BY u.nome ASC";
    $todos_membros = $pdo->query($sql_membros)->fetchAll(PDO::FETCH_ASSOC);

    $atletas_por_modalidade = [];
    $minhas_modalidades = []; 
    
    foreach ($modalidades as $mod) {
        $atletas_por_modalidade[$mod['id']] = [];
    }
    
    foreach ($todos_membros as $m) {
        if (isset($atletas_por_modalidade[$m['modalidade_id']])) {
            $atletas_por_modalidade[$m['modalidade_id']][] = $m['atleta_nome'];
        }
        if ($usuario_id && $m['usuario_id'] == $usuario_id) {
            $minhas_modalidades[] = $m['modalidade_id'];
        }
    }
} catch (PDOException $e) {
    die("Erro crítico ao carregar dados do Sports Hub: " . $e->getMessage());
}

ob_end_flush();
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sports Hub // Kernel</title>
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
        }
        .code-font { 
            font-family: 'Fira Code', monospace;
        }
        .sports-card { 
            background-color: var(--dark-card); 
            border: 1px solid #222; border-radius: 12px; 
            transition: all 0.3s ease; 
        }
        .sports-card:hover { 
            border-color: var(--tech-green); 
            box-shadow: 0 0 20px rgba(0, 255, 102, 0.1); 
        }
        .list-container { background-color: #0D0D0D; border: 1px solid #222; border-radius: 8px; max-height: 180px; overflow-y: auto; padding: 10px; }
        .btn-kernel { background: transparent; color: var(--tech-green); border: 2px solid var(--tech-green); font-weight: 600; font-size: 0.85rem; }
        .btn-kernel:hover { background: var(--tech-green); color: var(--dark-bg); }
        .btn-danger-kernel { background: transparent; color: #FF3333; border: 2px solid #FF3333; font-weight: 600; font-size: 0.85rem; }
        .btn-danger-kernel:hover { background: #FF3333; color: #fff; }
    </style>
</head>
<body>

    <?php include 'navbar.php'; ?>

    <div class="container py-4 py-md-5">
        <div class="text-center mb-5">
            <h2 class="fw-bold code-font text-white">exec_command("listar_modalidades");</h2>
            <p class="text-secondary small">Acompanhe os horários de treino, listas de presença e gerencie seu status esportivo.</p>
            
            <?php if ($is_diretoria): ?>
                <a href="gerenciar_esportes.php" class="btn btn-sm btn-kernel code-font mt-2">
                    [⚙️] abrir_painel_gerenciamento();
                </a>
            <?php endif; ?>
            <hr class="border-secondary my-4">
        </div>

        <?php if(!empty($mensagem)): ?>
            <div class="alert alert-<?php echo $status_msg; ?> code-font small mb-4"><?php echo $mensagem; ?></div>
        <?php endif; ?>

        <div class="row g-4 mb-5">
            <?php foreach ($modalidades as $mod): 
                $id_mod = $mod['id'];
                $atletas = $atletas_por_modalidade[$id_mod] ?? [];
                $estou_inscrito = in_array($id_mod, $minhas_modalidades);
            ?>
                <div class="col-md-6 col-lg-4">
                    <div class="sports-card p-4 h-100 d-flex flex-column">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <span class="code-font text-success small">// <?php echo htmlspecialchars(($mod['chave'] ?? 'esporte') . '.' . ($mod['extensao'] ?? 'cfg')); ?></span>
                        </div>
                        
                        <h4 class="fw-bold text-white mb-3"><?php echo htmlspecialchars($mod['nome']); ?></h4>
                        <p class="text-secondary small mb-2"><strong>🕒 Horário:</strong> <?php echo htmlspecialchars($mod['horario']); ?></p>
                        <p class="text-secondary small mb-3"><strong>📍 Local:</strong> <?php echo htmlspecialchars($mod['local']); ?></p>
                        
                        <h6 class="code-font text-white-50 small mb-2">&gt;_ atletas_ativos (<?php echo count($atletas); ?>)</h6>
                        <div class="list-container flex-grow-1 mb-3">
                            <?php if(count($atletas) > 0): ?>
                                <?php foreach($atletas as $atleta): ?>
                                    <div class="border-bottom border-dark py-1 code-font small text-secondary">
                                        <div class="row g-0 align-items-center">
                                            <div class="col-12 text-truncate"># <?php echo htmlspecialchars($atleta); ?></div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="text-secondary-50 small font-monospace py-2">// nenhum_atleta_vinculado</div>
                            <?php endif; ?>
                        </div>

                        <div class="mt-auto pt-2">
                            <?php if(isset($_SESSION['logado']) && $_SESSION['logado'] === true): ?>
                                <?php if($estou_inscrito): ?>
                                    <form action="esportes.php" method="POST" onsubmit="return confirm('Deseja cancelar sua inscrição em <?php echo htmlspecialchars($mod['nome']); ?>?');">
                                        <input type="hidden" name="modalidade_id" value="<?php echo $id_mod; ?>">
                                        <button type="submit" name="cancelar_inscricao" class="btn btn-danger-kernel code-font w-100">
                                            cancelar_vinculo();
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <form action="esportes.php" method="POST">
                                        <input type="hidden" name="modalidade_id" value="<?php echo $id_mod; ?>">
                                        <button type="submit" name="inscrever_esporte" class="btn btn-kernel code-font w-100">
                                            vincular_id_atleta();
                                        </button>
                                    </form>
                                <?php endif; ?>
                            <?php else: ?>
                                <div class="text-center bg-black py-2 rounded border border-secondary border-dashed">
                                    <a href="login.php" class="text-warning code-font small text-decoration-none">[ auth_required ]</a>
                                </div>
                            <?php endif; ?>
                        </div>

                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
    // Limpa os parâmetros '?msg=' da URL assim que a página carrega
    if (typeof window.history.replaceState === 'function') {
        const url = new URL(window.location.href);
        if (url.searchParams.has('msg')) {
            url.searchParams.delete('msg');
            url.searchParams.delete('status');
            window.history.replaceState({}, document.title, url.pathname + url.search);
        }
    }
    </script>
</body>
</html>