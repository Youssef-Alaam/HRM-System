<?php

namespace App\Repositories\Contracts;

use App\Models\Message;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface MessageRepositoryInterface
{
    public function inboxForUser(int $orgId, int $userId): LengthAwarePaginator;

    public function sentByUser(int $orgId, int $userId): LengthAwarePaginator;

    public function thread(int $rootMessageId): Collection;

    public function findOrFail(int $id): Message;

    public function create(array $data): Message;

    public function markRead(Message $message): void;

    public function unreadCount(int $orgId, int $userId): int;
}
