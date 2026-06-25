import CharacterSection from '@/Components/Landing/CharacterSection';
import CombatSection from '@/Components/Landing/CombatSection';
import CtaSection from '@/Components/Landing/CtaSection';
import EquipmentSection from '@/Components/Landing/EquipmentSection';
import Footer from '@/Components/Landing/Footer';
import GameplaySection from '@/Components/Landing/GameplaySection';
import HeroSection from '@/Components/Landing/HeroSection';
import Navbar from '@/Components/Landing/Navbar';
import WorldSection from '@/Components/Landing/WorldSection';
import { Head } from '@inertiajs/react';

export default function Welcome({ auth }) {
    return (
        <div className="min-h-screen bg-background font-body text-foreground">
            <Head title="gigaIRL — Живи. Прокачивайся. Побеждай.">
                <meta
                    name="description"
                    content="Браузерная текстовая RPG, где твоя реальная жизнь питает приключения. Чем продуктивнее день — тем сильнее герой."
                />
            </Head>

            <main className="relative min-h-screen overflow-x-hidden">
                <Navbar auth={auth} />
                <HeroSection auth={auth} />
                <GameplaySection />
                <WorldSection />
                <CombatSection />
                <EquipmentSection />
                <CharacterSection />
                <CtaSection auth={auth} />
                <Footer />
            </main>
        </div>
    );
}
