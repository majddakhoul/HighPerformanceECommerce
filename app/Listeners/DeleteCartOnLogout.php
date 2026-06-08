<?php

namespace App\Listeners;

use App\Repositories\Contracts\CartServiceInterface;

class DeleteCartOnLogout
{
    public function __construct(private CartServiceInterface $cartService) {}

    public function handle($event): void
    {
        if ($user = $event->user) {
            $this->cartService->clearCart($user->id);
        }
    }
}