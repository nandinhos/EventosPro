<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ferramenta de Backup - EventosPro</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-100 min-h-screen">
    <div class="max-w-5xl mx-auto py-8 px-4">
        <div class="bg-white rounded-lg shadow-lg overflow-hidden">
            <div class="bg-indigo-600 px-6 py-4 flex items-center justify-between">
                <div class="flex items-center space-x-3">
                    <i class="fas fa-database text-white text-xl"></i>
                    <h1 class="text-xl font-bold text-white">Ferramenta de Backup</h1>
                </div>
                <a href="{{ route('dashboard') }}" class="text-white hover:text-indigo-200 text-sm">
                    <i class="fas fa-arrow-left mr-1"></i> Voltar ao Dashboard
                </a>
            </div>

            <div class="p-6">
                @if(session('success'))
                    <div class="mb-6 bg-green-50 border-l-4 border-green-500 p-4 rounded-r">
                        <div class="flex items-center">
                            <i class="fas fa-check-circle text-green-500 mr-3"></i>
                            <p class="text-green-700">{{ session('success') }}</p>
                        </div>
                    </div>
                @endif

                @if(session('error'))
                    <div class="mb-6 bg-red-50 border-l-4 border-red-500 p-4 rounded-r">
                        <div class="flex items-center">
                            <i class="fas fa-exclamation-circle text-red-500 mr-3"></i>
                            <p class="text-red-700">{{ session('error') }}</p>
                        </div>
                    </div>
                @endif

                <div class="mb-8 bg-blue-50 border border-blue-200 rounded-lg p-4">
                    <div class="flex items-start">
                        <i class="fas fa-info-circle text-blue-500 mt-1 mr-3"></i>
                        <div>
                            <h3 class="font-semibold text-blue-800">Como funciona</h3>
                            <p class="text-sm text-blue-700 mt-1">
                                Esta ferramenta faz <strong>backup apenas dos dados</strong> (gigs, artistas, bookers, pagamentos, etc), 
                                sem alterar a estrutura do banco. Isso significa que suas configurações, usuários e preferências 
                                permanecem intactas durante a restauração.
                            </p>
                        </div>
                    </div>
                </div>

                <div class="flex flex-wrap gap-4 mb-8">
                    <form id="create-form" action="{{ route('backup-tool.create') }}" method="POST" class="inline">
                        @csrf
                        <button type="submit" 
                                onclick="this.disabled=true; this.innerHTML='<i class=\'fas fa-spinner fa-spin mr-2\'></i> Criando...'; document.getElementById('create-form').submit()"
                                class="bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-2 px-4 rounded-lg shadow transition flex items-center">
                            <i class="fas fa-plus mr-2"></i> Criar Backup de Dados
                        </button>
                    </form>

                    <button onclick="document.getElementById('upload-modal').classList.remove('hidden')"
                            class="bg-gray-600 hover:bg-gray-700 text-white font-semibold py-2 px-4 rounded-lg shadow transition flex items-center">
                        <i class="fas fa-upload mr-2"></i> Enviar Backup
                    </button>
                </div>

                <div class="border-t pt-6">
                    <h2 class="text-lg font-semibold text-gray-800 mb-4">
                        <i class="fas fa-folder-open mr-2 text-gray-500"></i> Backups Disponíveis
                    </h2>

                    @if(count($backups) > 0)
                        <div class="overflow-x-auto">
                            <table class="w-full text-left border-collapse">
                                <thead>
                                    <tr class="bg-gray-50 border-b">
                                        <th class="py-3 px-4 text-sm font-semibold text-gray-600">Arquivo</th>
                                        <th class="py-3 px-4 text-sm font-semibold text-gray-600">Tamanho</th>
                                        <th class="py-3 px-4 text-sm font-semibold text-gray-600">Criado em</th>
                                        <th class="py-3 px-4 text-sm font-semibold text-gray-600 text-right">Ações</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($backups as $backup)
                                        <tr class="border-b hover:bg-gray-50">
                                            <td class="py-3 px-4">
                                                <div class="flex items-center">
                                                    <i class="fas fa-file-code text-indigo-500 mr-3"></i>
                                                    <span class="text-sm font-medium text-gray-800">{{ $backup['filename'] }}</span>
                                                </div>
                                            </td>
                                            <td class="py-3 px-4 text-sm text-gray-600">
                                                @if($backup['size'] < 1024)
                                                    {{ $backup['size'] }} bytes
                                                @elseif($backup['size'] < 1024 * 1024)
                                                    {{ round($backup['size'] / 1024, 2) }} KB
                                                @else
                                                    {{ round($backup['size'] / (1024 * 1024), 2) }} MB
                                                @endif
                                            </td>
                                            <td class="py-3 px-4 text-sm text-gray-600">{{ $backup['created_at'] }}</td>
                                            <td class="py-3 px-4 text-right">
                                                <div class="flex justify-end space-x-2">
                                                    <form action="{{ route('backup-tool.restore', $backup['filename']) }}" method="POST" class="inline" 
                                                          onsubmit="return confirm('Tem certeza que deseja restaurar este backup? Os dados atuais serão substituídos.');">
                                                        @csrf
                                                        <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white text-xs font-semibold py-1.5 px-3 rounded transition">
                                                            <i class="fas fa-undo mr-1"></i> Restaurar
                                                        </button>
                                                    </form>
                                                    <a href="{{ route('backup-tool.download', $backup['filename']) }}" 
                                                       class="bg-green-600 hover:bg-green-700 text-white text-xs font-semibold py-1.5 px-3 rounded transition inline-block">
                                                        <i class="fas fa-download mr-1"></i> Baixar
                                                    </a>
                                                    <form action="{{ route('backup-tool.destroy', $backup['filename']) }}" method="POST" class="inline">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" 
                                                                onclick="return confirm('Excluir este backup?');"
                                                                class="bg-red-600 hover:bg-red-700 text-white text-xs font-semibold py-1.5 px-3 rounded transition">
                                                            <i class="fas fa-trash mr-1"></i>
                                                        </button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="text-center py-12 bg-gray-50 rounded-lg">
                            <i class="fas fa-folder-open text-gray-400 text-4xl mb-4"></i>
                            <p class="text-gray-500">Nenhum backup encontrado</p>
                            <p class="text-gray-400 text-sm mt-1">Clique em "Criar Backup de Dados" para gerar o primeiro</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div id="upload-modal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white rounded-lg shadow-xl max-w-md w-full mx-4">
            <div class="px-6 py-4 border-b flex items-center justify-between">
                <h3 class="text-lg font-semibold text-gray-800">
                    <i class="fas fa-upload mr-2 text-indigo-600"></i> Enviar Backup
                </h3>
                <button onclick="document.getElementById('upload-modal').classList.add('hidden')" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <form action="{{ route('backup-tool.upload') }}" method="POST" enctype="multipart/form-data" class="p-6">
                @csrf
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Selecione o arquivo (.sql)</label>
                    <input type="file" name="backup_file" accept=".sql" required
                           class="w-full border border-gray-300 rounded-lg p-2 text-sm">
                </div>
                <div class="flex justify-end space-x-3">
                    <button type="button" onclick="document.getElementById('upload-modal').classList.add('hidden')"
                            class="bg-gray-300 hover:bg-gray-400 text-gray-700 font-semibold py-2 px-4 rounded transition">
                        Cancelar
                    </button>
                    <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-2 px-4 rounded transition">
                        <i class="fas fa-upload mr-2"></i> Enviar
                    </button>
                </div>
            </form>
        </div>
    </div>
</body>
</html>