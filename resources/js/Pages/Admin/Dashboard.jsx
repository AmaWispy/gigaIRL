import AdminLayout from '@/Layouts/AdminLayout';
import { Head, Link } from '@inertiajs/react';

export default function Dashboard({ navigation }) {
    return (
        <AdminLayout title="Панель администратора">
            <Head title="Админка" />

            <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                {navigation.map((section) => (
                    <div key={section.group} className="rounded-lg bg-white p-4 shadow-sm">
                        <h2 className="mb-3 font-semibold text-slate-800">{section.group}</h2>
                        <ul className="space-y-2">
                            {section.items.map((item) => (
                                <li key={item.key}>
                                    <Link
                                        href={route('admin.resources.index', item.key)}
                                        className="text-indigo-600 hover:underline"
                                    >
                                        {item.label}
                                    </Link>
                                </li>
                            ))}
                        </ul>
                    </div>
                ))}
            </div>
        </AdminLayout>
    );
}
