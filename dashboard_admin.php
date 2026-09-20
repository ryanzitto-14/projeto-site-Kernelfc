<?php
ini_set('session.cookie_path', '/');
if (!session_id()) session_start();

if (!isset($_SESSION['logado']) || $_SESSION['logado'] !== true || $_SESSION['usuario_nivel'] !== 'diretoria') {
    header('Location: login.php');
    exit;
}

require_once 'conexao.php';

// Captura o filtro selecionado pelo admin
$filtro_curso = isset($_GET['filtrar_curso']) ? $_GET['filtrar_curso'] : '';

try {
    // Busca todos os cursos para montar o cabeçalho de filtros dinamicamente da tabela correta
    $stmt_lista_cursos = $pdo->query("SELECT nome FROM cursos ORDER BY (nome = 'Outros') ASC, nome ASC");
    $cursos_disponiveis = $stmt_lista_cursos->fetchAll(PDO::FETCH_COLUMN);

    // QUERY ATUALIZADA: Busca o curso real da tabela cursos e traz o tamanho do manto (u.camisa_tamanho)
    $sql_membros = "
        SELECT 
            u.id, 
            u.nome, 
            u.matricula, 
            c.nome AS curso_nome,
            u.email, 
            u.celular, 
            u.status_socio, 
            u.tipo_membro, 
            u.nivel_acesso, 
            u.modelo_camisa, 
            u.camisa_nome, 
            u.camisa_numero,
            u.camisa_tamanho,
            GROUP_CONCAT(mo.nome SEPARATOR ', ') AS modalidades_lista
        FROM usuarios u
        LEFT JOIN cursos c ON u.curso_id = c.id
        LEFT JOIN membros_modalidades mm ON u.id = mm.usuario_id
        LEFT JOIN modalidades mo ON mm.modalidade_id = mo.id
    ";

    if (!empty($filtro_curso)) {
        if ($filtro_curso === 'Sem Curso') {
            $sql_membros .= " WHERE c.nome IS NULL OR c.nome = 'Sem Curso' ";
        } else {
            $sql_membros .= " WHERE c.nome = :curso ";
        }
    }

    $sql_membros .= " GROUP BY u.id ORDER BY u.data_cadastro DESC";

    $query_membros = $pdo->prepare($sql_membros);
    
    if (!empty($filtro_curso) && $filtro_curso !== 'Sem Curso') {
        $query_membros->bindValue(':curso', $filtro_curso);
    }
    
    $query_membros->execute();
    $membros = $query_membros->fetchAll(PDO::FETCH_ASSOC);

    $total_membros = count($membros);
    
    $stmt_ativos = $pdo->query("SELECT COUNT(id) as total FROM usuarios WHERE status_socio = 'ativo'");
    $total_ativos = $stmt_ativos->fetch(PDO::FETCH_ASSOC)['total'];

    $stmt_pendentes = $pdo->query("SELECT COUNT(id) as total FROM usuarios WHERE status_socio = 'pendente'");
    $total_pendentes = $stmt_pendentes->fetch(PDO::FETCH_ASSOC)['total'];

} catch (PDOException $e) {
    die("Erro ao carregar dados do painel administrativo: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel Administrativo - Kernel</title>
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
        }
        
        .code-font { 
            font-family: 'Fira Code', monospace; 
        }

        .admin-card {
            background-color: var(--dark-card);
            border: 1px solid #222;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.3);
        }

        .table-kernel {
            background-color: #000000 !important;
            border: 1px solid #222;
            border-radius: 8px;
            overflow: hidden;
        }

        .table-kernel thead {
            background-color: #0D0D0D !important;
        }

        .table-kernel th {
            color: #888 !important;
            font-family: 'Fira Code', monospace;
            font-size: 0.85rem;
            text-transform: uppercase;
            border-bottom: 2px solid #222 !important;
            padding: 15px;
            background-color: #0D0D0D !important;
            white-space: nowrap;
        }

        .table-kernel td {
            color: #ddd !important;
            border-bottom: 1px solid #1c1c1c !important;
            padding: 15px;
            background-color: #000000 !important;
            white-space: nowrap;
        }

        .table-kernel tr:hover td {
            background-color: #0D0D0D !important;
            color: #fff !important;
        }

        .select-nivel, .select-filtro {
            background-color: #111 !important;
            color: #fff !important;
            border: 1px solid #333 !important;
            font-family: 'Fira Code', monospace;
            font-size: 0.85rem;
            padding: 4px 8px;
            border-radius: 6px;
            cursor: pointer;
            transition: border-color 0.2s;
        }
        .select-nivel:focus, .select-filtro:focus {
            border-color: var(--tech-green) !important;
            box-shadow: none;
        }

        .badge-atleta {
            background-color: rgba(0, 255, 102, 0.15) !important;
            color: var(--tech-green) !important;
            border: 1px solid var(--tech-green);
        }

        .badge-apoiador {
            background-color: rgba(0, 188, 255, 0.15) !important;
            color: #00bcff !important;
            border: 1px solid #00bcff;
        }

        @media (max-width: 767.98px) {
            .container {
                padding-left: 12px;
                padding-right: 12px;
            }
            .d-flex.justify-content-between.align-items-center {
                flex-direction: column;
                align-items: flex-start !important;
                gap: 10px;
            }
            h2 {
                font-size: 1.5rem;
            }
            .admin-card {
                padding: 1.25rem !important;
            }
            .admin-card .fs-1 {
                font-size: 2rem !important;
            }
            .admin-card h3 {
                font-size: 1.5rem;
            }
        }
    </style>
</head>
<body>

    <?php include 'navbar.php'; ?>

    <div class="container py-4 py-md-5">
        
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="fw-bold m-0">Controle de Integrantes 🛡️</h2>
                <p class="text-muted m-0 small">Gerencie permissões, níveis de acesso e pedidos de mantos oficiais.</p>
            </div>
        </div>

        <div class="row g-3 g-md-4 mb-4 mb-md-5">
            <div class="col-md-4">
                <div class="admin-card p-4 d-flex justify-content-between align-items-center" style="border-left: 4px solid #00bcff;">
                    <div>
                        <span class="small text-uppercase code-font d-block mb-1" style="color: #00bcff; font-weight: 600; font-size: 0.8rem;">// total_cadastros</span>
                        <h3 class="fw-bold mt-1 mb-0 text-white"><?php echo $total_membros; ?></h3>
                    </div>
                    <span class="fs-1">👥</span>
                </div>
            </div>

            <div class="col-md-4">
                <div class="admin-card p-4 d-flex justify-content-between align-items-center" style="border-left: 4px solid var(--tech-green);">
                    <div>
                        <span class="small text-uppercase code-font d-block mb-1" style="color: var(--tech-green); font-weight: 600; font-size: 0.8rem;">// socios_ativos</span>
                        <h3 class="fw-bold mt-1 mb-0" style="color: var(--tech-green);"><?php echo $total_ativos; ?></h3>
                    </div>
                    <span class="fs-1">✅</span>
                </div>
            </div>

            <div class="col-md-4">
                <div class="admin-card p-4 d-flex justify-content-between align-items-center" style="border-left: 4px solid #ffc107;">
                    <div>
                        <span class="small text-uppercase code-font d-block mb-1" style="color: #ffc107; font-weight: 600; font-size: 0.8rem;">// aguardando_aprovacao</span>
                        <h3 class="fw-bold mt-1 mb-0" style="color: #ffc107;"><?php echo $total_pendentes; ?></h3>
                    </div>
                    <span class="fs-1">⏳</span>
                </div>
            </div>
        </div>

        <div class="admin-card p-3 mb-4" style="background-color: #111;">
            <form action="" method="GET" class="row g-2 align-items-center m-0">
                <div class="col-auto">
                    <label for="filtrar_curso" class="code-font small text-muted">// filtrar_por_curso:</label>
                </div>
                <div class="col-auto">
                    <select name="filtrar_curso" id="filtrar_curso" class="form-select select-filtro">
                        <option value="">[ Mostrar Todos ]</option>
                        <option value="Sem Curso" <?php echo $filtro_curso === 'Sem Curso' ? 'selected' : ''; ?>>Sem Curso</option>
                        <?php foreach ($cursos_disponiveis as $c_opcao): ?>
                            <?php if ($c_opcao === 'Sem Curso') continue; ?>
                            <option value="<?php echo htmlspecialchars($c_opcao); ?>" <?php echo $filtro_curso === $c_opcao ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($c_opcao); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-auto">
                    <button type="submit" class="btn btn-sm btn-outline-success code-font" style="font-size: 0.85rem; padding: 4px 12px;">
                        executar_filtro();
                    </button>
                </div>
            </form>
        </div>

        <div class="card admin-card border-0 p-2 p-md-3" style="background-color: #000000; border: 1px solid #222;">
            <h5 class="fw-bold code-font mb-3 text-white px-2 pt-2" style="font-size: 1rem;">&gt;_ listagem_socios</h5>
            
            <div class="table-responsive">
                <table class="table table-kernel align-middle m-0">
                    <thead>
                        <tr>
                            <th>Nome</th>
                            <th>Curso</th>
                            <th>Matrícula</th>
                            <th>Nível de Acesso</th>
                            <th>Tipo Manto</th>
                            <th>Manto Customizado</th>
                            <th>WhatsApp</th>
                            <th>Modalidades</th>
                            <th>Status</th>
                            <th class="text-center">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($membros) > 0): ?>
                            <?php foreach ($membros as $membro): ?>
                                <tr>
                                    <td class="fw-semibold text-white"><?php echo htmlspecialchars($membro['nome']); ?></td>
                                    
                                    <td class="code-font small text-warning"><?php echo !empty($membro['curso_nome']) ? htmlspecialchars($membro['curso_nome']) : '// indeterminado'; ?></td>
                                    
                                    <td>
                                        <code class="text-info">
                                            <?php echo (!empty($membro['matricula'])) ? htmlspecialchars($membro['matricula']) : 'Excluído/Sem'; ?>
                                        </code>
                                    </td>
                                    
                                    <td>
                                        <form action="alterar_nivel.php" method="POST" class="m-0">
                                            <input type="hidden" name="usuario_id" value="<?php echo $membro['id']; ?>">
                                            <select name="novo_nivel" class="form-select select-nivel" onchange="this.form.submit()">
                                                <option value="integrante" <?php echo ($membro['nivel_acesso'] === 'integrante' || empty($membro['nivel_acesso'])) ? 'selected' : ''; ?>>Integrante</option>
                                                <option value="atleta" <?php echo ($membro['nivel_acesso'] === 'atleta') ? 'selected' : ''; ?>>Atleta ⚽</option>
                                                <option value="diretoria" <?php echo ($membro['nivel_acesso'] === 'diretoria') ? 'selected' : ''; ?>>Diretoria ⚙️</option>
                                            </select>
                                        </form>
                                    </td>

                                    <td>
                                        <?php 
                                            if ($membro['tipo_membro'] === 'atleta') {
                                                echo '<span class="badge badge-atleta">Atleta</span>';
                                            } elseif ($membro['tipo_membro'] === 'apoiador') {
                                                echo '<span class="badge badge-apoiador">Apoiador</span>';
                                            } else {
                                                echo '<span class="text-muted small font-monospace">// não definido</span>';
                                            }
                                        ?>
                                    </td>

                                    <td class="code-font text-warning fw-bold">
                                        <?php 
                                            if (!empty($membro['camisa_nome'])) {
                                                // Exibe Nome e Número
                                                echo htmlspecialchars($membro['camisa_nome']) . " #" . $membro['camisa_numero'];
                                                
                                                // 👕 ATUALIZADO: Renderiza o Tamanho do Manto se existir no banco
                                                if (!empty($membro['camisa_tamanho'])) {
                                                    echo ' <span class="badge bg-dark border border-secondary text-white-50 ms-1 px-1.5 py-0.5" style="font-size:0.7rem;">' . htmlspecialchars($membro['camisa_tamanho']) . '</span>';
                                                }
                                                
                                                // Modelo da camisa (Verde ou Preta)
                                                echo $membro['modelo_camisa'] === 'goleiro' ? ' <small class="text-white-50 d-block" style="font-size:0.75rem; font-weight:normal;">(Manto Preto)</small>' : ' <small class="text-success d-block" style="font-size:0.75rem; font-weight:normal;">(Manto Verde)</small>';
                                            } else {
                                                echo '<span class="text-muted font-monospace fw-normal" style="font-size:0.85rem;">- Nenhuma -</span>';
                                            }
                                        ?>
                                    </td>

                                    <td><?php echo htmlspecialchars($membro['celular']); ?></td>
                                    
                                    <td>
                                        <?php if (!empty($membro['modalidades_lista'])): ?>
                                            <?php 
                                                $mods_array = explode(', ', $membro['modalidades_lista']);
                                                $mods_formatadas = array_map(function($m) {
                                                    if (strtolower($m) === 'fut7') return 'FUT7';
                                                    return ucfirst($m);
                                                }, $mods_array);
                                            ?>
                                            <span class="badge bg-secondary"><?php echo htmlspecialchars(implode(', ', $mods_formatadas)); ?></span>
                                        <?php else: ?>
                                            <span class="text-muted small font-monospace" style="font-size: 0.8rem;">// nenhuma</span>
                                        <?php endif; ?>
                                    </td>
                                    
                                    <td>
                                        <?php if ($membro['status_socio'] === 'ativo'): ?>
                                            <span class="badge bg-success-subtle text-success border border-success px-2 py-1">Ativo</span>
                                        <?php else: ?>
                                            <span class="badge bg-warning-subtle text-warning border border-warning px-2 py-1">Pendente</span>
                                        <?php endif; ?>
                                    </td>
                                    
                                    <td class="text-center">
                                        <div class="d-flex gap-1 justify-content-center">
                                            <?php if ($membro['status_socio'] === 'pendente'): ?>
                                                <a href="alterar_status.php?id=<?php echo $membro['id']; ?>&acao=ativar" class="btn btn-sm btn-success code-font" style="font-size: 0.8rem; padding: 4px 10px;">
                                                    [ ativar ]
                                                </a>
                                            <?php else: ?>
                                                <a href="alterar_status.php?id=<?php echo $membro['id']; ?>&acao=suspender" class="btn btn-sm btn-outline-secondary code-font" style="font-size: 0.8rem; padding: 4px 10px; color: #888; border-color: #444;">
                                                    [ suspender ]
                                                </a>
                                            <?php endif; ?>

                                            <a href="deletar_membro.php?id=<?php echo $membro['id']; ?>" 
                                               class="btn btn-sm btn-outline-danger code-font" 
                                               style="font-size: 0.8rem; padding: 4px 10px;" 
                                               onclick="return confirm('ATENÇÃO: Tem certeza absoluta que deseja remover permanentemente o integrante <?php echo htmlspecialchars($membro['nome']); ?>? Esta ação não pode ser desfeita.');">
                                                 [ remover ]
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="10" class="text-center text-muted py-5" style="background-color: #000000 !important;">Nenhum integrante encontrado para os critérios selecionados.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>