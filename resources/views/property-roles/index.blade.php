@extends('layouts.app')

@section('title', 'Agents & accountants')
@section('page_heading', 'Agents & accountants')
@section('page_subtitle', 'Who can see which property’s finances or market its units, and the commissions owed to agents.')

@section('content')
<div class="row g-4">
    <div class="col-xl-7">
        <div class="card border-0 shadow-sm mb-4" style="border-radius:14px;">
            <div class="card-header bg-white fw-bold" style="color:var(--terra-navy);">Assignments</div>
            <div class="table-responsive">
                <table class="table table-sm align-middle mb-0">
                    <thead class="table-light small"><tr><th>Person</th><th>Role</th><th>Property</th><th>Commission rate</th><th></th></tr></thead>
                    <tbody>
                        @forelse($assignments as $a)
                            <tr>
                                <td>{{ $a->user?->name }}<div class="small text-secondary">{{ $a->user?->email }}</div></td>
                                <td><span class="badge rounded-pill {{ $a->role === 'agent' ? 'text-bg-info' : 'text-bg-secondary' }}">{{ $a->role === 'agent' ? 'Agent' : 'Accountant' }}</span></td>
                                <td>{{ $a->property?->name }}</td>
                                <td>{{ $a->commission_rate !== null ? rtrim(rtrim(number_format((float) $a->commission_rate, 2), '0'), '.') . '%' : '—' }}</td>
                                <td class="text-end text-nowrap">
                                    <x-hr-add-button :user="$a->user" :property-id="$a->property_id" class="btn btn-sm btn-light me-2" />
                                    @if($isAdmin)
                                        <form method="POST" action="{{ route('property-roles.destroy', $a) }}" class="d-inline" onsubmit="return confirm('Remove this assignment?')">@csrf @method('DELETE')
                                            <button class="btn btn-sm btn-link text-danger p-0">Remove</button></form>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-center text-secondary py-4">No agents or accountants assigned yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card border-0 shadow-sm" style="border-radius:14px;">
            <div class="card-header bg-white fw-bold" style="color:var(--terra-navy);">Agent commissions</div>
            <div class="table-responsive">
                <table class="table table-sm align-middle mb-0">
                    <thead class="table-light small"><tr><th>Agent</th><th>Property / lease</th><th class="text-end">Amount</th><th>Status</th><th></th></tr></thead>
                    <tbody>
                        @forelse($commissions as $c)
                            <tr>
                                <td>{{ $c->agent?->name }}<div class="small text-secondary">{{ $c->created_at->format('d M Y') }}</div></td>
                                <td class="small">{{ $c->property?->name }}{{ $c->lease ? ' · ' . $c->lease->lease_number : '' }}<div class="text-secondary">{{ $c->description }}</div></td>
                                <td class="text-end fw-semibold">{{ $c->currency }} {{ number_format((float) $c->amount) }}</td>
                                <td><span class="badge rounded-pill {{ ['paid' => 'text-bg-success', 'approved' => 'text-bg-info', 'cancelled' => 'text-bg-secondary'][$c->status] ?? 'text-bg-warning' }}">{{ \App\Models\AgentCommission::STATUSES[$c->status] ?? $c->status }}</span></td>
                                <td>
                                    @if(in_array($c->status, ['pending', 'approved'], true))
                                        <form method="POST" action="{{ route('property-roles.commissions.update', $c) }}" class="d-flex gap-1">
                                            @csrf @method('PATCH')
                                            @if($c->status === 'pending')
                                                <button name="status" value="approved" class="btn btn-sm btn-light">Approve</button>
                                            @endif
                                            <input type="text" name="payment_reference" class="form-control form-control-sm" placeholder="Payment ref" style="width:110px">
                                            <button name="status" value="paid" class="btn btn-sm btn-success">Paid</button>
                                            <button name="status" value="cancelled" class="btn btn-sm btn-link text-danger p-0" onclick="return confirm('Cancel this commission?')">✕</button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-center text-secondary py-4">No commissions recorded.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($commissions->hasPages())<div class="p-3 border-top">{{ $commissions->links() }}</div>@endif
        </div>
    </div>

    <div class="col-xl-5">
        @if($errors->any())<div class="alert alert-danger small">{{ $errors->first() }}</div>@endif
        @if($isAdmin)
            <div class="card border-0 shadow-sm mb-4" style="border-radius:14px;">
                <div class="card-body">
                    <h2 class="h6 fw-bold mb-3" style="color:var(--terra-navy);">Assign to a property</h2>
                    @if($people->isEmpty())
                        <p class="small text-secondary mb-0">Create a user with the Accountant or Agent role in <a href="{{ route('admin.users.index') }}">User management</a> first.</p>
                    @else
                        <form method="POST" action="{{ route('property-roles.store') }}" class="d-grid gap-2">
                            @csrf
                            <select name="user_id" class="form-select form-select-sm" required aria-label="Person">
                                @foreach($people as $u)<option value="{{ $u->id }}">{{ $u->name }} ({{ $u->role === 'agent' ? 'Agent' : 'Accountant' }})</option>@endforeach
                            </select>
                            <select name="property_id" class="form-select form-select-sm" required aria-label="Property">
                                @foreach($properties as $p)<option value="{{ $p->id }}">{{ $p->name }}</option>@endforeach
                            </select>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text">Agent commission</span>
                                <input type="number" name="commission_rate" min="0" max="100" step="0.5" class="form-control" placeholder="e.g. 50">
                                <span class="input-group-text">% of 1 month</span>
                            </div>
                            <button class="btn btn-sm btn-dark rounded-pill">Assign</button>
                        </form>
                    @endif
                </div>
            </div>
        @endif

        <div class="card border-0 shadow-sm" style="border-radius:14px;">
            <div class="card-body">
                <h2 class="h6 fw-bold mb-3" style="color:var(--terra-navy);">Record a commission</h2>
                @if($agents->isEmpty())
                    <p class="small text-secondary mb-0">No agents are assigned to your properties.</p>
                @else
                    <form method="POST" action="{{ route('property-roles.commissions.store') }}" class="d-grid gap-2">
                        @csrf
                        <select name="agent_role_id" class="form-select form-select-sm" required aria-label="Agent">
                            @foreach($agents as $a)<option value="{{ $a->id }}">{{ $a->user?->name }} · {{ $properties->firstWhere('id', $a->property_id)?->name }}</option>@endforeach
                        </select>
                        <select name="lease_id" class="form-select form-select-sm" aria-label="Lease">
                            <option value="">No specific lease</option>
                            @foreach($leases as $l)<option value="{{ $l->id }}">{{ $l->lease_number }} · {{ $l->tenant?->full_name }}</option>@endforeach
                        </select>
                        <input type="number" name="amount" min="0" class="form-control form-control-sm" placeholder="Amount (leave empty to use the agent's rate × rent)">
                        <input type="text" name="description" maxlength="255" class="form-control form-control-sm" placeholder="Description (optional)">
                        <button class="btn btn-sm rounded-pill text-white" style="background:var(--terra-orange);">Record commission</button>
                    </form>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
