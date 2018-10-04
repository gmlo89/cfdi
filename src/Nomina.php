<?php

namespace Gmlo\CFDI;

use Gmlo\CFDI\Nodes\Receipt;
use Gmlo\CFDI\Nodes\Receiver;
use Gmlo\CFDI\Nodes\Transmitter;
use Gmlo\CFDI\Nodes\Nomina\Perception;
use Gmlo\CFDI\Nodes\Nomina\Deduction;
use Gmlo\CFDI\Nodes\Nomina\Nomina as NominaComplement;
use Gmlo\CFDI\Nodes\Nomina\Receiver as ReceriverNomina;
use Gmlo\CFDI\Nodes\Nomina\Perceptions;
use Gmlo\CFDI\Nodes\Nomina\Deductions;
use Gmlo\CFDI\Nodes\Complement;
use Gmlo\CFDI\Nodes\Concept;

class Nomina extends Receipt
{
    protected $perceptions = [];
    protected $deductions = [];
    protected $nomina12 = null;

    public function __construct($data = [], $other_rules = [])
    {
        $data += [
            'xmlns_nomina12' => 'http://www.sat.gob.mx/nomina12',
        ];
        $this->nomina12 = new NominaComplement([
            'pay_date' => $data['pay_date'],
            'pay_start_date' => $data['pay_start_date'],
            'pay_end_date' => $data['pay_end_date'],
            'pay_days' => $data['pay_days'],
            'type' => $data['type'],
            'total_perceptions' => 0,
            'total_deductions' => 0,
        ]);
        $data['type'] = 'N';

        parent::__construct($data, ['xmlns_nomina12' => 'required']);
        $this->data['xsi_schemaLocation'] .= 'http://www.sat.gob.mx/nomina12 http://www.sat.gob.mx/sitio_internet/cfd/nomina/nomina12.xsd';
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

        $this->setReceiver($employee);
    }

    public function setEmployer($data)
    {
        $this->setTransmitter(new Transmitter($data));
    }

    public function addPerception($type, $code, $concept, $import_taxed, $import_exempt)
    {
        $perception = new Perception(compact('type', 'code', 'concept', 'import_taxed', 'import_exempt'));
        $this->perceptions[] = $perception;
        //$this->addChild($perception);
    }

    public function addDeduction($type, $code, $concept, $import)
    {
        $deduction = new Deduction(compact('type', 'code', 'concept', 'import'));
        $this->deductions[] = $deduction;
        //$this->addChild($deduction);
    }

    public function calcule()
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
        //$this->complements = $complement;

        $this->addConcept(new Concept([
            'quantity' => 1,
            'price' => $perceptions->total_salaries,
            'description' => 'Pago de nómina',
            'category_code' => '84111505',
            'unit' => 'ACT',
            //'unit_str' => 'Actividad',
            'discount' => $deductions->total,
        ]));

        parent::calcule();
        $this->addChild($complement);
    }
}
