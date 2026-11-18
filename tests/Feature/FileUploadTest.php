<?php

namespace Tests\Feature;

use App\Exceptions\UploadFailedException;
use App\Models\Building;
use App\Models\Document;
use App\Models\MaintenanceRequest;
use App\Models\Property;
use App\Models\PropertyOwner;
use App\Models\Tenant;
use App\Models\Unit;
use App\Models\UnitImage;
use App\Models\User;
use App\Support\FileUpload;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

/** Custom local uploads with move(): folders, filenames, privacy and cleanup. */
class FileUploadTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private Property $property;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => 'admin']);
        $ownerUser = User::factory()->create(['role' => 'owner']);
        $owner = PropertyOwner::forceCreate(['user_id' => $ownerUser->id, 'owner_number' => 'O1', 'name' => 'Olive Owner', 'status' => 'active', 'created_by' => $this->admin->id]);
        $this->property = Property::forceCreate(['property_owner_id' => $owner->id, 'property_code' => 'P1', 'name' => 'Kacyiru Heights', 'property_type' => 'residential', 'status' => 'active', 'created_by' => $this->admin->id]);
    }

    public function test_files_are_moved_into_project_folders_with_unique_names(): void
    {
        $path = FileUpload::store(UploadedFile::fake()->image('Front View.JPG'), FileUpload::PROPERTY_IMAGES);

        $this->assertMatchesRegularExpression('#^image/properties/\d{10}_[0-9a-f]{13}\.jpg$#', $path);
        $this->assertFileExists(config('uploads.public_root') . '/image/properties/' . basename($path));
        $this->assertSame(asset($path), upload_url($path));

        $second = FileUpload::store(UploadedFile::fake()->image('Front View.JPG'), FileUpload::PROPERTY_IMAGES);
        $this->assertNotSame($path, $second, 'two uploads never overwrite each other');

        // Private files go under uploads/, never under public/.
        $contract = FileUpload::store(UploadedFile::fake()->create('lease.pdf', 50, 'application/pdf'), FileUpload::CONTRACTS, true);
        $this->assertStringStartsWith('documents/contracts/', $contract);
        $this->assertFileExists(config('uploads.private_root') . '/' . $contract);
        $this->assertFileDoesNotExist(config('uploads.public_root') . '/' . $contract);

        $this->assertTrue(FileUpload::delete($path));
        $this->assertUploadMissing($path);
    }

    public function test_dangerous_names_and_paths_are_neutralised(): void
    {
        $path = FileUpload::store(UploadedFile::fake()->create('shell.php', 1, 'image/png'), FileUpload::PROPERTY_IMAGES);
        $this->assertStringEndsNotWith('.php', $path);

        $this->assertNull(FileUpload::absolutePath('../../.env'));
        $this->assertNull(FileUpload::absolutePath('image/../../.env', true));
        $this->assertFalse(FileUpload::delete('../composer.json'));
        $this->assertFileExists(base_path('composer.json'));

        $this->expectException(UploadFailedException::class);
        FileUpload::store(UploadedFile::fake()->image('a.jpg'), '../outside');
    }

    public function test_building_images_are_uploaded_and_deleted_with_their_record(): void
    {
        $this->actingAs($this->admin)->post(route('admin.properties.buildings.store', $this->property), [
            'building_code' => 'B1', 'name' => 'Block A', 'number_of_floors' => 3, 'status' => 'active',
            'images' => [UploadedFile::fake()->image('block.jpg'), UploadedFile::fake()->image('entrance.png')],
        ])->assertSessionHasNoErrors();

        $building = Building::firstOrFail();
        $paths = $building->images()->orderBy('sort_order')->pluck('image_path');
        $this->assertCount(2, $paths);
        foreach ($paths as $p) {
            $this->assertStringStartsWith('image/buildings/', $p);
            $this->assertUploadExists($p);
        }

        // Removing an image record removes its file.
        $unit = Unit::forceCreate(['building_id' => $building->id, 'unit_number' => '101', 'unit_type' => 'apartment', 'rent_amount' => 300000, 'status' => 'available']);
        $unitPath = FileUpload::store(UploadedFile::fake()->image('room.jpg'), FileUpload::UNIT_IMAGES);
        $image = UnitImage::create(['unit_id' => $unit->id, 'image_path' => $unitPath, 'is_primary' => true, 'sort_order' => 0]);

        $this->actingAs($this->admin)->delete(route('admin.properties.units.images.destroy', [$this->property, $unit, $image]))->assertSessionHas('success');
        $this->assertUploadMissing($unitPath);
    }

    public function test_replacing_or_permanently_deleting_a_record_cleans_up_old_files(): void
    {
        $building = Building::forceCreate(['property_id' => $this->property->id, 'building_code' => 'B1', 'name' => 'Block A', 'status' => 'active']);
        $unit = Unit::forceCreate(['building_id' => $building->id, 'unit_number' => '101', 'unit_type' => 'apartment', 'rent_amount' => 300000, 'status' => 'occupied']);
        $tenant = Tenant::forceCreate(['tenant_number' => 'T1', 'full_name' => 'Tina Tenant', 'phone' => '+250788111111', 'status' => 'active']);

        $first = FileUpload::store(UploadedFile::fake()->image('leak.jpg'), FileUpload::MAINTENANCE_IMAGES);
        $request = MaintenanceRequest::forceCreate(['request_number' => 'MNT-1', 'tenant_id' => $tenant->id, 'unit_id' => $unit->id, 'title' => 'Leak',
            'description' => 'Kitchen tap', 'category' => 'plumbing', 'priority' => 'medium', 'status' => 'open', 'attachment_path' => $first]);

        $second = FileUpload::store(UploadedFile::fake()->image('leak-2.jpg'), FileUpload::MAINTENANCE_IMAGES);
        $request->update(['attachment_path' => $second]);
        $this->assertUploadMissing($first);
        $this->assertUploadExists($second);

        // Soft delete keeps the file (the record can be restored); permanent delete removes it.
        $request->delete();
        $this->assertUploadExists($second);
        $request->forceDelete();
        $this->assertUploadMissing($second);
    }

    public function test_legacy_storage_files_are_moved_into_the_new_folders(): void
    {
        $legacyImage = 'properties/legacy-' . uniqid() . '.jpg';
        $legacyDoc = 'documents/property/' . $this->property->id . '/legacy-' . uniqid() . '.pdf';
        @mkdir(dirname(storage_path('app/public/' . $legacyImage)), 0755, true);
        @mkdir(dirname(storage_path('app/private/' . $legacyDoc)), 0755, true);
        file_put_contents(storage_path('app/public/' . $legacyImage), 'jpg-bytes');
        file_put_contents(storage_path('app/private/' . $legacyDoc), '%PDF-1.4');

        $imageId = \App\Models\PropertyImage::create(['property_id' => $this->property->id, 'image_path' => $legacyImage, 'is_primary' => true, 'sort_order' => 0])->id;
        $docId = $this->property->documents()->create(['category' => 'title_deed', 'title' => 'Deed', 'path' => $legacyDoc, 'original_name' => 'deed.pdf',
            'mime_type' => 'application/pdf', 'size' => 8, 'uploaded_by' => $this->admin->id, 'status' => 'not_required'])->id;

        $this->artisan('uploads:migrate-legacy')->assertSuccessful();

        $newImage = \App\Models\PropertyImage::find($imageId)->image_path;
        $newDoc = Document::find($docId)->path;
        $this->assertMatchesRegularExpression('#^image/properties/\d{10}_[0-9a-f]{13}\.jpg$#', $newImage);
        $this->assertStringStartsWith('documents/properties/', $newDoc);
        $this->assertUploadExists($newImage);
        $this->assertUploadExists($newDoc, true);
        $this->assertFileDoesNotExist(storage_path('app/public/' . $legacyImage));
        $this->assertFileDoesNotExist(storage_path('app/private/' . $legacyDoc));

        // Running again changes nothing.
        $this->artisan('uploads:migrate-legacy')->assertSuccessful();
        $this->assertSame($newImage, \App\Models\PropertyImage::find($imageId)->image_path);
    }

    public function test_shared_hosting_public_html_becomes_the_upload_web_root(): void
    {
        $publicHtml = $this->uploadsRoot . DIRECTORY_SEPARATOR . 'public_html';
        mkdir($publicHtml, 0755, true);

        // APP_PUBLIC_PATH=/home/user/public_html with no uploads override.
        config(['uploads.public_root' => null, 'app.public_path' => $publicHtml]);
        $this->app->register(\App\Providers\AppServiceProvider::class, true);

        $this->assertSame($publicHtml, public_path());
        $path = FileUpload::store(UploadedFile::fake()->image('front.jpg'), FileUpload::PROPERTY_IMAGES);
        $this->assertFileExists($publicHtml . '/' . $path);

        // Images saved earlier in the project public/ folder are moved across.
        $oldPublic = $this->uploadsRoot . DIRECTORY_SEPARATOR . 'project-public';
        mkdir($oldPublic . '/image/units', 0755, true);
        file_put_contents($oldPublic . '/image/units/1731000000_abc.jpg', 'jpg');

        $this->artisan('uploads:move-public', ['--from' => $oldPublic])->assertSuccessful();
        $this->assertFileExists($publicHtml . '/image/units/1731000000_abc.jpg');
        $this->assertFileDoesNotExist($oldPublic . '/image/units/1731000000_abc.jpg');
    }

    public function test_documents_are_private_and_streamed_only_to_authorised_users(): void
    {
        $this->actingAs($this->admin)->post(route('documents.store'), [
            'owner' => 'property:' . $this->property->id, 'category' => 'title_deed', 'title' => 'Title deed',
            'file' => UploadedFile::fake()->create('deed.pdf', 80, 'application/pdf'),
        ])->assertSessionHasNoErrors();

        $doc = Document::firstOrFail();
        $this->assertStringStartsWith('documents/properties/', $doc->path);
        $this->assertSame('deed.pdf', $doc->original_name);
        $this->assertSame('application/pdf', $doc->mime_type);
        $this->assertGreaterThan(0, $doc->size);
        $this->assertUploadExists($doc->path, true);
        $this->assertFileDoesNotExist(config('uploads.public_root') . '/' . $doc->path);

        $this->actingAs($this->admin)->get(route('documents.download', $doc))->assertOk()->assertHeader('content-type', 'application/pdf');
        $this->actingAs($this->admin)->get(route('documents.download', [$doc, 'download' => 1]))->assertOk()->assertDownload('deed.pdf');

        $stranger = User::factory()->create(['role' => 'tenant']);
        $this->actingAs($stranger)->get(route('documents.download', $doc))->assertNotFound();
    }
}
