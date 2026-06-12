<?php

namespace App\Modules\Audit\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Audit\Models\AuditLog;
use Illuminate\Http\Request;

class AuditController extends Controller
{
    public function index(Request $request)
    {
        $event  = $request->get('event');
        $model  = $request->get('model');
        $userId = $request->get('user_id');

        $logs = AuditLog::with('user')
            ->when($event,  fn($q) => $q->where('event', $event))
            ->when($model,  fn($q) => $q->where('auditable_type', 'like', "%{$model}%"))
            ->when($userId, fn($q) => $q->where('user_id', $userId))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('audit.index', compact('logs', 'event', 'model', 'userId'));
    }
}
