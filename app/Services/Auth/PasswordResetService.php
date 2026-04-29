<?php

namespace App\Services\Auth;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

class PasswordResetService
{
    public function sendResetLink(Request $request): string
    {
        $email = (string) $request->string('email');
        $status = Password::sendResetLink(['email' => $email]);

        $user = User::where('email', $email)->first();
        $this->writeAudit('password_reset_link_requested', $user, $request, [
            'email' => $email,
            'status' => $status,
        ]);

        return $status;
    }

    public function resetPassword(Request $request): string
    {
        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user) use ($request) {
                $user->forceFill([
                    'password' => Hash::make($request->string('password')),
                    'remember_token' => Str::random(60),
                ])->save();

                event(new PasswordReset($user));

                $this->writeAudit('password_reset_completed', $user, $request);
            },
        );

        if ($status !== Password::PASSWORD_RESET) {
            $this->writeAudit('password_reset_failed', null, $request, [
                'email' => (string) $request->string('email'),
                'status' => $status,
            ]);
        }

        return $status;
    }

    private function writeAudit(string $action, ?User $user, Request $request, array $changes = []): void
    {
        AuditLog::create([
            'org_id' => $user?->org_id,
            'user_id' => $user?->id,
            'action' => $action,
            'entity_type' => $user ? User::class : null,
            'entity_id' => $user?->id,
            'changes' => $changes ?: null,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);
    }
}
