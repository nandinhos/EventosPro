<?php

namespace App\Http\Controllers;

use App\Services\BackupDataService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class BackupToolController extends Controller
{
    public function __construct(
        protected BackupDataService $backupService
    ) {}

    public function index()
    {
        $backups = $this->backupService->listBackups();

        return view('backup-tool.index', compact('backups'));
    }

    public function create(Request $request)
    {
        $result = $this->backupService->createDataBackup();

        if ($request->ajax()) {
            return response()->json($result);
        }

        if ($result['success']) {
            return redirect()->back()->with('success', $result['message']);
        }

        return redirect()->back()->with('error', $result['message']);
    }

    public function restore(Request $request, string $filename)
    {
        if (! $this->backupService->isValidFilename($filename)) {
            if ($request->ajax()) {
                return response()->json(['success' => false, 'message' => 'Nome de arquivo inválido'], 400);
            }

            return redirect()->back()->with('error', 'Nome de arquivo inválido');
        }

        Log::info("[BackupTool] Iniciando restauração do arquivo: {$filename}");

        $result = $this->backupService->restoreDataBackup($filename);

        if ($request->ajax()) {
            return response()->json($result);
        }

        if ($result['success']) {
            return redirect()->back()->with('success', $result['message']);
        }

        return redirect()->back()->with('error', $result['message']);
    }

    public function download(string $filename)
    {
        if (! $this->backupService->isValidFilename($filename)) {
            abort(400, 'Nome de arquivo inválido');
        }

        $filepath = $this->backupService->getBackupFilePath($filename);

        if (! $filepath) {
            abort(404, 'Arquivo não encontrado');
        }

        return response()->download($filepath, $filename, [
            'Content-Type' => 'application/sql',
        ]);
    }

    public function upload(Request $request)
    {
        $request->validate([
            'backup_file' => 'required|file|mimes:sql,txt|max:51200',
        ]);

        $file = $request->file('backup_file');
        $result = $this->backupService->uploadBackup($file);

        if ($request->ajax()) {
            return response()->json($result);
        }

        if ($result['success']) {
            return redirect()->back()->with('success', $result['message']);
        }

        return redirect()->back()->with('error', $result['message']);
    }

    public function destroy(string $filename)
    {
        if (! $this->backupService->isValidFilename($filename)) {
            return response()->json(['success' => false, 'message' => 'Nome de arquivo inválido'], 400);
        }

        $result = $this->backupService->deleteBackup($filename);

        return response()->json(['success' => $result]);
    }
}
