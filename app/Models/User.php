<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'location',
        'is_supervisor',
        'color',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_supervisor' => 'boolean',
        ];
    }

    public function activities()
    {
        return $this->hasMany(Activity::class);
    }

    public function canEdit(Activity $activity): bool
    {
        return $this->is_supervisor || $activity->user_id === $this->id;
    }

    public function canDelete(Activity $activity): bool
    {
        return $this->canEdit($activity);
    }
}