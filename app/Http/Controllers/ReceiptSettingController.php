<?php

namespace App\Http\Controllers;

use App\Models\ReceiptSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ReceiptSettingController extends Controller
{
    public function edit(): View
    {
        return view('superadmin.receipt-settings', [
            'settings' => ReceiptSetting::firstOrCreate(['id' => 1]),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'business_name' => ['required', 'string', 'max:120'],
            'address' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:60'],
            'footer' => ['nullable', 'string', 'max:255'],
            'paper_width' => ['required', 'in:58,80'],
            'survey_url' => ['nullable', 'url', 'max:500'],
            'survey_qr_file' => ['nullable', 'file', 'mimes:png', 'max:2048'],
            'remove_survey_qr' => ['nullable', 'boolean'],
        ]);

        $settings = ReceiptSetting::firstOrCreate(['id' => 1]);
        unset($data['survey_qr_file'], $data['remove_survey_qr']);
        $oldQrPath = $settings->survey_qr_path;

        if ($request->boolean('remove_survey_qr')) {
            $data['survey_qr_path'] = null;
        }

        if ($request->hasFile('survey_qr_file')) {
            $directory = public_path('uploads/receipts');
            File::ensureDirectoryExists($directory);
            $fileName = Str::uuid().'.png';
            $request->file('survey_qr_file')->move($directory, $fileName);
            $data['survey_qr_path'] = $fileName;
        }

        $settings->fill($data)->save();

        if ($oldQrPath && array_key_exists('survey_qr_path', $data) && $oldQrPath !== $data['survey_qr_path']) {
            File::delete(public_path('uploads/receipts/'.$oldQrPath));
        }

        return redirect()->route('superadmin.receipt-settings')->with('status', 'Receipt settings saved.');
    }
}