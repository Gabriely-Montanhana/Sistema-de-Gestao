function getSwal() {
    return window.Swal;
}
export function showAlert(message, type) {
    const swal = getSwal();
    if (!swal) {
        window.alert(message);
        return;
    }
    void swal.fire({
        icon: type === 'success' ? 'success' : 'error',
        title: type === 'success' ? 'Sucesso!' : 'Erro',
        text: message,
        confirmButtonText: 'OK',
        confirmButtonColor: type === 'success' ? '#2563eb' : '#dc2626',
    });
}
export function exibirFlash() {
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
export async function confirmar(opcoes) {
    const swal = getSwal();
    if (!swal) {
        return window.confirm(`${opcoes.title}\n${opcoes.text}`);
    }
    const result = await swal.fire({
        icon: opcoes.icon ?? 'warning',
        title: opcoes.title,
        text: opcoes.text,
        showCancelButton: true,
        confirmButtonText: opcoes.confirmButtonText,
        cancelButtonText: 'Cancelar',
        confirmButtonColor: opcoes.confirmButtonColor ?? '#dc2626',
    });
    return result.isConfirmed;
}
