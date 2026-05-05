<?php

namespace App\Http\Controllers;

use App\Http\Requests\SendMessageRequest;
use App\Models\User;
use App\Services\MessageService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class MessageController extends Controller
{
    public function __construct(private readonly MessageService $service) {}

    public function index(Request $request): Response
    {
        abort_unless($request->user()->can('chat.send'), 403);

        $user = $request->user();
        $orgId = (int) $user->org_id;
        $tab = $request->query('tab', 'inbox');

        $inbox = $tab === 'sent'
            ? $this->service->sent($orgId, $user->id)
            : $this->service->inbox($orgId, $user->id);

        $contacts = User::query()
            ->where('org_id', $orgId)
            ->where('id', '!=', $user->id)
            ->select('id', 'name', 'email')
            ->orderBy('name')
            ->get();

        return Inertia::render('Messages/Index', [
            'messages' => $inbox->through(fn ($m) => $this->serializeMessage($m)),
            'tab' => $tab,
            'contacts' => $contacts->map(fn ($c) => ['id' => $c->id, 'name' => $c->name, 'email' => $c->email])->values(),
            'unread_count' => $this->service->unreadCount($orgId, $user->id),
        ]);
    }

    public function show(int $message, Request $request): Response
    {
        abort_unless($request->user()->can('chat.send'), 403);

        $user = $request->user();
        $root = $this->service->read($message, $user->id);

        abort_unless(
            $root->sender_id === $user->id || $root->recipient_id === $user->id,
            403,
        );

        $thread = $this->service->thread($root->id);

        return Inertia::render('Messages/Show', [
            'thread' => $thread->map(fn ($m) => $this->serializeMessage($m))->values(),
            'root_id' => $root->id,
        ]);
    }

    public function store(SendMessageRequest $request): RedirectResponse
    {
        $message = $this->service->send(
            (int) $request->user()->org_id,
            $request->user(),
            $request->validated(),
        );

        return redirect()->route('messages.show', $message->id)
            ->with('status', 'Message sent.');
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeMessage($m): array
    {
        return [
            'id' => $m->id,
            'subject' => $m->subject,
            'body' => $m->body,
            'sender' => $m->sender ? ['id' => $m->sender->id, 'name' => $m->sender->name] : null,
            'recipient' => $m->recipient ? ['id' => $m->recipient->id, 'name' => $m->recipient->name] : null,
            'parent_message_id' => $m->parent_message_id,
            'read_at' => $m->read_at?->toIso8601String(),
            'created_at' => $m->created_at?->toIso8601String(),
        ];
    }
}
