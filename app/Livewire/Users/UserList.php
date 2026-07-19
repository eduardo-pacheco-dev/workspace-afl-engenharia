<?php

namespace App\Livewire\Users;

use App\Models\User;
use Flux\Flux;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Title('Users')]
class UserList extends Component
{
    use WithPagination;

    public string $search = '';

    public function render()
    {
        return view('livewire.users.user-list', [
            'users' => User::query()
                ->where('name', 'like', "%{$this->search}%")
                ->orWhere('email', 'like', "%{$this->search}%")
                ->orderBy('name')
                ->paginate(10),
        ]);
    }

    public function delete(User $user): void
    {
        if ($user->id === auth()->id()) {
            Flux::toast(variant: 'error', text: __('You cannot delete your own account.'));
            return;
        }

        $user->delete();
        Flux::toast(variant: 'success', text: __('User deleted successfully.'));
    }
}
