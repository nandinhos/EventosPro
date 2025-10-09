<?php

namespace App\Services;

use App\Models\Booker;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class UserManagementService
{
    /**
     * Cria um novo usuário e, opcionalmente, associa ou cria um perfil de Booker.
     *
     * @param  array  $userData  Dados validados do usuário.
     *
     * @throws Exception
     */
    public function createUser(array $userData): User
    {
        DB::beginTransaction(); // Inicia uma transação de banco de dados para garantir atomicidade
        try {
            $bookerId = null;

            // Lógica para Booker: verifica se o usuário deve ser associado a um booker
            if (isset($userData['is_booker']) && $userData['is_booker']) {
                if ($userData['booker_creation_type'] === 'new') {
                    // Cria um novo booker se a opção 'new' for selecionada
                    $booker = Booker::create([
                        'name' => strtoupper($userData['booker_name']), // Converte o nome para maiúsculas
                        'default_commission_rate' => $userData['default_commission_rate'],
                        'contact_info' => $userData['contact_info'] ?? null,
                    ]);
                    $bookerId = $booker->id;
                } else {
                    // Associa a um booker existente se a opção 'existing' for selecionada
                    $bookerId = $userData['existing_booker_id'];
                    // Valida se o booker existente já não está associado a outro usuário
                    $existingBooker = Booker::find($bookerId);
                    if ($existingBooker && $existingBooker->user) {
                        throw new Exception('O booker selecionado já está associado a outro usuário.');
                    }
                }
            }

            // Cria o usuário
            $user = User::create([
                'name' => $userData['name'],
                'email' => $userData['email'],
                'password' => Hash::make($userData['password']),
                'booker_id' => $bookerId, // Associa o booker_id, se houver
            ]);

            DB::commit(); // Confirma a transação

            return $user;

        } catch (Exception $e) {
            DB::rollBack(); // Reverte a transação em caso de erro
            Log::error('Erro ao criar usuário no serviço: '.$e->getMessage(), ['exception' => $e, 'user_data' => $userData]);
            throw $e; // Re-lança a exceção para o controller lidar com a resposta
        }
    }

    /**
     * Atualiza um usuário existente e gerencia seu perfil de Booker.
     *
     * @param  User  $user  Instância do usuário a ser atualizado.
     * @param  array  $userData  Dados validados para atualização.
     *
     * @throws Exception
     */
    public function updateUser(User $user, array $userData): User
    {
        DB::beginTransaction(); // Inicia uma transação
        try {
            // 1. Atualiza os dados básicos do usuário
            $user->name = $userData['name'];
            $user->email = $userData['email'];
            // Atualiza a senha apenas se um novo valor for fornecido
            if (isset($userData['password']) && ! empty($userData['password'])) {
                $user->password = Hash::make($userData['password']);
            }
            $user->save();

            // 2. Lógica para o perfil de Booker
            $isBookerRequested = isset($userData['is_booker']) && $userData['is_booker'];

            if ($isBookerRequested) {
                // Se o usuário já tem um booker associado
                if ($user->booker) {
                    // Apenas atualiza o perfil do booker existente
                    $user->booker->update([
                        'default_commission_rate' => $userData['default_commission_rate'] ?? $user->booker->default_commission_rate,
                        'contact_info' => $userData['contact_info'] ?? $user->booker->contact_info,
                    ]);
                } else {
                    // O usuário está se tornando um booker (não tinha um antes)
                    if ($userData['booker_creation_type'] === 'existing') {
                        $bookerIdToAssociate = $userData['existing_booker_id'];
                        // Valida se o booker existente já não está associado a outro usuário (exceto o próprio)
                        $existingBooker = Booker::find($bookerIdToAssociate);
                        if ($existingBooker && $existingBooker->user && $existingBooker->user->id !== $user->id) {
                            throw new Exception('O booker selecionado já está associado a outro usuário.');
                        }
                        $user->booker_id = $bookerIdToAssociate;
                        $user->save();
                    } elseif ($userData['booker_creation_type'] === 'new') {
                        // Cria um novo booker e associa ao usuário
                        $newBooker = Booker::create([
                            'name' => strtoupper($userData['booker_name']),
                            'default_commission_rate' => $userData['default_commission_rate'],
                            'contact_info' => $userData['contact_info'] ?? null,
                        ]);
                        $user->booker_id = $newBooker->id;
                        $user->save();
                    }
                }
            } else {
                // Usuário não deve ser um booker, desvincula se estava associado
                if ($user->booker_id) {
                    // Importante: Se a regra de negócio permitir que um Booker exista sem um User,
                    // mas o User está sendo desvinculado, você pode optar por:
                    // 1. Manter o Booker (apenas nullificar booker_id do User)
                    // 2. Soft delete o Booker (como feito no método destroy)
                    // 3. Hard delete o Booker (se não houver dependências)
                    // No seu caso original, o Booker era soft-deletado junto com o User.
                    // Aqui, apenas desvinculamos o Booker do User, mantendo o Booker ativo.
                    // Se o Booker deve ser desativado/soft-deletado ao ser desvinculado,
                    // adicione essa lógica aqui.
                    $user->booker_id = null;
                    $user->save();
                }
            }

            DB::commit(); // Confirma a transação

            return $user;

        } catch (Exception $e) {
            DB::rollBack(); // Reverte a transação
            Log::error('Erro ao atualizar usuário no serviço: '.$e->getMessage(), ['exception' => $e, 'user_id' => $user->id, 'user_data' => $userData]);
            throw $e; // Re-lança a exceção
        }
    }

    /**
     * Remove (soft delete) o usuário e, opcionalmente, seu perfil de Booker associado.
     *
     * @param  User  $user  Instância do usuário a ser removido.
     * @return bool True se a remoção foi bem-sucedida, false caso contrário.
     *
     * @throws Exception
     */
    public function deleteUser(User $user): bool
    {
        DB::beginTransaction(); // Inicia uma transação
        try {
            // Opcional: Se o usuário tiver um booker associado, soft delete o booker também.
            // Isso assume que o modelo Booker usa o trait SoftDeletes.
            if ($user->booker) {
                $user->booker->delete();
            }

            // Soft delete o usuário. Isso assume que o modelo User usa o trait SoftDeletes.
            $user->delete();

            DB::commit(); // Confirma a transação

            return true;

        } catch (Exception $e) {
            DB::rollBack(); // Reverte a transação
            Log::error('Erro ao remover usuário no serviço: '.$e->getMessage(), ['exception' => $e, 'user_id' => $user->id]);
            throw $e; // Re-lança a exceção para o controller
        }
    }
}
