import TierBadge from '@/Components/Admin/TierBadge';
import PrimaryButton from '@/Components/PrimaryButton';
import TextInput from '@/Components/TextInput';
import AdminLayout from '@/Layouts/AdminLayout';
import { Head, Link, router } from '@inertiajs/react';
import { useState } from 'react';

export default function Index({ resourceKey, resource, records, filters }) {
    const [search, setSearch] = useState(filters.search || '');

    const submitSearch = (e) => {
        e.preventDefault();
        router.get(route('admin.resources.index', resourceKey), { search }, { preserveState: true });
    };

    return (
        <AdminLayout
            title={resource.label}
            actions={
                resource.creatable && (
                    <Link href={route('admin.resources.create', resourceKey)}>
                        <PrimaryButton>Создать</PrimaryButton>
                    </Link>
                )
            }
        >
            <Head title={resource.label} />

            <form onSubmit={submitSearch} className="mb-4 flex gap-2">
                <TextInput
                    className="max-w-xs"
                    value={search}
                    onChange={(e) => setSearch(e.target.value)}
                    placeholder="Поиск..."
                />
                <PrimaryButton type="submit">Найти</PrimaryButton>
            </form>

            <div className="overflow-x-auto rounded-lg bg-white shadow-sm">
                <table className="min-w-full text-sm">
                    <thead className="border-b bg-slate-50 text-left">
                        <tr>
                            {resource.columns.map((col) => (
                                <th key={col} className="px-4 py-3 font-medium text-slate-600">
                                    {col}
                                </th>
                            ))}
                            <th className="px-4 py-3" />
                        </tr>
                    </thead>
                    <tbody>
                        {records.data.map((row) => (
                            <tr key={row.id} className="border-b hover:bg-slate-50">
                                {resource.columns.map((col) => (
                                    <td key={col} className="px-4 py-2 text-slate-700">
                                        {col === 'tier' ? (
                                            <TierBadge value={row[col]} />
                                        ) : (
                                            String(row[col] ?? '—')
                                        )}
                                    </td>
                                ))}
                                <td className="px-4 py-2 text-right whitespace-nowrap">
                                    <Link
                                        href={route('admin.resources.show', [resourceKey, row.id])}
                                        className="text-indigo-600 hover:underline"
                                    >
                                        Открыть
                                    </Link>
                                </td>
                            </tr>
                        ))}
                    </tbody>
                </table>
            </div>

            {records.links?.length > 3 && (
                <div className="mt-4 flex flex-wrap gap-2">
                    {records.links.map((link, i) =>
                        link.url ? (
                            <Link
                                key={i}
                                href={link.url}
                                className={`rounded px-3 py-1 text-sm ${
                                    link.active
                                        ? 'bg-indigo-600 text-white'
                                        : 'bg-white text-slate-700 shadow-sm'
                                }`}
                                dangerouslySetInnerHTML={{ __html: link.label }}
                            />
                        ) : null,
                    )}
                </div>
            )}
        </AdminLayout>
    );
}
