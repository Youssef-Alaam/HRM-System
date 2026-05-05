<?php

namespace App\Services;

use App\Models\Message;
use App\Models\User;
use App\Repositories\Contracts\MessageRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class MessageService extends BaseService
{
    public function __construct(private readonly MessageRepositoryInterface $repo) {}

    public function inbox(int $orgId, int $userId): LengthAwarePaginator
    {
        return $this->repo->inboxForUser($orgId, $userId);
    }

    public function sent(int $orgId, int $userId): LengthAwarePaginator
    {
        return $this->repo->sentByUser($orgId, $userId);
    }

    public function thread(int $rootId): Collection
    {
        return $this->repo->thread($rootId);
    }

    public function send(int $orgId, User $sender, array $data): Message
    {
        return $this->transaction(function () use ($orgId, $sender, $data) {
            return $this->repo->create(array_merge($data, [
                'org_id' => $orgId,
                'sender_id' => $sender->id,
            ]));
        });
    }

    public function read(int $messageId, int $viewerUserId): Message
    {
        $message = $this->repo->findOrFail($messageId);

        if ($message->recipient_id === $viewerUserId) {
            $this->repo->markRead($message);
        }

        return $message;
    }

    public function unreadCount(int $orgId, int $userId): int
    {
        return $this->repo->unreadCount($orgId, $userId);
    }
}
