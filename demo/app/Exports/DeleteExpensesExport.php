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

class DeleteExpensesExport implements FromCollection, WithHeadings, WithMapping
{


    public function __construct($category_filter , $project_filter, $user_filter, $from, $to,$auth,$role,$search,$main_category)
    {
        $this->category_filter = $category_filter;
        $this->project_filter = $project_filter;
        $this->user_filter = $user_filter;
        $this->from = $from;
        $this->to = $to;
        $this->auth = $auth;
        $this->search = $search;
        $this->role = $role;
        $this->main_category = $main_category;

    }

    // Headings
    public function headings(): array{
        if($this->role == 1){
        return[
          'Main Category',
            'Category Name',
            'Paid Date',
            'Paid Time',
            'Project Name',
            'Reason',
            'Amount',
            'Paid Amount',
            'Unpaid Amount',
            'Advanced Amount',
            'Description',
            'Payment Mode',


            'Added By',
            'Edited By',
            "Deleted Date"

        ];
    }else{
        return[
          'Main Category',
        'Category Name',
        'Paid Date',
        'Paid Time',
        'Project Name',
        'Amount',
        'Paid Amount',
        'Unpaid Amount',
        'Advanced Amount',
        'Payment Mode',
        'Description',


        ];
    }
    }

    /**
    * @return \Illuminate\Support\Collection
    */

    public function collection()
    {
    $expenses = Expenses::join('category', 'category.id', '=', 'expenses.category_id')
    ->leftjoin('main_category','main_category.id','=','expenses.main_category_id')
      ->leftJoin('project_details', function ($join) {
        $join->on('project_details.id', 'expenses.project_id')
          ->where('expenses.project_id', '!=', null);
      });


    $expenses = $expenses->leftjoin('payment', 'payment.id', '=', 'expenses.payment_mode')
      ->where(['category.active_status' => 1, 'category.delete_status' => 0])->when($this->from, function ($query, $from) {
        $query->wheredate('current_date', '>=', $from);
      })
      ->when($this->to, function ($query, $to) {
        $query->wheredate('current_date', '<=', $to);
      })
      ->when($this->main_category,function($query,$main_category){
        $query->where('expenses.main_category_id',$main_category);
      })
      ->when($this->category_filter, function ($query, $category_id) {
        $query->where('expenses.category_id', $category_id);
      })
      ->when($this->project_filter, function ($query, $project_id) {
        $query->where('expenses.project_id', $project_id);
      })
      ->when($this->user_filter, function ($query, $user_id) {
        $query->where('expenses.user_id', $user_id);
      })
      ->whereNull('expenses.labour_id')->whereNull('expenses.vendor_id');
    if ($this->role != 1) {
      $expenses = $expenses->leftjoin('users', 'users.id', '=', 'expenses.user_id')->where('users.id', $this->auth);
      $expenses = $expenses->select('expenses.*', 'category.name as category_name', 'project_details.name as project_name', 'payment.name as payment_name', 'users.first_name', 'users.last_name','main_category.name as main_category_name')->when($this->search, function ($query, $search) {
        $query->where(function ($q) use ($search) {
          $q->where('category.name', 'like', "%$search%")
            ->orWhere('project_details.name', 'like', "%$search%")
            ->orWhere('main_category.name','like',"%$search%")
            ->orWhere('payment.name', 'like', "%$search%")
            ->orWhere('users.first_name', 'like', "%$search%")
            ->orWhere('users.last_name', 'like', "%$search%")
            ->orWhere('expenses.amount', 'like', "%$search")
            ->orWhere('expenses.paid_amt', 'like', "%$search")
            ->orWhere('expenses.unpaid_amt', 'like', "%$search")
            ->orWhere('expenses.extra_amt', 'like', "%$search")
            ->orWhere('expenses.description', 'like', "%$search%");
        });
      });
    } else {
      $expenses = $expenses->leftjoin('users', 'users.id', '=', 'expenses.editedBy')->leftjoin('users as users_add', 'users_add.id', '=', 'expenses.user_id');
      $expenses = $expenses->select('expenses.*', 'category.name as category_name', 'project_details.name as project_name', 'payment.name as payment_name', 'users.first_name', 'users.last_name', 'users_add.first_name as first', 'users_add.last_name as last','main_category.name as main_category_name')->when($this->search, function ($query, $search) {
        $query->where(function ($q) use ($search) {
          $q->where('category.name', 'like', "%$search%")
            ->orWhere('project_details.name', 'like', "%$search%")
            ->orWhere('main_category.name','like',"%$search%")
            ->orWhere('payment.name', 'like', "%$search%")
            ->orWhere('users.first_name', 'like', "%$search%")
            ->orWhere('users.last_name', 'like', "%$search%")
            ->orWhere('users_add.first_name', 'like', "%$search%")
            ->orWhere('users_add.last_name', 'like', "%$search%")
            ->orWhere('expenses.amount', 'like', "%$search")
            ->orWhere('expenses.paid_amt', 'like', "%$search")
            ->orWhere('expenses.unpaid_amt', 'like', "%$search")
            ->orWhere('expenses.extra_amt', 'like', "%$search")
            ->orWhere('expenses.description', 'like', "%$search%");
        });
      });
    }
     $expenses = $expenses->onlyTrashed()->orderBy($this->from || $this->to ? 'expenses.current_date' : 'expenses.id', 'desc')->get();

        return collect($expenses);
    }
    // here you select the row that you want in the file
    public function map($row): array{
        $unpaid_amt = ExpensesUnpaidDate::where('expense_id',$row->id)?->select('*')?->orderBy('id','desc')?->first();
        $unpaid_amt1 =  $row->current_date;
        if($this->role == 1){
        $fields = [
          $row->main_category_name,
           $row->category_name,
            Carbon::parse($unpaid_amt1)->format('d/m/Y'),
           Carbon::parse($unpaid_amt1)->format('H:i A'),
           $row->project_name,
           $row->reason,
           $row->amount,
           $row->paid_amt,
           $row->unpaid_amt,
           $row->extra_amt,
           $row->description,
           $row->payment_name,


           $row->first.' '.$row->last,
           $row->first_name.' '.$row->last_name,

           $row->deleted_at

      ];
    }else{
        $fields = [
          $row->main_category_name,
            $row->category_name,
             Carbon::parse($unpaid_amt1)->format('m/d/Y'),
           Carbon::parse($unpaid_amt1)->format('H:i A'),
            $row->project_name,
            $row->amount,
            $row->paid_amt,
            $row->unpaid_amt,
            $row->extra_amt,
            $row->payment_name,
            $row->description,


       ];
    }
     return $fields;
    }

}
