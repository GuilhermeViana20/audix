<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>Entrar · Audix</title>
    <link rel="icon" href="/images/audix-favicon.png">

    <style>
        :root {
            color-scheme: light;
            --azul: #4063FF;
            --grafite: #151922;
            --fundo: #F5F7FA;
            --borda: #dfe4ee;
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            font-family: Inter, system-ui, -apple-system, "Segoe UI", sans-serif;
            color: var(--grafite);
            background: var(--fundo);
            -webkit-font-smoothing: antialiased;
        }

        .login {
            min-height: 100vh;
            min-height: 100dvh;
            display: grid;
            grid-template-columns: 1.05fr 1fr;
        }

        /* ---------- Painel da marca ---------- */
        .marca {
            position: relative;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            padding: clamp(32px, 5vw, 80px);
            color: #fff;
            background: var(--grafite);
            isolation: isolate;
        }

        .marca::before,
        .marca::after {
            content: '';
            position: absolute;
            z-index: -1;
            width: 520px;
            height: 520px;
            border-radius: 50%;
            filter: blur(70px);
            opacity: .55;
            animation: flutuar 14s ease-in-out infinite;
        }
        .marca::before {
            right: -160px;
            bottom: -180px;
            background: #4063ff;
        }
        .marca::after {
            left: -200px;
            top: -220px;
            background: #7a5cff;
            opacity: .35;
            animation-delay: -6s;
        }

        .logo img {
            display: block;
            width: 220px;
            max-width: 100%;
            height: auto;
            animation: entrar 1.1s cubic-bezier(.2, .8, .2, 1) both;
        }

        .marca-conteudo { margin: 70px 0; }

        .etiqueta {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: #acbcff;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: .16em;
            text-transform: uppercase;
            animation: entrar .9s .2s cubic-bezier(.2, .8, .2, 1) both;
        }
        .etiqueta::before {
            content: '';
            width: 22px;
            height: 2px;
            border-radius: 2px;
            background: #acbcff;
        }

        .marca h1 {
            max-width: 560px;
            margin: 20px 0;
            font-size: clamp(36px, 4vw, 62px);
            line-height: 1.08;
            letter-spacing: -.05em;
            animation: entrar .9s .3s cubic-bezier(.2, .8, .2, 1) both;
        }

        .marca h1 span {
            background: linear-gradient(90deg, #9aafff, #ffffff 60%, #9aafff);
            background-size: 200% auto;
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
            animation: brilho 6s linear infinite;
        }

        .marca p {
            max-width: 420px;
            color: #b8c0d1;
            line-height: 1.8;
            font-size: 15px;
            animation: entrar .9s .4s cubic-bezier(.2, .8, .2, 1) both;
        }

        .marca-rodape {
            font-size: 12px;
            color: #a4aec0;
            animation: entrar .9s .5s both;
        }

        /* ---------- Formulário ---------- */
        .acesso {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 40px 24px;
        }

        .formulario {
            width: 100%;
            max-width: 420px;
            animation: entrar .9s .15s cubic-bezier(.2, .8, .2, 1) both;
        }

        .selo {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 7px 12px;
            border: 1px solid #4063ff25;
            border-radius: 30px;
            background: #4063ff0b;
            color: #3152dc;
            font-size: 11px;
            font-weight: 600;
        }
        .selo::before {
            content: '';
            width: 7px;
            height: 7px;
            border-radius: 50%;
            background: #22c55e;
            box-shadow: 0 0 0 0 #22c55e80;
            animation: pulsar 2s infinite;
        }

        h2 {
            margin: 22px 0 10px;
            font-size: 34px;
            letter-spacing: -.04em;
        }

        .descricao {
            margin: 0 0 30px;
            color: #657086;
            font-size: 14px;
            line-height: 1.7;
        }

        .campo { margin-bottom: 18px; }

        .campo label {
            display: block;
            margin-bottom: 8px;
            font-size: 13px;
            font-weight: 600;
        }

        .campo-entrada { position: relative; }

        .campo input {
            width: 100%;
            min-height: 52px;
            padding: 13px 16px;
            border: 1px solid var(--borda);
            border-radius: 12px;
            background: #fff;
            font: inherit;
            font-size: 14px;
            color: var(--grafite);
            transition: border-color .2s ease, box-shadow .2s ease;
        }

        .campo input::placeholder { color: #a3acbd; }

        .campo input:hover { border-color: #c5cddd; }

        .campo input:focus {
            outline: none;
            border-color: var(--azul);
            box-shadow: 0 0 0 4px rgb(64 99 255 / .14);
        }

        .campo.com-botao input { padding-right: 52px; }

        .mostrar {
            position: absolute;
            right: 8px;
            top: 50%;
            transform: translateY(-50%);
            width: 36px;
            height: 36px;
            border: 0;
            border-radius: 9px;
            background: transparent;
            color: #7a8499;
            cursor: pointer;
            display: grid;
            place-items: center;
            transition: background-color .2s ease, color .2s ease;
        }
        .mostrar:hover { background: #eef2fb; color: var(--azul); }
        .mostrar svg { width: 18px; height: 18px; }

        .lembrar {
            display: flex;
            align-items: center;
            gap: 10px;
            margin: 6px 0 24px;
            font-size: 13px;
            color: #526078;
            cursor: pointer;
            user-select: none;
        }
        .lembrar input {
            width: 17px;
            height: 17px;
            margin: 0;
            accent-color: var(--azul);
            cursor: pointer;
        }

        .entrar {
            position: relative;
            width: 100%;
            min-height: 52px;
            border: 0;
            border-radius: 12px;
            background: linear-gradient(135deg, #4a6fff, #3152e8);
            color: #fff;
            font: inherit;
            font-size: 14px;
            font-weight: 650;
            letter-spacing: .01em;
            cursor: pointer;
            overflow: hidden;
            box-shadow: 0 10px 24px -8px #4063ff80;
            transition: transform .15s ease, box-shadow .25s ease, filter .25s ease;
        }
        .entrar::after {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(105deg, transparent 35%, rgb(255 255 255 / .3) 50%, transparent 65%);
            transform: translateX(-120%);
            transition: transform .7s ease;
        }
        .entrar:hover { box-shadow: 0 14px 30px -8px #4063ff99; filter: brightness(1.05); }
        .entrar:hover::after { transform: translateX(120%); }
        .entrar:active { transform: scale(.98); }
        .entrar:disabled { opacity: .7; cursor: wait; }

        .erro {
            display: flex;
            gap: 10px;
            padding: 14px;
            margin-bottom: 22px;
            border: 1px solid #fecaca;
            border-radius: 12px;
            background: #fff1f2;
            color: #9f1239;
            font-size: 13px;
            line-height: 1.6;
            animation: tremer .45s ease;
        }

        .ajuda {
            margin-top: 28px;
            padding-top: 22px;
            border-top: 1px solid #e1e6ee;
            color: #697386;
            font-size: 12px;
            line-height: 1.8;
        }

        :focus-visible {
            outline: 3px solid #4063ff55;
            outline-offset: 3px;
        }

        /* ---------- Animações ---------- */
        @keyframes entrar {
            from { opacity: 0; transform: translateY(16px); filter: blur(4px); }
            to { opacity: 1; transform: none; filter: blur(0); }
        }
        @keyframes flutuar {
            0%, 100% { transform: translate(0, 0) scale(1); }
            50% { transform: translate(40px, -30px) scale(1.08); }
        }
        @keyframes brilho {
            to { background-position: 200% center; }
        }
        @keyframes pulsar {
            0% { box-shadow: 0 0 0 0 #22c55e80; }
            70% { box-shadow: 0 0 0 7px #22c55e00; }
            100% { box-shadow: 0 0 0 0 #22c55e00; }
        }
        @keyframes tremer {
            20%, 60% { transform: translateX(-4px); }
            40%, 80% { transform: translateX(4px); }
        }

        @media (max-width: 800px) {
            .login { grid-template-columns: 1fr; }
            .marca { padding: 24px; }
            .marca-conteudo { margin: 28px 0 0; }
            .marca h1 { font-size: 32px; }
            .marca p, .marca-rodape { display: none; }
            .acesso { padding: 36px 24px 48px; }
            .logo img { width: 170px; }
            h2 { font-size: 28px; }
        }

        @media (prefers-reduced-motion: reduce) {
            *, *::before, *::after {
                animation: none !important;
                transition: none !important;
            }
        }
    </style>
</head>
<body>
    <main class="login">
        <section class="marca" aria-label="Audix">
            <div class="logo">
                <img src="/images/audix-logo-branco.png" alt="Audix">
            </div>
            <div class="marca-conteudo">
                <span class="etiqueta">Inteligência em cada controle</span>
                <h1>
                    Sua rotina,<br>
                    <span>sob controle.</span>
                </h1>
                <p>
                    Temperatura, higiene e controle de pragas.
                    Mais organização para cuidar da qualidade
                    em cada supermercado.
                </p>
            </div>
            <div class="marca-rodape">
                © {{ date('Y') }} Audix
            </div>
        </section>

        <section class="acesso" aria-labelledby="titulo-login">
            <div class="formulario">
                <span class="selo">Acesso exclusivo para contas autorizadas</span>
                <h2 id="titulo-login">Bem-vindo de volta.</h2>
                <p class="descricao">
                    Entre com suas credenciais para acessar o Audix.
                </p>

                @if ($errors->any())
                    <div class="erro" role="alert">
                        <span aria-hidden="true">!</span>
                        <span>{{ $errors->first() }}</span>
                    </div>
                @endif

                <form method="POST" action="{{ route('login.store') }}" id="form-login">
                    @csrf
                    <div class="campo">
                        <label for="email">E-mail</label>
                        <input
                            id="email"
                            name="email"
                            type="email"
                            value="{{ old('email') }}"
                            placeholder="seu@email.com"
                            autocomplete="username"
                            maxlength="255"
                            required
                            autofocus
                        >
                    </div>

                    <div class="campo com-botao">
                        <label for="password">Senha</label>
                        <div class="campo-entrada">
                            <input
                                id="password"
                                name="password"
                                type="password"
                                placeholder="Digite sua senha"
                                autocomplete="current-password"
                                required
                            >
                            <button type="button" class="mostrar" id="mostrar-senha" aria-label="Mostrar senha" aria-pressed="false">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7S1 12 1 12z"/>
                                    <circle cx="12" cy="12" r="3"/>
                                </svg>
                            </button>
                        </div>
                    </div>

                    <label class="lembrar">
                        <input
                            type="checkbox"
                            name="remember"
                            value="1"
                            @checked(old('remember'))
                        >
                        Manter conectado neste dispositivo
                    </label>

                    <button class="entrar" type="submit">
                        Entrar no Audix →
                    </button>
                </form>

                <p class="ajuda">
                    Precisa de acesso ou esqueceu sua senha?
                    Entre em contato com o responsável pelo sistema.
                </p>
            </div>
        </section>
    </main>

    <script>
        // Mostrar/ocultar a senha e feedback de envio no botão
        (function () {
            var senha = document.getElementById('password');
            var botaoMostrar = document.getElementById('mostrar-senha');
            botaoMostrar.addEventListener('click', function () {
                var mostrar = senha.type === 'password';
                senha.type = mostrar ? 'text' : 'password';
                botaoMostrar.setAttribute('aria-pressed', mostrar ? 'true' : 'false');
                botaoMostrar.setAttribute('aria-label', mostrar ? 'Ocultar senha' : 'Mostrar senha');
            });

            var formulario = document.getElementById('form-login');
            formulario.addEventListener('submit', function () {
                var botao = formulario.querySelector('.entrar');
                botao.disabled = true;
                botao.textContent = 'Entrando…';
            });
        })();
    </script>
</body>
</html>
