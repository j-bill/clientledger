<?php

return [
    'email_verification_code' => [
        'subject' => 'E-Mail-Verifizierungscode',
        'greeting' => 'Hallo :name!',
        'requested' => 'Sie haben die Verifizierung Ihrer E-Mail-Adresse angefordert.',
        'code_is' => 'Ihr Verifizierungscode lautet:',
        'expires' => 'Dieser Code läuft in 1 Stunde ab.',
        'ignore' => 'Wenn Sie diese Verifizierung nicht angefordert haben, ignorieren Sie diese E-Mail bitte.',
        'thank_you' => 'Vielen Dank für die Nutzung unserer Anwendung!',
    ],
    'project_assigned' => [
        'subject' => 'Sie wurden einem neuen Projekt zugewiesen',
        'greeting' => 'Hallo :name!',
        'assigned_to' => 'Sie wurden dem Projekt zugewiesen: **:project**',
        'customer' => 'Kunde: :customer',
        'hourly_rate' => 'Ihr Stundensatz für dieses Projekt: :rate',
        'deadline' => 'Frist: :deadline',
        'description' => 'Beschreibung: :description',
        'action' => 'Projekt ansehen',
        'thank_you' => 'Vielen Dank für Ihre kontinuierliche Arbeit!',
    ],
    'invoice' => [
        'title' => 'RECHNUNG',
        'date' => 'Datum',
        'invoice_number' => 'Rechnungsnummer',
        'bill_to' => 'Rechnung an',
        'worker' => 'Mitarbeiter',
        'description' => 'Beschreibung',
        'rate_unit' => 'Satz/Einheit',
        'amount' => 'Betrag',
        'total' => 'Gesamt',
        'project_total' => 'Projektsumme',
        'subtotal' => 'Zwischensumme',
        'tax' => 'Steuer',
        'no_work_logs_found' => 'Keine Arbeitslogs gefunden',
    ],
];
