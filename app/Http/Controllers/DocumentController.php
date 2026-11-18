<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\Lease;
use App\Models\Property;
use App\Models\PropertyOwner;
use App\Models\SubscriptionContract;
use App\Models\Tenant;
use App\Models\TenantApplication;
use App\Models\Unit;
use App\Models\User;
use App\Services\Notifier;
use App\Services\PropertyAudience;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use App\Support\FileUpload;
use RuntimeException;

/**
 * Document management (MVP #14) and tenant KYC (brief §6).
 * Files are stored on the private disk and only streamed to people
 * connected to the property, unit, lease or tenant they belong to.
 */
class DocumentController extends Controller
{
    private const FILE_RULES = ['required', 'file', 'max:10240', 'mimes:pdf,jpg,jpeg,png,webp,doc,docx'];

    public function __construct(private PropertyAudience $audience, private Notifier $notifier)
    {
    }

    public function index(Request $request)
    {
        $user = $request->user();
        $isStaff = $this->audience->isStaff($user);

        $documents = $this->visibleTo($user)
            ->with(['documentable', 'uploader:id,name'])
            ->when($request->filled('owner'), function ($q) use ($request) {
                [$key, $id] = array_pad(explode(':', (string) $request->query('owner'), 2), 2, null);
                if (isset(Document::OWNER_TYPES[$key])) {
                    $q->where('documentable_type', Document::OWNER_TYPES[$key][0])->when($id, fn ($w) => $w->where('documentable_id', (int) $id));
                }
            })
            ->when(isset(Document::CATEGORIES[$request->query('category')]), fn ($q) => $q->where('category', $request->query('category')))
            ->when(in_array($request->query('status'), ['pending', 'verified', 'rejected'], true), fn ($q) => $q->where('status', $request->query('status')))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        $tenant = $user->tenant;

        return view('documents.index', [
            'documents' => $documents,
            'isStaff' => $isStaff,
            'tenant' => $tenant,
            'owners' => $this->uploadTargets($user),
            'pendingKyc' => $isStaff ? (clone $this->visibleTo($user))->where('status', 'pending')->count() : 0,
        ]);
    }

    public function store(Request $request)
    {
        $user = $request->user();

        $data = $request->validate([
            'owner' => ['required', 'string', 'regex:/^(property|unit|tenant|lease|owner|subscription):\d+$/'],
            'category' => ['required', Rule::in(array_keys(Document::CATEGORIES))],
            'title' => ['nullable', 'string', 'max:150'],
            'expires_at' => ['nullable', 'date', 'after:today'],
            // Contracts must be PDF; other documents may also be photos or Word files.
            'file' => in_array($request->input('category'), Document::CONTRACT_CATEGORIES, true)
                ? ['required', 'file', 'max:10240', 'mimes:pdf', 'mimetypes:application/pdf']
                : self::FILE_RULES,
        ], ['file.mimes' => 'Contracts must be uploaded as a PDF file.', 'file.mimetypes' => 'Contracts must be uploaded as a PDF file.']);

        [$key, $id] = explode(':', $data['owner']);
        $owner = $this->resolveOwner($key, (int) $id);
        abort_unless($owner && $this->canWrite($user, $owner), 403);

        if (! in_array($data['category'], Document::OWNER_TYPES[$key][1], true)) {
            return back()->withInput()->withErrors(['category' => 'That document type does not apply to a ' . $key . '.']);
        }

        $isStaff = $this->canManageOwner($user, $owner);
        $isContract = in_array($data['category'], Document::CONTRACT_CATEGORIES, true);

        // Contracts are legal records: only staff responsible for the record may file them.
        abort_if($isContract && ! $isStaff, 403, 'Only staff can upload contracts.');

        $needsReview = $owner instanceof Tenant && ! $isContract;

        if (! $request->hasFile('file')) {
            return back()->withInput()->withErrors(['file' => 'Choose a file to upload.']);
        }

        // Read the details before move(): the temporary upload is gone afterwards.
        $file = $request->file('file');
        $meta = [
            'original_name' => mb_substr($file->getClientOriginalName(), 0, 255),
            'mime_type' => $file->getMimeType(),
            'size' => $file->getSize(),
        ];

        // Private folder, outside public/: uploads/documents/{contracts|kyc|properties|general}/
        $path = FileUpload::store($file, Document::directoryFor($data['category'], $key), true);

        try {
            $document = $owner->documents()->create($meta + [
                'category' => $data['category'],
                'title' => ($data['title'] ?? null) ?: Document::CATEGORIES[$data['category']],
                'path' => $path,
                'uploaded_by' => $user->id,
                // Staff uploading a tenant document have seen the original, so it counts as verified.
                'status' => $needsReview ? ($isStaff ? 'verified' : 'pending') : 'not_required',
                'verified_by' => $needsReview && $isStaff ? $user->id : null,
                'verified_at' => $needsReview && $isStaff ? now() : null,
                'expires_at' => $data['expires_at'] ?? null,
            ]);
        } catch (\Throwable $e) {
            FileUpload::delete($path, true);

            throw $e;
        }

        if ($owner instanceof Tenant && $needsReview) {
            $owner->refreshKycStatus();
            if (! $isStaff) {
                $this->notifier->documentUploaded($document);
            }
        }

        if ($isContract) {
            $this->notifier->contractDocumentFiled($document);
        }

        return back()->with('success', $document->status === 'pending'
            ? 'Document uploaded. Your property manager will verify it.'
            : 'Document uploaded.');
    }

    public function download(Request $request, Document $document)
    {
        $owner = $document->documentable;
        abort_unless($owner && $this->canRead($request->user(), $owner), 404);

        $inline = str_starts_with((string) $document->mime_type, 'image/') || $document->mime_type === 'application/pdf';

        return FileUpload::response($document->path, $document->original_name, $document->mime_type, $inline && ! $request->boolean('download'));
    }

    public function review(Request $request, Document $document)
    {
        $owner = $document->documentable;
        abort_unless($owner instanceof Tenant && $this->canManageOwner($request->user(), $owner), 403);

        $data = $request->validate([
            'decision' => ['required', Rule::in(['verify', 'reject'])],
            'reason' => ['nullable', 'required_if:decision,reject', 'string', 'max:255'],
        ], ['reason.required_if' => 'Tell the tenant why the document was rejected.']);

        $document->update([
            'status' => $data['decision'] === 'verify' ? 'verified' : 'rejected',
            'verified_by' => $request->user()->id,
            'verified_at' => now(),
            'rejection_reason' => $data['decision'] === 'reject' ? $data['reason'] : null,
        ]);
        $owner->refreshKycStatus();
        $this->notifier->documentReviewed($document);

        return back()->with('success', $data['decision'] === 'verify' ? 'Document verified.' : 'Document rejected; the tenant has been told why.');
    }

    public function destroy(Request $request, Document $document)
    {
        $user = $request->user();
        $owner = $document->documentable;
        $mayDelete = $owner && ($this->canManageOwner($user, $owner)
            || ($document->uploaded_by === $user->id && $document->status !== 'verified'));
        abort_unless($mayDelete, 403);

        $document->delete(); // soft delete: the record and file stay for the audit trail
        if ($owner instanceof Tenant) {
            $owner->refreshKycStatus();
        }

        return back()->with('success', 'Document removed.');
    }

    /** Tenant identity details for KYC. */
    public function updateIdentity(Request $request)
    {
        $tenant = $request->user()->tenant;
        abort_unless($tenant, 404);

        $data = $request->validate([
            'id_type' => ['required', Rule::in(array_keys(Tenant::ID_TYPES))],
            'id_number' => ['required', 'string', 'max:40', $request->input('id_type') === 'national_id' ? 'regex:/^\d{16}$/' : 'regex:/^[A-Za-z0-9-]{5,40}$/'],
            'occupants' => ['nullable', 'integer', 'min:1', 'max:30'],
        ], ['id_number.regex' => 'A Rwandan National ID number has 16 digits.']);

        $tenant->update($data);

        return back()->with('success', 'Identity details saved.');
    }

    /* ------------------------------------------------------------------ */

    private function resolveOwner(string $key, int $id): ?Model
    {
        return isset(Document::OWNER_TYPES[$key]) ? Document::OWNER_TYPES[$key][0]::find($id) : null;
    }

    /** Admin, or owner/manager of the property the record belongs to. */
    private function canManageOwner(User $user, Model $owner): bool
    {
        return match (true) {
            $owner instanceof Property => $this->audience->canManageProperty($user, $owner->id),
            $owner instanceof Unit => $this->audience->canManageProperty($user, $this->audience->propertyIdForUnit($owner)),
            $owner instanceof Lease => $this->audience->canManageProperty($user, $this->audience->propertyIdForLease($owner)),
            $owner instanceof Tenant => $this->tenantPropertyIds($owner)->contains(fn ($pid) => $this->audience->canManageProperty($user, $pid))
                || $this->audience->accessiblePropertyIds($user) === null
                || ($this->audience->isStaff($user) && (int) $owner->created_by === $user->id),
            // Owner agreements and subscriptions are between the owner and Terra: admins only.
            $owner instanceof PropertyOwner, $owner instanceof SubscriptionContract => $user->isAdmin(),
            default => false,
        };
    }

    private function canRead(User $user, Model $owner): bool
    {
        return $this->canManageOwner($user, $owner)
            || ($owner instanceof Tenant && $owner->user_id === $user->id)
            || ($owner instanceof Lease && $this->audience->isTenantOf($user, $owner))
            || ($owner instanceof PropertyOwner && $owner->user_id && $owner->user_id === $user->id)
            || ($owner instanceof SubscriptionContract && $owner->user_id === $user->id);
    }

    private function canWrite(User $user, Model $owner): bool
    {
        // Owners and subscribers may read their agreements but only Terra files them.
        if ($owner instanceof PropertyOwner || $owner instanceof SubscriptionContract) {
            return $this->canManageOwner($user, $owner);
        }

        return $this->canRead($user, $owner);
    }

    /** Properties a tenant is connected to: live leases and open applications. */
    private function tenantPropertyIds(Tenant $tenant): Collection
    {
        $unitIds = Lease::where('tenant_id', $tenant->id)->pluck('unit_id')
            ->concat(TenantApplication::where('tenant_id', $tenant->id)->pluck('unit_id'));

        return Unit::whereIn('id', $unitIds)->with('building')->get()->pluck('building.property_id')->filter()->unique()->values();
    }

    private function visibleTo(User $user): Builder
    {
        $ids = $this->audience->accessiblePropertyIds($user);
        $query = Document::query();

        if ($ids === null) {
            return $query; // admin
        }

        if ($user->isFieldStaff()) {
            return $query->whereRaw('1 = 0');
        }

        $unitIds = Unit::whereHas('building', fn ($b) => $b->whereIn('property_id', $ids))->pluck('id');
        $leaseIds = Lease::whereIn('unit_id', $unitIds)->pluck('id');
        $tenantIds = Lease::whereIn('unit_id', $unitIds)->pluck('tenant_id')
            ->concat(TenantApplication::whereIn('unit_id', $unitIds)->pluck('tenant_id'))
            ->concat(Tenant::where('created_by', $user->id)->pluck('id'));

        if ($tenant = $user->tenant) {
            $tenantIds->push($tenant->id);
            $leaseIds = $leaseIds->concat($tenant->leases()->pluck('id'));
        }

        $ownerProfileId = $user->propertyOwner?->id;
        $subscriptionIds = SubscriptionContract::where('user_id', $user->id)->pluck('id');

        return $query->where(function ($q) use ($ids, $unitIds, $leaseIds, $tenantIds, $ownerProfileId, $subscriptionIds) {
            $q->where(fn ($w) => $w->where('documentable_type', Property::class)->whereIn('documentable_id', $ids))
                ->orWhere(fn ($w) => $w->where('documentable_type', Unit::class)->whereIn('documentable_id', $unitIds))
                ->orWhere(fn ($w) => $w->where('documentable_type', Lease::class)->whereIn('documentable_id', $leaseIds->unique()))
                ->orWhere(fn ($w) => $w->where('documentable_type', Tenant::class)->whereIn('documentable_id', $tenantIds->unique()))
                ->orWhere(fn ($w) => $w->where('documentable_type', PropertyOwner::class)->where('documentable_id', $ownerProfileId ?? 0))
                ->orWhere(fn ($w) => $w->where('documentable_type', SubscriptionContract::class)->whereIn('documentable_id', $subscriptionIds));
        });
    }

    /** Records the user can upload to, for the upload form. */
    private function uploadTargets(User $user): Collection
    {
        $targets = collect();

        if ($tenant = $user->tenant) {
            $targets->push(['value' => 'tenant:' . $tenant->id, 'label' => 'My identity & tenancy documents', 'type' => 'tenant']);
            foreach ($tenant->leases()->with('unit')->get() as $lease) {
                $targets->push(['value' => 'lease:' . $lease->id, 'label' => 'Lease ' . $lease->lease_number . ' · Unit ' . $lease->unit?->unit_number, 'type' => 'lease']);
            }
        }

        $ids = $this->audience->accessiblePropertyIds($user);
        if ($ids === null) {
            foreach (PropertyOwner::orderBy('name')->limit(200)->get(['id', 'name']) as $o) {
                $targets->push(['value' => 'owner:' . $o->id, 'label' => 'Owner · ' . $o->name, 'type' => 'owner']);
            }
        }
        if ($ids === null || $ids->isNotEmpty()) {
            $properties = Property::when($ids !== null, fn ($q) => $q->whereIn('id', $ids))->orderBy('name')->limit(200)->get(['id', 'name']);
            foreach ($properties as $p) {
                $targets->push(['value' => 'property:' . $p->id, 'label' => 'Property · ' . $p->name, 'type' => 'property']);
            }
            $leases = Lease::whereIn('status', ['active', 'pending_activation', 'pending_tenant_acceptance', 'draft'])
                ->when($ids !== null, fn ($q) => $q->whereHas('unit.building', fn ($b) => $b->whereIn('property_id', $ids)))
                ->with(['tenant', 'unit'])->latest('id')->limit(200)->get();
            foreach ($leases as $l) {
                $targets->push(['value' => 'lease:' . $l->id, 'label' => 'Lease ' . $l->lease_number . ' · ' . ($l->tenant?->full_name ?? 'Tenant'), 'type' => 'lease']);
                if ($l->tenant) {
                    $targets->push(['value' => 'tenant:' . $l->tenant_id, 'label' => 'Tenant · ' . $l->tenant->full_name, 'type' => 'tenant']);
                }
            }
        }

        return $targets->unique('value')->values();
    }
}
