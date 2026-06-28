<?php

namespace App\Modules\Tickets\Tests\Feature;

use App\Models\User;
use App\Modules\Tickets\Models\Ticket;
use App\Modules\Tickets\Models\TicketMessage;
use App\Modules\Tickets\Models\TicketAttachment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class TicketFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_ticket_creation_requires_at_least_one_evidence_image(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson(route('tickets.store'), [
            'title'       => 'Test Ticket',
            'description' => 'Test Description',
            'severity'    => 'medio',
            'evidence'    => [], // No images
        ]);

        $response->assertStatus(422); // Validation error
        $response->assertJsonValidationErrors('evidence');
    }

    public function test_ticket_creation_succeeds_with_multiple_images(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();

        $image1 = UploadedFile::fake()->image('evidence1.jpg');
        $image2 = UploadedFile::fake()->image('evidence2.png');

        $response = $this->actingAs($user)->post(route('tickets.store'), [
            'title'       => 'Test Ticket',
            'description' => 'Test Description',
            'severity'    => 'alto',
            'evidence'    => [$image1, $image2],
        ]);

        $response->assertRedirect(route('tickets.index'));
        $this->assertDatabaseHas('tickets', [
            'title'       => 'Test Ticket',
            'description' => 'Test Description',
            'severity'    => 'alto',
            'status'      => 'pendiente',
        ]);

        $ticket = Ticket::first();
        $this->assertCount(2, $ticket->attachments);
        Storage::disk('public')->assertExists($ticket->attachments[0]->file_path);
        Storage::disk('public')->assertExists($ticket->attachments[1]->file_path);
    }

    public function test_superadmin_cannot_approve_or_reject_without_replying_first(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();
        $superadmin = User::factory()->create(['role' => 'superadmin']);

        // Create a ticket
        $image = UploadedFile::fake()->image('evidence.jpg');
        $this->actingAs($user)->post(route('tickets.store'), [
            'title'       => 'Test Ticket',
            'description' => 'Test Description',
            'severity'    => 'medio',
            'evidence'    => [$image],
        ]);

        $ticket = Ticket::first();

        // Superadmin tries to approve it directly
        $response = $this->actingAs($superadmin)->post(route('superadmin.tickets.status.update', $ticket), [
            'status' => 'aprobado',
        ]);

        $response->assertSessionHasErrors('status');
        $ticket->refresh();
        $this->assertEquals('pendiente', $ticket->status);
    }

    public function test_superadmin_can_approve_or_reject_after_replying(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();
        $superadmin = User::factory()->create(['role' => 'superadmin']);

        // Create a ticket
        $image = UploadedFile::fake()->image('evidence.jpg');
        $this->actingAs($user)->post(route('tickets.store'), [
            'title'       => 'Test Ticket',
            'description' => 'Test Description',
            'severity'    => 'medio',
            'evidence'    => [$image],
        ]);

        $ticket = Ticket::first();

        // Superadmin sends a reply message
        $this->actingAs($superadmin)->post(route('superadmin.tickets.messages.store', $ticket), [
            'message' => 'Estamos revisando el problema.',
        ]);

        $ticket->refresh();
        // Superadmin's message should auto-transition ticket to 'en proceso'
        $this->assertEquals('en proceso', $ticket->status);

        // Superadmin now approves the ticket
        $response = $this->actingAs($superadmin)->post(route('superadmin.tickets.status.update', $ticket), [
            'status' => 'aprobado',
        ]);

        $response->assertRedirect(route('superadmin.tickets.show', $ticket));
        $ticket->refresh();
        $this->assertEquals('aprobado', $ticket->status);
    }

    public function test_normal_user_cannot_access_superadmin_tickets(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('superadmin.tickets.index'));

        $response->assertStatus(302); // Redirected / unauthorized
    }
}
