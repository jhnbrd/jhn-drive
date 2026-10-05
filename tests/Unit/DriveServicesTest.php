<?php

namespace Tests\Unit;

use App\Services\DrivePathService;
use App\Services\FileMetadataService;
use PHPUnit\Framework\TestCase;

class DriveServicesTest extends TestCase
{
    public function test_metadata_classification_preserves_drive_and_vault_behavior(): void
    {
        $metadata = new FileMetadataService;

        $this->assertSame('application/octet-stream', $metadata->knownMimeType('svg'));
        $this->assertSame('other', $metadata->category('svg', 'image/svg+xml'));
        $this->assertSame('video', $metadata->category('mp4', 'application/octet-stream'));
        $this->assertSame('document', $metadata->category('docx', 'application/octet-stream'));
        $this->assertSame('other', $metadata->previewCategory('docx', 'application/octet-stream'));
        $this->assertSame('audio', $metadata->previewCategory('mp3', 'audio/mpeg'));
    }

    public function test_metadata_helpers_preserve_paths_and_safe_names(): void
    {
        $metadata = new FileMetadataService;

        $this->assertSame([
            ['name' => 'My Drive', 'path' => ''],
            ['name' => 'Projects', 'path' => 'Projects'],
            ['name' => 'JHN', 'path' => 'Projects/JHN'],
        ], $metadata->breadcrumbs('Projects/JHN'));

        $this->assertSame('unsafe_name_.txt', $metadata->sanitizeFilename('unsafe:name?.txt'));
        $this->assertSame('1.5 KB', $metadata->formatBytes(1536));
    }

    public function test_path_boundary_check_rejects_prefix_confusion(): void
    {
        $paths = new DrivePathService;
        $separator = DIRECTORY_SEPARATOR;
        $root = $separator.'drive'.$separator.'shared';

        $this->assertTrue($paths->isWithin($root, $root.$separator.'folder'.$separator.'file.txt'));
        $this->assertFalse($paths->isWithin($root, $root.'-private'.$separator.'secret.txt'));
    }
}
