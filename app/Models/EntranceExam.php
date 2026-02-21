<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EntranceExam extends Model
{
    use HasFactory;

    protected $fillable = [
        'applicant_id',
        'exam_date',
        'exam_score',
        'status',
    ];

    // Each exam belongs to an applicant
    public function applicant()
    {
        return $this->belongsTo(Applicant::class);
    }
}