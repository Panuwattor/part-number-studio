<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>@yield('title', 'Part Number Studio')</title>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=IBM+Plex+Mono:wght@400;500;600&family=IBM+Plex+Sans+Condensed:wght@500;600;700&family=IBM+Plex+Sans:wght@400;500;600;700&display=swap">

<style>
/* =====================================================================
   Palette and tone lifted from the prototype part-number-studio.html,
   mapped onto Bootstrap variables so its components inherit the look
   ===================================================================== */
:root{
  --paper:#EDF1EF; --surface:#FFFFFF; --surface-2:#F6F9F7; --surface-3:#E7EDEA;
  --ink:#121A18; --ink-2:#33403D; --muted:#5F6D69;
  --line:#D2DAD7; --line-2:#BAC6C2;
  --accent:#0E7C66; --accent-2:#0A5B4B; --accent-soft:#DCEDE8; --on-accent:#FFFFFF;
  --warn:#8E6410; --warn-soft:#F6EDD9;
  --danger:#9E362C; --danger-soft:#F7E4E1;
  --shadow:0 1px 1px rgba(18,26,24,.04), 0 10px 26px -16px rgba(18,26,24,.30);
  color-scheme:light;
}
@media (prefers-color-scheme: dark){
  :root:not([data-theme="light"]){
    --paper:#0D1312; --surface:#151D1C; --surface-2:#1B2423; --surface-3:#222D2B;
    --ink:#E3EAE8; --ink-2:#C2CDCA; --muted:#8B9B97;
    --line:#2A3634; --line-2:#3A4844;
    --accent:#43C2A3; --accent-2:#6FD8BD; --accent-soft:#123029; --on-accent:#06201A;
    --warn:#D6A648; --warn-soft:#33290F;
    --danger:#E08076; --danger-soft:#391C18;
    --shadow:0 1px 1px rgba(0,0,0,.3), 0 12px 30px -18px rgba(0,0,0,.8);
    color-scheme:dark;
  }
}
:root[data-theme="dark"]{
  --paper:#0D1312; --surface:#151D1C; --surface-2:#1B2423; --surface-3:#222D2B;
  --ink:#E3EAE8; --ink-2:#C2CDCA; --muted:#8B9B97;
  --line:#2A3634; --line-2:#3A4844;
  --accent:#43C2A3; --accent-2:#6FD8BD; --accent-soft:#123029; --on-accent:#06201A;
  --warn:#D6A648; --warn-soft:#33290F;
  --danger:#E08076; --danger-soft:#391C18;
  --shadow:0 1px 1px rgba(0,0,0,.3), 0 12px 30px -18px rgba(0,0,0,.8);
  color-scheme:dark;
}

/* Feed the palette into Bootstrap */
:root{
  --bs-body-bg:var(--paper);
  --bs-body-color:var(--ink);
  --bs-emphasis-color:var(--ink);
  --bs-secondary-color:var(--muted);
  --bs-tertiary-color:var(--muted);
  --bs-border-color:var(--line);
  --bs-border-color-translucent:var(--line);
  --bs-border-radius:0; --bs-border-radius-sm:0; --bs-border-radius-lg:0;
  --bs-border-radius-xl:0; --bs-border-radius-2xl:0; --bs-border-radius-pill:0;
  --bs-primary:var(--accent);
  --bs-primary-rgb:14,124,102;
  --bs-link-color:var(--accent);
  --bs-link-hover-color:var(--accent-2);
  --bs-body-font-family:"IBM Plex Sans",system-ui,-apple-system,sans-serif;
  --bs-body-font-size:15px;
  --bs-body-line-height:1.55;
  --bs-focus-ring-color:rgba(14,124,102,.35);
}

*{box-sizing:border-box}
body{
  background:var(--paper); color:var(--ink);
  font-family:var(--bs-body-font-family);
  -webkit-font-smoothing:antialiased;
}
h1,h2,h3,h4,h5{font-weight:600; line-height:1.25; text-wrap:balance; margin:0}

.mono{font-family:"IBM Plex Mono",ui-monospace,SFMono-Regular,Menlo,monospace; font-variant-numeric:tabular-nums}

/* Uppercase, letter-spaced labels, as in the prototype */
.lbl{
  font-family:"IBM Plex Sans Condensed","IBM Plex Sans",sans-serif;
  text-transform:uppercase; letter-spacing:.12em; font-size:11px; font-weight:600; color:var(--muted);
}

/* ---------- top bar ---------- */
.topbar{
  display:flex; align-items:center; gap:14px; flex-wrap:wrap;
  padding:18px 0 16px; border-bottom:1px solid var(--line); margin-bottom:0;
}
.brand{display:flex; align-items:center; gap:10px; margin-right:auto}
.brand .mark{
  font-family:"IBM Plex Mono",monospace; font-weight:600; font-size:12px; letter-spacing:.04em;
  border:1px solid var(--line-2); padding:3px 7px; color:var(--accent);
}
.brand .nm{
  font-family:"IBM Plex Sans Condensed","IBM Plex Sans",sans-serif;
  font-size:19px; letter-spacing:.01em; font-weight:600; color:var(--ink);
}
.brand .sub{font-size:12.5px; color:var(--muted)}

/* Company picker: caption and select share one border */
.vendorbox{display:flex; align-items:stretch; border:1px solid var(--line-2); background:var(--surface)}
.vendorbox .cap{display:flex; align-items:center; padding:0 10px; border-right:1px solid var(--line); background:var(--surface-2)}
.vendorbox select{
  border:0; background:transparent; padding:8px 10px; min-width:190px; outline:none;
  color:var(--ink); font:inherit;
}
.vendorbox select:focus-visible{outline:2px solid var(--accent); outline-offset:-2px}

/* ---------- tabs ---------- */
.tabs{display:flex; gap:2px; flex-wrap:wrap; border-bottom:1px solid var(--line); margin-bottom:24px}
.tabs a{
  background:transparent; border:0; border-bottom:2px solid transparent;
  padding:12px 14px 10px; cursor:pointer; color:var(--muted); font-weight:500; font-size:14px;
  text-decoration:none; display:inline-flex; align-items:center; gap:6px;
}
.tabs a:hover{color:var(--ink)}
.tabs a[aria-current="page"]{color:var(--ink); border-bottom-color:var(--accent); font-weight:600}
.tabs a:focus-visible{outline:2px solid var(--accent); outline-offset:2px}
.tabs .count{font-family:"IBM Plex Mono",monospace; font-size:11.5px; color:var(--muted)}

/* ---------- cards: square corners, thin shadow, tinted header ---------- */
.card{
  background:var(--surface); border:1px solid var(--line);
  box-shadow:var(--shadow); border-radius:0;
}
.card-header{
  display:flex; align-items:center; gap:12px; flex-wrap:wrap;
  padding:11px 14px; border-bottom:1px solid var(--line);
  background:var(--surface-2); border-radius:0;
}
.card-body{padding:14px}
.card-footer{background:var(--surface-2); border-top:1px solid var(--line)}

/* ---------- buttons: square, no shadow ---------- */
.btn{
  border-radius:0; font-size:13.5px; font-weight:500; padding:7px 13px;
  transition:background .12s, border-color .12s;
}
.btn-sm{padding:4px 9px; font-size:12.5px}
.btn-outline-secondary{
  border-color:var(--line-2); background:var(--surface); color:var(--ink);
}
.btn-outline-secondary:hover,.btn-outline-secondary:focus{
  background:var(--surface-3); border-color:var(--muted); color:var(--ink);
}
.btn-pn{background:var(--accent); border:1px solid var(--accent); color:var(--on-accent)}
.btn-pn:hover,.btn-pn:focus{background:var(--accent-2); border-color:var(--accent-2); color:var(--on-accent)}
.btn-outline-danger{border-color:var(--line-2); background:var(--surface); color:var(--danger)}
.btn-outline-danger:hover,.btn-outline-danger:focus{background:var(--danger-soft); border-color:var(--danger); color:var(--danger)}
.btn-danger{background:var(--danger); border-color:var(--danger); color:#fff}
.btn-danger:hover,.btn-danger:focus{background:var(--danger); border-color:var(--danger); filter:brightness(.92); color:#fff}
.btn:focus-visible{outline:2px solid var(--accent); outline-offset:2px; box-shadow:none}
.btn-dgr{color:var(--danger)}
.btn-dgr:hover,.btn-dgr:focus{background:var(--danger-soft); border-color:var(--danger); color:var(--danger)}
.btn.ico{padding:4px 8px; font-family:"IBM Plex Mono",monospace; line-height:1}

/* ---------- inputs ---------- */
.form-control,.form-select{
  border:1px solid var(--line-2); background:var(--surface); color:var(--ink);
  border-radius:0; padding:7px 9px; font-size:14px;
}
.form-control:focus,.form-select:focus{
  border-color:var(--accent); box-shadow:inset 0 0 0 1px var(--accent);
  background:var(--surface); color:var(--ink);
}
.form-control::placeholder{color:var(--muted); opacity:.7}
.form-control-sm,.form-select-sm{padding:4px 7px; font-size:13px}
.form-text{font-size:12.5px; color:var(--muted); line-height:1.5}
.form-label{margin-bottom:4px}
.input-group>.form-control,.input-group>.btn{border-radius:0}
.form-check-input{border-radius:0; border-color:var(--line-2)}
.form-check-input:checked{background-color:var(--accent); border-color:var(--accent)}
.form-check-input:focus{border-color:var(--accent); box-shadow:0 0 0 .2rem rgba(14,124,102,.2)}

/* ---------- tables ---------- */
.table{
  --bs-table-bg:var(--surface);
  --bs-table-color:var(--ink);
  --bs-table-border-color:var(--line);
  --bs-table-hover-bg:var(--surface-2);
  --bs-table-hover-color:var(--ink);
  font-size:13px; margin-bottom:0;
}
.table>:not(caption)>*>*{padding:7px 9px; background-color:var(--bs-table-bg)}
.table thead th{
  font-family:"IBM Plex Sans Condensed","IBM Plex Sans",sans-serif;
  text-transform:uppercase; letter-spacing:.1em; font-size:10.5px;
  color:var(--muted); font-weight:600; white-space:nowrap;
  background:var(--surface); border-bottom:1px solid var(--line);
}
.table tbody tr:last-child>*{border-bottom-width:0}

/* ---------- status pills ---------- */
.pill{
  display:inline-flex; align-items:center; gap:6px; padding:2px 8px;
  font-size:11.5px; font-weight:600; border:1px solid var(--line-2);
  background:var(--surface-2); color:var(--muted);
  font-family:"IBM Plex Sans Condensed","IBM Plex Sans",sans-serif;
  letter-spacing:.06em; text-transform:uppercase;
}
.pill.ok{color:var(--accent); border-color:var(--accent); background:var(--accent-soft)}
.pill.warn{color:var(--warn); border-color:var(--warn); background:var(--warn-soft)}
.pill.bad{color:var(--danger); border-color:var(--danger); background:var(--surface)}
.pill.flat{
  text-transform:none; letter-spacing:0; font-weight:500;
  font-family:"IBM Plex Sans",sans-serif;
}

/* summary figures */
.tally{display:flex; gap:10px; flex-wrap:wrap; align-items:stretch}
.tally .n{font-family:"IBM Plex Mono",monospace; font-size:22px; font-weight:600; line-height:1}
.tally .n.ok{color:var(--accent)}
.tally .n.dim{color:var(--muted)}
.tally .u{font-size:12.5px; color:var(--muted); margin-left:6px}

/* Each tally is a filter over the rows below, so it reads and behaves as a button */
.tally button{
  display:flex; align-items:baseline; gap:0;
  border:1px solid var(--line); border-radius:8px;
  background:var(--surface); padding:9px 13px;
  font:inherit; text-align:left; cursor:pointer;
  transition:border-color .12s, background .12s;
}
.tally button:hover:not(:disabled){border-color:var(--accent); background:var(--accent-soft)}
.tally button:focus-visible{outline:2px solid var(--accent); outline-offset:2px}
/* A count of zero has nothing to show, so it is not offered */
.tally button:disabled{cursor:default; opacity:.5}
.tally button[aria-pressed="true"]{border-color:var(--accent); background:var(--accent-soft)}
.tally button[aria-pressed="true"] .u{color:var(--ink-2)}
/* Says what a press does, without spending a row on it until it is useful */
.tally .filternote{
  align-self:center; font-size:12.5px; color:var(--muted); margin-left:4px;
}

.hint{font-size:12.5px; color:var(--muted); line-height:1.5}
.empty{padding:28px 14px; text-align:center; color:var(--muted); font-size:14px}

/* ---------- left-hand template list ---------- */
.tplitem{
  display:block; width:100%; text-align:left;
  border:1px solid var(--line); border-left:3px solid transparent;
  background:var(--surface); padding:9px 11px; cursor:pointer;
  margin-bottom:-1px; text-decoration:none; color:var(--ink);
}
.tplitem:hover{background:var(--surface-2); color:var(--ink)}
.tplitem[aria-current="true"]{border-left-color:var(--accent); background:var(--surface-2)}
.tplitem:focus-visible{outline:2px solid var(--accent); outline-offset:-2px}
.tplitem .nm{font-weight:600; font-size:14px}
.tplitem .cd{font-size:11.5px; color:var(--muted)}

/* ---------- segment cards ---------- */
/* Adding a segment lands on it by fragment, so keep it clear of the viewport edge */
.seg{border:1px solid var(--line); background:var(--surface); margin-bottom:-1px; scroll-margin:16px}

/* The segment just added, so it is obvious which card is new */
.seg.isnew{animation:segnew 1.6s ease-out}
@keyframes segnew{
  0%, 55%{border-color:var(--accent); background:var(--accent-soft)}
  100%{border-color:var(--line); background:var(--surface)}
}
@media (prefers-reduced-motion:reduce){
  .seg.isnew{animation:none; border-color:var(--accent)}
}
.seg > .sh{
  display:flex; align-items:center; flex-wrap:wrap; gap:8px 10px;
  padding:8px 10px; background:var(--surface-2); border-bottom:1px solid var(--line);
}
.seg > .sb{padding:12px 10px}
.seg .idx{
  font-family:"IBM Plex Mono",monospace; font-size:11px; color:var(--muted);
  border:1px solid var(--line-2); padding:1px 5px;
}

/* the dashed separator row between two segments */
.conn{
  display:flex; align-items:center; gap:10px; flex-wrap:wrap;
  border:1px solid var(--line); border-top-style:dashed; border-bottom-style:dashed;
  background:var(--paper); padding:6px 10px; margin-bottom:-1px;
}
.conn .lbl{font-size:10px}
.conn .shown{font-family:"IBM Plex Mono",monospace; font-size:12px; color:var(--muted); margin-left:auto}
.conn .shown b{color:var(--accent); font-weight:600}

/* ---------- preview code box ---------- */
.previewcol{position:sticky; top:14px}
.codebox{
  padding:18px 14px; background:var(--surface-2); border-bottom:1px solid var(--line);
  display:flex; flex-wrap:wrap; align-items:center; justify-content:center;
  min-height:76px; overflow-x:auto;
}
.tok{
  font-family:"IBM Plex Mono",monospace; font-size:22px; font-weight:500; letter-spacing:.02em;
  padding:3px 3px 6px; border-bottom:2px solid transparent; white-space:pre;
}
.tok.t-fixed{border-bottom-color:var(--line-2)}
.tok.t-free{border-bottom-color:var(--accent)}
.tok.sep{color:var(--muted); font-weight:400; border-bottom-color:transparent; padding-left:1px; padding-right:1px}

.legend{display:flex; flex-wrap:wrap; gap:10px; padding:10px 14px; border-top:1px solid var(--line); font-size:11.5px; color:var(--muted)}
.legend span{display:inline-flex; align-items:center; gap:5px}
.legend i{width:14px; height:2px; display:inline-block}

/* ---------- inline-edit table ---------- */
.dtable td{padding:3px 6px; vertical-align:middle}
.dtable td.act{width:1%; white-space:nowrap; text-align:center}
tr.off td:not(.act){opacity:.6}
tr.off .cat-code{text-decoration:line-through}
.drag-handle{cursor:grab; color:var(--line-2)}
.drag-handle:hover{color:var(--muted)}
tr.dragging{opacity:.4}

/* ---------- modal / alert ---------- */
.modal-content{background:var(--surface); border:1px solid var(--line-2); border-radius:0; box-shadow:var(--shadow)}
.modal-header{padding:12px 16px; border-bottom:1px solid var(--line); background:var(--surface-2)}
.modal-body{padding:16px}
.modal-footer{padding:12px 16px; border-top:1px solid var(--line); background:var(--surface)}
.modal-title{font-size:16px}

.alert{border-radius:0; border:1px solid; font-size:13.5px; padding:10px 14px}
.alert-success{background:var(--accent-soft); border-color:var(--accent); color:var(--accent-2)}
.alert-danger{background:var(--danger-soft); border-color:var(--danger); color:var(--danger)}
.alert-warning{background:var(--warn-soft); border-color:var(--warn); color:var(--warn)}

.dropdown-menu{
  border-radius:0; border:1px solid var(--line-2); background:var(--surface);
  box-shadow:var(--shadow); font-size:13.5px; padding:4px 0;
}
.dropdown-item{color:var(--ink); padding:6px 12px}
.dropdown-item:hover,.dropdown-item:focus{background:var(--surface-3); color:var(--ink)}
.dropdown-item.text-danger:hover{background:var(--danger-soft)}
.dropdown-divider{border-top-color:var(--line)}

/* ---------- SweetAlert, wearing the same clothes as the rest ---------- */
.swal2-container{z-index:2000}
.pn-swal,.pn-toast{
  border-radius:0 !important; border:1px solid var(--line-2);
  background:var(--surface) !important; color:var(--ink) !important;
  box-shadow:var(--shadow); font-family:"IBM Plex Sans",system-ui,sans-serif;
}
.pn-swal .swal2-title{font-size:17px; font-weight:600; color:var(--ink)}
.pn-swal .swal2-html-container{font-size:13.5px; color:var(--ink-2); line-height:1.55}
.pn-swal .swal2-actions{gap:8px}
.pn-toast{padding:10px 14px !important}
.pn-toast .swal2-title{font-size:13.5px !important; font-weight:600; margin:0; color:var(--ink)}
.pn-toast .swal2-html-container{font-size:12.5px !important; color:var(--muted); margin:2px 0 0}
.pn-toast .swal2-icon{width:1.5em; height:1.5em; margin:0 10px 0 0; border-width:2px}
.pn-toast .swal2-icon .swal2-icon-content{font-size:1em}
.swal2-timer-progress-bar{background:var(--accent) !important}
.swal2-icon.swal2-success .swal2-success-ring{border-color:var(--accent)}
.swal2-icon.swal2-success [class^="swal2-success-line"]{background-color:var(--accent)}

.shell{max-width:1260px}

@media (prefers-reduced-motion: reduce){
  *{transition-duration:.01ms !important; animation-duration:.01ms !important}
}
@media (max-width:576px){
  .codebox .tok{font-size:17px}
  .brand .sub{display:none}
}
</style>
@stack('head')
</head>
<body>

<div class="container-xl shell px-3">

  <header class="topbar">
    <div class="brand">
      <span class="mark">PN</span>
      <div>
        <div class="nm">Part Number Studio</div>
        <div class="sub">Part number format designer for multiple companies</div>
      </div>
    </div>

    @isset($companies)
      @if($companies->isNotEmpty())
        <form method="get" action="{{ url()->current() }}" class="vendorbox">
          <span class="cap lbl">Company</span>
          <select name="company" aria-label="Select company" onchange="this.form.submit()">
            @foreach($companies as $c)
              <option value="{{ $c->id }}" @selected(isset($company) && $company && $company->id === $c->id)>
                {{ $c->name }}
              </option>
            @endforeach
          </select>
        </form>
      @endif
    @endisset

    <button class="btn btn-outline-secondary btn-sm" data-bs-toggle="modal" data-bs-target="#companyAddModal">New</button>
    @isset($company)
      @if($company)
        <button class="btn btn-outline-secondary btn-sm" data-bs-toggle="modal" data-bs-target="#companyEditModal">Rename</button>
        <form method="post" action="{{ route('companies.destroy', $company) }}">
          @csrf @method('delete')
          <button class="btn btn-outline-secondary btn-sm btn-dgr"
                  @disabled($companies->count() < 2)
                  data-confirm="Delete {{ $company->name }}?"
                  data-confirm-text="Removes the company along with its {{ $company->categories()->count() }} categories and {{ $company->templates()->count() }} templates. This cannot be undone."
                  data-confirm-button="Delete">Delete</button>
        </form>
      @endif
    @endisset
    <a class="btn btn-outline-secondary btn-sm" href="{{ route('export') }}"
       title="Download all companies and templates as JSON">Export</a>
  </header>

  <nav class="tabs">
    <a href="{{ route('templates.index', ['company' => $company->id ?? null]) }}"
       @if(request()->routeIs('templates.*') || request()->routeIs('segments.*')) aria-current="page" @endif>
      Templates
      @isset($templates)<span class="count">{{ $templates->count() ?: '' }}</span>@endisset
    </a>
    {{-- Project Category is built and its data is kept, but nothing reads it
         yet: no segment can draw its value from a category, so the page would
         promise a link that does not exist. Restore this tab once a segment
         can be bound to the category table. The routes stay live, so a saved
         URL still works.
    <a href="{{ route('categories.index', ['company' => $company->id ?? null]) }}"
       @if(request()->routeIs('categories.*')) aria-current="page" @endif>
      Project Category
      @isset($totalCount)<span class="count">{{ $totalCount ?: '' }}</span>@endisset
    </a>
    --}}
    <a href="{{ route('import.index', ['company' => $company->id ?? null]) }}"
       @if(request()->routeIs('import.*')) aria-current="page" @endif>
      Import &amp; Match
    </a>
  </nav>

  <main class="pb-5">
    @yield('content')
  </main>
</div>

@include('partials.company-modals')

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.14.5/dist/sweetalert2.all.min.js"></script>

<script>
"use strict";

/* ---------------------------------------------------------------
   Feedback lives in SweetAlert, not in banners that push the page
   down. A save or a failure is worth a word; adding a row is not.
   --------------------------------------------------------------- */

/* A quiet toast in the corner, for outcomes that need no decision */
window.pnToast = function (icon, title, text) {
  Swal.fire({
    toast: true,
    position: "bottom-end",
    icon: icon,
    title: title,
    text: text || undefined,
    showConfirmButton: false,
    timer: icon === "error" ? 6000 : 2600,
    timerProgressBar: true,
    customClass: { popup: "pn-toast" },
    didOpen: function (el) {
      el.addEventListener("mouseenter", Swal.stopTimer);
      el.addEventListener("mouseleave", Swal.resumeTimer);
    }
  });
};

/* A blocking dialog, for a destructive step that cannot be undone */
window.pnConfirm = function (opts) {
  return Swal.fire({
    title: opts.title,
    html: opts.html || undefined,
    icon: "warning",
    iconColor: "#9E362C",
    showCancelButton: true,
    confirmButtonText: opts.confirmText || "Delete",
    cancelButtonText: "Cancel",
    reverseButtons: true,
    focusCancel: true,
    buttonsStyling: false,
    customClass: {
      popup: "pn-swal",
      confirmButton: "btn btn-danger",
      cancelButton: "btn btn-outline-secondary"
    }
  });
};

/* Server-side outcomes, handed over from the session */
@if(session('status'))
  pnToast("success", @json(session('status')));
@endif

@if($errors->any())
  pnToast("error", "Check the details", @json(implode(' · ', $errors->all())));
@endif

/* Any button carrying data-confirm asks first, then submits its form */
document.addEventListener("click", function (e) {
  var btn = e.target.closest("[data-confirm]");
  if (!btn) return;

  /* A button may own its form through form="…" rather than by nesting —
     btn.form honours both, closest() would find the wrong one */
  var form = btn.form || btn.closest("form");
  if (!form) return;

  e.preventDefault();
  pnConfirm({
    title: btn.dataset.confirm,
    html: btn.dataset.confirmText,
    confirmText: btn.dataset.confirmButton
  }).then(function (r) {
    /* requestSubmit keeps the clicked button's formaction and name/value */
    if (r.isConfirmed) {
      if (form.requestSubmit) form.requestSubmit(btn);
      else form.submit();
    }
  });
});
</script>

@stack('scripts')
</body>
</html>
