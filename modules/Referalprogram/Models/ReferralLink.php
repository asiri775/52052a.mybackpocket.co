<?php

namespace Modules\Referalprogram\Models;

use App\BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\User\Models\User;
use Ramsey\Uuid\Uuid;

class ReferralLink extends BaseModel
{
    use HasFactory;

    protected $fillable = ['user_id', 'referral_program_id', 'code'];

    public static function getRefferal($user, $program)
    {
        $model = static::where([
            'user_id' => $user->id,
            'referral_program_id' => $program->id
        ])->first();
        if ($model == null) {
            $model = static::create([
                'user_id' => $user->id,
                'referral_program_id' => $program->id,
                'code' => Uuid::uuid1()->toString()
            ]);
        }
        return $model;
    }

    public function getLinkAttribute()
    {
        return url($this->program->uri) . '?ref=' . $this->code;
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function program()
    {
        return $this->belongsTo(ReferralProgram::class, 'referral_program_id');
    }

    public function relationships()
    {
        return $this->hasMany(ReferralRelationship::class);
    }
}