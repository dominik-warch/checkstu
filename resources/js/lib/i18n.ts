/**
 * Frontend i18n for checkstu.
 *
 * Convention: code is English, UI is German. Components never hardcode German
 * strings — they call `t('some.key')` and the German text lives here, in one
 * place to proofread. Single locale for now (`de`); the shape leaves room to
 * add another locale later without touching call sites.
 */

const de = {
    common: {
        appName: 'checkstu',
        save: 'Speichern',
        cancel: 'Abbrechen',
        delete: 'Löschen',
        edit: 'Bearbeiten',
        add: 'Hinzufügen',
        done: 'Erledigt',
        create: 'Erstellen',
        search: 'Suchen',
        loading: 'Wird geladen …',
        back: 'Zurück',
        all: 'Alle',
    },
    nav: {
        today: 'Heute',
        tasks: 'Aufgaben',
        family: 'Familie',
        archive: 'Archiv',
        settings: 'Einstellungen',
        menu: 'Menü',
        media: 'Medien',
        switchToTasks: 'Haushalt',
        switchToMedia: 'Medien',
        switchToBoardGames: 'Brettspiele',
    },
    task: {
        title: 'Titel',
        description: 'Beschreibung',
        dueDate: 'Fällig am',
        priority: 'Dringlichkeit',
        assignee: 'Zuständig',
        markDone: 'Als erledigt markieren',
        blocked: 'Blockiert',
        waitingOn: 'Wartet auf: :task',
        newTask: 'Neue Aufgabe',
        editTask: 'Aufgabe bearbeiten',
        whoDidIt: 'Wer hat es erledigt?',
        unassigned: 'Für alle',
        private: 'Privat',
        privateHint: 'Nur die zugewiesene Person sieht diese Aufgabe – sonst niemand',
        recurring: 'Wiederkehrende Aufgabe',
        blockedBy: 'Blockiert durch',
        blocks: 'Blockiert',
        noDescription: 'Keine Beschreibung',
        moreOptions: 'Mehr Optionen',
        alreadyDone: 'Diese Aufgabe ist bereits erledigt.',
    },
    archive: {
        title: 'Archiv',
        empty: 'Noch nichts erledigt. Was du abhakst, landet hier.',
        restore: 'Wiederherstellen',
        completedBy: 'Erledigt von :name',
    },
    recurrence: {
        title: 'Wiederholung',
        oneOff: 'Einmalig',
        regular: 'Regelmäßig',
        irregular: 'Unregelmäßig',
        everyNDays: 'Alle X Tage',
        frequency: 'Häufigkeit',
        daily: 'Täglich',
        weekly: 'Wöchentlich',
        monthly: 'Monatlich',
        every: 'Alle',
        intervalDaysUnit: 'Tag(e)',
        intervalWeeksUnit: 'Woche(n)',
        intervalMonthsUnit: 'Monat(e)',
        weekdays: 'Wochentage',
        startsOn: 'Beginnt am',
        endsOn: 'Endet am (optional)',
        specificDates: 'Termine',
        addDate: 'Termin hinzufügen',
        daysAfterCompletion: 'Anzahl Tage nach Erledigung',
    },
    priority: {
        low: 'Niedrig',
        normal: 'Normal',
        high: 'Hoch',
        urgent: 'Dringend',
    },
    role: {
        admin: 'Elternteil',
        member: 'Kind',
        guest: 'Gast',
    },
    family: {
        title: 'Familie',
        addMember: 'Mitglied hinzufügen',
        editMember: 'Mitglied bearbeiten',
        name: 'Name',
        username: 'Benutzername',
        email: 'E-Mail (optional)',
        password: 'Passwort',
        passwordKeepHint: 'Leer lassen, um das Passwort nicht zu ändern',
        roleParent: 'Elternteil (Admin)',
        roleChild: 'Kind (Mitglied)',
        roleGuest: 'Gast (nur eigene Aufgaben)',
        color: 'Farbe',
        deleteMember: 'Mitglied löschen',
    },
    media: {
        home: 'Medien',
        library: 'Bibliothek',
        typeMovie: 'Film',
        typeTv: 'Serie',
        typeBook: 'Buch',
        searchPlaceholderMovie: 'Film suchen …',
        searchPlaceholderTv: 'Serie suchen …',
        searchPlaceholderBook: 'Buch suchen …',
        searchEmpty: 'Keine Ergebnisse.',
        searchError: 'Suche fehlgeschlagen.',
        searchUnavailable: 'Die Suche ist gerade nicht erreichbar — das liegt nicht an deiner Eingabe. Bitte versuche es in ein paar Minuten erneut.',
        addToWatchlist: 'Zur Merkliste',
        markWatched: 'Gesehen',
        markRead: 'Gelesen',
        watchlist: 'Merkliste',
        watching: 'Wird geschaut',
        completed: 'Abgeschlossen',
        dropped: 'Abgebrochen',
        markCompletedTv: 'Serie ist durch',
        markDropped: 'Serie abbrechen',
        sortAlphabetical: 'A–Z',
        sortRecent: 'Zuletzt hinzugefügt',
        sortRelease: 'Veröffentlichung',
        sortLastWatched: 'Zuletzt geschaut',
        sortFewestRemaining: 'Wenigste Folgen übrig',
        emptyLibrary: 'Noch nichts in der Bibliothek. Mit + fügst du Serien, Filme und Bücher hinzu.',
        watchedAt: 'Gesehen am',
        watchedTimes: ':count× gesehen',
        logAnotherWatch: 'Weiteres Mal loggen',
        readAt: 'Gelesen am',
        byAuthor: 'von :authors',
        remove: 'Entfernen',
        removeFromLibrary: 'Aus der Bibliothek entfernen',
        nextEpisode: 'Nächste Folge',
        season: 'Staffel :number',
        episode: 'Folge :number',
        nothingInProgress: 'Keine neuen Folgen. Serien, die ihr gerade schaut, tauchen hier auf.',
        markEpisodeWatched: 'Als gesehen markieren',
        unmarkEpisodeWatched: 'Als ungesehen markieren',
        markSeasonWatched: 'Staffel als gesehen markieren',
        markAllWatched: 'Alle Folgen abhaken',
        overview: 'Beschreibung',
        releaseDate: 'Veröffentlicht am',
        seasons: 'Staffeln',
        allCaughtUp: 'Alle verfügbaren Folgen gesehen.',
        addNew: 'Hinzufügen',
        inLibrary: 'Schon in deiner Bibliothek',
        sharedWith: 'Auch bei:',
        comingUp: 'Kommt bald',
        comingUpEmpty: 'Nichts Neues in Sicht.',
        together: 'Gemeinsam',
        togetherPrompt: 'Wähle ein Familienmitglied, um eure gemeinsame Merkliste zu sehen.',
        togetherEmpty: 'Nichts, das ihr beide auf der Merkliste habt.',
        whereToStream: 'Wo streamen?',
    },
    boardGames: {
        catalogue: 'Katalog',
        plays: 'Partien',
        owned: 'Haben wir',
        wishlist: 'Wollen wir',
        addGame: 'Spiel hinzufügen',
        searchPlaceholder: 'Spiel suchen …',
        searchEmpty: 'Keine Ergebnisse.',
        searchError: 'Suche fehlgeschlagen.',
        searchUnavailable: 'Die Suche ist gerade nicht erreichbar — das liegt nicht an deiner Eingabe. Bitte versuche es in ein paar Minuten erneut.',
        poweredByBgg: 'Spieledaten von BoardGameGeek',
        addManually: 'Manuell hinzufügen',
        gameName: 'Name des Spiels',
        moveToOwned: 'Als „Haben wir" markieren',
        moveToWishlist: 'Als „Wollen wir" markieren',
        emptyCatalogue: 'Noch kein Spiel im Katalog. Mit + suchst du eins und stellst es ins Regal.',
        removeGame: 'Spiel entfernen',
        logPlay: 'Partie eintragen',
        editPlay: 'Partie bearbeiten',
        game: 'Spiel',
        playedAt: 'Gespielt am',
        players: 'Mitspieler',
        addPlayer: 'Mitspieler hinzufügen',
        addGuest: 'Gast hinzufügen',
        guestName: 'Name des Gasts',
        winner: 'Gewonnen',
        notes: 'Notizen',
        emptyPlays: 'Noch keine Partie eingetragen. Mit + hältst du die nächste fest.',
        removePlay: 'Eintrag löschen',
        playersRequired: 'Mindestens ein Mitspieler ist erforderlich.',
    },
    notifications: {
        title: 'Benachrichtigungen',
        description: 'Erhalte eine Push-Benachrichtigung bei neuen oder überfälligen Aufgaben.',
        enable: 'Push-Benachrichtigungen aktivieren',
        disable: 'Push-Benachrichtigungen deaktivieren',
        enabled: 'Aktiviert auf diesem Gerät',
        unsupported: 'Push-Benachrichtigungen werden auf diesem Gerät/Browser nicht unterstützt.',
        permissionDenied:
            'Benachrichtigungen sind für checkstu gerade aus. Erlaube sie in den Website-Einstellungen deines Browsers (meist über das Symbol neben der Adresse) und lade die Seite neu. Auf dem iPhone klappt das nur, wenn checkstu auf dem Home-Bildschirm liegt.',
    },
    settings: {
        backToApp: 'Zurück zur App',
        menuTitle: 'Einstellungen',
        menuSettings: 'Einstellungen',
        logOut: 'Abmelden',
        profileTitle: 'Profil',
        profileHeading: 'Profilangaben',
        profileDescription: 'Name und E-Mail-Adresse aktualisieren',
        name: 'Name',
        email: 'E-Mail-Adresse',
        emailUnverified: 'Deine E-Mail-Adresse ist nicht bestätigt.',
        resendVerification: 'Bestätigungslink erneut senden.',
        verificationSent: 'Ein neuer Bestätigungslink wurde an deine E-Mail-Adresse gesendet.',
        save: 'Speichern',
        saved: 'Gespeichert',
        passwordTitle: 'Passwort',
        passwordHeading: 'Passwort ändern',
        passwordDescription: 'Verwende ein langes, zufälliges Passwort, um dein Konto abzusichern',
        currentPassword: 'Aktuelles Passwort',
        newPassword: 'Neues Passwort',
        confirmPassword: 'Passwort bestätigen',
        savePassword: 'Passwort speichern',
        appearanceTitle: 'Darstellung',
        appearanceHeading: 'Darstellung',
        appearanceDescription: 'Erscheinungsbild der App anpassen',
        appearanceLight: 'Hell',
        appearanceDark: 'Dunkel',
        appearanceSystem: 'System',
    },
} as const;

type Dict = typeof de;

// Dotted key paths into the dictionary, e.g. 'nav.today'.
type Leaves<T, P extends string = ''> = {
    [K in keyof T & string]: T[K] extends object ? Leaves<T[K], `${P}${K}.`> : `${P}${K}`;
}[keyof T & string];

export type TranslationKey = Leaves<Dict>;

function lookup(key: string): string {
    return (
        (key.split('.').reduce<unknown>((acc, part) => {
            if (acc && typeof acc === 'object' && part in acc) {
                return (acc as Record<string, unknown>)[part];
            }
            return undefined;
        }, de) as string) ?? key
    );
}

/**
 * Translate a key, interpolating `:name` placeholders.
 * t('task.waitingOn', { task: 'Aufräumen' }) => 'Wartet auf: Aufräumen'
 */
export function t(key: TranslationKey, replacements?: Record<string, string | number>): string {
    let text = lookup(key);
    if (replacements) {
        for (const [k, v] of Object.entries(replacements)) {
            text = text.replaceAll(`:${k}`, String(v));
        }
    }
    return text;
}

export default de;
