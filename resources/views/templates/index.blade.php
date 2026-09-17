@extends('layouts.app')

@section('title', 'Templates — Part Number Studio')

@php use App\Support\PartNumberFormat as Fmt; @endphp

@section('content')

@if(! $company)
  <div class="card">
    <div class="empty">
      <p class="mb-2" style="color:var(--ink); font-weight:600">No companies yet</p>
      <p class="hint mb-3">Add a company before designing a number format.</p>
      <button class="btn btn-pn btn-sm" data-bs-toggle="modal" data-bs-target="#companyAddModal">Add company</button>
    </div>
  </div>
@else

<div class="row g-4">
  {{-- ================= template list ================= --}}
  <div class="col-lg-3">
    <div class="d-flex flex-column gap-2">
      <div class="lbl">Templates — {{ $company->name }}</div>
      <div>
        @forelse($templates as $t)
          <a class="tplitem" aria-current="{{ $template && $template->id === $t->id ? 'true' : 'false' }}"
             href="{{ route('templates.index', ['company' => $company->id, 'template' => $t->id]) }}">
            <div class="nm">{{ $t->name }}</div>
            <div class="cd mono">{{ $t->code ? $t->code.' · ' : '' }}{{ $t->segments->count() }} segments</div>
          </a>
        @empty
          <div class="hint">No templates yet</div>
        @endforelse
      </div>
      <button class="btn btn-outline-secondary btn-sm align-self-start"
              data-bs-toggle="modal" data-bs-target="#tplAddModal">+ New template</button>
      <p class="hint">
        A company can hold as many templates as it needs — production parts, raw
        materials and tooling often each want their own.
      </p>
    </div>
  </div>

  @if($template)
    {{-- ================= the editor ================= --}}
    <div class="col-lg-6">
      <div class="d-flex flex-column gap-4">

        {{-- template settings --}}
        <div class="card">
          <div class="card-header">
            <span class="lbl">Template settings</span>
            <div class="ms-auto d-flex gap-2">
              <form method="post" action="{{ route('templates.duplicate', $template) }}">
                @csrf
                <button class="btn btn-outline-secondary btn-sm">Duplicate</button>
              </form>
              @if($templates->count() > 1)
                <form method="post" action="{{ route('templates.destroy', $template) }}">
                  @csrf @method('delete')
                  <button class="btn btn-outline-danger btn-sm"
                          data-confirm="Delete {{ $template->name }}?"
                          data-confirm-text="Removes the template and its {{ $template->segments->count() }} segment{{ $template->segments->count() === 1 ? '' : 's' }}. This cannot be undone."
                          data-confirm-button="Delete">Delete</button>
                </form>
              @endif
            </div>
          </div>
          <form method="post" action="{{ route('templates.update', $template) }}" class="card-body">
            @csrf @method('put')
            <div class="row g-3">
              <div class="col-sm-6">
                <label for="tplName" class="form-label lbl">Template name</label>
                <input type="text" class="form-control" id="tplName" name="name" required value="{{ $template->name }}">
              </div>
              <div class="col-sm-6">
                <label for="tplCode" class="form-label lbl">Template code (optional)</label>
                <input type="text" class="form-control mono text-uppercase" id="tplCode" name="code"
                       maxlength="20" value="{{ $template->code }}" placeholder="none">
                <div class="form-text">Shorthand for the list on the left. It is a label, not part of the number.</div>
              </div>
              <div class="col-sm-6">
                <label for="tplSep" class="form-label lbl">Default separator</label>
                <div class="input-group input-group-sm">
                  <input type="text" class="form-control mono" id="tplSep" name="separator"
                         maxlength="4" value="{{ $template->separator }}" data-sep-input>
                  @foreach(Fmt::SEPARATOR_PRESETS as $preset)
                    <button type="button" class="btn btn-outline-secondary mono ico"
                            data-sep-set="{{ $preset }}" title="Use {{ $preset }}">{{ $preset }}</button>
                  @endforeach
                </div>
              </div>
              <div class="col-sm-6">
                <label for="tplNote" class="form-label lbl">Description</label>
                <input type="text" class="form-control" id="tplNote" name="note" value="{{ $template->note }}"
                       placeholder="What this template is used for">
              </div>
              <div class="col-12">
                <p class="hint mb-2">
                  Separators use hyphen and underscore only. Repeat a character for a wider
                  gap — <span class="mono">-</span> <span class="mono">--</span>
                  <span class="mono">_</span> <span class="mono">__</span>.
                  Every join below starts from this default and can override it, so one
                  number can mix them — <span class="mono">A-123_A</span>.
                </p>
              </div>
              <div class="col-12 d-flex align-items-center gap-3">
                <div class="form-check">
                  <input class="form-check-input" type="checkbox" id="tplActive" name="is_active" value="1"
                         @checked($template->is_active)>
                  <label class="form-check-label" for="tplActive">Active</label>
                </div>
                <button class="btn btn-pn btn-sm ms-auto">Save settings</button>
              </div>
            </div>
          </form>
        </div>

        {{-- code structure --}}
        <div class="card">
          <div class="card-header">
            <span class="lbl">Code structure</span>
            <span class="hint">{{ $template->segments->count() }} segments · left to right · this is what the number is made of</span>
            <form method="post" action="{{ route('segments.add', $template) }}" class="ms-auto d-flex gap-2">
              @csrf
              <button class="btn btn-outline-secondary btn-sm" name="type" value="fixed">+ Fixed text</button>
              <button class="btn btn-pn btn-sm" name="type" value="free">+ Free text</button>
            </form>
          </div>

          @if($template->segments->isEmpty())
            <div class="empty">No segments yet — add one to start</div>
          @else
            <form method="post" action="{{ route('segments.save', $template) }}" class="card-body">
              @csrf @method('put')

              @foreach($template->segments as $i => $seg)
                @php $own = Fmt::cleanSeparator($seg->separator); @endphp

                {{-- the separator between two segments --}}
                @if($i > 0)
                  <div class="conn">
                    <span class="lbl">Between {{ $template->segments[$i - 1]->label }} and {{ $seg->label }}</span>
                    <select name="segments[{{ $i }}][separator]" class="form-select form-select-sm mono" style="width:auto">
                      <option value="" @selected($own === '')>
                        default ({{ Fmt::cleanSeparator($template->separator) ?: Fmt::SEPARATOR_FALLBACK }})
                      </option>
                      @foreach(Fmt::SEPARATOR_PRESETS as $preset)
                        <option value="{{ $preset }}" @selected($own === $preset)>{{ $preset }}</option>
                      @endforeach
                    </select>
                    <span class="shown">…{{ mb_substr(Fmt::segmentMask($template->segments[$i - 1]), -3) }}<b>{{ Fmt::separatorBefore($template, $seg, $i) }}</b>{{ mb_substr(Fmt::segmentMask($seg), 0, 3) }}…</span>
                  </div>
                @endif

                {{-- one segment --}}
                <div class="seg" id="seg{{ $seg->id }}">
                  <div class="sh">
                    <span class="idx">{{ str_pad($i + 1, 2, '0', STR_PAD_LEFT) }}</span>

                    <input type="hidden" name="segments[{{ $i }}][id]" value="{{ $seg->id }}">

                    <input type="text" name="segments[{{ $i }}][label]" value="{{ $seg->label }}"
                           class="form-control form-control-sm fw-semibold" required
                           style="max-width:140px; min-width:100px" aria-label="Segment name">

                    <select name="segments[{{ $i }}][type]" class="form-select form-select-sm"
                            style="max-width:150px" data-seg-type aria-label="Segment type">
                      @foreach(Fmt::TYPES as $key => $meta)
                        <option value="{{ $key }}" @selected($seg->type === $key)>{{ $meta['label'] }}</option>
                      @endforeach
                    </select>

                    <div class="ms-auto d-flex gap-1">
                      <button type="submit" class="btn btn-outline-secondary btn-sm ico"
                              formaction="{{ route('segments.move', [$seg, 'up']) }}"
                              formmethod="post" formnovalidate
                              @disabled($i === 0) aria-label="Move up">↑</button>
                      <button type="submit" class="btn btn-outline-secondary btn-sm ico"
                              formaction="{{ route('segments.move', [$seg, 'down']) }}"
                              formmethod="post" formnovalidate
                              @disabled($i === $template->segments->count() - 1) aria-label="Move down">↓</button>
                      <button type="submit" class="btn btn-outline-danger btn-sm ico"
                              form="segDel{{ $seg->id }}" aria-label="Remove segment"
                              data-confirm="Remove {{ $seg->label }}?"
                              data-confirm-text="Numbers already issued do not change, but new ones get shorter."
                              data-confirm-button="Remove">✕</button>
                    </div>
                  </div>

                  <div class="sb">
                    {{-- fields for fixed text --}}
                    <div class="row g-3 align-items-end" data-when="fixed" @if($seg->type !== 'fixed') hidden @endif>
                      <div class="col-sm-6">
                        <label class="form-label lbl">Text</label>
                        <input type="text" name="segments[{{ $i }}][fixed_value]"
                               class="form-control form-control-sm mono text-uppercase"
                               maxlength="40" value="{{ $seg->fixed_value }}" placeholder="AB">
                      </div>
                      <div class="col-sm-6">
                        <div class="hint">{{ Fmt::TYPES['fixed']['hint'] }}</div>
                      </div>
                    </div>

                    {{-- fields for free text --}}
                    <div class="row g-3 align-items-end" data-when="free" @if($seg->type !== 'free') hidden @endif>
                      <div class="col-4 col-sm-3">
                        <label class="form-label lbl">Min length</label>
                        <input type="number" name="segments[{{ $i }}][min_length]"
                               class="form-control form-control-sm" min="1" max="40"
                               value="{{ $seg->min_length ?: 1 }}">
                      </div>
                      <div class="col-4 col-sm-3">
                        <label class="form-label lbl">Max length</label>
                        <input type="number" name="segments[{{ $i }}][max_length]"
                               class="form-control form-control-sm" min="1" max="40"
                               value="{{ $seg->max_length ?: 1 }}">
                      </div>
                      <div class="col-sm-6">
                        <label class="form-label lbl">Allowed characters</label>
                        <select name="segments[{{ $i }}][charset]" class="form-select form-select-sm">
                          @foreach(Fmt::CHARSETS as $key => $meta)
                            <option value="{{ $key }}" @selected(($seg->charset ?: 'A-Z0-9') === $key)>
                              {{ $meta['label'] }}
                            </option>
                          @endforeach
                        </select>
                      </div>
                    </div>
                  </div>
                </div>
              @endforeach

              <div class="d-flex align-items-center gap-3 mt-3">
                <div class="hint">Set min and max to the same number to force a fixed length</div>
                <button class="btn btn-pn btn-sm ms-auto">Save structure</button>
              </div>
            </form>
          @endif
        </div>

      </div>
    </div>

    {{-- ================= preview ================= --}}
    <div class="col-lg-3">
      <aside class="previewcol">
        <div class="card">
          <div class="card-header"><span class="lbl">Preview</span></div>

          <div class="codebox">
            @if($template->segments->isEmpty())
              <span class="hint">No segments yet</span>
            @else
              @foreach($template->segments as $i => $seg)
                @php $sep = Fmt::separatorBefore($template, $seg, $i); @endphp
                @if($sep)<span class="tok sep">{{ $sep }}</span>@endif
                <span class="tok t-{{ $seg->type }}" title="{{ $seg->label }}">{{ Fmt::segmentMask($seg) }}</span>
              @endforeach
            @endif
          </div>

          <div class="legend">
            <span><i style="background:var(--line-2)"></i>Fixed text</span>
            <span><i style="background:var(--accent)"></i>Free text</span>
          </div>

          <div class="card-body d-flex flex-column gap-3">
            @php $total = Fmt::totalLength($template); @endphp
            <div class="d-flex flex-wrap gap-2">
              <span class="pill flat">Pattern <span class="mono">{{ Fmt::mask($template) ?: '—' }}</span></span>
              <span class="pill {{ $total === null ? 'warn' : 'ok' }}">
                {{ $total === null ? 'variable length' : $total.' characters' }}
              </span>
            </div>

            <div class="table-responsive">
              <table class="table">
                <thead><tr><th>#</th><th>Segment</th><th>Format</th><th class="text-end">Length</th></tr></thead>
                <tbody>
                  @foreach($template->segments as $i => $seg)
                    <tr>
                      <td class="mono" style="color:var(--muted)">{{ str_pad($i + 1, 2, '0', STR_PAD_LEFT) }}</td>
                      <td>
                        {{ $seg->label }}
                        <div class="hint">{{ Fmt::TYPES[$seg->type]['label'] }}</div>
                      </td>
                      <td class="mono">{{ Fmt::segmentMask($seg) }}</td>
                      <td class="mono text-end">{{ Fmt::segmentLength($seg) ?? 'varies' }}</td>
                    </tr>
                  @endforeach
                </tbody>
              </table>
            </div>

            @if($template->segments->isNotEmpty())
              <div class="d-flex flex-column" style="gap:4px">
                <span class="lbl">Example number</span>
                <div class="mono" style="font-size:13px; color:var(--ink-2)">{{ Fmt::sample($template) }}</div>
              </div>

              <div class="d-flex flex-column" style="gap:4px">
                <span class="lbl">Matching pattern (regex)</span>
                <div class="mono" style="font-size:11.5px; word-break:break-all; color:var(--muted)">
                  {{ Fmt::regex($template) }}
                </div>
              </div>
            @endif
          </div>
        </div>
      </aside>
    </div>
  @else
    <div class="col-lg-9">
      <div class="card">
        <div class="empty">No templates in this company yet — press <strong>New template</strong> to start</div>
      </div>
    </div>
  @endif
</div>

{{-- new template --}}
<div class="modal fade" id="tplAddModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <form class="modal-content" method="post" action="{{ route('templates.store', $company) }}">
      @csrf
      <div class="modal-header">
        <h5 class="modal-title">New template</h5>
        <button type="button" class="btn-close p-2" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body d-flex flex-column gap-3">
        <div>
          <label for="newTplName" class="form-label lbl">Template name</label>
          <input type="text" class="form-control" id="newTplName" name="name" required
                 autocomplete="off" placeholder="Finished goods">
        </div>
        <div>
          <label for="newTplCode" class="form-label lbl">Template code (optional)</label>
          <input type="text" class="form-control mono text-uppercase" id="newTplCode" name="code"
                 maxlength="20" autocomplete="off" placeholder="FG">
        </div>
        <div>
          <label for="newTplNote" class="form-label lbl">Description (optional)</label>
          <textarea class="form-control" id="newTplNote" name="note" rows="2"></textarea>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
        <button class="btn btn-pn">Create</button>
      </div>
    </form>
  </div>
</div>

@if($template)
  {{-- One removal form per segment, outside the edit form so it carries no pending edits --}}
  @foreach($template->segments as $seg)
    <form method="post" id="segDel{{ $seg->id }}" action="{{ route('segments.delete', $seg) }}" class="d-none">
      @csrf @method('delete')
    </form>
  @endforeach
@endif

@endif
@endsection

@push('scripts')
<script>
"use strict";

/* A new segment is appended at the end of the list, which can sit past the
   fold. The server redirects to its fragment, so the browser is already there —
   this only marks which card is new and softens the jump. */
(function () {
  var id = window.location.hash;
  if (!id || id.indexOf("#seg") !== 0) return;

  var card = document.getElementById(id.slice(1));
  if (!card) return;

  card.classList.add("isnew");

  /* The fragment jump is instant; re-run it smoothly now the page has settled */
  if (!window.matchMedia("(prefers-reduced-motion: reduce)").matches) {
    card.scrollIntoView({ behavior: "smooth", block: "nearest" });
  }

  /* Keep the address bar clean, so a reload does not re-highlight */
  if (window.history.replaceState) {
    window.history.replaceState(null, "", window.location.pathname + window.location.search);
  }
})();

/* Show only the fields that belong to the chosen segment type */
document.querySelectorAll("[data-seg-type]").forEach(function (sel) {
  var card = sel.closest(".seg");
  function sync() {
    card.querySelectorAll("[data-when]").forEach(function (box) {
      box.hidden = box.dataset.when !== sel.value;
    });
  }
  sel.addEventListener("change", sync);
  sync();
});

/* Separator preset buttons */
document.querySelectorAll("[data-sep-set]").forEach(function (btn) {
  btn.addEventListener("click", function () {
    var input = btn.closest(".input-group").querySelector("[data-sep-input]");
    if (input) input.value = btn.dataset.sepSet;
  });
});

/* A separator is punctuation. Letters and digits are segment content, so they
   are stripped as they are typed — mirrors PartNumberFormat::cleanSeparator */
document.querySelectorAll("[data-sep-input]").forEach(function (input) {
  input.addEventListener("input", function () {
    var cleaned = input.value.replace(/[\p{L}\p{N}]/gu, "").slice(0, 4);
    if (cleaned !== input.value) input.value = cleaned;
  });
});

</script>
@endpush
