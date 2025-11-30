<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Feedback extends Model
{
    use HasFactory;

    /**
     * Explicitly set the table name since Laravel might not pluralize "Feedback" correctly
     */
    protected $table = 'feedbacks';

    protected $fillable = [
        'ride_id',
        'from_user_id',
        'to_user_id',
        'from_role',
        'rating',
        'comment',
    ];

    protected $casts = [
        'rating' => 'integer',
    ];

    public function ride()
    {
        return $this->belongsTo(Ride::class);
    }

    public function fromUser()
    {
        return $this->belongsTo(User::class, 'from_user_id');
    }

    public function toUser()
    {
        return $this->belongsTo(User::class, 'to_user_id');
    }
}

