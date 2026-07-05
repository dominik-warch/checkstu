<?php

declare(strict_types=1);

/*
 * German validation messages. Attribute names for interpolation into :attribute
 * live in the 'attributes' array at the bottom — add domain fields there so
 * messages read naturally in German (e.g. "Der Titel ist erforderlich.").
 */

return [
    'accepted' => ':attribute muss akzeptiert werden.',
    'active_url' => ':attribute ist keine gültige URL.',
    'after' => ':attribute muss ein Datum nach dem :date sein.',
    'after_or_equal' => ':attribute muss ein Datum nach dem :date oder gleich diesem sein.',
    'alpha' => ':attribute darf nur Buchstaben enthalten.',
    'alpha_dash' => ':attribute darf nur Buchstaben, Zahlen, Binde- und Unterstriche enthalten.',
    'alpha_num' => ':attribute darf nur Buchstaben und Zahlen enthalten.',
    'array' => ':attribute muss ein Array sein.',
    'before' => ':attribute muss ein Datum vor dem :date sein.',
    'before_or_equal' => ':attribute muss ein Datum vor dem :date oder gleich diesem sein.',
    'between' => [
        'array' => ':attribute muss zwischen :min & :max Elemente haben.',
        'file' => ':attribute muss zwischen :min & :max Kilobytes groß sein.',
        'numeric' => ':attribute muss zwischen :min & :max liegen.',
        'string' => ':attribute muss zwischen :min & :max Zeichen lang sein.',
    ],
    'boolean' => ':attribute muss entweder wahr oder falsch sein.',
    'confirmed' => ':attribute stimmt nicht mit der Bestätigung überein.',
    'date' => ':attribute ist kein gültiges Datum.',
    'date_equals' => ':attribute muss ein Datum gleich :date sein.',
    'date_format' => ':attribute entspricht nicht dem Format :format.',
    'different' => ':attribute und :other müssen sich unterscheiden.',
    'digits' => ':attribute muss :digits Ziffern lang sein.',
    'digits_between' => ':attribute muss zwischen :min und :max Ziffern lang sein.',
    'email' => ':attribute muss eine gültige E-Mail-Adresse sein.',
    'ends_with' => ':attribute muss mit einem der folgenden Werte enden: :values.',
    'exists' => 'Der gewählte Wert für :attribute ist ungültig.',
    'file' => ':attribute muss eine Datei sein.',
    'filled' => ':attribute muss ausgefüllt sein.',
    'gt' => [
        'array' => ':attribute muss mehr als :value Elemente haben.',
        'file' => ':attribute muss größer als :value Kilobytes sein.',
        'numeric' => ':attribute muss größer als :value sein.',
        'string' => ':attribute muss länger als :value Zeichen sein.',
    ],
    'gte' => [
        'array' => ':attribute muss mindestens :value Elemente haben.',
        'file' => ':attribute muss größer oder gleich :value Kilobytes sein.',
        'numeric' => ':attribute muss größer oder gleich :value sein.',
        'string' => ':attribute muss mindestens :value Zeichen lang sein.',
    ],
    'image' => ':attribute muss ein Bild sein.',
    'in' => 'Der gewählte Wert für :attribute ist ungültig.',
    'integer' => ':attribute muss eine ganze Zahl sein.',
    'ip' => ':attribute muss eine gültige IP-Adresse sein.',
    'lt' => [
        'array' => ':attribute muss weniger als :value Elemente haben.',
        'file' => ':attribute muss kleiner als :value Kilobytes sein.',
        'numeric' => ':attribute muss kleiner als :value sein.',
        'string' => ':attribute muss kürzer als :value Zeichen sein.',
    ],
    'lte' => [
        'array' => ':attribute darf höchstens :value Elemente haben.',
        'file' => ':attribute muss kleiner oder gleich :value Kilobytes sein.',
        'numeric' => ':attribute muss kleiner oder gleich :value sein.',
        'string' => ':attribute darf höchstens :value Zeichen lang sein.',
    ],
    'max' => [
        'array' => ':attribute darf nicht mehr als :max Elemente haben.',
        'file' => ':attribute darf nicht größer als :max Kilobytes sein.',
        'numeric' => ':attribute darf nicht größer als :max sein.',
        'string' => ':attribute darf nicht länger als :max Zeichen sein.',
    ],
    'min' => [
        'array' => ':attribute muss mindestens :min Elemente haben.',
        'file' => ':attribute muss mindestens :min Kilobytes groß sein.',
        'numeric' => ':attribute muss mindestens :min sein.',
        'string' => ':attribute muss mindestens :min Zeichen lang sein.',
    ],
    'not_in' => 'Der gewählte Wert für :attribute ist ungültig.',
    'numeric' => ':attribute muss eine Zahl sein.',
    'present' => ':attribute muss vorhanden sein.',
    'regex' => ':attribute hat ein ungültiges Format.',
    'required' => ':attribute ist erforderlich.',
    'required_if' => ':attribute ist erforderlich, wenn :other :value ist.',
    'required_unless' => ':attribute ist erforderlich, außer wenn :other :values ist.',
    'required_with' => ':attribute ist erforderlich, wenn :values vorhanden ist.',
    'required_without' => ':attribute ist erforderlich, wenn :values nicht vorhanden ist.',
    'same' => ':attribute und :other müssen übereinstimmen.',
    'size' => [
        'array' => ':attribute muss genau :size Elemente enthalten.',
        'file' => ':attribute muss :size Kilobytes groß sein.',
        'numeric' => ':attribute muss genau :size sein.',
        'string' => ':attribute muss genau :size Zeichen lang sein.',
    ],
    'starts_with' => ':attribute muss mit einem der folgenden Werte beginnen: :values.',
    'string' => ':attribute muss ein Text sein.',
    'unique' => ':attribute ist bereits vergeben.',
    'uppercase' => ':attribute muss aus Großbuchstaben bestehen.',
    'url' => ':attribute muss eine gültige URL sein.',

    /*
     * Custom messages for specific attribute/rule pairs go here.
     */
    'custom' => [
        'attribute-name' => [
            'rule-name' => 'custom-message',
        ],
    ],

    /*
     * Human-readable German attribute names (fill in as domain fields appear).
     */
    'attributes' => [
        'name' => 'Name',
        'username' => 'Benutzername',
        'email' => 'E-Mail-Adresse',
        'password' => 'Passwort',
        'title' => 'Titel',
        'description' => 'Beschreibung',
        'due_date' => 'Fälligkeitsdatum',
        'priority' => 'Priorität',
        'assignee_id' => 'Zuständigkeit',
        'category' => 'Kategorie',
        'role' => 'Rolle',
    ],
];
