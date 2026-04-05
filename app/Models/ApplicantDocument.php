<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ApplicantDocument extends Model
{
    use HasFactory;

    protected $fillable = [
        'applicant_id',
        'document_type',
        'file_content', 
        'original_filename',
        'mime_type',
        'file_size',
        'sha256',
        'scan_status',
    ];

    // This prevents the heavy binary data from being sent in every API call
    protected $hidden = ['file_content'];

    public function applicant()
    {
        return $this->belongsTo(Applicant::class);
    }
}
