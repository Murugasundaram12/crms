<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Vendor extends Model
{
  use SoftDeletes;
  protected $dates = ['deleted_at'];
    protected $table = 'vendor_details';
    protected $fillable = ['id','name','phone','advance_amt','address'];
}
?>
