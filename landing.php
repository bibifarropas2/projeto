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
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Lexend:wght@300;400;500;600;700&family=Sora:wght@500;600;700;800&display=swap');

        :root {
            --brand: #0f7a4d;
            --brand-2: #17a866;
            --accent: #d6a21b;
            --ink: #1f1a17;
            --ink-soft: #4b433b;
            --paper: #f8f3ea;
            --card: #fffdfa;
            --border: #e7ddcf;
            --shadow: 0 14px 34px rgba(31, 26, 23, 0.16);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Lexend', "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: var(--ink);
            background: var(--paper);
        }

        h1, h2, h3 {
            font-family: 'Sora', 'Lexend', sans-serif;
            letter-spacing: -0.02em;
        }

        /* HEADER / NAVBAR */
        header {
            background: linear-gradient(135deg, #0f7a4d 0%, #1d8c58 100%);
            color: white;
            padding: 20px 0;
            position: sticky;
            top: 0;
            z-index: 1000;
            box-shadow: 0 10px 25px rgba(15, 122, 77, 0.25);
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }

        .navbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 24px;
        }

        .logo {
            font-size: 24px;
            font-weight: 700;
            letter-spacing: 1px;
        }

        nav {
            display: flex;
            gap: 20px;
            align-items: center;
            flex-wrap: wrap;
            justify-content: flex-end;
        }

        .nav-links {
            display: flex;
            gap: 20px;
            align-items: center;
            flex-wrap: wrap;
        }

        nav a {
            color: white;
            text-decoration: none;
            font-size: 15px;
            font-weight: 500;
            transition: opacity 0.3s;
        }

        nav a:hover {
            opacity: 0.8;
        }

        .btn-group {
            display: flex;
            gap: 15px;
        }

        .btn {
            padding: 10px 24px;
            border: none;
            border-radius: 999px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
        }

        .btn-download {
            background: white;
            color: var(--brand);
            border: 2px solid white;
            box-shadow: 0 8px 18px rgba(255, 255, 255, 0.15);
        }

        .btn-download:hover {
            background: rgba(255,255,255,0.1);
            color: white;
        }

        .btn-login {
            background: var(--accent);
            color: #2b1f0d;
            border: 2px solid var(--accent);
        }

        .btn-login:hover {
            background: #FFC300;
            border-color: #FFC300;
        }

        /* HERO SECTION */
        .hero {
            background: linear-gradient(135deg, #0f7a4d 0%, #0c6c45 45%, #0b3e2c 100%);
            color: white;
            padding: 120px 20px;
            text-align: left;
            position: relative;
            overflow: hidden;
        }

        .hero::before {
            content: '';
            position: absolute;
            right: -10%;
            top: -20%;
            width: 60%;
            height: 140%;
            background: radial-gradient(circle at top, rgba(214, 162, 27, 0.18), transparent 60%),
                        radial-gradient(circle at 30% 70%, rgba(255, 255, 255, 0.08), transparent 55%);
            opacity: 0.9;
        }

        .hero-content {
            max-width: 600px;
            position: relative;
            z-index: 2;
        }

        .hero h1 {
            font-size: 50px;
            margin-bottom: 20px;
            line-height: 1.15;
            font-weight: 800;
        }

        .hero p {
            font-size: 18px;
            margin-bottom: 30px;
            opacity: 0.9;
            line-height: 1.8;
        }

        .btn-cta {
            background: linear-gradient(135deg, #d6a21b 0%, #f4c542 100%);
            color: #2b1f0d;
            padding: 14px 40px;
            border: none;
            border-radius: 999px;
            font-size: 16px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-block;
            box-shadow: 0 10px 25px rgba(214, 162, 27, 0.35);
        }

        .btn-cta:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(99, 102, 241, 0.3);
        }

        /* FEATURES SECTION */
        .features {
            padding: 80px 20px;
            background: var(--paper);
        }

        .features h2 {
            text-align: center;
            font-size: 36px;
            margin-bottom: 50px;
            color: var(--ink);
        }

        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 30px;
            max-width: 1200px;
            margin: 0 auto;
        }

        .feature-card {
            background: var(--card);
            padding: 30px;
            border-radius: 16px;
            text-align: center;
            border: 1px solid var(--border);
            box-shadow: 0 8px 18px rgba(31, 26, 23, 0.08);
            transition: all 0.3s;
        }

        .feature-card:hover {
            transform: translateY(-6px);
            box-shadow: 0 16px 30px rgba(15, 122, 77, 0.18);
        }

        .feature-icon {
            font-size: 48px;
            margin-bottom: 15px;
            height: 60px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, rgba(214, 162, 27, 0.15) 0%, rgba(15, 122, 77, 0.08) 100%);
            border-radius: 14px;
            padding: 15px;
        }

        .feature-card h3 {
            font-size: 20px;
            margin-bottom: 15px;
            color: var(--ink);
        }

        .feature-card p {
            color: var(--ink-soft);
            font-size: 14px;
            line-height: 1.6;
        }

        /* BENEFITS SECTION */
        .benefits {
            padding: 80px 20px;
            background: #fff;
        }

        .benefits-container {
            max-width: 1200px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 60px;
            align-items: center;
        }

        .benefits h2 {
            font-size: 36px;
            margin-bottom: 30px;
            color: var(--ink);
        }

        .benefit-list {
            list-style: none;
        }

        .benefit-list li {
            padding: 15px 0;
            padding-left: 40px;
            position: relative;
            color: var(--ink-soft);
            font-size: 16px;
        }

        .benefit-list li::before {
            content: "✓";
            position: absolute;
            left: 0;
            color: var(--brand);
            font-weight: 700;
            font-size: 20px;
        }

        .benefits-image {
            background: linear-gradient(135deg, #0f7a4d 0%, #0c6c45 100%);
            height: 400px;
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 80px;
            box-shadow: var(--shadow);
        }

        /* CTA SECTION */
        .cta-section {
            background: linear-gradient(135deg, #0f7a4d 0%, #115e42 100%);
            color: white;
            padding: 70px 20px;
            text-align: center;
        }

        .cta-section h2 {
            font-size: 36px;
            margin-bottom: 20px;
        }

        .cta-section p {
            font-size: 18px;
            margin-bottom: 30px;
            opacity: 0.95;
        }

        .btn-cta-white {
            background: white;
            color: var(--brand);
            padding: 14px 40px;
            border: none;
            border-radius: 999px;
            font-size: 16px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-block;
            box-shadow: 0 10px 25px rgba(255, 255, 255, 0.15);
        }

        .btn-cta-white:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.2);
        }

        /* FOOTER */
        footer {
            background: #1f1a17;
            color: white;
            padding: 50px 20px;
            text-align: center;
        }

        footer p {
            opacity: 0.8;
            font-size: 14px;
        }

        /* RESPONSIVE */
        @media (max-width: 768px) {
            nav {
                gap: 12px;
                justify-content: flex-start;
            }

            .nav-links {
                width: 100%;
                justify-content: flex-start;
            }

            .hero h1 {
                font-size: 36px;
            }

            .hero p {
                font-size: 16px;
            }

            .benefits-container {
                grid-template-columns: 1fr;
                gap: 30px;
            }

            .benefits-image {
                height: 300px;
            }

            .features-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <!-- HEADER -->
    <header>
        <div class="container">
            <div class="navbar">
                <div class="logo">💰 Minhas Economias</div>
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
                <h1>Tome controlo das suas finanças agora!</h1>
                <p>Simplifique seu orçamento, maximize seus ganhos e alcance independência financeira com uma plataforma moderna e segura.</p>
                <a href="registar.php" class="btn-cta">COMEÇAR GRÁTIS AGORA</a>
            </div>
        </div>
    </section>

    <!-- FEATURES SECTION -->
    <section class="features" id="recursos">
        <div class="container">
            <h2>Recursos Principais</h2>
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
                <div>
                    <h2>Transforme sua relação com o dinheiro</h2>
                    <ul class="benefit-list">
                        <li>Visão completa das suas finanças em um único lugar</li>
                        <li>Categorização automática e inteligente de despesas</li>
                        <li>Alertas de gastos acima do orçamento</li>
                        <li>Relatórios detalhados mensais e anuais</li>
                        <li>Interface simples, intuitiva e amigável</li>
                        <li>Completamente gratuito, sem custos surpresa</li>
                        <li>Acesso rápido até mesmo sem internet</li>
                        <li>Exportar dados em múltiplos formatos</li>
                    </ul>
                </div>
                <div class="benefits-image">📈</div>
            </div>
        </div>
    </section>

    <!-- CTA SECTION -->
    <section class="cta-section" id="como-funciona">
        <div class="container">
            <h2>Comece a economizar hoje</h2>
            <p>Junte-se a milhares de utilizadores que já estão controlando suas finanças com confiança, segurança e simplicidade.</p>
            <a href="registar.php" class="btn-cta-white">Criar Conta Gratuita</a>
        </div>
    </section>

    <!-- FOOTER -->
    <footer>
        <div class="container">
            <p>&copy; 2026 Minhas Economias. Todos os direitos reservados.</p>
            <p style="margin-top: 10px; font-size: 12px;">Seu companheiro de confiança na gestão financeira pessoal.</p>
        </div>
    </footer>
</body>
</html>
