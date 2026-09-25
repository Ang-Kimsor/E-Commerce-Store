<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use App\Enums\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProductImageTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected Category $category;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::create([
            'name' => 'Admin Tester',
            'email' => 'admin_tester@example.com',
            'password' => bcrypt('password123'),
            'role' => UserRole::Admin,
            'is_active' => true,
        ]);

        $this->category = Category::create([
            'name' => 'Beverages',
            'slug' => 'beverages',
            'is_active' => true,
        ]);
    }

    public function test_old_product_image_is_deleted_when_image_is_removed_on_update(): void
    {
        Storage::fake('public');

        $fakeOldImage = 'products/old_product_image_test.jpg';
        Storage::disk('public')->put($fakeOldImage, 'fake image binary data');
        $this->assertTrue(Storage::disk('public')->exists($fakeOldImage));

        $product = Product::create([
            'name' => 'Coca Cola',
            'sku' => 'COCA-001',
            'slug' => 'coca-cola',
            'price' => 1.50,
            'stock' => 100,
            'category_id' => $this->category->id,
            'is_active' => true,
            'image' => '/storage/' . $fakeOldImage,
        ]);

        // Update product with image => null (user clicked remove image)
        $response = $this->actingAs($this->admin)
            ->putJson('/api/admin/products/' . $product->id, [
                'name' => $product->name,
                'sku' => $product->sku,
                'slug' => $product->slug,
                'price' => $product->price,
                'stock' => $product->stock,
                'category_id' => $this->category->id,
                'image' => null,
            ]);

        $response->assertStatus(200);

        // Assert old file was removed from the backend folder
        $this->assertFalse(
            Storage::disk('public')->exists($fakeOldImage),
            'The old product image file should have been deleted from storage'
        );

        $product->refresh();
        $this->assertNull($product->image);
    }

    public function test_old_product_image_is_deleted_when_replaced_with_new_image(): void
    {
        Storage::fake('public');

        $fakeOldImage = 'products/old_to_be_replaced.jpg';
        Storage::disk('public')->put($fakeOldImage, 'old content');
        $this->assertTrue(Storage::disk('public')->exists($fakeOldImage));

        $product = Product::create([
            'name' => 'Pepsi',
            'sku' => 'PEPSI-001',
            'slug' => 'pepsi',
            'price' => 1.25,
            'stock' => 50,
            'category_id' => $this->category->id,
            'is_active' => true,
            'image' => '/storage/' . $fakeOldImage,
        ]);

        // 1x1 transparent PNG as base64 data URL
        $newBase64 = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==';

        $response = $this->actingAs($this->admin)
            ->putJson('/api/admin/products/' . $product->id, [
                'name' => $product->name,
                'sku' => $product->sku,
                'slug' => $product->slug,
                'price' => $product->price,
                'stock' => $product->stock,
                'category_id' => $this->category->id,
                'image' => $newBase64,
            ]);

        $response->assertStatus(200);

        // Old file must be deleted
        $this->assertFalse(
            Storage::disk('public')->exists($fakeOldImage),
            'The replaced product image file should have been deleted from storage'
        );

        // Product should have new image
        $product->refresh();
        $this->assertNotNull($product->image);
        $this->assertNotEquals('/storage/' . $fakeOldImage, $product->image);
    }

    public function test_product_image_is_preserved_when_image_is_not_changed_on_update(): void
    {
        Storage::fake('public');

        $fakeImage = 'products/keep_this_image.jpg';
        Storage::disk('public')->put($fakeImage, 'image content');
        $this->assertTrue(Storage::disk('public')->exists($fakeImage));

        $product = Product::create([
            'name' => 'Sprite',
            'sku' => 'SPRITE-001',
            'slug' => 'sprite',
            'price' => 1.10,
            'stock' => 75,
            'category_id' => $this->category->id,
            'is_active' => true,
            'image' => '/storage/' . $fakeImage,
        ]);

        // Update product keeping existing normalized image URL
        $response = $this->actingAs($this->admin)
            ->putJson('/api/admin/products/' . $product->id, [
                'name' => 'Sprite Updated',
                'sku' => $product->sku,
                'slug' => $product->slug,
                'price' => 1.20,
                'stock' => 75,
                'category_id' => $this->category->id,
                'image' => $product->image, // e.g. /api/storage/products/keep_this_image.jpg
            ]);

        $response->assertStatus(200);

        // The image file should STILL exist
        $this->assertTrue(
            Storage::disk('public')->exists($fakeImage),
            'The unchanged product image file should still exist in storage'
        );
    }
}
