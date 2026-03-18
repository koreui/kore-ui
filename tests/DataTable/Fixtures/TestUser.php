<?php

namespace KoreUi\Tests\DataTable\Fixtures;

use Illuminate\Database\Eloquent\Model;

class TestUser extends Model
{
    protected $table = 'test_users';

    protected $guarded = [];

    public $timestamps = false;
}
