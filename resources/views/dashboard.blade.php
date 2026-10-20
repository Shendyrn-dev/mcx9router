<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#091019">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="icon" type="image/svg+xml" href="{{ asset('mcx9route-logo.svg') }}">
    <link rel="apple-touch-icon" href="{{ asset('mcx9route-logo.svg') }}">
    <title>mcx9route Terminal</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@200..800&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        * { font-variant-numeric: tabular-nums; }
        body { font-family: 'Manrope', sans-serif; overflow-x: hidden; scrollbar-color: #333 #000; scrollbar-width: thin; background:#000; }
        * { scrollbar-width: thin; scrollbar-color: #333 transparent; }
        *::-webkit-scrollbar { height: 8px; width: 8px; }
        *::-webkit-scrollbar-track { background: transparent; }
        *::-webkit-scrollbar-thumb { background: linear-gradient(180deg, #555, #333); border: 2px solid transparent; border-radius: 999px; background-clip: padding-box; }
        *::-webkit-scrollbar-thumb:hover { background: linear-gradient(180deg, #666, #444); border-width: 1px; background-clip: padding-box; }
        *::-webkit-scrollbar-corner { background: transparent; }
        .mono { font-family: 'DM Mono', ui-monospace, monospace; }
        .terminal-card { background:#000000; border:2px solid #333333; border-radius:16px; min-width:0; box-shadow:0 8px 24px rgba(0,0,0,.5); }
         body { background:#000; background-image:none; }
         .dashboard-header { position:sticky; top:0; z-index:20; min-height:40px; background:#0a0a0a; border-bottom:1px solid rgba(148,163,184,.12); transition:box-shadow .2s; }
         .page-kicker { color:#0057FF; font:10px 'DM Mono',monospace; letter-spacing:.18em; }
         .header-actions { display:flex; align-items:center; }
         .status-pill { display:inline-flex; align-items:center; color:#86efac; font:10px 'DM Mono',monospace; text-transform:uppercase; letter-spacing:.08em; }
         .period-buttons { display:flex; gap:8px; padding:6px; background:rgba(30,30,30,.8); border-radius:12px; }
         .period-button { padding:8px 16px; border:0; border-radius:8px; background:transparent; color:#8190a5; font:600 13px system-ui,sans-serif; cursor:pointer; transition:.2s; }
         .period-button:hover { background:rgba(128,128,128,.15); color:#0057FF; }
         .period-button.active { background:rgba(56,189,248,.18); color:#0057FF; }
        .panel-title { color: #dce6f3; font-size: 12px; font-weight: 700; letter-spacing: .04em; }
        .metric { border:0; padding:10px 12px; min-height:68px; display:flex; flex-direction:column; justify-content:center; border-radius:10px; background:rgba(255,255,255,.018); transition:transform .2s,background .2s,box-shadow .2s; }
         .metric:hover { transform:translateY(-2px); background:rgba(255,255,255,.045); box-shadow:0 8px 20px rgba(0,0,0,.22); }
        .metric-value { color: #e8f0f8; font-family: 'DM Mono', ui-monospace, monospace; font-size: 24px; font-weight: 500; letter-spacing: -.05em; }
        .metric-label { color: #8190a5; font-size: 0.75rem; font-weight: 700; letter-spacing: .09em; text-transform: uppercase; margin-bottom: 6px; }
        .cost { color: #eab308; }
        @media (min-width: 1280px) {
            #overview.grid-cols-2 .metric-value, #overview .metric-value { font-size: 28px; }
            #overview.grid-cols-2 .metric-label, #overview .metric-label { font-size: 0.875rem; }
        }
        @media (max-width: 640px) { #overview.grid-cols-2 .metric-value { font-size: 20px; } }
        #overview.grid { gap: 1rem; }
        .metric--violet { border-color: #0057FF; }.metric--red { border-color: #f87171; }.metric--green { border-color: #4ade80; }.metric--yellow { border-color: #fbbf24; }.metric--blue { border-color: #0057FF; }
        #overview.grid-cols-2 > .terminal-card { min-height: 138px; padding: 26px !important; }
        #overview.grid-cols-2 .metric { padding: 12px 14px; min-height: 78px; }
        #overview.grid-cols-2 .metric-value { font-size: 36px; line-height: 1.2; }
        #overview.grid-cols-2 .metric-label { font-size: 14px; margin-bottom: 10px; }
        @media (max-width: 640px) { #overview.grid-cols-2 > .terminal-card { min-height: 108px; padding: 18px !important; } #overview.grid-cols-2 .metric-value { font-size: 20px; } }
        .chart-container { height: 310px; position: relative; }
        .ai-activity { position:relative; overflow:hidden; min-height:170px; padding:24px 26px; background-color:#000000; border:2px solid #333333; border-radius:16px; }
        .ai-activity::before { content:''; position:absolute; left:12%; right:12%; top:50%; height:1px; background:linear-gradient(90deg,transparent,rgba(139,92,246,.35),transparent); }
        .ai-activity-head { position:relative; z-index:1; display:flex; align-items:center; justify-content:space-between; gap:12px; }
        .ai-model-list { position:relative; z-index:1; display:flex; flex-wrap:wrap; align-items:flex-start; justify-content:center; gap:18px; min-height:125px; padding-top:20px; }
        .ai-model-chip { display:flex; flex-direction:column; align-items:center; gap:8px; min-width:92px; padding:4px 8px 8px; border:0; border-radius:0; background:transparent; box-shadow:none; animation:nodeFloat 3.8s ease-in-out infinite; }
        .ai-model-chip .ai-model-name { max-width:110px; text-align:center; } .ai-model-chip:nth-child(2n) { animation-delay:-1.25s; } .ai-model-chip:nth-child(3n) { animation-delay:-2.2s; }
        .ai-model-dot { position:relative; display:block; width:44px; height:55px; flex:none; border:4px solid #111827; border-radius:18px 18px 11px 11px; background:var(--crew-color); box-shadow:5px 5px 0 #111827; image-rendering:pixelated; } .ai-model-dot::before { content:''; position:absolute; top:9px; right:-8px; width:25px; height:15px; border:4px solid #111827; border-radius:14px 7px 7px 14px; background:#bae6fd; } .ai-model-dot::after { content:''; position:absolute; bottom:-5px; left:6px; width:9px; height:9px; background:#111827; box-shadow:21px 0 #111827; }
        .ai-model-name { overflow:hidden; color:#dce6f3; font-size:12px; font-weight:700; text-overflow:ellipsis; white-space:nowrap; text-shadow:0 0 10px var(--crew-color); }
        .ai-model-count { color:#8190a5; font:10px 'DM Mono',monospace; white-space:nowrap; }
        .ai-pulse { width:7px; height:7px; border-radius:50%; background:#4ade80; box-shadow:0 0 0 0 rgba(74,222,128,.6); animation:activityPulse 1.8s infinite; }
        @keyframes nodeFloat { 50% { transform:translateY(-4px); border-color:rgba(167,139,250,.65); } }
        @keyframes activityPulse { 70% { box-shadow:0 0 0 7px rgba(74,222,128,0); } 100% { box-shadow:0 0 0 0 rgba(74,222,128,0); } }
        .chart-container-small { height: 260px; position: relative; }
        .side-link { align-items: center; border-radius: 6px; color: #8190a5; display: flex; font-size: 13px; font-weight: 600; gap: 10px; padding: 10px 12px; }
        .side-link.active { background: #1b2738; color: #e8f0f8; box-shadow: inset 2px 0 #0057FF; }
        .side-link:hover { background: #162131; color: #e8f0f8; }
        .live-dot { animation: blink 1.4s ease-in-out infinite; background: #4ade80; border-radius: 999px; display: inline-block; height: 7px; width: 7px; }
        @keyframes blink { 50% { opacity: .3; } }
        .status-pill { background: rgba(74,222,128,.09); border: 1px solid rgba(74,222,128,.2); border-radius: 99px; color: #86efac; font-size: 10px; font-weight: 700; letter-spacing: .08em; padding: 5px 9px; text-transform: uppercase; }
        .token-in { color: #f87171; }.token-out { color: #4ade80; }.cost { color: #eab308; }.muted { color: #8190a5; }
        table { border-collapse: collapse; min-width: 720px; width: 100%; }
        table th { background: #162131; border-bottom: 1px solid #243142; color: #8190a5; font-size: 10px; font-weight: 700; letter-spacing: .08em; padding: 11px 14px; text-align: left; text-transform: uppercase; }
        table td { border-bottom: 1px solid rgba(36,49,66,.7); color: #c2cfdd; font-size: 12px; padding: 12px 14px; }
        table tbody tr:hover { background: rgba(27,39,56,.65); }
        .badge { background: rgba(74,222,128,.1); border: 1px solid rgba(74,222,128,.22); border-radius: 4px; color: #86efac; font-size: 10px; font-weight: 700; padding: 3px 6px; text-transform: uppercase; }
        @media (max-width: 1023px) { .sidebar { display: none; } }
        /* Settings controls: stable layout without generated utility classes. */
        #settings { max-width: 980px; margin: 0 auto; }
        #settings .settings-status-card, #settings .settings-wake-card { background:#000000; border:2px solid #333333; border-radius:16px; box-shadow:0 12px 32px rgba(0,0,0,.5); }
        #settings .settings-status-card { padding: 24px; margin-bottom: 18px; }
        #settings .settings-status-head { display:flex; align-items:center; justify-content:space-between; gap:20px; }
        #settings .settings-status-badge { display:inline-flex; align-items:center; gap:8px; border:1px solid rgba(148,163,184,.35); border-radius:999px; padding:8px 14px; color:#cbd5e1; background:rgba(148,163,184,.08); white-space:nowrap; }
        #serviceStatusDot { display:inline-block; width:10px; height:10px; border-radius:50%; background:#94a3b8; }
        #serviceStatusBadge:has(#serviceStatusText) { transition:color .2s, background .2s, border-color .2s; }
        #serviceStatusBadge:has(#serviceStatusText.text-green-400) { border-color:rgba(74,222,128,.4); color:#86efac; background:rgba(74,222,128,.1); }
        #serviceStatusBadge:has(#serviceStatusText.text-red-400) { border-color:rgba(248,113,113,.4); color:#fca5a5; background:rgba(248,113,113,.1); }
        #serviceStatusBadge:has(#serviceStatusText.text-yellow-400) { border-color:rgba(250,204,21,.4); color:#fde047; background:rgba(250,204,21,.1); }
        #serviceStatusDot.bg-green-500 { background:#22c55e !important; }
        #serviceStatusDot.bg-red-500 { background:#ef4444 !important; }
        #serviceStatusDot.bg-yellow-500 { background:#eab308 !important; }
        #settings .settings-status-grid { display:grid; grid-template-columns:1fr 1fr; gap:12px; margin-top:18px; }
        #settings .settings-meta { background:#000000; border:2px solid #333333; border-radius:10px; padding:14px 16px; min-width:0; }
        #settings .settings-wake-card { padding:28px; }
        #settings .settings-wake-head { display:flex; align-items:flex-start; gap:16px; }
        #settings .settings-wake-icon { display:grid; place-items:center; width:48px; height:48px; flex:none; border-radius:12px; background:rgba(139,92,246,.16); font-size:24px; }
        #settings .settings-wake-copy { min-width:0; }
        #settings .settings-wake-copy h3 { margin:0 0 7px; font-size:18px; }
        #settings .settings-wake-copy p { max-width:650px; margin:0; color:#8190a5; font-size:13px; line-height:1.6; }
        #settings .settings-wake-action { display:flex; justify-content:center; padding-top:26px; margin-top:24px; border-top:1px solid #243142; }
        #wakeBtn { position:relative; display:inline-flex; align-items:center; justify-content:center; width:280px; min-height:54px; border:0; border-radius:10px; background:linear-gradient(135deg,#0057FF,#0057FF); box-shadow:0 10px 28px rgba(56,189,248,.28); cursor:pointer; }
        #wakeBtn:hover { background:linear-gradient(135deg,#0057FF,#0057FF); box-shadow:0 12px 34px rgba(56,189,248,.42); transform:translateY(-1px); }
        #wakeBtn:disabled { cursor:not-allowed; opacity:.6; transform:none; }
        #wakeBtn > div { position:static !important; background:none !important; }
        #wakeBtn > div > div { position:static !important; padding:0 !important; background:none !important; }
        #wakeBtnIcon { font-size:20px; }
        #wakeStatusBox { width:100%; box-sizing:border-box; margin-top:18px; border:1px solid #243142; border-radius:8px; padding:13px 16px; background:#0d1520; }
        @media (max-width:640px) { #settings .settings-status-grid { grid-template-columns:1fr; } #settings .settings-wake-card { padding:20px; } #settings .settings-status-head { align-items:flex-start; flex-direction:column; } #wakeBtn { width:100%; } }
        /* Black theme overrides */
        .terminal-card, #settings .settings-status-card, #settings .settings-wake-card { background:rgba(18,18,18,.58); border:0; box-shadow:0 12px 32px rgba(0,0,0,.5), inset 0 1px rgba(255,255,255,.035); backdrop-filter:blur(20px); -webkit-backdrop-filter:blur(20px); }
        .ai-activity {
            background: radial-gradient(2px 2px at 20% 30%, #fff, rgba(255,255,255,0)), 
                        radial-gradient(2px 2px at 60% 70%, #fff, rgba(255,255,255,0)),
                        radial-gradient(1px 1px at 50% 50%, #fff, rgba(255,255,255,0)),
                        radial-gradient(1px 1px at 80% 10%, #fff, rgba(255,255,255,0)),
                        radial-gradient(1px 1px at 10% 90%, #fff, rgba(255,255,255,0)),
                        radial-gradient(3px 3px at 90% 60%, #ffb347, rgba(255,179,71,0)),
                        radial-gradient(2px 2px at 15% 60%, #87ceeb, rgba(135,206,235,0)),
                        linear-gradient(180deg, #0a0e27 0%, #1a2d4d 50%, #0f1a35 100%);
            background-size: 200px 200px, 150px 150px, 180px 180px, 160px 160px, 220px 220px, 100% 100%, 100% 100%, 100% 100%;
            background-attachment: fixed;
            border:0;
            box-shadow:0 12px 32px rgba(0,0,0,.55), inset 0 1px rgba(255,255,255,.04);
        }
        .dashboard-header { min-height:64px; background:rgba(5,5,5,.72); border-bottom:0; backdrop-filter:blur(22px); -webkit-backdrop-filter:blur(22px); }
        #pageTitle { font-family: 'Manrope', sans-serif; font-size:13px; }
        #pageSubtitle { font-size:9px; }
        .sidebar { background:#000; border-right:0; }
        .side-link.active { background:#151515; box-shadow:none; }
        table th { background:#101010; border-bottom:1px solid #222; }
        table td { border-bottom:1px solid #181818; }
        #settings .settings-meta { background:rgba(25,25,25,.5); border:0; backdrop-filter:blur(12px); -webkit-backdrop-filter:blur(12px); }
        #settings .settings-wake-card, #settings .settings-status-card { background:rgba(18,18,18,.58); }
    </style>
</head>
<body class="text-[#e8f0f8]" style="background:#000;">
    <div class="h-screen flex overflow-hidden">
        <aside class="sidebar flex-shrink-0 h-screen px-3 py-1" style="background:#000; border-right:1px solid rgba(148,163,184,.12); width:220px; overflow-y-auto;">
            <div class="mb-0 flex items-center gap-3 px-3 py-0">
                <img src="{{ asset('logos/mcx9route-tech-logo.png') }}" alt="mcx9route logo" width="300" height="300" style="display:block;">
            </div>
            <nav class="space-y-1">
                <a class="side-link active" href="#overview" data-view="overview"><svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="currentColor"><path d="m787-145 28-28-75-75v-112h-40v128l87 87Zm-587 25q-33 0-56.5-23.5T120-200v-560q0-33 23.5-56.5T200-840h560q33 0 56.5 23.5T840-760v268q-19-9-39-15.5t-41-9.5v-243H200v560h242q3 22 9.5 42t15.5 38H200Zm0-120v40-560 243-3 280Zm80-40h163q3-21 9.5-41t14.5-39H280v80Zm0-160h244q32-30 71.5-50t84.5-27v-3H280v80Zm0-160h400v-80H280v80ZM720-40q-83 0-141.5-58.5T520-240q0-83 58.5-141.5T720-440q83 0 141.5 58.5T920-240q0 83-58.5 141.5T720-40Z"/></svg> Overview</a>

                <a class="side-link" href="#apiinfo" data-view="apiinfo"><svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="currentColor"><path d="M223.5-423.5Q200-447 200-480t23.5-56.5Q247-560 280-560t56.5 23.5Q360-513 360-480t-23.5 56.5Q313-400 280-400t-56.5-23.5ZM280-240q-100 0-170-70T40-480q0-100 70-170t170-70q67 0 121.5 33t86.5 87h352l120 120-180 180-80-60-80 60-85-60h-47q-32 54-86.5 87T280-240Zm0-80q56 0 98.5-34t56.5-86h125l58 41 82-61 71 55 75-75-40-40H435q-14-52-56.5-86T280-640q-66 0-113 47t-47 113q0 66 47 113t113 47Z"/></svg> API Info</a>

                <p class="text-xs text-[#8190a5] mt-4" style="margin-left:12px;">System</p>
                <a class="side-link" href="#settings" data-view="settings"><svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="currentColor"><path d="m370-80-16-128q-13-5-24.5-12T307-235l-119 50L78-375l103-78q-1-7-1-13.5v-27q0-6.5 1-13.5L78-585l110-190 119 50q11-8 23-15t24-12l16-128h220l16 128q13 5 24.5 12t22.5 15l119-50 110 190-103 78q1 7 1 13.5v27q0 6.5-2 13.5l103 78-110 190-118-50q-11 8-23 15t-24 12L590-80H370Zm70-80h79l14-106q31-8 57.5-23.5T639-327l99 41 39-68-86-65q5-14 7-29.5t2-31.5q0-16-2-31.5t-7-29.5l86-65-39-68-99 42q-22-23-48.5-38.5T533-694l-13-106h-79l-14 106q-31 8-57.5 23.5T321-633l-99-41-39 68 86 64q-5 15-7 30t-2 32q0 16 2 31t7 30l-86 65 39 68 99-42q22 23 48.5 38.5T427-266l13 106Zm42-180q58 0 99-41t41-99q0-58-41-99t-99-41q-59 0-99.5 41T342-480q0 58 40.5 99t99.5 41Zm-2-140Z"/></svg> Settings</a>
            </nav>

        </aside>

        <main class="min-w-0 flex-1" id="mainContent" style="overflow-y:auto; height:100vh;">
            <header class="dashboard-header flex items-center justify-between gap-4 px-4 sm:px-6" style="min-height:40px; padding-top:4px; padding-bottom:4px;">
                <div class="header-title"><h1 id="pageTitle" class="text-lg font-bold tracking-tight">Usage Overview</h1></div>
            </header>

            <div class="p-4 sm:p-6">
                <div id="statusError" class="mb-4 hidden rounded-md border border-red-400/30 bg-red-950/30 p-3 text-sm text-red-200"><strong>API Error:</strong> <span id="errorMessage"></span></div>


                <select id="periodSelect" style="display:none;">
                    <option value="today">Today</option>
                    <option value="24h">24 Hours</option>
                    <option value="7d">7 Days</option>
                    <option value="30d">30 Days</option>
                    <option value="60d">60 Days</option>
                    <option value="all">All Time</option>
                </select>

                <section id="overview" data-view="overview" class="mb-5">
                    <div class="flex justify-end mb-4">
                        <div class="period-buttons">
                            <button type="button" class="period-button active" data-period="today">Today</button>
                            <button type="button" class="period-button" data-period="24h">24h</button>
                            <button type="button" class="period-button" data-period="7d">7D</button>
                            <button type="button" class="period-button" data-period="30d">30D</button>
                            <button type="button" class="period-button" data-period="60d">60D</button>
                            <button type="button" class="period-button" data-period="all">All</button>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-3 xl:grid-cols-6">
                        <div class="terminal-card p-4"><div class="metric metric--violet"><div class="metric-label">Total Requests</div><div class="metric-value" id="statRequests">—</div></div></div>
                        <div class="terminal-card p-4"><div class="metric metric--red"><div class="metric-label">Input Tokens</div><div class="metric-value token-in" id="statPromptTokens">—</div></div></div>
                        <div class="terminal-card p-4"><div class="metric metric--blue"><div class="metric-label">Cached Tokens</div><div class="metric-value" id="statCachedTokens">—</div></div></div>
                        <div class="terminal-card p-4"><div class="metric metric--green"><div class="metric-label">Output Tokens</div><div class="metric-value token-out" id="statCompletionTokens">—</div></div></div>
                        <div class="terminal-card p-4"><div class="metric metric--yellow"><div class="metric-label">Est. Cost</div><div class="metric-value cost" id="statCost">—</div></div></div>
                        <div class="terminal-card p-4"><div class="metric metric--violet"><div class="metric-label">Cache Hit Rate</div><div class="metric-value" id="statCachePercent">—</div></div></div>
                    </div>
                </section>

                <section id="aiActivity" data-view="overview" class="ai-activity terminal-card mb-5">
                    <div class="ai-activity-head"><div><h2 class="panel-title">ACTIVE AI PROVIDERS</h2><p class="mono mt-1 text-[10px] text-[#8190a5]">CURRENTLY ACTIVE PROVIDER</p></div><span class="mono flex items-center gap-2 text-[10px] text-[#86efac]"><span class="ai-pulse"></span> REAL-TIME</span></div>
                    <div id="activeProviders" class="ai-model-list"><span class="mono text-xs text-[#8190a5]">Loading provider activity…</span></div>
                </section>

                <section data-view="overview" class="mb-5 grid grid-cols-1 gap-4 xl:grid-cols-[minmax(0,1.65fr)_minmax(280px,.85fr)]">
                    <div class="terminal-card overflow-hidden"><div class="flex items-center justify-between px-4 py-3"><div><h2 class="panel-title">REQUEST FLOW</h2><p class="mono mt-1 text-[10px] text-[#8190a5]">REQUESTS OVER TIME</p></div><span id="refreshStatus" class="mono text-[10px] text-[#8190a5]">Auto-refresh 5s</span></div><div class="p-4"><div class="chart-container"><canvas id="requestsChart"></canvas></div></div></div>
                    <div class="terminal-card overflow-hidden"><div class="px-4 py-3"><h2 class="panel-title">TOKEN VOLUME</h2><p class="mono mt-1 text-[10px] text-[#8190a5]">INPUT + OUTPUT ACTIVITY</p></div><div class="p-4"><div class="chart-container"><canvas id="tokensChart"></canvas></div></div></div>
                </section>

                <section id="activity" data-view="activity" class="terminal-card mb-5 overflow-hidden"><div class="flex items-center justify-between px-4 py-3"><div><h2 class="panel-title">RECENT REQUESTS</h2><p class="mono mt-1 text-[10px] text-[#8190a5]">LATEST API ACTIVITY</p></div><span class="mono text-[10px] text-[#8190a5]">LAST 10</span></div><div class="overflow-x-auto"><table><thead><tr><th>Time</th><th>Model</th><th>Provider</th><th>Input</th><th>Output</th><th>Status</th></tr></thead><tbody id="recentTable"></tbody></table></div></section>

                <section id="models" data-view="models" class="terminal-card mb-5 overflow-hidden"><div class="flex items-center justify-between px-4 py-3"><div><h2 class="panel-title">MODEL BREAKDOWN</h2><p class="mono mt-1 text-[10px] text-[#8190a5]">AGGREGATED USAGE BY MODEL</p></div><span class="mono text-[10px] text-[#8190a5]">COST RANKED</span></div><div class="overflow-x-auto"><table><thead><tr><th>Model</th><th>Provider</th><th>Requests</th><th>Input Tokens</th><th>Output Tokens</th><th>Cached</th><th>Total Cost</th><th>Last Used</th></tr></thead><tbody id="modelsTable"></tbody></table></div></section>

                <section id="settings" data-view="settings" class="mb-5" style="display:none;">
                    <div class="mb-8">
                        <p class="mono mb-2 text-[10px] tracking-[.2em] text-blue-300">SERVICE CONTROL</p>
                        <h2 class="text-3xl font-bold text-[#e8f0f8]">Settings</h2>
                        <p class="mt-2 text-sm text-[#8190a5]">Manage 9Router service connection and instance status.</p>
                    </div>

                    <div class="settings-status-card">
                        <div class="settings-status-head">
                            <div><p class="mono text-[10px] tracking-wider text-[#8190a5] mb-1">SERVICE STATUS</p><h3 class="text-lg font-bold text-[#e8f0f8]">9Router Instance</h3></div>
                            <div id="serviceStatusBadge" class="settings-status-badge"><span id="serviceStatusDot"></span><span id="serviceStatusText" class="mono text-xs font-bold uppercase tracking-wider text-yellow-400">CHECKING</span></div>
                        </div>
                        <div class="settings-status-grid">
                            <div class="settings-meta"><p class="mono text-[10px] text-[#8190a5] mb-1">ENDPOINT</p><p class="mono text-xs text-[#c2cfdd] break-all">{{ config('services.ninerouter.url') }}</p></div>
                            <div class="settings-meta"><p class="mono text-[10px] text-[#8190a5] mb-1">LAST CHECK</p><p id="lastCheckTime" class="mono text-xs text-[#c2cfdd]">—</p></div>
                        </div>
                    </div>

                    <div class="settings-wake-card">
                        <div class="settings-wake-head">
                            <div class="settings-wake-icon">⚡</div>
                            <div class="settings-wake-copy"><h3 class="font-bold text-[#e8f0f8]">Wake 9Router</h3><p>Send one request to wake a sleeping Blitz.cloud instance. Use this only when service status is offline or unreachable.</p></div>
                        </div>
                        <div class="settings-wake-action">
                            <button id="wakeBtn" type="button"><div><div><span id="wakeBtnIcon">⚡</span><span id="wakeBtnText" class="ml-2 text-sm font-bold tracking-wide text-white">WAKE INSTANCE</span></div></div></button>
                        </div>
                        <div id="wakeStatusBox" class="hidden"><div class="flex items-start gap-3"><span id="wakeStatusIcon" class="text-lg"></span><p id="wakeStatus" class="text-sm leading-6 text-[#aab8c9]"></p></div></div>
                    </div>

                    <p class="mt-6 text-[11px] leading-5 text-[#64748b]">
                        <strong>Note:</strong> This sends an HTTP request with a 45-second timeout. If the instance was manually stopped or the platform doesn't support auto-wake, the request won't start it.
                    </p>
                </section>

                <section id="apiinfo" data-view="apiinfo" class="mb-5" style="display:none;">
                    <div class="mb-8">
                        <p class="mono mb-2 text-[10px] tracking-[.2em] text-blue-300">API CONFIGURATION</p>
                        <h2 class="text-3xl font-bold text-[#e8f0f8]">API Info</h2>
                        <p class="mt-2 text-sm text-[#8190a5]">Integration credentials for 9Router API access.</p>
                    </div>

                    <div class="rounded-lg border border-amber-500/25 bg-amber-950/20 p-4 mb-6">
                        <div class="flex items-start gap-3">
                            <span class="text-lg">⚠️</span>
                            <div>
                                <p class="font-bold text-amber-200">Keep credentials private</p>
                                <p class="text-sm text-amber-100/70 mt-1">Never share API keys publicly or commit to version control. Anyone with your key can access your models and exhaust your quota.</p>
                            </div>
                        </div>
                    </div>

                    <div class="settings-status-card">
                        <div class="mb-6">
                            <p class="mono text-[10px] text-[#8190a5] mb-3">ENDPOINT</p>
                            <div class="flex items-center gap-2">
                                <input type="text" id="apiEndpoint" readonly class="flex-1 px-3 py-2 rounded bg-[#111823] border border-[#243142] text-[#c2cfdd] font-mono text-sm" value="{{ config('services.ninerouter.url') }}">
                                <button id="copyEndpointBtn" onclick="copyToClipboard('apiEndpoint', 'copyEndpointBtn')" class="px-3 py-2 rounded bg-[#7c3aed]/20 border border-[#7c3aed]/50 text-[#c2cfdd] hover:bg-[#7c3aed]/40 transition text-sm font-mono">Copy</button>
                            </div>
                        </div>

                        <div>
                            <p class="mono text-[10px] text-[#8190a5] mb-3">API KEY</p>
                            <p class="text-sm text-[#8190a5]">API key tidak ditampilkan untuk keamanan. Hubungi admin atau lihat file .env pada server.</p>
                        </div>
                    </div>

                    <div class="settings-status-card mt-6">
                        <p class="mono text-[10px] text-[#8190a5] mb-3">USAGE</p>
                        <code class="block bg-[#0a0f16] border border-[#243142] rounded p-3 text-[#86efac] text-xs leading-relaxed font-mono overflow-x-auto">
curl -X GET {{ config('services.ninerouter.url') }}/api/usage/stats \<br>
&nbsp;&nbsp;-H "Authorization: Basic $(echo -n 'admin:YOUR_PASSWORD' | base64)"
                        </code>
                    </div>
                </section>
            </div>
        </main>
    </div>

    <script>
        const API_ENDPOINT = '/api/stats'; const CHART_ENDPOINT = '/api/chart';
        let loading = false, pollTimeout = null, requestsChartInstance = null, tokensChartInstance = null;
        const chartGrid = 'rgba(129, 144, 165, .14)', chartText = '#8190a5';
        function formatNumber(n) { return new Intl.NumberFormat('id-ID').format(Math.round(Number(n) || 0)); }
        function formatCurrency(n) { return '$' + new Intl.NumberFormat('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(Number(n) || 0); }
        function formatTime(iso) { if (!iso) return '—'; const mins = Math.floor((new Date() - new Date(iso)) / 60000); if (mins < 1) return 'now'; if (mins < 60) return `${mins}m ago`; const hrs = Math.floor(mins / 60); return hrs < 24 ? `${hrs}h ago` : new Date(iso).toLocaleDateString('id-ID'); }
        function text(value) { return String(value ?? '—').replace(/[&<>'"]/g, char => ({ '&':'&amp;', '<':'&lt;', '>':'&gt;', "'":'&#39;', '"':'&quot;' }[char])); }
        async function loadData() {
            if (loading) return; loading = true;
            try {
                document.getElementById('statusError').classList.add('hidden');
                const period = document.getElementById('periodSelect').value;
                const [statsRes, chartRes] = await Promise.all([fetch(`${API_ENDPOINT}?period=${encodeURIComponent(period)}`), fetch(`${CHART_ENDPOINT}?period=${encodeURIComponent(period)}`)]);
                if (!statsRes.ok || !chartRes.ok) throw new Error('Gagal mengambil data 9Router.');
                const stats = await statsRes.json(), chart = await chartRes.json();
                renderStats(stats); renderActiveProviders(stats.recentRequests || []); renderCharts(Array.isArray(chart) ? chart : []); renderRecent(stats.recentRequests || []); renderModels(stats.byModel || {});
            } catch (err) { document.getElementById('errorMessage').textContent = err.message; document.getElementById('statusError').classList.remove('hidden'); }
            finally { loading = false; scheduleNextRefresh(); }
        }
        function renderStats(stats) {
            const cachePercent = Number(stats.totalPromptTokens) > 0 ? Math.round((Number(stats.totalCachedTokens) || 0) / Number(stats.totalPromptTokens) * 100) : 0;
            document.getElementById('statRequests').textContent = formatNumber(stats.totalRequests);
            document.getElementById('statPromptTokens').textContent = formatNumber(stats.totalPromptTokens);
            document.getElementById('statCachedTokens').textContent = formatNumber(stats.totalCachedTokens);
            document.getElementById('statCompletionTokens').textContent = formatNumber(stats.totalCompletionTokens);
            document.getElementById('statCost').textContent = formatCurrency(stats.totalCost);
            document.getElementById('statCachePercent').textContent = `${cachePercent}%`;
        }
        function makeOptions() { return { responsive: true, maintainAspectRatio: false, interaction: { intersect: false, mode: 'index' }, plugins: { legend: { labels: { boxWidth: 8, color: chartText, font: { family: 'DM Mono', size: 10 } } }, tooltip: { backgroundColor: '#111823', borderColor: '#2d3c50', borderWidth: 1, titleColor: '#e8f0f8', bodyColor: '#b5c3d3' } }, scales: { y: { beginAtZero: true, border: { display: false }, grid: { color: chartGrid }, ticks: { color: chartText, font: { family: 'DM Mono', size: 10 } } }, x: { border: { display: false }, grid: { display: false }, ticks: { color: chartText, maxTicksLimit: 8, font: { family: 'DM Mono', size: 10 } } } } }; }
        function renderCharts(data) {
            const labels = data.map(d => d.label || ''), requests = data.map(d => Number(d.requests) || 0), tokens = data.map(d => Number(d.tokens) || 0);
            requestsChartInstance?.destroy(); tokensChartInstance?.destroy();
            requestsChartInstance = new Chart(document.getElementById('requestsChart'), { type: 'line', data: { labels, datasets: [{ label: 'Requests', data: requests, borderColor: '#0057FF', backgroundColor: 'rgba(0,87,255,.12)', pointRadius: 0, pointHoverRadius: 4, borderWidth: 2, tension: .35, fill: true }] }, options: makeOptions() });
            tokensChartInstance = new Chart(document.getElementById('tokensChart'), { type: 'bar', data: { labels, datasets: [{ label: 'Tokens', data: tokens, backgroundColor: 'rgba(74,222,128,.55)', hoverBackgroundColor: '#4ade80', borderRadius: 2, maxBarThickness: 18 }] }, options: makeOptions() });
        }
        function renderRecent(recent) { const rows = recent.slice(0, 10).map(r => `<tr><td class="mono muted">${formatTime(r.timestamp)}</td><td class="mono">${text(r.model)}</td><td>${text(r.provider)}</td><td class="mono token-in">${formatNumber(r.promptTokens)}</td><td class="mono token-out">${formatNumber(r.completionTokens)}</td><td><span class="badge">${text(r.status || 'ok')}</span></td></tr>`).join(''); document.getElementById('recentTable').innerHTML = rows || '<tr><td colspan="6" class="muted py-5 text-center">No recent requests</td></tr>'; }
        function renderActiveProviders(recentRequests) {
            const container = document.getElementById('activeProviders');
            const now = Date.now();
            const recentThreshold = 60 * 1000;
            
            const providerChars = [
                { match: ['codex', 'openai', 'open ai'], color: '#22c55e' },
                { match: ['opencode', 'open code'], color: '#3b82f6' },
                { match: ['ollama'], color: '#f97316' },
                { match: ['antigrafity', 'antigravity'], color: '#eab308' },
                { match: ['kiro'], color: '#ec4899' },
                { match: ['harbor'], color: '#06b6d4' },
                { match: ['codebuddy', 'buddy'], color: '#a855f7' },
            ];
            const pickChar = name => {
                const key = String(name || '').toLowerCase();
                return providerChars.find(p => p.match.some(m => key.includes(m))) || null;
            };

            let latestProvider = null;
            let latestTime = 0;

            recentRequests.forEach(request => {
                const lastUsed = request.timestamp ? new Date(request.timestamp).getTime() : 0;
                if (!lastUsed || now - lastUsed < 0 || now - lastUsed > recentThreshold) return;
                
                if (lastUsed > latestTime) {
                    latestTime = lastUsed;
                    latestProvider = request.provider;
                }
            });

            if (latestProvider) {
                const char = pickChar(latestProvider);
                const color = char?.color || '#94a3b8';
                container.innerHTML = `<div class="ai-model-chip" title="${text(latestProvider)}"><span class="ai-model-dot" style="--crew-color:${color}"><i></i></span><span class="ai-model-name">${text(latestProvider)}</span></div>`;
            } else {
                container.innerHTML = '<span class="mono text-xs text-[#8190a5]">No active provider</span>';
            }
        }
        function renderModels(models) { const rows = Object.entries(models).sort((a,b) => (Number(b[1].cost)||0) - (Number(a[1].cost)||0)).map(([name,m]) => `<tr><td class="mono">${text(m.rawModel || name)}</td><td>${text(m.provider)}</td><td class="mono">${formatNumber(m.requests)}</td><td class="mono token-in">${formatNumber(m.promptTokens)}</td><td class="mono token-out">${formatNumber(m.completionTokens)}</td><td class="mono">${formatNumber(m.cachedTokens)}</td><td class="mono cost">${formatCurrency(m.cost)}</td><td class="mono muted">${formatTime(m.lastUsed)}</td></tr>`).join(''); document.getElementById('modelsTable').innerHTML = rows || '<tr><td colspan="8" class="muted py-5 text-center">No model data</td></tr>'; }
        function scheduleNextRefresh() { clearTimeout(pollTimeout); document.getElementById('refreshStatus').textContent = 'Next refresh 5s'; pollTimeout = setTimeout(loadData, 5000); }
        const viewMeta = {
            overview: { title: 'Usage Overview', sub: '9ROUTER / ANALYTICS' },
            activity: { title: 'Recent Activity', sub: '9ROUTER / REQUEST LOG' },
            models: { title: 'Model Breakdown', sub: '9ROUTER / MODELS' },
            settings: { title: 'Settings', sub: '9ROUTER / MANAGEMENT' },
            apiinfo: { title: 'API Info', sub: '9ROUTER / INTEGRATION' },
        };
        function switchView(target) {
            const meta = viewMeta[target] || viewMeta.overview;
            document.getElementById('pageTitle').textContent = meta.title;
            document.querySelectorAll('.side-link').forEach(link => {
                link.classList.toggle('active', link.dataset.view === target);
            });
            document.querySelectorAll('main section[data-view]').forEach(section => {
                if (target === 'settings' || target === 'apiinfo') {
                    section.style.display = section.dataset.view === target ? '' : 'none';
                } else {
                    section.style.display = (section.dataset.view === 'settings' || section.dataset.view === 'apiinfo') ? 'none' : '';
                }
            });
            if (target === 'settings') {
                window.scrollTo({ top: 0, behavior: 'smooth' });
                checkServiceStatus();
            } else if (target === 'apiinfo') {
                window.scrollTo({ top: 0, behavior: 'smooth' });
            } else if (target === 'overview') {
                window.scrollTo({ top: 0, behavior: 'smooth' });
                requestsChartInstance?.resize();
                tokensChartInstance?.resize();
            } else {
                document.getElementById(target)?.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        }
        document.querySelectorAll('.side-link').forEach(link => {
            link.addEventListener('click', e => {
                e.preventDefault();
                switchView(link.dataset.view);
                location.hash = link.dataset.view;
            });
        });
        const initialView = ['overview', 'settings', 'apiinfo'].includes(location.hash.slice(1)) ? location.hash.slice(1) : 'overview';
        switchView(initialView);
        if (initialView === 'settings') {
            checkServiceStatus();
        }
        const periodSelect = document.getElementById('periodSelect');
        document.querySelectorAll('.period-button').forEach(btn => {
          btn.addEventListener('click', () => {
            document.querySelectorAll('.period-button').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            periodSelect.value = btn.dataset.period;
            periodSelect.dispatchEvent(new Event('change'));
          });
        });
        periodSelect.addEventListener('change', loadData);
        loadData();

        let lastServiceCheck = null;
        function checkServiceStatus() {
            const badge = document.getElementById('serviceStatusBadge');
            const dot = document.getElementById('serviceStatusDot');
            const label = document.getElementById('serviceStatusText');
            const timeEl = document.getElementById('lastCheckTime');
            if (!badge) return;

            badge.className = 'flex items-center gap-2 rounded-full border border-yellow-500/30 bg-yellow-500/10 px-4 py-2';
            dot.className = 'h-2.5 w-2.5 rounded-full bg-yellow-500 animate-pulse';
            label.textContent = 'CHECKING';
            label.className = 'mono text-xs font-bold uppercase tracking-wider text-yellow-400';

            fetch('/api/stats?period=today', { method: 'GET', headers: { 'Accept': 'application/json' } })
                .then(res => {
                    lastServiceCheck = new Date();
                    timeEl.textContent = lastServiceCheck.toLocaleTimeString('en-US', { hour12: false });
                    if (res.ok) {
                        badge.className = 'flex items-center gap-2 rounded-full border border-green-500/30 bg-green-500/10 px-4 py-2';
                        dot.className = 'h-2.5 w-2.5 rounded-full bg-green-500 animate-pulse';
                        label.textContent = 'ONLINE';
                        label.className = 'mono text-xs font-bold uppercase tracking-wider text-green-400';
                    } else {
                        badge.className = 'flex items-center gap-2 rounded-full border border-red-500/30 bg-red-500/10 px-4 py-2';
                        dot.className = 'h-2.5 w-2.5 rounded-full bg-red-500';
                        label.textContent = 'OFFLINE';
                        label.className = 'mono text-xs font-bold uppercase tracking-wider text-red-400';
                    }
                })
                .catch(() => {
                    lastServiceCheck = new Date();
                    timeEl.textContent = lastServiceCheck.toLocaleTimeString('en-US', { hour12: false });
                    badge.className = 'flex items-center gap-2 rounded-full border border-red-500/30 bg-red-500/10 px-4 py-2';
                    dot.className = 'h-2.5 w-2.5 rounded-full bg-red-500';
                    label.textContent = 'UNREACHABLE';
                    label.className = 'mono text-xs font-bold uppercase tracking-wider text-red-400';
                });
        }

        const wakeBtn = document.getElementById('wakeBtn');
        const wakeBtnText = document.getElementById('wakeBtnText');
        const wakeBtnIcon = document.getElementById('wakeBtnIcon');
        const wakeStatus = document.getElementById('wakeStatus');
        const wakeStatusIcon = document.getElementById('wakeStatusIcon');
        const wakeStatusBox = document.getElementById('wakeStatusBox');

        wakeBtn?.addEventListener('mousemove', e => {
            const rect = e.currentTarget.getBoundingClientRect();
            const x = ((e.clientX - rect.left) / rect.width * 100).toFixed(2);
            const y = ((e.clientY - rect.top) / rect.height * 100).toFixed(2);
            e.currentTarget.style.setProperty('--mouse-x', x + '%');
            e.currentTarget.style.setProperty('--mouse-y', y + '%');
        });

        wakeBtn?.addEventListener('click', async () => {
            wakeBtn.disabled = true;
            wakeBtn.classList.add('opacity-50', 'cursor-not-allowed');
            wakeBtnText.textContent = 'Waking up...';
            wakeBtnIcon.textContent = '⏳';
            wakeStatusBox.classList.remove('hidden');
            wakeStatus.textContent = 'Sending HTTP signal to Blitz.cloud... (this may take 15-40 seconds)';
            wakeStatusIcon.textContent = '⏳';

            try {
                const res = await fetch('/api/wake-up', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    },
                });
                const data = await res.json();

                if (res.ok) {
                    wakeStatusIcon.textContent = '✓';
                    wakeStatus.textContent = `Success! ${data.message} Response time: ${data.elapsed_seconds}s`;
                    wakeBtnText.textContent = 'Instance Active';
                    wakeBtnIcon.textContent = '✓';
                    checkServiceStatus();
                    setTimeout(() => {
                        loadData();
                        switchView('overview');
                        location.hash = 'overview';
                    }, 1500);
                } else if (res.status === 429) {
                    wakeStatusIcon.textContent = '⏸';
                    wakeStatus.textContent = `Cooldown active. ${data.message}`;
                    wakeBtnText.textContent = 'Wait Cooldown';
                    wakeBtnIcon.textContent = '⏸';
                } else {
                    wakeStatusIcon.textContent = '✗';
                    wakeStatus.textContent = `Failed: ${data.message || 'Unknown error'}`;
                    wakeBtnText.textContent = 'Try Again';
                    wakeBtnIcon.textContent = '⚡';
                }
            } catch (err) {
                wakeStatusIcon.textContent = '✗';
                wakeStatus.textContent = `Error: ${err.message}`;
                wakeBtnText.textContent = 'Try Again';
                wakeBtnIcon.textContent = '⚡';
            } finally {
                setTimeout(() => {
                    wakeBtn.disabled = false;
                    wakeBtn.classList.remove('opacity-50', 'cursor-not-allowed');
                    if (wakeBtnText.textContent !== 'Waking up...') {
                        wakeBtnText.textContent = 'Wake 9Router';
                        wakeBtnIcon.textContent = '⚡';
                    }
                }, 5000);
            }
        });

        function copyToClipboard(elementId, buttonId) {
            const element = document.getElementById(elementId);
            const button = document.getElementById(buttonId);
            const originalText = button.textContent;
            
            navigator.clipboard.writeText(element.value).then(() => {
                button.textContent = 'Copied!';
                button.style.background = '#22c55e/20';
                button.style.borderColor = '#22c55e/50';
                button.style.color = '#86efac';
                setTimeout(() => {
                    button.textContent = originalText;
                    button.style.background = '#7c3aed/20';
                    button.style.borderColor = '#7c3aed/50';
                    button.style.color = '#c2cfdd';
                }, 2000);
            }).catch(() => {
                button.textContent = 'Failed';
                setTimeout(() => {
                    button.textContent = originalText;
                }, 2000);
            });
        }

        let keyVisible = false;
        function toggleKeyVisibility() {
            const keyInput = document.getElementById('apiKey');
            const toggleBtn = document.getElementById('toggleKeyBtn');
            const fullKey = "{{ config('services.ninerouter.password') ?? '' }}";
            
            if (keyVisible) {
                keyInput.type = 'password';
                keyInput.value = fullKey.substring(0, 4) + '•'.repeat(Math.max(0, fullKey.length - 4));
                toggleBtn.textContent = 'Show';
                keyVisible = false;
            } else {
                keyInput.type = 'text';
                keyInput.value = fullKey;
                toggleBtn.textContent = 'Hide';
                keyVisible = true;
            }
        }
        const mainContent = document.getElementById('mainContent');
        mainContent.addEventListener('scroll', () => {
            const header = document.querySelector('.dashboard-header');
            if (mainContent.scrollTop > 0) {
                header.style.boxShadow = '0 4px 16px rgba(0,0,0,.6)';
            } else {
                header.style.boxShadow = 'none';
            }
        });
</script>
</body>
</html>
