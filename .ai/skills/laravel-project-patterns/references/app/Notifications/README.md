# app/Notifications

## Purpose

This reference defines conventions for notifications under `app/Notifications`.

## When To Use

Use this reference when your task matches this path and you need to follow its local conventions.

## Required Pattern

Use `app/Notifications` for queued recipient-facing notifications sent by actions or session flows.

### Notification Shape

- Extend `Notification` and implement `ShouldQueue` when the notification is recipient-facing mail.
- Use `Queueable`.
- Use constructor property promotion for notification data.
- Keep channel selection in `via(): array`.
- Keep message rendering in the notification method such as `toMail(...)`.
- Keep mail subjects, greetings, and body lines literal unless a sibling notification already localizes them.

```php
<?php

declare(strict_types=1);

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ExampleCodeNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly ExampleCode $code)
    {
    }

    public function toMail(): MailMessage
    {
        return new MailMessage()
            ->subject('Your verification code')
            ->greeting('Here is your verification code')
            ->line('Use this code to continue: '.$this->code->value)
            ->line("If you didn't request this, you can ignore this message.");
    }

    /**
     * @return array<int, string>
     */
    public function via(): array
    {
        return ['mail'];
    }
}
```

### Tests

- Notifications are usually asserted through the action or controller that sends them.
- Use notification fakes and assert the notifiable route/data that matters.
- When a controller endpoint delegates notification payload generation to an action, mock that action in the controller feature test and return a persisted payload model. Leave payload persistence, cleanup, retry, and max-attempt behavior to `tests/Integration/Actions`.
- Add direct notification assertions only when the message structure itself changes.

```php
Notification::fake();

$code = ExampleCode::factory()->createOne([
    'recipient' => 'actor@example.com',
    'value' => '111111',
]);

$this->mock(GenerateExampleCode::class, function (MockInterface $mock) use ($code): void {
    $mock->shouldReceive('handle')
        ->once()
        ->with('actor@example.com')
        ->andReturn($code);
});

$response = postJson(route('api.sessions.code.request'), [
    'recipient' => 'actor@example.com',
]);

$response->assertOk();

Notification::assertSentOnDemand(
    ExampleCodeNotification::class,
    function (
        ExampleCodeNotification $notification,
        array $channels,
        AnonymousNotifiable $notifiable
    ) use ($code): bool {
        return ($notifiable->routes['mail'] ?? null) === $code->recipient
            && $notification->code->is($code);
    }
);
```

## Coverage Expectations

Read the live file in this path, compare it with sibling files, and cover the behavior in the suite or reference that owns that surface. Do not add adjacent coverage just for symmetry.

## Do Not

- Do not contradict the skill non-negotiables or project conventions.

## Related References

- `references/tests/Feature/Http/Controllers/Api/README.md`
- `references/app/Actions/README.md`
- `references/tests/Integration/Actions/README.md`
