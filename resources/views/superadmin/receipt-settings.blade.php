@extends('layouts.app')

@section('content')
<main class="pos-shell">
    <header class="pos-header">
        <a class="header-brand header-brand-link" href="{{ route('superadmin.dashboard') }}"><img class="pos-logo" src="{{ asset('images/ES LOGO.jpg') }}" alt="EtivacSilog Logo"><span><span class="brand-name">ETIVACSILOG</span><span class="brand-subtitle">RECEIPT EDITOR</span></span></a>
        <div class="user-area"><a class="header-link" href="{{ route('superadmin.dashboard') }}">Admin</a><a class="header-link" href="{{ route('superadmin.accounts') }}">Accounts</a><div class="welcome-copy"><span>Administrator</span><strong>{{ auth()->user()->username }}</strong></div><form method="POST" action="{{ route('logout') }}">@csrf<button class="logout-button" type="submit">Log out</button></form></div>
    </header>
    <section class="page-content admin-page receipt-editor-page">
        <div class="page-heading"><div><p class="eyebrow">CHECKOUT CONFIGURATION</p><h1>Receipt editor</h1></div><a class="secondary-button link-button" href="{{ route('superadmin.dashboard') }}">Back to admin</a></div>
        @if (session('status'))<p class="status-message">{{ session('status') }}</p>@endif
        @foreach ($errors->all() as $error)<p class="field-error">{{ $error }}</p>@endforeach

        <div class="receipt-editor-layout">
            <form method="POST" action="{{ route('superadmin.receipt-settings.update') }}" class="receipt-editor-form" enctype="multipart/form-data">
                @csrf
                @method('PUT')
                <label>Receipt business name<input name="business_name" maxlength="120" value="{{ old('business_name', $settings->business_name) }}" required></label>
                <label>Address<input name="address" maxlength="255" value="{{ old('address', $settings->address) }}" placeholder="Optional address"></label>
                <label>Phone or contact<input name="phone" maxlength="60" value="{{ old('phone', $settings->phone) }}" placeholder="Optional phone number"></label>
                <label>Footer message<input name="footer" maxlength="255" value="{{ old('footer', $settings->footer) }}" placeholder="Thank you!"></label>
                <label>Thermal paper width<select name="paper_width" required><option value="58" @selected((string) old('paper_width', $settings->paper_width) === '58')>58 mm</option><option value="80" @selected((string) old('paper_width', $settings->paper_width) === '80')>80 mm</option></select></label>
                <label>Survey URL <span>(optional, for a future receipt QR code)</span><input name="survey_url" type="url" maxlength="500" value="{{ old('survey_url', $settings->survey_url) }}" placeholder="https://example.com/survey"></label>
                <label>Survey QR image <span>(optional PNG, up to 2 MB)</span><input name="survey_qr_file" type="file" accept="image/png"></label>
                @if ($settings->survey_qr_path)
                    <div class="receipt-qr-current"><img src="{{ asset('uploads/receipts/'.$settings->survey_qr_path) }}" alt="Current survey QR code"><span>Current survey QR</span><label><input type="checkbox" name="remove_survey_qr" value="1"> Remove QR image</label></div>
                @endif
                <button class="primary-button" type="submit">Save receipt settings</button>
            </form>

            <aside class="receipt-preview-panel">
                <p class="eyebrow">LIVE PREVIEW</p>
                <div class="receipt-preview" style="--receipt-width: {{ $settings->paper_width }}mm">
                    <img src="{{ asset('images/ES LOGO.jpg') }}" alt="EtivacSilog Logo">
                    <h2>{{ $settings->business_name }}</h2>
                    @if ($settings->address)<p>{{ $settings->address }}</p>@endif
                    @if ($settings->phone)<p>{{ $settings->phone }}</p>@endif
                    <hr><p>Order #0000 · {{ now()->format('M j, Y g:i A') }}</p><p>1 × Sample item <span>₱69.00</span></p><hr><strong>Total ₱69.00</strong>
                    @if ($settings->survey_qr_path)<img class="receipt-preview-qr" src="{{ asset('uploads/receipts/'.$settings->survey_qr_path) }}" alt="Survey QR code">@endif
                    @if ($settings->survey_url)<p class="preview-survey">Survey: {{ $settings->survey_url }}</p>@endif
                    @if ($settings->footer)<p class="preview-footer">{{ $settings->footer }}</p>@endif
                </div>
                <small>Receipt output is sized for {{ $settings->paper_width }} mm thermal paper.</small>
            </aside>
        </div>
    </section>
</main>
@endsection
