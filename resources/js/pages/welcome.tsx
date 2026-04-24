import { Head, Link, usePage } from '@inertiajs/react';
import { dashboard, login, register } from '@/routes';
import AppLogoIcon from '@/components/app-logo-icon';

export default function Welcome({
    canRegister = true,
}: {
    canRegister?: boolean;
}) {
    const { auth } = usePage().props;
    const roomImage = '/storage/rooms/8V5DgqQhQFocVOdDouFLUE4t0SQv2FMKIK9Bmhc2.jpg';

    return (
        <>
            <Head title="Welcome" />
            <div className="landing-shell flex min-h-screen flex-col bg-white text-gray-800 dark:bg-neutral-950 dark:text-gray-200">
                {/* Header */}
                <header className="w-full border-b border-orange-100/80 bg-white/90 px-6 py-4 backdrop-blur-sm dark:border-neutral-800 dark:bg-neutral-900/90">
                    <div className="mx-auto flex max-w-6xl items-center justify-between">
                        <Link href="/" className="flex items-center gap-2">
                            <div className="flex h-10 w-10 items-center justify-center rounded-full bg-orange-500">
                                <AppLogoIcon className="size-6 fill-current text-white" />
                            </div>
                            <span className="text-xl font-bold text-gray-900 dark:text-gray-100">
                                G&D Boarding House
                            </span>
                        </Link>
                        <nav className="flex items-center gap-3">
                            {auth.user ? (
                                <Link
                                    href={dashboard()}
                                    className="rounded-lg bg-orange-500 px-5 py-2 text-sm font-medium text-white transition hover:bg-orange-600"
                                >
                                    Dashboard
                                </Link>
                            ) : (
                                <>
                                    <Link
                                        href={login()}
                                        className="rounded-lg px-5 py-2 text-sm font-medium text-gray-700 transition hover:text-orange-600 dark:text-gray-300 dark:hover:text-orange-400"
                                    >
                                        Log in
                                    </Link>
                                    {canRegister && (
                                        <Link
                                            href={register()}
                                            className="rounded-lg bg-orange-500 px-5 py-2 text-sm font-medium text-white transition hover:bg-orange-600"
                                        >
                                            Register
                                        </Link>
                                    )}
                                </>
                            )}
                        </nav>
                    </div>
                </header>

                {/* Hero Section */}
                <section className="landing-hero bg-gradient-to-br from-orange-50 to-white px-6 py-20 dark:from-neutral-900 dark:to-neutral-950 lg:py-32">
                    <div className="mx-auto max-w-6xl text-center">
                        <h1 className="landing-rise text-4xl font-bold tracking-tight text-gray-900 dark:text-gray-100 sm:text-5xl lg:text-6xl">
                            Find Your Perfect
                            <span className="text-orange-500"> Boarding House</span>
                        </h1>
                        <p className="landing-rise mx-auto mt-6 max-w-2xl text-lg text-gray-600 [animation-delay:120ms] dark:text-gray-300">
                            Comfortable, affordable, and conveniently located rooms for students and professionals.
                            Your home away from home starts here.
                        </p>
                        <div className="landing-rise mt-10 flex items-center justify-center gap-4 [animation-delay:220ms]">
                            <Link
                                href={canRegister ? register() : login()}
                                className="rounded-lg bg-orange-500 px-8 py-3 text-base font-semibold text-white shadow-md transition hover:-translate-y-0.5 hover:bg-orange-600"
                            >
                                Get Started
                            </Link>
                            <a
                                href="#features"
                                className="rounded-lg border border-orange-300 px-8 py-3 text-base font-semibold text-orange-600 transition hover:bg-orange-50 dark:border-orange-700 dark:text-orange-400 dark:hover:bg-orange-950/40"
                            >
                                Learn More
                            </a>
                        </div>
                    </div>
                </section>

                {/* Features Section */}
                <section id="features" className="bg-white px-6 py-20 dark:bg-neutral-950">
                    <div className="mx-auto max-w-6xl">
                        <h2 className="text-center text-3xl font-bold text-gray-900 dark:text-gray-100">
                            Why Choose Us?
                        </h2>
                        <p className="mx-auto mt-4 max-w-xl text-center text-gray-500 dark:text-gray-400">
                            We provide everything you need for a comfortable stay.
                        </p>

                        <div className="mt-14 grid gap-8 sm:grid-cols-2 lg:grid-cols-3">
                            {/* Feature 1 */}
                            <div className="landing-card landing-card-delay-1 rounded-xl border border-orange-100 bg-orange-50 p-8 text-center dark:border-neutral-800 dark:bg-neutral-900">
                                <div className="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-orange-500 text-white">
                                    <svg className="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2}>
                                        <path strokeLinecap="round" strokeLinejoin="round" d="M3 10.5L12 3l9 7.5V21a1 1 0 01-1 1H4a1 1 0 01-1-1V10.5z" />
                                        <path strokeLinecap="round" strokeLinejoin="round" d="M9 21V13h6v8" />
                                    </svg>
                                </div>
                                <h3 className="text-lg font-semibold text-gray-900 dark:text-gray-100">Comfortable Rooms</h3>
                                <p className="mt-2 text-sm text-gray-600 dark:text-gray-300">
                                    Well-furnished rooms designed for rest and productivity, with ample space and natural light.
                                </p>
                            </div>

                            {/* Feature 2 */}
                            <div className="landing-card landing-card-delay-2 rounded-xl border border-orange-100 bg-orange-50 p-8 text-center dark:border-neutral-800 dark:bg-neutral-900">
                                <div className="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-orange-500 text-white">
                                    <svg className="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2}>
                                        <path strokeLinecap="round" strokeLinejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1" />
                                    </svg>
                                </div>
                                <h3 className="text-lg font-semibold text-gray-900 dark:text-gray-100">Affordable Rates</h3>
                                <p className="mt-2 text-sm text-gray-600 dark:text-gray-300">
                                    Budget-friendly pricing with flexible payment options that won't break the bank.
                                </p>
                            </div>

                            {/* Feature 3 */}
                            <div className="landing-card landing-card-delay-3 rounded-xl border border-orange-100 bg-orange-50 p-8 text-center dark:border-neutral-800 dark:bg-neutral-900">
                                <div className="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-orange-500 text-white">
                                    <svg className="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2}>
                                        <path strokeLinecap="round" strokeLinejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                        <path strokeLinecap="round" strokeLinejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                    </svg>
                                </div>
                                <h3 className="text-lg font-semibold text-gray-900 dark:text-gray-100">Prime Location</h3>
                                <p className="mt-2 text-sm text-gray-600 dark:text-gray-300">
                                    Strategically located near schools, offices, and public transportation for easy access.
                                </p>
                            </div>
                        </div>
                    </div>
                </section>

                {/* Rooms Preview Section */}
                <section className="bg-orange-50 px-6 py-20 dark:bg-neutral-900">
                    <div className="mx-auto max-w-6xl">
                        <h2 className="text-center text-3xl font-bold text-gray-900 dark:text-gray-100">
                            Available Rooms
                        </h2>
                        <p className="mx-auto mt-4 max-w-xl text-center text-gray-500 dark:text-gray-400">
                            Browse our selection of rooms tailored to your needs.
                        </p>

                        <div className="mt-14 grid gap-8 sm:grid-cols-2 lg:grid-cols-3">
                            {[
                                { name: 'Single Room', price: '₱3,000/mo', desc: 'Perfect for solo tenants who value privacy and quiet space.' },
                                { name: 'Shared Room', price: '₱2,000/mo', desc: 'A great option for those who enjoy company and want to save.' },
                                { name: 'Premium Room', price: '₱5,000/mo', desc: 'Spacious room with private bathroom and premium amenities.' },
                            ].map((room, index) => (
                                <div key={room.name} className={`landing-card overflow-hidden rounded-xl border border-orange-100 bg-white shadow-sm dark:border-neutral-800 dark:bg-neutral-950 ${index === 0 ? 'landing-card-delay-1' : index === 1 ? 'landing-card-delay-2' : 'landing-card-delay-3'}`}>
                                    <div className="relative h-48 overflow-hidden">
                                        <img
                                            src={roomImage}
                                            alt={`${room.name} preview`}
                                            className="h-full w-full object-cover transition duration-700 hover:scale-110"
                                            loading="lazy"
                                        />
                                        <div className="absolute inset-0 bg-gradient-to-t from-black/35 via-transparent to-transparent" />
                                    </div>
                                    <div className="p-6">
                                        <h3 className="text-lg font-semibold text-gray-900 dark:text-gray-100">{room.name}</h3>
                                        <p className="mt-1 text-2xl font-bold text-orange-500">{room.price}</p>
                                        <p className="mt-2 text-sm text-gray-600 dark:text-gray-300">{room.desc}</p>
                                    </div>
                                </div>
                            ))}
                        </div>
                    </div>
                </section>

                {/* Footer */}
                <footer className="border-t border-orange-100 bg-white px-6 py-10 dark:border-neutral-800 dark:bg-neutral-900">
                    <div className="mx-auto flex max-w-6xl flex-col items-center justify-between gap-4 sm:flex-row">
                        <div className="flex items-center gap-2">
                            <div className="flex h-8 w-8 items-center justify-center rounded-full bg-orange-500">
                                <AppLogoIcon className="size-4 fill-current text-white" />
                            </div>
                            <span className="font-semibold text-gray-900 dark:text-gray-100"> G&D Boarding House</span>
                        </div>
                        <p className="text-sm text-gray-500 dark:text-gray-400">
                            &copy; {new Date().getFullYear()} G&D Boarding House. All rights reserved.
                        </p>
                    </div>
                </footer>
            </div>
        </>
    );
}
