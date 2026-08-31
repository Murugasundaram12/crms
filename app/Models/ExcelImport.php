<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class ExcelImport extends Model { protected $fillable = ['module','filename','user_id','status','total_rows','imported_rows','skipped_rows','failed_rows','errors']; protected $casts = ['errors'=>'array']; }
