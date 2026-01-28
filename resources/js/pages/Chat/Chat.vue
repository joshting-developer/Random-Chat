<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import { Button } from '@/components/ui/button';
import { useChatIdentity } from '@/composables/useChatIdentity';

type ChatMessage = {
    id: number;
    sender: string;
    content: string;
    time: string;
};

const { userKey } = useChatIdentity();

const messages = ref<ChatMessage[]>([
    {
        id: 1,
        sender: '系統',
        content: '歡迎進入隨機聊天室，現在你可以開始聊天了！',
        time: '剛剛',
    },
]);

const input = ref('');
const displayUserKey = computed(() => userKey.value || '載入中...');

const sendMessage = () => {
    const trimmed = input.value.trim();
    if (!trimmed) {
        return;
    }

    messages.value = [
        ...messages.value,
        {
            id: Date.now(),
            sender: displayUserKey.value,
            content: trimmed,
            time: '現在',
        },
    ];

    input.value = '';
};
</script>

<template>
    <Head title="Random Chat Room">
        <link rel="preconnect" href="https://fonts.googleapis.com" />
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
        <link
            href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;600;700&display=swap"
            rel="stylesheet"
        />
    </Head>

    <div
        class="relative min-h-screen overflow-hidden bg-gradient-to-b from-white via-slate-50 to-sky-50"
        style="font-family: 'Space Grotesk', ui-sans-serif, system-ui"
    >
        <div class="pointer-events-none absolute inset-0">
            <div
                class="absolute left-10 top-10 h-56 w-56 rounded-full bg-sky-200/40 blur-3xl"
            />
            <div
                class="absolute right-0 top-1/3 h-64 w-64 rounded-full bg-emerald-200/40 blur-3xl"
            />
        </div>

        <div class="relative mx-auto flex max-w-5xl flex-col gap-8 p-8">
            <header class="space-y-3">
                <p class="text-xs uppercase tracking-[0.35em] text-slate-500">
                    Random Chat Room
                </p>
                <h1 class="text-3xl font-bold text-slate-900 md:text-4xl">
                    讓對話保持流動
                </h1>
                <p class="text-sm text-slate-600">
                    你的識別碼：<span class="font-semibold text-slate-900">{{ displayUserKey }}</span>
                </p>
            </header>

            <section
                class="flex min-h-[520px] flex-col gap-6 rounded-3xl border border-slate-200/70 bg-white p-6 shadow-[0_30px_90px_-60px_rgba(15,23,42,0.45)]"
            >
                <div class="flex items-center justify-between">
                    <h2 class="text-lg font-semibold text-slate-900">
                        聊天記錄
                    </h2>
                    <span
                        class="rounded-full bg-slate-100 px-3 py-1 text-xs text-slate-500"
                    >
                        即時更新
                    </span>
                </div>

                <div
                    class="flex h-[360px] flex-col gap-4 overflow-y-auto rounded-2xl border border-slate-100 bg-slate-50/40 p-4"
                >
                    <div
                        v-for="message in messages"
                        :key="message.id"
                        class="rounded-2xl bg-white p-4 shadow-sm"
                    >
                        <div class="flex items-center justify-between">
                            <p class="text-xs font-semibold text-slate-500">
                                {{ message.sender }}
                            </p>
                            <p class="text-xs text-slate-400">
                                {{ message.time }}
                            </p>
                        </div>
                        <p class="mt-2 text-sm text-slate-800">
                            {{ message.content }}
                        </p>
                    </div>
                </div>

                <form
                    class="flex flex-col gap-3 rounded-2xl border border-slate-200/70 bg-white p-4 shadow-sm md:flex-row"
                    @submit.prevent="sendMessage"
                >
                    <textarea
                        v-model="input"
                        rows="2"
                        placeholder="輸入訊息，按 Enter 送出"
                        class="min-h-[44px] flex-1 resize-none rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-800 placeholder:text-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-900/10"
                    />
                    <Button class="h-11 px-6 text-base">
                        送出
                    </Button>
                </form>
            </section>
        </div>
    </div>
</template>
