<?php

use Illuminate\Database\Migrations\Migration;
use App\Models\Module;

class BillingApprovalStatusPermission extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::table('module')->insert([
            [
                'title'     => 'Faturamento a Aprovar',
                'route' => 'billing/open',
                'parent' => 40,
            ], [
                'title'     => 'Faturamento Aprovado',
                'route' => 'billing/approved',
                'parent' => 40,
            ], [
                'title'     => 'Faturamento Reprovado',
                'route' => 'billing/rejected',
                'parent' => 40,
            ], [
                'title'     => 'Faturamento Cancelado',
                'route' => 'billing/canceled',
                'parent' => 40,
            ], [
                'title'     => 'Faturamento Pago',
                'route' => 'billing/paid',
                'parent' => 40,
            ], [
                'title'     => 'Faturamento com Erro',
                'route' => 'billing/error',
                'parent' => 40,
            ], [
                'title'     => 'Faturamento com CNAB Gerado',
                'route' => 'billing/cnab-generated',
                'parent' => 40,
            ], [
                'title'     => 'Faturamento Finalizado',
                'route' => 'billing/finished',
                'parent' => 40,
            ]
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        $modules = [
            'billing/open',
            'billing/approved',
            'billing/rejected',
            'billing/canceled',
            'billing/paid',
            'billing/error',
            'billing/cnab-generated',
            'billing/finished',
        ];
        foreach ($modules as $element) {
            $module = Module::where(['route' => $element])->first();
            Module::find($module->id)->delete();
        }
    }
}
