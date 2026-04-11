<?php

namespace App\Http\Controllers\Profile;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    public function show(string $username): Response
    {
        $user = User::where('username', $username)->firstOrFail();

        return Inertia::render('Profile/Show', [
            'profile' => [
                'username' => $user->username,
                'avatar_url' => $user->avatar_url,
                'github_id' => $user->github_id,
                'gitlab_id' => $user->gitlab_id,
                'created_at' => $user->created_at,
                // Stats will be computed from bounties/claims once those models exist
                'stats' => [
                    'bounties_completed' => 0,
                    'success_rate' => 0,
                    'total_earned' => 0,
                    'bounties_posted' => 0,
                    'validation_rate' => 0,
                    'total_invested' => 0,
                ],
            ],
        ]);
    }
}
