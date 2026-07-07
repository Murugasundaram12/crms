<?php

namespace App\Models; 
  
use Illuminate\Database\Eloquent\Model; 
use Illuminate\Database\Eloquent\SoftDeletes;
  
class MainCategory extends Model 
{
    use SoftDeletes; 

    protected $table = 'main_category';
    protected $fillable = ['id','name','status'];

    public function category(){
      return $this->hasMany(Category::class);
    }
} 
?>