<?php

namespace App\Http\Controllers;

use App\Services\PropertyAudience;
use App\Services\ReportBuilder;
use Carbon\Carbon;
use Illuminate\Http\Request;

/** Report centre (brief §28) — exportable to Excel (CSV) and PDF (print). */
class ReportController extends Controller
{
    public function __construct(private ReportBuilder $reports, private PropertyAudience $audience)
    {
    }

    public function index(Request $request)
    {
        $this->propertyIds($request);

        return view('reports.index', ['groups' => ReportBuilder::REPORTS]);
    }

    public function show(Request $request, string $report)
    {
        abort_unless(ReportBuilder::title($report), 404);
        $ids = $this->propertyIds($request);

        $data = $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
            'property' => ['nullable', 'integer'],
        ]);
        $from = Carbon::parse($data['from'] ?? now()->startOfYear())->startOfDay();
        $to = Carbon::parse($data['to'] ?? now())->endOfDay();

        if (! empty($data['property'])) {
            abort_unless($ids === null || $ids->contains((int) $data['property']), 403);
            $ids = collect([(int) $data['property']]);
        }

        $result = $this->reports->build($report, $ids, $from, $to);

        if ($request->query('format') === 'csv') {
            return response()->streamDownload(function () use ($result) {
                $out = fopen('php://output', 'w');
                fwrite($out, "\xEF\xBB\xBF"); // UTF-8 BOM so Excel shows accents correctly
                fputcsv($out, array_values($result['columns']));
                foreach ($result['rows'] as $row) {
                    fputcsv($out, array_map(fn ($k) => $row[$k] ?? '', array_keys($result['columns'])));
                }
                if ($result['totals']) {
                    fputcsv($out, array_map(fn ($k) => $result['totals'][$k] ?? '', array_keys($result['columns'])));
                }
                fclose($out);
            }, $report . '-' . now()->format('Ymd') . '.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
        }

        $allowedIds = $this->propertyIds($request);

        return view('reports.show', [
            'key' => $report,
            'report' => $result,
            'from' => $from,
            'to' => $to,
            'properties' => \App\Models\Property::when($allowedIds !== null, fn ($q) => $q->whereIn('id', $allowedIds))->orderBy('name')->get(['id', 'name']),
        ]);
    }

    /** Admin → all; owners, managers and accountants → their properties. */
    private function propertyIds(Request $request)
    {
        $ids = $this->audience->financePropertyIds($request->user());
        abort_unless($ids === null || $ids->isNotEmpty(), 403);

        return $ids;
    }
}
