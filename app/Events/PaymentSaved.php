<?php

namespace App\Events;

use App\Models\Payment; // Importa o modelo Payment
use App\Models\Gig;     // Importa o modelo Gig
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PaymentSaved
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    // Propriedade pública para armazenar a Gig afetada
    public Gig $gig;

    /**
     * Create a new event instance.
     *
     * @param Payment $payment O pagamento que foi salvo
     */
    public function __construct(Payment $payment)
    {
        // Carrega a Gig associada ao pagamento para passar ao Listener
        // Usamos loadMissing para carregar apenas se ainda não estiver carregada
        $this->gig = $payment->loadMissing('gig')->gig;
    }

    /**
     * Get the channels the event should broadcast on.
     * (Não precisamos de broadcasting agora)
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [];
        // Exemplo se fosse broadcast: return [new PrivateChannel('channel-name')];
    }
}