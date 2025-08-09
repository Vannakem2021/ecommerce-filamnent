<?php

namespace App\Livewire\Partials;

use App\Helpers\CartManagement;
use Illuminate\Support\Facades\Auth;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Attributes\On;
use Livewire\Component;

class Navbar extends Component
{
    use LivewireAlert;

    public $total_count = 0;

    public function mount()
    {
        $this->total_count = CartManagement::calculateTotalQuantity();
    }

    #[On('update-cart-count')]
    public function updateCartCount($total_count)
    {
        $this->total_count = $total_count;
    }

    public function logout()
    {
        Auth::logout();

        $this->alert('success', 'Logged out successfully!', [
            'position' => 'top-end',
            'timer' => 3000,
            'toast' => true,
        ]);

        return $this->redirect('/', navigate: true);
    }

    public function render()
    {
        return view('livewire.partials.navbar');
    }
}
