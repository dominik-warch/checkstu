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
    },
    nav: {
        today: 'Heute',
        tasks: 'Aufgaben',
        upcoming: 'Demnächst',
        family: 'Familie',
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
        whoDidIt: 'Wer hat es erledigt?',
    },
    priority: {
        low: 'Niedrig',
        normal: 'Normal',
        high: 'Hoch',
        urgent: 'Dringend',
    },
} as const;

type Dict = typeof de;

// Dotted key paths into the dictionary, e.g. 'nav.today'.
type Leaves<T, P extends string = ''> = {
    [K in keyof T & string]: T[K] extends object
        ? Leaves<T[K], `${P}${K}.`>
        : `${P}${K}`;
}[keyof T & string];

export type TranslationKey = Leaves<Dict>;

function lookup(key: string): string {
    return key.split('.').reduce<unknown>((acc, part) => {
        if (acc && typeof acc === 'object' && part in acc) {
            return (acc as Record<string, unknown>)[part];
        }
        return undefined;
    }, de) as string ?? key;
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
