<?php

namespace App\View\Components;

use App\Models\CategoryType;
use App\Models\User;
use Illuminate\View\Component;

class TaskModal extends Component
{
    public $categoryTypes;
    public $users;

    public function __construct()
    {
        $this->categoryTypes = CategoryType::with('values')->get();
        $this->users = User::orderBy('name')->get();
    }

    public function render()
    {
        return view('components.task-modal.index');
    }
}
