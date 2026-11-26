<?php

declare(strict_types=1);

namespace App\Providers;

use App\Application\Feedback\SendFeedback;
use App\Domain\Account\Clock;
use App\Domain\Feedback\FeedbackBox;
use App\Infrastructure\Persistence\EloquentFeedbackBox;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\ServiceProvider;

/**
 * Composition root of the feedback form: binds the port of `App\Domain\Feedback` and builds its use case.
 */
final class FeedbackServiceProvider extends ServiceProvider
{
    /**
     * Binds where the messages are kept, and the use case that sends one.
     */
    public function register(): void
    {
        $this->app->bind(FeedbackBox::class, EloquentFeedbackBox::class);
        $this->app->bind(SendFeedback::class, static fn (Application $app): SendFeedback => new SendFeedback(
            $app->make(FeedbackBox::class),
            $app->make(Clock::class),
        ));
    }
}
