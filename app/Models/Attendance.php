<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Attendance extends Model
{
    protected $fillable = ['team_member_id', 'from_date', 'to_date', 'activity'];

    public function member()
    {
        return $this->belongsTo(TeamMember::class, 'team_member_id');
    }
}