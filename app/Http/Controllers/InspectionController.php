<?php

namespace App\Http\Controllers;

use App\Models\Inspection;
use App\Models\InspectionMedia;
use App\Models\Lease;
use App\Models\User;
use App\Services\InspectionService;
use App\Services\Notifier;
use App\Services\PropertyAudience;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use App\Support\FileUpload;
use RuntimeException;

/**
 * Move-in & move-out inspections (brief §16) with deposit settlement.
 *
 * Workflow:  draft → awaiting_tenant → completed
 *                         ↓  ↑ (staff edits and resubmits)
 *                      disputed
 *
 * Staff (admin, or the property's owner/managers) record the inspection;
 * the tenant confirms or disputes it. Tenants without a Terra login can
 * sign the paper copy and staff record that confirmation in person.
 */
class InspectionController extends Controller
{
    private const MEDIA_RULES = ['file', 'max:40960', 'mimetypes:image/jpeg,image/png,image/webp,image/heic,video/mp4,video/quicktime,video/webm,video/3gpp'];

    public function __construct(
        private InspectionService $service,
        private PropertyAudience $audience,
        private Notifier $notifier,
    ) {
    }

    public function index(Request $request)
    {
        $user = $request->user();
        $type = $request->query('type');
        $status = $request->query('status');

        $inspections = $this->visibleTo($user)
            ->with(['lease.tenant', 'unit.building.property'])
            ->when(in_array($type, [Inspection::TYPE_MOVE_IN, Inspection::TYPE_MOVE_OUT], true), fn ($q) => $q->where('type', $type))
            ->when(isset(Inspection::STATUSES[$status]), fn ($q) => $q->where('status', $status))
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        // Leases that are ready for an inspection to be started.
        $ready = $this->isStaff($user) ? $this->leasesNeedingInspection($user) : collect();

        return view('inspections.index', compact('inspections', 'ready', 'type', 'status') + [
            'isStaff' => $this->isStaff($user),
            'counts' => $this->visibleTo($user)->selectRaw('status, count(*) c')->groupBy('status')->pluck('c', 'status'),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'lease_id' => ['required', 'integer', 'exists:leases,id'],
            'type' => ['required', Rule::in([Inspection::TYPE_MOVE_IN, Inspection::TYPE_MOVE_OUT])],
        ]);

        $lease = Lease::with('unit.building')->findOrFail($data['lease_id']);
        abort_unless($this->canManageProperty($request->user(), $lease->unit?->building?->property_id), 403);

        $inspection = $this->service->start($lease, $data['type'], $request->user());

        return redirect()->route('inspections.edit', $inspection)
            ->with('success', 'Inspection started with a checklist for this unit. Rate each item, add photos, then send it to the tenant.');
    }

    public function show(Request $request, Inspection $inspection)
    {
        $role = $this->roleFor($request->user(), $inspection);

        $inspection->load(['lease.tenant.user', 'unit.building.property', 'inspector', 'items.baseline', 'items.media',
            'media', 'baseline.media', 'moveOut']);

        return view('inspections.show', [
            'inspection' => $inspection,
            'role' => $role,
            'areas' => $inspection->items->groupBy('area'),
            'generalMedia' => $inspection->media->whereNull('inspection_item_id'),
            'tenantHasLogin' => (bool) $inspection->lease?->tenant?->user_id,
        ]);
    }

    public function edit(Request $request, Inspection $inspection)
    {
        abort_unless($this->roleFor($request->user(), $inspection) === 'staff', 403);
        if (! $inspection->isEditable()) {
            return redirect()->route('inspections.show', $inspection)->with('warning', 'This inspection is with the tenant or completed and can no longer be edited.');
        }

        $inspection->load(['lease.tenant', 'unit.building.property', 'items.baseline', 'items.media', 'media.item', 'baseline']);

        return view('inspections.edit', [
            'inspection' => $inspection,
            'areas' => $inspection->items->groupBy('area'),
        ]);
    }

    public function update(Request $request, Inspection $inspection)
    {
        $this->assertEditable($request->user(), $inspection);

        $data = $request->validate([
            'inspection_date' => ['required', 'date', 'before_or_equal:today'],
            'electricity_reading' => ['nullable', 'numeric', 'min:0', 'max:9999999999'],
            'water_reading' => ['nullable', 'numeric', 'min:0', 'max:9999999999'],
            'keys_handed' => ['nullable', 'integer', 'min:0', 'max:999'],
            'general_notes' => ['nullable', 'string', 'max:5000'],
            'items' => ['nullable', 'array'],
            'items.*.condition' => ['nullable', Rule::in(array_keys(Inspection::CONDITIONS))],
            'items.*.quantity' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'items.*.notes' => ['nullable', 'string', 'max:1000'],
            'items.*.deduction_amount' => ['nullable', 'numeric', 'min:0', 'max:999999999'],
            'items.*.remove' => ['nullable', 'boolean'],
            'new_items' => ['nullable', 'array', 'max:30'],
            'new_items.*.area' => ['nullable', 'required_with:new_items.*.item', 'string', 'max:80'],
            'new_items.*.item' => ['nullable', 'string', 'max:120'],
            'new_items.*.condition' => ['nullable', Rule::in(array_keys(Inspection::CONDITIONS))],
            'new_items.*.notes' => ['nullable', 'string', 'max:1000'],
        ]);

        DB::transaction(function () use ($inspection, $data) {
            $inspection->update(collect($data)->only(['inspection_date', 'electricity_reading', 'water_reading', 'keys_handed', 'general_notes'])->all());

            $items = $inspection->items()->get()->keyBy('id');
            foreach ($data['items'] ?? [] as $id => $row) {
                $item = $items->get((int) $id);
                if (! $item) {
                    continue; // ignore ids from other inspections
                }
                if (! empty($row['remove']) && ! $item->baseline_item_id) {
                    $item->delete();
                    continue;
                }
                $item->update([
                    'condition' => $row['condition'] ?? null,
                    'quantity' => $row['quantity'] ?? null,
                    'notes' => $row['notes'] ?? null,
                    'deduction_amount' => $inspection->isMoveOut() ? (float) ($row['deduction_amount'] ?? 0) : 0,
                ]);
            }

            $order = (int) $inspection->items()->max('sort_order');
            foreach ($data['new_items'] ?? [] as $row) {
                if (blank($row['item'] ?? null)) {
                    continue;
                }
                $inspection->items()->create([
                    'area' => $row['area'],
                    'item' => $row['item'],
                    'condition' => $row['condition'] ?? null,
                    'notes' => $row['notes'] ?? null,
                    'sort_order' => ++$order,
                ]);
            }

            $this->service->recalculateDeposit($inspection);
        });

        return $request->input('then') === 'submit'
            ? $this->submit($request, $inspection->fresh())
            : back()->with('success', 'Inspection saved.');
    }

    public function uploadMedia(Request $request, Inspection $inspection)
    {
        $this->assertEditable($request->user(), $inspection);

        $data = $request->validate([
            'media' => ['required', 'array', 'max:10'],
            'media.*' => self::MEDIA_RULES,
            'inspection_item_id' => ['nullable', 'integer', Rule::exists('inspection_items', 'id')->where('inspection_id', $inspection->id)],
            'caption' => ['nullable', 'string', 'max:255'],
        ]);

        // Evidence is private: moved to uploads/inspections/{id}/, never under public/.
        $saved = [];
        try {
            foreach ($request->file('media') as $file) {
                // Read the details before move(): the temporary file is gone afterwards.
                $meta = ['original_name' => mb_substr($file->getClientOriginalName(), 0, 255), 'mime_type' => $file->getMimeType(), 'size' => $file->getSize()];
                $path = FileUpload::store($file, FileUpload::INSPECTIONS . '/' . $inspection->id, true);
                $saved[] = $path;

                $inspection->media()->create($meta + [
                    'inspection_item_id' => $data['inspection_item_id'] ?? null,
                    'uploaded_by' => $request->user()->id,
                    'path' => $path,
                    'caption' => $data['caption'] ?? null,
                ]);
            }
        } catch (RuntimeException $e) {
            FileUpload::deleteMany($saved, true);
            InspectionMedia::whereIn('path', $saved)->delete();

            return back()->withErrors(['media' => $e->getMessage()]);
        }

        return back()->with('success', count($request->file('media')) . ' file(s) added.');
    }

    public function destroyMedia(Request $request, Inspection $inspection, InspectionMedia $media)
    {
        $this->assertEditable($request->user(), $inspection);
        abort_unless($media->inspection_id === $inspection->id, 404);

        $media->delete(); // the model removes its file from uploads/

        return back()->with('success', 'File removed.');
    }

    public function media(Request $request, InspectionMedia $media)
    {
        $media->loadMissing('inspection');
        $this->roleFor($request->user(), $media->inspection);

        return FileUpload::response($media->path, $media->original_name, $media->mime_type);
    }

    public function submit(Request $request, Inspection $inspection)
    {
        $this->assertEditable($request->user(), $inspection);

        $unrated = $inspection->items()->whereNull('condition')->count();
        if ($unrated > 0) {
            throw ValidationException::withMessages(['items' => "Rate every item before sending — {$unrated} still have no condition (use “Not applicable” where needed)."]);
        }

        $this->service->recalculateDeposit($inspection);
        $inspection->update([
            'status' => 'awaiting_tenant',
            'submitted_at' => now(),
            'tenant_responded_at' => null,
        ]);

        $this->notifier->inspectionSubmitted($inspection);

        return redirect()->route('inspections.show', $inspection)
            ->with('success', 'Inspection sent to the tenant for confirmation.');
    }

    /** Tenant confirms or disputes; staff can record an in-person (paper) confirmation. */
    public function respond(Request $request, Inspection $inspection)
    {
        $role = $this->roleFor($request->user(), $inspection);
        abort_unless($inspection->status === 'awaiting_tenant', 422, 'This inspection is not awaiting confirmation.');

        $data = $request->validate([
            'action' => ['required', Rule::in($role === 'tenant' ? ['confirm', 'dispute'] : ['confirm_in_person'])],
            'comment' => ['nullable', 'required_if:action,dispute', 'required_if:action,confirm_in_person', 'string', 'max:3000'],
            'agree' => ['required_if:action,confirm', 'accepted_if:action,confirm'],
        ], [
            'comment.required_if' => $role === 'tenant'
                ? 'Please explain what you disagree with.'
                : 'Note how the tenant confirmed (e.g. signed paper copy on site).',
            'agree.accepted_if' => 'Tick the box to confirm the report is accurate.',
        ]);

        $disputed = $data['action'] === 'dispute';

        $inspection->update([
            'status' => $disputed ? 'disputed' : 'completed',
            'tenant_responded_at' => now(),
            'tenant_comment' => $data['action'] === 'confirm_in_person'
                ? 'Confirmed in person — recorded by ' . $request->user()->name . ': ' . $data['comment']
                : ($data['comment'] ?? null),
            'completed_at' => $disputed ? null : now(),
        ]);

        if ($role === 'tenant') {
            $this->notifier->inspectionResponded($inspection);
        }

        return redirect()->route('inspections.show', $inspection)->with('success', match ($data['action']) {
            'dispute' => 'Thanks — the property manager has been told and will review your comments.',
            'confirm' => 'Inspection confirmed. A copy stays in your Terra account.',
            default => 'In-person confirmation recorded. The inspection is complete.',
        });
    }

    public function settleDeposit(Request $request, Inspection $inspection)
    {
        abort_unless($this->roleFor($request->user(), $inspection) === 'staff', 403);
        abort_unless($inspection->isMoveOut() && $inspection->status === 'completed' && ! $inspection->deposit_settled_at, 422,
            'The deposit can be settled once the move-out inspection is completed.');

        $data = $request->validate([
            'deposit_refund_reference' => ['nullable', 'string', 'max:255'],
            'settlement_notes' => ['nullable', 'string', 'max:3000'],
        ]);

        $this->service->recalculateDeposit($inspection);
        $inspection->update($data + ['deposit_settled_at' => now()]);

        $this->notifier->depositSettled($inspection);

        return back()->with('success', 'Deposit settlement recorded.');
    }

    public function report(Request $request, Inspection $inspection)
    {
        $this->roleFor($request->user(), $inspection);
        $inspection->load(['lease.tenant', 'unit.building.property.owner', 'inspector', 'items.baseline', 'media']);

        return view('inspections.report', [
            'inspection' => $inspection,
            'areas' => $inspection->items->groupBy('area'),
        ]);
    }

    /* ------------------------------------------------------------------ */

    /** 'staff' or 'tenant' for users who may see this inspection; 404 for everyone else. */
    private function roleFor(?User $user, Inspection $inspection): string
    {
        $inspection->loadMissing(['unit.building', 'lease.tenant']);

        if ($user && $this->canManageProperty($user, $inspection->unit?->building?->property_id)) {
            return 'staff';
        }

        if ($user && $inspection->lease?->tenant?->user_id === $user->id && $inspection->status !== 'draft') {
            return 'tenant';
        }

        abort(404);
    }

    private function canManageProperty(User $user, ?int $propertyId): bool
    {
        $ids = $this->audience->accessiblePropertyIds($user);

        return $ids === null || ($propertyId && $ids->contains($propertyId));
    }

    private function isStaff(User $user): bool
    {
        $ids = $this->audience->accessiblePropertyIds($user);

        return $ids === null || $ids->isNotEmpty();
    }

    private function assertEditable(User $user, Inspection $inspection): void
    {
        abort_unless($this->roleFor($user, $inspection) === 'staff', 403);
        abort_unless($inspection->isEditable(), 422, 'This inspection can no longer be edited.');
    }

    private function visibleTo(User $user)
    {
        $ids = $this->audience->accessiblePropertyIds($user);

        return Inspection::query()->where(function ($q) use ($user, $ids) {
            if ($ids === null) {
                return; // admin: everything
            }
            $q->whereHas('unit.building', fn ($b) => $b->whereIn('property_id', $ids))
                ->orWhere(fn ($t) => $t->where('status', '!=', 'draft')
                    ->whereHas('lease.tenant', fn ($x) => $x->where('user_id', $user->id)));
        });
    }

    /** Live/ending leases in the user's portfolio that still lack a move-in or move-out inspection. */
    private function leasesNeedingInspection(User $user)
    {
        $ids = $this->audience->accessiblePropertyIds($user);

        return Lease::query()
            ->whereIn('status', ['pending_tenant_acceptance', 'pending_activation', 'active', 'terminated', 'expired'])
            ->when($ids !== null, fn ($q) => $q->whereHas('unit.building', fn ($b) => $b->whereIn('property_id', $ids)))
            ->with(['tenant', 'unit.building.property', 'inspections:id,lease_id,type'])
            ->latest('id')
            ->limit(100)
            ->get()
            ->map(function ($lease) {
                $types = $lease->inspections->pluck('type');
                $needs = null;
                if (in_array($lease->status, ['pending_tenant_acceptance', 'pending_activation', 'active'], true) && ! $types->contains('move_in')) {
                    $needs = Inspection::TYPE_MOVE_IN;
                } elseif (in_array($lease->status, ['terminated', 'expired'], true) && ! $types->contains('move_out')) {
                    $needs = Inspection::TYPE_MOVE_OUT;
                }

                return $needs ? ['lease' => $lease, 'type' => $needs] : null;
            })
            ->filter()
            ->take(8)
            ->values();
    }
}
