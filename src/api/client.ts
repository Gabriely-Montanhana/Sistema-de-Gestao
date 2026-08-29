import type { ApiResponse } from '../types/tipos.js';

async function parseJson<T>(response: Response): Promise<ApiResponse<T>> {
    const text = await response.text();

    try {
        return JSON.parse(text) as ApiResponse<T>;
    } catch {
        throw new Error(
            text.trim().startsWith('<')
                ? 'Resposta inválida da API. Verifique se o Apache/PHP e o banco estão ativos.'
                : text.slice(0, 200) || `Erro HTTP ${response.status}`
        );
    }
}

export function getAppRoot(): string {
    return document.querySelector('meta[name="app-root"]')?.getAttribute('content') ?? '';
}

function resolveUrl(url: string): string {
    if (/^(https?:)?\/\//.test(url) || url.startsWith('/')) {
        return url;
    }

    return `${getAppRoot()}${url}`;
}

export async function apiGet<T>(url: string): Promise<T> {
    const response = await fetch(resolveUrl(url));
    const json = await parseJson<T>(response);

    if (!response.ok || !json.success) {
        throw new Error(json.message ?? `Erro HTTP ${response.status}`);
    }

    return json.data;
}

export async function apiPost<T>(url: string, body: unknown): Promise<T> {
    const response = await fetch(resolveUrl(url), {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(body),
    });

    const json = await parseJson<T>(response);

    if (!response.ok || !json.success) {
        throw new Error(json.message ?? `Erro HTTP ${response.status}`);
    }

    return json.data;
}
