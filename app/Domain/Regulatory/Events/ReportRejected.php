<?php

namespace App\Domain\Regulatory\Events;

use App\Domain\Regulatory\Models\RegulatoryReport;
use App\Domain\Regulatory\Models\RegulatoryFilingRecord;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ReportRejected
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public RegulatoryReport $report;
    public RegulatoryFilingRecord $filing;

    public function __construct(RegulatoryReport $report, RegulatoryFilingRecord $filing)
    {
        $this->report = $report;
        $this->filing = $filing;
    }

    /**
     * Get the tags that should be assigned to the event
     */
    public function tags(): array
    {
        return [
            'regulatory',
            'report_rejected',
            'report:' . $this->report->report_id,
            'type:' . $this->report->report_type,
            'jurisdiction:' . $this->report->jurisdiction,
            'filing:' . $this->filing->filing_id,
        ];
    }
}
