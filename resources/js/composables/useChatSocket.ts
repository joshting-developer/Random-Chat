import type { ComputedRef } from 'vue';
import { computed, onBeforeUnmount, watch } from 'vue';
import { useChatIdentity } from '@/composables/useChatIdentity';

type EchoChannel = {
    listen: (event: string, callback: (payload: unknown) => void) => EchoChannel;
    stopListening?: (event: string) => EchoChannel;
};

type EchoInstance = {
    private: (channel: string) => EchoChannel;
    leave: (channel: string) => void;
};

export type ChatSocketHandlers = {
    onMatchQueued?: (payload: unknown) => void;
    onMatchFound?: (payload: unknown) => void;
    onPartnerLeft?: (payload: unknown) => void;
};

export type UseChatSocketReturn = {
    channelName: ComputedRef<string | null>;
    connect: () => EchoChannel | null;
};

const getEcho = (): EchoInstance | null => {
    if (typeof window === 'undefined') {
        return null;
    }

    return (window as { Echo?: EchoInstance }).Echo ?? null;
};

export function useChatSocket(handlers: ChatSocketHandlers = {}): UseChatSocketReturn {
    const { userKey } = useChatIdentity();
    const channelName = computed(() =>
        userKey.value ? `user.${userKey.value}` : null,
    );

    const connect = () => {
        if (!channelName.value) {
            return null;
        }

        const echo = getEcho();

        if (!echo) {
            return null;
        }

        const channel = echo.private(channelName.value);

        console.log(channel);
        console.log(123);

        if (handlers.onMatchQueued) {
            channel.listen('.chat.match.queued', handlers.onMatchQueued);
        }

        if (handlers.onMatchFound) {
            channel.listen('.chat.match.found', handlers.onMatchFound);
        }

        if (handlers.onPartnerLeft) {
            channel.listen('.chat.partner.left', handlers.onPartnerLeft);
        }

        return channel;
    };

    watch(
        () => channelName.value,
        (value) => {
            if (value) connect();
        },
        { immediate: true }
    );

    onBeforeUnmount(() => {
        const echo = getEcho();
        if (echo && channelName.value) echo.leave(channelName.value);
    });

    return {
        channelName,
        connect,
    };
}
