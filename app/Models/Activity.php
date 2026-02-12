<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Activity extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'note',
        'start_date',
        'end_date',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Zkontroluje, zda existuje překryv s jinými aktivitami stejného uživatele
     */
    public function hasOverlap(): bool
    {
        $query = static::where('user_id', $this->user_id)
            ->where(function ($q) {
                $q->whereBetween('start_date', [$this->start_date, $this->end_date])
                  ->orWhereBetween('end_date', [$this->start_date, $this->end_date])
                  ->orWhere(function ($q) {
                      $q->where('start_date', '<=', $this->start_date)
                        ->where('end_date', '>=', $this->end_date);
                  });
            });

        if ($this->exists) {
            $query->where('id', '!=', $this->id);
        }

        return $query->exists();
    }

    /**
     * Vrátí všechny dny, které aktivita pokrývá (včetně víkendů)
     */
    public function getDays(): array
    {
        $days = [];
        $current = $this->start_date->copy();
        
        while ($current->lte($this->end_date)) {
            $days[] = $current->copy();
            $current->addDay();
        }
        
        return $days;
    }

    /**
     * Vrátí pouze pracovní dny (Po-Pá), které aktivita pokrývá
     */
    public function getWorkingDays(): array
    {
        return array_filter($this->getDays(), function ($day) {
            return $day->isWeekday();
        });
    }
}