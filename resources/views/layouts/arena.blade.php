<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'QUIZ ARENA')</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            min-height: 100vh;
            background-color: #05060f;
            background-image:
                radial-gradient(circle at 15% 15%, rgba(139, 92, 246, 0.20) 0%, rgba(139, 92, 246, 0) 45%),
                radial-gradient(circle at 85% 85%, rgba(34, 211, 238, 0.14) 0%, rgba(34, 211, 238, 0) 45%);
            background-attachment: fixed;
            color: #f8fafc;
            font-family: system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
        }

        .qa-wrap { width: 100%; max-width: 880px; text-align: center; position: relative; z-index: 1; }
        .qa-wrap-wide { max-width: 1180px; text-align: left; }
        .qa-dashboard-layout { display: grid; grid-template-columns: 250px minmax(0, 1fr); gap: 24px; align-items: start; }
        .qa-dashboard-sidebar, .qa-dashboard-main { background: rgba(13,16,38,0.85); border: 1px solid rgba(139,92,246,0.25); border-radius: 16px; padding: 24px; }
        .qa-dashboard-nav { display: flex; flex-direction: column; gap: 8px; margin-top: 24px; }
        .qa-dashboard-nav a { display: flex; justify-content: space-between; gap: 12px; align-items: center; }
        .qa-dashboard-nav a[aria-current="page"] { background: rgba(139,92,246,0.18); color: #fff; }
        .qa-dashboard-top { display: flex; justify-content: space-between; gap: 20px; align-items: center; flex-wrap: wrap; }
        .qa-dashboard-stats { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 16px; margin-top: 28px; }
        .qa-dashboard-stat { background: rgba(5,6,15,0.55); border: 1px solid rgba(139,92,246,0.2); border-radius: 14px; padding: 20px; }
        .qa-dashboard-stat strong { display: block; margin-top: 8px; font-size: 30px; color: #fff; }
        .qa-dashboard-meta { color: #94a3b8; font-size: 13px; }
        .qa-dashboard-logout { border: 0; cursor: pointer; }
        .qa-dashboard-home { margin-top: 28px; }
        .qa-dashboard-home h2 { font-size: 18px; margin-bottom: 8px; }
        .qa-dashboard-home p { color: #94a3b8; line-height: 1.6; }
        .qa-status { border-radius: 999px; padding: 5px 10px; font-size: 12px; font-weight: 700; }
        .qa-status-draft { color: #cbd5e1; background: rgba(148,163,184,0.12); border: 1px solid rgba(148,163,184,0.3); }
        .qa-status-active { color: #86efac; background: rgba(34,197,94,0.12); border: 1px solid rgba(34,197,94,0.35); }
        .qa-quiz-actions { display: flex; gap: 8px; align-items: center; flex-wrap: wrap; }
        .qa-quiz-actions form { margin: 0; }
        .qa-quiz-actions .qa-btn { padding: 10px 16px; font-size: 13px; }
        .qa-btn-danger { border: 1px solid rgba(248,113,113,0.5); color: #fca5a5; background: rgba(239,68,68,0.1); cursor: pointer; }
        .qa-btn-danger:hover { box-shadow: 0 0 20px rgba(239,68,68,0.2); }
        .qa-question-heading { display: flex; justify-content: space-between; align-items: flex-start; gap: 18px; flex-wrap: wrap; }
        .qa-question-actions { display: flex; align-items: center; flex-wrap: wrap; gap: 8px; }
        .qa-question-actions form { margin: 0; }
        .qa-question-actions .qa-btn { padding: 9px 13px; font-size: 13px; }
        .qa-question-actions button:disabled { opacity: 0.35; cursor: not-allowed; }
        .qa-option-list { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 10px; list-style: none; margin-top: 20px; }
        .qa-option-list li { display: flex; align-items: center; gap: 10px; padding: 12px; border-radius: 10px; background: rgba(5,6,15,0.55); border: 1px solid rgba(148,163,184,0.15); color: #cbd5e1; }
        .qa-option-list li.qa-option-correct { border-color: rgba(34,197,94,0.45); background: rgba(34,197,94,0.08); color: #f8fafc; }
        .qa-option-label, .qa-option-editor-label { display: inline-grid; place-items: center; width: 30px; height: 30px; flex-shrink: 0; border-radius: 8px; background: rgba(139,92,246,0.18); color: #c4b5fd; font-weight: 800; }
        .qa-correct-indicator { margin-left: auto; color: #86efac; font-size: 12px; font-weight: 700; }
        .qa-field { display: grid; gap: 8px; }
        .qa-field label, .qa-options-fieldset legend { color: #cbd5e1; font-size: 14px; font-weight: 700; }
        .qa-field input, .qa-field textarea, .qa-option-editor-row > input { width: 100%; padding: 12px; border-radius: 10px; border: 1px solid rgba(139,92,246,0.35); background: rgba(5,6,15,0.7); color: #f8fafc; font: inherit; }
        .qa-field textarea { resize: vertical; }
        .qa-field input:focus, .qa-field textarea:focus, .qa-option-editor-row > input:focus { outline: 2px solid rgba(34,211,238,0.5); outline-offset: 1px; }
        .qa-question-settings { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 16px; }
        .qa-options-fieldset { display: grid; gap: 12px; border: 1px solid rgba(139,92,246,0.25); border-radius: 14px; padding: 18px; }
        .qa-option-editor { display: grid; gap: 12px; margin-top: 8px; }
        .qa-option-editor-row { display: grid; grid-template-columns: 34px minmax(0, 1fr) auto; gap: 10px; align-items: center; }
        .qa-correct-choice { display: flex; align-items: center; gap: 6px; color: #86efac; font-size: 12px; font-weight: 700; cursor: pointer; }
        .qa-correct-choice input { accent-color: #22c55e; width: 16px; height: 16px; }

        .qa-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 56px;
            gap: 16px;
            flex-wrap: wrap;
        }

        .qa-brand {
            font-size: 22px;
            font-weight: 900;
            letter-spacing: 3px;
            background: linear-gradient(90deg, #a78bfa 0%, #22d3ee 100%);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
        }

        .qa-nav { display: flex; gap: 12px; align-items: center; flex-wrap: wrap; }

        .qa-btn {
            display: inline-block;
            padding: 14px 28px;
            border-radius: 999px;
            font-weight: 700;
            font-size: 15px;
            text-decoration: none;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .qa-btn:hover { transform: translateY(-2px); }

        .qa-btn-primary {
            background: linear-gradient(90deg, #7c3aed 0%, #22d3ee 100%);
            color: #ffffff;
            box-shadow: 0 0 28px rgba(124, 58, 237, 0.45);
        }

        .qa-btn-secondary {
            border: 1px solid rgba(34, 211, 238, 0.6);
            color: #22d3ee;
            background: rgba(34, 211, 238, 0.06);
        }

        .qa-badge {
            display: inline-block;
            padding: 8px 18px;
            border-radius: 999px;
            border: 1px solid rgba(139, 92, 246, 0.55);
            background: rgba(139, 92, 246, 0.12);
            color: #c4b5fd;
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 2px;
            text-transform: uppercase;
            margin-bottom: 28px;
        }

        .qa-title {
            font-size: clamp(52px, 11vw, 104px);
            font-weight: 900;
            letter-spacing: 8px;
            line-height: 1;
            background: linear-gradient(90deg, #a78bfa 0%, #22d3ee 100%);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
            filter: drop-shadow(0 0 30px rgba(139, 92, 246, 0.35));
        }

        .qa-subtitle { margin-top: 20px; font-size: clamp(18px, 3vw, 26px); font-weight: 700; color: #e2e8f0; }
        .qa-desc { margin: 18px auto 0; max-width: 560px; color: #94a3b8; font-size: 16px; line-height: 1.7; }

        .qa-actions { margin-top: 40px; display: flex; gap: 16px; justify-content: center; flex-wrap: wrap; }

        .qa-footer { margin-top: 64px; color: #64748b; font-size: 14px; }

        /* Tarjetas */
        .qa-card {
            background: rgba(13, 16, 38, 0.85);
            border: 1px solid rgba(139, 92, 246, 0.25);
            border-radius: 16px;
            padding: 24px;
        }

        /* Alertas */
        .qa-alert {
            border-radius: 12px;
            padding: 14px 18px;
            margin-bottom: 20px;
            font-size: 14px;
            text-align: center;
        }

        .qa-alert-ok {
            background: rgba(34, 197, 94, 0.12);
            border: 1px solid rgba(34, 197, 94, 0.45);
            color: #86efac;
        }

        .qa-alert-error {
            background: rgba(239, 68, 68, 0.12);
            border: 1px solid rgba(239, 68, 68, 0.45);
            color: #fca5a5;
        }

        @keyframes qaFadeIn {
            from { opacity: 0; transform: translateY(16px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .qa-wrap > * {
            animation: qaFadeIn 0.7s ease both;
        }

        @keyframes qaGlow {
            0%, 100% { box-shadow: 0 0 22px rgba(124, 58, 237, 0.45); }
            50% { box-shadow: 0 0 38px rgba(34, 211, 238, 0.55); }
        }

        .qa-btn-primary {
            animation: qaGlow 2.8s ease-in-out infinite;
        }

        /* Menú lateral */
        .qa-menu-link {
            display: block;
            padding: 10px 12px;
            border-radius: 8px;
            color: #cbd5e1;
            text-decoration: none;
            transition: background 0.2s ease, color 0.2s ease;
        }

        .qa-menu-link:hover {
            background: rgba(139, 92, 246, 0.15);
            color: #ffffff;
        }

        @media (max-width: 640px) {
            body { padding: 16px; }
            .qa-header { justify-content: center; margin-bottom: 40px; }
            .qa-actions { flex-direction: column; align-items: stretch; }
            .qa-btn { text-align: center; }
            .qa-dashboard-layout { grid-template-columns: 1fr; }
            .qa-dashboard-stats { grid-template-columns: 1fr; }
            .qa-dashboard-sidebar, .qa-dashboard-main { padding: 18px; }
            .qa-option-list, .qa-question-settings { grid-template-columns: 1fr; }
            .qa-option-editor-row { grid-template-columns: 30px minmax(0, 1fr); }
            .qa-correct-choice { grid-column: 2; }
        }
    </style>
</head>
<body>
    <div class="qa-wrap @yield('layout-class')">
        @include('partials.messages')
        @yield('content')
    </div>
</body>
</html>
