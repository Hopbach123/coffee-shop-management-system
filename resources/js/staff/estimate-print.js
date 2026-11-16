export function initializeEstimatePrint(scope = document, printDialog = () => window.print()) {
    const button = scope.querySelector('[data-estimate-print]');
    const status = scope.querySelector('[data-estimate-print-status]');
    if (!button || !status) return;
    button.addEventListener('click', () => {
        button.disabled = true;
        try {
            printDialog();
            status.textContent = 'Đã yêu cầu mở hộp thoại in. Chưa xác nhận bản in hoặc thanh toán.';
        } catch {
            status.textContent = 'Không thể mở chế độ in. Dùng chức năng In của trình duyệt hoặc thử lại; dữ liệu đơn không thay đổi.';
        } finally {
            button.disabled = false;
        }
    });
}
