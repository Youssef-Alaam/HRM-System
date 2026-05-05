import { router } from '@inertiajs/react';
import axios from 'axios';
import { Search, X } from 'lucide-react';
import { useCallback, useEffect, useRef, useState } from 'react';

type Result = {
    type: string;
    id: number;
    label: string;
    meta: string;
    href: string;
};

const TYPE_LABELS: Record<string, string> = {
    employee: 'Employee',
    announcement: 'Announcement',
    document: 'Document',
};

export default function GlobalSearch() {
    const [open, setOpen] = useState(false);
    const [query, setQuery] = useState('');
    const [results, setResults] = useState<Result[]>([]);
    const [loading, setLoading] = useState(false);
    const [selected, setSelected] = useState(0);
    const inputRef = useRef<HTMLInputElement>(null);
    const timerRef = useRef<ReturnType<typeof setTimeout> | null>(null);

    // Open on Cmd+K / Ctrl+K
    useEffect(() => {
        function onKey(e: KeyboardEvent) {
            if ((e.metaKey || e.ctrlKey) && e.key === 'k') {
                e.preventDefault();
                setOpen(v => !v);
            }
            if (e.key === 'Escape') {
                setOpen(false);
            }
        }
        window.addEventListener('keydown', onKey);
        return () => window.removeEventListener('keydown', onKey);
    }, []);

    useEffect(() => {
        if (open) {
            setTimeout(() => inputRef.current?.focus(), 50);
        } else {
            setQuery('');
            setResults([]);
            setSelected(0);
        }
    }, [open]);

    const search = useCallback((q: string) => {
        if (q.length < 2) { setResults([]); return; }
        setLoading(true);
        axios.get('/search', { params: { q } })
            .then(r => { setResults(r.data.results); setSelected(0); })
            .finally(() => setLoading(false));
    }, []);

    function handleChange(e: React.ChangeEvent<HTMLInputElement>) {
        const q = e.target.value;
        setQuery(q);
        if (timerRef.current) clearTimeout(timerRef.current);
        timerRef.current = setTimeout(() => search(q), 200);
    }

    function navigate(href: string) {
        setOpen(false);
        router.visit(href);
    }

    function handleKeyDown(e: React.KeyboardEvent) {
        if (e.key === 'ArrowDown') { e.preventDefault(); setSelected(s => Math.min(s + 1, results.length - 1)); }
        if (e.key === 'ArrowUp') { e.preventDefault(); setSelected(s => Math.max(s - 1, 0)); }
        if (e.key === 'Enter' && results[selected]) { navigate(results[selected].href); }
    }

    if (!open) {
        return (
            <button onClick={() => setOpen(true)}
                className="hidden sm:flex items-center gap-2 h-8 px-3 rounded-md border border-yzh-bone-soft bg-white text-yzh-slate hover:border-yzh-gold hover:text-yzh-ink transition-colors">
                <Search className="h-3.5 w-3.5" />
                <span className="font-mono text-[0.6875rem] uppercase tracking-[0.18em]">Search</span>
                <kbd className="ml-1 font-mono text-[0.6rem] text-yzh-slate border border-yzh-bone-soft rounded px-1">⌘K</kbd>
            </button>
        );
    }

    return (
        <div className="fixed inset-0 z-50 flex items-start justify-center pt-20 px-4" onClick={() => setOpen(false)}>
            <div className="w-full max-w-lg bg-white border border-yzh-bone-soft rounded-sm shadow-xl" onClick={e => e.stopPropagation()}>
                {/* Input row */}
                <div className="flex items-center gap-3 px-4 py-3 border-b border-yzh-bone-soft">
                    <Search className="h-4 w-4 shrink-0 text-yzh-slate" />
                    <input
                        ref={inputRef}
                        value={query}
                        onChange={handleChange}
                        onKeyDown={handleKeyDown}
                        placeholder="Search employees, announcements…"
                        className="flex-1 bg-transparent text-sm text-yzh-ink placeholder:text-yzh-slate outline-none"
                    />
                    {loading && <span className="font-mono text-[0.6rem] text-yzh-slate animate-pulse">…</span>}
                    <button onClick={() => setOpen(false)} className="text-yzh-slate hover:text-yzh-ink">
                        <X className="h-4 w-4" />
                    </button>
                </div>

                {/* Results */}
                {results.length > 0 && (
                    <ul className="py-2 max-h-72 overflow-y-auto">
                        {results.map((r, i) => (
                            <li key={`${r.type}-${r.id}`}>
                                <button
                                    onClick={() => navigate(r.href)}
                                    className={`w-full flex items-baseline gap-3 px-4 py-2.5 text-left transition-colors ${i === selected ? 'bg-yzh-bone-soft/60' : 'hover:bg-yzh-bone-soft/30'}`}>
                                    <span className="font-mono text-[0.6rem] uppercase tracking-[0.18em] text-yzh-gold shrink-0 w-20 truncate">
                                        {TYPE_LABELS[r.type] ?? r.type}
                                    </span>
                                    <span className="text-sm text-yzh-ink truncate">{r.label}</span>
                                    <span className="ml-auto font-mono text-[0.6875rem] text-yzh-slate shrink-0">{r.meta}</span>
                                </button>
                            </li>
                        ))}
                    </ul>
                )}
                {query.length >= 2 && results.length === 0 && !loading && (
                    <p className="px-4 py-4 text-sm text-yzh-slate">No results for "{query}"</p>
                )}

                <div className="px-4 py-2 border-t border-yzh-bone-soft flex gap-4">
                    <span className="font-mono text-[0.6rem] text-yzh-slate">↑↓ navigate</span>
                    <span className="font-mono text-[0.6rem] text-yzh-slate">↵ open</span>
                    <span className="font-mono text-[0.6rem] text-yzh-slate">esc close</span>
                </div>
            </div>
        </div>
    );
}
