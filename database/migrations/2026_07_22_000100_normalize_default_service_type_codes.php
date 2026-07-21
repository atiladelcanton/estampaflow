<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $codes = [
            'D_T_F' => 'DTF',
            'S_I_L_K' => 'SILK',
            'S_U_B_L_I_M_A_C_A_O' => 'SUBLIMACAO',
            'B_O_R_D_A_D_O' => 'BORDADO',
        ];

        foreach ($codes as $legacyCode => $canonicalCode) {
            $rows = DB::table('service_types')
                ->select(['id', 'tenant_id'])
                ->where('is_default', true)
                ->where('code', $legacyCode)
                ->get();

            foreach ($rows as $row) {
                $canonicalExists = DB::table('service_types')
                    ->where('tenant_id', $row->tenant_id)
                    ->where('code', $canonicalCode)
                    ->exists();

                if ($canonicalExists) {
                    continue;
                }

                DB::table('service_types')
                    ->where('id', $row->id)
                    ->update([
                        'code' => $canonicalCode,
                        'updated_at' => now(),
                    ]);
            }
        }
    }

    public function down(): void
    {
        // Correção de dados intencionalmente irreversível.
    }
};
