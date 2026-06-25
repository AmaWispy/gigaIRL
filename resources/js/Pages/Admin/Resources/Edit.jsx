import FieldRenderer from '@/Components/Admin/FieldRenderer';
import PrimaryButton from '@/Components/PrimaryButton';
import AdminLayout from '@/Layouts/AdminLayout';
import { Head, Link, useForm } from '@inertiajs/react';

export default function Edit({ resourceKey, resource, record, isCreate }) {
    const { data, setData, post, put, processing, errors } = useForm(record);

    const submit = (e) => {
        e.preventDefault();
        if (isCreate) {
            post(route('admin.resources.store', resourceKey));
        } else {
            put(route('admin.resources.update', [resourceKey, record.id]));
        }
    };

    return (
        <AdminLayout
            title={isCreate ? `Создать: ${resource.label}` : `Редактировать #${record.id}`}
            actions={
                <Link href={route('admin.resources.index', resourceKey)}>
                    <PrimaryButton type="button">← Назад</PrimaryButton>
                </Link>
            }
        >
            <Head title={resource.label} />

            <form onSubmit={submit} className="space-y-4 rounded-lg bg-white p-6 shadow-sm">
                {resource.fields.map((field) => (
                    <FieldRenderer
                        key={field.name}
                        field={field}
                        value={data[field.name]}
                        onChange={(value) => setData(field.name, value)}
                        error={errors[field.name]}
                    />
                ))}

                <PrimaryButton disabled={processing}>
                    {isCreate ? 'Создать' : 'Сохранить'}
                </PrimaryButton>
            </form>
        </AdminLayout>
    );
}
