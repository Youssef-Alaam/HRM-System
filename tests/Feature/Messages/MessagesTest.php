<?php

use App\Models\Message;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(\Database\Seeders\RolePermissionSeeder::class);
});

// ── Access control ────────────────────────────────────────────────────────────

test('guest is redirected to login', function () {
    $this->get('/messages')->assertRedirect('/login');
    $this->post('/messages')->assertRedirect('/login');
});

test('employee can access messages inbox', function () {
    actingAsRole('employee');
    $this->get('/messages')->assertOk();
});

test('hr can access messages inbox', function () {
    actingAsRole('hr');
    $this->get('/messages')->assertOk();
});

test('manager can access messages inbox', function () {
    actingAsRole('manager');
    $this->get('/messages')->assertOk();
});

test('admin can access messages inbox', function () {
    actingAsRole('admin');
    $this->get('/messages')->assertOk();
});

// ── Sending ───────────────────────────────────────────────────────────────────

test('user can send a message to another user in the same org', function () {
    $org = Organization::factory()->create();
    $sender = actingAsRole('employee', $org);
    $recipient = User::factory()->create(['org_id' => $org->id]);

    $this->post('/messages', [
        'recipient_id' => $recipient->id,
        'subject' => 'Hello',
        'body' => 'Hello there!',
    ])->assertRedirect();

    expect(Message::where('sender_id', $sender->id)
        ->where('recipient_id', $recipient->id)
        ->where('subject', 'Hello')
        ->exists()
    )->toBeTrue();
});

test('user cannot send a message to a user in a different org', function () {
    $org = Organization::factory()->create();
    $otherOrg = Organization::factory()->create();
    actingAsRole('employee', $org);
    $outsider = User::factory()->create(['org_id' => $otherOrg->id]);

    $this->post('/messages', [
        'recipient_id' => $outsider->id,
        'subject' => 'Hello',
        'body' => 'Cross-org message',
    ])->assertSessionHasErrors('recipient_id');
});

test('message body is required', function () {
    $org = Organization::factory()->create();
    actingAsRole('employee', $org);
    $recipient = User::factory()->create(['org_id' => $org->id]);

    $this->post('/messages', [
        'recipient_id' => $recipient->id,
        'subject' => 'Hello',
        'body' => '',
    ])->assertSessionHasErrors('body');
});

test('subject is required', function () {
    $org = Organization::factory()->create();
    actingAsRole('employee', $org);
    $recipient = User::factory()->create(['org_id' => $org->id]);

    $this->post('/messages', [
        'recipient_id' => $recipient->id,
        'subject' => '',
        'body' => 'Hello there!',
    ])->assertSessionHasErrors('subject');
});

// ── Inbox ─────────────────────────────────────────────────────────────────────

test('inbox shows messages received by the current user', function () {
    $org = Organization::factory()->create();
    $user = actingAsRole('employee', $org);
    $sender = User::factory()->create(['org_id' => $org->id]);

    Message::create([
        'org_id' => $org->id,
        'sender_id' => $sender->id,
        'recipient_id' => $user->id,
        'subject' => 'Inbox Test',
        'body' => 'You have a message.',
    ]);

    $response = $this->get('/messages');
    $response->assertOk();

    $props = $response->original->getData()['page']['props'];
    $subjects = collect($props['messages']['data'])->pluck('subject');
    expect($subjects)->toContain('Inbox Test');
});

test('inbox does not show messages sent by the current user as received', function () {
    $org = Organization::factory()->create();
    $user = actingAsRole('employee', $org);
    $recipient = User::factory()->create(['org_id' => $org->id]);

    Message::create([
        'org_id' => $org->id,
        'sender_id' => $user->id,
        'recipient_id' => $recipient->id,
        'subject' => 'Sent Message',
        'body' => 'I sent this.',
    ]);

    $response = $this->get('/messages');
    $props = $response->original->getData()['page']['props'];
    $subjects = collect($props['messages']['data'])->pluck('subject');
    expect($subjects)->not->toContain('Sent Message');
});

test('sent tab shows messages sent by the current user', function () {
    $org = Organization::factory()->create();
    $user = actingAsRole('employee', $org);
    $recipient = User::factory()->create(['org_id' => $org->id]);

    Message::create([
        'org_id' => $org->id,
        'sender_id' => $user->id,
        'recipient_id' => $recipient->id,
        'subject' => 'My Sent Item',
        'body' => 'Sent body.',
    ]);

    $response = $this->get('/messages?tab=sent');
    $props = $response->original->getData()['page']['props'];
    $subjects = collect($props['messages']['data'])->pluck('subject');
    expect($subjects)->toContain('My Sent Item');
});

// ── Thread + read ─────────────────────────────────────────────────────────────

test('viewing a message thread marks it as read', function () {
    $org = Organization::factory()->create();
    $recipient = actingAsRole('employee', $org);
    $sender = User::factory()->create(['org_id' => $org->id]);

    $message = Message::create([
        'org_id' => $org->id,
        'sender_id' => $sender->id,
        'recipient_id' => $recipient->id,
        'subject' => 'Read Me',
        'body' => 'Content.',
    ]);

    expect($message->read_at)->toBeNull();

    $this->get("/messages/{$message->id}")->assertOk();

    expect($message->fresh()->read_at)->not->toBeNull();
});

test('thread page shows all replies in order', function () {
    $org = Organization::factory()->create();
    $user = actingAsRole('employee', $org);
    $other = User::factory()->create(['org_id' => $org->id]);

    $root = Message::create([
        'org_id' => $org->id,
        'sender_id' => $other->id,
        'recipient_id' => $user->id,
        'subject' => 'Thread Root',
        'body' => 'Root body.',
    ]);

    Message::create([
        'org_id' => $org->id,
        'sender_id' => $user->id,
        'recipient_id' => $other->id,
        'subject' => 'Re: Thread Root',
        'body' => 'Reply 1.',
        'parent_message_id' => $root->id,
    ]);

    Message::create([
        'org_id' => $org->id,
        'sender_id' => $other->id,
        'recipient_id' => $user->id,
        'subject' => 'Re: Thread Root',
        'body' => 'Reply 2.',
        'parent_message_id' => $root->id,
    ]);

    $response = $this->get("/messages/{$root->id}");
    $response->assertOk();

    $props = $response->original->getData()['page']['props'];
    expect($props['thread'])->toHaveCount(3);
    expect($props['thread'][0]['id'])->toBe($root->id);
});

test('user cannot view a thread they are not part of', function () {
    $org = Organization::factory()->create();
    actingAsRole('employee', $org);
    $userA = User::factory()->create(['org_id' => $org->id]);
    $userB = User::factory()->create(['org_id' => $org->id]);

    $message = Message::create([
        'org_id' => $org->id,
        'sender_id' => $userA->id,
        'recipient_id' => $userB->id,
        'subject' => 'Private',
        'body' => 'Not your message.',
    ]);

    $this->get("/messages/{$message->id}")->assertForbidden();
});

// ── Unread count ──────────────────────────────────────────────────────────────

test('unread count reflects unread inbox messages', function () {
    $org = Organization::factory()->create();
    $user = actingAsRole('employee', $org);
    $sender = User::factory()->create(['org_id' => $org->id]);

    Message::create(['org_id' => $org->id, 'sender_id' => $sender->id, 'recipient_id' => $user->id, 'subject' => 'A', 'body' => 'B']);
    Message::create(['org_id' => $org->id, 'sender_id' => $sender->id, 'recipient_id' => $user->id, 'subject' => 'C', 'body' => 'D']);

    $response = $this->get('/messages');
    $props = $response->original->getData()['page']['props'];
    expect($props['unread_count'])->toBe(2);
});

test('unread count decrements after reading a message', function () {
    $org = Organization::factory()->create();
    $user = actingAsRole('employee', $org);
    $sender = User::factory()->create(['org_id' => $org->id]);

    $msg = Message::create(['org_id' => $org->id, 'sender_id' => $sender->id, 'recipient_id' => $user->id, 'subject' => 'A', 'body' => 'B']);

    $this->get("/messages/{$msg->id}");

    $response = $this->get('/messages');
    $props = $response->original->getData()['page']['props'];
    expect($props['unread_count'])->toBe(0);
});

// ── Org isolation ─────────────────────────────────────────────────────────────

test('inbox only shows messages from same org', function () {
    $org = Organization::factory()->create();
    $otherOrg = Organization::factory()->create();
    $user = actingAsRole('employee', $org);

    $foreignSender = User::factory()->create(['org_id' => $otherOrg->id]);

    // Manually create a cross-org message (bypasses validation)
    Message::withoutEvents(fn () => Message::forceCreate([
        'org_id' => $otherOrg->id,
        'sender_id' => $foreignSender->id,
        'recipient_id' => $user->id,
        'subject' => 'Foreign Org Message',
        'body' => 'Should not appear.',
    ]));

    $response = $this->get('/messages');
    $props = $response->original->getData()['page']['props'];
    $subjects = collect($props['messages']['data'])->pluck('subject');
    expect($subjects)->not->toContain('Foreign Org Message');
});

// ── Replies ───────────────────────────────────────────────────────────────────

test('user can reply to a message', function () {
    $org = Organization::factory()->create();
    $user = actingAsRole('employee', $org);
    $other = User::factory()->create(['org_id' => $org->id]);

    $root = Message::create([
        'org_id' => $org->id,
        'sender_id' => $other->id,
        'recipient_id' => $user->id,
        'subject' => 'Original',
        'body' => 'Hi.',
    ]);

    $this->post('/messages', [
        'recipient_id' => $other->id,
        'subject' => 'Re: Original',
        'body' => 'Reply body.',
        'parent_message_id' => $root->id,
    ])->assertRedirect();

    expect(Message::where('parent_message_id', $root->id)->where('sender_id', $user->id)->exists())->toBeTrue();
});
