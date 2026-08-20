<?php

namespace KoreUi\Tests\DataTable\Fixtures;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TestCompany extends Model
{
    protected $table = 'test_companies';

    protected $guarded = [];

    public $timestamps = false;

    public function users(): HasMany
    {
        return $this->hasMany(TestUser::class, 'company_id');
    }
}
