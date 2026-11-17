<div class="table-responsive">
    <table class="table table-sm align-middle mb-0">
        <thead class="table-light small"><tr><th>Date</th><th>Applicant</th><th>Property / unit</th><th>Move-in</th><th>Status</th></tr></thead>
        <tbody>
            @forelse($applications as $a)
                <tr>
                    <td class="small">{{ ($a->application_date ?? $a->created_at)?->format('d M Y') }}</td>
                    <td>{{ $a->tenant?->full_name }}<div class="small text-secondary">{{ $a->tenant?->phone }}</div></td>
                    <td class="small">{{ $a->unit?->building?->property?->name }} · Unit {{ $a->unit?->unit_number }}</td>
                    <td class="small">{{ $a->desired_move_in_date?->format('d M Y') }}</td>
                    <td><span class="badge rounded-pill {{ ['approved' => 'text-bg-success', 'rejected' => 'text-bg-danger'][$a->status] ?? 'text-bg-warning' }}">{{ ucwords(str_replace('_', ' ', $a->status)) }}</span></td>
                </tr>
            @empty
                <tr><td colspan="5" class="text-center text-secondary py-4">No applications yet.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
