<?php
namespace App\Policies;
use App\Models\Gig;
use App\Models\User;

class GigPolicy
{
    public function viewAny(User $user): bool { return $user->hasAnyRole(['ADMIN', 'DIRETOR', 'BOOKER']); }
    public function view(User $user, Gig $gig): bool {
        if ($user->hasAnyRole(['ADMIN', 'DIRETOR'])) return true;
        if ($user->hasRole('BOOKER')) return $user->booker_id === $gig->booker_id;
        return false;
    }
    public function create(User $user): bool { return false; } // Painel é apenas consulta
    public function update(User $user, Gig $gig): bool { return false; }
    public function delete(User $user, Gig $gig): bool { return false; }
    public function restore(User $user, Gig $gig): bool { return false; }
    public function forceDelete(User $user, Gig $gig): bool { return false; }
}