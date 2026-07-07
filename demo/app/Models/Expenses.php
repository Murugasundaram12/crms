<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Expenses extends Model
{
    use SoftDeletes;
    protected $dates = ['deleted_at'];
    protected $table = 'expenses';
    protected $fillable = ['id','amount','category_id','project_id','user_id','current_date','paid_amt','unpaid_amt','image','payment_mode','description','editedBy','extra_amt','reason','labour_id','vendor_id','main_category_id','is_advance'];

    public function scopeOther($query)
    {
        return $query->whereNull('expenses.labour_id')->whereNull('expenses.vendor_id');
    }

    public function scopeLabourExpense($query)
    {
        return $query->whereNotNull('expenses.labour_id')->whereNull('expenses.vendor_id');
    }

    public function scopeVendorExpense($query)
    {
        return $query->whereNotNull('expenses.vendor_id')->whereNull('expenses.labour_id');
    }
}
?>
