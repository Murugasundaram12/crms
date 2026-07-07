<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithMapping;
use DB;
use App\Models\ProjectDetails;
use App\Models\Expenses;
use Maatwebsite\Excel\Concerns\WithHeadings;

class PaymentExpenseExport implements FromCollection, WithHeadings, WithMapping
{
    

    public function __construct($user_filter, $from, $to_date,$id,$category_filter)
    {
        $this->category_filter = $category_filter;
    
        $this->user_filter = $user_filter;
        $this->from = $from;
        $this->to_date = $to_date;
        $this->id = $id;
        
    }

    // Headings
    public function headings(): array{
  
        return[
            'Category Name',
            'Added By',
            'Amount',
            'Paid Amount',
            'Payment Mode',
            'Description',
            'Received Date'

        ];
    }

    /**
    * @return \Illuminate\Support\Collection
    */

    public function collection()
    {
       
      $project = Expenses::join('users','users.id','=','expenses.user_id')->join('category','category.id','=','expenses.category_id')->join('payment','payment.id','=','expenses.payment_mode')->where('expenses.project_id',$this->id)->select('expenses.*','category.name as category_name','users.first_name','users.last_name','payment.name as payment_name');
      if($this->from != '' && $this->to_date != ''){
        $project = $project->whereBetween('wallet.current_date', [$this->from,$this->to_date]);
    }
    if($this->category_filter != 'undefined' && $this->category_filter != ''){
      $project = $project->where('expenses.category_id',$this->category_filter);
    }
 
      if($this->user_filter != 'undefined' && $this->user_filter != ''){
        $project = $project->where('expenses.user_id',$this->user_filter);
      }
      $project = $project->get();
    


        return collect($project);
    }
    // here you select the row that you want in the file
    public function map($row): array{
     
        $fields = [
            $row->category_name,
           $row->first_name.' '.$row->last_name,
           $row->amount,
           $row->paid_amt,
           $row->payment_name,
           $row->description,
           $row->current_date,
          
           
      ];
  
     return $fields;
    }

}
