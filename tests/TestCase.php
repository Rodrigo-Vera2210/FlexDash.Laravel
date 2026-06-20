<?php

namespace Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;

abstract class TestCase extends BaseTestCase
{
    use RefreshDatabase;

    /**
     * Creates the application.
     *
     * @return \Illuminate\Foundation\Application
     */
    public function createApplication()
    {
        $app = require __DIR__ . '/../bootstrap/app.php';

        $app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

        return $app;
    }

    /**
     * Fake the Notification facade for the current test.
     *
     * Call this from a test's setUp() or directly inside a test method
     * when you need to assert that notifications were (or were not) sent.
     *
     * @return \Illuminate\Support\Testing\Fakes\NotificationFake
     */
    protected function fakeNotifications(): \Illuminate\Support\Testing\Fakes\NotificationFake
    {
        return Notification::fake();
    }

    /**
     * Fake the Mail facade for the current test.
     *
     * Call this from a test's setUp() or directly inside a test method
     * when you need to assert that mailables were (or were not) sent.
     *
     * @return \Illuminate\Support\Testing\Fakes\MailFake
     */
    protected function fakeMail(): \Illuminate\Support\Testing\Fakes\MailFake
    {
        return Mail::fake();
    }
}
