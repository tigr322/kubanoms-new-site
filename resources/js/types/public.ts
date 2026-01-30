export type MenuItem = {
    id: number;
    title: string;
    url: string | null;
    children: MenuItem[];
};

export type NewsListItem = {
    id: number;
    title: string;
    url: string;
    date: string | null;
    image?: string | null;
    path?: string | null;
};
