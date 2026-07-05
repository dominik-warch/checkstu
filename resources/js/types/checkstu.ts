export type OccurrenceStatus = 'done' | 'skipped' | 'someday' | 'overdue' | 'due_soon' | 'open';

export interface Member {
    id: number;
    name: string;
    role: 'admin' | 'member';
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

export interface FamilyMember {
    id: number;
    name: string;
    username: string;
    email: string | null;
    role: 'admin' | 'member';
    open_count: number;
}
