<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Ujian Sekolah' }} · Backend Ujian</title>
    <link rel="icon" type="image/x-icon" href="{{ asset('assets/img/favicon/favicon.ico') }}">
    <style>
        :root{--bg:#f5f5f9;--card:#fff;--text:#384551;--heading:#2b2c40;--muted:#697a8d;--primary:#696cff;--primary-soft:#eef0ff;--success:#71dd37;--success-soft:#e8fadf;--danger:#ff3e1d;--danger-soft:#ffe0db;--warning:#ffab00;--warning-soft:#fff2d6;--info:#03c3ec;--info-soft:#d7f5fc;--line:#d9dee3;--menu:#fff;--shadow:0 .1875rem .75rem 0 rgba(67,89,113,.14);--radius:.75rem;}
        *{box-sizing:border-box} body{margin:0;font-family:"Public Sans",Inter,system-ui,-apple-system,"Segoe UI",Roboto,Arial,sans-serif;background:var(--bg);color:var(--text);font-size:15px} a{color:inherit;text-decoration:none} h1,h2,h3,h4{color:var(--heading);letter-spacing:-.025em;margin-top:0} h1{font-size:30px} h2{font-size:22px} h3{font-size:18px}.mb{margin-bottom:1.25rem}.mb0{margin-bottom:0}.mt{margin-top:1.25rem}.small{font-size:13px}.tiny{font-size:12px}.muted{color:var(--muted)}
        .layout-wrapper{min-height:100vh;display:flex}.sidebar{width:270px;position:fixed;inset:0 auto 0 0;background:var(--menu);box-shadow:var(--shadow);z-index:20;transition:.2s}.brand{height:72px;display:flex;align-items:center;gap:12px;padding:0 24px;font-weight:800;color:var(--heading);font-size:20px}.brand-logo{width:38px;height:38px;border-radius:12px;background:linear-gradient(135deg,var(--primary),#8b5cf6);display:grid;place-items:center;color:#fff;font-weight:900}.menu{padding:8px 14px 24px}.menu-header{font-size:11px;text-transform:uppercase;color:#a1acb8;letter-spacing:.06em;font-weight:800;margin:18px 12px 8px}.menu-link{display:flex;align-items:center;gap:12px;padding:11px 14px;border-radius:.55rem;color:#697a8d;margin:3px 0;font-weight:700}.menu-link:hover{background:#f6f6f7;color:var(--heading)}.menu-link.active{background:var(--primary);color:#fff;box-shadow:0 .125rem .375rem rgba(105,108,255,.35)}.menu-ico{width:24px;text-align:center}.layout-page{margin-left:270px;min-height:100vh;width:calc(100% - 270px);display:flex;flex-direction:column}.navbar{height:72px;position:sticky;top:0;z-index:10;margin:18px 26px 0;background:rgba(255,255,255,.92);backdrop-filter:blur(12px);border-radius:var(--radius);box-shadow:var(--shadow);display:flex;align-items:center;justify-content:space-between;padding:0 20px}.content{padding:26px;max-width:1440px;width:100%;margin:0 auto}.user-chip{display:flex;align-items:center;gap:10px}.avatar{width:38px;height:38px;border-radius:999px;object-fit:cover;background:var(--primary-soft)}.mobile-toggle{display:none;border:0;background:var(--primary-soft);color:var(--primary);border-radius:.5rem;padding:9px 12px;font-weight:900}.card{background:var(--card);border:0;border-radius:var(--radius);box-shadow:var(--shadow);padding:1.5rem}.hero{background:linear-gradient(135deg,#fff 0%,#f0f1ff 100%);border-radius:var(--radius);box-shadow:var(--shadow);padding:1.65rem}.grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:1.25rem}.two{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:1.25rem}.three{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:1.25rem}.stat{font-size:34px;font-weight:900;color:var(--heading);letter-spacing:-.05em}.between{display:flex;justify-content:space-between;gap:1rem;align-items:center}.row{display:flex;align-items:center;gap:.65rem;flex-wrap:wrap}.btn{border:0;border-radius:.5rem;padding:.72rem 1rem;background:#f5f5f9;color:var(--heading);font-weight:800;cursor:pointer;display:inline-flex;align-items:center;justify-content:center;gap:.4rem;line-height:1;box-shadow:none}.btn:hover{filter:brightness(.98)}.btn.primary{background:var(--primary);color:#fff}.btn.soft{background:var(--primary-soft);color:var(--primary)}.btn.green{background:var(--success-soft);color:#2b8a0e}.btn.danger{background:var(--danger-soft);color:#b42318}.btn.warning{background:var(--warning-soft);color:#946200}.btn.ghost{background:transparent;border:1px dashed var(--line)}.badge,.pill{display:inline-flex;align-items:center;gap:.35rem;border-radius:999px;padding:.35rem .65rem;font-size:12px;font-weight:800;background:var(--primary-soft);color:var(--primary);white-space:nowrap}.badge.published,.badge.submitted,.badge.active,.badge.success{background:var(--success-soft);color:#2b8a0e}.badge.ready{background:var(--info-soft);color:#0787a1}.badge.archived{background:#ebeef0;color:#566a7f}.badge.closed,.badge.inactive,.badge.danger{background:var(--danger-soft);color:#b42318}.badge.draft,.badge.assigned{background:#ebeef0;color:#566a7f}.badge.download_ready,.badge.downloaded,.badge.unlocked{background:var(--info-soft);color:#0787a1}.badge.downloading,.badge.in_progress,.badge.synced,.badge.locked,.badge.warning{background:var(--warning-soft);color:#946200}.badge.info{background:var(--info-soft);color:#0787a1}.table-wrap{width:100%;overflow:auto}.table{width:100%;border-collapse:collapse}.table th{font-size:12px;text-transform:uppercase;letter-spacing:.04em;color:#566a7f;background:#f6f7f9;text-align:left;padding:.85rem 1rem;white-space:nowrap}.table td{padding:1rem;border-top:1px solid var(--line);vertical-align:top;background:#fff}.table tr:hover td{background:#fafafa}.form{display:grid;gap:1rem}.field label{display:block;font-weight:800;color:var(--heading);margin-bottom:.45rem}.input,textarea,select{width:100%;border:1px solid var(--line);border-radius:.5rem;padding:.72rem .9rem;background:#fff;color:var(--text);font:inherit}textarea{resize:vertical}.input:focus,textarea:focus,select:focus{outline:0;border-color:var(--primary);box-shadow:0 0 0 .2rem rgba(105,108,255,.16)}input:disabled,textarea:disabled,select:disabled,button:disabled{opacity:.7;cursor:not-allowed}.help{font-size:13px;color:var(--muted);margin:.35rem 0 0}.alert{padding:1rem 1.15rem;border-radius:var(--radius);margin-bottom:1rem;font-weight:700}.alert.success{background:var(--success-soft);color:#2b8a0e}.alert.error{background:var(--danger-soft);color:#b42318}.alert.info{background:var(--info-soft);color:#0787a1}.alert.warning{background:var(--warning-soft);color:#946200}.code{white-space:pre-wrap;background:#232333;color:#e8e8f3;padding:1rem;border-radius:.75rem;overflow:auto;font-size:13px}.searchbar{display:flex;gap:.65rem}.searchbar .input{max-width:360px}.class-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:1rem}.mini-card{border:1px solid var(--line);border-radius:var(--radius);padding:1rem;background:#fff}.check-list{display:grid;gap:.45rem;margin-top:.8rem}.check-pill{display:flex;align-items:center;gap:.5rem;padding:.55rem .65rem;border-radius:.55rem;background:#f6f7f9;font-weight:700}.check-pill input{width:auto}.q-card{border:1px solid #eef0f4}.q-card.active{box-shadow:0 .25rem 1rem rgba(105,108,255,.16)}.type-note{padding:.75rem .9rem;border-radius:.65rem;background:var(--info-soft);color:#0787a1}.options-list,.matching-list{display:grid;gap:.65rem}.option-row,.matching-row{display:grid;grid-template-columns:auto minmax(0,1fr) auto auto;gap:.6rem;align-items:center;padding:.65rem;border:1px solid var(--line);border-radius:.65rem;background:#fff}.matching-row{grid-template-columns:minmax(0,1fr) minmax(0,1fr) auto}.split-note{display:grid;grid-template-columns:1fr 1fr;gap:.65rem;color:var(--muted);font-size:13px}.tf-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:.75rem}.tf-card{border:1px solid var(--line);border-radius:.75rem;padding:1rem;background:#fff;font-weight:800}.tf-card:has(input:checked){border-color:var(--primary);background:var(--primary-soft);color:var(--primary)}.mt-sm{margin-top:.35rem}.mb-sm{margin-bottom:.35rem} /* Pagination custom: mencegah ikon panah SVG/Tailwind default membesar dan berantakan */.pagination,.pagination nav{margin-top:1rem}.pagination svg,.us-pagination svg{width:16px!important;height:16px!important;max-width:16px!important;max-height:16px!important}.us-pagination{width:100%;font-size:13px;color:var(--muted)}.us-pagination-desktop{display:flex;align-items:center;justify-content:space-between;gap:1rem;flex-wrap:wrap}.us-pagination-mobile{display:none;align-items:center;justify-content:space-between;gap:.65rem;flex-wrap:wrap}.us-page-info{white-space:nowrap}.us-page-list{display:inline-flex;align-items:center;gap:.35rem;flex-wrap:wrap}.us-page-square,.us-page-btn{min-width:34px;height:34px;border:1px solid var(--line);border-radius:.5rem;background:#fff;color:var(--heading);display:inline-flex;align-items:center;justify-content:center;font-size:13px;font-weight:900;line-height:1;padding:0 .55rem;text-decoration:none}.us-page-btn{width:auto;min-width:112px}.us-page-square:hover,.us-page-btn:hover{background:var(--primary-soft);border-color:#cfd2ff;color:var(--primary)}.us-page-square.active{background:var(--primary);border-color:var(--primary);color:#fff;box-shadow:0 .125rem .375rem rgba(105,108,255,.28)}.us-page-square.disabled,.us-page-btn.disabled,.us-page-square.dots{background:#f6f7f9;color:#a1acb8;cursor:not-allowed;border-color:#ebeef0}.us-page-summary{font-weight:800;color:var(--muted);white-space:nowrap}.data-card{padding:0;overflow:hidden}.table-toolbar{display:flex;align-items:flex-end;justify-content:space-between;gap:1rem;padding:1.25rem 1.5rem;border-bottom:1px solid var(--line);background:linear-gradient(180deg,#fff 0%,#fbfbff 100%)}.table-title h2{margin:0}.table-tools{display:flex;align-items:end;gap:.65rem;flex-wrap:wrap;justify-content:flex-end}.tool-field{min-width:180px}.tool-field label{display:block;font-size:11px;text-transform:uppercase;letter-spacing:.05em;font-weight:900;color:#a1acb8;margin-bottom:.35rem}.tool-field.search{min-width:280px}.live-search-wrap{position:relative}.live-search-wrap:before{content:'⌕';position:absolute;left:.85rem;top:50%;transform:translateY(-50%);color:#a1acb8;font-weight:900}.live-search-wrap .input{padding-left:2.15rem}.table-meta{display:flex;align-items:center;gap:.45rem;flex-wrap:wrap;padding:.85rem 1.5rem;border-top:1px solid var(--line);background:#fff}.table-actions{display:flex;gap:.5rem;flex-wrap:wrap}.import-hero{background:linear-gradient(135deg,#fff 0%,#eef0ff 55%,#fff 100%);border-radius:var(--radius);box-shadow:var(--shadow);padding:1.5rem}.import-layout{display:grid;grid-template-columns:minmax(0,1.25fr) minmax(280px,.75fr);gap:1.25rem;align-items:start}.step-list{display:grid;gap:.75rem}.step{display:flex;gap:.75rem;align-items:flex-start;padding:.85rem;border:1px solid var(--line);border-radius:.75rem;background:#fff}.step-no{min-width:30px;height:30px;border-radius:999px;background:var(--primary-soft);color:var(--primary);display:grid;place-items:center;font-weight:900}.import-format{background:#232333;color:#e8e8f3;border-radius:.75rem;padding:1rem;overflow:auto;font-size:13px}.import-toolbar{display:flex;align-items:center;justify-content:space-between;gap:1rem;margin-bottom:1rem}.kbd{border:1px solid var(--line);border-bottom-width:2px;border-radius:.35rem;padding:.1rem .35rem;background:#fff;font-size:12px;font-weight:900;color:var(--muted)}.auth-page{min-height:100vh;display:grid;place-items:center;padding:24px}.auth-card{width:100%;max-width:480px}.auth-logo{display:flex;align-items:center;justify-content:center;gap:10px;margin-bottom:18px;font-weight:900;font-size:24px;color:var(--heading)}.footer-note{padding:0 26px 20px;color:var(--muted);font-size:13px;text-align:center}
        @media(max-width:1100px){.grid{grid-template-columns:repeat(2,1fr)}.two,.three,.class-grid,.import-layout{grid-template-columns:1fr}.sidebar{transform:translateX(-105%)}body.menu-open .sidebar{transform:translateX(0)}.layout-page{margin-left:0;width:100%}.mobile-toggle{display:inline-flex}.navbar{margin:12px 14px 0}.content{padding:18px 14px}.between{align-items:flex-start;flex-direction:column}.searchbar,.table-toolbar,.table-tools{flex-direction:column;align-items:stretch}.searchbar .input,.tool-field,.tool-field.search{max-width:none;min-width:0}.table{font-size:13px}}@media(max-width:640px){.grid{grid-template-columns:1fr}.card,.hero,.import-hero{padding:1.1rem}.data-card{padding:0}.table-toolbar{padding:1rem}.user-chip .muted{display:none}}

        @media(max-width:760px){.us-pagination-desktop{display:none}.us-pagination-mobile{display:flex}.us-page-btn{min-width:104px;height:34px}.us-page-summary{width:100%;text-align:center;order:-1}}
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
                <a class="menu-link {{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}"><span class="menu-ico">📊</span>Dashboard</a>
                <a class="menu-link {{ request()->routeIs('guide') ? 'active' : '' }}" href="{{ route('guide') }}"><span class="menu-ico">📖</span>Panduan</a>
                @if(auth()->user()->isAdmin() || auth()->user()->isTeacher())
                    <a class="menu-link {{ request()->routeIs('exams.*') ? 'active' : '' }}" href="{{ route('exams.index') }}"><span class="menu-ico">📝</span>Ujian</a>
                    <a class="menu-link {{ request()->routeIs('question-bank.*') ? 'active' : '' }}" href="{{ route('question-bank.index') }}"><span class="menu-ico">🧩</span>Bank Soal</a>
                @endif
                @if(auth()->user()->isAdmin())
                    <div class="menu-header">Data Master</div>
                    <a class="menu-link {{ request()->routeIs('classrooms.*') ? 'active' : '' }}" href="{{ route('classrooms.index') }}"><span class="menu-ico">🏫</span>Kelas</a>
                    <a class="menu-link {{ request()->routeIs('students.*') ? 'active' : '' }}" href="{{ route('students.index') }}"><span class="menu-ico">🎓</span>Siswa</a>
                    <a class="menu-link {{ request()->routeIs('teachers.*') ? 'active' : '' }}" href="{{ route('teachers.index') }}"><span class="menu-ico">👨‍🏫</span>Guru</a>
                    <a class="menu-link {{ request()->routeIs('accounts.*') ? 'active' : '' }}" href="{{ route('accounts.index') }}"><span class="menu-ico">🔐</span>Akun</a>
                    <a class="menu-link {{ request()->routeIs('silap.*') ? 'active' : '' }}" href="{{ route('silap.index') }}"><span class="menu-ico">🔄</span>Sinkron SILAP</a>
                    <div class="menu-header">Produksi</div>
                    <a class="menu-link {{ request()->routeIs('settings.*') ? 'active' : '' }}" href="{{ route('settings.school.edit') }}"><span class="menu-ico">⚙️</span>Pengaturan</a>
                    <a class="menu-link {{ request()->routeIs('audit.*') ? 'active' : '' }}" href="{{ route('audit.index') }}"><span class="menu-ico">🧾</span>Audit Log</a>
                @endif
            </nav>
        </aside>
        <section class="layout-page">
            <header class="navbar">
                <button type="button" class="mobile-toggle" onclick="document.body.classList.toggle('menu-open')">☰</button>
                <div>
                    <div class="small muted">Backend Konfigurasi</div>
                    <b>{{ $title ?? 'Ujian Sekolah' }}</b>
                </div>
                <div class="user-chip">
                    <img class="avatar" src="{{ asset('assets/img/avatars/1.png') }}" alt="avatar">
                    <div>
                        <b>{{ auth()->user()->name }}</b><br><a class="muted small" href="{{ route('profile.password.edit') }}">{{ auth()->user()->role ?? 'admin' }} · ganti password</a>
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
            <div class="footer-note">Aplikasi Ujian Sekolah · Semi-online · Backend Laravel</div>
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
