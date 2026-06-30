<?php

namespace App\Http\Controllers;

use App\Models\GithubConnection;
use App\Models\SlackConnection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class OnboardingController extends Controller
{
    public function show(Request $request)
    {
        $user = $request->user();

        abort_unless($user->isSuperAdmin(), 403);

        if (! $user->needsOnboarding()) {
            return redirect()->route('tasks.index');
        }

        $githubConnection = GithubConnection::active();
        $slackConnection  = SlackConnection::active();

        return view('onboarding.index', compact('githubConnection', 'slackConnection'));
    }

    public function saveProfile(Request $request)
    {
        $user = $request->user();
        abort_unless($user->isSuperAdmin(), 403);

        $validated = $request->validate([
            'name'       => ['required', 'string', 'max:255'],
            'position'   => ['nullable', 'string', 'max:100'],
            'department' => ['nullable', 'string', 'max:100'],
            'phone'      => ['nullable', 'string', 'max:30'],
        ]);

        $user->update($validated);

        return response()->json(['ok' => true]);
    }

    public function complete(Request $request)
    {
        $user = $request->user();
        abort_unless($user->isSuperAdmin(), 403);

        $user->update(['onboarding_completed_at' => now()]);

        return redirect()->route('tasks.index')
            ->with('success', '¡Bienvenido a TaskLab! Tu espacio de trabajo está listo.');
    }

    public function skip(Request $request)
    {
        $user = $request->user();
        abort_unless($user->isSuperAdmin(), 403);

        $user->update(['onboarding_completed_at' => now()]);

        return redirect()->route('tasks.index');
    }
}
