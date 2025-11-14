## Root Cause
- Nested form in `resources/views/cost-centers/_form.blade.php` prevents the inner Restore form from submitting (invalid HTML). The Restore button ends up submitting the outer Create/Update form instead, so nothing happens and no logs are written.
- Duplicate route declarations for `cost-centers.restore` (one at the very top of `routes/web.php` and one inside the `auth` group) can cause confusion and incorrect middleware scoping.

## Fix Plan
### 1) Remove Nested Form
- Replace the inner Restore form in `_form.blade.php` with controls that submit the OUTER form:
  - Add hidden inputs: `ghost_id`, `restore_confirm=1` when a restore candidate exists.
  - Change Restore button to: `<button type="submit" name="restore_confirm" value="1">Restaurar</button>`.
  - Keep Cancel as a simple link that refreshes page and clears `restore_candidate`.

### 2) Controller Logic
- Update `CostCenterController@store` and `@update` to branch:
  - If `restore_confirm=1` and `ghost_id` is present → perform the restore inline:
    - `onlyTrashed()->findOrFail($ghost_id)` → `restore()` → `update($payload)` → redirect success.
  - Else if ghost exists (and no confirm) → return `back()->with('restore_candidate', ...)->withInput()`.
  - Else → normal create/update.
- Keep `restoreGhost()` for API/JS submissions if desired, but not needed for the Blade form.
- Add minimal debug logging around restore branch to confirm execution.

### 3) Routes Cleanup
- Remove the top‑of‑file `Route::post('/cost-centers/restore', ...)` and keep only the route inside the `auth` group (or drop it entirely if using inline restore in `store/update`).

### 4) UX
- The banner remains above the form with the message and [Restaurar]/[Cancelar].
- Restore button submits the current form (no nested forms), the controller detects confirm and completes the restore.

### 5) Validation and Flags
- Continue using `Rule::unique()->whereNull('deleted_at')` to compare only active records.
- Normalize `is_active` and `color` from outer form inputs (`use_custom_color` flag).

### 6) Verification
- Scenario: Create "Financeiro" soft‑deleted → open Create, enter "Financeiro" → banner appears → click Restaurar → record is restored with current inputs, redirect with success.
- Scenario: Update name to existing soft‑deleted → banner appears → click Restaurar → merge and success.
- Confirm single route in `web.php` and that middleware is correct.

## Deliverables
- `_form.blade.php`: hidden fields + submit button for restore; remove inner form.
- `CostCenterController@store/@update`: inline restore branch.
- `routes/web.php`: cleanup duplicate route.
- Logs (info level) for restore execution to help future debugging.