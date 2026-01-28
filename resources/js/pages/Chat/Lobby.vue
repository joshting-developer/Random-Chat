<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import { Button } from '@/components/ui/button';
import { useChatIdentity } from '@/composables/useChatIdentity';
import { useChatSocket } from '@/composables/useChatSocket';

const { userKey } = useChatIdentity();

const displayUserKey = computed(() => userKey.value || '產生中...');
const matchState = ref<'idle' | 'queue'>('idle');
const csrfToken =
    typeof document !== 'undefined'
        ? document
              .querySelector('meta[name="csrf-token"]')
              ?.getAttribute('content')
        : null;

useChatSocket({
    onMatchQueued: () => {
        matchState.value = 'queue';
    },
    onMatchFound: (payload) => {
        if (payload && typeof payload === 'object' && 'roomKey' in payload) {
            router.visit(`/chat/rooms/${payload.roomKey}`);
        }
    },
});

const startMatching = async () => {
    if (!userKey.value) {
        return;
    }

    const response = await fetch('/api/chat/match/start', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            Accept: 'application/json',
            ...(csrfToken ? { 'X-CSRF-TOKEN': csrfToken } : {}),
        },
        credentials: 'same-origin',
        body: JSON.stringify({
            user_key: userKey.value,
        }),
    });
};

const cancelMatching = async () => {
    if (!userKey.value) {
        return;
    }

    const response = await fetch('/api/chat/match/cancel', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            Accept: 'application/json',
            ...(csrfToken ? { 'X-CSRF-TOKEN': csrfToken } : {}),
        },
        credentials: 'same-origin',
        body: JSON.stringify({
            user_key: userKey.value,
        }),
    });
};
</script>

<template>

    <Head title="Random Chat Lobby">
        <link rel="preconnect" href="https://fonts.googleapis.com" />
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
        <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;600;700&display=swap"
            rel="stylesheet" />
    </Head>

    <div class="relative min-h-screen overflow-hidden bg-gradient-to-b from-slate-50 via-white to-amber-50"
        style="font-family: 'Space Grotesk', ui-sans-serif, system-ui">
        <div class="pointer-events-none absolute inset-0">
            <div class="absolute -right-24 top-10 h-64 w-64 rounded-full bg-amber-200/40 blur-3xl" />
            <div class="absolute left-10 top-1/2 h-72 w-72 -translate-y-1/2 rounded-full bg-sky-200/50 blur-3xl" />
            <div class="absolute bottom-10 right-1/4 h-56 w-56 rounded-full bg-rose-200/40 blur-3xl" />
        </div>

        <div class="relative mx-auto flex max-w-5xl flex-col gap-10 p-8">
            <header class="mt-6 space-y-4">
                <p class="text-xs uppercase tracking-[0.4em] text-slate-500">
                    Random Chat
                </p>
                <h1 class="text-4xl font-bold text-slate-900 md:text-5xl">
                    即時隨機配對，讓對話自然發生
                </h1>
                <p class="max-w-2xl text-base text-slate-600 md:text-lg">
                    你會被配對到另一位線上使用者，進入一個私密房間聊天。
                    這個流程不需要登入，系統只會用 session
                    生成的專屬識別碼來維持你的身份。
                </p>
            </header>

            <section class="grid gap-6 md:grid-cols-[1.3fr_1fr]">
                <div
                    class="relative overflow-hidden rounded-3xl border border-white/60 bg-white/80 p-8 shadow-[0_24px_70px_-40px_rgba(15,23,42,0.35)] backdrop-blur">
                    <div class="absolute right-6 top-6 h-16 w-16 rounded-2xl bg-amber-100 text-amber-600 shadow-lg">
                        <div class="flex h-full items-center justify-center text-xl font-bold">
                            ⊙
                        </div>
                    </div>
                    <h2 class="text-2xl font-semibold text-slate-900">
                        你的專屬 UUID
                    </h2>
                    <p class="mt-2 text-sm text-slate-500">
                        後端已為你建立身份，請將此 UUID 視為匿名暱稱。
                    </p>
                    <div class="mt-6 rounded-2xl border border-slate-200/70 bg-white px-5 py-4 shadow-inner">
                        <p class="text-xs uppercase tracking-widest text-slate-400">
                            userKey
                        </p>
                        <p class="mt-2 break-all text-base font-semibold text-slate-900">
                            {{ displayUserKey }}
                        </p>
                    </div>
                    <div class="mt-6 flex flex-wrap gap-3">
                        <Button v-if="matchState !== 'queue'" class="h-11 px-6 text-base" :disabled="!userKey"
                            @click="startMatching">
                            開始配對
                        </Button>
                        <Button v-else variant="outline"
                            class="h-11 border-slate-200 px-6 text-base text-slate-600 hover:text-slate-800"
                            :disabled="!userKey" @click="cancelMatching">
                            取消配對
                        </Button>
                    </div>
                </div>

                <div class="flex flex-col gap-6">
                    <div
                        class="rounded-3xl border border-slate-200/70 bg-white p-6 shadow-[0_20px_60px_-45px_rgba(15,23,42,0.4)]">
                        <h3 class="text-lg font-semibold text-slate-900">
                            配對流程
                        </h3>
                        <ol class="mt-4 space-y-3 text-sm text-slate-600">
                            <li class="flex gap-3">
                                <span
                                    class="mt-0.5 h-6 w-6 rounded-full bg-slate-900 text-center text-xs font-semibold leading-6 text-white">
                                    1
                                </span>
                                <span>加入等待佇列，系統找尋下一位可配對的人。</span>
                            </li>
                            <li class="flex gap-3">
                                <span
                                    class="mt-0.5 h-6 w-6 rounded-full bg-slate-900 text-center text-xs font-semibold leading-6 text-white">
                                    2
                                </span>
                                <span>配對成功後建立私密房間，並傳送通知。</span>
                            </li>
                            <li class="flex gap-3">
                                <span
                                    class="mt-0.5 h-6 w-6 rounded-full bg-slate-900 text-center text-xs font-semibold leading-6 text-white">
                                    3
                                </span>
                                <span>房間內可即時聊天，離線時會通知另一方。</span>
                            </li>
                        </ol>
                    </div>

                    <div
                        class="rounded-3xl border border-amber-200/70 bg-gradient-to-br from-amber-50 to-white p-6 shadow-[0_18px_50px_-40px_rgba(245,158,11,0.45)]">
                        <h3 class="text-lg font-semibold text-amber-900">
                            建議與提醒
                        </h3>
                        <p class="mt-3 text-sm text-amber-800/80">
                            請避免分享個人敏感資訊，隨機聊天室更適合用來輕鬆交流、
                            快速發現新的對話主題。
                        </p>
                    </div>
                </div>
            </section>
        </div>
    </div>
</template>
