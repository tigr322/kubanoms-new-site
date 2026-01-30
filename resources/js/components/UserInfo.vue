<script setup lang="ts">
import { computed } from 'vue';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { useInitials } from '@/composables/useInitials';
import type { User } from '@/types';

type Props = {
    user?: User | null;
    showEmail?: boolean;
};

const props = withDefaults(defineProps<Props>(), {
    showEmail: false,
});

const { getInitials } = useInitials();

// Compute whether we should show the avatar image
const showAvatar = computed(
    () => props.user?.avatar && props.user.avatar !== '',
);

// Compute user name safely
const userName = computed(() => props.user?.name || 'Гость');
const userEmail = computed(() => props.user?.email || '');
</script>

<template>
    <div v-if="user" class="flex items-center space-x-3">
        <Avatar class="h-8 w-8 overflow-hidden rounded-lg">
            <AvatarImage v-if="showAvatar" :src="user.avatar!" :alt="user.name" />
            <AvatarFallback class="rounded-lg text-black dark:text-white">
                {{ getInitials(userName) }}
            </AvatarFallback>
        </Avatar>

        <div class="grid flex-1 text-left text-sm leading-tight">
            <span class="truncate font-medium">{{ userName }}</span>
            <span v-if="showEmail && userEmail" class="truncate text-xs text-muted-foreground">{{
                userEmail
            }}</span>
        </div>
    </div>
</template>
