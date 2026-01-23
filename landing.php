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
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
        }

        /* HEADER / NAVBAR */
        header {
            background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
            color: white;
            padding: 20px 0;
            position: sticky;
            top: 0;
            z-index: 1000;
            box-shadow: 0 2px 10px rgba(99, 102, 241, 0.2);
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
        }

        .logo {
            font-size: 24px;
            font-weight: 700;
            letter-spacing: 1px;
        }

        nav {
            display: flex;
            gap: 30px;
            align-items: center;
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
            border-radius: 5px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-block;
            font-size: 14px;
        }

        .btn-download {
            background: white;
            color: #6366f1;
            border: 2px solid white;
        }

        .btn-download:hover {
            background: rgba(255,255,255,0.1);
            color: white;
        }

        .btn-login {
            background: #FFD600;
            color: #333;
            border: 2px solid #FFD600;
        }

        .btn-login:hover {
            background: #FFC300;
            border-color: #FFC300;
        }

        /* HERO SECTION */
        .hero {
            background: linear-gradient(135deg, #1e293b 0%, #334155 100%);
            color: white;
            padding: 120px 20px;
            text-align: left;
            position: relative;
            overflow: hidden;
        }

        .hero::before {
            content: '';
            position: absolute;
            right: 0;
            top: 0;
            width: 50%;
            height: 100%;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 500 500"><circle cx="250" cy="250" r="200" fill="rgba(99,102,241,0.05)"/><circle cx="100" cy="100" r="150" fill="rgba(139,92,246,0.05)"/></svg>');
            opacity: 0.5;
        }

        .hero-content {
            max-width: 600px;
            position: relative;
            z-index: 2;
        }

        .hero h1 {
            font-size: 48px;
            margin-bottom: 20px;
            line-height: 1.2;
            font-weight: 700;
        }

        .hero p {
            font-size: 18px;
            margin-bottom: 30px;
            opacity: 0.9;
            line-height: 1.8;
        }

        .btn-cta {
            background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
            color: white;
            padding: 14px 40px;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-block;
        }

        .btn-cta:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(99, 102, 241, 0.3);
        }

        /* FEATURES SECTION */
        .features {
            padding: 80px 20px;
            background: #f8f9fa;
        }

        .features h2 {
            text-align: center;
            font-size: 36px;
            margin-bottom: 50px;
            color: #333;
        }

        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 30px;
            max-width: 1200px;
            margin: 0 auto;
        }

        .feature-card {
            background: white;
            padding: 30px;
            border-radius: 10px;
            text-align: center;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
            transition: all 0.3s;
        }

        .feature-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(99, 102, 241, 0.15);
        }

        .feature-icon {
            font-size: 48px;
            margin-bottom: 15px;
            height: 60px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #f0f4ff 0%, #f5f3ff 100%);
            border-radius: 12px;
            padding: 15px;
        }

        .feature-card h3 {
            font-size: 20px;
            margin-bottom: 15px;
            color: #333;
        }

        .feature-card p {
            color: #666;
            font-size: 14px;
            line-height: 1.6;
        }

        /* BENEFITS SECTION */
        .benefits {
            padding: 80px 20px;
            background: white;
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
            color: #333;
        }

        .benefit-list {
            list-style: none;
        }

        .benefit-list li {
            padding: 15px 0;
            padding-left: 40px;
            position: relative;
            color: #555;
            font-size: 16px;
        }

        .benefit-list li::before {
            content:6366f1
            position: absolute;
            left: 0;
            color: #00C853;
            font-weight: bold;
            font-size: 20px;
        }6366f1 0%, #8b5cf6

        .benefits-image {
            background: linear-gradient(135deg, #00C853 0%, #00A840 100%);
            height: 400px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 80px;
        }

        /* CTA SECTION */
        .cta-section {
            background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
            color: white;
            padding: 60px 20px;
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
            color: #6366f1;
            padding: 14px 40px;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-block;
        }

        .btn-cta-white:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.2);
        }

        /* FOOTER */
        footer {
            background: #1a1a2e;
            color: white;
            padding: 40px 20px;
            text-align: center;
        }

        footer p {
            opacity: 0.8;
            font-size: 14px;
        }

        /* RESPONSIVE */
        @media (max-width: 768px) {
            nav {
                gap: 15px;
                flex-wrap: wrap;
            }

            nav a {
                display: none;
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
                    <a href="#como-funciona">Como funciona</a>
                    <a href="#beneficios">Benefícios</a>
                    <a href="#recursos">Recursos</a>
                    <div class="btn-group">
                        <a href="registar.php" class="btn btn-download">Baixar agora</a>
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