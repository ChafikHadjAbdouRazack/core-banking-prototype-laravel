@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <h1 class="mb-4">Transaction Status Tracking</h1>
            
            <!-- Filters -->
            <div class="card mb-4">
                <div class="card-body">
                    <form method="GET" action="{{ route('transactions.status') }}" class="row g-3">
                        <div class="col-md-2">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select">
                                <option value="all" {{ request('status') == 'all' ? 'selected' : '' }}>All</option>
                                <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                                <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>Completed</option>
                                <option value="failed" {{ request('status') == 'failed' ? 'selected' : '' }}>Failed</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Type</label>
                            <select name="type" class="form-select">
                                <option value="all" {{ request('type') == 'all' ? 'selected' : '' }}>All</option>
                                <option value="deposit" {{ request('type') == 'deposit' ? 'selected' : '' }}>Deposit</option>
                                <option value="withdrawal" {{ request('type') == 'withdrawal' ? 'selected' : '' }}>Withdrawal</option>
                                <option value="transfer" {{ request('type') == 'transfer' ? 'selected' : '' }}>Transfer</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Account</label>
                            <select name="account" class="form-select">
                                <option value="all">All Accounts</option>
                                @foreach($accounts as $account)
                                    <option value="{{ $account->uuid }}" {{ request('account') == $account->uuid ? 'selected' : '' }}>
                                        {{ $account->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">From Date</label>
                            <input type="date" name="date_from" class="form-control" value="{{ request('date_from') }}">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">To Date</label>
                            <input type="date" name="date_to" class="form-control" value="{{ request('date_to') }}">
                        </div>
                        <div class="col-md-1 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary w-100">Filter</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Statistics -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">Total Transactions</h5>
                            <h2>{{ $statistics->total ?? 0 }}</h2>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">Success Rate</h5>
                            <h2>{{ $statistics->success_rate ?? 0 }}%</h2>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">Pending</h5>
                            <h2>{{ ($statistics->pending ?? 0) + ($statistics->processing ?? 0) }}</h2>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">Avg Completion Time</h5>
                            <h2>{{ $statistics->avg_completion_time_formatted ?? 'N/A' }}</h2>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Pending Transactions -->
            @if(count($pendingTransactions) > 0)
            <div class="card mb-4">
                <div class="card-header">
                    <h3>Pending Transactions</h3>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Type</th>
                                    <th>Account</th>
                                    <th>Amount</th>
                                    <th>Status</th>
                                    <th>Progress</th>
                                    <th>Created</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($pendingTransactions as $transaction)
                                <tr>
                                    <td>{{ substr($transaction->id, 0, 8) }}...</td>
                                    <td>{{ ucfirst($transaction->type) }}</td>
                                    <td>{{ $transaction->account_name }}</td>
                                    <td>${{ number_format($transaction->amount / 100, 2) }}</td>
                                    <td>
                                        <span class="badge bg-warning">{{ ucfirst($transaction->status) }}</span>
                                    </td>
                                    <td>
                                        <div class="progress">
                                            <div class="progress-bar" style="width: {{ $transaction->progress_percentage }}%"></div>
                                        </div>
                                    </td>
                                    <td>{{ \Carbon\Carbon::parse($transaction->created_at)->diffForHumans() }}</td>
                                    <td>
                                        <a href="{{ route('transactions.status.show', $transaction->id) }}" class="btn btn-sm btn-info">View</a>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            @endif

            <!-- Completed Transactions -->
            <div class="card">
                <div class="card-header">
                    <h3>Recent Transactions</h3>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Type</th>
                                    <th>Account</th>
                                    <th>Amount</th>
                                    <th>Status</th>
                                    <th>Created</th>
                                    <th>Completed</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($completedTransactions as $transaction)
                                <tr>
                                    <td>{{ substr($transaction->id, 0, 8) }}...</td>
                                    <td>{{ ucfirst($transaction->type) }}</td>
                                    <td>{{ $transaction->account_name }}</td>
                                    <td>${{ number_format($transaction->amount / 100, 2) }}</td>
                                    <td>
                                        @if($transaction->status == 'completed')
                                            <span class="badge bg-success">Completed</span>
                                        @elseif($transaction->status == 'failed')
                                            <span class="badge bg-danger">Failed</span>
                                        @else
                                            <span class="badge bg-secondary">{{ ucfirst($transaction->status) }}</span>
                                        @endif
                                    </td>
                                    <td>{{ \Carbon\Carbon::parse($transaction->created_at)->format('Y-m-d H:i') }}</td>
                                    <td>{{ \Carbon\Carbon::parse($transaction->updated_at)->format('Y-m-d H:i') }}</td>
                                    <td>
                                        <a href="{{ route('transactions.status.show', $transaction->id) }}" class="btn btn-sm btn-info">View</a>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection