<?php

namespace App\Livewire\Users;

use App\Models\User;
use Flux\Flux;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app.sidebar')]
#[Title('User Form')]
class UserForm extends Component
{
    public ?int $userId = null;
    public string $name = '';
    public string $email = '';
    public string $password = '';
    public bool $sendWelcomeEmail = true;

    public function mount(?int $id = null): void
    {
        if ($id) {
            $user = User::findOrFail($id);
            $this->userId = $user->id;
            $this->name = $user->name;
            $this->email = $user->email;
            $this->sendWelcomeEmail = false;
        }
    }

    public function save(): void
    {
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email,' . $this->userId],
            'password' => $this->userId
                ? ['nullable', 'string', 'min:8']
                : ['required', 'string', 'min:8'],
        ]);

        if ($this->userId) {
            $user = User::findOrFail($this->userId);
            $user->fill($validated);

            if (! empty($validated['password'])) {
                $user->password = Hash::make($validated['password']);
            }

            $user->save();
            Flux::toast(variant: 'success', text: __('User updated successfully.'));
        } else {
            $validated['password'] = Hash::make($validated['password']);

            if (! $this->sendWelcomeEmail) {
                $validated['email_verified_at'] = now();
            }

            User::create($validated);
            Flux::toast(variant: 'success', text: __('User created successfully.'));
        }

        $this->redirectRoute('users.index');
    }

    public function render()
    {
        return view('livewire.users.user-form');
    }
}
