<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithMapping;
use DB;
use App\Models\ProjectDetails;
use App\Models\Wallet;
use Maatwebsite\Excel\Concerns\WithHeadings;

class PaymentIncomeExport implements FromCollection, WithHeadings, WithMapping
{
    

    public function __construct($user_filter, $from, $to_date,$id,$search)
    {
      
    
        $this->user_filter = $user_filter;
        $this->from = $from;
        $this->to = $to_date;
        $this->id = $id;
        $this->search = $search;
        
    }

    // Headings
    public function headings(): array{
  
        return[
            'Client Name',
            'Received Amount',
            'Payment Mode',
            'Description',
            'Stages',
            'Received Date'

        ];
    }

    /**
    * @return \Illuminate\Support\Collection
    */

    public function collection()
    {
        $project = Wallet::leftjoin('clientdetails', 'clientdetails.id', '=', 'wallet.client_id')
                ->leftjoin('payment', 'payment.id', '=', 'wallet.payment_mode')?->leftjoin('stage', 'stage.id', '=', 'wallet.stage_id')?->where('wallet.project_id', $this->id)
                ->select('wallet.*', 'clientdetails.first_name', 'clientdetails.last_name', 'payment.name as payment_name', 'stage.name as stage_name')
                ->when($this->from,function($query,$from){
                  $query->whereDate('wallet.current_date','>=',$from);
                })
                ->when($this->to,function($query,$to){
                  $query->whereDate('wallet.current_date','<=',$to);
                })
                ->when($this->user_filter,function($query,$category_id){
                  $query->where('wallet.client_id',$category_id);
                })
                ->when($this->search,function($query,$search){
                  $query->where(function ($q) use ($search) {
          $q->where('clientdetails.first_name', 'like', "%$search%")
            ->orWhere('clientdetails.last_name', 'like', "%$search%")
            ->orWhere('wallet.amount', 'like', "%$search%")
            ->orWhere('wallet.description', 'like', "%$search%")
            ->orWhere('payment.name', 'like', "%$search")
            ->orWhere('stage.name', 'like', "%$search");
        });
                })->get();


        return collect($project);
    }
    // here you select the row that you want in the file
    public function map($row): array{
     
        $fields = [
           $row->first_name.' '.$row->last_name,
           $row->amount,
           $row->payment_name,
           $row->description,
           $row->stage_name,
           $row->current_date,
          
           
      ];
  
     return $fields;
    }

}
