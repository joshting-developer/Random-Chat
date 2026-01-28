export * from './auth';
export * from './navigation';
export * from './ui';

import type { Auth } from './auth';

export type ChatIdentity = {
    userKey: string | null;
};

export type AppPageProps<
    T extends Record<string, unknown> = Record<string, unknown>,
> = T & {
    name: string;
    auth: Auth;
    chat: ChatIdentity;
    sidebarOpen: boolean;
    [key: string]: unknown;
};
