<?php

namespace App\Exports;

use App\Models\User;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class UsersExport implements FromCollection, ShouldAutoSize, WithMapping, WithHeadings
{
    private $with = ['cost_center', 'business', 'role'];

    use Exportable;

    public function __construct($requestInfo)
    {
        $this->requestInfo = $requestInfo;
    }

    public function collection()
    {
        $requestInfo = $this->requestInfo;
        return User::with($this->with)->whereHas('cost_center', function ($query) use ($requestInfo) {
            if (array_key_exists('cost_center', $requestInfo)) {
                $query->where('cost_center_id', $requestInfo['cost_center']);
            }
        })->get();
    }

    public function map($user): array
    {
        $nameCostCenter = '';
        foreach ($user->cost_center as $key => $costCenter) {
            if ($key == 0) {
                $nameCostCenter = $costCenter->title;
            }else{
                $nameCostCenter = $nameCostCenter . ', ' . $costCenter->title;
            }
        }

        return [
            $user->name,
            $user->email,
            $user->phone,
            $user->extension,
            $user->role ? $user->role->title : $user->role,
            $user->created_at,
            $nameCostCenter,
        ];
    }

    public function headings(): array
    {
        return [
            'Nome',
            'Email',
            'Telefone',
            'Ramal',
            'Perfil de Acesso',
            'Data de Criação',
            'Centro de Custo'
        ];
    }
}
