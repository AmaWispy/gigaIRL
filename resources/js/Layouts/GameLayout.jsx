import ApplicationLogo from '@/Components/ApplicationLogo';
import Dropdown from '@/Components/Dropdown';
import NavLink from '@/Components/NavLink';
import ResponsiveNavLink from '@/Components/ResponsiveNavLink';
import { Link, usePage } from '@inertiajs/react';
import { useState } from 'react';

function StatBar({ label, current, max, color }) {
    const percent = max > 0 ? Math.round((current / max) * 100) : 0;
    return (
        <div className="min-w-[120px]">
            <div className="flex justify-between text-xs text-gray-600">
                <span>{label}</span>
                <span>
                    {current}/{max}
                </span>
            </div>
            <div className="mt-1 h-2 w-full rounded-full bg-gray-200">
                <div
                    className={`h-2 rounded-full ${color}`}
                    style={{ width: `${percent}%` }}
                />
            </div>
        </div>
    );
}

export default function GameLayout({ header, children }) {
    const { auth, character, flash } = usePage().props;
    const [showingNavigationDropdown, setShowingNavigationDropdown] =
        useState(false);

    return (
        <div className="min-h-screen bg-gray-100">
            <nav className="border-b border-gray-100 bg-white">
                <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div className="flex h-16 justify-between">
                        <div className="flex">
                            <div className="flex shrink-0 items-center">
                                <Link href="/">
                                    <ApplicationLogo className="block h-9 w-auto fill-current text-gray-800" />
                                </Link>
                            </div>

                            <div className="hidden space-x-8 sm:-my-px sm:ms-10 sm:flex">
                                <NavLink
                                    href={route('dashboard')}
                                    active={route().current('dashboard')}
                                >
                                    Игра
                                </NavLink>
                                <NavLink
                                    href={route('achievements.index')}
                                    active={route().current('achievements.*')}
                                >
                                    Достижения
                                </NavLink>
                                <NavLink
                                    href={route('character.show')}
                                    active={route().current('character.*')}
                                >
                                    Персонаж
                                </NavLink>
                                <NavLink
                                    href={route('world.map')}
                                    active={route().current('world.*')}
                                >
                                    Карта
                                </NavLink>
                                <NavLink
                                    href={route('crafting.index')}
                                    active={route().current('crafting.*')}
                                >
                                    Ремесло
                                </NavLink>
                                {auth.is_admin && (
                                    <NavLink
                                        href={route('admin.dashboard')}
                                        active={route().current('admin.*')}
                                    >
                                        Админка
                                    </NavLink>
                                )}
                            </div>
                        </div>

                        {character && (
                            <div className="hidden items-center gap-4 lg:flex">
                                <StatBar
                                    label="HP"
                                    current={character.hp}
                                    max={character.effective_max_hp ?? character.max_hp}
                                    color="bg-red-500"
                                />
                                <div className="text-sm">
                                    <span className="text-amber-600 font-semibold">
                                        ⚡ {character.energy}
                                    </span>
                                    <span className="mx-2 text-gray-400">|</span>
                                    <span className="text-yellow-600 font-semibold">
                                        💰 {character.money}
                                    </span>
                                    <span className="mx-2 text-gray-400">|</span>
                                    <span className="text-blue-600 font-semibold">
                                        Ур. {character.level}
                                    </span>
                                </div>
                            </div>
                        )}

                        <div className="hidden sm:ms-6 sm:flex sm:items-center">
                            <div className="relative ms-3">
                                <Dropdown>
                                    <Dropdown.Trigger>
                                        <span className="inline-flex rounded-md">
                                            <button
                                                type="button"
                                                className="inline-flex items-center rounded-md border border-transparent bg-white px-3 py-2 text-sm font-medium leading-4 text-gray-500 transition duration-150 ease-in-out hover:text-gray-700 focus:outline-none"
                                            >
                                                {auth.user.nickname}
                                            </button>
                                        </span>
                                    </Dropdown.Trigger>
                                    <Dropdown.Content>
                                        <Dropdown.Link href={route('profile.edit')}>
                                            Профиль
                                        </Dropdown.Link>
                                        <Dropdown.Link
                                            href={route('logout')}
                                            method="post"
                                            as="button"
                                        >
                                            Выйти
                                        </Dropdown.Link>
                                    </Dropdown.Content>
                                </Dropdown>
                            </div>
                        </div>

                        <div className="-me-2 flex items-center sm:hidden">
                            <button
                                onClick={() =>
                                    setShowingNavigationDropdown((s) => !s)
                                }
                                className="inline-flex items-center justify-center rounded-md p-2 text-gray-400 hover:bg-gray-100 hover:text-gray-500"
                            >
                                <svg className="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                                    <path
                                        className={!showingNavigationDropdown ? 'inline-flex' : 'hidden'}
                                        strokeLinecap="round" strokeLinejoin="round" strokeWidth="2"
                                        d="M4 6h16M4 12h16M4 18h16"
                                    />
                                    <path
                                        className={showingNavigationDropdown ? 'inline-flex' : 'hidden'}
                                        strokeLinecap="round" strokeLinejoin="round" strokeWidth="2"
                                        d="M6 18L18 6M6 6l12 12"
                                    />
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>

                <div className={(showingNavigationDropdown ? 'block' : 'hidden') + ' sm:hidden'}>
                    <div className="space-y-1 pb-3 pt-2">
                        <ResponsiveNavLink href={route('dashboard')} active={route().current('dashboard')}>Игра</ResponsiveNavLink>
                        <ResponsiveNavLink href={route('achievements.index')} active={route().current('achievements.*')}>Достижения</ResponsiveNavLink>
                        <ResponsiveNavLink href={route('character.show')} active={route().current('character.*')}>Персонаж</ResponsiveNavLink>
                        <ResponsiveNavLink href={route('world.map')} active={route().current('world.*')}>Карта</ResponsiveNavLink>
                        <ResponsiveNavLink href={route('crafting.index')} active={route().current('crafting.*')}>Ремесло</ResponsiveNavLink>
                    </div>
                </div>
            </nav>

            {flash?.success && (
                <div className="bg-green-100 px-4 py-2 text-center text-sm text-green-800">{flash.success}</div>
            )}
            {flash?.warning && (
                <div className="bg-yellow-100 px-4 py-2 text-center text-sm text-yellow-800">{flash.warning}</div>
            )}
            {flash?.flavor && (
                <div className="bg-purple-50 px-4 py-2 text-center text-sm italic text-purple-800">{flash.flavor}</div>
            )}

            {header && (
                <header className="bg-white shadow">
                    <div className="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">{header}</div>
                </header>
            )}

            <main>{children}</main>
        </div>
    );
}
