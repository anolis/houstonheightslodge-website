<?php

namespace Tests\Feature;

use App\Services\FacebookEvents;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class FacebookEventsTest extends TestCase
{
    private string $snapshotPath;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->snapshotPath = storage_path('framework/testing/facebook-'.bin2hex(random_bytes(8)).'.json');
        config([
            'facebook.page_id' => '12345',
            'facebook.page_access_token' => 'private-test-token',
            'facebook.graph_version' => 'v24.0',
            'facebook.calendar_enabled' => true,
            'facebook.snapshot_path' => $this->snapshotPath,
        ]);
        Http::preventStrayRequests();
    }

    protected function tearDown(): void
    {
        File::delete($this->snapshotPath);
        parent::tearDown();
    }

    private function event(array $overrides = []): array
    {
        return array_replace([
            'id' => '100', 'name' => 'Sausage Fest',
            'description' => '<script>alert("test")</script> Family fun!',
            'start_time' => '2026-09-26T12:00:00-0500',
            'end_time' => '2026-09-26T16:00:00-0500',
            'timezone' => 'America/Chicago',
            'place' => ['name' => 'Lodge #225', 'location' => ['street' => '115 E. 14th Street']],
        ], $overrides);
    }

    public function test_sync_paginates_and_publishes_only_normalized_events(): void
    {
        Http::fakeSequence('graph.facebook.com/*')
            ->push(['data' => [$this->event()], 'paging' => ['next' => 'https://untrusted.invalid/?access_token=secret', 'cursors' => ['after' => 'next-page']]])
            ->push(['data' => [$this->event(['id' => '101', 'is_canceled' => true]), $this->event(['id' => '102', 'name' => 'National Night Out'])]]);

        $this->artisan('facebook:sync-events')->assertSuccessful();
        Http::assertSentCount(2);
        Http::assertSent(fn ($request) => str_starts_with($request->url(), 'https://graph.facebook.com/v24.0/12345/events')
            && $request->hasHeader('Authorization', 'Bearer private-test-token') && ($request['after'] ?? null) === 'next-page');
        $this->get('/events/feed')->assertOk()
            ->assertJsonCount(2, 'events')
            ->assertJsonPath('events.0.start', '2026-09-26T17:00:00+00:00')
            ->assertJsonPath('events.0.url', 'https://www.facebook.com/events/100/')
            ->assertJsonPath('events.0.extendedProps.location', 'Lodge #225, 115 E. 14th Street')
            ->assertDontSee('private-test-token');
    }

    public function test_failed_sync_keeps_the_previous_complete_snapshot(): void
    {
        Http::fakeSequence('graph.facebook.com/*')
            ->push(['data' => [$this->event()]])
            ->push(['data' => [$this->event(['name' => 'Incomplete update'])], 'paging' => ['next' => 'next', 'cursors' => ['after' => 'page2']]])
            ->push(['error' => ['message' => 'private-test-token']], 403);
        $service = app(FacebookEvents::class);
        $previous = $service->sync();
        $this->artisan('facebook:sync-events')->assertFailed();
        $this->assertSame($previous, $service->snapshot());
        $this->get('/events/feed')->assertOk()->assertJsonPath('events.0.title', 'Sausage Fest');
    }

    public function test_successful_empty_sync_removes_deleted_events(): void
    {
        Http::fakeSequence('graph.facebook.com/*')->push(['data' => [$this->event()]])->push(['data' => []]);
        $service = app(FacebookEvents::class);
        $service->sync();
        $service->sync();
        $this->get('/events/feed')->assertOk()->assertJsonCount(0, 'events');
    }

    public function test_recurring_events_preserve_each_occurrence_and_dst_offset(): void
    {
        Http::fake(['graph.facebook.com/*' => Http::response(['data' => [$this->event([
            'event_times' => [
                ['id' => '201', 'start_time' => '2026-10-06T19:30:00-0500', 'end_time' => '2026-10-06T20:30:00-0500'],
                ['id' => '202', 'start_time' => '2026-11-03T19:30:00-0600', 'end_time' => '2026-11-03T20:30:00-0600'],
            ],
        ])]])]);
        app(FacebookEvents::class)->sync();
        $this->get('/events/feed')->assertJsonPath('events.0.start', '2026-10-07T00:30:00+00:00')
            ->assertJsonPath('events.1.start', '2026-11-04T01:30:00+00:00');
    }

    public function test_native_calendar_replaces_iframe_without_exposing_credentials(): void
    {
        $response = $this->get('/')->assertOk()->assertSee('data-lodge-calendar', false)
            ->assertDontSee('widgets.sociablekit.com/facebook-page-events/iframe')
            ->assertDontSee('private-test-token');
        $this->assertStringNotContainsString('widgets.sociablekit.com', $response->headers->get('Content-Security-Policy'));
        Http::assertNothingSent();
        $this->get('/events/feed')->assertStatus(503);
    }
}
