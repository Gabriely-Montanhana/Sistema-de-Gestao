import { confirmar, exibirFlash } from '../utils/alertas.js';
import { iniciarFiltroTabela } from '../utils/filtroTabela.js';

type SummernoteApi = {
    summernote: (command: string | Record<string, unknown>) => unknown;
};

function jquery(): ((selector: string) => SummernoteApi) | null {
    const jq = (window as Window & { jQuery?: (selector: string) => SummernoteApi }).jQuery;

    return jq ?? null;
}

function descricaoVazia(html: string): boolean {
    const tmp = document.createElement('div');
    tmp.innerHTML = html;

    return (tmp.textContent ?? '').trim() === '';
}

function iniciarEditorDescricao(): void {
    const campo = document.getElementById('descricao');
    const jq = jquery();

    if (campo === null || jq === null) {
        return;
    }

    jq('#descricao').summernote({
        lang: 'pt-BR',
        height: 220,
        placeholder: 'Digite a descrição',
        toolbar: [
            ['style', ['style']],
            ['font', ['bold', 'underline', 'clear']],
            ['color', ['color']],
            ['para', ['ul', 'ol', 'paragraph']],
            ['table', ['table']],
            ['insert', ['link', 'picture', 'video']],
            ['view', ['fullscreen', 'codeview', 'help']],
        ],
    });
}

function confirmarExclusao(): void {
    document.querySelectorAll<HTMLFormElement>('.form-excluir-servico').forEach((form) => {
        form.addEventListener('submit', (event) => {
            event.preventDefault();

            const nome = form.dataset.nome ?? 'este serviço';

            void confirmar({
                title: 'Excluir serviço?',
                text: `O serviço "${nome}" será removido.`,
                confirmButtonText: 'Excluir',
            }).then((ok) => {
                if (ok) {
                    form.submit();
                }
            });
        });
    });
}

function validarFormulario(): void {
    const form = document.getElementById('form-servico') as HTMLFormElement | null;
    if (!form) {
        return;
    }

    form.addEventListener('submit', (event) => {
        const campo = document.getElementById('descricao') as HTMLTextAreaElement | null;
        const jq = jquery();

        if (campo !== null && jq !== null) {
            const html = String(jq('#descricao').summernote('code') ?? '');
            campo.value = html;

            if (descricaoVazia(html)) {
                event.preventDefault();
                event.stopPropagation();
                form.classList.add('was-validated');
                campo.setCustomValidity('Informe a descrição do serviço.');
                return;
            }

            campo.setCustomValidity('');
        }

        if (!form.checkValidity()) {
            event.preventDefault();
            event.stopPropagation();
            form.classList.add('was-validated');
        }
    });
}

document.addEventListener('DOMContentLoaded', () => {
    exibirFlash();
    iniciarEditorDescricao();
    confirmarExclusao();
    validarFormulario();
    iniciarFiltroTabela({
        seletorLinha: '.linha-servico',
        idBusca: 'filtro-servicos-busca',
        idSelect: 'filtro-servicos-status',
        idVazio: 'filtro-servicos-vazio',
        campoTexto: 'busca',
        campoSelect: 'status',
    });
    iniciarFiltroTabela({
        seletorLinha: '.linha-produto-servico',
        idBusca: 'filtro-produtos-servico',
        idVazio: 'filtro-produtos-servico-vazio',
        campoTexto: 'nome',
    });
});
