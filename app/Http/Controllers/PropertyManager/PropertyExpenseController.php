<?php

namespace App\Http\Controllers\PropertyManager;

use App\Http\Controllers\Controller;
use App\Models\Property;
use App\Models\PropertyExpense;
use App\Models\PropertyManager;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class PropertyExpenseController extends Controller
{
    /**
     * Get the authenticated user's active property manager account.
     */
    private function manager(): ?PropertyManager
    {
        return auth()->user()
            ?->propertyManager()
            ->where('status', 'active')
            ->first();
    }

    /**
     * Get the authenticated user's property owner account.
     */
    private function owner()
    {
        return auth()->user()?->propertyOwner;
    }

    /**
     * Ensure the user is an active manager or property owner.
     */
    private function authorizeExpenseAccess(): void
    {
        abort_unless(
            $this->manager() || $this->owner(),
            403,
            'An active property manager or property owner account is required.'
        );
    }

    /**
     * Properties accessible to the authenticated user.
     */
    private function accessibleProperties(): Builder
    {
        $this->authorizeExpenseAccess();

        $manager = $this->manager();
        $owner = $this->owner();

        return Property::query()
            ->where(function (Builder $query) use ($manager, $owner) {
                if ($owner) {
                    $query->where('property_owner_id', $owner->id);
                }

                if ($manager) {
                    $method = $owner ? 'orWhereHas' : 'whereHas';

                    $query->{$method}(
                        'activeManagers',
                        function (Builder $managerQuery) use ($manager) {
                            $managerQuery->where(
                                'property_managers.id',
                                $manager->id
                            );
                        }
                    );
                }
            });
    }

    /**
     * Expenses belonging to properties accessible to the current user.
     */
    private function expensesQuery(): Builder
    {
        $propertyIds = $this->accessibleProperties()
            ->select('properties.id');

        return PropertyExpense::query()
            ->with(['property', 'recorder'])
            ->whereIn('property_id', $propertyIds);
    }

    /**
     * Retrieve an expense only if it belongs to an accessible property.
     */
    private function scopedExpense(int $id): PropertyExpense
    {
        return $this->expensesQuery()->findOrFail($id);
    }

    /**
     * Expense categories used by the forms.
     */
    private function categories(): array
    {
        return [
            'maintenance' => 'Maintenance',
            'repairs' => 'Repairs',
            'utilities' => 'Utilities',
            'cleaning' => 'Cleaning',
            'security' => 'Security',
            'insurance' => 'Insurance',
            'taxes' => 'Taxes',
            'management_fee' => 'Management Fee',
            'landscaping' => 'Landscaping',
            'other' => 'Other',
        ];
    }

    /**
     * Display expenses and summary statistics.
     */
    public function index(Request $request)
    {
        $query = $this->expensesQuery();

        if ($search = trim($request->input('search', ''))) {
            $query->where(function (Builder $q) use ($search) {
                $q->where('expense_number', 'like', "%{$search}%")
                    ->orWhere('title', 'like', "%{$search}%")
                    ->orWhere('vendor_name', 'like', "%{$search}%")
                    ->orWhere('reference_number', 'like', "%{$search}%")
                    ->orWhereHas('property', function (Builder $property) use ($search) {
                        $property->where('name', 'like', "%{$search}%");
                    });
            });
        }

        if ($request->filled('property_id')) {
            $query->where('property_id', $request->input('property_id'));
        }

        if ($request->filled('category')) {
            if (array_key_exists($request->input('category'), $this->categories())) {
                $query->where('category', $request->input('category'));
            }
        }

        if ($request->filled('status')) {
            if (in_array($request->input('status'), ['paid', 'pending', 'cancelled'], true)) {
                $query->where('status', $request->input('status'));
            }
        }

        if ($request->filled('date_from')) {
            $query->whereDate('expense_date', '>=', $request->input('date_from'));
        }

        if ($request->filled('date_to')) {
            $query->whereDate('expense_date', '<=', $request->input('date_to'));
        }

        $expenses = $query
            ->orderByDesc('expense_date')
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        $summaryQuery = $this->expensesQuery();

        $summary = [
            'all' => (clone $summaryQuery)->count(),

            'total_amount' => (clone $summaryQuery)
                ->where('status', '!=', 'cancelled')
                ->sum('amount'),

            'paid_amount' => (clone $summaryQuery)
                ->where('status', 'paid')
                ->sum('amount'),

            'pending_amount' => (clone $summaryQuery)
                ->where('status', 'pending')
                ->sum('amount'),

            'paid_count' => (clone $summaryQuery)
                ->where('status', 'paid')
                ->count(),

            'pending_count' => (clone $summaryQuery)
                ->where('status', 'pending')
                ->count(),
        ];

        $properties = $this->accessibleProperties()
            ->orderBy('name')
            ->get(['id', 'name']);

        $categories = $this->categories();

        return view('property-portal.expenses.index', compact(
            'expenses',
            'summary',
            'properties',
            'categories'
        ));
    }

    /**
     * Show the create expense form.
     */
    public function create()
    {
        $properties = $this->accessibleProperties()
            ->orderBy('name')
            ->get(['id', 'name']);

        abort_if($properties->isEmpty(), 403, 'You do not have access to any properties.');

        $categories = $this->categories();

        return view('property-portal.expenses.create', compact(
            'properties',
            'categories'
        ));
    }

    /**
     * Store a new property expense.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'property_id' => [
                'required',
                'integer',
                Rule::exists('properties', 'id')->where(function ($query) {
                    $query->whereIn(
                        'id',
                        $this->accessibleProperties()->select('properties.id')
                    );
                }),
            ],

            'category' => [
                'required',
                Rule::in(array_keys($this->categories())),
            ],

            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],

            'amount' => ['required', 'numeric', 'gt:0', 'max:999999999999'],
            'currency' => ['required', Rule::in(['RWF', 'USD'])],

            'expense_date' => ['required', 'date', 'before_or_equal:today'],

            'payment_method' => [
                'nullable',
                Rule::in([
                    'cash',
                    'bank_transfer',
                    'mobile_money',
                    'card',
                    'other',
                ]),
            ],

            'vendor_name' => ['nullable', 'string', 'max:255'],
            'reference_number' => ['nullable', 'string', 'max:255'],

            'status' => [
                'required',
                Rule::in(['paid', 'pending', 'cancelled']),
            ],

            'notes' => ['nullable', 'string', 'max:5000'],
        ]);

        $expense = DB::transaction(function () use ($validated, $request) {
            return PropertyExpense::create([
                ...$validated,
                'expense_number' => $this->generateExpenseNumber(),
                'recorded_by' => $request->user()->id,
            ]);
        });

        return redirect()
            ->route('property-manager.expenses.show', $expense->id)
            ->with('success', 'Property expense recorded successfully.');
    }

    /**
     * Display a single expense.
     */
    public function show(int $expense)
    {
        $expense = $this->scopedExpense($expense);

        $expense->load(['property', 'recorder']);

        $categories = $this->categories();

        return view('property-portal.expenses.show', compact(
            'expense',
            'categories'
        ));
    }

    /**
     * Show the edit expense form.
     */
    public function edit(int $expense)
    {
        $expense = $this->scopedExpense($expense);

        $properties = $this->accessibleProperties()
            ->orderBy('name')
            ->get(['id', 'name']);

        $categories = $this->categories();

        return view('property-portal.expenses.edit', compact(
            'expense',
            'properties',
            'categories'
        ));
    }

    /**
     * Update an existing expense.
     */
    public function update(Request $request, int $expense)
    {
        $expense = $this->scopedExpense($expense);

        $validated = $request->validate([
            'property_id' => [
                'required',
                'integer',
                Rule::exists('properties', 'id')->where(function ($query) {
                    $query->whereIn(
                        'id',
                        $this->accessibleProperties()->select('properties.id')
                    );
                }),
            ],

            'category' => [
                'required',
                Rule::in(array_keys($this->categories())),
            ],

            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],

            'amount' => ['required', 'numeric', 'gt:0', 'max:999999999999'],
            'currency' => ['required', Rule::in(['RWF', 'USD'])],

            'expense_date' => ['required', 'date', 'before_or_equal:today'],

            'payment_method' => [
                'nullable',
                Rule::in([
                    'cash',
                    'bank_transfer',
                    'mobile_money',
                    'card',
                    'other',
                ]),
            ],

            'vendor_name' => ['nullable', 'string', 'max:255'],
            'reference_number' => ['nullable', 'string', 'max:255'],

            'status' => [
                'required',
                Rule::in(['paid', 'pending', 'cancelled']),
            ],

            'notes' => ['nullable', 'string', 'max:5000'],
        ]);

        $expense->update($validated);

        return redirect()
            ->route('property-manager.expenses.show', $expense->id)
            ->with('success', 'Property expense updated successfully.');
    }

    /**
     * Soft-delete an expense.
     */
    public function destroy(int $expense)
    {
        $expense = $this->scopedExpense($expense);

        $expense->delete();

        return redirect()
            ->route('property-manager.expenses.index')
            ->with('success', 'Property expense deleted successfully.');
    }

    /**
     * Generate a unique expense number.
     */
    private function generateExpenseNumber(): string
    {
        do {
            $number = 'EXP-' . strtoupper(Str::random(10));
        } while (
            PropertyExpense::where('expense_number', $number)->exists()
        );

        return $number;
    }
}