<?php

namespace App\Http\Controllers\Admin\Configuracoes;

use App\Http\Controllers\Controller;
use App\Services\BackupService;
use Illuminate\Http\Request;

class BackupController extends Controller
{
    protected BackupService $backupService;

    public function __construct(BackupService $backupService)
    {
        $this->backupService = $backupService;
        $this->middleware(['auth', 'can:manage backups']);
    }

    /**
     * Exibe a página de gerenciamento de backups
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        $backups = $this->backupService->listBackups();

        return view('admin.configuracoes.backup.index', compact('backups'));
    }

    /**
     * Cria um novo backup
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        $result = $this->backupService->createBackup();

        if ($result['success']) {
            return redirect()
                ->route('admin.backup.index')
                ->with('success', 'Backup criado com sucesso: '.$result['filename']);
        }

        return redirect()
            ->route('admin.backup.index')
            ->with('error', 'Erro ao criar backup: '.$result['message']);
    }

    /**
     * Faz o download de um arquivo de backup
     *
     * @return \Symfony\Component\HttpFoundation\StreamedResponse|\Illuminate\Http\RedirectResponse
     */
    public function download(string $filename)
    {
        $filepath = $this->backupService->getBackupFilePath($filename);

        if (! $filepath) {
            return redirect()
                ->route('admin.backup.index')
                ->with('error', 'Arquivo de backup não encontrado');
        }

        return response()->streamDownload(function () use ($filepath) {
            $stream = fopen($filepath, 'r');
            fpassthru($stream);
            fclose($stream);
        }, $filename, [
            'Content-Type' => 'application/sql',
            'Content-Length' => filesize($filepath),
        ]);
    }

    /**
     * Remove um arquivo de backup
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(string $filename)
    {
        $result = $this->backupService->deleteBackup($filename);

        if ($result) {
            return redirect()
                ->route('admin.backup.index')
                ->with('success', 'Backup removido com sucesso');
        }

        return redirect()
            ->route('admin.backup.index')
            ->with('error', 'Erro ao remover backup');
    }

    /**
     * Restaura um backup do banco de dados
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function restore(string $filename)
    {
        $result = $this->backupService->restoreBackup($filename);

        if ($result['success']) {
            return redirect()
                ->route('admin.backup.index')
                ->with('success', 'Backup restaurado com sucesso: '.$filename);
        }

        return redirect()
            ->route('admin.backup.index')
            ->with('error', 'Erro ao restaurar backup: '.$result['message']);
    }
}
