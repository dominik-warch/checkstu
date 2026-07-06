export type OccurrenceStatus = 'done' | 'skipped' | 'someday' | 'overdue' | 'due_soon' | 'open';

export interface Member {
    id: number;
    name: string;
    role: 'admin' | 'member' | 'guest';
}

export interface CategoryTag {
    id: number;
    name: string;
    color: string | null;
}

export interface Occurrence {
    id: number;
    task_id: number;
    title: string;
    description: string | null;
    priority: number; // 0..3
    is_private: boolean;
    due_date: string | null;
    status: OccurrenceStatus;
    is_blocked: boolean;
    blocking_titles: string[];
    assignee: { id: number; name: string } | null;
    completed_by: { id: number; name: string } | null;
    categories: CategoryTag[];
}

export interface TaskAbilities {
    completeOnBehalf: boolean;
    createTask: boolean;
    manageUsers?: boolean;
}

export interface TaskTemplateSummary {
    id: number;
    name: string;
    usage_count: number;
}

export interface FamilyMember {
    id: number;
    name: string;
    username: string;
    email: string | null;
    role: 'admin' | 'member' | 'guest';
    open_count: number;
}
