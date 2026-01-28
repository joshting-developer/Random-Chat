<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import { Button } from '@/components/ui/button';
import { useChatIdentity } from '@/composables/useChatIdentity';

type ChatMessage = {
    id: number;
    sender: string;
    content: string;
    time: string;
};

type ChatHistoryRecord = {
    id: number;
    user_key: string;
    message: string;
    sent_at: string | null;
};

type ChatMessagePayload = {
    room_key: string;
    user_key: string;
    message: string;
    sent_at: string;
};

type JoinRoomResponse = {
    state: string;
    room_key: string;
    history: ChatHistoryRecord[];
};

type ChatPartnerLeftPayload = {
    room_key: string;
    user_key: string;
};

type EchoChannel = {
    listen: (event: string, callback: (payload: unknown) => void) => EchoChannel;
};

type EchoInstance = {
    private: (channel: string) => EchoChannel;
    leave: (channel: string) => void;
};

const { userKey } = useChatIdentity();
const isLeaving = ref(false);
const showPartnerLeft = ref(false);
const props = defineProps<{
    room_key?: string | null;
}>();

const messages = ref<ChatMessage[]>([]);

const input = ref('');
const displayUserKey = computed(() => userKey.value || '載入中...');
const csrfToken =
    typeof document !== 'undefined'
        ? document
            .querySelector('meta[name="csrf-token"]')
            ?.getAttribute('content')
        : null;
const roomKey = computed(() => props.room_key ?? null);

const getEcho = (): EchoInstance | null => {
    if (typeof window === 'undefined') {
        return null;
    }

    return (window as { Echo?: EchoInstance }).Echo ?? null;
};

const formatMessageTime = (sentAt?: string | null) => {
    if (!sentAt) {
        return '剛剛';
    }

    const parsed = new Date(sentAt);

    if (Number.isNaN(parsed.getTime())) {
        return '剛剛';
    }

    return parsed.toLocaleTimeString('zh-TW', {
        hour: '2-digit',
        minute: '2-digit',
    });
};

onMounted(async () => {
    if (!roomKey.value || !userKey.value) {
        return;
    }

    const response = await fetch(`/api/chat/rooms/${roomKey.value}/join`, {
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

    if (!response.ok) {
        router.visit('/');
        return;
    }

    const payload = (await response.json()) as JoinRoomResponse;
    const history = Array.isArray(payload.history)
        ? payload.history.map((record) => ({
            id: record.id,
            sender: record.user_key === userKey.value ? '你' : '對方',
            content: record.message,
            time: formatMessageTime(record.sent_at),
        }))
        : [];

    messages.value = [
        {
            id: 0,
            sender: '系統',
            content: '歡迎進入隨機聊天室，現在你可以開始聊天了！',
            time: '剛剛',
        },
        ...history,
    ];

    const echo = getEcho();

    if (!echo) {
        return;
    }

    const channel = echo.private(`chat-${roomKey.value}`);

    channel.listen('.chat.message', (payload) => {
        const messagePayload = payload as ChatMessagePayload | null;

        if (!messagePayload || messagePayload.room_key !== roomKey.value) {
            return;
        }

        messages.value = [
            ...messages.value,
            {
                id: Date.now(),
                sender: messagePayload.user_key === userKey.value ? '你' : '對方',
                content: messagePayload.message,
                time: formatMessageTime(messagePayload.sent_at),
            },
        ];
    });

    channel.listen('.chat.partner.left', (payload) => {
        const partnerPayload = payload as ChatPartnerLeftPayload | null;

        if (!partnerPayload || partnerPayload.room_key !== roomKey.value) {
            return;
        }

        if (isLeaving.value) {
            return;
        }

        showPartnerLeft.value = true;
    });
});

onBeforeUnmount(() => {
    const echo = getEcho();

    if (!echo || !roomKey.value) {
        return;
    }

    echo.leave(`chat-${roomKey.value}`);
});

const sendMessage = () => {
    const trimmed = input.value.trim();
    if (!trimmed) {
        return;
    }

    if (!roomKey.value || !userKey.value) {
        return;
    }

    fetch(`/api/chat/rooms/${roomKey.value}/messages`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            Accept: 'application/json',
            ...(csrfToken ? { 'X-CSRF-TOKEN': csrfToken } : {}),
        },
        credentials: 'same-origin',
        body: JSON.stringify({
            user_key: userKey.value,
            message: trimmed,
        }),
    }).then((response) => {
        if (response.ok) {
            input.value = '';
        }
    });
};

const leaveRoom = async () => {
    if (isLeaving.value) {
        return;
    }

    if (!roomKey.value || !userKey.value) {
        router.visit('/');
        return;
    }

    isLeaving.value = true;

    try {
        const response = await fetch(`/api/chat/rooms/${roomKey.value}/leave`, {
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

        if (response.ok) {
            router.visit('/');
            return;
        }
    } finally {
        isLeaving.value = false;
    }
};

const confirmPartnerLeft = () => {
    showPartnerLeft.value = false;
    leaveRoom();
};
</script>

<template>

    <Head title="Random Chat Room">
        <link rel="preconnect" href="https://fonts.googleapis.com" />
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
        <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;600;700&display=swap"
            rel="stylesheet" />
    </Head>

    <div class="relative min-h-screen overflow-hidden bg-gradient-to-b from-white via-slate-50 to-sky-50"
        style="font-family: 'Space Grotesk', ui-sans-serif, system-ui">
        <div v-if="isLeaving"
            class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/40 backdrop-blur-sm">
            <div
                class="flex items-center gap-3 rounded-full border border-white/30 bg-white/90 px-5 py-3 text-sm font-semibold text-slate-700 shadow-xl">
                <span class="h-5 w-5 animate-spin rounded-full border-2 border-slate-200 border-t-slate-600"
                    aria-hidden="true" />
                <span>正在離開聊天室...</span>
            </div>
        </div>
        <div v-if="showPartnerLeft"
            class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/30 backdrop-blur-sm">
            <div
                class="w-full max-w-sm rounded-3xl border border-white/40 bg-white/95 p-6 text-center shadow-[0_30px_90px_-60px_rgba(15,23,42,0.6)]">
                <p class="text-sm font-semibold text-slate-800">
                    對方已離開聊天室
                </p>
                <p class="mt-2 text-xs text-slate-500">
                    房間將在確認後離開。
                </p>
                <Button class="mt-4 h-10 w-full text-sm" @click="confirmPartnerLeft">
                    確認
                </Button>
            </div>
        </div>
        <div class="pointer-events-none absolute inset-0">
            <div class="absolute left-10 top-10 h-56 w-56 rounded-full bg-sky-200/40 blur-3xl" />
            <div class="absolute right-0 top-1/3 h-64 w-64 rounded-full bg-emerald-200/40 blur-3xl" />
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
                <p class="text-sm text-slate-600">
                    房間號碼：<span class="font-semibold text-slate-900">{{ roomKey }}</span>
                </p>
            </header>

            <section
                class="flex min-h-[520px] flex-col gap-6 rounded-3xl border border-slate-200/70 bg-white p-6 shadow-[0_30px_90px_-60px_rgba(15,23,42,0.45)]">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <h2 class="text-lg font-semibold text-slate-900">
                        聊天記錄
                    </h2>
                    <div class="flex items-center gap-3">
                        <span class="rounded-full bg-slate-100 px-3 py-1 text-xs text-slate-500">
                            即時更新
                        </span>
                        <Button variant="outline"
                            class="h-9 border-slate-200 px-4 text-sm text-slate-600 hover:text-slate-800"
                            :disabled="isLeaving" @click="leaveRoom">
                            離開聊天
                        </Button>
                    </div>
                </div>

                <div
                    class="flex h-[360px] flex-col gap-4 overflow-y-auto rounded-2xl border border-slate-100 bg-slate-50/40 p-4">
                    <div v-for="message in messages" :key="message.id" class="rounded-2xl bg-white p-4 shadow-sm">
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
                    @submit.prevent="sendMessage">
                    <textarea v-model="input" rows="2" placeholder="輸入訊息，按 Enter 送出"
                        class="min-h-[44px] flex-1 resize-none rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-800 placeholder:text-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-900/10" />
                    <Button class="h-11 px-6 text-base">
                        送出
                    </Button>
                </form>
            </section>
        </div>
    </div>
</template>
