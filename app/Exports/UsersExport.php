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
    private $requestInfo;

    use Exportable;

    public function __construct($requestInfo)
    {
        $this->requestInfo = $requestInfo;
    }

    public function collection()
    {
        $requestInfo = $this->requestInfo;
        $user = User::with($this->with);

        if (array_key_exists('status', $requestInfo)) {
            if (!empty($requestInfo['status']) or $requestInfo['status'] == 0) {
                if (!is_null($requestInfo['status'])) {
                    $user = $user->where('status', $requestInfo['status']);
                }
            }
        }
        if (array_key_exists('cost_center', $requestInfo)) {
            if (!empty($requestInfo['cost_center'])) {
                $user->whereHas('cost_center', function ($query) use ($requestInfo) {
                    $query->where('cost_center_id', $requestInfo['cost_center']);
                });
            }
        }
        return $user->get();
    }

    public function map($user): array
    {
        $nameCostCenter = '';
        foreach ($user->cost_center as $key => $costCenter) {
            if ($key == 0) {
                $nameCostCenter = $costCenter->title;
            } else {
                $nameCostCenter = $nameCostCenter . ', ' . $costCenter->title;
            }
        }

        switch ($user->status) {
            case 0:
                $user->status = "Ativo";
                break;
            case 1:
                $user->status = "Férias";
                break;
            case 2:
                $user->status = "Desativado";
                break;
            case 3:
                $user->status = "Suspenso";
                break;
            default:
                $user->status = "Error";
        }

        return [
            $user->name,
            $user->email,
            $user->phone,
            $user->extension,
            $user->role ? $user->role->title : $user->role,
            $nameCostCenter,
            $user->status,
            $user->created_at,
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
            'Centro de Custo',
            'Status',
            'Data de Criação',
        ];
    }
}
