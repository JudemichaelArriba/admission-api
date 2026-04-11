<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EntranceExam extends Model
{
    use HasFactory;

    protected $fillable = [
        'applicant_id',
        'exam_schedule_id', 
        'exam_score',
        'status',
    ];

    public function applicant()
    {
        return $this->belongsTo(Applicant::class);
    }

    // New relationship linking back to the master schedule
    public function schedule()
    {
        return $this->belongsTo(ExamSchedule::class, 'exam_schedule_id');
    }
}