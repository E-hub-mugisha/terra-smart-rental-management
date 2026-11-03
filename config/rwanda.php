<?php

/*
 * Rwanda administrative divisions: 5 provinces and their 30 districts.
 * Used by the property owner form (province -> district cascade) and by
 * server-side validation. Add sectors here later if you need a third level.
 */
return [
    'provinces' => [
        'Kigali City' => [
            'Gasabo', 'Kicukiro', 'Nyarugenge',
        ],
        'Northern Province' => [
            'Burera', 'Gakenke', 'Gicumbi', 'Musanze', 'Rulindo',
        ],
        'Southern Province' => [
            'Gisagara', 'Huye', 'Kamonyi', 'Muhanga',
            'Nyamagabe', 'Nyanza', 'Nyaruguru', 'Ruhango',
        ],
        'Eastern Province' => [
            'Bugesera', 'Gatsibo', 'Kayonza', 'Kirehe',
            'Ngoma', 'Nyagatare', 'Rwamagana',
        ],
        'Western Province' => [
            'Karongi', 'Ngororero', 'Nyabihu', 'Nyamasheke',
            'Rubavu', 'Rusizi', 'Rutsiro',
        ],
    ],
];