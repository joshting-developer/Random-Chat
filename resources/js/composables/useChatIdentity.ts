import type { ComputedRef } from 'vue';
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import type { AppPageProps, ChatIdentity } from '@/types';

export type UseChatIdentityReturn = {
    userKey: ComputedRef<string | null>;
    chat: ComputedRef<ChatIdentity>;
};

export function useChatIdentity(): UseChatIdentityReturn {
    const page = usePage<AppPageProps>();

    const chat = computed(() => page.props.chat);
    const userKey = computed(() => page.props.chat?.userKey ?? null);

    return {
        userKey,
        chat,
    };
}
