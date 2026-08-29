async function parseJson(response) {
    const text = await response.text();
    try {
        return JSON.parse(text);
    }
    catch {
        throw new Error(text.trim().startsWith('<')
            ? 'Resposta inválida da API. Verifique se o Apache/PHP e o banco estão ativos.'
            : text.slice(0, 200) || `Erro HTTP ${response.status}`);
    }
}
export function getAppRoot() {
    return document.querySelector('meta[name="app-root"]')?.getAttribute('content') ?? '';
}
function resolveUrl(url) {
    if (/^(https?:)?\/\//.test(url) || url.startsWith('/')) {
        return url;
    }
    return `${getAppRoot()}${url}`;
}
export async function apiGet(url) {
    const response = await fetch(resolveUrl(url));
    const json = await parseJson(response);
    if (!response.ok || !json.success) {
        throw new Error(json.message ?? `Erro HTTP ${response.status}`);
    }
    return json.data;
}
export async function apiPost(url, body) {
    const response = await fetch(resolveUrl(url), {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(body),
    });
    const json = await parseJson(response);
    if (!response.ok || !json.success) {
        throw new Error(json.message ?? `Erro HTTP ${response.status}`);
    }
    return json.data;
}
