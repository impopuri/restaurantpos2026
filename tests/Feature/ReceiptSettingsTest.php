<?php

namespace Tests\Feature;

use App\Models\MenuItem;
use App\Models\ReceiptSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class ReceiptSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_superadmin_can_save_receipt_content_and_paper_width(): void
    {
        $admin = User::factory()->create(['role' => 'superadmin']);

        $this->actingAs($admin)
            ->put(route('superadmin.receipt-settings.update'), [
                'business_name' => 'EtivacSilog Main Branch',
                'address' => '123 Market Road',
                'phone' => '09171234567',
                'footer' => 'Salamat po!',
                'paper_width' => '58',
                'survey_url' => 'https://example.com/survey',
            ])
            ->assertRedirect(route('superadmin.receipt-settings'));

        $this->assertDatabaseHas('receipt_settings', [
            'id' => 1,
            'business_name' => 'EtivacSilog Main Branch',
            'paper_width' => '58',
            'survey_url' => 'https://example.com/survey',
        ]);

        $this->post(route('cart.add'), ['product_key' => 'tohsilog']);
        $this->get(route('cart'))
            ->assertOk()
            ->assertSee('EtivacSilog Main Branch')
            ->assertSee('--receipt-width: 58mm', false)
            ->assertSee('https://example.com/survey');
    }

    public function test_cashier_cannot_edit_receipt_settings(): void
    {
        $cashier = User::factory()->create(['role' => 'cashier']);

        $this->actingAs($cashier)
            ->get(route('superadmin.receipt-settings'))
            ->assertForbidden();
    }

    public function test_superadmin_can_upload_and_remove_survey_qr_png(): void
    {
        $admin = User::factory()->create(['role' => 'superadmin']);
        $this->actingAs($admin)
            ->put(route('superadmin.receipt-settings.update'), [
                'business_name' => 'EtivacSilog',
                'paper_width' => '80',
                'survey_qr_file' => UploadedFile::fake()->create('survey.png', 1, 'image/png'),
            ])
            ->assertRedirect(route('superadmin.receipt-settings'));

        $settings = ReceiptSetting::firstOrFail();
        $savedQrPath = public_path('uploads/receipts/'.$settings->survey_qr_path);
        $this->assertFileExists($savedQrPath);

        $this->post(route('cart.add'), ['product_key' => 'tohsilog']);
        $this->get(route('cart'))
            ->assertOk()
            ->assertSee('id="receipt-qr-image"', false)
            ->assertSee($settings->survey_qr_path);

        $this->put(route('superadmin.receipt-settings.update'), [
            'business_name' => 'EtivacSilog',
            'paper_width' => '80',
            'remove_survey_qr' => '1',
        ])->assertRedirect(route('superadmin.receipt-settings'));

        $this->assertNull($settings->fresh()->survey_qr_path);
        $this->assertFileDoesNotExist($savedQrPath);
    }
}