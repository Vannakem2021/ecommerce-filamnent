<?php

namespace App\Livewire\Auth;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Register - ByteWebster')]

class RegisterPage extends Component
{
    use LivewireAlert;

    public $name = '';
    public $email = '';
    public $password = '';

    public function register()
    {
        $this->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:6',
        ]);

        $user = User::create([
            'name' => $this->name,
            'email' => $this->email,
            'password' => Hash::make($this->password),
        ]);

        Auth::login($user);

        $this->alert('success', 'Registration successful! Welcome to ByteWebster!', [
            'position' => 'top-end',
            'timer' => 3000,
            'toast' => true,
        ]);

        return $this->redirect('/', navigate: true);
    }

    public function render()
    {
        return view('livewire.auth.register-page');
    }
}
