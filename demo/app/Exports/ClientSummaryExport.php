<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithMapping;
use DB;
use App\Models\ProjectDetails;
use App\Models\ExpensesUnpaidDate;
use Maatwebsite\Excel\Concerns\WithHeadings;

class ClientSummaryExport implements FromCollection, WithHeadings, WithMapping
{


    public function __construct($project_filter, $category, $from, $to,$search)
    {

        $this->project_id = $project_filter;
        $this->category = $category;
        $this->from = $from;
        $this->to = $to;
        $this->search = $search;

    }

    // Headings
    public function headings(): array{

        return[
            'Client Name',
            'Received Date',
            'Project Name',
            'Received Amount',
            'Total Amount',
            'Payment Mode',
            'Stages'


        ];
    }

    /**
    * @return \Illuminate\Support\Collection
    */

    public function collection()
    {
        $clients = ProjectDetails::leftjoin('wallet', 'wallet.project_id', '=', 'project_details.id')
      ->leftjoin('stage', 'stage.id', '=', 'wallet.stage_id')
      ->leftjoin('clientdetails', 'wallet.client_id', '=', 'clientdetails.id')
      ->leftjoin('payment', 'payment.id', '=', 'wallet.payment_mode')
      ->select(
        'project_details.*',
        'stage.name as stage_name',
        'wallet.amount',
        'payment.name as payment',
        'wallet.current_date as currentdate',
        'clientdetails.first_name',
        'clientdetails.last_name'
      )
      ->when($this->from, function ($query, $from) {
        $query->whereDate('wallet.current_date', '>=', $from);
      })
      ->when($this->to, function ($query, $to) {
        $query->whereDate('wallet.current_date', '<=', $to);
      })
      ->when($this->category, function ($query, $category_id) {
        $query->where('wallet.client_id', $category_id);
      })
      ->when($this->project_id, function ($query, $project_id) {
        $query->where('wallet.project_id', $project_id);
      })
      ->when($this->search, function ($query, $search) {
        $query->where(function ($q) use ($search) {
          $q->where('clientdetails.first_name', 'like', "%$search%")
            ->orWhere('clientdetails.last_name', 'like', "%$search%")
            ->orWhere('project_details.name', 'like', "%$search%")
            ->orWhere('wallet.amount', 'like', "%$search%")
            ->orWhere('project_details.total_amt', 'like', "%$search%")
            ->orWhere('payment.name', 'like', "%$search")
            ->orWhere('stage.name', 'like', "%$search");
        });
      })->get();


        return collect($clients);
    }
    // here you select the row that you want in the file
    public function map($row): array{

        $fields = [
           $row->first_name.' '.$row->last_name,
           $row->currentdate,
           $row->name,
           $row->amount,
           $row->total_amt,
           $row->payment,
           $row->stage_name,



      ];

     return $fields;
    }

}
