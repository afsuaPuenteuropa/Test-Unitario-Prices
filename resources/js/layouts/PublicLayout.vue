<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import { Badge } from '@/components/ui/badge';

const page = usePage();
const user = computed(() => page.props.auth.user);
</script>

<template>
    <div class="min-h-screen bg-background text-foreground">
        <header
            class="sticky top-0 z-50 border-b border-sidebar-border/70 bg-background/85 backdrop-blur"
        >
            <div
                class="mx-auto flex h-16 w-full max-w-6xl items-center justify-between gap-4 px-4 sm:px-6"
            >
                <Link href="/" class="flex items-center gap-2 font-semibold">
                    <AppLogoIcon class="size-6" />
                    <span>Laravel</span>
                    <Badge variant="secondary" class="hidden sm:inline-flex">
                        Precios
                    </Badge>
                </Link>

                <nav class="flex items-center gap-2 text-sm">
                    <Link
                        href="/prices"
                        class="rounded-md px-3 py-2 text-muted-foreground transition-colors hover:bg-accent hover:text-accent-foreground"
                    >
                        Calculadora
                    </Link>
                    <Link
                        v-if="user"
                        href="/dashboard"
                        class="rounded-md px-3 py-2 text-muted-foreground transition-colors hover:bg-accent hover:text-accent-foreground"
                    >
                        Dashboard
                    </Link>
                    <Link
                        v-else
                        href="/login"
                        class="rounded-md border border-input px-3 py-2 text-sm font-medium shadow-xs transition-colors hover:bg-accent hover:text-accent-foreground"
                    >
                        Iniciar sesión
                    </Link>
                </nav>
            </div>
        </header>

        <main class="mx-auto w-full max-w-6xl px-4 py-10 sm:px-6 lg:py-14">
            <slot />
        </main>
    </div>
</template>
