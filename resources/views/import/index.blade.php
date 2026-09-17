@extends('layouts.app')

@section('title', 'Import & Match — Part Number Studio')

@section('content')

@if(! $company)
  <div class="card">
    <div class="empty">
      <p class="mb-2" style="color:var(--ink); font-weight:600">No companies yet</p>
      <p class="hint mb-3">Add a company and a template before matching existing numbers.</p>
      <button class="btn btn-pn btn-sm" data-bs-toggle="modal" data-bs-target="#companyAddModal">Add company</button>
    </div>
  </div>
@else

<div class="d-flex flex-column gap-4">

  {{-- ================= where the numbers come from ================= --}}
  <div class="card">
    <div class="card-header">
      <span class="lbl">Import part numbers</span>
      <span class="hint ms-auto">
        Matched against all {{ $templates->count() }}
        template{{ $templates->count() === 1 ? '' : 's' }} of {{ $company->name }}
      </span>
    </div>

    <div class="card-body d-flex flex-column gap-3">
      {{-- upload an Excel file or a CSV --}}
      <form method="post" action="{{ route('import.upload') }}" enctype="multipart/form-data"
            class="d-flex flex-wrap align-items-end gap-2">
        @csrf
        <input type="hidden" name="company" value="{{ $company->id }}">
        <label class="d-flex flex-column flex-grow-1" style="gap:5px; min-width:240px">
          <span class="lbl">Excel or CSV file</span>
          <input type="file" name="file" class="form-control" required
                 accept=".xlsx,.xlsm,.xls,.csv,.txt,.ods">
        </label>
        <button class="btn btn-pn">Read file</button>
      </form>
      <p class="hint mb-0">
        The first sheet is read. After uploading you choose which column holds the
        part numbers — nothing in the file is changed or saved.
        Up to {{ number_format(\App\Support\SpreadsheetReader::MAX_ROWS) }} rows.
      </p>

      {{-- or paste them --}}
      <hr style="border-color:var(--line); margin:2px 0">

      <form method="get" action="{{ route('import.index') }}" class="d-flex flex-column gap-3">
        <input type="hidden" name="company" value="{{ $company->id }}">
        <label class="d-flex flex-column" style="gap:5px">
          <span class="lbl">Or paste the numbers — one per line, or separated by commas</span>
          <textarea class="form-control mono" name="codes" rows="5"
                    placeholder="{{ $templates->isNotEmpty() ? \App\Support\PartNumberFormat::sample($templates->first()) : 'AB-0001' }}">{{ $blob }}</textarea>
        </label>
        <div class="d-flex flex-wrap" style="gap:8px">
          <button class="btn btn-pn btn-sm">Match</button>
          <a class="btn btn-outline-secondary btn-sm"
             href="{{ route('import.sample', $company) }}">Load sample numbers</a>
          @if(trim($blob) !== '' || $sheet)
            <a class="btn btn-outline-secondary btn-sm"
               href="{{ route('import.index', ['company' => $company->id]) }}">Clear</a>
          @endif
        </div>
      </form>
    </div>
  </div>

  {{-- ================= the uploaded sheet: pick the code column ================= --}}
  @if($sheet)
    @php
      $preview = $firstRowIsHeader ? array_slice($sheet['rows'], 1, 5) : array_slice($sheet['rows'], 0, 5);
      $dataRows = count($sheet['rows']) - ($firstRowIsHeader ? 1 : 0);
      $width = max(array_map('count', $sheet['rows']));
    @endphp

    <div class="card">
      <div class="card-header">
        <span class="lbl">{{ $sheet['name'] }}</span>
        <span class="hint">
          sheet "{{ $sheet['sheet'] }}" · {{ number_format(max($dataRows, 0)) }} data rows · {{ $width }} columns
        </span>
        <form method="post" action="{{ route('import.discard') }}" class="ms-auto">
          @csrf
          <input type="hidden" name="company" value="{{ $company->id }}">
          <input type="hidden" name="sheet" value="{{ $sheetKey }}">
          <button class="btn btn-outline-secondary btn-sm">Discard file</button>
        </form>
      </div>

      @if($sheet['truncated'])
        <div class="card-body" style="padding-bottom:0">
          <span class="pill warn">only the first {{ number_format(\App\Support\SpreadsheetReader::MAX_ROWS) }} rows were read</span>
        </div>
      @endif

      <form method="get" action="{{ route('import.index') }}" class="card-body d-flex flex-column gap-3">
        <input type="hidden" name="company" value="{{ $company->id }}">
        <input type="hidden" name="sheet" value="{{ $sheetKey }}">

        <div class="d-flex flex-wrap align-items-end gap-3">
          <label class="d-flex flex-column" style="gap:5px">
            <span class="lbl">Column holding the part number</span>
            <select name="column" class="form-select" onchange="this.form.submit()">
              @for($c = 0; $c < $width; $c++)
                <option value="{{ $c }}" @selected($codeColumn === $c)>
                  {{ \App\Support\SpreadsheetReader::columnLabel($c, $sheet['headers'], $firstRowIsHeader) }}
                </option>
              @endfor
            </select>
          </label>

          <div class="form-check mb-2">
            {{-- An unticked box sends nothing, so a 0 goes along to carry the "off" state --}}
            <input type="hidden" name="header" value="0">
            <input class="form-check-input" type="checkbox" name="header" value="1" id="hdr"
                   @checked($firstRowIsHeader) onchange="this.form.submit()">
            <label class="form-check-label" for="hdr">First row is a header</label>
          </div>

          <button class="btn btn-pn ms-auto">Match this column</button>
        </div>

        {{-- a peek at the file, with the chosen column marked --}}
        <div class="table-responsive">
          <table class="table">
            <thead>
              <tr>
                @for($c = 0; $c < $width; $c++)
                  <th @if($codeColumn === $c) style="color:var(--accent)" @endif>
                    {{ \App\Support\SpreadsheetReader::columnLabel($c, $sheet['headers'], $firstRowIsHeader) }}
                    @if($codeColumn === $c)<span class="mono">←</span>@endif
                  </th>
                @endfor
              </tr>
            </thead>
            <tbody>
              @foreach($preview as $row)
                <tr>
                  @for($c = 0; $c < $width; $c++)
                    <td class="{{ $codeColumn === $c ? 'mono fw-semibold' : '' }}"
                        @if($codeColumn !== $c) style="color:var(--muted)" @endif>
                      {{ $row[$c] ?? '' }}
                    </td>
                  @endfor
                </tr>
              @endforeach
            </tbody>
          </table>
        </div>
        <p class="hint mb-0">First {{ count($preview) }} rows shown as a check that the right column is picked.</p>
      </form>
    </div>
  @endif

  @if($results)
    {{-- ================= result ================= --}}
    <div class="card">
      <div class="card-header">
        <span class="lbl">Result</span>
        <span class="hint ms-auto">
          @if($byTemplate)
            @foreach($byTemplate as $name => $n)
              {{ $name }} · {{ $n }}@if(! $loop->last) &nbsp;|&nbsp; @endif
            @endforeach
          @else
            nothing identified
          @endif
        </span>
      </div>

      <div class="card-body">
        {{-- Each count filters the table below to just those rows --}}
        <div class="tally" data-filter-group>
          <button type="button" data-filter="matched" aria-pressed="false"
                  @disabled($tally['matched'] === 0)>
            <span class="n ok">{{ $tally['matched'] }}</span><span class="u">matched</span>
          </button>
          <button type="button" data-filter="ambiguous" aria-pressed="false"
                  @disabled($tally['ambiguous'] === 0)>
            <span class="n warn">{{ $tally['ambiguous'] }}</span><span class="u">ambiguous</span>
          </button>
          <button type="button" data-filter="none" aria-pressed="false"
                  @disabled($tally['none'] === 0)>
            <span class="n dim">{{ $tally['none'] }}</span><span class="u">no match</span>
          </button>
          <span class="filternote" data-filter-note>Press a count to show only those numbers</span>
        </div>
      </div>

      <div class="table-responsive">
        <table class="table">
          <thead>
            <tr><th>Number</th><th style="width:7rem">Result</th><th style="width:22%">Template</th><th>Decoded</th></tr>
          </thead>
          <tbody>
            @foreach($results as $r)
              <tr class="{{ $r['status'] === 'none' ? 'r-none' : '' }}" data-status="{{ $r['status'] }}">
                <td class="mono fw-semibold text-nowrap">{{ $r['code'] }}</td>
                <td>
                  @if($r['status'] === 'matched')
                    <span class="pill ok">matched</span>
                  @elseif($r['status'] === 'ambiguous')
                    <span class="pill warn">ambiguous</span>
                  @else
                    <span class="pill">no match</span>
                  @endif
                </td>
                <td>
                  @if($r['status'] === 'ambiguous')
                    @foreach($r['tied'] as $t)
                      {{ $t['template']->name }}@if(! $loop->last)<br>@endif
                    @endforeach
                  @elseif($r['hit'])
                    {{ $r['hit']['template']->name }}
                  @else
                    —
                  @endif
                </td>
                <td>
                  @if($r['hit'])
                    @if($r['status'] === 'ambiguous')
                      <div class="hint mb-1">
                        More than one template accepts this number — the shapes overlap.
                      </div>
                    @endif
                    <div class="d-flex flex-wrap" style="gap:5px">
                      @foreach($r['hit']['decoded'] as $d)
                        <span class="pill flat">
                          {{ $d['label'] }}: <span class="mono">{{ $d['value'] }}</span>
                          <span style="color:var(--muted)">· {{ $d['note'] }}</span>
                        </span>
                      @endforeach
                    </div>
                  @else
                    <span class="hint">Matches none of the templates in this company</span>
                  @endif
                </td>
              </tr>
            @endforeach
            {{-- Only ever shown while a filter hides every row --}}
            <tr data-empty hidden>
              <td colspan="4"><div class="empty">No numbers with that result</div></td>
            </tr>
          </tbody>
        </table>
      </div>

      <div class="card-body" style="padding-top:12px">
        <p class="hint mb-0">
          A number that matches nothing is either from a scheme not yet described here,
          or it was issued by hand outside the rules. An <strong>ambiguous</strong> one is
          worth fixing in the designer: two templates that accept the same string cannot
          be told apart later.
        </p>
      </div>
    </div>
  @elseif(trim($blob) !== '' && ! $sheet)
    <div class="card"><div class="empty">Nothing to match — every line was blank.</div></div>
  @endif

</div>

@endif

@endsection

@push('scripts')
<script>
"use strict";

/* The result counts double as filters over the table below. Pressing one shows
   only those rows; pressing it again, or the one already pressed, shows all. */
(function () {
  var group = document.querySelector("[data-filter-group]");
  if (!group) return;

  var table = document.querySelector("[data-status]") &&
              document.querySelector("[data-status]").closest("table");
  if (!table) return;

  var rows = Array.prototype.slice.call(table.querySelectorAll("tr[data-status]"));
  var emptyRow = table.querySelector("[data-empty]");
  var note = group.querySelector("[data-filter-note]");
  var buttons = Array.prototype.slice.call(group.querySelectorAll("[data-filter]"));
  var active = null;

  function apply() {
    var shown = 0;

    rows.forEach(function (row) {
      var show = active === null || row.dataset.status === active;
      row.hidden = !show;
      if (show) shown++;
    });

    buttons.forEach(function (b) {
      b.setAttribute("aria-pressed", String(b.dataset.filter === active));
    });

    if (emptyRow) emptyRow.hidden = shown !== 0;

    if (note) {
      note.textContent = active === null
        ? "Press a count to show only those numbers"
        : "Showing " + shown + " of " + rows.length + " — press again to show all";
    }
  }

  group.addEventListener("click", function (e) {
    var btn = e.target.closest("[data-filter]");
    if (!btn || btn.disabled) return;

    /* Pressing the active filter clears it, so the counts toggle */
    active = active === btn.dataset.filter ? null : btn.dataset.filter;
    apply();
  });

  apply();
})();

</script>
@endpush
