<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Salary extends Model
{

    protected $table = 'salary_details';
    protected $fillable = ['id','user_id','salary'];
}
?>
