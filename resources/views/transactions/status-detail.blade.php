@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <h1 class="mb-4">Transaction Details</h1>
            
            <div class="card mb-4">
                <div class="card-header">
                    <h3>Transaction Information</h3>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>Transaction ID:</strong> {{ $transaction->id }}</p>
                            <p><strong>Type:</strong> {{ ucfirst($transaction->type) }}</p>
                            <p><strong>Account:</strong> {{ $transaction->account_name }}</p>
                            <p><strong>Amount:</strong> ${{ number_format($transaction->amount / 100, 2) }}</p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Status:</strong> 
                                @if($transaction->status == 'completed')
                                    <span class="badge bg-success">Completed</span>
                                @elseif($transaction->status == 'failed')
                                    <span class="badge bg-danger">Failed</span>
                                @elseif($transaction->status == 'pending')
                                    <span class="badge bg-warning">Pending</span>
                                @else
                                    <span class="badge bg-secondary">{{ ucfirst($transaction->status) }}</span>
                                @endif
                            </p>
                            <p><strong>Created:</strong> {{ \Carbon\Carbon::parse($transaction->created_at)->format('Y-m-d H:i:s') }}</p>
                            <p><strong>Last Updated:</strong> {{ \Carbon\Carbon::parse($transaction->updated_at)->format('Y-m-d H:i:s') }}</p>
                            @if(isset($transaction->reference))
                            <p><strong>Reference:</strong> {{ $transaction->reference }}</p>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <!-- Timeline -->
            <div class="card mb-4">
                <div class="card-header">
                    <h3>Transaction Timeline</h3>
                </div>
                <div class="card-body">
                    <div class="timeline">
                        @foreach($timeline as $event)
                        <div class="timeline-item">
                            <div class="timeline-marker 
                                @if($event['status'] == 'completed') bg-success
                                @elseif($event['status'] == 'active') bg-primary
                                @elseif($event['status'] == 'error') bg-danger
                                @else bg-secondary
                                @endif">
                            </div>
                            <div class="timeline-content">
                                <h5>{{ ucfirst($event['event']) }}</h5>
                                <p>{{ $event['description'] }}</p>
                                <small class="text-muted">{{ \Carbon\Carbon::parse($event['timestamp'])->format('Y-m-d H:i:s') }}</small>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <!-- Related Transactions -->
            @if(count($relatedTransactions) > 0)
            <div class="card">
                <div class="card-header">
                    <h3>Related Transactions</h3>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Type</th>
                                    <th>Transaction ID</th>
                                    <th>Status</th>
                                    <th>Amount</th>
                                    <th>Created</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($relatedTransactions as $related)
                                <tr>
                                    <td>{{ ucfirst($related['type']) }}</td>
                                    <td>{{ substr($related['transaction']->id, 0, 8) }}...</td>
                                    <td>
                                        @if($related['transaction']->status == 'completed')
                                            <span class="badge bg-success">Completed</span>
                                        @elseif($related['transaction']->status == 'failed')
                                            <span class="badge bg-danger">Failed</span>
                                        @else
                                            <span class="badge bg-secondary">{{ ucfirst($related['transaction']->status) }}</span>
                                        @endif
                                    </td>
                                    <td>${{ number_format($related['transaction']->amount / 100, 2) }}</td>
                                    <td>{{ \Carbon\Carbon::parse($related['transaction']->created_at)->format('Y-m-d H:i:s') }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            @endif
        </div>
    </div>
</div>

<style>
.timeline {
    position: relative;
    padding: 20px 0;
}
.timeline-item {
    position: relative;
    padding-left: 40px;
    margin-bottom: 20px;
}
.timeline-item:before {
    content: '';
    position: absolute;
    left: 15px;
    top: 25px;
    bottom: -25px;
    width: 2px;
    background: #e9ecef;
}
.timeline-item:last-child:before {
    display: none;
}
.timeline-marker {
    position: absolute;
    left: 10px;
    top: 5px;
    width: 12px;
    height: 12px;
    border-radius: 50%;
}
.timeline-content {
    background: #f8f9fa;
    padding: 15px;
    border-radius: 5px;
}
</style>
@endsection