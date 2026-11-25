{{-- HR section tabs and the employer filter (property, or Terra internal for admins). --}}
@include('partials.sx-styles')
@php
    $hrTabs = [
        ['hr.dashboard', 'hr.dashboard', 'bi-grid-1x2', 'Overview'],
        ['hr.employees.index', 'hr.employees.*', 'bi-people', 'Employees'],
        ['hr.attendance.index', 'hr.attendance.*', 'bi-calendar-check', 'Attendance'],
        ['hr.leave.index', 'hr.leave.*', 'bi-airplane', 'Leave'],
        ['hr.payroll.index', 'hr.payroll.*', 'bi-cash-coin', 'Payroll'],
    ];
    $currentEmployer = request('employer');
@endphp

<div class="d-flex flex-wrap justify-content-between align-items-end gap-2 mb-3">
    <nav class="sx-chipbar mb-0" aria-label="HR sections">
        @foreach($hrTabs as [$route, $pattern, $icon, $label])
            <a href="{{ route($route, array_filter(['employer' => $currentEmployer])) }}"
               class="sx-chip {{ request()->routeIs($pattern) ? 'active' : '' }}"
               @if(request()->routeIs($pattern)) aria-current="page" @endif><i class="bi {{ $icon }} me-1"></i>{{ $label }}</a>
        @endforeach
    </nav>

    @if(($showEmployerFilter ?? true) && count($employers ?? []) > 1)
        <form method="GET" class="d-flex gap-2 align-items-center">
            @foreach(request()->except(['employer', 'page']) as $k => $v)
                @if(is_string($v))<input type="hidden" name="{{ $k }}" value="{{ $v }}">@endif
            @endforeach
            <label for="hrEmployer" class="small sx-muted text-nowrap">Employer</label>
            <select id="hrEmployer" name="employer" class="form-select form-select-sm" style="min-width:200px" onchange="this.form.submit()">
                <option value="">All employers</option>
                @foreach($employers as $value => $label)
                    @php $optionValue = $value === '' ? 'internal' : (string) $value; @endphp
                    <option value="{{ $optionValue }}" @selected($currentEmployer === $optionValue)>{{ $label }}</option>
                @endforeach
            </select>
        </form>
    @endif
</div>
