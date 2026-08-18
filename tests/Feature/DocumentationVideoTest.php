<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DocumentationVideoTest extends TestCase
{
    use RefreshDatabase;

    public function test_s3_video_page_uses_stable_application_route_and_stream_generates_fresh_signed_url(): void
    {
        Storage::fake('s3');
        Storage::disk('s3')->put('documentation/videos/video_01.mp4', 'video-content');
        Storage::disk('s3')->buildTemporaryUrlsUsing(
            fn (string $path): string => 'https://space.example/'.$path.'?signature=fresh'
        );

        config([
            'alertbook.documentation.driver' => 's3',
            'alertbook.documentation.disk' => 's3',
            'alertbook.documentation.prefix' => 'documentation/videos',
            'filesystems.disks.s3.bucket' => 'alertbook-documentation',
            'filesystems.disks.s3.endpoint' => 'https://nyc3.digitaloceanspaces.com',
            'filesystems.disks.s3.key' => 'test-key',
            'filesystems.disks.s3.secret' => 'test-secret',
            'filesystems.disks.s3.region' => 'nyc3',
        ]);

        $user = User::factory()->create(['is_active' => true]);
        $this->actingAs($user);

        $key = substr(sha1('documentation/videos/video_01.mp4'), 0, 16);
        $streamRoute = route('documentation.videos.stream', $key);

        $this->get(route('documentation.videos'))
            ->assertOk()
            ->assertSee($streamRoute, false)
            ->assertDontSee('signature=fresh', false);

        $this->get($streamRoute)
            ->assertRedirect('https://space.example/documentation/videos/video_01.mp4?signature=fresh');
    }
}
