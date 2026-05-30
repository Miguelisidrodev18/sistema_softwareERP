    <!DOCTYPE html>
    <html lang="es">
    <head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ESTELAR - Software Empresarial</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;500;600;700;800;900&family=Inter:wght@300;400;500;600;700&family=Space+Mono:wght@400;700&family=Barlow+Condensed:wght@700;900&display=swap" rel="stylesheet">
    <link href="https://fonts.cdnfonts.com/css/neuropol-x" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
    *,*::before,*::after{margin:0;padding:0;box-sizing:border-box}
    :root{
    --bg:#060912;
    --blue-deep:#0d1b3e;
    --blue-corp:#1a3a7c;
    --blue-mid:#2557cc;
    --blue-light:#5b9bd5;
    --blue-glow:#73c6f5;
    --white:#ffffff;
    --text-muted:#7a8fa8;
    --text-dim:#4a5a70;
    --gold:#c9a84c;
    --gold-light:#f0d080;
    --border:rgba(91,155,213,.18);
    --border-glow:rgba(115,198,245,.40);
    --surface:rgba(6,12,24,.78);
    --card-bg:rgba(10,18,36,.72);
    }
    html{scroll-behavior:smooth}
    body{font-family:'Inter',sans-serif;background:var(--bg);color:var(--white);overflow-x:hidden;min-height:100vh;-webkit-font-smoothing:antialiased}
    ::selection{background:rgba(115,198,245,.2);color:var(--white)}
    ::-webkit-scrollbar{width:6px}
    ::-webkit-scrollbar-track{background:transparent}
    ::-webkit-scrollbar-thumb{background:var(--blue-mid);border-radius:4px}
    ::-webkit-scrollbar-thumb:hover{background:var(--blue-glow)}
    /* BG LAYERS */
    .bg-stars{position:fixed;inset:0;z-index:0;pointer-events:none}
    canvas#particles-canvas{position:fixed;inset:0;z-index:1;pointer-events:none}
    .bg-grid{position:fixed;inset:0;z-index:0;opacity:.18;background-image:linear-gradient(rgba(115,198,245,.06) 1px,transparent 1px),linear-gradient(90deg,rgba(115,198,245,.06) 1px,transparent 1px);background-size:90px 90px;animation:gridPan 60s linear infinite;pointer-events:none}
    @keyframes gridPan{from{background-position:0 0}to{background-position:90px 90px}}
    .main-container{position:relative;z-index:2}
    /* NAV */
    nav{position:fixed;top:0;width:100%;z-index:1000;padding:1rem 0;background:rgba(6,9,18,.62);backdrop-filter:blur(28px);border-bottom:1px solid rgba(115,198,245,.08);transition:all .3s ease}
    nav.scrolled{background:rgba(6,9,18,.94);box-shadow:0 18px 55px rgba(0,0,0,.35)}
    .nav-inner{max-width:1280px;margin:0 auto;padding:0 2rem;display:flex;justify-content:space-between;align-items:center}
    .logo-wrap{display:flex;align-items:center;gap:14px;text-decoration:none}
    .logo-icon{width:48px;height:48px;background:transparent;border-radius:0;display:flex;align-items:center;justify-content:center;flex-shrink:0;padding:0;animation:logoGlow 3.5s ease-in-out infinite}
    @keyframes logoGlow{0%,100%{filter:drop-shadow(0 0 4px rgba(0,200,195,.6)) drop-shadow(0 0 8px rgba(37,87,204,.35))}50%{filter:drop-shadow(0 0 14px rgba(0,228,215,.92)) drop-shadow(0 0 28px rgba(0,180,200,.5)) drop-shadow(0 0 44px rgba(37,87,204,.28))}}
    .logo-text-wrap{display:flex;flex-direction:column;line-height:1.1}
    .logo-name{font-family:'Orbitron',sans-serif;font-size:1.2rem;font-weight:900;letter-spacing:4px;background:linear-gradient(135deg,#fff 0%,var(--blue-glow) 100%);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text}
    .logo-sub{font-family:'Orbitron',sans-serif;font-size:.42rem;letter-spacing:3px;color:var(--blue-light);font-weight:400}
    .nav-links{display:flex;align-items:center;gap:1.75rem;list-style:none}
    .nav-links a{font-family:'Inter',sans-serif;font-size:.75rem;font-weight:600;letter-spacing:1px;color:rgba(255,255,255,.78);text-decoration:none;text-transform:uppercase;transition:color .25s ease,transform .25s ease;position:relative}
    .nav-links a::after{content:'';position:absolute;bottom:-6px;left:0;width:0;height:2px;background:var(--blue-light);transition:width .25s ease}
    .nav-links a:hover{color:var(--white);transform:translateY(-1px)}
    .nav-links a:hover::after{width:100%}
    .btn-nav{font-family:'Inter',sans-serif;font-size:.75rem;font-weight:700;letter-spacing:1.5px;text-transform:uppercase;padding:.8rem 1.6rem;background:linear-gradient(180deg,rgba(0,120,255,.28),rgba(0,65,200,.42));color:white;border:1px solid rgba(0,190,255,.62);border-radius:6px;text-decoration:none;transition:all .3s ease;box-shadow:0 0 18px rgba(0,165,255,.42),0 0 35px rgba(0,100,220,.15)}
    .btn-nav:hover{background:linear-gradient(180deg,rgba(0,175,255,.42),rgba(0,100,230,.58));box-shadow:0 0 30px rgba(0,210,255,.62);border-color:rgba(0,235,255,.88);transform:translateY(-2px)}
    .btn-erp{background:linear-gradient(180deg,rgba(201,168,76,.22),rgba(180,140,40,.38))!important;border-color:rgba(240,208,128,.62)!important;box-shadow:0 0 18px rgba(201,168,76,.35),0 0 35px rgba(180,140,40,.15)!important;color:#f0d080!important}
    .btn-erp:hover{background:linear-gradient(180deg,rgba(240,208,128,.38),rgba(201,168,76,.52))!important;box-shadow:0 0 30px rgba(240,208,128,.65)!important;border-color:rgba(255,230,150,.9)!important;color:#fff!important}
    .mobile-toggle{display:none;background:none;border:none;color:white;font-size:1.4rem;cursor:pointer}/* HERO */
    .hero{min-height:100vh;display:flex;align-items:center;justify-content:center;padding:120px 2rem 70px;position:relative;overflow:hidden;background:radial-gradient(ellipse 70% 55% at 50% 42%,rgba(0,40,100,.38) 0%,rgba(0,15,50,.18) 55%,transparent 75%)}
    .hero-slides-wrap{position:relative;width:100%;max-width:1040px;text-align:center;padding:3rem 2.5rem 2.5rem;background:transparent;border:none;backdrop-filter:none;box-shadow:none;overflow:hidden}
    .hero-slide{position:absolute;inset:0;opacity:0;transform:translateY(22px) scale(.97);transition:opacity .9s cubic-bezier(.22,1,.36,1),transform .9s ease;pointer-events:none;display:flex;flex-direction:column;align-items:center;padding:2rem 2rem 1.5rem}
    .hero-slide.active{opacity:1;position:relative;pointer-events:all;transform:translateY(0) scale(1)}
    .hero-badge{display:inline-flex;align-items:center;gap:10px;padding:11px 28px;border:1px solid rgba(0,210,200,.38);border-radius:999px;background:rgba(4,16,36,.88);font-family:'Inter',sans-serif;font-size:.74rem;font-weight:700;letter-spacing:2px;color:rgba(0,225,215,.92);text-transform:uppercase;margin-bottom:3rem;box-shadow:0 0 30px rgba(0,200,195,.18),inset 0 0 20px rgba(0,180,180,.05);position:relative}
    .hero-badge::after{content:'';position:absolute;bottom:-20px;left:50%;transform:translateX(-50%);width:80%;height:12px;background:radial-gradient(ellipse,rgba(0,200,195,.55) 0%,transparent 72%);border-radius:50%;filter:blur(5px);pointer-events:none}
    .badge-dot{width:8px;height:8px;border-radius:50%;background:rgba(0,225,215,1);box-shadow:0 0 12px rgba(0,210,200,.9);animation:pulseDot 2s infinite}
    @keyframes pulseDot{0%,100%{transform:scale(1);opacity:1}50%{transform:scale(1.8);opacity:.5}}
    .hero h1{font-family:'Neuropol X','Barlow Condensed',sans-serif;font-size:clamp(3.6rem,9.5vw,8rem);font-weight:400;line-height:.92;letter-spacing:.08em;text-transform:uppercase;color:#ddf4f2;text-shadow:0 0 18px rgba(0,210,200,.8),0 0 40px rgba(0,160,200,.5),0 0 80px rgba(0,80,180,.3),0 4px 12px rgba(0,0,0,.98);margin-bottom:1.6rem}
    .hero p{font-size:1.05rem;color:rgba(180,220,218,.88);max-width:680px;margin:0 auto 3rem;line-height:1.9;font-weight:400;text-shadow:0 1px 8px rgba(0,0,0,.8)}
    .hero-btns{display:flex;gap:1rem;justify-content:center;flex-wrap:wrap}
    .btn-primary{font-family:'Inter',sans-serif;font-size:.82rem;font-weight:800;letter-spacing:2.5px;text-transform:uppercase;padding:.9rem 2.4rem;background:linear-gradient(180deg,rgba(0,130,255,.28),rgba(0,65,200,.42));color:white;border:1px solid rgba(0,195,255,.68);border-radius:6px;text-decoration:none;display:inline-flex;align-items:center;gap:8px;transition:all .3s ease;box-shadow:0 0 22px rgba(0,175,255,.48),0 0 45px rgba(0,100,220,.2),inset 0 1px 0 rgba(255,255,255,.1);cursor:pointer}
    .btn-primary:hover{transform:translateY(-2px);box-shadow:0 0 35px rgba(0,220,255,.68),0 0 65px rgba(0,150,255,.32);border-color:rgba(0,235,255,.92);background:linear-gradient(180deg,rgba(0,155,255,.38),rgba(0,85,220,.52))}
    .btn-ghost{font-family:'Inter',sans-serif;font-size:.82rem;font-weight:700;letter-spacing:2.5px;text-transform:uppercase;padding:.9rem 2.4rem;background:rgba(0,40,85,.35);color:var(--white);border:1px solid rgba(0,175,255,.45);border-radius:6px;text-decoration:none;display:inline-flex;align-items:center;gap:8px;transition:all .3s ease;backdrop-filter:blur(12px);box-shadow:0 0 15px rgba(0,150,255,.2),inset 0 1px 0 rgba(255,255,255,.05);cursor:pointer}
    .btn-ghost:hover{background:rgba(0,95,200,.25);border-color:rgba(0,220,255,.72);box-shadow:0 0 28px rgba(0,180,255,.38);transform:translateY(-2px)}
    .hero-dots{display:flex;gap:12px;justify-content:center;margin-top:2.5rem}
    .hero-dot{width:10px;height:10px;border-radius:999px;background:rgba(0,200,190,.22);cursor:pointer;transition:all .3s ease;border:1px solid rgba(0,200,190,.3)}
    .hero-dot.active{background:rgba(0,220,210,.9);width:32px;box-shadow:0 0 20px rgba(0,210,200,.7);border-color:rgba(0,220,210,.6)}
    /* STATS BAR */
    .stats-section{background:linear-gradient(180deg,rgba(6,12,24,.84),rgba(6,12,24,.74));border-top:1px solid rgba(115,198,245,.18);border-bottom:1px solid rgba(115,198,245,.18);backdrop-filter:blur(28px);padding:2rem;box-shadow:0 0 45px rgba(37,87,204,.12)}
    .stats-inner{max-width:1280px;margin:0 auto;display:grid;grid-template-columns:repeat(4,1fr);gap:2rem;text-align:center}
    .stat-item{position:relative}
    .stat-item:not(:last-child)::after{content:'';position:absolute;right:0;top:10%;height:80%;width:1px;background:var(--border)}
    .stat-num{font-family:'Orbitron',sans-serif;font-size:2.2rem;font-weight:900;background:linear-gradient(135deg,var(--white),var(--blue-glow));-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;display:block}
    .stat-label{font-size:.8rem;color:var(--text-muted);text-transform:uppercase;letter-spacing:2px;font-family:'Space Mono',monospace;margin-top:.25rem}
    /* SECTIONS */
    section{padding:6rem 2rem}
    .container{max-width:1280px;margin:0 auto}
    .section-label{font-family:'Space Mono',monospace;font-size:.7rem;letter-spacing:3.5px;text-transform:uppercase;color:var(--blue-light);display:inline-flex;align-items:center;gap:.4rem;margin-bottom:1rem}
    .section-title{font-family:'Inter',sans-serif;font-size:clamp(1.9rem,3.8vw,3.3rem);font-weight:800;letter-spacing:.02em;text-transform:uppercase;background:linear-gradient(135deg,#ffffff 25%,var(--blue-light) 95%);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;margin-bottom:1rem}
    .section-sub{color:var(--text-muted);font-size:1.05rem;max-width:720px;line-height:1.9}
    .section-head{margin-bottom:4rem}
    .section-head.center{text-align:center}
    .section-head.center .section-sub{margin:0 auto}/* CARDS */
    .card{background:var(--card-bg);border:1px solid var(--border);border-radius:20px;padding:1.8rem;backdrop-filter:blur(14px);transition:all .3s ease;position:relative;overflow:hidden}
    .card::before{content:'';position:absolute;inset:0;background:linear-gradient(135deg,rgba(115,198,245,.05),transparent);opacity:0;transition:opacity .3s}
    .card:hover{border-color:var(--border-glow);box-shadow:0 12px 40px rgba(37,87,204,.18);transform:translateY(-5px)}
    .card:hover::before{opacity:1}
    .card-icon{width:50px;height:50px;border-radius:14px;display:flex;align-items:center;justify-content:center;font-size:1.2rem;margin-bottom:1.2rem;background:linear-gradient(135deg,var(--blue-corp),var(--blue-mid));box-shadow:0 8px 20px rgba(37,87,204,.3)}
    .card-title{font-family:'Inter',sans-serif;font-size:1.05rem;font-weight:700;color:var(--white);margin-bottom:.7rem;letter-spacing:.3px}
    .card-body{color:var(--text-muted);font-size:.9rem;line-height:1.7}
    /* SPOTLIGHT CARDS (características) */
    .spotlight-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:1.5rem}
    /* BOT CARDS */
    .bots-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(290px,1fr));gap:1.5rem}
    .bot-card{background:var(--card-bg);border:1px solid var(--border);border-radius:20px;padding:1.8rem;backdrop-filter:blur(14px);transition:all .3s ease}
    .bot-card:hover{border-color:var(--border-glow);box-shadow:0 12px 40px rgba(37,87,204,.18);transform:translateY(-5px)}
    .bot-top{display:flex;align-items:center;gap:14px;margin-bottom:1rem}
    .bot-avatar{width:50px;height:50px;border-radius:14px;display:flex;align-items:center;justify-content:center;font-size:1.3rem;background:linear-gradient(135deg,var(--blue-corp),var(--blue-mid));box-shadow:0 8px 20px rgba(37,87,204,.3);flex-shrink:0}
    .bot-name{font-family:'Inter',sans-serif;font-weight:700;font-size:1rem;color:var(--white)}
    .bot-role{font-size:.78rem;color:var(--text-muted)}
    .bot-desc{font-size:.88rem;color:var(--text-muted);line-height:1.7;margin-bottom:1rem}
    .bot-tags{display:flex;gap:.6rem;flex-wrap:wrap}
    .bot-tag{background:rgba(37,87,204,.18);border:1px solid rgba(91,155,213,.2);border-radius:6px;padding:.25rem .65rem;font-size:.72rem;font-family:'Space Mono',monospace;color:var(--blue-light)}
    /* PLANES */
    .planes-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:2rem}
    .plan-card{background:var(--card-bg);border:1px solid var(--border);border-radius:24px;padding:2.2rem;backdrop-filter:blur(14px);position:relative;transition:all .3s ease}
    .plan-card.featured{border-color:var(--blue-glow);box-shadow:0 0 50px rgba(115,198,245,.15)}
    .plan-card:hover{transform:translateY(-4px);box-shadow:0 16px 50px rgba(37,87,204,.2)}
    .plan-popular{position:absolute;top:-14px;left:50%;transform:translateX(-50%);background:linear-gradient(135deg,var(--blue-mid),var(--blue-glow));color:#fff;font-family:'Orbitron',sans-serif;font-size:.6rem;font-weight:800;letter-spacing:2px;padding:5px 16px;border-radius:999px;white-space:nowrap}
    .plan-name{font-family:'Orbitron',sans-serif;font-size:.9rem;font-weight:700;color:var(--text-muted);letter-spacing:2px;text-transform:uppercase;margin-bottom:.8rem}
    .plan-price{font-family:'Orbitron',sans-serif;font-size:3rem;font-weight:900;color:var(--white);margin-bottom:.3rem}
    .plan-price sup{font-size:1.4rem;color:var(--blue-glow);vertical-align:super}
    .plan-period{font-size:.82rem;color:var(--text-muted);margin-bottom:1.8rem}
    .plan-divider{border:none;border-top:1px solid var(--border);margin-bottom:1.8rem}
    .plan-features{list-style:none;margin-bottom:2rem}
    .plan-features li{display:flex;align-items:center;gap:10px;padding:.6rem 0;font-size:.9rem;color:var(--text-muted);border-bottom:1px solid rgba(255,255,255,.03)}
    .plan-features li:last-child{border-bottom:none}
    .plan-features li i{color:var(--blue-glow);font-size:.8rem;flex-shrink:0}
    /* CTA */
    .cta-wrap{background:linear-gradient(135deg,rgba(13,27,62,.9),rgba(26,58,124,.7));border:1px solid rgba(91,155,213,.25);border-radius:28px;text-align:center;padding:5rem 2rem}
    /* CONTACT FORM */
    .contact-form{max-width:640px;margin:0 auto;background:var(--card-bg);border:1px solid var(--border);border-radius:24px;padding:2.5rem;backdrop-filter:blur(14px)}
    .form-group{margin-bottom:1.4rem}
    .form-group label{display:block;font-size:.8rem;font-weight:600;color:var(--blue-light);margin-bottom:.45rem;letter-spacing:.5px;text-transform:uppercase}
    .form-group input,.form-group textarea,.form-group select{width:100%;padding:.85rem 1rem;background:rgba(13,27,62,.6);border:1px solid var(--border);border-radius:10px;color:var(--white);font-family:'Inter',sans-serif;font-size:.9rem;transition:border-color .25s;outline:none}
    .form-group input:focus,.form-group textarea:focus,.form-group select:focus{border-color:var(--blue-glow)}
    .form-group textarea{min-height:120px;resize:vertical}
    .form-row{display:grid;grid-template-columns:1fr 1fr;gap:1rem}
    /* FOOTER */
    footer{background:rgba(6,9,18,.9);border-top:1px solid var(--border);padding:4rem 2rem}
    .footer-inner{max-width:1280px;margin:0 auto;display:grid;grid-template-columns:1.6fr 1fr 1fr 1fr;gap:3rem}
    .footer-brand p{color:var(--text-muted);font-size:.88rem;line-height:1.7;margin-top:.9rem;max-width:280px}
    .footer-col h4{font-family:'Orbitron',sans-serif;font-size:.72rem;font-weight:700;color:var(--white);letter-spacing:2px;text-transform:uppercase;margin-bottom:1.2rem}
    .footer-col ul{list-style:none}
    .footer-col ul li a{color:var(--text-muted);text-decoration:none;font-size:.88rem;display:block;padding:.35rem 0;transition:color .2s}
    .footer-col ul li a:hover{color:var(--blue-glow)}
    .footer-bottom{max-width:1280px;margin:2.5rem auto 0;padding-top:2rem;border-top:1px solid var(--border);display:flex;justify-content:space-between;align-items:center;font-size:.82rem;color:var(--text-muted);flex-wrap:wrap;gap:1rem}
    .social-links{display:flex;gap:1rem}
    .social-links a{width:36px;height:36px;border-radius:9px;background:rgba(37,87,204,.15);border:1px solid var(--border);display:flex;align-items:center;justify-content:center;color:var(--text-muted);text-decoration:none;transition:all .25s}
    .social-links a:hover{background:var(--blue-mid);color:white;border-color:var(--blue-glow);transform:translateY(-2px)}
    /* REVEAL */
    .reveal{opacity:0;transform:translateY(24px);transition:opacity .6s ease,transform .6s ease}
    .reveal.visible{opacity:1;transform:translateY(0)}
    /* RESPONSIVE */
    @media(max-width:1024px){.footer-inner{grid-template-columns:1fr 1fr}.stats-inner{grid-template-columns:repeat(2,1fr)}}
    @media(max-width:768px){
    section{padding:4rem 1rem}
    .nav-links{display:none}
    .nav-links.open{display:flex;flex-direction:column;position:fixed;top:78px;left:0;right:0;background:rgba(6,9,18,.98);padding:2rem;gap:1.5rem;border-bottom:1px solid var(--border)}
    .mobile-toggle{display:block}
    .stats-inner{grid-template-columns:repeat(2,1fr)}
    .footer-inner{grid-template-columns:1fr;gap:2rem}
    .footer-bottom{flex-direction:column;text-align:center}
    .form-row{grid-template-columns:1fr}
    }
    
    </style>
    </head>
    <body>
    <div class="bg-grid"></div>
    <canvas id="particles-canvas"></canvas>
    <div class="main-container"><nav id="navbar">
    <div class="nav-inner">
        <a href="#inicio" class="logo-wrap">
        <div style="height:68px;display:flex;align-items:center;animation:logoGlow 3.5s ease-in-out infinite"><img src="img/logo_final.png" alt="ESTELAR" height="58" style="height:98px;width:auto;display:block;object-fit:contain"></div>
        </a>
        <ul class="nav-links" id="navLinks">
        <li><a href="#inicio">Inicio</a></li>
        <li><a href="#caracteristicas">Caracter&iacute;sticas</a></li>
        <li><a href="#clientes">Clientes</a></li>
        <li><a href="#planes">Planes</a></li>
        <li><a href="#contacto">Contacto</a></li>
        @auth
        <li><a href="{{ route('dashboard') }}" style="color:#f0d080"><i class="fas fa-th-large"></i> Panel ERP</a></li>
        @else
        <li><a href="{{ route('login') }}" style="color:#f0d080"><i class="fas fa-sign-in-alt"></i> Ingresar al ERP</a></li>
        @endauth
        </ul>
        @auth
        <a href="{{ route('dashboard') }}" class="btn-nav btn-erp"><i class="fas fa-th-large"></i> Panel ERP</a>
        @else
        <a href="{{ route('login') }}" class="btn-nav btn-erp"><i class="fas fa-sign-in-alt"></i> Ingresar</a>
        @endauth
        <a href="#contacto" class="btn-nav">Demo Gratuita</a>
        <button class="mobile-toggle" id="mobileToggle"><i class="fas fa-bars"></i></button>
    </div>
    </nav>

    <!-- HERO -->
    <section class="hero" id="inicio">
    <div class="hero-slides-wrap">
        <div class="hero-slide active" id="slide-0">
        <div class="hero-badge"><div class="badge-dot"></div> Automatizaci&oacute;n Inteligente</div>
        <h1>Bots &amp; IA que<br>trabajan por ti</h1>
        <p>Reduce el trabajo manual hasta un 80%. Bots inteligentes que aprenden, optimizan y ejecutan tus flujos 24/7 sin descanso.</p>
        <div class="hero-btns">
            <a href="#contacto" class="btn-primary"><i class="fas fa-bolt"></i> Automatizar Ahora</a>
            <a href="#caracteristicas" class="btn-ghost"><i class="fas fa-eye"></i> Ver Soluciones</a>
        </div>
        </div>
        <div class="hero-slide" id="slide-1">
        <div class="hero-badge"><div class="badge-dot"></div> Cloud Empresarial</div>
        <h1>Plataforma<br>Cloud Nativa</h1>
        <p>Infraestructura de nivel enterprise con 99.9% de uptime. Escala desde startups hasta corporaciones sin cambiar de plataforma.</p>
        <div class="hero-btns">
            <a href="#planes" class="btn-primary"><i class="fas fa-rocket"></i> Ver Planes</a>
            <a href="#caracteristicas" class="btn-ghost"><i class="fas fa-info-circle"></i> M&aacute;s Info</a>
        </div>
        </div>
        <div class="hero-slide" id="slide-2">
        <div class="hero-badge"><div class="badge-dot"></div> Integraciones Nativas</div>
        <h1>Conecta todo<br>tu ecosistema</h1>
        <p>+50 integraciones nativas con WhatsApp, Salesforce, SAP, Google Workspace y las herramientas que ya usas a diario.</p>
        <div class="hero-btns">
            <a href="#contacto" class="btn-primary"><i class="fas fa-plug"></i> Solicitar Demo</a>
            <a href="#caracteristicas" class="btn-ghost"><i class="fas fa-list"></i> Ver Integraciones</a>
        </div>
        </div>
        <div class="hero-dots" id="heroDots">
        <button class="hero-dot active" data-index="0"></button>
        <button class="hero-dot" data-index="1"></button>
        <button class="hero-dot" data-index="2"></button>
        </div>
    </div>
    </section>

    <!-- STATS -->
    <div class="stats-section">
    <div class="stats-inner">
        <div class="stat-item"><span class="stat-num" data-count="850" data-suffix="+">0</span><div class="stat-label">Empresas Activas</div></div>
        <div class="stat-item"><span class="stat-num" data-count="99" data-suffix=".9%">0</span><div class="stat-label">Uptime Garantizado</div></div>
        <div class="stat-item"><span class="stat-num" data-count="3200" data-suffix="+">0</span><div class="stat-label">Procesos Automatizados</div></div>
        <div class="stat-item"><span class="stat-num" data-count="50" data-suffix="+">0</span><div class="stat-label">Integraciones Nativas</div></div>
    </div>
    </div><!-- CARACTERÍSTICAS -->
    <section id="caracteristicas">
    <div class="container">
        <div class="section-head center reveal">
        <div class="section-label"><i class="fas fa-microchip"></i> Capacidades</div>
        <h2 class="section-title">Todo lo que tu empresa necesita</h2>
        <p class="section-sub">Una plataforma unificada que elimina el caos operativo y pone la inteligencia artificial al servicio de tu negocio.</p>
        </div>
        <div class="spotlight-grid">
        <div class="card reveal">
            <div class="card-icon"><i class="fas fa-brain"></i></div>
            <div class="card-title">IA Conversacional</div>
            <div class="card-body">Bots que entienden lenguaje natural, aprenden de cada interacci&oacute;n y resuelven consultas en segundos sin intervenci&oacute;n humana.</div>
        </div>
        <div class="card reveal">
            <div class="card-icon"><i class="fas fa-plug"></i></div>
            <div class="card-title">+50 Integraciones</div>
            <div class="card-body">Conecta con WhatsApp, Slack, Salesforce, SAP, Google Workspace y decenas de herramientas m&aacute;s con un solo clic.</div>
        </div>
        <div class="card reveal">
            <div class="card-icon"><i class="fas fa-shield-halved"></i></div>
            <div class="card-title">Seguridad Enterprise</div>
            <div class="card-body">Cifrado AES-256, autenticaci&oacute;n multifactor, auditor&iacute;a completa y cumplimiento de ISO 27001 y GDPR.</div>
        </div>
        <div class="card reveal">
            <div class="card-icon"><i class="fas fa-chart-line"></i></div>
            <div class="card-title">Analytics en Tiempo Real</div>
            <div class="card-body">Dashboards personalizables con m&eacute;tricas actualizadas al instante. Toma decisiones con datos, no con intuici&oacute;n.</div>
        </div>
        <div class="card reveal">
            <div class="card-icon"><i class="fas fa-code-branch"></i></div>
            <div class="card-title">Workflows sin C&oacute;digo</div>
            <div class="card-body">Editor visual drag-and-drop para dise&ntilde;ar flujos de trabajo complejos sin escribir una sola l&iacute;nea de c&oacute;digo.</div>
        </div>
        <div class="card reveal">
            <div class="card-icon"><i class="fas fa-headset"></i></div>
            <div class="card-title">Soporte 24 / 7</div>
            <div class="card-body">Equipo t&eacute;cnico especializado disponible a cualquier hora. Tiempo de respuesta menor a 3 minutos para incidencias cr&iacute;ticas.</div>
        </div>
        </div>
    </div>
    </section>

    <!-- BOTS & IA -->
    <section id="bots" style="padding-top:0">
    <div class="container">
        <div class="section-head center reveal">
        <div class="section-label"><i class="fas fa-robot"></i> Bots &amp; Agentes IA</div>
        <h2 class="section-title">Agentes listos para trabajar</h2>
        <p class="section-sub">Cada bot est&aacute; especializado en su &aacute;rea. Desp&iacute;egalos en minutos y mide resultados desde el primer d&iacute;a.</p>
        </div>
        <div class="bots-grid">
        <div class="bot-card reveal">
            <div class="bot-top"><div class="bot-avatar"><i class="fas fa-comments"></i></div><div><div class="bot-name">SupportBot Pro</div><div class="bot-role">Atenci&oacute;n al cliente &middot; 24/7</div></div></div>
            <p class="bot-desc">Resuelve consultas frecuentes, gestiona tickets y escala casos complejos al equipo humano de forma inteligente.</p>
            <div class="bot-tags"><span class="bot-tag">94% satisfacci&oacute;n</span><span class="bot-tag">&lt;2s respuesta</span></div>
        </div>
        <div class="bot-card reveal">
            <div class="bot-top"><div class="bot-avatar"><i class="fas fa-chart-bar"></i></div><div><div class="bot-name">DataAnalyst AI</div><div class="bot-role">An&aacute;lisis de datos &middot; BI</div></div></div>
            <p class="bot-desc">Procesa miles de filas de datos, genera reportes autom&aacute;ticos y env&iacute;a alertas cuando detecta anomal&iacute;as.</p>
            <div class="bot-tags"><span class="bot-tag">10x m&aacute;s r&aacute;pido</span><span class="bot-tag">99% precisi&oacute;n</span></div>
        </div>
        <div class="bot-card reveal">
            <div class="bot-top"><div class="bot-avatar"><i class="fas fa-envelope"></i></div><div><div class="bot-name">EmailFlow Bot</div><div class="bot-role">Email marketing &middot; CRM</div></div></div>
            <p class="bot-desc">Segmenta audiencias, crea campa&ntilde;as personalizadas y automatiza secuencias de seguimiento post-venta.</p>
            <div class="bot-tags"><span class="bot-tag">+35% apertura</span><span class="bot-tag">+22% conversi&oacute;n</span></div>
        </div>
        <div class="bot-card reveal">
            <div class="bot-top"><div class="bot-avatar"><i class="fas fa-boxes-stacked"></i></div><div><div class="bot-name">InventoryAI</div><div class="bot-role">Inventario &middot; Supply chain</div></div></div>
            <p class="bot-desc">Predice demanda, genera &oacute;rdenes de compra autom&aacute;ticas y evita quiebres de stock con modelos predictivos.</p>
            <div class="bot-tags"><span class="bot-tag">-40% sobrestock</span><span class="bot-tag">98% exactitud</span></div>
        </div>
        <div class="bot-card reveal">
            <div class="bot-top"><div class="bot-avatar"><i class="fas fa-file-invoice-dollar"></i></div><div><div class="bot-name">BillingBot</div><div class="bot-role">Facturaci&oacute;n &middot; Cobranzas</div></div></div>
            <p class="bot-desc">Genera facturas electr&oacute;nicas, env&iacute;a recordatorios de pago y reporta morosidad en tiempo real.</p>
            <div class="bot-tags"><span class="bot-tag">-60% morosidad</span><span class="bot-tag">SUNAT auto</span></div>
        </div>
        <div class="bot-card reveal">
            <div class="bot-top"><div class="bot-avatar"><i class="fas fa-user-tie"></i></div><div><div class="bot-name">HRAssistant</div><div class="bot-role">RRHH &middot; Onboarding</div></div></div>
            <p class="bot-desc">Filtra CVs, programa entrevistas, gestiona onboarding digital y responde consultas laborales al instante.</p>
            <div class="bot-tags"><span class="bot-tag">5h ahorro/semana</span><span class="bot-tag">100% digital</span></div>
        </div>
        </div>
    </div>
    </section><!-- CLIENTES -->
    <section id="clientes" style="padding-top:0">
    <div class="container">
        <div class="section-head center reveal">
        <div class="section-label"><i class="fas fa-trophy"></i> Casos de &Eacute;xito</div>
        <h2 class="section-title">Lo que dicen nuestros clientes</h2>
        <p class="section-sub">Empresas de todos los sectores conf&iacute;an en ESTELAR para transformar su operaci&oacute;n digital.</p>
        </div>
        <div class="spotlight-grid">
        <div class="card reveal" style="border-left:3px solid var(--blue-glow)">
            <div style="display:flex;align-items:center;gap:12px;margin-bottom:1rem">
            <div style="width:44px;height:44px;border-radius:50%;background:linear-gradient(135deg,var(--blue-corp),var(--blue-mid));display:flex;align-items:center;justify-content:center;font-weight:700;font-size:.9rem">MR</div>
            <div><div style="font-weight:600;font-size:.9rem">Mar&iacute;a Rodr&iacute;guez</div><div style="color:var(--text-muted);font-size:.78rem">Gerente Operaciones &middot; LogisPer&uacute;</div></div>
            </div>
            <p style="color:var(--text-muted);font-size:.9rem;line-height:1.7;font-style:italic">"ESTELAR redujo nuestros tiempos de procesamiento de pedidos en un 65%. El ROI fue visible desde el primer mes."</p>
            <div style="display:flex;gap:2px;margin-top:.8rem;color:#f0d080">&#9733;&#9733;&#9733;&#9733;&#9733;</div>
        </div>
        <div class="card reveal" style="border-left:3px solid var(--blue-glow)">
            <div style="display:flex;align-items:center;gap:12px;margin-bottom:1rem">
            <div style="width:44px;height:44px;border-radius:50%;background:linear-gradient(135deg,var(--blue-corp),var(--blue-mid));display:flex;align-items:center;justify-content:center;font-weight:700;font-size:.9rem">CL</div>
            <div><div style="font-weight:600;font-size:.9rem">Carlos Lira</div><div style="color:var(--text-muted);font-size:.78rem">CTO &middot; Grupo Comercial Andino</div></div>
            </div>
            <p style="color:var(--text-muted);font-size:.9rem;line-height:1.7;font-style:italic">"La integraci&oacute;n con nuestro ERP fue impecable. En 3 semanas ten&iacute;amos todos los procesos automatizados."</p>
            <div style="display:flex;gap:2px;margin-top:.8rem;color:#f0d080">&#9733;&#9733;&#9733;&#9733;&#9733;</div>
        </div>
        <div class="card reveal" style="border-left:3px solid var(--blue-glow)">
            <div style="display:flex;align-items:center;gap:12px;margin-bottom:1rem">
            <div style="width:44px;height:44px;border-radius:50%;background:linear-gradient(135deg,var(--blue-corp),var(--blue-mid));display:flex;align-items:center;justify-content:center;font-weight:700;font-size:.9rem">AP</div>
            <div><div style="font-weight:600;font-size:.9rem">Ana Paredes</div><div style="color:var(--text-muted);font-size:.78rem">Directora RRHH &middot; BancoPyme</div></div>
            </div>
            <p style="color:var(--text-muted);font-size:.9rem;line-height:1.7;font-style:italic">"El bot de RRHH ahorra 15 horas semanales a nuestro equipo. El onboarding ahora es completamente digital."</p>
            <div style="display:flex;gap:2px;margin-top:.8rem;color:#f0d080">&#9733;&#9733;&#9733;&#9733;&#9733;</div>
        </div>
        </div>
    </div>
    </section>

    <!-- PLANES -->
    <section id="planes" style="padding-top:0">
    <div class="container">
        <div class="section-head center reveal">
        <div class="section-label"><i class="fas fa-tag"></i> Precios</div>
        <h2 class="section-title">Planes para cada empresa</h2>
        <p class="section-sub">Sin sorpresas. Todos los planes incluyen soporte prioritario, actualizaciones autom&aacute;ticas y 30 d&iacute;as de prueba gratuita.</p>
        </div>
        <div class="planes-grid">
        <div class="plan-card reveal">
            <div class="plan-name">Starter</div>
            <div class="plan-price"><sup>S/.</sup>199</div>
            <div class="plan-period">por mes &middot; facturado mensualmente</div>
            <hr class="plan-divider">
            <ul class="plan-features">
            <li><i class="fas fa-check"></i> Hasta 3 bots activos</li>
            <li><i class="fas fa-check"></i> 5 usuarios incluidos</li>
            <li><i class="fas fa-check"></i> 20 integraciones</li>
            <li><i class="fas fa-check"></i> 10 GB almacenamiento</li>
            <li><i class="fas fa-check"></i> Soporte por email</li>
            </ul>
            <a href="#contacto" class="btn-ghost" style="width:100%;justify-content:center;border-radius:12px">Comenzar Gratis</a>
        </div>
        <div class="plan-card featured reveal">
            <div class="plan-popular">MÁS POPULAR</div>
            <div class="plan-name">Professional</div>
            <div class="plan-price"><sup>S/.</sup>599</div>
            <div class="plan-period">por mes &middot; facturado mensualmente</div>
            <hr class="plan-divider">
            <ul class="plan-features">
            <li><i class="fas fa-check"></i> Bots ilimitados</li>
            <li><i class="fas fa-check"></i> 25 usuarios incluidos</li>
            <li><i class="fas fa-check"></i> Todas las integraciones</li>
            <li><i class="fas fa-check"></i> 200 GB almacenamiento</li>
            <li><i class="fas fa-check"></i> Soporte prioritario 24/7</li>
            <li><i class="fas fa-check"></i> Analytics avanzados</li>
            </ul>
            <a href="#contacto" class="btn-primary" style="width:100%;justify-content:center;border-radius:12px"><i class="fas fa-rocket"></i> Empezar Ahora</a>
        </div>
        <div class="plan-card reveal">
            <div class="plan-name">Enterprise</div>
            <div class="plan-price" style="font-size:2.2rem;padding-top:.5rem">A medida</div>
            <div class="plan-period">precio personalizado</div>
            <hr class="plan-divider">
            <ul class="plan-features">
            <li><i class="fas fa-check"></i> Todo lo del plan Pro</li>
            <li><i class="fas fa-check"></i> Usuarios ilimitados</li>
            <li><i class="fas fa-check"></i> Integraciones custom</li>
            <li><i class="fas fa-check"></i> SLA garantizado 99.9%</li>
            <li><i class="fas fa-check"></i> Account Manager dedicado</li>
            <li><i class="fas fa-check"></i> Instalaci&oacute;n on-premise</li>
            </ul>
            <a href="#contacto" class="btn-ghost" style="width:100%;justify-content:center;border-radius:12px">Contactar Ventas</a>
        </div>
        </div>
    </div>
    </section><!-- CTA -->
    <section style="padding-top:0;padding-bottom:4rem">
    <div class="container">
        <div class="cta-wrap reveal">
        <div class="section-label" style="justify-content:center;margin-bottom:1rem"><i class="fas fa-bolt"></i> &iquest;Listo para empezar?</div>
        <h2 class="section-title" style="font-size:clamp(1.8rem,4vw,3rem);margin-bottom:.8rem">Transforma tu empresa hoy</h2>
        <p style="color:var(--text-muted);font-size:1.05rem;max-width:600px;margin:0 auto 2.5rem;line-height:1.8">&Uacute;nete a m&aacute;s de 850 empresas que ya automatizaron sus operaciones con ESTELAR.</p>
        <div style="display:flex;gap:1rem;justify-content:center;flex-wrap:wrap">
            <a href="#contacto" class="btn-primary" style="font-size:.9rem;padding:1rem 2.4rem"><i class="fas fa-rocket"></i> Solicitar Demo</a>
            <a href="#planes" class="btn-ghost" style="font-size:.9rem;padding:1rem 2.4rem">Ver Planes <i class="fas fa-arrow-right"></i></a>
        </div>
        </div>
    </div>
    </section>

    <!-- CONTACTO -->
    <section id="contacto" style="padding-top:0">
    <div class="container">
        <div class="section-head center reveal">
        <div class="section-label"><i class="fas fa-envelope"></i> Contacto</div>
        <h2 class="section-title">Hablemos de tu proyecto</h2>
        <p class="section-sub">Nuestro equipo te responder&aacute; en menos de 2 horas h&aacute;biles con una propuesta personalizada.</p>
        </div>
        <div class="contact-form reveal">
        <div class="form-row">
            <div class="form-group"><label>Nombre</label><input type="text" placeholder="Tu nombre completo"></div>
            <div class="form-group"><label>Empresa</label><input type="text" placeholder="Nombre de tu empresa"></div>
        </div>
        <div class="form-row">
            <div class="form-group"><label>Email</label><input type="email" placeholder="correo@empresa.com"></div>
            <div class="form-group"><label>Tel&eacute;fono</label><input type="tel" placeholder="+51 999 999 999"></div>
        </div>
        <div class="form-group">
            <label>&iquest;Qu&eacute; quieres automatizar?</label>
            <select>
            <option value="">Selecciona un &aacute;rea</option>
            <option>Atenci&oacute;n al Cliente</option>
            <option>Facturaci&oacute;n y Cobranzas</option>
            <option>RRHH y Onboarding</option>
            <option>Inventario y Log&iacute;stica</option>
            <option>Marketing y Email</option>
            <option>An&aacute;lisis de Datos</option>
            <option>Otro</option>
            </select>
        </div>
        <div class="form-group"><label>Mensaje</label><textarea placeholder="Cu&eacute;ntanos sobre tu empresa y sus necesidades..."></textarea></div>
        <button class="btn-primary" style="width:100%;justify-content:center;font-size:.95rem;padding:1rem;border-radius:12px" id="submitBtn">
            <i class="fas fa-paper-plane"></i> Enviar Solicitud
        </button>
        <p id="formMsg" style="display:none;text-align:center;margin-top:1rem;color:var(--blue-glow);font-size:.9rem"></p>
        </div>
    </div>
    </section>
    </div><!-- /.main-container -->

    <!-- FOOTER -->
    <footer>
    <div class="footer-inner">
        <div class="footer-brand">
        <a href="#inicio" class="logo-wrap">
            <div style="height:50px;display:flex;align-items:center;animation:logoGlow 3.5s ease-in-out infinite"><img src="img/logo_final.png" alt="ESTELAR" height="50" style="height:50px;width:auto;display:block;object-fit:contain"></div>
        </a>
        <p>Plataforma de automatizaci&oacute;n empresarial con IA. Transformamos operaciones en ventajas competitivas.</p>
        <div class="social-links" style="margin-top:1.2rem">
            <a href="#"><i class="fab fa-linkedin"></i></a>
            <a href="#"><i class="fab fa-github"></i></a>
            <a href="#"><i class="fab fa-twitter"></i></a>
            <a href="#"><i class="fab fa-whatsapp"></i></a>
        </div>
        </div>
        <div class="footer-col"><h4>Producto</h4><ul>
        <li><a href="#caracteristicas">Caracter&iacute;sticas</a></li>
        <li><a href="#bots">Bots &amp; IA</a></li>
        <li><a href="#planes">Planes</a></li>
        <li><a href="#">API Docs</a></li>
        <li><a href="#">Changelog</a></li>
        </ul></div>
        <div class="footer-col"><h4>Empresa</h4><ul>
        <li><a href="#">Nosotros</a></li>
        <li><a href="#">Blog</a></li>
        <li><a href="#clientes">Casos de &Eacute;xito</a></li>
        <li><a href="#">Partners</a></li>
        <li><a href="#contacto">Contacto</a></li>
        </ul></div>
        <div class="footer-col"><h4>Legal</h4><ul>
        <li><a href="#">T&eacute;rminos de Uso</a></li>
        <li><a href="#">Privacidad</a></li>
        <li><a href="#">Cookies</a></li>
        <li><a href="#">SLA</a></li>
        </ul></div>
    </div>
    <div class="footer-bottom">
        <span>&copy; 2026 ESTELAR Software S.A.C. &mdash; Huancayo, Per&uacute;</span>
        <span>Todos los derechos reservados.</span>
    </div>
    </footer><script>
    /* ---- MOBILE NAV ---- */
    const mobileToggle = document.getElementById('mobileToggle');
    const navLinks = document.getElementById('navLinks');
    mobileToggle.addEventListener('click', () => {
    const open = navLinks.classList.toggle('open');
    mobileToggle.querySelector('i').className = open ? 'fas fa-times' : 'fas fa-bars';
    });
    document.querySelectorAll('.nav-links a').forEach(a => {
    a.addEventListener('click', () => {
        navLinks.classList.remove('open');
        mobileToggle.querySelector('i').className = 'fas fa-bars';
    });
    });

    /* ---- NAV SCROLL ---- */
    window.addEventListener('scroll', () => {
    document.getElementById('navbar').classList.toggle('scrolled', window.scrollY > 40);
    });

    /* ---- SMOOTH SCROLL ---- */
    document.querySelectorAll('a[href^="#"]').forEach(a => {
    a.addEventListener('click', e => {
        const t = document.querySelector(a.getAttribute('href'));
        if (t) { e.preventDefault(); t.scrollIntoView({ behavior: 'smooth' }); }
    });
    });

    /* ---- HERO SLIDES ---- */
    let currentSlide = 0;
    const slides = document.querySelectorAll('.hero-slide');
    const dots = document.querySelectorAll('.hero-dot');
    function goToSlide(n) {
    slides[currentSlide].classList.remove('active');
    dots[currentSlide].classList.remove('active');
    currentSlide = (n + slides.length) % slides.length;
    slides[currentSlide].classList.add('active');
    dots[currentSlide].classList.add('active');
    }
    dots.forEach(d => d.addEventListener('click', () => goToSlide(parseInt(d.dataset.index))));
    setInterval(() => goToSlide(currentSlide + 1), 5000);

    /* ---- MATRIX RAIN + NODE NETWORK ---- */
    (function() {
    const canvas = document.getElementById('particles-canvas');
    const ctx    = canvas.getContext('2d');
    let W, H, drops, speeds, nodes, sizes;
    const FS   = 12;
    const NN   = 58;

    function initNodes() {
        nodes = [];
        for (let i = 0; i < NN; i++) {
        nodes.push({
            x:  Math.random() * W,
            y:  Math.random() * H,
            vx: (Math.random() - 0.5) * 0.28,
            vy: (Math.random() - 0.5) * 0.28,
            r:  Math.random() * 1.4 + 0.6
        });
        }
    }
    function init() {
        W = canvas.width  = window.innerWidth;
        H = canvas.height = window.innerHeight;
        const n = Math.floor(W / FS);
        drops  = Array.from({length: n}, function() { return Math.random() * -(H / FS); });
        speeds = Array.from({length: n}, function() { return 0.04 + Math.random() * 0.18; });
        var SZ = [9,9,10,10,10,11,11,12,12,12,12,13,14,16,20,26];
        sizes  = Array.from({length: n}, function() { return SZ[Math.floor(Math.random() * SZ.length)]; });
        initNodes();
    }
    init();
    window.addEventListener('resize', init);

    function loop() {
        /* fade overlay — crea el rastro de la lluvia */
        ctx.fillStyle = 'rgba(5,8,18,0.05)';
        ctx.fillRect(0, 0, W, H);

        /* ---- lluvia binaria teal — tamaños variados ---- */
        for (let i = 0; i < drops.length; i++) {
        const sz = sizes[i];
        const y  = Math.floor(drops[i]) * FS;
        const x  = i * FS;
        if (y >= -FS && y <= H) {
            const bright = Math.random() > 0.92;
            ctx.font        = sz + 'px "Space Mono", monospace';
            ctx.fillStyle   = bright ? 'rgba(215,255,252,0.97)' : 'rgba(0,215,200,0.82)';
            ctx.shadowBlur  = bright ? (sz > 14 ? 12 : 7) : 2;
            ctx.shadowColor = 'rgba(0,210,195,0.65)';
            ctx.fillText(Math.random() > 0.5 ? '1' : '0', x, y);
            ctx.shadowBlur = 0;
        }
        drops[i] += speeds[i];
        if (y > H && Math.random() > 0.97) {
            var SZ2 = [9,9,10,10,10,11,11,12,12,12,12,13,14,16,20,26];
            drops[i]  = Math.floor(Math.random() * -28);
            speeds[i] = 0.04 + Math.random() * 0.18;
            sizes[i]  = SZ2[Math.floor(Math.random() * SZ2.length)];
        }
        }

        /* ---- actualizar nodos ---- */
        for (let i = 0; i < nodes.length; i++) {
        const nd = nodes[i];
        nd.x += nd.vx;  nd.y += nd.vy;
        if (nd.x < 0 || nd.x > W) nd.vx *= -1;
        if (nd.y < 0 || nd.y > H) nd.vy *= -1;
        }

        /* ---- líneas de conexión ---- */
        ctx.lineWidth = 0.6;
        for (let i = 0; i < nodes.length; i++) {
        for (let j = i + 1; j < nodes.length; j++) {
            const dx   = nodes[i].x - nodes[j].x;
            const dy   = nodes[i].y - nodes[j].y;
            const dist = Math.sqrt(dx * dx + dy * dy);
            if (dist < 175) {
            ctx.strokeStyle = 'rgba(0,210,200,' + (0.2 * (1 - dist / 175)) + ')';
            ctx.beginPath();
            ctx.moveTo(nodes[i].x, nodes[i].y);
            ctx.lineTo(nodes[j].x, nodes[j].y);
            ctx.stroke();
            }
        }
        }

        /* ---- puntos de nodo ---- */
        ctx.shadowBlur  = 6;
        ctx.shadowColor = 'rgba(0,215,200,0.75)';
        for (let i = 0; i < nodes.length; i++) {
        const nd = nodes[i];
        ctx.beginPath();
        ctx.arc(nd.x, nd.y, nd.r, 0, Math.PI * 2);
        ctx.fillStyle = 'rgba(0,228,215,0.78)';
        ctx.fill();
        }
        ctx.shadowBlur = 0;

        requestAnimationFrame(loop);
    }
    loop();
    })();

    /* ---- COUNTERS ---- */
    function animCounter(el, end, suf) {
    const step = end / (1600 / 16);
    let cur = 0;
    const t = setInterval(() => {
        cur = Math.min(cur + step, end);
        el.textContent = Math.floor(cur).toLocaleString() + suf;
        if (cur >= end) clearInterval(t);
    }, 16);
    }
    const seen = new Set();
    const cObs = new IntersectionObserver(entries => {
    entries.forEach(e => {
        if (e.isIntersecting && !seen.has(e.target)) {
        seen.add(e.target);
        animCounter(e.target, parseInt(e.target.dataset.count), e.target.dataset.suffix || '+');
        }
    });
    }, { threshold: 0.4 });
    document.querySelectorAll('[data-count]').forEach(el => cObs.observe(el));

    /* ---- SCROLL REVEAL ---- */
    const rObs = new IntersectionObserver(entries => {
    entries.forEach(e => { if (e.isIntersecting) { e.target.classList.add('visible'); rObs.unobserve(e.target); } });
    }, { threshold: 0.1, rootMargin: '0px 0px -40px 0px' });
    document.querySelectorAll('.reveal').forEach((el, i) => {
    el.style.transitionDelay = (i % 6) * 80 + 'ms';
    rObs.observe(el);
    });

    /* ---- FORM ---- */
    document.getElementById('submitBtn').addEventListener('click', function() {
    const msg = document.getElementById('formMsg');
    this.disabled = true;
    this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Enviando...';
    setTimeout(() => {
        this.innerHTML = '<i class="fas fa-check"></i> Solicitud Enviada';
        this.style.background = 'linear-gradient(135deg,#4ade80,#16a34a)';
        this.style.color = '#060912';
        msg.style.display = 'block';
        msg.textContent = 'Recibimos tu solicitud. Te contactaremos en menos de 2 horas habiles.';
    }, 1200);
    });
    </script>
    </body>
    </html>