<?php

namespace Tests\Feature;

use App\Support\Media;
use Illuminate\Queue\Events\QueueBusy;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

/**
 * Production wiring that is easy to break silently: media URLs on the
 * configured disk/CDN and a health check that covers the app's dependencies.
 */
class InfrastructureTest extends TestCase
{
    public function test_media_paths_resolve_to_the_configured_disk_and_cdn()
    {
        config([
            'shop.media_disk' => 's3',
            'filesystems.disks.s3.region' => 'eu-west-2',
            'filesystems.disks.s3.bucket' => 'loom-lane-media',
            'filesystems.disks.s3.url' => 'https://cdn.example.com',
        ]);

        $this->assertSame('https://cdn.example.com/products/merino-jumper.jpg', Media::url('products/merino-jumper.jpg'));
    }

    public function test_media_paths_use_the_public_disk_locally()
    {
        config(['shop.media_disk' => 'public', 'filesystems.disks.public.url' => 'http://localhost/storage']);

        $this->assertSame('http://localhost/storage/products/a.jpg', Media::url('products/a.jpg'));
    }

    public function test_absolute_urls_and_missing_images_pass_through()
    {
        $this->assertSame('https://images.example.com/a.jpg', Media::url('https://images.example.com/a.jpg'));
        $this->assertSame('/images/placeholder.svg', Media::url('/images/placeholder.svg'));
        $this->assertNull(Media::url(null));
        $this->assertNull(Media::url(''));
    }

    public function test_health_check_passes_when_dependencies_are_up()
    {
        $this->get('/up')->assertOk();
    }

    public function test_health_check_fails_when_the_database_is_unreachable()
    {
        config([
            'app.debug' => false,
            'database.connections.unreachable' => ['driver' => 'sqlite', 'database' => '/nonexistent/db.sqlite', 'prefix' => ''],
            'database.default' => 'unreachable',
        ]);
        DB::purge('unreachable');

        $this->get('/up')->assertStatus(500);
    }

    public function test_a_queue_backlog_is_logged_as_a_warning()
    {
        Log::spy();

        event(new QueueBusy('redis', 'default', 250));

        Log::shouldHaveReceived('warning')->once()->withArgs(fn ($message, $context) => $context['size'] === 250);
    }
}
