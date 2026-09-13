import { confirmar, exibirFlash } from '../utils/alertas.js';
import { iniciarFiltroTabela } from '../utils/filtroTabela.js';

type MaskInstance = {
    updateValue: (value: string) => void;
    updateOptions: (options: object) => void;
};

type TipoDocumento = 'cnpj' | 'cpf';

let documentoMask: MaskInstance | null = null;

function mascaraDocumento(tipo: TipoDocumento): string {
    return tipo === 'cpf' ? '000.000.000-00' : '00.000.000/0000-00';
}

function tipoDocumentoSelecionado(): TipoDocumento {
    const cpf = document.getElementById('tipo-cpf') as HTMLInputElement | null;
    return cpf?.checked ? 'cpf' : 'cnpj';
}

function aplicarTipoDocumento(tipo: TipoDocumento, limpar = false): void {
    const label = document.getElementById('label-documento');
    const input = document.getElementById('cnpj') as HTMLInputElement | null;
    const feedback = document.getElementById('feedback-documento');
    const rotulo = tipo === 'cpf' ? 'CPF' : 'CNPJ';

    if (label) {
        label.innerHTML = `${rotulo} <span class="text-danger">*</span>`;
    }

    if (input) {
        input.placeholder = mascaraDocumento(tipo);
        input.maxLength = tipo === 'cpf' ? 14 : 18;
    }

    if (feedback) {
        feedback.textContent = `Informe o ${rotulo}.`;
    }

    documentoMask?.updateOptions({ mask: mascaraDocumento(tipo) });

    if (limpar) {
        documentoMask?.updateValue('');
        if (input) {
            input.value = '';
        }
    }
}

function aplicarMascaras(): void {
    const imask = (window as Window & { IMask?: (el: Element, options: object) => MaskInstance }).IMask;
    if (!imask) {
        return;
    }

    const documentoInput = document.getElementById('cnpj');
    const telefoneInput = document.getElementById('telefone');

    if (documentoInput) {
        documentoMask = imask(documentoInput, { mask: mascaraDocumento(tipoDocumentoSelecionado()) });
    }

    if (telefoneInput) {
        imask(telefoneInput, {
            mask: [
                { mask: '(00) 0000-0000' },
                { mask: '(00) 00000-0000' },
            ],
        });
    }

    document.getElementById('tipo-cnpj')?.addEventListener('change', () => aplicarTipoDocumento('cnpj', true));
    document.getElementById('tipo-cpf')?.addEventListener('change', () => aplicarTipoDocumento('cpf', true));
}

function confirmarExclusao(): void {
    document.querySelectorAll<HTMLFormElement>('.form-excluir-empresa').forEach((form) => {
        form.addEventListener('submit', (event) => {
            event.preventDefault();

            const nome = form.dataset.nome ?? 'esta empresa';
            const temServico = form.dataset.temServico === '1';

            void confirmar({
                title: temServico ? 'Desativar empresa?' : 'Excluir empresa?',
                text: temServico
                    ? `"${nome}" possui serviços e não pode ser excluída. Ela será desativada.`
                    : `A empresa "${nome}" será excluída.`,
                confirmButtonText: temServico ? 'Desativar' : 'Excluir',
            }).then((ok) => {
                if (ok) {
                    form.submit();
                }
            });
        });
    });
}

function confirmarStatus(): void {
    document.querySelectorAll<HTMLFormElement>('.form-acao-status').forEach((form) => {
        form.addEventListener('submit', (event) => {
            event.preventDefault();

            const nome = form.dataset.nome ?? 'esta empresa';
            const acao = (form.querySelector('input[name="acao"]') as HTMLInputElement | null)?.value;
            const inativar = acao === 'inativar';

            void confirmar({
                title: inativar ? 'Inativar empresa?' : 'Ativar empresa?',
                text: nome,
                confirmButtonText: inativar ? 'Inativar' : 'Ativar',
                icon: inativar ? 'warning' : 'question',
                confirmButtonColor: inativar ? '#dc2626' : '#16a34a',
            }).then((ok) => {
                if (ok) {
                    form.submit();
                }
            });
        });
    });
}

function validarFormulario(): void {
    const form = document.getElementById('form-empresa') as HTMLFormElement | null;
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
    aplicarMascaras();
    exibirFlash();
    confirmarStatus();
    confirmarExclusao();
    validarFormulario();
    iniciarFiltroTabela({
        seletorLinha: '.linha-empresa',
        idBusca: 'filtro-empresas-busca',
        idSelect: 'filtro-empresas-status',
        idVazio: 'filtro-empresas-vazio',
        campoTexto: 'busca',
        campoSelect: 'status',
    });
});
