<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WhatsappTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'whatsapp_account_id',
        'name',
        'language',
        'approved_body',
    ];

    /**
     * @return BelongsTo<WhatsappAccount, $this>
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(WhatsappAccount::class, 'whatsapp_account_id');
    }
}
