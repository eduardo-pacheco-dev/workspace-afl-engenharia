<?php

namespace App\Livewire\Auth;

use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app.sidebar')]
#[Title('Change Password')]
class ForcePasswordChange extends Component
{
    public string $password = '';
    public string $password_confirmation = '';

    public function save(): void
    {
        $validated = $this->validate([
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user = Auth::user();
        $user->password = Hash::make($validated['password']);
        $user->must_change_password = false;
        $user->save();

        Auth::login($user);

        Flux::toast(variant: 'success', text: __('Password changed successfully.'));

        return $this->redirect(route('dashboard'), navigate: true);
    }

    public function render()
    {
        return view('livewire.auth.force-password-change');
    }
}
