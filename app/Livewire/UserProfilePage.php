<?php

namespace App\Livewire;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('My Profile - ByteWebster')]

class UserProfilePage extends Component
{
    use LivewireAlert;

    public $user;
    public $name;
    public $email;
    public $phone;
    public $bio;
    public $activeTab = 'profile';
    public $editMode = false;

    // Password change properties
    public $showPasswordForm = false;
    public $currentPassword = '';
    public $newPassword = '';
    public $newPasswordConfirmation = '';

    // Address form properties
    public $showAddressForm = false;
    public $addressLabel = '';
    public $addressFullName = '';
    public $addressPhone = '';
    public $streetAddress = '';
    public $city = '';
    public $postalCode = '';
    public $isDefaultAddress = false;

    public function mount()
    {
        $this->user = Auth::user();
        $this->name = $this->user->name;
        $this->email = $this->user->email;
        $this->phone = $this->user->phone ?? '';
        $this->bio = $this->user->bio ?? '';
    }

    public function switchTab($tab)
    {
        $this->activeTab = $tab;
        $this->editMode = false;
        $this->showAddressForm = false;
        $this->showPasswordForm = false;
        $this->resetPasswordForm();
    }

    public function toggleEditProfile()
    {
        $this->editMode = !$this->editMode;
    }

    public function updateProfile()
    {
        $this->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $this->user->id,
            'phone' => 'nullable|string|max:20',
            'bio' => 'nullable|string|max:500',
        ]);

        $this->user->update([
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'bio' => $this->bio,
        ]);

        $this->editMode = false;

        $this->alert('success', 'Profile updated successfully!', [
            'position' => 'top-end',
            'timer' => 3000,
            'toast' => true,
        ]);
    }

    public function toggleAddAddress()
    {
        $this->showAddressForm = !$this->showAddressForm;
        $this->resetAddressForm();
    }

    public function resetAddressForm()
    {
        $this->addressLabel = '';
        $this->addressFullName = '';
        $this->addressPhone = '';
        $this->streetAddress = '';
        $this->city = '';
        $this->postalCode = '';
        $this->isDefaultAddress = false;
    }

    public function saveAddress()
    {
        $this->validate([
            'addressLabel' => 'required|string|max:255',
            'addressFullName' => 'required|string|max:255',
            'addressPhone' => 'required|string|max:20',
            'streetAddress' => 'required|string|max:500',
            'city' => 'required|string|max:255',
            'postalCode' => 'required|string|max:10',
        ]);

        // Here you would save the address to database
        // For now, just show success message

        $this->showAddressForm = false;
        $this->resetAddressForm();

        $this->alert('success', 'Address saved successfully!', [
            'position' => 'top-end',
            'timer' => 3000,
            'toast' => true,
        ]);
    }

    public function togglePasswordForm()
    {
        $this->showPasswordForm = !$this->showPasswordForm;
        $this->resetPasswordForm();
    }

    public function resetPasswordForm()
    {
        $this->currentPassword = '';
        $this->newPassword = '';
        $this->newPasswordConfirmation = '';
    }

    public function updatePassword()
    {
        $this->validate([
            'currentPassword' => 'required',
            'newPassword' => 'required|min:8|confirmed',
            'newPasswordConfirmation' => 'required',
        ]);

        // Check if current password is correct
        if (!Hash::check($this->currentPassword, $this->user->password)) {
            $this->addError('currentPassword', 'The current password is incorrect.');
            return;
        }

        // Update password
        $this->user->update([
            'password' => Hash::make($this->newPassword),
        ]);

        $this->showPasswordForm = false;
        $this->resetPasswordForm();

        $this->alert('success', 'Password updated successfully!', [
            'position' => 'top-end',
            'timer' => 3000,
            'toast' => true,
        ]);
    }

    public function getUserStats()
    {
        return [
            'orders' => $this->user->orders()->count(),
            'wishlist' => 0, // Placeholder - implement wishlist functionality
            'addresses' => 2, // Placeholder - implement address count
        ];
    }

    public function render()
    {
        $stats = $this->getUserStats();
        $orders = $this->user->orders()->with(['items.product', 'address'])->latest()->get();

        return view('livewire.user-profile-page', compact('stats', 'orders'));
    }
}
