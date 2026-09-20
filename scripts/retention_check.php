<?php

use Carbon\Carbon;

$cutoffEnv = getenv('RETENTION_CUTOFF') ?: null;
$cutoff = $cutoffEnv ? Carbon::parse($cutoffEnv) : Carbon::today();

$result = [
    'cutoff' => $cutoff->toDateTimeString(),
    'personal' => \App\Models\Candidate::withTrashed()->whereNull('disociado_at')->where('created_at', '<', $cutoff)->count(),
    'psico_huerfanos' => \App\Models\TestResult::whereNotIn('candidate_id', \App\Models\Candidate::withTrashed()->select('id'))->where('created_at', '<', $cutoff)->count(),
    'psico_vencidos' => \App\Models\TestResult::where('created_at', '<', $cutoff)->count(),
    'actividad' => \App\Models\ActivityLog::where('created_at', '<', $cutoff)->count(),
    'tecnico_sesiones' => \App\Models\TestSession::withTrashed()->where('created_at', '<', $cutoff)->where(function($q){ $q->whereNotNull('ip_address')->orWhereNotNull('user_agent')->orWhereNotNull('browser_info'); })->count(),
    'tecnico_logs' => \App\Models\ActivityLog::where('created_at', '<', $cutoff)->where(function($q){ $q->whereNotNull('ip_address')->orWhereNotNull('user_agent'); })->count(),
];

echo json_encode($result, JSON_PRETTY_PRINT) . PHP_EOL;
