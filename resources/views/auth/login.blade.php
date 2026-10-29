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
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            font-family: Inter, system-ui, -apple-system, "Segoe UI", sans-serif;
            color: var(--grafite);
            background: var(--fundo);
        }

        .login {
            min-height: 100vh;
            min-height: 100dvh;
            display: grid;
            grid-template-columns: 1fr 1fr;
        }

        .marca {
            position: relative;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            padding: clamp(32px, 5vw, 80px);
            color: white;
            background:
                radial-gradient(ellipse at 100% 100%, #4063ff40, transparent 65%),
                var(--grafite);
        }

        .logo {
            display: inline-flex;
            align-self: flex-start;
            align-items: center;
            padding: 0;
            background: transparent;
            border: 0;
            border-radius: 0;
            box-shadow: none;
        }

        .logo img {
            display: block;
            width: 220px;
            max-width: 100%;
            height: auto;
            object-fit: contain;

            /* Deixa a logo inteira branca, incluindo o X */
            filter: brightness(0) invert(1);
        }

        .marca-conteudo { margin: 70px 0; }

        .etiqueta {
            color: #acbcff;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: .16em;
            text-transform: uppercase;
        }

        .marca h1 {
            max-width: 560px;
            margin: 20px 0;
            font-size: clamp(36px, 4vw, 62px);
            line-height: 1.08;
            letter-spacing: -.05em;
        }

        .marca h1 span { color: #9aafff; }

        .marca p {
            max-width: 420px;
            color: #b8c0d1;
            line-height: 1.8;
            font-size: 15px;
        }

        .marca-rodape {
            font-size: 12px;
            color: #a4aec0;
        }

        .acesso {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 40px 24px;
        }

        .formulario { width: 100%; max-width: 420px; }

        .selo {
            display: inline-block;
            padding: 8px 12px;
            border: 1px solid #4063ff25;
            border-radius: 30px;
            background: #4063ff0b;
            color: #3152dc;
            font-size: 11px;
            font-weight: 600;
        }

        h2 {
            margin: 24px 0 10px;
            font-size: 34px;
            letter-spacing: -.04em;
        }

        .descricao {
            margin: 0 0 32px;
            color: #657086;
            font-size: 14px;
            line-height: 1.7;
        }

        .campo { margin-bottom: 20px; }

        .campo label {
            display: block;
            margin-bottom: 9px;
            font-size: 13px;
            font-weight: 600;
        }

        .campo input {
            width: 100%;
            min-height: 50px;
            padding: 13px 15px;
            border: 1px solid #dce1eb;
            border-radius: 11px;
            background: white;
            font: inherit;
            font-size: 14px;
            color: var(--grafite);
        }

        input:focus-visible,
        button:focus-visible {
            outline: 3px solid #4063ff55;
            outline-offset: 3px;
        }

        .lembrar {
            display: flex;
            align-items: center;
            gap: 9px;
            margin: 8px 0 24px;
            font-size: 13px;
            color: #526078;
            cursor: pointer;
        }

        .lembrar input {
            width: 17px;
            height: 17px;
            accent-color: var(--azul);
        }

        .entrar {
            width: 100%;
            min-height: 50px;
            border: 0;
            border-radius: 11px;
            background: var(--azul);
            color: white;
            font: inherit;
            font-size: 14px;
            font-weight: 650;
            cursor: pointer;
            box-shadow: 0 8px 20px #4063ff20;
        }

        .entrar:hover { background: #3152e8; }

        .erro {
            padding: 14px;
            margin-bottom: 22px;
            border: 1px solid #fecaca;
            border-radius: 10px;
            background: #fff1f2;
            color: #9f1239;
            font-size: 13px;
            line-height: 1.6;
        }

        .ajuda {
            margin-top: 26px;
            padding-top: 22px;
            border-top: 1px solid #e1e6ee;
            color: #697386;
            font-size: 12px;
            line-height: 1.8;
        }

        @media (max-width: 800px) {
            .login { grid-template-columns: 1fr; }
            .marca { padding: 24px; }
            .marca-conteudo { margin: 28px 0 0; }
            .marca h1 { font-size: 32px; }
            .marca p, .marca-rodape { display: none; }
            .acesso { padding: 36px 24px 48px; }
            .logo img {
                width: 170px;
                height: auto;
            }
        }
    </style>
</head>
<body>
    <main class="login">
        <section class="marca" aria-label="Audix">
            <div class="logo">
                <img src="/images/audix-logo.png" alt="Audix">
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
                        {{ $errors->first() }}
                    </div>
                @endif

                <form method="POST" action="{{ route('login.store') }}">
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

                    <div class="campo">
                        <label for="password">Senha</label>
                        <input
                            id="password"
                            name="password"
                            type="password"
                            placeholder="Digite sua senha"
                            autocomplete="current-password"
                            required
                        >
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
</body>
</html>