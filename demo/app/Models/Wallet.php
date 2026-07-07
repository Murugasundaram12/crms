<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Wallet extends Model
{

    protected $table = 'wallet';
    protected $fillable = ['id','user_id','client_id','project_id','amount','description','current_date','active_status','delete_status','payment_mode','stage_id','transfer_type'];
}
?>
