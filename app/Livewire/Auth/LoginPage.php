<?php

namespace App\Livewire\Auth;

use Illuminate\Support\Facades\Auth;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Login - ByteWebster')]

class LoginPage extends Component
{
    use LivewireAlert;

    public $email = '';
    public $password = '';

    public function login()
    {
        $this->validate([
            'email' => 'required|email',
            'password' => 'required|min:6',
        ]);

        if (Auth::attempt(['email' => $this->email, 'password' => $this->password])) {
            $this->alert('success', 'Login successful!', [
                'position' => 'top-end',
                'timer' => 3000,
                'toast' => true,
            ]);

            return $this->redirect('/', navigate: true);
        } else {
            $this->alert('error', 'Invalid email or password!', [
                'position' => 'top-end',
                'timer' => 3000,
                'toast' => true,
            ]);
        }
    }

    public function render()
    {
        return view('livewire.auth.login-page');
    }
}
