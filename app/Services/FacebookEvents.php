<?php

namespace App\Services;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class FacebookEvents
{
    public function snapshot(): ?array
    {
        $path = config('facebook.snapshot_path');
        if (! File::exists($path)) {
            return null;
        }

        $snapshot = json_decode(File::get($path), true);

        return is_array($snapshot) && isset($snapshot['events'], $snapshot['updatedAt']) ? $snapshot : null;
    }

    public function sync(): array
    {
        $page = (string) config('facebook.page_id');
        $version = (string) config('facebook.graph_version');
        $token = config('facebook.page_access_token');
        if (! preg_match('/^\d+$/', $page) || ! preg_match('/^v\d+\.\d+$/', $version) || ! $token) {
            throw new RuntimeException('Configure the Facebook Page ID, Graph API version, and Page access token before syncing.');
        }

        $events = [];
        $after = null;
        for ($pageNumber = 0; $pageNumber < 20; $pageNumber++) {
            $query = [
                'fields' => 'id,name,description,start_time,end_time,timezone,place,cover,is_canceled,event_times',
                'since' => CarbonImmutable::now('America/Chicago')->subMonth()->timestamp,
                'until' => CarbonImmutable::now('America/Chicago')->addYear()->timestamp,
                'limit' => 100,
            ];
            if ($after !== null) {
                $query['after'] = $after;
            }
            // Tokens never appear in public JSON, query strings, or log messages.
            $response = Http::withToken($token)->acceptJson()->connectTimeout(5)->timeout(20)
                ->get("https://graph.facebook.com/{$version}/{$page}/events", $query);
            if (! $response->successful() || ! is_array($response->json('data'))) {
                throw new RuntimeException('Facebook event sync failed (HTTP '.$response->status().'). Check the app permissions and Page access token.');
            }
            foreach ($response->json('data') as $event) {
                if (! empty($event['is_canceled'])) {
                    continue;
                }
                $occurrences = $event['event_times'] ?? [];
                if (isset($occurrences['data'])) {
                    $occurrences = $occurrences['data'];
                }
                foreach ($occurrences ?: [$event] as $occurrence) {
                    if (! empty($occurrence['is_canceled'])) {
                        continue;
                    }
                    $normalized = $this->normalize(array_replace($event, $occurrence));
                    $events[$normalized['id']] = $normalized;
                }
            }
            if (! $response->json('paging.next')) {
                $after = null;
                break;
            }
            $cursor = $response->json('paging.cursors.after');
            if (! is_string($cursor) || $cursor === '' || $cursor === $after) {
                throw new RuntimeException('Facebook returned invalid pagination; the previous event snapshot was retained.');
            }
            $after = $cursor;
        }
        if ($after !== null) {
            throw new RuntimeException('Facebook event pagination exceeded the sync limit; the previous snapshot was retained.');
        }
        $events = array_values($events);
        usort($events, fn (array $a, array $b) => strcmp($a['start'], $b['start']));
        $snapshot = ['events' => $events, 'updatedAt' => CarbonImmutable::now()->toIso8601String()];
        $path = config('facebook.snapshot_path');
        File::ensureDirectoryExists(dirname($path));
        // Replace only after every API page succeeds. A failure leaves the last good feed intact.
        File::replace($path, json_encode($snapshot, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES));

        return $snapshot;
    }

    private function normalize(array $event): array
    {
        if (! preg_match('/^\d+$/', (string) ($event['id'] ?? '')) || empty($event['name']) || empty($event['start_time'])) {
            throw new RuntimeException('Facebook returned an incomplete event; the previous snapshot was retained.');
        }
        $zone = $event['timezone'] ?? 'America/Chicago';
        $place = $event['place'] ?? [];
        $address = $place['location'] ?? [];
        $start = CarbonImmutable::parse($event['start_time'], $zone)->utc();
        $end = isset($event['end_time']) ? CarbonImmutable::parse($event['end_time'], $zone)->utc() : null;
        $cover = $event['cover']['source'] ?? null;

        return [
            'id' => (string) $event['id'],
            'title' => (string) $event['name'],
            'start' => $start->toIso8601String(),
            'end' => $end?->toIso8601String(),
            'url' => 'https://www.facebook.com/events/'.$event['id'].'/',
            'extendedProps' => [
                'description' => (string) ($event['description'] ?? ''),
                'location' => implode(', ', array_filter([
                    $place['name'] ?? null, $address['street'] ?? null,
                    $address['city'] ?? null, $address['state'] ?? null, $address['zip'] ?? null,
                ])),
                'cover' => is_string($cover) && str_starts_with($cover, 'https://') ? $cover : null,
            ],
        ];
    }
}
