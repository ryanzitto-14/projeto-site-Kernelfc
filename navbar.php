<?php
if (!session_id()) {
    session_start();
}

// Lógica de verificação do status do usuário
$logado = isset($_SESSION['logado']) && $_SESSION['logado'] === true;
$nivel_usuario = isset($_SESSION['usuario_nivel']) ? $_SESSION['usuario_nivel'] : '';
?>

<nav class="navbar navbar-kernel sticky-top">
    <div class="container">
        <a class="navbar-brand code-font d-flex align-items-center" href="index.php">
            <img src="img/logos (1).png" alt="Logo Kernel" height="65" class="me-2 d-inline-block align-top" style="margin-top: -8px; margin-bottom: -8px;">
            &lt;KERNEL /&gt;
        </a>
        
        <button class="btn text-white p-2 d-flex align-items-center justify-content-center toggle-menu-kernel" type="button" data-bs-toggle="offcanvas" data-bs-target="#menuLateralKernel" aria-controls="menuLateralKernel" aria-label="Abrir menu">
            <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" fill="#00FF66" class="bi bi-list" viewBox="0 0 16 16">
                <path fill-rule="evenodd" d="M2.5 12a.5.5 0 0 1 .5-.5h10a.5.5 0 0 1 0 1H3a.5.5 0 0 1-.5-.5m0-4a.5.5 0 0 1 .5-.5h10a.5.5 0 0 1 0 1H3a.5.5 0 0 1-.5-.5m0-4a.5.5 0 0 1 .5-.5h10a.5.5 0 0 1 0 1H3a.5.5 0 0 1-.5-.5"/>
            </svg>
        </button>
    </div>
</nav>

<div class="offcanvas offcanvas-end text-bg-dark" tabindex="-1" id="menuLateralKernel" aria-labelledby="menuLateralKernelLabel" style="background-color: #0D0D0D !important; border-left: 2px solid #00FF66;">
    
    <div class="offcanvas-header border-bottom border-secondary py-4">
        <h5 class="offcanvas-title code-font" id="menuLateralKernelLabel" style="color: #00FF66; font-weight: 700;">&lt;MENU_NAVEGACAO /&gt;</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    
    <div class="offcanvas-body d-flex flex-column justify-content-between py-4">
        <ul class="nav flex-column gap-2 fs-5">
            <li class="nav-item">
                <a class="nav-link text-white code-font py-2 px-3 rounded text-truncate" href="index.php">&gt; Home</a>
            </li>
            <li class="nav-item">
                <a class="nav-link text-white code-font py-2 px-3 rounded text-truncate" href="esportes.php">&gt; Esportes</a>
            </li>
              <li class="nav-item">
                <a class="nav-link text-white code-font py-2 px-3 rounded text-truncate" href="galeria.php">&gt; Galeria Kernel</a>
            </li>
            <li class="nav-item">
                <a class="nav-link text-white code-font py-2 px-3 rounded text-truncate" href="loja.php">&gt; Kernel Store</a>
            </li>
            
            <?php if ($logado): ?>
                <li class="nav-item">
                    <a class="nav-link text-white code-font py-2 px-3 rounded text-truncate" href="painel_integrante.php">
                        &gt; Meu perfil
                    </a>
                </li >
                
                <?php if (in_array($nivel_usuario, ['atleta', 'diretoria'])): ?>
                     <li class="nav-item">
                    <a class="nav-link text-white code-font py-2 px-3 rounded text-truncate" href="pagamentos.php">
                        &gt; Mensalidades
                    </a>
                </li >
                
                <?php endif; ?>
                
                <?php if ($nivel_usuario === 'diretoria'): ?>
                    <li class="nav-item mt-2 pt-2 border-top border-secondary-subtle">
                        <a class="nav-link code-font py-2 px-3 rounded bg-success-subtle border border-success mb-2 text-truncate" href="dashboard_admin.php" style="color: #00FF66 !important; font-weight: 600; font-size: 0.95rem;">
                            ⚙️ Painel de Admin
                        </a>
                        <a class="nav-link code-font py-2 px-3 rounded bg-dark border border-secondary text-truncate" href="cadastrar_produto.php" style="color: #ffffff !important; font-size: 0.85rem;">
                            ➕ Cadastrar Produto
                        </a>
                    </li>
                <?php endif; ?>
            <?php endif; ?>
        </ul>
        
        <div class="pt-4 border-top border-secondary">
            <?php if ($logado): ?>
                <div class="d-grid">
                    <a href="logout.php" class="btn btn-outline-danger code-font btn-sm py-2">
                        logout();
                    </a>
                </div>
            <?php else: ?>
                <div class="d-grid">
                    <a href="login.php" class="btn btn-kernel code-font py-2 text-center" style="font-size: 0.95rem; white-space: normal; word-wrap: break-word;">
                        Acessar Área do Integrante
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
    .navbar-kernel {
        background-color: #1A1A1A !important;
        border-bottom: 2px solid #00FF66 !important;
        padding-top: 5px;
        padding-bottom: 5px;
        width: 100% !important;
    }
    .navbar-kernel .navbar-brand {
        color: #00FF66 !important;
        font-weight: 700;
        font-size: 1.4rem;
    }
    .toggle-menu-kernel {
        background: transparent; 
        border: 1px solid rgba(0, 255, 102, 0.3); 
        border-radius: 8px; 
        width: 45px; 
        height: 45px; 
        transition: all 0.3s ease;
    }
    .toggle-menu-kernel:hover {
        background-color: rgba(0, 255, 102, 0.1);
        border-color: #00FF66;
    }
    #menuLateralKernel {
        width: 100% !important;
        max-width: 320px !important; /* Design responsivo seguro para displays ultra-pequenos */
    }
    #menuLateralKernel .nav-link {
        transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
    }
    #menuLateralKernel .nav-link:hover {
        background-color: rgba(0, 255, 102, 0.1);
        color: #00FF66 !important;
        padding-left: 24px !important; /* Deslocamento suave sem quebras bruscas */
    }
    .bg-dark-card {
        background-color: #141414 !important;
    }
</style>