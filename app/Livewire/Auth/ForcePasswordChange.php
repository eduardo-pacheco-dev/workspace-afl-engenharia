<?php

namespace App\Livewire\Auth;

use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.auth')]
#[Title('Change Password')]
class ForcePasswordChange extends Component
{
    public string $password = '';
    public string $password_confirmation = '';

    public function save(): mixed
    {
        $validated = $this->validate([
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user = Auth::user();
        $user->forceFill(['password' => Hash::make($validated['password']), 'must_change_password' => false])->save();

        Flux::toast(variant: 'success', text: __('Password changed successfully.'));

        return redirect()->route('dashboard');
    }

    public function render()
    {
        return view('livewire.auth.force-password-change');
    }
}
