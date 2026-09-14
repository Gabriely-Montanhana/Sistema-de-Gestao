import { confirmar, exibirFlash } from '../utils/alertas.js';
import { iniciarFiltroTabela } from '../utils/filtroTabela.js';
function jquery() {
    const jq = window.jQuery;
    return jq ?? null;
}
function descricaoVazia(html) {
    const tmp = document.createElement('div');
    tmp.innerHTML = html;
    return (tmp.textContent ?? '').trim() === '';
}
function iniciarEditorDescricao() {
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
function confirmarExclusao() {
    document.querySelectorAll('.form-excluir-servico').forEach((form) => {
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
function validarFormulario() {
    const form = document.getElementById('form-servico');
    if (!form) {
        return;
    }
    form.addEventListener('submit', (event) => {
        const campo = document.getElementById('descricao');
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
