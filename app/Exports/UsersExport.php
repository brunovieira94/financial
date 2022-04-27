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

    use Exportable;

    public function collection()
    {
        return User::get();
    }

    public function map($user): array
    {
        return [
            $user->name,
            $user->email,
            $user->phone,
            $user->extension,
            $user->role ? $user->role->title : $user->role,
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
            'Data de Criação',
        ];
    }
}
