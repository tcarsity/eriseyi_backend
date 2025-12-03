<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use App\Models\Member;

class NigerianPhoneUnique implements ValidationRule
{
    protected $ignoreId;

    public function __construct($ignoreId = null)
    {
        $this->ignoreId = $ignoreId;
    }


    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $formatted = '+234' . $value;

        $query = Member::where('phone', $formatted);

        if($this->ignoreId){
            $query->where('id', '!=', $this->ignoreId);
        }

        if($query->exists()){
            $fail('The phone has already been taken.');
        }


    }
}
