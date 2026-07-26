<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'DietaIA' }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Fraunces:opsz,wght@9..144,600;9..144,700&display=swap" rel="stylesheet">
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @endif
    @livewireStyles
    <style>
        :root {
            --bg: #f3f6f2;
            --ink: #1c2b24;
            --muted: #5c6f66;
            --accent: #2f6f4e;
            --accent-2: #e8f3ec;
            --card: #ffffff;
            --line: #d7e0d9;
            --warn: #9a5b00;
        }
        body {
            margin: 0;
            font-family: 'DM Sans', sans-serif;
            color: var(--ink);
            background:
                radial-gradient(1200px 500px at 10% -10%, #d9ebe0 0%, transparent 55%),
                radial-gradient(900px 400px at 100% 0%, #e8f0e4 0%, transparent 50%),
                var(--bg);
            min-height: 100vh;
        }
        h1, h2 {
            font-family: 'Fraunces', Georgia, serif;
        }
        .shell { max-width: 1100px; margin: 0 auto; padding: 1.25rem 1.25rem 6rem; }
        .nav {
            display: flex; flex-wrap: wrap; gap: .85rem 1rem; align-items: center;
            justify-content: space-between; margin-bottom: 1.5rem;
        }
        .brand {
            display: inline-flex;
            align-items: center;
            gap: .7rem;
            text-decoration: none;
            color: var(--ink);
            min-height: 2.6rem;
        }
        .brand-mark {
            width: 2.35rem;
            height: 2.35rem;
            flex-shrink: 0;
            display: block;
        }
        .brand-text-wrap {
            display: inline-flex;
            align-items: center;
            gap: .5rem;
            flex-wrap: wrap;
        }
        .brand-name {
            font-family: 'DM Sans', ui-sans-serif, system-ui, sans-serif;
            font-size: 1.35rem;
            font-weight: 800;
            letter-spacing: -0.02em;
            line-height: 1;
            color: #0f172a;
        }
        .brand-name .brand-ia {
            color: #10b981;
        }
        .brand-badge {
            display: inline-flex;
            align-items: center;
            padding: .22rem .55rem;
            border-radius: 999px;
            background: #e0e7ff;
            color: #3730a3;
            font-family: 'DM Sans', ui-sans-serif, system-ui, sans-serif;
            font-size: .68rem;
            font-weight: 700;
            letter-spacing: .04em;
            text-transform: uppercase;
            line-height: 1;
        }
        .nav-links { display: flex; flex-wrap: wrap; gap: .6rem; align-items: center; }
        .nav-links a, .btn {
            text-decoration: none; color: var(--ink); background: #fff;
            border: 1px solid var(--line); border-radius: 10px; padding: .55rem .9rem;
            font-size: .92rem; font-weight: 500; display: inline-flex; align-items: center;
            cursor: pointer;
        }
        .btn-primary { background: var(--accent); color: #fff; border-color: var(--accent); }
        .btn-ghost { background: transparent; }
        .card {
            background: var(--card); border: 1px solid var(--line); border-radius: 16px;
            padding: 1.1rem 1.2rem; margin-bottom: 1rem;
        }
        .grid { display: grid; gap: 1rem; }
        .grid-2 { grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); }
        .grid-4 { grid-template-columns: repeat(auto-fit, minmax(140px, 1fr)); }
        .muted { color: var(--muted); }
        .stat-value { font-size: 1.6rem; font-weight: 700; }
        label { display: block; font-size: .85rem; margin-bottom: .35rem; color: var(--muted); }
        input, select, textarea {
            width: 100%; box-sizing: border-box; border: 1px solid var(--line);
            border-radius: 10px; padding: .7rem .8rem; font: inherit; background: #fff;
            margin-bottom: .85rem;
        }
        .alert {
            background: #fff7e8; border: 1px solid #f0d7a8; color: var(--warn);
            padding: .8rem 1rem; border-radius: 12px; margin-bottom: 1rem; font-size: .92rem;
        }
        .success {
            background: #e8f6ee; border: 1px solid #b7dfc5; color: #1f5b3a;
            padding: .8rem 1rem; border-radius: 12px; margin-bottom: 1rem;
        }
        .error {
            background: #fdeeee; border: 1px solid #efc0c0; color: #8a2f2f;
            padding: .8rem 1rem; border-radius: 12px; margin-bottom: 1rem;
        }
        .progress {
            height: 10px; background: #e7eee9; border-radius: 999px; overflow: hidden; margin-top: .4rem;
        }
        .progress > span { display: block; height: 100%; background: var(--accent); }
        .meal-row {
            display: flex; justify-content: space-between; gap: 1rem; padding: .7rem 0;
            border-bottom: 1px solid var(--line);
        }
        .meal-row:last-child { border-bottom: 0; }
        .auth-wrap { max-width: 420px; margin: 3rem auto; }
        .disclaimer { font-size: .8rem; color: var(--muted); margin-top: 1.5rem; }
        .fab-ai {
            position: fixed;
            right: 1.4rem;
            bottom: 1.4rem;
            width: 3.5rem;
            height: 3.5rem;
            border-radius: 999px;
            border: none;
            background: var(--accent);
            color: #fff;
            box-shadow: 0 10px 28px rgba(47, 111, 78, .35);
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            z-index: 40;
        }
        .fab-ai:hover { filter: brightness(1.05); }
        .modal-backdrop {
            position: fixed;
            inset: 0;
            background: rgba(28, 43, 36, .45);
            z-index: 50;
        }
        .modal-dialog {
            position: fixed;
            left: 50%;
            top: 50%;
            transform: translate(-50%, -50%);
            width: min(560px, calc(100vw - 2rem));
            max-height: min(86vh, 760px);
            overflow: auto;
            background: #fff;
            border: 1px solid var(--line);
            border-radius: 18px;
            padding: 1.2rem 1.25rem 1.35rem;
            z-index: 60;
            box-shadow: 0 20px 50px rgba(28, 43, 36, .22);
        }
        .modal-dialog-lg { width: min(720px, calc(100vw - 2rem)); }
        .assistant-tabs {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: .5rem;
            margin-bottom: 1rem;
        }
        .assistant-tab {
            border: 1px solid var(--line);
            background: #fff;
            border-radius: 999px;
            padding: .55rem .9rem;
            font: inherit;
            cursor: pointer;
            color: var(--muted);
        }
        .assistant-tab.is-active {
            background: var(--accent);
            border-color: var(--accent);
            color: #fff;
            font-weight: 600;
        }
        .chip-row { display: flex; flex-wrap: wrap; gap: .45rem; margin-bottom: .75rem; }
        .chip {
            border: 1px solid var(--line);
            background: #f7faf8;
            color: var(--ink);
            border-radius: 999px;
            padding: .35rem .7rem;
            font-size: .82rem;
            cursor: pointer;
        }
        .nav-links a.is-active {
            background: var(--accent);
            border-color: var(--accent);
            color: #fff;
            font-weight: 600;
            box-shadow: 0 6px 16px rgba(47, 111, 78, .22);
        }
        .fab-ai-label {
            width: auto;
            min-height: 3.65rem;
            padding: 0 1.25rem 0 1rem;
            gap: .6rem;
            border-radius: 999px;
            font-weight: 700;
            font-size: 1rem;
            letter-spacing: .01em;
            background: linear-gradient(135deg, #2f6f4e, #1f8a5b);
            animation: fabPulse 2.4s ease-in-out infinite;
        }
        .fab-text { white-space: nowrap; }
        .fab-spark { font-size: 1.15rem; line-height: 1; }
        @keyframes fabPulse {
            0%, 100% { box-shadow: 0 10px 28px rgba(47, 111, 78, .35); transform: translateY(0); }
            50% { box-shadow: 0 14px 34px rgba(47, 111, 78, .5); transform: translateY(-2px); }
        }
        .meal-block {
            border-top: 1px solid var(--line);
            padding-top: .85rem;
            margin-top: .85rem;
        }
        .meal-block:first-of-type { border-top: 0; padding-top: 0; margin-top: 0; }
        .meal-block-title {
            font-weight: 700;
            margin-bottom: .45rem;
            color: var(--accent);
            display: flex;
            align-items: center;
            gap: .45rem;
        }
        .meal-icon { font-size: 1.15rem; line-height: 1; }
        .calorie-card {
            background: linear-gradient(160deg, #e8f3ec, #ffffff);
            border-color: #b7dfc5;
        }
        .remaining-value { color: var(--accent); }
        .remaining-label {
            font-weight: 700;
            font-size: .92rem;
            margin: .15rem 0 .35rem;
            color: #1f5b3a;
        }
        .water-card { background: linear-gradient(160deg, #eaf4ff, #fff); border-color: #c5daf0; }
        .water-row {
            display: flex;
            justify-content: space-between;
            gap: 1rem;
            align-items: center;
            flex-wrap: wrap;
        }
        .water-count { font-size: 1.35rem; font-weight: 700; margin-top: .2rem; }
        .water-actions { display: flex; gap: .45rem; }
        .water-btn {
            min-width: 2.6rem;
            justify-content: center;
            font-size: 1.2rem;
            font-weight: 700;
            padding: .45rem .7rem;
        }
        .meal-empty { margin: .2rem 0 .4rem; font-size: .9rem; }
        .badge-row { display: flex; flex-wrap: wrap; gap: .35rem; margin-top: .3rem; }
        .badge {
            display: inline-flex;
            align-items: center;
            border-radius: 999px;
            padding: .18rem .55rem;
            font-size: .75rem;
            font-weight: 600;
        }
        .badge-ai { background: #e7f4ec; color: #1f5b3a; }
        .badge-warn { background: #fff3d9; color: #9a5b00; }
        .micro-card {
            background: #f7faf8;
            border: 1px solid var(--line);
            border-radius: 12px;
            padding: .75rem .8rem;
        }
        .unit { font-size: .85rem; font-weight: 500; color: var(--muted); }
        .grid-3 { grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); }
        .weight-card-current {
            background: linear-gradient(160deg, #e8f3ec, #fff);
            border-color: #b7dfc5;
        }
        .progress-lg { height: 14px; }
        .weight-chart-wrap {
            display: grid;
            grid-template-columns: 1fr auto;
            gap: .75rem;
            align-items: stretch;
            min-height: 180px;
        }
        .weight-chart {
            width: 100%;
            height: 180px;
            background: linear-gradient(180deg, #f7faf8, #fff);
            border: 1px solid var(--line);
            border-radius: 12px;
        }
        .chart-scale {
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            font-size: .8rem;
        }
        .chart-labels {
            display: flex;
            justify-content: space-between;
            gap: .35rem;
            font-size: .75rem;
            margin-top: .5rem;
            overflow: hidden;
        }
        .tip-card {
            border: 1px solid var(--line);
            border-radius: 12px;
            padding: .9rem 1rem;
            background: #fff;
        }
        .tip-card p { margin: .35rem 0 0; color: var(--muted); }
        .tip-motivational { border-left: 4px solid #2f6f4e; }
        .tip-practical { border-left: 4px solid #3d6f9a; }
        .tip-caution { border-left: 4px solid #9a5b00; }
        .streak-banner {
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
            gap: 1rem;
            align-items: center;
            background: #e8f3ec;
            border-radius: 14px;
            padding: 1rem 1.1rem;
        }
        .milestone {
            display: flex;
            gap: .75rem;
            align-items: flex-start;
            border: 1px solid var(--line);
            border-radius: 12px;
            padding: .85rem 1rem;
            background: #fff;
        }
        .milestone.is-done {
            background: #eef8f1;
            border-color: #b7dfc5;
        }
        .milestone-check {
            width: 1.6rem;
            height: 1.6rem;
            border-radius: 999px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: #fff;
            border: 1px solid var(--line);
            font-weight: 700;
            color: var(--accent);
        }
        @media (max-width: 640px) {
            .fab-ai-label { right: 1rem; bottom: 1rem; font-size: .85rem; padding: 0 .9rem; }
            .fab-text { max-width: 11rem; white-space: normal; line-height: 1.15; text-align: left; }
            .brand-name { font-size: 1.2rem; }
            .brand-mark { width: 2.1rem; height: 2.1rem; }
        }
    </style>
</head>
<body>
    <div class="shell">
        @if(auth()->check())
            <nav class="nav">
                <a class="brand" href="{{ route('dashboard') }}" aria-label="DietaIA by Ancame — Ir al inicio">
                    <svg class="brand-mark" viewBox="0 0 48 48" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                        <rect width="48" height="48" rx="14" fill="#ecfdf5"/>
                        <path d="M24 8c-7.4 8.1-11.2 14.8-11.2 21.2 0 6.2 4.9 11.1 11.2 11.1s11.2-5 11.2-11.1C35.2 22.8 31.4 16.1 24 8z" fill="#10b981"/>
                        <path d="M24 12.2c1.8 4.2 4.7 8.4 4.7 12.8 0 2.7-2.1 4.8-4.7 4.8" stroke="#065f46" stroke-width="2" stroke-linecap="round" opacity=".55"/>
                        <path d="M33.5 11.5l1.1 2.6 2.6 1.1-2.6 1.1-1.1 2.6-1.1-2.6-2.6-1.1 2.6-1.1 1.1-2.6z" fill="#34d399"/>
                        <path d="M37.8 18.2l.7 1.6 1.6.7-1.6.7-.7 1.6-.7-1.6-1.6-.7 1.6-.7.7-1.6z" fill="#6ee7b7"/>
                        <path d="M30.8 7.8l.55 1.25 1.25.55-1.25.55-.55 1.25-.55-1.25-1.25-.55 1.25-.55.55-1.25z" fill="#a7f3d0"/>
                    </svg>
                    <span class="brand-text-wrap">
                        <span class="brand-name">Dieta<span class="brand-ia">IA</span></span>
                        <span class="brand-badge">by Ancame</span>
                    </span>
                </a>
                <div class="nav-links">
                    <a href="{{ route('dashboard') }}" class="{{ request()->routeIs('dashboard') ? 'is-active' : '' }}">Hoy</a>
                    <a href="{{ route('progress') }}" class="{{ request()->routeIs('progress') ? 'is-active' : '' }}">Progreso</a>
                    <a href="{{ route('meals.create') }}" class="{{ request()->routeIs('meals.create') ? 'is-active' : '' }}">Comida</a>
                    <a href="{{ route('diets') }}" class="{{ request()->routeIs('diets') ? 'is-active' : '' }}">Dietas</a>
                    <a href="{{ route('menus') }}" class="{{ request()->routeIs('menus') ? 'is-active' : '' }}">Menús</a>
                    <a href="{{ route('tips') }}" class="{{ request()->routeIs('tips') ? 'is-active' : '' }}">Consejos</a>
                    <a href="{{ route('profile') }}" class="{{ request()->routeIs('profile') ? 'is-active' : '' }}">Perfil</a>
                    <form method="POST" action="{{ route('logout') }}" style="display:inline">
                        @csrf
                        <button class="btn btn-ghost" type="submit">Salir</button>
                    </form>
                </div>
            </nav>
        @endif

        @if (session('status'))
            <div class="success">{{ session('status') }}</div>
        @endif
        @if (session('error'))
            <div class="error">{{ session('error') }}</div>
        @endif

        {{ $slot }}

        <p class="disclaimer">DietaIA ofrece orientación general con IA y no sustituye consejo médico ni nutricional profesional.</p>
    </div>
    @livewireScripts
</body>
</html>
