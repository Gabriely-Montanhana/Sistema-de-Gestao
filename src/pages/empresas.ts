(() => {
    'use strict';

    let cnpjMask: { updateValue: (value: string) => void } | null = null;
    let telefoneMask: { updateValue: (value: string) => void } | null = null;

    function getAppRoot(): string {
        return document.querySelector('meta[name="app-root"]')?.getAttribute('content') ?? '';
    }

    function apiUrl(): string {
        return `${getAppRoot()}api/empresas.php`;
    }

    async function parseJson(response: Response): Promise<{ success: boolean; message?: string; data: unknown }> {
        const text = await response.text();

        try {
            return JSON.parse(text);
        } catch {
            throw new Error(
                text.trim().startsWith('<')
                    ? 'Resposta inválida da API. Verifique Apache, PHP e o banco de dados.'
                    : text.slice(0, 200) || `Erro HTTP ${response.status}`
            );
        }
    }

    async function apiGet<T>(url: string): Promise<T> {
        const response = await fetch(url);
        const json = await parseJson(response);

        if (!response.ok || !json.success) {
            throw new Error(json.message ?? `Erro HTTP ${response.status}`);
        }

        return json.data as T;
    }

    async function apiPost<T>(url: string, body: unknown): Promise<T> {
        const response = await fetch(url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(body),
        });

        const json = await parseJson(response);

        if (!response.ok || !json.success) {
            throw new Error(json.message ?? `Erro HTTP ${response.status}`);
        }

        return json.data as T;
    }

    function showAlert(message: string, type: 'success' | 'danger'): void {
        const swal = (window as Window & { Swal?: { fire: (options: Record<string, string>) => void } }).Swal;

        if (!swal) {
            window.alert(message);
            return;
        }

        swal.fire({
            icon: type === 'success' ? 'success' : 'error',
            title: type === 'success' ? 'Sucesso!' : 'Erro',
            text: message,
            confirmButtonText: 'OK',
            confirmButtonColor: type === 'success' ? '#2563eb' : '#dc2626',
        });
    }

    function statusBadge(status: string): string {
        return status === 'Ativo'
            ? '<span class="badge rounded-pill text-bg-success">Ativo</span>'
            : '<span class="badge rounded-pill text-bg-secondary">Inativo</span>';
    }

    function renderTabela(empresas: Array<Record<string, string | null>>): void {
        const tbody = document.getElementById('tabela-empresas');
        if (!tbody) {
            return;
        }

        if (empresas.length === 0) {
            tbody.innerHTML = '<tr><td colspan="7" class="text-muted">Nenhuma empresa cadastrada.</td></tr>';
            return;
        }

        tbody.innerHTML = empresas
            .map(
                (empresa) => `
            <tr>
                <td class="fw-semibold">${empresa.nome_empresa ?? ''}</td>
                <td>${empresa.cnpj ?? ''}</td>
                <td>${empresa.cidade ?? '—'}</td>
                <td>${empresa.endereco ?? '—'}</td>
                <td>${empresa.telefone ?? '—'}</td>
                <td>${empresa.email ?? '—'}</td>
                <td>${statusBadge(empresa.status ?? 'Ativo')}</td>
            </tr>
        `
            )
            .join('');
    }

    async function carregarEmpresas(): Promise<void> {
        const tbody = document.getElementById('tabela-empresas');

        try {
            const empresas = await apiGet<Array<Record<string, string | null>>>(apiUrl());
            renderTabela(empresas);
        } catch (error) {
            const msg = error instanceof Error ? error.message : 'Erro desconhecido';
            if (tbody) {
                tbody.innerHTML = `<tr><td colspan="7" class="text-danger">Falha ao carregar: ${msg}</td></tr>`;
            }
        }
    }

    function getFormData(form: HTMLFormElement): Record<string, string | null> {
        const data = new FormData(form);

        return {
            nome_empresa: String(data.get('nome_empresa') ?? '').trim(),
            cnpj: String(data.get('cnpj') ?? '').trim(),
            cidade: String(data.get('cidade') ?? '').trim() || null,
            endereco: String(data.get('endereco') ?? '').trim() || null,
            telefone: String(data.get('telefone') ?? '').trim() || null,
            email: String(data.get('email') ?? '').trim() || null,
        };
    }

    function aplicarMascaras(): void {
        const imask = (window as Window & { IMask?: (el: Element, options: object) => { updateValue: (value: string) => void } }).IMask;
        if (!imask) {
            return;
        }

        const cnpjInput = document.getElementById('cnpj');
        const telefoneInput = document.getElementById('telefone');

        if (cnpjInput) {
            cnpjMask = imask(cnpjInput, { mask: '00.000.000/0000-00' });
        }

        if (telefoneInput) {
            telefoneMask = imask(telefoneInput, {
                mask: [
                    { mask: '(00) 0000-0000' },
                    { mask: '(00) 00000-0000' },
                ],
            });
        }
    }

    function limparMascaras(): void {
        cnpjMask?.updateValue('');
        telefoneMask?.updateValue('');
    }

    async function salvarEmpresa(form: HTMLFormElement): Promise<void> {
        const btn = document.getElementById('btn-salvar') as HTMLButtonElement | null;
        const dados = getFormData(form);

        btn?.setAttribute('disabled', 'true');

        try {
            await apiPost<{ id: number }>(apiUrl(), dados);
            showAlert('Empresa cadastrada com sucesso!', 'success');
            form.reset();
            form.classList.remove('was-validated');
            limparMascaras();
            await carregarEmpresas();
        } catch (error) {
            const msg = error instanceof Error ? error.message : 'Erro desconhecido';
            showAlert(`Não foi possível salvar: ${msg}`, 'danger');
        } finally {
            btn?.removeAttribute('disabled');
        }
    }

    function init(): void {
        const form = document.getElementById('form-empresa') as HTMLFormElement | null;
        const tabVisualizar = document.getElementById('tab-visualizar');

        if (!form) {
            return;
        }

        aplicarMascaras();

        form.addEventListener('submit', (event) => {
            event.preventDefault();
            event.stopPropagation();

            if (!form.checkValidity()) {
                form.classList.add('was-validated');
                return;
            }

            void salvarEmpresa(form);
        });

        form.addEventListener('reset', () => {
            form.classList.remove('was-validated');
            setTimeout(limparMascaras, 0);
        });

        tabVisualizar?.addEventListener('shown.bs.tab', () => {
            void carregarEmpresas();
        });

        void carregarEmpresas();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
