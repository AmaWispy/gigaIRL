import DangerButton from '@/Components/DangerButton';
import DungeonConfigView from '@/Components/Admin/DungeonConfigView';
import FieldDisplay from '@/Components/Admin/FieldDisplay';
import PrimaryButton from '@/Components/PrimaryButton';
import AdminLayout from '@/Layouts/AdminLayout';
import { Head, Link, router } from '@inertiajs/react';

export default function Show({ resourceKey, resource, record, dungeonConfig }) {
    const destroy = () => {
        if (confirm('Удалить запись?')) {
            router.delete(route('admin.resources.destroy', [resourceKey, record.id]));
        }
    };

    return (
        <AdminLayout
            title={`${resource.label} #${record.id}`}
            actions={
                <div className="flex gap-2">
                    <Link href={route('admin.resources.index', resourceKey)}>
                        <PrimaryButton type="button">← Назад</PrimaryButton>
                    </Link>
                    {resource.editable && (
                        <Link href={route('admin.resources.edit', [resourceKey, record.id])}>
                            <PrimaryButton type="button">Редактировать</PrimaryButton>
                        </Link>
                    )}
                    {resource.editable && (
                        <DangerButton type="button" onClick={destroy}>
                            Удалить
                        </DangerButton>
                    )}
                </div>
            }
        >
            <Head title={`${resource.label} #${record.id}`} />

            <div className="rounded-lg bg-white p-6 shadow-sm">
                <dl className="grid gap-4 sm:grid-cols-2">
                    <div>
                        <dt className="text-sm font-medium text-slate-500">ID</dt>
                        <dd className="mt-1 text-slate-900">{record.id}</dd>
                    </div>
                    {resource.fields.map((field) => (
                        <FieldDisplay key={field.name} field={field} value={record[field.name]} />
                    ))}
                </dl>
            </div>

            {dungeonConfig && (
                <div className="mt-6 rounded-lg bg-white p-6 shadow-sm">
                    <h2 className="mb-4 text-lg font-semibold text-slate-800">Конфиг данжа</h2>
                    <DungeonConfigView config={dungeonConfig} showHeader={false} />
                </div>
            )}
        </AdminLayout>
    );
}
