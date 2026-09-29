<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReceiptSetting extends Model
{
    protected $fillable = ['business_name', 'address', 'phone', 'footer', 'paper_width', 'survey_url', 'survey_qr_path'];
}