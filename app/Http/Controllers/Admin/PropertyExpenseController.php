<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Property;
use App\Models\PropertyExpense;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class PropertyExpenseController extends Controller
{
    private function categories(): array
    {
        return [
            'Maintenance',
            'Repairs',
            'Utilities',
            'Cleaning',
            'Security',
            'Insurance',
            'Property Tax',
            'Management Fees',
            'Gardening',
            'Supplies',
            'Salaries',
            'Other',
        ];
    }

    public function index(Request $request)
    {
        $query = PropertyExpense::with('property')
            ->when($request->filled('property_id'), function ($query) use ($request) {
                $query->where('property_id', $request->integer('property_id'));
            })
            ->when($request->filled('category'), function ($query) use ($request) {
                $query->where('category', $request->category);
            })
            ->when($request->filled('currency'), function ($query) use ($request) {
                $query->where('currency', $request->currency);
            })
            ->when($request->filled('status'), function ($query) use ($request) {
                $query->where('status', $request->status);
            })
            ->when($request->filled('from_date'), function ($query) use ($request) {
                $query->whereDate('expense_date', '>=', $request->from_date);
            })
            ->when($request->filled('to_date'), function ($query) use ($request) {
                $query->whereDate('expense_date', '<=', $request->to_date);
            })
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->search;

                $query->where(function ($subQuery) use ($search) {
                    $subQuery->where('expense_number', 'like', "%{$search}%")
                        ->orWhere('title', 'like', "%{$search}%")
                        ->orWhere('vendor_name', 'like', "%{$search}%")
                        ->orWhere('reference_number', 'like', "%{$search}%");
                });
            });

        $expenses = $query->latest('expense_date')
            ->paginate(15)
            ->withQueryString();

        // Keep currencies separate; never add RWF to USD.
        $totals = PropertyExpense::query()
            ->when($request->filled('property_id'), fn ($q) =>
                $q->where('property_id', $request->integer('property_id'))
            )
            ->when($request->filled('category'), fn ($q) =>
                $q->where('category', $request->category)
            )
            ->when($request->filled('status'), fn ($q) =>
                $q->where('status', $request->status)
            )
            ->when($request->filled('from_date'), fn ($q) =>
                $q->whereDate('expense_date', '>=', $request->from_date)
            )
            ->when($request->filled('to_date'), fn ($q) =>
                $q->whereDate('expense_date', '<=', $request->to_date)
            )
            ->selectRaw('currency, SUM(amount) as total')
            ->groupBy('currency')
            ->pluck('total', 'currency');

        $properties = Property::orderBy('name')->get(['id', 'name', 'property_code']);

        return view('admin.property-expenses.index', [
            'expenses' => $expenses,
            'properties' => $properties,
            'categories' => $this->categories(),
            'totals' => $totals,
        ]);
    }

    public function create()
    {
        return view('admin.property-expenses.create', [
            'properties' => Property::orderBy('name')->get(['id', 'name', 'property_code']),
            'categories' => $this->categories(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $this->validateExpense($request);

        $validated['expense_number'] = $this->generateExpenseNumber();
        $validated['recorded_by'] = Auth::id();

        PropertyExpense::create($validated);

        return redirect()
            ->route('admin.property-expenses.index')
            ->with('success', 'Property expense recorded successfully.');
    }

    public function show(PropertyExpense $propertyExpense)
    {
        $propertyExpense->load(['property', 'recorder']);

        return view('admin.property-expenses.show', compact('propertyExpense'));
    }

    public function edit(PropertyExpense $propertyExpense)
    {
        return view('admin.property-expenses.edit', [
            'expense' => $propertyExpense,
            'properties' => Property::orderBy('name')->get(['id', 'name', 'property_code']),
            'categories' => $this->categories(),
        ]);
    }

    public function update(Request $request, PropertyExpense $propertyExpense)
    {
        $validated = $this->validateExpense($request);

        // Preserve the original expense number and recorder.
        unset($validated['expense_number'], $validated['recorded_by']);

        $propertyExpense->update($validated);

        return redirect()
            ->route('admin.property-expenses.index')
            ->with('success', 'Property expense updated successfully.');
    }

    public function destroy(PropertyExpense $propertyExpense)
    {
        $propertyExpense->delete();

        return redirect()
            ->route('admin.property-expenses.index')
            ->with('success', 'Property expense deleted successfully.');
    }

    private function validateExpense(Request $request): array
    {
        return $request->validate([
            'property_id' => [
                'required',
                'integer',
                Rule::exists('properties', 'id'),
            ],
            'category' => ['required', Rule::in($this->categories())],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'amount' => ['required', 'numeric', 'gt:0', 'max:9999999999999'],
            'currency' => ['required', Rule::in(['RWF', 'USD'])],
            'expense_date' => ['required', 'date'],
            'payment_method' => [
                'nullable',
                Rule::in(['Cash', 'Bank Transfer', 'Mobile Money', 'Card', 'Other']),
            ],
            'vendor_name' => ['nullable', 'string', 'max:255'],
            'reference_number' => ['nullable', 'string', 'max:255'],
            'status' => ['required', Rule::in(['paid', 'pending', 'cancelled'])],
            'notes' => ['nullable', 'string'],
        ]);
    }

    private function generateExpenseNumber(): string
    {
        do {
            $number = 'EXP-' . now()->format('YmdHis') . '-' . strtoupper(
                \Illuminate\Support\Str::random(5)
            );
        } while (PropertyExpense::where('expense_number', $number)->exists());

        return $number;
    }
}