import { reactive } from 'vue';
import { Codebooks } from './Codebooks.js';
import { usePage } from '@inertiajs/vue3'

export const useCodebooks = () => {
    return {
        updateSortOrder,
        getSortOrderBy
    }
};

const updateSortOrder = async ({ order, codebook }) => {
    codebook.code_order = order
};

const getSortOrderBy = (codebook) => {
    const order = codebook.code_order ?? [];

    if (!order.length) {
        return () => 0;
    }

    // transform to a read-optimized version of the order
    const map = {};
    const parseOrder = (list) => {
        list.forEach((item, i) => {
            item.index = i;
            map[item.id] = item;
            if (item.children?.length) {
                parseOrder(item.children);
            }
        });
    };

    parseOrder(order);

    return (a, b) => {
        const indexA = map[a.id].index;
        const indexB = map[b.id].index;
        return indexA - indexB;
    };
};
