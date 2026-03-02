<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Applicant extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'first_name',
        'last_name',
        'middle_name',
        'email',
        'phone_number',
        'date_of_birth',
        'address',
        'course_id',
    ];

    protected $casts = [
        'date_of_birth' => 'date',
    ];

    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_ENROLLED = 'enrolled';

    // Each applicant belongs to a course
    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Applicant can have many documents
    public function documents()
    {
        return $this->hasMany(ApplicantDocument::class);
    }

    // Applicant can have many exams
    public function exams()
    {
        return $this->hasMany(EntranceExam::class);
    }

    public function student()
    {
        return $this->hasOne(Student::class);
    }
}
