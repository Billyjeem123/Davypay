<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GiftCard extends Model
{
    use HasFactory;

    protected $fillable = [
        'type_id',
        'country',
        'initial_value',
        'current_value',
        'image_path',
        'code',
        'package',
        'evaluated_value',
        'status',
        'user_id',
        'currency',
        'issue_by',
        'issue_date',
        'expiration_date',
        'notes'
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function type(): BelongsTo
    {
        return $this->belongsTo(GiftCardList::class, 'type_id');
    }

}
