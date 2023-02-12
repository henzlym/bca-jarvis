import {
    pasteHandler,
    serialize
} from '@wordpress/blocks';

export const convertToBlocks = (content) => {
    let blocks = pasteHandler({ HTML: content });
    return serialize(blocks)
}


export async function createTerm(taxonomy = 'categories', name, description = null) {
    if (!name) {
        throw new Error('Name is required');
    }

    const category = await getTerm(taxonomy, name);

    console.log(taxonomy, name, category);
    if (category.length !== 0) {
        return category;
    }

    const endpoint = '/wp-json/wp/v2/' + taxonomy;

    const data = {
        name: name,
        description: description || '',
    };

    const response = await fetch(endpoint, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-WP-Nonce': jarvisSettings.nonce
        },
        body: JSON.stringify(data),
    });

    if (response.status !== 201) {
        throw new Error(`Failed to create category: ${response.statusText}`);
    }

    return await response.json();
}
export async function createTerms(taxonomy = 'categories', categories) {

    const promises = categories.map(async category => {

        return createTerm(taxonomy, category)

    });

    return await Promise.all(promises);
}

export async function getTerm(taxonomy = 'categories', nameOrSlug) {
    const endpoint = `/wp-json/wp/v2/${taxonomy}?slug=${convertToSlug(nameOrSlug)}`;

    const response = await fetch(endpoint);

    if (!response.ok) {
        throw new Error(`Failed to get category: ${response.statusText}`);
    }

    const categories = await response.json();

    if (categories.length === 0) {
        return [];
    }

    return categories[0];
}

function convertToSlug(string) {
    return string.toLowerCase().replace(/\s/g, '-');
}
