<?php
session_start();

// Se já estiver logado, redireciona para o dashboard
if(isset($_SESSION['user_id'])){
    header("Location: dashboard.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Minhas Economias - Gestão Financeira Simplificada</title>
    <link rel="stylesheet" href="assets/css/site-enhancements.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap');

        :root {
            /* Cores principais refinadas */
            --brand: #059669;
            --brand-dark: #047857;
            --brand-light: #d1fae5;
            --accent: #f59e0b;
            --accent-light: #fef3c7;

            /* Neutros sofisticados */
            --ink: #0f172a;
            --ink-soft: #475569;
            --ink-muted: #94a3b8;
            --paper: #fafaf9;
            --card: #ffffff;
            --surface: #f1f5f9;

            /* Bordas e sombras */
            --border: #e2e8f0;
            --shadow-sm: 0 1px 2px 0 rgb(0 0 0 / 0.05);
            --shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1);
            --shadow-lg: 0 10px 15px -3px rgb(0 0 0 / 0.1), 0 4px 6px -4px rgb(0 0 0 / 0.1);
            --shadow-xl: 0 20px 25px -5px rgb(0 0 0 / 0.1), 0 8px 10px -6px rgb(0 0 0 / 0.1);
            --shadow-brand: 0 10px 40px -10px rgba(5, 150, 105, 0.4);
            --shadow-accent: 0 10px 40px -10px rgba(245, 158, 11, 0.4);

            /* Transições */
            --transition-fast: 150ms cubic-bezier(0.4, 0, 0.2, 1);
            --transition: 300ms cubic-bezier(0.4, 0, 0.2, 1);
            --transition-slow: 500ms cubic-bezier(0.4, 0, 0.2, 1);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        html {
            scroll-behavior: smooth;
        }

        body {
            font-family: 'Plus Jakarta Sans', system-ui, -apple-system, sans-serif;
            line-height: 1.6;
            color: var(--ink);
            background: var(--paper);
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }

        h1, h2, h3 {
            font-weight: 700;
            letter-spacing: -0.025em;
            line-height: 1.2;
        }

        /* HEADER / NAVBAR */
        header {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border-bottom: 1px solid var(--border);
            padding: 16px 0;
            position: sticky;
            top: 0;
            z-index: 1000;
            transition: var(--transition);
        }

        header.scrolled {
            box-shadow: var(--shadow-lg);
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 24px;
        }

        .navbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 24px;
        }

        .logo {
            font-size: 24px;
            font-weight: 800;
            letter-spacing: -0.02em;
            background: linear-gradient(135deg, var(--brand) 0%, var(--brand-dark) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .logo::before {
            content: '💰';
            -webkit-text-fill-color: initial;
            font-size: 28px;
            filter: drop-shadow(0 2px 4px rgba(0,0,0,0.1));
        }

        nav {
            display: flex;
            gap: 32px;
            align-items: center;
            flex-wrap: wrap;
            justify-content: flex-end;
        }

        .nav-links {
            display: flex;
            gap: 32px;
            align-items: center;
            flex-wrap: wrap;
        }

        nav a {
            color: var(--ink-soft);
            text-decoration: none;
            font-size: 15px;
            font-weight: 500;
            position: relative;
            transition: var(--transition-fast);
            padding: 8px 0;
        }

        nav a::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 0;
            height: 2px;
            background: var(--brand);
            transition: var(--transition);
            border-radius: 2px;
        }

        nav a:hover {
            color: var(--brand);
        }

        nav a:hover::after {
            width: 100%;
        }

        .btn-group {
            display: flex;
            gap: 12px;
        }

        .btn {
            padding: 10px 24px;
            border: none;
            border-radius: 12px;
            font-weight: 600;
            cursor: pointer;
            transition: all var(--transition);
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
            position: relative;
            overflow: hidden;
        }

        .btn::before {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(to right, transparent, rgba(255,255,255,0.2), transparent);
            transform: translateX(-100%);
            transition: var(--transition-slow);
        }

        .btn:hover::before {
            transform: translateX(100%);
        }

        .btn-download {
            background: var(--brand);
            color: white;
            box-shadow: var(--shadow-brand);
        }

        .btn-download:hover {
            background: var(--brand-dark);
            transform: translateY(-2px);
            box-shadow: 0 20px 40px -10px rgba(5, 150, 105, 0.5);
        }

        .btn-login {
            background: transparent;
            color: var(--brand);
            border: 2px solid var(--brand);
        }

        .btn-login:hover {
            background: var(--brand);
            color: white;
            transform: translateY(-2px);
        }

        /* HERO SECTION */
        .hero {
            background: linear-gradient(135deg, #ecfdf5 0%, #d1fae5 50%, #a7f3d0 100%);
            padding: 140px 20px 120px;
            text-align: left;
            position: relative;
            overflow: hidden;
        }

        .hero::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -20%;
            width: 80%;
            height: 200%;
            background: radial-gradient(ellipse at center, rgba(5, 150, 105, 0.15) 0%, transparent 70%);
            animation: float 20s ease-in-out infinite;
        }

        .hero::after {
            content: '';
            position: absolute;
            bottom: -30%;
            left: -10%;
            width: 60%;
            height: 100%;
            background: radial-gradient(ellipse at center, rgba(245, 158, 11, 0.1) 0%, transparent 60%);
            animation: float 15s ease-in-out infinite reverse;
        }

        @keyframes float {
            0%, 100% { transform: translate(0, 0) rotate(0deg); }
            50% { transform: translate(30px, -30px) rotate(2deg); }
        }

        .hero-content {
            max-width: 640px;
            position: relative;
            z-index: 2;
        }

        .hero-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: white;
            color: var(--brand);
            padding: 8px 16px;
            border-radius: 999px;
            font-size: 14px;
            font-weight: 600;
            margin-bottom: 24px;
            box-shadow: var(--shadow);
            border: 1px solid var(--border);
        }

        .hero h1 {
            font-size: 56px;
            margin-bottom: 24px;
            line-height: 1.1;
            color: var(--ink);
            background: linear-gradient(135deg, var(--ink) 0%, var(--brand-dark) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .hero p {
            font-size: 20px;
            margin-bottom: 40px;
            color: var(--ink-soft);
            line-height: 1.7;
            max-width: 540px;
        }

        .btn-cta {
            background: linear-gradient(135deg, var(--accent) 0%, #fbbf24 100%);
            color: #78350f;
            padding: 16px 40px;
            border: none;
            border-radius: 14px;
            font-size: 16px;
            font-weight: 700;
            cursor: pointer;
            transition: all var(--transition);
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            box-shadow: var(--shadow-accent);
            position: relative;
            overflow: hidden;
        }

        .btn-cta::after {
            content: '→';
            transition: var(--transition);
        }

        .btn-cta:hover {
            transform: translateY(-3px);
            box-shadow: 0 20px 40px -10px rgba(245, 158, 11, 0.5);
        }

        .btn-cta:hover::after {
            transform: translateX(4px);
        }

        /* FEATURES SECTION */
        .features {
            padding: 120px 20px;
            background: var(--paper);
            position: relative;
        }

        .features::before {
            content: '';
            position: absolute;
            top: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 200px;
            height: 1px;
            background: linear-gradient(to right, transparent, var(--border), transparent);
        }

        .section-header {
            text-align: center;
            max-width: 640px;
            margin: 0 auto 60px;
        }

        .section-header h2 {
            font-size: 40px;
            margin-bottom: 16px;
            color: var(--ink);
        }

        .section-header p {
            color: var(--ink-soft);
            font-size: 18px;
        }

        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
            gap: 24px;
            max-width: 1200px;
            margin: 0 auto;
        }

        .feature-card {
            background: var(--card);
            padding: 32px;
            border-radius: 20px;
            border: 1px solid var(--border);
            box-shadow: var(--shadow);
            transition: all var(--transition);
            position: relative;
            overflow: hidden;
        }

        .feature-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--brand), var(--accent));
            transform: scaleX(0);
            transition: var(--transition);
        }

        .feature-card:hover {
            transform: translateY(-8px);
            box-shadow: var(--shadow-xl);
            border-color: var(--brand-light);
        }

        .feature-card:hover::before {
            transform: scaleX(1);
        }

        .feature-icon {
            width: 60px;
            height: 60px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, var(--brand-light) 0%, var(--accent-light) 100%);
            border-radius: 16px;
            font-size: 28px;
            margin-bottom: 20px;
            transition: var(--transition);
        }

        .feature-card:hover .feature-icon {
            transform: scale(1.1) rotate(5deg);
        }

        .feature-card h3 {
            font-size: 20px;
            margin-bottom: 12px;
            color: var(--ink);
        }

        .feature-card p {
            color: var(--ink-soft);
            font-size: 15px;
            line-height: 1.7;
        }

        /* BENEFITS SECTION */
        .benefits {
            padding: 120px 20px;
            background: white;
            position: relative;
        }

        .benefits-container {
            max-width: 1200px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 80px;
            align-items: center;
        }

        .benefits-content h2 {
            font-size: 40px;
            margin-bottom: 24px;
            color: var(--ink);
        }

        .benefits-content > p {
            color: var(--ink-soft);
            font-size: 18px;
            margin-bottom: 40px;
            line-height: 1.7;
        }

        .benefit-list {
            list-style: none;
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .benefit-list li {
            display: flex;
            align-items: flex-start;
            gap: 16px;
            color: var(--ink-soft);
            font-size: 16px;
            line-height: 1.6;
            padding: 0;
        }

        .benefit-list li::before {
            content: "";
            flex-shrink: 0;
            width: 24px;
            height: 24px;
            background: var(--brand);
            mask: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='white' stroke-width='3' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='20 6 9 17 4 12'%3E%3C/polyline%3E%3C/svg%3E") center/contain no-repeat;
            -webkit-mask: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='white' stroke-width='3' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='20 6 9 17 4 12'%3E%3C/polyline%3E%3C/svg%3E") center/contain no-repeat;
            border-radius: 50%;
            position: static;
        }

        .benefits-image {
            background: linear-gradient(135deg, var(--brand) 0%, var(--brand-dark) 100%);
            height: 480px;
            border-radius: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            overflow: hidden;
            box-shadow: var(--shadow-brand);
        }

        .benefits-image::before {
            content: '';
            position: absolute;
            inset: 0;
            background: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23ffffff' fill-opacity='0.1'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
            opacity: 0.3;
        }

        .benefits-image-content {
            text-align: center;
            color: white;
            z-index: 1;
        }

        .benefits-image-content .icon {
            font-size: 80px;
            margin-bottom: 24px;
            filter: drop-shadow(0 4px 8px rgba(0,0,0,0.2));
        }

        .benefits-image-content p {
            font-size: 24px;
            font-weight: 600;
            opacity: 0.95;
        }

        /* CTA SECTION */
        .cta-section {
            background: linear-gradient(135deg, var(--brand) 0%, var(--brand-dark) 100%);
            color: white;
            padding: 100px 20px;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .cta-section::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle at 30% 70%, rgba(255,255,255,0.1) 0%, transparent 50%);
        }

        .cta-section .container {
            position: relative;
            z-index: 1;
        }

        .cta-section h2 {
            font-size: 44px;
            margin-bottom: 20px;
        }

        .cta-section > .container > p {
            font-size: 20px;
            margin-bottom: 40px;
            opacity: 0.95;
            max-width: 600px;
            margin-left: auto;
            margin-right: auto;
        }

        .btn-cta-white {
            background: white;
            color: var(--brand);
            padding: 16px 40px;
            border: none;
            border-radius: 14px;
            font-size: 16px;
            font-weight: 700;
            cursor: pointer;
            transition: all var(--transition);
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            box-shadow: var(--shadow-lg);
        }

        .btn-cta-white:hover {
            transform: translateY(-3px);
            box-shadow: 0 20px 40px rgba(0,0,0,0.2);
            background: var(--paper);
        }

        /* FOOTER */
        footer {
            background: var(--ink);
            color: white;
            padding: 60px 20px 40px;
        }

        .footer-content {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 24px;
        }

        .footer-logo {
            font-size: 24px;
            font-weight: 800;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .footer-links {
            display: flex;
            gap: 32px;
            flex-wrap: wrap;
            justify-content: center;
        }

        .footer-links a {
            color: var(--ink-muted);
            text-decoration: none;
            font-size: 14px;
            transition: var(--transition-fast);
        }

        .footer-links a:hover {
            color: white;
        }

        .footer-divider {
            width: 100%;
            height: 1px;
            background: linear-gradient(to right, transparent, var(--ink-soft), transparent);
            margin: 16px 0;
        }

        footer p {
            opacity: 0.6;
            font-size: 14px;
            text-align: center;
        }

        /* RESPONSIVE */
        @media (max-width: 968px) {
            .benefits-container {
                grid-template-columns: 1fr;
                gap: 48px;
            }

            .benefits-image {
                order: -1;
                height: 320px;
            }

            .hero h1 {
                font-size: 40px;
            }

            .section-header h2,
            .benefits-content h2,
            .cta-section h2 {
                font-size: 32px;
            }
        }

        @media (max-width: 768px) {
            .navbar {
                flex-direction: column;
                gap: 16px;
            }

            nav {
                width: 100%;
                justify-content: center;
            }

            .nav-links {
                width: 100%;
                justify-content: center;
            }

            .btn-group {
                width: 100%;
                justify-content: center;
            }

            .hero {
                padding: 100px 20px 80px;
                text-align: center;
            }

            .hero-content {
                max-width: 100%;
            }

            .hero p {
                margin-left: auto;
                margin-right: auto;
            }

            .features-grid {
                grid-template-columns: 1fr;
            }

            .feature-card {
                padding: 24px;
            }
        }

        /* Animações de entrada */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .feature-card {
            animation: fadeInUp 0.6s ease-out backwards;
        }

        .feature-card:nth-child(1) { animation-delay: 0.1s; }
        .feature-card:nth-child(2) { animation-delay: 0.2s; }
        .feature-card:nth-child(3) { animation-delay: 0.3s; }
        .feature-card:nth-child(4) { animation-delay: 0.4s; }
        .feature-card:nth-child(5) { animation-delay: 0.5s; }
        .feature-card:nth-child(6) { animation-delay: 0.6s; }
    </style>
</head>
<body>
    <!-- HEADER -->
    <header id="header">
        <div class="container">
            <div class="navbar">
                <div class="logo">Minhas Economias</div>
                <nav>
                    <div class="nav-links">
                        <a href="#como-funciona">Como funciona</a>
                        <a href="#beneficios">Benefícios</a>
                        <a href="#recursos">Recursos</a>
                    </div>
                    <div class="btn-group">
                        <a href="registar.php" class="btn btn-download">Registar</a>
                        <a href="login.php" class="btn btn-login">Entrar</a>
                    </div>
                </nav>
            </div>
        </div>
    </header>

    <!-- HERO SECTION -->
    <section class="hero">
        <div class="container">
            <div class="hero-content">
                <div class="hero-badge">✨ Novo design 2026</div>
                <h1>Tome controlo das suas finanças agora!</h1>
                <p>Simplifique seu orçamento, maximize seus ganhos e alcance independência financeira com uma plataforma moderna, segura e intuitiva.</p>
                <a href="registar.php" class="btn-cta">COMEÇAR GRÁTIS AGORA</a>
            </div>
        </div>
    </section>

    <!-- FEATURES SECTION -->
    <section class="features" id="recursos">
        <div class="container">
            <div class="section-header">
                <h2>Recursos Principais</h2>
                <p>Tudo o que precisa para gerir as suas finanças de forma inteligente e eficiente</p>
            </div>
            <div class="features-grid">
                <div class="feature-card">
                    <div class="feature-icon">📊</div>
                    <h3>Análise em Tempo Real</h3>
                    <p>Visualize instantaneamente onde vai seu dinheiro com relatórios claros, precisos e personalizáveis.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">🎯</div>
                    <h3>Orçamento Inteligente</h3>
                    <p>Planeje seus gastos com precisão e acompanhe o progresso mês a mês para manter o controlo.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">🔐</div>
                    <h3>Segurança Total</h3>
                    <p>Seus dados financeiros protegidos com encriptação de nível bancário e padrões internacionais.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">📱</div>
                    <h3>Sem Limites</h3>
                    <p>Acesse a qualquer momento, de qualquer dispositivo, sem taxas ocultas ou assinaturas.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">💡</div>
                    <h3>Metas Financeiras</h3>
                    <p>Defina objetivos claros e acompanhe o caminho rumo à independência e estabilidade financeira.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">📈</div>
                    <h3>Relatórios Detalhados</h3>
                    <p>Gráficos intuitivos e análises profundas para compreender melhor seus padrões de gastos.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- BENEFITS SECTION -->
    <section class="benefits" id="beneficios">
        <div class="container">
            <div class="benefits-container">
                <div class="benefits-content">
                    <h2>Transforme sua relação com o dinheiro</h2>
                    <p>Descubra como milhares de utilizadores já estão a alcançar a liberdade financeira com as nossas ferramentas.</p>
                    <ul class="benefit-list">
                        <li>Visão completa das suas finanças em um único painel intuitivo</li>
                        <li>Categorização automática e inteligente de todas as despesas</li>
                        <li>Alertas personalizados quando ultrapassar o orçamento definido</li>
                        <li>Relatórios detalhados mensais e anuais exportáveis</li>
                        <li>Interface moderna, simples e acessível em qualquer dispositivo</li>
                        <li>Completamente gratuito, sem custos surpresa ou limitações</li>
                        <li>Acesso offline disponível para consultas rápidas</li>
                        <li>Exporte dados em PDF, Excel ou CSV com um clique</li>
                    </ul>
                </div>
                <div class="benefits-image">
                    <div class="benefits-image-content">
                        <div class="icon">📈</div>
                        <p>Crescimento constante</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA SECTION -->
    <section class="cta-section" id="como-funciona">
        <div class="container">
            <h2>Comece a economizar hoje</h2>
            <p>Junte-se a milhares de utilizadores que já estão a controlar suas finanças com confiança, segurança e simplicidade.</p>
            <a href="registar.php" class="btn-cta-white">Criar Conta Gratuita →</a>
        </div>
    </section>

    <!-- FOOTER -->
    <footer>
        <div class="container">
            <div class="footer-content">
                <div class="footer-logo">💰 Minhas Economias</div>
                <div class="footer-links">
                    <a href="#">Sobre nós</a>
                    <a href="#">Privacidade</a>
                    <a href="#">Termos</a>
                    <a href="#">Contacto</a>
                </div>
                <div class="footer-divider"></div>
                <p>&copy; 2026 Minhas Economias. Todos os direitos reservados.</p>
                <p style="font-size: 12px; margin-top: 8px;">Seu companheiro de confiança na gestão financeira pessoal.</p>
            </div>
        </div>
    </footer>

    <script>
        // Efeito de scroll no header
        window.addEventListener('scroll', () => {
            const header = document.getElementById('header');
            if (window.scrollY > 50) {
                header.classList.add('scrolled');
            } else {
                header.classList.remove('scrolled');
            }
        });
    </script>
</body>
</html>