<?php

namespace Gmlo\CFDI;

use Validator;
use Gmlo\CFDI\Exceptions\CFDIException;
use Gmlo\CFDI\Nodes\Transmitter;
use Gmlo\CFDI\Nodes\Concept;
use Gmlo\CFDI\Nodes\Receiver;
use Gmlo\CFDI\Nodes\Nomina\Nomina as NominaComplement;
use Gmlo\CFDI\Nodes\Nomina\Receiver as ReceriverNomina;
use Gmlo\CFDI\Nodes\Nomina\Perception;
use Gmlo\CFDI\Nodes\Nomina\Deduction;
use Gmlo\CFDI\Nodes\Nomina\Perceptions;
use Gmlo\CFDI\Nodes\Nomina\Deductions;
use Gmlo\CFDI\Nodes\Complement;

class Nomina extends CFDIMaker
{
    protected $type = 'N';
    protected $is_ordinary;
    protected $perceptions = [];
    protected $deductions = [];
    protected $nomina12 = null;
    protected $other_namespaces = [
        'xmlns:nomina12' => 'http://www.sat.gob.mx/nomina12',
    ];

    public function __construct($data, $key_path, $cer_path)
    {
        $validator = Validator::make($data, [
            'pay_way' => 'required|in:' . implode(',', array_keys(sat_catalogs()->payWaysList())),
            'pay_method' => 'required|in:' . implode(',', array_keys(config('cfdi.pay_methods'))),
            'zip_code' => 'nullable|required',
            'serie' => 'required',
            'folio' => 'required',
            'cert_number' => 'required',
            'pay_start_date' => 'required|date|date_format:Y-m-d',
            'pay_end_date' => 'required|date|date_format:Y-m-d|after_or_equal:pay_start_date',
            'pay_date' => 'required|date|date_format:Y-m-d',
            'pay_days' => 'required|numeric',
            'type' => 'required|in:O,E',
        ], trans('CFDI::validation_messages'));
        if ($validator->fails()) {
            $message = 'Tienes un error con la información general de la nomina.';
            throw new CFDIException($message, 0, null, ['errors' => $validator->errors()->all()]);
        }
        $this->is_ordinary = strtoupper($data['type']) == 'O';
        $this->nomina12 = new NominaComplement([
            'pay_date' => $data['pay_date'],
            'pay_start_date' => $data['pay_start_date'],
            'pay_end_date' => $data['pay_end_date'],
            'pay_days' => $data['pay_days'],
            'type' => $data['type'],
            'total_perceptions' => 0,
            'total_deductions' => 0,
        ]);
        parent::__construct($data, $key_path, $cer_path);
    }

    public function setEmployee($data)
    {
        $data['how_use'] = 'P01';

        $employee = new Receiver($data, [
            'curp' => 'required',
            'imss' => 'nullable',
            'contract_type' => 'required',
            'regime_type' => 'required',
            'employee_number' => 'required',
            'employee_position' => 'required',
            'periodicity_of_payment' => 'required',
            'state' => 'required',
        ]);

        parent::setReceiver($employee);
    }

    public function setEmployer($data)
    {
        parent::setTransmitter(new Transmitter($data));
    }

    public function generate()
    {
        $this->nomina12->addChild(new ReceriverNomina($this->receiver->getData()));

        $perceptions = new Perceptions();
        foreach ($this->perceptions as $perception) {
            $perceptions->addChild($perception);
        }
        $perceptions->calcule();
        $this->nomina12->addChild($perceptions);

        $deductions = new Deductions(compact('total'));
        foreach ($this->deductions as $deduction) {
            $deductions->addChild($deduction);
        }
        $deductions->calcule();
        $this->nomina12->addChild($deductions);
        $this->nomina12->calcule();
        $complement = new Complement();
        $complement->addChild($this->nomina12);
        $this->complements = $complement;

        $this->addConcept(new Concept([
            'quantity' => 1,
            'price' => $perceptions->total_salaries,
            'description' => 'Pago de nómina',
            'category_code' => '84111505',
            'unit' => 'ACT',
            'unit_str' => 'Actividad',
            'discount' => $deductions->total,
        ]));

        parent::generate();
    }

    public function addPerception($type, $code, $concept, $import_taxed, $import_exempt)
    {
        $this->perceptions[] = new Perception(compact('type', 'code', 'concept', 'import_taxed', 'import_exempt'));
    }

    public function addDeduction($type, $code, $concept, $import)
    {
        $this->deductions[] = new Deduction(compact('type', 'code', 'concept', 'import'));
    }
}
