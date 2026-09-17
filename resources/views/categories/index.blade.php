@extends('layouts.app')

@section('title', 'Project Category — Part Number Studio')

@section('content')

@if(! $company)
  <div class="card">
    <div class="empty">
      <p class="mb-2" style="color:var(--ink); font-weight:600">No companies yet</p>
      <p class="hint mb-3">Project categories are held per company. Add the first one to start.</p>
      <button class="btn btn-pn btn-sm" data-bs-toggle="modal" data-bs-target="#companyAddModal">Add company</button>
    </div>
  </div>
@else

<form method="post" action="{{ route('categories.saveAll', $company) }}" id="rowsForm">
@csrf @method('put')

<div class="d-flex flex-column gap-4">

  {{-- ================= the category table ================= --}}
  <div class="card">
    <div class="card-header">
      <span class="lbl">Project categories — {{ $company->name }}</span>
      <span class="hint">
        {{ $totalCount }} {{ $totalCount === 1 ? 'entry' : 'entries' }} · {{ $activeCount }} active
      </span>
      <div class="ms-auto d-flex gap-2">
        <button type="button" class="btn btn-outline-secondary btn-sm"
                data-bs-toggle="modal" data-bs-target="#bulkModal">Paste rows</button>
        <button type="submit" class="btn btn-outline-secondary btn-sm"
                formaction="{{ route('categories.store', $company) }}" formmethod="post"
                formnovalidate>+ Add row</button>
      </div>
    </div>

    <div class="table-responsive">
      <table class="table dtable align-middle">
        <thead>
          <tr>
            <th style="width:2rem"></th>
            <th style="width:130px">Code</th>
            <th style="width:26%">Name</th>
            <th>Description</th>
            <th style="width:4.5rem" class="text-center">Active</th>
            <th style="width:3rem"></th>
          </tr>
        </thead>
        <tbody id="catRows">
          @forelse($categories as $i => $cat)
            <tr class="{{ $cat->is_active ? '' : 'off' }}" data-id="{{ $cat->id }}">
              <td class="act"><span class="drag-handle mono" title="Drag to reorder">⣿</span></td>
              <td>
                <input type="hidden" name="rows[{{ $i }}][id]" value="{{ $cat->id }}">
                <input type="text" class="form-control mono cat-code" name="rows[{{ $i }}][code]"
                       value="{{ $cat->code }}" placeholder="CODE" maxlength="20"
                       aria-label="Code" data-upper>
              </td>
              <td>
                <input type="text" class="form-control" name="rows[{{ $i }}][name]"
                       value="{{ $cat->name }}" placeholder="Name" aria-label="Name">
              </td>
              <td>
                <input type="text" class="form-control" name="rows[{{ $i }}][note]"
                       value="{{ $cat->note }}" placeholder="Description" aria-label="Description">
              </td>
              <td class="act">
                {{-- Its own form, so ticking the box does not submit the edits --}}
                <input type="checkbox" class="form-check-input chk" data-toggle-url="{{ route('categories.toggle', $cat) }}"
                       @checked($cat->is_active) aria-label="Active">
              </td>
              <td class="act">
                <button type="submit" class="btn btn-outline-danger btn-sm ico"
                        form="delRow{{ $cat->id }}" aria-label="Remove row"
                        data-confirm="Remove {{ $cat->code ?: 'this row' }}?"
                        data-confirm-text="Numbers already issued with this code stay as they are, but the code can no longer be chosen. Switching the row off instead keeps it on record."
                        data-confirm-button="Remove">✕</button>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="6" class="empty">
                No categories yet — add a row, or paste them in from a spreadsheet
              </td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>

    @if($dupes || $blank)
      <div class="card-body" style="padding-top:0">
        <div class="d-flex flex-wrap align-items-center" style="gap:8px">
          @if($dupes)
            <span class="pill warn">duplicate code {{ implode(', ', $dupes) }}</span>
          @endif
          @if($blank)
            <span class="pill warn">{{ $blank }} row{{ $blank > 1 ? 's' : '' }} without a code</span>
          @endif
          <span class="hint">Rows without a code, and inactive rows, are left out of the pick lists.</span>
        </div>
      </div>
    @endif

    @if($categories->isNotEmpty())
      <div class="legend">
        <span>Drag a row to reorder — this is the order codes are offered in</span>
        <button type="submit" class="btn btn-pn btn-sm ms-auto">Save changes</button>
      </div>
    @endif
  </div>

  {{-- ================= where this table is used ================= --}}
  <div class="card">
    <div class="card-header">
      <span class="lbl">Where this table is used</span>
      <span class="hint ms-auto">{{ $mapped->count() }} segment{{ $mapped->count() === 1 ? '' : 's' }}</span>
    </div>
    <div class="card-body d-flex flex-column gap-3">
      @if($mapped->isNotEmpty())
        <div class="d-flex flex-wrap" style="gap:6px">
          @foreach($mapped as $m)
            <a class="btn btn-outline-secondary btn-sm"
               href="{{ route('templates.index', ['company' => $company->id, 'template' => $m['template']->id]) }}">
              {{ $m['template']->name }} → {{ $m['segment']->label }}
            </a>
          @endforeach
        </div>
      @else
        <p class="hint mb-0">
          No template reads from this table yet. Open a template and add a
          <strong>Free text</strong> segment sized to fit a category code.
        </p>
      @endif
      <p class="hint mb-0">
        Codes here are the vocabulary your part numbers draw on — rename a category and
        every document that refers to it follows. Codes already stamped on existing parts
        do not change, so retire a code by switching it off rather than deleting the row.
      </p>
    </div>
  </div>

</div>
</form>

{{-- One form per row for removal, and one shared for the Active toggle.
     Both sit outside the edit form so neither carries pending row edits. --}}
@foreach($categories as $cat)
  <form method="post" id="delRow{{ $cat->id }}" action="{{ route('categories.destroy', $cat) }}" class="d-none">
    @csrf @method('delete')
  </form>
@endforeach
<form method="post" id="toggleForm" class="d-none">@csrf @method('patch')</form>

{{-- ================= paste rows ================= --}}
<div class="modal fade" id="bulkModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <form class="modal-content" method="post" action="{{ route('categories.bulk', $company) }}">
      @csrf
      <div class="modal-header">
        <h5 class="modal-title">Paste rows</h5>
        <button type="button" class="btn-close p-2" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <label for="bulkRows" class="form-label lbl">One category per line</label>
        <textarea class="form-control mono" id="bulkRows" name="rows" rows="9" required
placeholder="MTR | Motor | Drive motors and sub-assemblies
BRG | Bearing | Ball and roller bearings
SHF | Shaft"></textarea>
        <div class="form-text mt-2">
          Written as <span class="mono">CODE | Name | Description</span> — columns split on
          <span class="mono">|</span> <span class="mono">,</span> or a tab, so a paste
          straight out of a spreadsheet works. Name and description are optional, and
          codes that already exist are skipped.
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
        <button class="btn btn-pn">Add</button>
      </div>
    </form>
  </div>
</div>

@endif

@endsection

@push('scripts')
<script>
"use strict";

/* Codes read better uppercase, and the server stores them that way anyway */
document.querySelectorAll("[data-upper]").forEach(function (input) {
  input.addEventListener("input", function () {
    var pos = input.selectionStart;
    input.value = input.value.toUpperCase();
    try { input.setSelectionRange(pos, pos); } catch (e) { /* not supported here */ }
  });
});

/* Ticking Active posts on its own, so pending row edits are left alone */
document.querySelectorAll("[data-toggle-url]").forEach(function (box) {
  box.addEventListener("change", function () {
    var f = document.getElementById("toggleForm");
    f.action = box.dataset.toggleUrl;
    f.submit();
  });
});

/* ---------- drag to reorder ---------- */
(function () {
  var tbody = document.getElementById("catRows");
  if (!tbody) return;

  var dragging = null;

  tbody.querySelectorAll("tr[data-id]").forEach(function (tr) {
    var handle = tr.querySelector(".drag-handle");
    if (!handle) return;

    /* Only a pull on the handle starts a drag, so text stays selectable */
    handle.addEventListener("mousedown", function () { tr.draggable = true; });
    tr.addEventListener("dragend", function () { tr.draggable = false; });

    tr.addEventListener("dragstart", function (e) {
      dragging = tr;
      tr.classList.add("dragging");
      e.dataTransfer.effectAllowed = "move";
      /* Firefox needs at least one payload */
      e.dataTransfer.setData("text/plain", tr.dataset.id);
    });

    tr.addEventListener("dragover", function (e) {
      e.preventDefault();
      if (!dragging || dragging === tr) return;
      var box = tr.getBoundingClientRect();
      var after = e.clientY > box.top + box.height / 2;
      tbody.insertBefore(dragging, after ? tr.nextSibling : tr);
    });

    tr.addEventListener("drop", function (e) { e.preventDefault(); });
  });

  tbody.addEventListener("dragend", function () {
    if (!dragging) return;
    dragging.classList.remove("dragging");
    dragging = null;

    /* Post the new order straight away — the row edits keep their own form */
    var f = document.createElement("form");
    f.method = "post";
    f.action = @json(route('categories.reorder', $company ?: 0));
    f.className = "d-none";

    var token = document.createElement("input");
    token.type = "hidden"; token.name = "_token";
    token.value = document.querySelector('meta[name="csrf-token"]').content;
    f.appendChild(token);

    tbody.querySelectorAll("tr[data-id]").forEach(function (tr) {
      var i = document.createElement("input");
      i.type = "hidden"; i.name = "order[]"; i.value = tr.dataset.id;
      f.appendChild(i);
    });

    document.body.appendChild(f);
    f.submit();
  });
})();
</script>
@endpush
