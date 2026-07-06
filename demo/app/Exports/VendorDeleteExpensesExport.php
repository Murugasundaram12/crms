<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithMapping;
use DB;use Carbon\Carbon;
use App\Models\Expenses;
use App\Models\ExpensesUnpaidDate;
use Maatwebsite\Excel\Concerns\WithHeadings;

class VendorDeleteExpensesExport implements FromCollection, WithHeadings, WithMapping
{


    public function __construct($category_filter , $project_filter, $user_filter, $from, $to_date,$auth,$role,$search,$main_category)
    {
        $this->category_filter = $category_filter;
        $this->project_filter = $project_filter;
        $this->user_filter = $user_filter;
        $this->from = $from;
        $this->to_date = $to_date;
        $this->auth = $auth;
        $this->search = $search;
        $this->role = $role;
        $this->main_category = $main_category;

    }

    // Headings
    public function headings(): array{
        return[
          'Main Category',
            'Category Name',
            'Paid Date',
            'Paid Time',
            'Project Name',
            'Reason',
            'Vendor Name',
            'Amount',
            'Paid Amount',
            'Unpaid Amount',
            'Advanced Amount',
            'Description',
            'Payment Mode',


            'Added By',
            'Edited By',
            'Advance Edited By',
            "Deleted Date"

        ];
    }

    /**
    * @return \Illuminate\Support\Collection
    */

    public function collection()
    {
      $expenses = Expenses::join('category', 'category.id', '=', 'expenses.category_id')
      ->whereNotNull('expenses.vendor_id')->leftjoin('vendor_details as l','l.id','=','expenses.vendor_id')->leftJoin('project_details', function ($join) {
        $join->on('project_details.id', 'expenses.project_id')
          ->where('expenses.project_id', '!=', null);
      })
      ->leftjoin('main_category','main_category.id','=','expenses.main_category_id')
      ->leftjoin('payment', 'payment.id', '=', 'expenses.payment_mode')
      ->where(['category.active_status' => 1, 'category.delete_status' => 0])
      ->leftjoin('users', 'users.id', '=', 'expenses.editedBy')
      ->leftjoin('users as users_add', 'users_add.id', '=', 'expenses.user_id')
      ->leftjoin('users as labour_ad','labour_ad.id','=','expenses.is_advance')
      ->select('expenses.*', 'category.name as category_name', 'project_details.name as project_name', 
      'payment.name as payment_name', 'users.first_name', 
      'users.last_name', 'users_add.first_name as first', 
      'users_add.last_name as last','l.name as labour_name',
      'labour_ad.first_name as labour_first','labour_ad.last_name as labour_last','main_category.name as main_category_name')
      ->when($this->from, function($query,$from){
        $query->wheredate('current_date', '>=',$from);
      })
   ->when($this->to_date,function($query,$to){
        $query->wheredate('current_date', '<=', $to);
      })
      ->when($this->main_category,function($query,$main_category){
        $query->where('expenses.main_category_id',$main_category);
      })
      ->when($this->category_filter,function($query,$category_id){
        $query->where('expenses.category_id', $category_id);
      })
      ->when($this->project_filter,function($query,$project_id){
        $query->where('expenses.project_id', $project_id);
      })
      ->when($this->user_filter,function($query,$user_id){
        $query->where('expenses.vendor_id', $user_id);
      })
      ->when($this->search, function ($query, $search) {
        $query->where(function ($q) use ($search) {
          $q->where('category.name', 'like', "%$search%")
            ->orWhere('project_details.name', 'like', "%$search%")
            ->orWhere('payment.name', 'like', "%$search%")
            ->orWhere(DB::raw("CONCAT(users.first_name, ' ', users.last_name)"), 'like', "%$search%")
            ->orWhere(DB::raw("CONCAT(users_add.first_name, ' ', users_add.last_name)"), 'like', "%$search%")
            ->orWhere(DB::raw("CONCAT(labour_ad.first_name, ' ', labour_ad.last_name)"), 'like', "%$search%")
            ->orWhere('users.first_name', 'like', "%$search%")
            ->orWhere('users.last_name', 'like', "%$search%")
            ->orWhere('users_add.first_name', 'like', "%$search%")
            ->orWhere('users_add.last_name', 'like', "%$search%")
            ->orWhere('l.name', 'like', "%$search%")
            ->orWhere('labour_ad.first_name', 'like', "%$search%")
            ->orWhere('labour_ad.last_name', 'like', "%$search%")
            ->orWhere('expenses.amount', 'like', "%$search%")
            ->orWhere('expenses.paid_amt', 'like', "%$search%")
            ->orWhere('expenses.unpaid_amt', 'like', "%$search%")
            ->orWhere('expenses.extra_amt', 'like', "%$search%")
            ->orWhere('expenses.reason','like',"%$search%")
            ->orWhere('main_category.name','like',"%$search%")
            ->orWhere('expenses.description', 'like', "%$search%");
        });
      });


    if($this->from != '' && $this->to_date != '' ){
      $expenses = $expenses->onlyTrashed()->orderBy('expenses.current_date', 'desc')->get();

     }else{
      $expenses = $expenses->onlyTrashed()->orderBy('expenses.id','desc')->get();
     }

        return collect($expenses);
    }
    // here you select the row that you want in the file
    public function map($row): array{
        $unpaid_amt = ExpensesUnpaidDate::where('expense_id',$row->id)?->select('*')?->orderBy('id','desc')?->first();
        $unpaid_amt1 =  $row->current_date;
        $fields = [
          $row->main_category_name,
           $row->category_name,
            Carbon::parse($unpaid_amt1)->format('m/d/Y'),
           Carbon::parse($unpaid_amt1)->format('H:i A'),
           $row->project_name,
           $row->reason,
           $row->labour_name,
           $row->amount,
           $row->paid_amt,
           $row->unpaid_amt,
           $row->extra_amt,
           $row->description,
           $row->payment_name,


           $row->first.' '.$row->last,
           $row->first_name.' '.$row->last_name,
           $row->labour_first.' '.$row->labour_last,
           $row->deleted_at

      ];

     return $fields;
    }

}
