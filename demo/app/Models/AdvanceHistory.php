<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdvanceHistory extends Model
{

    protected $table = 'advance_history';
    protected $fillable = ['id','labour_id','vendor_id','expense_id','amount','date'];
}
?>
