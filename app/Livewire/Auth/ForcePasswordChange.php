<?php

namespace App\Livewire\Auth;

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

    public int $level = 0;
    public string $levelLabel = '';

    public array $rules = [
        ['label' => 'At least 8 characters', 'met' => false],
        ['label' => 'One uppercase letter', 'met' => false],
        ['label' => 'One lowercase letter', 'met' => false],
        ['label' => 'One number', 'met' => false],
        ['label' => 'One special character (!@#$%^&*)', 'met' => false],
    ];

    protected $listeners = ['checkStrength' => 'evaluate'];

    public function updatedPassword(string $value): void
    {
        $this->evaluate();
    }

    public function evaluate(): void
    {
        $p = $this->password;

        $this->rules[0]['met'] = strlen($p) >= 8;
        $this->rules[1]['met'] = (bool) preg_match('/[A-Z]/', $p);
        $this->rules[2]['met'] = (bool) preg_match('/[a-z]/', $p);
        $this->rules[3]['met'] = (bool) preg_match('/[0-9]/', $p);
        $this->rules[4]['met'] = (bool) preg_match('/[!@#$%^&*()_+\-=\[\]{};\':",.<>\/?\\\\|]/', $p);

        $score = count(array_filter($this->rules, fn ($r) => $r['met']));
        $empty = $p === '';

        $this->level = match (true) {
            $empty => 0,
            $score <= 1 => 1,
            $score === 2 => 2,
            $score === 3 => 3,
            default => 4,
        };

        $this->levelLabel = match ($this->level) {
            0 => __('Enter a password'),
            1 => __('Very weak'),
            2 => __('Weak'),
            3 => __('Fair'),
            4 => __('Strong'),
        };
    }

    public function save(): mixed
    {
        $validated = $this->validate([
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user = Auth::user();
        $user->forceFill(['password' => Hash::make($validated['password']), 'must_change_password' => false])->save();

        return redirect()->route('dashboard');
    }

    public function render()
    {
        return view('livewire.auth.force-password-change');
    }
}
