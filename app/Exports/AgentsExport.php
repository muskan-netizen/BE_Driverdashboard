<?php

namespace App\Exports;
use App\Model\Agent;
use App\Model\DriverRegistrationDocument;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\FromCollection;
use Auth;
class AgentsExport implements FromCollection, WithMapping, WithHeadings{
    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {   
        $agents = Agent::orderBy('id', 'DESC');
       
        if (Auth::user()->is_superadmin == 0 && Auth::user()->all_team_access == 0) {
            $agents = $agents->whereHas('team.permissionToManager', function ($query) {
                $query->where('sub_admin_id', Auth::user()->id);
            });
        }

        $agents = $agents->with('agentdocs')->orderBy('id', 'desc')->get();
          
        return $agents;
    }
    public function headings(): array{
        $headings = [
            'Uid',
            'Name',
            'Phone',
            'Type',
            'Team',
            'Cash Collected',
            'Order Earning',
            'Total Paid to '.getAgentNomenclature(),
            'Total Receive from '.getAgentNomenclature(),
            'Final Balance'
        ];
    
     

        $agents_docs = DriverRegistrationDocument::whereNotIn('file_type', ['Image', 'Pdf'])->pluck('name')->toArray();
        if(!empty($agents_docs)){
            $headings = array_merge($headings, $agents_docs);
           return $headings ;
        }else{
            return $headings;
        }
    
    }

    public function map($agents): array
    {
        
        $data =  [
            $agents->uid,
            $agents->name,
            $agents->phone_number,
            $agents->type,
            (isset($agents->team->name)? $agents->team->name : 'Team Not Alloted'),
            $agents->order->sum('cash_to_be_collected'),
            $agents->order->sum('driver_cost'),
            $agents->agentPayment->sum('cr'),
            $agents->agentPayment->sum('dr'),
            ($agents->agentPayment->sum('dr') - $agents->agentPayment->sum('cr')) - ($agents->order->sum('cash_to_be_collected') - $agents->order->sum('driver_cost')),
          
        
        ];

        if (!empty($agents->agentdocs)) {
            $agent_docs = [];
            foreach ($agents->agentdocs as $agentdoc) {
                $agent_docs[] = $agentdoc->file_name;
            }
            
            $data =array_merge($data,$agent_docs);
            return $data ; 
        }else{
            $data ;
        }
    
    }
    
}
