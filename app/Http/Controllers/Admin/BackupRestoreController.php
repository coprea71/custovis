<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\RestoreBackupRequest;
use App\Models\AuditLog;
use App\Services\Backup\BackupService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use RuntimeException;

/**
 * Plain form upload instead of Livewire, whose temporary uploads are capped
 * far below the size of a database backup.
 */
class BackupRestoreController extends Controller
{
    public function __invoke(RestoreBackupRequest $request, BackupService $backups): RedirectResponse
    {
        set_time_limit(0);
        $user = $request->user();

        try {
            $backups->restore($request->file('archive')->getRealPath(), $request->file('password_file')->get());
        } catch (RuntimeException $e) {
            return back()->withErrors(['archive' => $e->getMessage()]);
        }

        // The restored database has its own users and sessions, so the current login is gone.
        AuditLog::record('system.backup_restored', null, null, null, ['by_email' => $user->email]);
        Auth::logout();
        $request->session()->invalidate();

        return redirect()->route('login')->with('backup-restored', true);
    }
}
