<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

class TeamController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        // Simple gate: only admins can view the team page for now
        abort_unless($user && $user->is_admin, 403);

        $teamMembers = User::with('developerProfile')
            ->orderBy('name')
            ->get();

        return view('team.index', [
            'teamMembers' => $teamMembers,
        ]);
    }
}
