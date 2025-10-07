<?php

namespace App\Http\Controllers;

use Exception;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\Booker;
use App\Models\User;
use App\Services\UserManagementService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class UserController extends Controller
{
    protected $userManagementService;

    /**
     * Construtor da classe, injeta o serviço de gerenciamento de usuários.
     */
    public function __construct(UserManagementService $userManagementService)
    {
        $this->userManagementService = $userManagementService;
    }

    /**
     * Exibe uma lista paginada de usuários.
     */
    public function index(): View
    {
        // Carrega usuários com seus bookers associados e os ordena por nome, paginando 15 por página.
        $users = User::with('booker')->orderBy('name')->paginate(15);

        return view('users.index', compact('users'));
    }

    /**
     * Mostra o formulário para criar um novo usuário.
     * Passa a lista de bookers disponíveis e dados iniciais para o Alpine.js.
     */
    public function create(): View
    {
        return view('users.create', [
            'bookers' => Booker::all(),
            'alpineData' => [
                'isBooker' => old('is_booker', false),
                'creationType' => old('booker_creation_type', 'existing'),
            ],
        ]);
    }

    /**
     * Armazena um novo usuário no banco de dados.
     * Delega a lógica de criação ao serviço.
     */
    public function store(StoreUserRequest $request): RedirectResponse
    {
        try {
            // Delega a lógica de criação e associação/criação de booker para o serviço.
            $this->userManagementService->createUser($request->validated());

            return redirect()
                ->route('users.index')
                ->with('success', 'Usuário criado com sucesso!');

        } catch (Exception $e) {
            // Em caso de erro, redireciona de volta com os inputs e uma mensagem de erro.
            return back()
                ->withInput()
                ->with('error', 'Erro ao criar usuário: '.$e->getMessage());
        }
    }

    /**
     * Mostra o formulário para editar um usuário existente.
     */
    public function edit(User $user): View
    {
        $user->load('booker');
        // A view de edição também precisa da lista de bookers para o cenário
        // em que um operador se torna um booker.
        $availableBookers = Booker::orderBy('name')->get();

        return view('users.edit', compact('user', 'availableBookers'));
    }

    /**
     * Atualiza um usuário existente no banco de dados.
     * Delega a lógica de atualização ao serviço.
     */
    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        try {
            $this->userManagementService->updateUser($user, $request->validated());

            return redirect()->route('users.index')->with('success', 'Usuário atualizado com sucesso!');
        } catch (Exception $e) {
            return back()->withInput()->with('error', 'Erro ao atualizar usuário: '.$e->getMessage());
        }
    }

    /**
     * Exibe os detalhes de um usuário específico.
     */
    public function show(User $user): View
    {
        return view('users.show', compact('user'));
    }

    /**
     * Remove (soft delete) o usuário selecionado.
     * Protege contra a remoção do próprio usuário logado.
     */
    public function destroy(User $user): RedirectResponse
    {
        // Proteção para não permitir que o usuário logado se auto-delete.
        if ($user->getAttribute('id') == Auth::id()) {
            return redirect()->route('users.index')->with('error', 'Você não pode remover seu próprio usuário.');
        }

        try {
            // Delega a lógica de remoção (soft delete) do usuário e do booker associado ao serviço.
            $this->userManagementService->deleteUser($user);

            return redirect()->route('users.index')->with('success', 'Usuário removido com sucesso.');
        } catch (Exception $e) {
            // Em caso de erro, loga a exceção e redireciona com uma mensagem de erro.
            Log::error('Erro ao remover usuário: '.$e->getMessage(), ['exception' => $e]);

            return redirect()->route('users.index')->with('error', 'Erro ao remover o usuário.');
        }
    }
}
