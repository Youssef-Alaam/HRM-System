<?php

namespace App\Repositories;

use App\Models\Message;
use App\Repositories\Contracts\MessageRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class MessageRepository extends BaseRepository implements MessageRepositoryInterface
{
    protected function model(): string
    {
        return Message::class;
    }

    public function inboxForUser(int $orgId, int $userId): LengthAwarePaginator
    {
        return Message::query()
            ->where('org_id', $orgId)
            ->where('recipient_id', $userId)
            ->whereNull('parent_message_id')
            ->with('sender:id,name')
            ->orderByDesc('created_at')
            ->paginate(25)
            ->withQueryString();
    }

    public function sentByUser(int $orgId, int $userId): LengthAwarePaginator
    {
        return Message::query()
            ->where('org_id', $orgId)
            ->where('sender_id', $userId)
            ->whereNull('parent_message_id')
            ->with('recipient:id,name')
            ->orderByDesc('created_at')
            ->paginate(25)
            ->withQueryString();
    }

    public function thread(int $rootMessageId): Collection
    {
        return Message::query()
            ->where(fn ($q) => $q
                ->where('id', $rootMessageId)
                ->orWhere('parent_message_id', $rootMessageId)
            )
            ->with(['sender:id,name', 'recipient:id,name'])
            ->orderBy('created_at')
            ->orderBy('id')
            ->get();
    }

    public function findOrFail(int $id): Message
    {
        /** @var Message */
        return Message::query()->findOrFail($id);
    }

    public function create(array $data): Message
    {
        /** @var Message */
        return Message::create($data);
    }

    public function markRead(Message $message): void
    {
        if (! $message->read_at) {
            $message->update(['read_at' => now()]);
        }
    }

    public function unreadCount(int $orgId, int $userId): int
    {
        return Message::query()
            ->where('org_id', $orgId)
            ->where('recipient_id', $userId)
            ->whereNull('read_at')
            ->count();
    }
}
