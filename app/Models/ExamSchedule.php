<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExamSchedule extends Model
{
    use HasFactory;

    protected $fillable = [
        'exam_date',
        'exam_end_time',
        'room',
        'status',
    ];

    public function exams()
    {
        return $this->hasMany(EntranceExam::class, 'exam_schedule_id');
    }
}