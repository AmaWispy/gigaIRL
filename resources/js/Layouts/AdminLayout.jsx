import ApplicationLogo from '@/Components/ApplicationLogo';
import { Link, usePage } from '@inertiajs/react';

export default function AdminLayout({ title, children, actions }) {
    const { auth, admin, flash } = usePage().props;
    const { url } = usePage();

    return (
        <div className="min-h-screen bg-slate-100">
            <nav className="border-b border-slate-200 bg-slate-900 text-white">
                <div className="mx-auto flex h-14 max-w-7xl items-center justify-between px-4 sm:px-6 lg:px-8">
                    <div className="flex items-center gap-6">
                        <Link href={route('admin.dashboard')} className="flex items-center gap-2">
                            <ApplicationLogo className="h-8 w-8" />
                            <span className="font-semibold">gigaIRL Admin</span>
                        </Link>
                        <Link
                            href={route('admin.config')}
                            className="text-sm text-slate-300 hover:text-white"
                        >
                            Баланс
                        </Link>
                        <Link
                            href={route('dashboard')}
                            className="text-sm text-slate-300 hover:text-white"
                        >
                            ← Игра
                        </Link>
                    </div>
                    <span className="text-sm text-slate-300">{auth.user?.nickname}</span>
                </div>
            </nav>

            <div className="mx-auto flex max-w-7xl gap-6 px-4 py-6 sm:px-6 lg:px-8">
                <aside className="hidden w-56 shrink-0 lg:block">
                    <nav className="space-y-6">
                        {admin?.navigation?.map((section) => (
                            <div key={section.group}>
                                <p className="mb-2 text-xs font-semibold uppercase tracking-wide text-slate-700">
                                    {section.group}
                                </p>
                                <ul className="space-y-1">
                                    {section.items.map((item) => {
                                        const active = url?.startsWith(`/admin/resources/${item.key}`);
                                        return (
                                            <li key={item.key}>
                                                <Link
                                                    href={route('admin.resources.index', item.key)}
                                                    className={`block rounded px-2 py-1.5 text-sm ${
                                                        active
                                                            ? 'bg-white font-medium text-slate-900 shadow-sm ring-1 ring-slate-200'
                                                            : 'text-slate-700 hover:bg-slate-200 hover:text-slate-900'
                                                    }`}
                                                >
                                                    {item.label}
                                                </Link>
                                            </li>
                                        );
                                    })}
                                </ul>
                            </div>
                        ))}
                    </nav>
                </aside>

                <main className="min-w-0 flex-1">
                    {flash?.success && (
                        <div className="mb-4 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">
                            {flash.success}
                        </div>
                    )}

                    {(title || actions) && (
                        <div className="mb-6 flex items-center justify-between">
                            {title && <h1 className="text-2xl font-bold text-slate-900">{title}</h1>}
                            {actions}
                        </div>
                    )}

                    {children}
                </main>
            </div>
        </div>
    );
}
