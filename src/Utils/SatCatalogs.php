<?php

namespace Gmlo\CFDI\Utils;

class SatCatalogs
{
    public function payWaysList()
    {
        return [
            '01' => 'Efectivo',
            '02' => 'Cheque nominativo',
            '03' => 'Transferencia electrónica de fondos',
            '04' => 'Tarjeta de crédito',
            '05' => 'Monedero electrónico',
            '06' => 'Dinero electrónico',
            '08' => 'Vales de despensa',
            '28' => 'Tarjeta de Débito',
            '29' => 'Tarjeta de Servicio',
            '99' => 'Otros',
        ];
    }

    public function countriesList()
    {
        return collect(config('cfdi.countries'))
                    ->sortBy('description')->map(function ($item, $key) {
                        return $item['description'];
                    })->toArray();
    }

    public function taxRegimeList()
    {
        return collect(config('cfdi.tax_regime'))
                    ->sortBy('description')
                    ->pluck('description', 'code')->toArray();
    }

    public function taxRegime($tax_regime)
    {
        $result = collect(config('cfdi.tax_regime'))
                    ->first(function ($value) use ($tax_regime) {
                        return $value['code'] == $tax_regime;
                    });
        return $result ? $result['description'] : '';
    }

    public function unitsList()
    {
        return collect(config('cfdi.units'))
                    ->pluck('text', 'id')->toArray();
    }

    public function units($query)
    {
        return collect(config('cfdi.units'))
                    ->sortBy('text')
                    ->filter(function ($item) use ($query) {
                        return strpos(mb_strtoupper($item['text']), $query) !== false;
                    })->toArray();
    }

    public function findUnit($code)
    {
        $x = collect(config('cfdi.units'))
                    ->first(function ($item) use ($code) {
                        return $item['id'] == $code;
                    });
        return $x ? (object)$x : null;
    }
}
