<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';

const props = defineProps<{
    reason: 'missing' | 'forbidden';
    roomKey?: string | null;
}>();

const title =
    props.reason === 'forbidden' ? '無權限進入聊天室' : '聊天室不存在';
const description =
    props.reason === 'forbidden'
        ? '這個聊天室不是你的配對房間，請回到大廳重新配對。'
        : '聊天室已結束或不存在，請回到大廳重新配對。';
</script>

<template>
    <Head title="Chat Room Unavailable">
        <link rel="preconnect" href="https://fonts.googleapis.com" />
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
        <link
            href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;600;700&display=swap"
            rel="stylesheet"
        />
    </Head>

    <div
        class="relative min-h-screen overflow-hidden bg-gradient-to-b from-white via-slate-50 to-amber-50"
        style="font-family: 'Space Grotesk', ui-sans-serif, system-ui"
    >
        <div class="pointer-events-none absolute inset-0">
            <div
                class="absolute -left-24 top-16 h-64 w-64 rounded-full bg-amber-200/40 blur-3xl"
            />
            <div
                class="absolute right-0 top-1/3 h-64 w-64 rounded-full bg-sky-200/40 blur-3xl"
            />
        </div>

        <div class="relative mx-auto flex max-w-3xl flex-col gap-8 p-8">
            <header class="space-y-3">
                <p class="text-xs uppercase tracking-[0.35em] text-slate-500">
                    Random Chat
                </p>
                <h1 class="text-3xl font-bold text-slate-900 md:text-4xl">
                    {{ title }}
                </h1>
                <p class="text-sm text-slate-600">
                    {{ description }}
                </p>
                <p v-if="roomKey" class="text-xs text-slate-400">
                    房間號碼：<span class="font-semibold text-slate-600">{{ roomKey }}</span>
                </p>
            </header>

            <div
                class="flex flex-col items-start gap-3 rounded-3xl border border-slate-200/70 bg-white p-6 shadow-[0_20px_60px_-45px_rgba(15,23,42,0.35)]"
            >
                <p class="text-sm text-slate-600">
                    點擊下方按鈕返回大廳，再次開始配對。
                </p>
                <Button as-child class="h-11 px-6 text-base">
                    <Link href="/">回到大廳</Link>
                </Button>
            </div>
        </div>
    </div>
</template>
