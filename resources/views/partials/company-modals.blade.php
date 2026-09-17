{{-- New / Rename / Delete company, driven by the top-bar buttons on every tab --}}

<div class="modal fade" id="companyAddModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <form class="modal-content" method="post" action="{{ route('companies.store') }}">
      @csrf
      <div class="modal-header">
        <h5 class="modal-title">New company</h5>
        <button type="button" class="btn-close p-2" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body d-flex flex-column gap-3">
        <div>
          <label for="coName" class="form-label lbl">Company name</label>
          <input type="text" class="form-control" id="coName" name="name" required
                 autocomplete="off" placeholder="Bangkok Parts Ltd.">
        </div>
        <div>
          <label for="coCode" class="form-label lbl">Short code</label>
          <input type="text" class="form-control mono text-uppercase" id="coCode" name="code"
                 maxlength="10" required autocomplete="off" placeholder="BKP" pattern="[A-Za-z0-9]+">
          <div class="form-text">Letters and digits, up to 10 · a starter template comes with it</div>
        </div>
        <div>
          <label for="coNote" class="form-label lbl">Note (optional)</label>
          <textarea class="form-control" id="coNote" name="note" rows="2"></textarea>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
        <button class="btn btn-pn">Create</button>
      </div>
    </form>
  </div>
</div>

@isset($company)
@if($company)
  <div class="modal fade" id="companyEditModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
      <form class="modal-content" method="post" action="{{ route('companies.update', $company) }}">
        @csrf @method('put')
        <div class="modal-header">
          <h5 class="modal-title">Rename company</h5>
          <button type="button" class="btn-close p-2" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body d-flex flex-column gap-3">
          <div>
            <label for="coEditName" class="form-label lbl">Company name</label>
            <input type="text" class="form-control" id="coEditName" name="name" required value="{{ $company->name }}">
          </div>
          <div>
            <label for="coEditCode" class="form-label lbl">Short code</label>
            <input type="text" class="form-control mono text-uppercase" id="coEditCode" name="code"
                   maxlength="10" required value="{{ $company->code }}" pattern="[A-Za-z0-9]+">
          </div>
          <div>
            <label for="coEditNote" class="form-label lbl">Note</label>
            <textarea class="form-control" id="coEditNote" name="note" rows="2">{{ $company->note }}</textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
          <button class="btn btn-pn">Save</button>
        </div>
      </form>
    </div>
  </div>

@endif
@endisset
