(() => {
    'use strict';

    let cnpjMask: { updateValue: (value: string) => void } | null = null;
    let telefoneMask: { updateValue: (value: string) => void } | null = null;

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

    function exibirFlash(): void {
        const el = document.getElementById('flash-message');
        if (!el) {
            return;
        }

        const type = el.dataset.type === 'success' ? 'success' : 'danger';
        const message = el.dataset.message ?? '';

        if (message !== '') {
            showAlert(message, type);
        }
    }

    function confirmarStatus(): void {
        const swal = (window as Window & {
            Swal?: {
                fire: (options: Record<string, unknown>) => Promise<{ isConfirmed: boolean }>;
            };
        }).Swal;

        document.querySelectorAll<HTMLFormElement>('.form-acao-status').forEach((form) => {
            form.addEventListener('submit', (event) => {
                event.preventDefault();

                const nome = form.dataset.nome ?? 'esta empresa';
                const acao = (form.querySelector('input[name="acao"]') as HTMLInputElement | null)?.value;
                const inativar = acao === 'inativar';

                if (!swal) {
                    if (window.confirm(`${inativar ? 'Inativar' : 'Ativar'} ${nome}?`)) {
                        form.submit();
                    }
                    return;
                }

                void swal.fire({
                    icon: inativar ? 'warning' : 'question',
                    title: inativar ? 'Inativar empresa?' : 'Ativar empresa?',
                    text: nome,
                    showCancelButton: true,
                    confirmButtonText: inativar ? 'Inativar' : 'Ativar',
                    cancelButtonText: 'Cancelar',
                    confirmButtonColor: inativar ? '#dc2626' : '#16a34a',
                }).then((result) => {
                    if (result.isConfirmed) {
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

        form.addEventListener('reset', () => {
            form.classList.remove('was-validated');
            setTimeout(() => {
                cnpjMask?.updateValue('');
                telefoneMask?.updateValue('');
            }, 0);
        });
    }

    function init(): void {
        aplicarMascaras();
        exibirFlash();
        confirmarStatus();
        validarFormulario();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
