<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\ControllerTestCase;

class BatchProcessingControllerTest extends ControllerTestCase
{
    use RefreshDatabase;

    protected User $user;

    protected Account $account;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->account = Account::factory()->create([
            'user_uuid' => $this->user->uuid,
            'status'    => 'active',
        ]);
    }

    #[Test]
    public function test_authenticated_user_can_access_batch_processing_index()
    {
        $response = $this->actingAs($this->user)
            ->get(route('batch-processing.index'));

        $response->assertStatus(200)
            ->assertViewIs('batch-processing.index')
            ->assertViewHas(['batchJobs', 'statistics']);
    }

    #[Test]
    public function test_authenticated_user_can_access_create_form()
    {
        $response = $this->actingAs($this->user)
            ->get(route('batch-processing.create'));

        $response->assertStatus(200)
            ->assertViewIs('batch-processing.create')
            ->assertViewHas(['accounts', 'templates']);
    }

    #[Test]
    public function test_authenticated_user_can_view_batch_job()
    {
        $batchJob = BatchJob::factory()->create([
            'user_uuid' => $this->user->uuid,
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('batch-processing.show', $batchJob->uuid));

        $response->assertStatus(200)
            ->assertViewIs('batch-processing.show')
            ->assertViewHas(['batchJob', 'items']);
    }

    #[Test]
    public function test_user_cannot_view_other_users_batch_job()
    {
        $otherUser = User::factory()->create();
        $batchJob = BatchJob::factory()->create([
            'user_uuid' => $otherUser->uuid,
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('batch-processing.show', $batchJob->uuid));

        $response->assertStatus(404);
    }

    #[Test]
    public function test_batch_creation_validation_fails_with_invalid_data()
    {
        $response = $this->actingAs($this->user)
            ->post(route('batch-processing.store'), [
                'name'  => '',
                'type'  => 'invalid',
                'items' => 'not-an-array',
            ]);

        $response->assertSessionHasErrors(['name', 'type', 'items']);
    }

    #[Test]
    public function test_can_cancel_pending_batch_job()
    {
        $batchJob = BatchJob::factory()->create([
            'user_uuid' => $this->user->uuid,
            'status'    => 'pending',
        ]);

        $response = $this->actingAs($this->user)
            ->post(route('batch-processing.cancel', $batchJob->uuid));

        $response->assertRedirect()
            ->assertSessionHas('success');
    }

    #[Test]
    public function test_cannot_cancel_completed_batch_job()
    {
        $batchJob = BatchJob::factory()->create([
            'user_uuid' => $this->user->uuid,
            'status'    => 'completed',
        ]);

        $response = $this->actingAs($this->user)
            ->post(route('batch-processing.cancel', $batchJob->uuid));

        $response->assertRedirect()
            ->assertSessionHasErrors();
    }

    #[Test]
    public function test_unauthenticated_user_cannot_access_batch_processing()
    {
        $response = $this->get(route('batch-processing.index'));

        $response->assertRedirect(route('login'));
    }
}
