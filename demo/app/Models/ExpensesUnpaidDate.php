<?php

namespace App\Models; 
  
use Illuminate\Database\Eloquent\Model; 
  
class ExpensesUnpaidDate extends Model 
{ 

    protected $table = 'expenses_unpaid_date';
    protected $fillable = ['id','amount','expense_id','current_date','unpaid_amt','description'];
} 
?>