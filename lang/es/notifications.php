<?php

return [
    'email_verification_code' => [
        'subject' => 'Código de verificación de correo electrónico',
        'greeting' => 'Hola :name!',
        'requested' => 'Ha solicitado verificar su dirección de correo electrónico.',
        'code_is' => 'Su código de verificación es:',
        'expires' => 'Este código expirará en 1 hora.',
        'ignore' => 'Si no solicitó esta verificación, ignore este correo electrónico.',
        'thank_you' => 'Gracias por usar nuestra aplicación!',
    ],
    'project_assigned' => [
        'subject' => 'Ha sido asignado a un nuevo proyecto',
        'greeting' => 'Hola :name!',
        'assigned_to' => 'Ha sido asignado al proyecto: **:project**',
        'customer' => 'Cliente: :customer',
        'hourly_rate' => 'Su tarifa por hora para este proyecto: :rate',
        'deadline' => 'Fecha límite: :deadline',
        'description' => 'Descripción: :description',
        'action' => 'Ver proyecto',
        'thank_you' => 'Gracias por su trabajo continuo!',
    ],
    'invoice' => [
        'title' => 'FACTURA',
        'date' => 'Fecha',
        'invoice_number' => 'Número de factura',
        'bill_to' => 'Facturar a',
        'worker' => 'Trabajador',
        'description' => 'Descripción',
        'rate_unit' => 'Tarifa/Unidad',
        'amount' => 'Importe',
        'total' => 'Total',
        'project_total' => 'Total del proyecto',
        'subtotal' => 'Subtotal',
        'tax' => 'Impuesto',
        'no_work_logs_found' => 'No se encontraron registros de trabajo',
    ],
];
