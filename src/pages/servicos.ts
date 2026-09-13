import { confirmar, exibirFlash } from '../utils/alertas.js';
import { iniciarFiltroTabela } from '../utils/filtroTabela.js';

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
        if (!form.checkValidity()) {
            event.preventDefault();
            event.stopPropagation();
            form.classList.add('was-validated');
        }
    });
}

document.addEventListener('DOMContentLoaded', () => {
    exibirFlash();
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
