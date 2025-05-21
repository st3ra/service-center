// Функция для экранирования HTML
function escapeHtml(unsafe) {
    if (!unsafe) return '';
    
    const div = document.createElement('div');
    div.textContent = unsafe;
    return div.innerHTML;
}

// Экспортируем функции для использования в других файлах
window.adminUtils = {
    escapeHtml: escapeHtml
}; 