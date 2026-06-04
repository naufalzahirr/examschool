<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Ujian Sekolah' }} | Backend Ujian</title>
    <link rel="icon" type="image/x-icon" href="{{ asset('assets/img/favicon/favicon.ico') }}">
    <style>
        :root{
            --bg:#f7f7fb;
            --bg-2:#eef7f5;
            --card:#ffffff;
            --text:#3f4756;
            --heading:#171923;
            --muted:#738096;
            --primary:#14b8a6;
            --primary-strong:#0f766e;
            --primary-soft:#ddf8f4;
            --accent:#ff5a7a;
            --accent-soft:#ffe5eb;
            --violet:#7c3aed;
            --violet-soft:#efe7ff;
            --success:#22c55e;
            --success-soft:#dcfce7;
            --danger:#ef4444;
            --danger-soft:#fee2e2;
            --warning:#f59e0b;
            --warning-soft:#fef3c7;
            --info:#0ea5e9;
            --info-soft:#e0f2fe;
            --line:#e6e8ef;
            --line-strong:#d4dae6;
            --menu:#181a20;
            --menu-soft:#242731;
            --shadow:0 14px 34px rgba(15,23,42,.08);
            --shadow-soft:0 8px 20px rgba(15,23,42,.06);
            --radius:8px;
        }

        *{box-sizing:border-box}
        html{scroll-behavior:smooth}
        body{
            margin:0;
            font-family:Inter,"Public Sans",system-ui,-apple-system,"Segoe UI",Roboto,Arial,sans-serif;
            background:linear-gradient(180deg,#fbfcff 0%,var(--bg-2) 42%,var(--bg) 100%);
            color:var(--text);
            font-size:15px;
            line-height:1.5;
        }
        body:before{
            content:"";
            position:fixed;
            inset:0;
            pointer-events:none;
            opacity:.34;
            background-image:
                linear-gradient(rgba(15,23,42,.04) 1px,transparent 1px),
                linear-gradient(90deg,rgba(15,23,42,.035) 1px,transparent 1px);
            background-size:42px 42px;
            mask-image:linear-gradient(180deg,#000 0%,transparent 72%);
        }
        a{color:inherit;text-decoration:none}
        h1,h2,h3,h4{color:var(--heading);letter-spacing:0;margin-top:0;line-height:1.16}
        h1{font-size:30px;margin-bottom:.35rem}
        h2{font-size:22px}
        h3{font-size:18px}
        p{margin-top:0}
        .mb{margin-bottom:1.25rem}
        .mb0{margin-bottom:0}
        .mt{margin-top:1.25rem}
        .small{font-size:13px}
        .tiny{font-size:12px}
        .muted{color:var(--muted)}

        .layout-wrapper{min-height:100vh;display:flex;position:relative}
        .sidebar{
            width:280px;
            position:fixed;
            inset:0 auto 0 0;
            background:var(--menu);
            color:#f8fafc;
            z-index:20;
            transition:.22s ease;
            display:flex;
            flex-direction:column;
            border-right:1px solid rgba(255,255,255,.08);
        }
        .brand{
            height:76px;
            display:flex;
            align-items:center;
            gap:12px;
            padding:0 22px;
            font-weight:900;
            font-size:19px;
            color:#fff;
            letter-spacing:.01em;
        }
        .brand-logo{
            width:42px;
            height:42px;
            border-radius:12px;
            background:linear-gradient(135deg,var(--primary),var(--accent));
            display:grid;
            place-items:center;
            color:#fff;
            font-weight:950;
            box-shadow:0 12px 26px rgba(20,184,166,.24);
        }
        .menu{padding:8px 14px 24px;overflow:auto}
        .menu-header{
            font-size:11px;
            text-transform:uppercase;
            color:#8790a4;
            letter-spacing:.12em;
            font-weight:900;
            margin:20px 12px 8px;
        }
        .menu-link{
            display:flex;
            align-items:center;
            gap:11px;
            padding:11px 12px;
            border-radius:10px;
            color:#cbd5e1;
            margin:4px 0;
            font-weight:800;
            transition:.18s ease;
        }
        .menu-link:hover{background:var(--menu-soft);color:#fff;transform:translateX(2px)}
        .menu-link.active{
            background:linear-gradient(135deg,var(--primary),var(--accent));
            color:#fff;
            box-shadow:0 12px 26px rgba(255,90,122,.22);
        }
        .menu-ico{
            width:29px;
            height:29px;
            border-radius:8px;
            display:grid;
            place-items:center;
            flex:0 0 29px;
            background:rgba(255,255,255,.08);
            color:#fff;
            font-size:10px;
            font-weight:950;
            letter-spacing:.02em;
        }
        .menu-link.active .menu-ico{background:rgba(255,255,255,.22)}
        .layout-page{
            margin-left:280px;
            min-height:100vh;
            width:calc(100% - 280px);
            display:flex;
            flex-direction:column;
            position:relative;
            z-index:1;
        }
        .navbar{
            min-height:74px;
            position:sticky;
            top:0;
            z-index:10;
            margin:18px 26px 0;
            background:rgba(255,255,255,.86);
            backdrop-filter:blur(18px);
            border:1px solid rgba(230,232,239,.82);
            border-radius:14px;
            box-shadow:var(--shadow-soft);
            display:flex;
            align-items:center;
            justify-content:space-between;
            gap:1rem;
            padding:12px 18px;
        }
        .content{padding:26px;max-width:1440px;width:100%;margin:0 auto}
        .user-chip{display:flex;align-items:center;gap:10px}
        .avatar{
            width:40px;
            height:40px;
            border-radius:999px;
            object-fit:cover;
            background:var(--primary-soft);
            border:2px solid #fff;
            box-shadow:0 0 0 1px var(--line);
        }
        .mobile-toggle{
            display:none;
            border:0;
            background:var(--heading);
            color:#fff;
            border-radius:10px;
            padding:9px 12px;
            font-weight:950;
        }

        .card{
            background:rgba(255,255,255,.94);
            border:1px solid rgba(230,232,239,.94);
            border-radius:var(--radius);
            box-shadow:var(--shadow-soft);
            padding:1.35rem;
        }
        .hero{
            background:
                linear-gradient(135deg,rgba(20,184,166,.12),rgba(255,90,122,.10)),
                #fff;
            border:1px solid rgba(230,232,239,.95);
            border-radius:var(--radius);
            box-shadow:var(--shadow);
            padding:1.55rem;
            position:relative;
            overflow:hidden;
        }
        .hero:before{
            content:"";
            position:absolute;
            left:0;
            top:0;
            bottom:0;
            width:5px;
            background:linear-gradient(180deg,var(--primary),var(--accent));
        }
        .grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:1.1rem}
        .two{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:1.1rem}
        .three{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:1.1rem}
        .stat{font-size:34px;font-weight:950;color:var(--heading);letter-spacing:0;line-height:1}
        .between{display:flex;justify-content:space-between;gap:1rem;align-items:center}
        .row{display:flex;align-items:center;gap:.6rem;flex-wrap:wrap}

        .btn{
            border:1px solid transparent;
            border-radius:10px;
            min-height:38px;
            padding:.68rem .95rem;
            background:#fff;
            color:var(--heading);
            font-weight:900;
            cursor:pointer;
            display:inline-flex;
            align-items:center;
            justify-content:center;
            gap:.4rem;
            line-height:1.1;
            box-shadow:0 1px 0 rgba(15,23,42,.04),0 8px 18px rgba(15,23,42,.05);
            transition:transform .16s ease,box-shadow .16s ease,background .16s ease,border-color .16s ease;
            white-space:nowrap;
        }
        .btn:hover{transform:translateY(-1px);box-shadow:0 12px 24px rgba(15,23,42,.10)}
        .btn:active{transform:translateY(0)}
        .btn.primary{background:linear-gradient(135deg,var(--primary),#10b981);color:#fff}
        .btn.soft{background:var(--primary-soft);color:var(--primary-strong);border-color:#bdeee7}
        .btn.green{background:var(--success-soft);color:#166534;border-color:#bbf7d0}
        .btn.danger{background:var(--danger-soft);color:#991b1b;border-color:#fecaca}
        .btn.warning{background:var(--warning-soft);color:#92400e;border-color:#fde68a}
        .btn.ghost{background:transparent;border:1px dashed var(--line-strong);box-shadow:none}

        .badge,.pill{
            display:inline-flex;
            align-items:center;
            gap:.35rem;
            border-radius:999px;
            padding:.36rem .68rem;
            font-size:12px;
            font-weight:900;
            background:var(--primary-soft);
            color:var(--primary-strong);
            white-space:nowrap;
            border:1px solid rgba(20,184,166,.14);
        }
        .badge.published,.badge.submitted,.badge.active,.badge.success{background:var(--success-soft);color:#166534;border-color:#bbf7d0}
        .badge.ready,.badge.info{background:var(--info-soft);color:#075985;border-color:#bae6fd}
        .badge.archived,.badge.draft,.badge.assigned{background:#f1f5f9;color:#475569;border-color:#e2e8f0}
        .badge.closed,.badge.inactive,.badge.danger{background:var(--danger-soft);color:#991b1b;border-color:#fecaca}
        .badge.download_ready,.badge.downloaded,.badge.unlocked{background:var(--violet-soft);color:#5b21b6;border-color:#ddd6fe}
        .badge.downloading,.badge.in_progress,.badge.synced,.badge.locked,.badge.warning{background:var(--warning-soft);color:#92400e;border-color:#fde68a}

        .table-wrap{width:100%;overflow:auto}
        .table{width:100%;border-collapse:separate;border-spacing:0}
        .table th{
            font-size:11px;
            text-transform:uppercase;
            letter-spacing:.08em;
            color:#64748b;
            background:#f8fafc;
            text-align:left;
            padding:.86rem 1rem;
            white-space:nowrap;
            border-bottom:1px solid var(--line);
        }
        .table td{
            padding:1rem;
            border-bottom:1px solid var(--line);
            vertical-align:top;
            background:#fff;
        }
        .table tr:hover td{background:#fbfffe}
        .table tbody tr:last-child td{border-bottom:0}

        .form{display:grid;gap:1rem}
        .field label{display:block;font-weight:900;color:var(--heading);margin-bottom:.45rem}
        .input,textarea,select{
            width:100%;
            border:1px solid var(--line-strong);
            border-radius:10px;
            padding:.72rem .86rem;
            background:#fff;
            color:var(--text);
            font:inherit;
            min-height:40px;
            transition:.16s ease;
        }
        textarea{resize:vertical}
        .input:focus,textarea:focus,select:focus{
            outline:0;
            border-color:var(--primary);
            box-shadow:0 0 0 4px rgba(20,184,166,.14);
        }
        input:disabled,textarea:disabled,select:disabled,button:disabled{opacity:.68;cursor:not-allowed}
        .help{font-size:13px;color:var(--muted);margin:.35rem 0 0}

        .alert{
            padding:.95rem 1.05rem;
            border-radius:var(--radius);
            margin-bottom:1rem;
            font-weight:800;
            border:1px solid transparent;
        }
        .alert.success{background:var(--success-soft);color:#166534;border-color:#bbf7d0}
        .alert.error{background:var(--danger-soft);color:#991b1b;border-color:#fecaca}
        .alert.info{background:var(--info-soft);color:#075985;border-color:#bae6fd}
        .alert.warning{background:var(--warning-soft);color:#92400e;border-color:#fde68a}

        .code,.import-format{
            white-space:pre-wrap;
            background:#181a20;
            color:#e5e7eb;
            padding:1rem;
            border-radius:var(--radius);
            overflow:auto;
            font-size:13px;
            border:1px solid #2f3441;
        }
        .searchbar{display:flex;gap:.65rem}
        .searchbar .input{max-width:360px}
        .class-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:1rem}
        .mini-card{
            border:1px solid var(--line);
            border-radius:var(--radius);
            padding:1rem;
            background:#fbfcff;
        }
        .check-list{display:grid;gap:.55rem;margin-top:.8rem}
        .check-pill{
            display:flex;
            align-items:center;
            gap:.6rem;
            padding:.72rem .78rem;
            border-radius:var(--radius);
            background:#f8fafc;
            border:1px solid var(--line);
            font-weight:800;
        }
        .check-pill input{width:auto}
        .q-card{border:1px solid var(--line)}
        .q-card.active{box-shadow:0 14px 30px rgba(20,184,166,.14);border-color:#99f6e4}
        .type-note{padding:.75rem .9rem;border-radius:var(--radius);background:var(--info-soft);color:#075985}
        .options-list,.matching-list{display:grid;gap:.65rem}
        .option-row,.matching-row{
            display:grid;
            grid-template-columns:auto minmax(0,1fr) auto auto;
            gap:.6rem;
            align-items:center;
            padding:.65rem;
            border:1px solid var(--line);
            border-radius:var(--radius);
            background:#fff;
        }
        .matching-row{grid-template-columns:minmax(0,1fr) minmax(0,1fr) auto}
        .split-note{display:grid;grid-template-columns:1fr 1fr;gap:.65rem;color:var(--muted);font-size:13px}
        .tf-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:.75rem}
        .tf-card{border:1px solid var(--line);border-radius:var(--radius);padding:1rem;background:#fff;font-weight:900}
        .tf-card:has(input:checked){border-color:var(--primary);background:var(--primary-soft);color:var(--primary-strong)}
        .mt-sm{margin-top:.35rem}
        .mb-sm{margin-bottom:.35rem}

        .pagination,.pagination nav{margin-top:1rem}
        .pagination svg,.us-pagination svg{width:16px!important;height:16px!important;max-width:16px!important;max-height:16px!important}
        .us-pagination{width:100%;font-size:13px;color:var(--muted)}
        .us-pagination-desktop{display:flex;align-items:center;justify-content:space-between;gap:1rem;flex-wrap:wrap}
        .us-pagination-mobile{display:none;align-items:center;justify-content:space-between;gap:.65rem;flex-wrap:wrap}
        .us-page-info{white-space:nowrap}
        .us-page-list{display:inline-flex;align-items:center;gap:.35rem;flex-wrap:wrap}
        .us-page-square,.us-page-btn{
            min-width:34px;
            height:34px;
            border:1px solid var(--line);
            border-radius:10px;
            background:#fff;
            color:var(--heading);
            display:inline-flex;
            align-items:center;
            justify-content:center;
            font-size:13px;
            font-weight:900;
            line-height:1;
            padding:0 .55rem;
        }
        .us-page-btn{width:auto;min-width:112px}
        .us-page-square:hover,.us-page-btn:hover{background:var(--primary-soft);border-color:#99f6e4;color:var(--primary-strong)}
        .us-page-square.active{background:var(--primary);border-color:var(--primary);color:#fff}
        .us-page-square.disabled,.us-page-btn.disabled,.us-page-square.dots{background:#f8fafc;color:#94a3b8;cursor:not-allowed;border-color:#e2e8f0}
        .us-page-summary{font-weight:900;color:var(--muted);white-space:nowrap}

        .data-card{padding:0;overflow:hidden}
        .table-toolbar{
            display:flex;
            align-items:flex-end;
            justify-content:space-between;
            gap:1rem;
            padding:1.15rem 1.35rem;
            border-bottom:1px solid var(--line);
            background:linear-gradient(135deg,#fff 0%,#f7fffd 100%);
        }
        .table-title h2{margin:0}
        .table-tools{display:flex;align-items:end;gap:.65rem;flex-wrap:wrap;justify-content:flex-end}
        .tool-field{min-width:180px}
        .tool-field label{display:block;font-size:11px;text-transform:uppercase;letter-spacing:.08em;font-weight:950;color:#94a3b8;margin-bottom:.35rem}
        .tool-field.search{min-width:280px}
        .live-search-wrap{position:relative}
        .live-search-wrap:before{
            content:"";
            position:absolute;
            left:.86rem;
            top:50%;
            width:9px;
            height:9px;
            border:2px solid #94a3b8;
            border-radius:999px;
            transform:translateY(-58%);
            pointer-events:none;
        }
        .live-search-wrap:after{
            content:"";
            position:absolute;
            left:1.47rem;
            top:50%;
            width:7px;
            height:2px;
            background:#94a3b8;
            transform:rotate(45deg) translateY(3px);
            border-radius:999px;
            pointer-events:none;
        }
        .live-search-wrap .input{padding-left:2.25rem}
        .table-meta{display:flex;align-items:center;gap:.45rem;flex-wrap:wrap;padding:.85rem 1.35rem;border-top:1px solid var(--line);background:#fff}
        .table-actions{display:flex;gap:.5rem;flex-wrap:wrap}

        .import-hero{
            background:linear-gradient(135deg,#fff 0%,#ecfeff 55%,#fff7ed 100%);
            border-radius:var(--radius);
            border:1px solid var(--line);
            box-shadow:var(--shadow);
            padding:1.5rem;
        }
        .import-layout{display:grid;grid-template-columns:minmax(0,1.25fr) minmax(280px,.75fr);gap:1.25rem;align-items:start}
        .step-list{display:grid;gap:.75rem}
        .step{display:flex;gap:.75rem;align-items:flex-start;padding:.85rem;border:1px solid var(--line);border-radius:var(--radius);background:#fff}
        .step-no{min-width:32px;height:32px;border-radius:10px;background:linear-gradient(135deg,var(--primary),var(--accent));color:#fff;display:grid;place-items:center;font-weight:950}
        .import-toolbar{display:flex;align-items:center;justify-content:space-between;gap:1rem;margin-bottom:1rem}
        .kbd{border:1px solid var(--line);border-bottom-width:2px;border-radius:6px;padding:.1rem .35rem;background:#fff;font-size:12px;font-weight:900;color:var(--muted)}

        .auth-page{
            min-height:100vh;
            display:grid;
            place-items:center;
            padding:24px;
            background:
                linear-gradient(135deg,rgba(20,184,166,.12),rgba(255,90,122,.10)),
                var(--bg);
        }
        .auth-card{width:100%;max-width:480px}
        .auth-logo{display:flex;align-items:center;justify-content:center;gap:10px;margin-bottom:18px;font-weight:950;font-size:24px;color:var(--heading)}
        .footer-note{padding:0 26px 20px;color:var(--muted);font-size:13px;text-align:center}

        @media(max-width:1100px){
            .grid{grid-template-columns:repeat(2,1fr)}
            .two,.three,.class-grid,.import-layout{grid-template-columns:1fr}
            .sidebar{transform:translateX(-105%)}
            body.menu-open .sidebar{transform:translateX(0)}
            .layout-page{margin-left:0;width:100%}
            .mobile-toggle{display:inline-flex}
            .navbar{margin:12px 14px 0}
            .content{padding:18px 14px}
            .between{align-items:flex-start;flex-direction:column}
            .searchbar,.table-toolbar,.table-tools{flex-direction:column;align-items:stretch}
            .searchbar .input,.tool-field,.tool-field.search{max-width:none;min-width:0}
            .table{font-size:13px}
            .user-chip{align-self:stretch;justify-content:space-between}
        }
        @media(max-width:760px){
            .us-pagination-desktop{display:none}
            .us-pagination-mobile{display:flex}
            .us-page-btn{min-width:104px;height:34px}
            .us-page-summary{width:100%;text-align:center;order:-1}
        }
        @media(max-width:640px){
            h1{font-size:25px}
            .grid{grid-template-columns:1fr}
            .card,.hero,.import-hero{padding:1rem}
            .data-card{padding:0}
            .table-toolbar{padding:1rem}
            .user-chip .muted{display:none}
            .btn{width:100%}
            .row .btn{width:auto}
        }
    </style>
    @stack('head')
</head>
<body>
@guest
    <div class="auth-page">
        <div class="auth-card">
            @if(session('success')) <div class="alert success">{{ session('success') }}</div> @endif
            @if(session('info')) <div class="alert info">{{ session('info') }}</div> @endif
            @if($errors->any()) <div class="alert error"><b>Ada yang perlu diperbaiki:</b><br>{{ implode(' ', $errors->all()) }}</div> @endif
            @yield('content')
        </div>
    </div>
@else
    <div class="layout-wrapper">
        <aside class="sidebar">
            <a href="{{ route('dashboard') }}" class="brand"><span class="brand-logo">US</span><span>Ujian Sekolah</span></a>
            <nav class="menu">
                <div class="menu-header">Utama</div>
                <a class="menu-link {{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}"><span class="menu-ico">DB</span>Dashboard</a>
                <a class="menu-link {{ request()->routeIs('guide') ? 'active' : '' }}" href="{{ route('guide') }}"><span class="menu-ico">PD</span>Panduan</a>
                @if(auth()->user()->isAdmin() || auth()->user()->isTeacher())
                    <a class="menu-link {{ request()->routeIs('exams.*') ? 'active' : '' }}" href="{{ route('exams.index') }}"><span class="menu-ico">UJ</span>Ujian</a>
                    <a class="menu-link {{ request()->routeIs('question-bank.*') ? 'active' : '' }}" href="{{ route('question-bank.index') }}"><span class="menu-ico">BS</span>Bank Soal</a>
                @endif
                @if(auth()->user()->isAdmin())
                    <div class="menu-header">Data Master</div>
                    <a class="menu-link {{ request()->routeIs('classrooms.*') ? 'active' : '' }}" href="{{ route('classrooms.index') }}"><span class="menu-ico">KL</span>Kelas</a>
                    <a class="menu-link {{ request()->routeIs('students.*') ? 'active' : '' }}" href="{{ route('students.index') }}"><span class="menu-ico">SW</span>Siswa</a>
                    <a class="menu-link {{ request()->routeIs('teachers.*') ? 'active' : '' }}" href="{{ route('teachers.index') }}"><span class="menu-ico">GR</span>Guru</a>
                    <a class="menu-link {{ request()->routeIs('accounts.*') ? 'active' : '' }}" href="{{ route('accounts.index') }}"><span class="menu-ico">AK</span>Akun</a>
                    <a class="menu-link {{ request()->routeIs('silap.*') ? 'active' : '' }}" href="{{ route('silap.index') }}"><span class="menu-ico">SI</span>Sinkron SILAP</a>
                    <div class="menu-header">Produksi</div>
                    <a class="menu-link {{ request()->routeIs('settings.*') ? 'active' : '' }}" href="{{ route('settings.school.edit') }}"><span class="menu-ico">PG</span>Pengaturan</a>
                    <a class="menu-link {{ request()->routeIs('audit.*') ? 'active' : '' }}" href="{{ route('audit.index') }}"><span class="menu-ico">LG</span>Audit Log</a>
                @endif
            </nav>
        </aside>
        <section class="layout-page">
            <header class="navbar">
                <button type="button" class="mobile-toggle" onclick="document.body.classList.toggle('menu-open')">Menu</button>
                <div>
                    <div class="small muted">Ruang kendali ujian</div>
                    <b>{{ $title ?? 'Ujian Sekolah' }}</b>
                </div>
                <div class="user-chip">
                    <img class="avatar" src="{{ asset('assets/img/avatars/1.png') }}" alt="avatar">
                    <div>
                        <b>{{ auth()->user()->name }}</b><br><a class="muted small" href="{{ route('profile.password.edit') }}">{{ auth()->user()->role ?? 'admin' }} | ganti password</a>
                    </div>
                    <form method="POST" action="{{ route('logout') }}">@csrf <button class="btn danger">Logout</button></form>
                </div>
            </header>
            <main class="content">
                @if(session('success')) <div class="alert success">{{ session('success') }}</div> @endif
                @if(session('info')) <div class="alert info">{{ session('info') }}</div> @endif
                @if($errors->any())
                    <div class="alert error"><b>Ada yang perlu diperbaiki:</b><br>{{ implode(' ', $errors->all()) }}</div>
                @endif
                @yield('content')
            </main>
            <div class="footer-note">Aplikasi Ujian Sekolah | Semi-online | Backend Laravel</div>
        </section>
    </div>
@endguest
@stack('scripts')
<script>
document.addEventListener('click',e=>{if(window.innerWidth<1100&&!e.target.closest('.sidebar')&&!e.target.closest('.mobile-toggle'))document.body.classList.remove('menu-open')});
document.addEventListener('DOMContentLoaded',()=>{
  document.querySelectorAll('[data-live-search]').forEach(input=>{
    const id=input.getAttribute('data-live-search');
    const table=document.getElementById(id);
    if(!table) return;
    const rows=[...table.querySelectorAll('tbody tr')].filter(row=>!row.hasAttribute('data-empty-row'));
    const counter=document.querySelector(`[data-live-count="${id}"]`);
    const apply=()=>{
      const term=(input.value||'').trim().toLowerCase();
      let shown=0;
      rows.forEach(row=>{
        const ok=!term || row.textContent.toLowerCase().includes(term);
        row.style.display=ok?'':'none';
        if(ok) shown++;
      });
      if(counter) counter.textContent=shown;
    };
    input.addEventListener('input',apply);
    apply();
  });
  document.querySelectorAll('[data-live-reset]').forEach(btn=>{
    btn.addEventListener('click',()=>{
      const input=document.querySelector(`[data-live-search="${btn.getAttribute('data-live-reset')}"]`);
      if(input){input.value='';input.dispatchEvent(new Event('input'));input.focus();}
    });
  });
});
</script>
</body>
</html>
