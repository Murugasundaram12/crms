<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Category extends Model
{

    protected $table = 'category';
    protected $fillable = ['id','name','active_status','delete_status','main_category_id'];


    public function maincategory(){
      return $this->belongsto(MainCategory::class,'main_category_id');
    }
}
?>