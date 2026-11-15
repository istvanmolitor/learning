import type { CalloutVariant } from './types';

// The single place that maps a callout's *semantic* variant key to its
// *look*. Blocks never store a color — a redesign only ever touches this
// file, never the database or an exported package.
export interface CalloutVariantStyle {
    label: string;
    icon: string;
    border: string;
    bg: string;
    badge: string;
}

export const CALLOUT_VARIANTS: Record<CalloutVariant, CalloutVariantStyle> = {
    definicio: {
        label: 'Definíció',
        icon: '📘',
        border: 'border-blue-500/60 dark:border-blue-400/50',
        bg: 'bg-blue-50 dark:bg-blue-950/30',
        badge: 'bg-blue-600 dark:bg-blue-500',
    },
    tetel: {
        label: 'Tétel',
        icon: '★',
        border: 'border-emerald-500/60 dark:border-emerald-400/50',
        bg: 'bg-emerald-50 dark:bg-emerald-950/30',
        badge: 'bg-emerald-600 dark:bg-emerald-500',
    },
    keplet: {
        label: 'Kulcsképlet',
        icon: '🔑',
        border: 'border-amber-500/60 dark:border-amber-400/50',
        bg: 'bg-amber-50 dark:bg-amber-950/30',
        badge: 'bg-amber-600 dark:bg-amber-500',
    },
    pelda: {
        label: 'Példa',
        icon: '✏️',
        border: 'border-orange-500/60 dark:border-orange-400/50',
        bg: 'bg-orange-50 dark:bg-orange-950/30',
        badge: 'bg-orange-600 dark:bg-orange-500',
    },
    szemleletesen: {
        label: 'Szemléletesen',
        icon: '👁',
        border: 'border-teal-500/60 dark:border-teal-400/50',
        bg: 'bg-teal-50 dark:bg-teal-950/30',
        badge: 'bg-teal-600 dark:bg-teal-500',
    },
    vigyazz: {
        label: 'Vigyázz!',
        icon: '⚠',
        border: 'border-red-500/60 dark:border-red-400/50',
        bg: 'bg-red-50 dark:bg-red-950/30',
        badge: 'bg-red-600 dark:bg-red-500',
    },
    roviden: {
        label: 'Röviden',
        icon: '→',
        border: 'border-slate-400/60 dark:border-slate-500/50',
        bg: 'bg-slate-50 dark:bg-slate-900/40',
        badge: 'bg-slate-500 dark:bg-slate-600',
    },
    kiegeszites: {
        label: 'Kiegészítés',
        icon: '✦',
        border: 'border-violet-500/60 dark:border-violet-400/50 border-dashed',
        bg: 'bg-violet-50 dark:bg-violet-950/30',
        badge: 'bg-violet-600 dark:bg-violet-500',
    },
    tipp: {
        label: 'Tipp',
        icon: '💡',
        border: 'border-cyan-500/60 dark:border-cyan-400/50',
        bg: 'bg-cyan-50 dark:bg-cyan-950/30',
        badge: 'bg-cyan-600 dark:bg-cyan-500',
    },
    cel: {
        label: 'Miről szól?',
        icon: '🎯',
        border: 'border-indigo-500/60 dark:border-indigo-400/50',
        bg: 'bg-indigo-50 dark:bg-indigo-950/30',
        badge: 'bg-indigo-600 dark:bg-indigo-500',
    },
};
