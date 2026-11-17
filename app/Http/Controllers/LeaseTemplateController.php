<?php

namespace App\Http\Controllers;

use App\Models\LeaseTemplate;
use App\Models\Property;
use App\Services\PropertyAudience;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/** Manage lease templates and their clauses (brief §8). Admins and property staff. */
class LeaseTemplateController extends Controller
{
    public function __construct(private PropertyAudience $audience)
    {
    }

    public function index(Request $request)
    {
        $this->authorizeStaff($request);
        $ids = $this->audience->accessiblePropertyIds($request->user());

        return view('contracts.templates.index', [
            'templates' => LeaseTemplate::with('property:id,name')
                ->when($ids !== null, fn ($q) => $q->where(fn ($w) => $w->whereNull('property_id')->orWhereIn('property_id', $ids)))
                ->orderByDesc('is_default')->orderBy('name')->get(),
        ]);
    }

    public function create(Request $request)
    {
        $this->authorizeStaff($request);
        $default = LeaseTemplate::where('is_default', true)->first();

        return view('contracts.templates.form', [
            'template' => new LeaseTemplate(['clauses' => $default?->clauses ?? [['title' => '', 'body' => '']]]),
            'properties' => $this->properties($request),
        ]);
    }

    public function store(Request $request)
    {
        $this->authorizeStaff($request);
        $template = LeaseTemplate::create($this->validated($request) + ['created_by' => $request->user()->id]);
        $this->syncDefault($template);

        return redirect()->route('lease-templates.index')->with('success', 'Template "' . $template->name . '" saved.');
    }

    public function edit(Request $request, LeaseTemplate $leaseTemplate)
    {
        $this->authorizeTemplate($request, $leaseTemplate);

        return view('contracts.templates.form', ['template' => $leaseTemplate, 'properties' => $this->properties($request)]);
    }

    public function update(Request $request, LeaseTemplate $leaseTemplate)
    {
        $this->authorizeTemplate($request, $leaseTemplate);
        $leaseTemplate->update($this->validated($request));
        $this->syncDefault($leaseTemplate);

        return redirect()->route('lease-templates.index')->with('success', 'Template updated.');
    }

    public function destroy(Request $request, LeaseTemplate $leaseTemplate)
    {
        $this->authorizeTemplate($request, $leaseTemplate);
        $leaseTemplate->delete(); // contracts keep their own copy of the text

        return back()->with('success', 'Template deleted.');
    }

    private function validated(Request $request): array
    {
        $ids = $this->audience->accessiblePropertyIds($request->user());

        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:1000'],
            'property_id' => ['nullable', 'integer', Rule::in($ids === null ? Property::pluck('id')->all() : $ids->all())],
            'is_default' => ['nullable', 'boolean'],
            'clauses' => ['required', 'array', 'min:1', 'max:60'],
            'clauses.*.title' => ['nullable', 'string', 'max:150'],
            'clauses.*.body' => ['nullable', 'string', 'max:10000'],
        ]);

        $data['clauses'] = collect($data['clauses'])
            ->filter(fn ($c) => filled($c['title'] ?? null) && filled($c['body'] ?? null))
            ->map(fn ($c) => ['title' => trim($c['title']), 'body' => trim($c['body'])])->values()->all();
        if (! $data['clauses']) {
            throw \Illuminate\Validation\ValidationException::withMessages(['clauses' => 'Add at least one clause with a title and text.']);
        }

        // Only admins can set the platform-wide default.
        $data['is_default'] = $request->user()->isAdmin() && $request->boolean('is_default');
        if ($ids !== null && empty($data['property_id'])) {
            // Managers' templates are tied to one of their properties, not shared platform-wide.
            $data['property_id'] = $ids->first();
        }

        return $data;
    }

    private function syncDefault(LeaseTemplate $template): void
    {
        if ($template->is_default) {
            LeaseTemplate::whereKeyNot($template->id)->update(['is_default' => false]);
        }
    }

    private function properties(Request $request)
    {
        $ids = $this->audience->accessiblePropertyIds($request->user());

        return Property::when($ids !== null, fn ($q) => $q->whereIn('id', $ids))->orderBy('name')->get(['id', 'name']);
    }

    private function authorizeStaff(Request $request): void
    {
        abort_unless($this->audience->isStaff($request->user()), 403);
    }

    private function authorizeTemplate(Request $request, LeaseTemplate $template): void
    {
        $user = $request->user();
        abort_unless($user->isAdmin() || ($template->property_id && $this->audience->canManageProperty($user, $template->property_id)), 403);
    }
}
