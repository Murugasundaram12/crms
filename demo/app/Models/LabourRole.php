<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class LabourRole extends Model
{

  use SoftDeletes;
    protected $dates = ['deleted_at'];
    protected $table = 'labour_role';
    protected $fillable = ['id','name','salary','salary_type'];
}
?>
