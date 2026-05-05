import AppLayout from '@/Layouts/AppLayout';
import { Head } from '@inertiajs/react';
import { Download } from 'lucide-react';
import { ReactNode, useState } from 'react';

type Props = {
    current_month: string;
    current_year: number;
};

function Section({ num, title, children }: { num: string; title: string; children: React.ReactNode }) {
    return (
        <section>
            <div className="border-t border-yzh-bone-soft pt-5">
                <div className="flex items-baseline gap-3 mb-6">
                    <span className="font-mono text-xs uppercase tracking-[0.24em] text-yzh-gold">{num}</span>
                    <span className="font-mono text-xs uppercase tracking-[0.24em] text-yzh-text">{title}</span>
                </div>
                {children}
            </div>
        </section>
    );
}

function DownloadButton({ href, label }: { href: string; label: string }) {
    return (
        <a href={href}
            className="inline-flex min-h-11 items-center gap-2 border border-yzh-gold px-4 py-2 font-mono text-xs uppercase tracking-[0.22em] text-yzh-gold hover:bg-yzh-gold hover:text-yzh-ink">
            <Download className="h-3.5 w-3.5" /> {label}
        </a>
    );
}

export default function Index({ current_month, current_year }: Props) {
    const [nosiMonth, setNosiMonth] = useState(current_month);
    const [form6Year, setForm6Year] = useState(current_year.toString());

    return (
        <>
            <Head title="Government Filings" />
            <div className="space-y-12 sm:space-y-16">
                <Section num="00" title="About government filings">
                    <div className="max-w-lg space-y-3 text-sm text-yzh-slate">
                        <p>Generate compliant export files for Egyptian government agencies. Download and upload manually to each agency's portal per Decision 21.</p>
                        <ul className="list-disc list-inside space-y-1">
                            <li><strong className="text-yzh-ink">NOSI</strong> — Monthly social insurance enrollment (Law 148/2019). Due by the 15th of the following month.</li>
                            <li><strong className="text-yzh-ink">Form 6</strong> — Annual employment earnings report for ETA. Due January 31 of the following year.</li>
                        </ul>
                        <p className="font-mono text-[0.6875rem] uppercase tracking-[0.18em] text-yzh-slate">Income tax withholding (ETA monthly Form 1) is deferred pending Payroll (Feature 15).</p>
                    </div>
                </Section>

                <Section num="01" title="NOSI — Monthly social insurance">
                    <div className="flex items-end gap-4 flex-wrap">
                        <div>
                            <label className="mb-1 block font-mono text-[0.6875rem] uppercase tracking-[0.22em] text-yzh-text">Month</label>
                            <input type="month" value={nosiMonth} onChange={e => setNosiMonth(e.target.value)}
                                className="input-base h-11 w-48" />
                        </div>
                        <DownloadButton href={`/government-filings/nosi?month=${nosiMonth}`} label="Download NOSI report" />
                    </div>
                    <div className="mt-4 text-xs text-yzh-slate space-y-1">
                        <p>Includes: employee code, name, national ID, SI number, hire date, insurable wage, employee + employer contributions.</p>
                        <p>SI rates: employee 11% + employer 18.75% of insurable wage. Cap EGP 14,500/month (2026).</p>
                    </div>
                </Section>

                <Section num="02" title="Form 6 — Annual employment report (ETA)">
                    <div className="flex items-end gap-4 flex-wrap">
                        <div>
                            <label className="mb-1 block font-mono text-[0.6875rem] uppercase tracking-[0.22em] text-yzh-text">Year</label>
                            <input type="number" value={form6Year} onChange={e => setForm6Year(e.target.value)}
                                min="2020" max={current_year + 1} className="input-base h-11 w-32" />
                        </div>
                        <DownloadButton href={`/government-filings/form6?year=${form6Year}`} label="Download Form 6" />
                    </div>
                    <div className="mt-4 text-xs text-yzh-slate space-y-1">
                        <p>Includes: all employees active during the year (including terminated), annual salary, SI contributions.</p>
                        <p>Income tax withholding column will be populated when Payroll (Feature 15) is activated.</p>
                    </div>
                </Section>

                <Section num="03" title="Filing deadlines">
                    <table className="w-full max-w-lg text-sm">
                        <thead>
                            <tr className="border-b border-yzh-bone-soft">
                                <th className="py-2 pr-8 text-left font-mono text-[0.6875rem] uppercase tracking-[0.22em] text-yzh-text">Report</th>
                                <th className="py-2 pr-8 text-left font-mono text-[0.6875rem] uppercase tracking-[0.22em] text-yzh-text">Frequency</th>
                                <th className="py-2 text-left font-mono text-[0.6875rem] uppercase tracking-[0.22em] text-yzh-text">Deadline</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-yzh-bone-soft">
                            <tr>
                                <td className="py-2 pr-8 text-yzh-ink">NOSI SI contribution</td>
                                <td className="py-2 pr-8 text-yzh-slate">Monthly</td>
                                <td className="py-2 text-yzh-slate">15th of following month</td>
                            </tr>
                            <tr>
                                <td className="py-2 pr-8 text-yzh-ink">ETA Form 1 (wage withholding)</td>
                                <td className="py-2 pr-8 text-yzh-slate">Monthly</td>
                                <td className="py-2 text-yzh-slate">15th of following month (deferred)</td>
                            </tr>
                            <tr>
                                <td className="py-2 pr-8 text-yzh-ink">ETA Form 6 (annual)</td>
                                <td className="py-2 pr-8 text-yzh-slate">Annual</td>
                                <td className="py-2 text-yzh-slate">31 Jan of following year</td>
                            </tr>
                        </tbody>
                    </table>
                </Section>
            </div>
        </>
    );
}

Index.layout = (page: ReactNode) => (
    <AppLayout header={
        <div className="flex flex-col gap-2">
            <p className="font-mono text-xs uppercase tracking-[0.24em] text-yzh-gold">G.06 / Government Filings</p>
            <h1 className="text-3xl font-semibold leading-tight tracking-tight text-yzh-ink sm:text-4xl">Government Filings.</h1>
        </div>
    }>{page}</AppLayout>
);
