<script setup>
import { Head } from '@inertiajs/vue3';

defineProps({
    canLogin: {
        type: Boolean,
    },
    canRegister: {
        type: Boolean,
    },
    laravelVersion: {
        type: String,
        required: true,
    },
    phpVersion: {
        type: String,
        required: true,
    },
});

function handleImageError() {
    document.getElementById('screenshot-container')?.classList.add('!hidden');
    document.getElementById('docs-card')?.classList.add('!row-span-1');
    document.getElementById('docs-card-content')?.classList.add('!flex-row');
    document.getElementById('background')?.classList.add('!hidden');
}
</script>

<template>
    <Head title="Welcome" />
    <div class="bg-gray-50 text-black/50 dark:bg-black dark:text-white/50">
        <img
            id="background"
            class="absolute -left-20 top-0 max-w-[877px]"
            src="https://laravel.com/assets/img/welcome/background.svg"
            alt="Background decoration"
            @error="handleImageError"
        />
        <div class="relative min-h-screen flex flex-col items-center justify-center selection:bg-[#FF2D20] selection:text-white">
            <div class="relative w-full max-w-2xl px-6 lg:max-w-7xl">
                <header class="grid grid-cols-2 items-center gap-2 py-10 lg:grid-cols-3">
                    <div class="flex lg:justify-center lg:col-start-2">
                        <svg class="h-12 w-auto text-white lg:h-16" viewBox="0 0 62 65" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M61.8548 14.6253C61.8778 14.7102 61.8895 14.7978 61.8895 14.8858V28.8885C61.8895 29.2075 61.7553 29.5115 61.5199 29.7262L42.2292 48.7263C41.8346 49.1156 41.2018 49.1127 40.8101 48.7202C40.4184 48.3277 40.4155 47.6949 40.8062 47.3023L59.2593 28.9922V15.7826C59.2593 15.4883 59.1031 15.2164 58.8482 15.0709C58.5933 14.9254 58.2787 14.9312 58.0298 15.0874L30.4744 30.7505L3.43516 15.0874C3.18628 14.9312 2.87172 14.9254 2.61681 15.0709C2.3619 15.2164 2.20573 15.4883 2.20573 15.7826V44.6923L20.6846 26.5622C21.0792 26.1729 21.712 26.1758 22.1037 26.5683C22.4954 26.9608 22.4983 27.5936 22.1076 27.9861L1.70845 48.1119C1.65857 48.1611 1.60329 48.2044 1.54376 48.2413C1.48422 48.2782 1.42077 48.3084 1.35427 48.3312C1.28777 48.354 1.21858 48.3692 1.14833 48.3764C1.07807 48.3837 1.00715 48.383 0.937388 48.3738C0.867624 48.3646 0.798948 48.3471 0.732975 48.3216C0.667002 48.2961 0.60418 48.2627 0.545767 48.2222C0.487355 48.1817 0.433539 48.1345 0.38527 48.0815C0.337001 48.0286 0.294364 47.9703 0.258219 47.9075C0.222074 47.8448 0.192662 47.7781 0.170435 47.7086C0.148207 47.6391 0.133347 47.5672 0.126113 47.4942C0.118879 47.4213 0.119317 47.3478 0.127403 47.2749V14.8858C0.127403 14.5087 0.363748 14.1702 0.730777 14.0342C1.09781 13.8981 1.5108 13.9888 1.79159 14.2676L30.4602 30.382L58.6101 14.2676C58.7168 14.1822 58.8385 14.119 58.9682 14.0811C59.0978 14.0432 59.2333 14.0314 59.367 14.046C59.5006 14.0607 59.6302 14.1013 59.7486 14.1657C59.867 14.2301 59.9721 14.3167 60.0585 14.4208C60.1449 14.5249 60.211 14.6444 60.2532 14.7734C60.2954 14.9023 60.3129 15.0383 60.3046 15.1741C60.2964 15.3099 60.2626 15.4432 60.2051 15.5666L61.8548 14.6253Z" fill="currentColor"/>
                        </svg>
                    </div>
                    <nav v-if="canLogin" class="-mx-3 flex flex-1 justify-end">
                        <Link
                            v-if="$page.props.auth.user"
                            href="/dashboard"
                            class="rounded-md px-3 py-2 text-black ring-1 ring-transparent transition hover:text-black/70 focus:outline-none focus-visible:ring-[#FF2D20] dark:text-white dark:hover:text-white/80 dark:focus-visible:ring-white"
                        >
                            Dashboard
                        </Link>

                        <template v-else>
                            <Link
                                href="/login"
                                class="rounded-md px-3 py-2 text-black ring-1 ring-transparent transition hover:text-black/70 focus:outline-none focus-visible:ring-[#FF2D20] dark:text-white dark:hover:text-white/80 dark:focus-visible:ring-white"
                            >
                                Log in
                            </Link>

                            <Link
                                v-if="canRegister"
                                href="/register"
                                class="rounded-md px-3 py-2 text-black ring-1 ring-transparent transition hover:text-black/70 focus:outline-none focus-visible:ring-[#FF2D20] dark:text-white dark:hover:text-white/80 dark:focus-visible:ring-white"
                            >
                                Register
                            </Link>
                        </template>
                    </nav>
                </header>

                <main class="mt-6">
                    <div class="grid gap-6 lg:grid-cols-2 lg:gap-8">
                        <a
                            id="docs-card"
                            href="https://laravel.com/docs"
                            class="flex flex-col items-start gap-6 overflow-hidden rounded-lg bg-white p-6 shadow-[0px_14px_34px_0px_rgba(0,0,0,0.08)] ring-1 ring-white/[0.05] transition duration-300 hover:text-black/70 hover:ring-black/20 focus:outline-none focus-visible:ring-[#FF2D20] md:row-span-3 lg:p-10 lg:pb-10 dark:bg-zinc-900 dark:ring-zinc-800 dark:hover:text-white/70 dark:hover:ring-zinc-700 dark:focus-visible:ring-[#FF2D20]"
                        >
                            <div id="screenshot-container" class="relative flex w-full items-center justify-center overflow-hidden rounded-lg border border-neutral-200 bg-white dark:border-neutral-700 dark:bg-neutral-800">
                                <img
                                    src="https://laravel.com/assets/img/welcome/docs-light.svg"
                                    alt="Laravel documentation screenshot"
                                    class="block max-w-full dark:hidden"
                                    @error="handleImageError"
                                />
                                <img
                                    src="https://laravel.com/assets/img/welcome/docs-dark.svg"
                                    alt="Laravel documentation screenshot"
                                    class="hidden max-w-full dark:block"
                                    @error="handleImageError"
                                />
                            </div>

                            <div id="docs-card-content" class="flex flex-col items-start text-sm font-semibold lg:flex-row lg:items-center">
                                <span class="text-2xl text-neutral-900 dark:text-white">Laravel {{ laravelVersion }} (PHP {{ phpVersion }})</span>
                                <span class="ml-1.5 rounded-md bg-[#FF2D20]/10 px-2 py-0.5 text-xs text-[#FF2D20]">DOCS</span>
                            </div>

                            <p class="text-sm text-neutral-600 dark:text-neutral-400">
                                Everything you need to know to build your first Laravel application. From installation to deployment, we've got you covered.
                            </p>
                        </a>

                        <a
                            href="https://laracasts.com"
                            class="flex items-start gap-6 rounded-lg bg-white p-6 shadow-[0px_14px_34px_0px_rgba(0,0,0,0.08)] ring-1 ring-white/[0.05] transition duration-300 hover:text-black/70 hover:ring-black/20 focus:outline-none focus-visible:ring-[#FF2D20] lg:col-span-2 lg:p-10 dark:bg-zinc-900 dark:ring-zinc-800 dark:hover:text-white/70 dark:hover:ring-zinc-700 dark:focus-visible:ring-[#FF2D20]"
                        >
                            <div class="flex size-12 shrink-0 items-center justify-center rounded-full bg-[#FF2D20]/10 sm:size-16">
                                <svg class="size-5 sm:size-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <path fill="#FF2D20" d="M8.504 4.501l3.495 6.002 6.002-3.495-3.495 6.002 6.002 3.495-6.002 3.495 3.495 6.002-6.002-3.495-3.495 6.002-3.495-6.002L0 17.505l6.002-3.495L2.507 8.008l5.997 3.495z"/>
                                </svg>
                            </div>

                            <div>
                                <h2 class="text-base font-semibold text-neutral-900 dark:text-white">Laracasts</h2>
                                <p class="mt-2 text-sm text-neutral-600 dark:text-neutral-400">
                                    Watch video tutorials from the creators of Laravel. New episodes every week, covering everything from basics to advanced topics.
                                </p>
                            </div>
                        </a>

                        <div class="flex items-start gap-6 rounded-lg bg-white p-6 shadow-[0px_14px_34px_0px_rgba(0,0,0,0.08)] ring-1 ring-white/[0.05] transition duration-300 hover:ring-black/20 focus:outline-none focus-visible:ring-[#FF2D20] lg:col-span-2 lg:p-10 dark:bg-zinc-900 dark:ring-zinc-800 dark:hover:ring-zinc-700 dark:focus-visible:ring-[#FF2D20]">
                            <div class="flex size-12 shrink-0 items-center justify-center rounded-full bg-[#FF2D20]/10 sm:size-16">
                                <svg class="size-5 sm:size-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <g fill="#FF2D20">
                                        <path d="M23.2 10.949c-.874-.922-1.81-1.766-2.78-2.534-1.59-1.262-3.324-2.265-5.15-3.013-1.84-.756-3.776-1.213-5.764-1.213-2.18 0-4.247.42-6.138 1.214-1.913.802-3.524 1.86-4.962 3.146-1.412 1.262-2.527 2.713-3.282 4.279-.785 1.61-1.122 3.288-1.122 5.018 0 1.788.39 3.448 1.1 4.97.731 1.557 1.768 2.95 3.026 4.121 1.295 1.213 2.778 2.149 4.585 2.857 1.825.717 3.74 1.087 5.768 1.087 1.43 0 2.78-.224 4.112-.6 1.342-.385 2.516-.92 3.6-1.616.137-.09.226-.235.226-.397v-2.052c0-.286-.243-.518-.532-.518-.103 0-.205.03-.295.092a10.93 10.93 0 0 1-3.275 1.448 10.418 10.418 0 0 1-3.286.515c-1.43 0-2.81-.275-4.044-.806-1.21-.523-2.286-1.252-3.144-2.123-.834-.847-1.503-1.85-1.943-2.92-.434-1.054-.668-2.2-.668-3.342 0-1.156.235-2.298.668-3.342.456-1.094 1.135-2.106 1.974-2.97.86-.882 1.918-1.604 3.115-2.106 1.21-.51 2.51-.776 3.86-.776 1.32 0 2.55.255 3.71.74 1.14.477 2.18 1.144 3.06 1.93.092.082.205.127.32.127.137 0 .275-.052.378-.155l1.825-1.825c.137-.137.21-.32.21-.51 0-.196-.082-.378-.234-.503z"/>
                                    </g>
                                </svg>
                            </div>

                            <div>
                                <h2 class="text-base font-semibold text-neutral-900 dark:text-white">Vibrant Ecosystem</h2>
                                <p class="mt-2 text-sm text-neutral-600 dark:text-neutral-400">
                                    Laravel's robust ecosystem includes a wide variety of tools and libraries. From Forge to Vapor to Nova, there's something for every use case.
                                </p>
                            </div>
                        </div>
                    </div>
                </main>

                <footer class="py-10 text-center text-sm text-neutral-500 dark:text-neutral-400">
                    Laravel v{{ laravelVersion }} (PHP v{{ phpVersion }})
                </footer>
            </div>
        </div>
    </div>
</template>
