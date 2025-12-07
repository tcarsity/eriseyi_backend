<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Member extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'phone',
        'address',
        'gender',
        'birth_month',
        'birth_date',
        'created_by'
    ];

    public function setPhoneAttribute($value)
    {
        if(str_starts_with($value, '+234')){
            $this->attributes['phone'] = $value;
        }else{
            $this->attributes['phone'] = '+234' . $value;
        }
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    protected $with = ['creator'];

}
