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
        'file_path',
    ];

    // Each document belongs to an applicant
    public function applicant()
    {
        return $this->belongsTo(Applicant::class);
    }
}