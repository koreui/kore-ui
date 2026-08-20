<?php

namespace KoreUi\Tests\DataTable\Fixtures;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TestUser extends Model
{
    protected $table = 'test_users';

    protected $guarded = [];

    public $timestamps = false;

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(TestCompany::class, 'company_id');
    }
}
