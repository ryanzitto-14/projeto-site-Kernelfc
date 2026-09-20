<?php
// 🔥 CORREÇÃO CRÍTICA: Força o cookie da sessão a valer para o site inteiro e inicia ANTES do HTML
ini_set('session.cookie_path', '/');
if (!session_id()) session_start();
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>A.A.A.K. - Atlética Kernel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Fira+Code:wght@400;600&family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="icon" type="image/png" href="favicon.png">

    <style>
        :root {
            --tech-green: #00FF66; /* Verde Kernel */
            --dark-bg: #0D0D0D;    /* Preto Fundo */
            --dark-card: #1A1A1A;  /* Preto Components */
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

        /* Custom Navbar */
        .navbar-kernel {
            background-color: var(--dark-card);
            border-bottom: 2px solid var(--tech-green);
            padding-top: 5px;
            padding-bottom: 5px;
        }

        .navbar-kernel .navbar-brand {
            color: var(--tech-green);
            font-weight: 700;
            font-size: 1.4rem;
        }

        /* Custom Banner / Carousel */
        .carousel-kernel {
            border-bottom: 1px solid rgba(0, 255, 102, 0.2);
        }

        .carousel-item {
            height: 450px;
            background-color: #000;
        }

        .slide-1 {
            background: linear-gradient(rgba(0,0,0,0.7), rgba(0,0,0,0.7)), url('https://images.unsplash.com/photo-1517694712202-14dd9538aa97?q=80&w=1200') no-repeat center center;
            background-size: cover;
        }

        .slide-2 {
            background: linear-gradient(rgba(0,0,0,0.7), rgba(0,0,0,0.7)), url('https://images.unsplash.com/photo-1508098682722-e99c43a406b2?q=80&w=1200') no-repeat center center;
            background-size: cover;
        }

        .carousel-caption h5 {
            color: var(--tech-green);
            font-size: 2.5rem;
            font-weight: 700;
            word-wrap: break-word;
        }

        /* Botão Verde Customizado */
        .btn-kernel {
            background-color: transparent;
            color: var(--tech-green);
            border: 2px solid var(--tech-green);
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .btn-kernel:hover {
            background-color: var(--tech-green);
            color: var(--dark-bg);
            box-shadow: 0 0 15px rgba(0, 255, 102, 0.4);
        }

        footer {
            background-color: var(--dark-card);
            border-top: 1px solid rgba(0, 255, 102, 0.1);
            color: #888;
        }

        /* =======================================================================
        /* 📱 REGRAS DE RESPONSIVIDADE PARA CELULARES E TABLETS
        /* ======================================================================= */
        @media (max-width: 767.98px) {
            .carousel-item {
                height: 380px;
            }
            .carousel-caption h5 {
                font-size: 1.5rem;
            }
            .titulo-banner {
                white-space: nowrap;
            }
            .carousel-caption p {
                font-size: 0.95rem !important;
            }
            .carousel-caption {
                text-align: center !important;
                padding-left: 15px;
                padding-right: 15px;
            }
            .carousel-caption div {
                text-align: center !important;
            }
        }
    </style>
</head>
<body>

    <?php include 'navbar.php'; ?>

    <div id="bannerKernel" class="carousel slide carousel-kernel" data-bs-ride="carousel">
        <div class="carousel-indicators">
            <button type="button" data-bs-target="#bannerKernel" data-bs-slide-to="0" class="active"></button>
            <button type="button" data-bs-target="#bannerKernel" data-bs-slide-to="1"></button>
        </div>
        <div class="carousel-inner">
            <div class="carousel-item active slide-1">
                <div class="carousel-caption d-flex flex-column justify-content-center h-100 text-start">
                    <h5 class="code-font titulo-banner">FORJA_SISTEMAS();</h5>
                    <p class="fs-4">A força do curso de Análise e Desenvolvimento de Sistemas da FACAM nas quadras e nos códigos.</p>
                    <div class="text-start">
                        <a href="#apresentacao" class="btn btn-kernel btn-lg mt-2">Conheça a Kernel</a>
                    </div>
                </div>
            </div>
            <div class="carousel-item slide-2">
                <div class="carousel-caption d-flex flex-column justify-content-center h-100 text-end">
                    <h5 class="fw-bold">NOSSOS TREINOS VÃO COMEÇAR</h5>
                    <p class="fs-4">Fique atento aos cronogramas de Futsal, Vôlei e muito mais. Prepare o seu manto verde e preto.</p>
                    <div class="text-end">
                        <a href="esportes.php" class="btn btn-kernel btn-lg mt-2">Ver Modalidades</a>
                    </div>
                </div>
            </div>
        </div>
        <button class="carousel-control-prev" type="button" data-bs-target="#bannerKernel" data-bs-slide="prev">
            <span class="carousel-control-prev-icon"></span>
        </button>
        <button class="carousel-control-next" type="button" data-bs-target="#bannerKernel" data-bs-slide="next">
            <span class="carousel-control-next-icon"></span>
        </button>
    </div>

    <section id="apresentacao" class="py-4 py-md-5 container">
        <div class="row align-items-center g-4 py-2">
            <div class="col-lg-6 mb-3 mb-lg-0">
                <h2 class="fw-bold mb-3" style="color: var(--tech-green);">Quem somos nós?</h2>
                <p class="text-secondary lead">
                    Fundada oficialmente em 05 de junho de 2026, a <strong>Associação Atlética Acadêmica Kernel (A.A.A.K.)</strong> é a entidade oficial que representa os estudantes do curso de Análise e Desenvolvimento de Sistemas da Faculdade do Maranhão (FACAM).
                </p>
                <p class="text-secondary">
                    Nosso objetivo vai muito além de integrar os alunos através de esportes competitivos e de lazer. Nós nascemos para unir o sentimento comunitário, criar espaço para desenvolvimento pessoal, liderança e, claro, buscar títulos nos principais cenários esportivos de São Luís.
                </p>
                <div class="mt-4">
                    <a href="docs/estatuto_kernel.pdf" target="_blank" class="text-decoration-none code-font" style="color: var(--tech-green);">
                        &gt; abrir estatuto social
                    </a>
                </div>
            </div>
            
            <div class="col-md-8 col-lg-5 offset-md-2 offset-lg-1 text-center">
                <div class="p-2 mb-4 rounded-3 text-center" style="background-color: var(--dark-card); border: 1px solid rgba(0, 255, 102, 0.2); box-shadow: 0 4px 25px rgba(0,0,0,0.7);">
                    <img src="img/logo.png" alt="Escudo Oficial Kernel" class="img-fluid rounded w-100" style="filter: drop-shadow(0 0 15px rgba(0, 255, 102, 0.25)); max-height: 350px; object-fit: contain;">
                </div>

                <div class="p-4 rounded-3 text-start" style="background-color: var(--dark-card); border-left: 4px solid var(--tech-green);">
                    <h5 class="code-font mb-3" style="color: var(--tech-green);">// Nossos Valores</h5>
                    <ul class="list-unstyled text-secondary mb-0">
                        <li class="mb-2"><strong class="text-white">🚀 Integração:</strong> Unir calouros e veteranos do curso de ADS.</li>
                        <li class="mb-2"><strong class="text-white">⚽ Espírito Esportivo:</strong> Disciplina e foco nos treinos e torneios.</li>
                        <li><strong class="text-white">🧬 Identidade:</strong> Carregar as cores verde e preta com orgulho.</li>
                    </ul>
                </div>
            </div>
        </div>
    </section>

    <footer class="py-4 text-center">
    <div class="container">
        <!-- Links de Contato e Redes Sociais -->
        <div class="d-flex justify-content-center align-items-center gap-4 mb-2 code-font text-sm">
            <a href="https://instagram.com/atleticakernel" target="_blank" class="text-secondary text-decoration-none hover-green">
                <i class="bi bi-instagram me-1"></i> @atleticakernel
            </a>
            <span class="text-secondary">|</span>
            <a href="mailto:contato@atleticakernel.com" class="text-secondary text-decoration-none hover-green">
                <i class="bi bi-envelope-fill me-1"></i> atleticakernel@gmail.com
            </a>
        </div>

        <!-- Direitos Autorais -->
        <p class="mb-0 text-sm code-font px-3 text-secondary">&copy; <?php echo date('Y'); ?> A.A.A. Kernel. Desenvolvido pelos alunos de ADS - FACAM.</p>
    </div>
</footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>