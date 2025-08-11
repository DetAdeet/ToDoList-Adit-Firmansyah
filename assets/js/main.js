// assets/js/main.js
// Main JavaScript file untuk To Do List

document.addEventListener('DOMContentLoaded', function() {
    // Initialize all functions
    initializeApp();
});

function initializeApp() {
    // Auto-hide messages after 5 seconds
    autoHideMessages();
    
    // Initialize form validation
    initializeFormValidation();
    
    // Initialize confirm dialogs
    initializeConfirmDialogs();
    
    // Initialize date validation
    initializeDateValidation();
    
    // Initialize task counters
    updateTaskCounters();
    
    console.log('✅ To Do List App initialized successfully!');
}

// Toggle edit form visibility
function toggleEdit(taskId) {
    const editForm = document.getElementById('edit-form-' + taskId);
    const isVisible = editForm.style.display !== 'none';
    
    // Hide all other edit forms first
    document.querySelectorAll('[id^="edit-form-"]').forEach(form => {
        if (form.id !== 'edit-form-' + taskId) {
            form.style.display = 'none';
        }
    });
    
    // Toggle current form
    if (isVisible) {
        editForm.style.display = 'none';
    } else {
        editForm.style.display = 'block';
        editForm.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        
        // Focus on first input
        const firstInput = editForm.querySelector('input, select, textarea');
        if (firstInput) {
            firstInput.focus();
        }
    }
}

// Auto-hide success and error messages
function autoHideMessages() {
    const messages = document.querySelectorAll('.message');
    
    messages.forEach(function(message) {
        // Add close button
        const closeBtn = document.createElement('button');
        closeBtn.innerHTML = '✕';
        closeBtn.className = 'btn-close';
        closeBtn.style.cssText = `
            background: none;
            border: none;
            color: inherit;
            font-size: 18px;
            cursor: pointer;
            padding: 0;
            margin-left: auto;
            opacity: 0.7;
        `;
        closeBtn.onclick = () => hideMessage(message);
        
        message.appendChild(closeBtn);
        
        // Auto-hide after 5 seconds
        setTimeout(() => hideMessage(message), 5000);
    });
}

function hideMessage(message) {
    message.style.transition = 'opacity 0.5s, transform 0.5s';
    message.style.opacity = '0';
    message.style.transform = 'translateY(-20px)';
    
    setTimeout(() => {
        if (message.parentNode) {
            message.parentNode.removeChild(message);
        }
    }, 500);
}

// Form validation
function initializeFormValidation() {
    const forms = document.querySelectorAll('form');
    
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            if (!validateForm(form)) {
                e.preventDefault();
                return false;
            }
        });
        
        // Real-time validation
        const inputs = form.querySelectorAll('input, select, textarea');
        inputs.forEach(input => {
            input.addEventListener('blur', () => validateField(input));
            input.addEventListener('input', () => clearFieldError(input));
        });
    });
}

function validateForm(form) {
    let isValid = true;
    const inputs = form.querySelectorAll('input[required], select[required], textarea[required]');
    
    inputs.forEach(input => {
        if (!validateField(input)) {
            isValid = false;
        }
    });
    
    return isValid;
}

function validateField(field) {
    const value = field.value.trim();
    let isValid = true;
    let errorMessage = '';
    
    // Required field validation
    if (field.hasAttribute('required') && !value) {
        errorMessage = 'Field ini wajib diisi';
        isValid = false;
    }
    
    // Date validation
    if (field.type === 'date' && value) {
        const selectedDate = new Date(value);
        const today = new Date();
        today.setHours(0, 0, 0, 0);
        
        if (field.name === 'deadline' && selectedDate < today) {
            errorMessage = 'Deadline tidak boleh kurang dari hari ini';
            isValid = false;
        }
    }
    
    // Text length validation
    if (field.type === 'text' && value.length > 255) {
        errorMessage = 'Maksimal 255 karakter';
        isValid = false;
    }
    
    // Show/hide error
    if (!isValid) {
        showFieldError(field, errorMessage);
    } else {
        clearFieldError(field);
    }
    
    return isValid;
}

function showFieldError(field, message) {
    clearFieldError(field);
    
    const errorDiv = document.createElement('div');
    errorDiv.className = 'field-error';
    errorDiv.style.cssText = `
        color: #dc3545;
        font-size: 0.875rem;
        margin-top: 5px;
        display: flex;
        align-items: center;
        gap: 5px;
    `;
    errorDiv.innerHTML = `⚠️ ${message}`;
    
    field.parentNode.appendChild(errorDiv);
    field.style.borderColor = '#dc3545';
    field.style.boxShadow = '0 0 0 3px rgba(220, 53, 69, 0.1)';
}

function clearFieldError(field) {
    const errorDiv = field.parentNode.querySelector('.field-error');
    if (errorDiv) {
        errorDiv.remove();
    }
    field.style.borderColor = '';
    field.style.boxShadow = '';
}

// Confirm dialogs for critical actions
function initializeConfirmDialogs() {
    // Delete confirmations
    const deleteForms = document.querySelectorAll('form[data-confirm="delete"]');
    deleteForms.forEach(form => {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            showConfirmDialog(
                'Hapus Tugas',
                'Yakin ingin menghapus tugas ini? Tindakan ini tidak dapat dibatalkan.',
                'danger',
                () => form.submit()
            );
        });
    });
    
    // Status toggle confirmations
    const statusForms = document.querySelectorAll('form[data-confirm="status"]');
    statusForms.forEach(form => {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            const currentStatus = form.querySelector('input[name="current_status"]').value;
            const newStatus = currentStatus === 'selesai' ? 'belum selesai' : 'selesai';
            
            showConfirmDialog(
                'Ubah Status',
                `Yakin ingin mengubah status menjadi "${newStatus}"?`,
                'warning',
                () => form.submit()
            );
        });
    });
}

function showConfirmDialog(title, message, type = 'warning', onConfirm = null) {
    const colors = {
        danger: { bg: '#dc3545', color: '#fff' },
        warning: { bg: '#ffc107', color: '#212529' },
        info: { bg: '#17a2b8', color: '#fff' }
    };
    
    const color = colors[type] || colors.warning;
    
    const overlay = document.createElement('div');
    overlay.style.cssText = `
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0,0,0,0.5);
        z-index: 10000;
        display: flex;
        align-items: center;
        justify-content: center;
        animation: fadeIn 0.3s ease;
    `;
    
    const dialog = document.createElement('div');
    dialog.style.cssText = `
        background: white;
        padding: 30px;
        border-radius: 10px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.3);
        max-width: 400px;
        width: 90%;
        text-align: center;
        animation: slideUp 0.3s ease;
    `;
    
    dialog.innerHTML = `
        <div style="font-size: 2rem; margin-bottom: 15px;">
            ${type === 'danger' ? '⚠️' : type === 'warning' ? '❓' : 'ℹ️'}
        </div>
        <h3 style="margin-bottom: 15px; color: #2c3e50;">${title}</h3>
        <p style="color: #6c757d; margin-bottom: 25px; line-height: 1.5;">${message}</p>
        <div style="display: flex; gap: 10px; justify-content: center;">
            <button id="confirm-btn" style="
                background: ${color.bg};
                color: ${color.color};
                border: none;
                padding: 10px 20px;
                border-radius: 5px;
                cursor: pointer;
                font-weight: 600;
            ">Ya, Lanjutkan</button>
            <button id="cancel-btn" style="
                background: #6c757d;
                color: white;
                border: none;
                padding: 10px 20px;
                border-radius: 5px;
                cursor: pointer;
                font-weight: 600;
            ">Batal</button>
        </div>
    `;
    
    overlay.appendChild(dialog);
    document.body.appendChild(overlay);
    
    // Event listeners
    dialog.querySelector('#confirm-btn').addEventListener('click', () => {
        document.body.removeChild(overlay);
        if (onConfirm) onConfirm();
    });
    
    dialog.querySelector('#cancel-btn').addEventListener('click', () => {
        document.body.removeChild(overlay);
    });
    
    overlay.addEventListener('click', (e) => {
        if (e.target === overlay) {
            document.body.removeChild(overlay);
        }
    });
    
    // Add animation styles
    const style = document.createElement('style');
    style.textContent = `
        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px) scale(0.9);
            }
            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }
    `;
    document.head.appendChild(style);
}

// Date validation and helpers
function initializeDateValidation() {
    const dateInputs = document.querySelectorAll('input[type="date"]');
    
    dateInputs.forEach(input => {
        // Set minimum date to today for deadline fields
        if (input.name === 'deadline') {
            input.min = new Date().toISOString().split('T')[0];
        }
        
        // Set default date for new tasks
        if (input.name === 'tanggal' && !input.value) {
            input.value = new Date().toISOString().split('T')[0];
        }
    });
}

// Task counters and statistics
function updateTaskCounters() {
    const tasks = document.querySelectorAll('.task-item');
    const completedTasks = document.querySelectorAll('.task-item.completed');
    const overdueTasks = document.querySelectorAll('.task-item.overdue');
    const pendingTasks = tasks.length - completedTasks.length;
    
    // Update counters in UI if elements exist
    const totalCounter = document.getElementById('total-tasks');
    const completedCounter = document.getElementById('completed-tasks');
    const pendingCounter = document.getElementById('pending-tasks');
    const overdueCounter = document.getElementById('overdue-tasks');
    
    if (totalCounter) totalCounter.textContent = tasks.length;
    if (completedCounter) completedCounter.textContent = completedTasks.length;
    if (pendingCounter) pendingCounter.textContent = pendingTasks;
    if (overdueCounter) overdueCounter.textContent = overdueTasks.length;
    
    // Update progress bar if exists
    const progressBar = document.getElementById('progress-bar');
    if (progressBar && tasks.length > 0) {
        const percentage = Math.round((completedTasks.length / tasks.length) * 100);
        progressBar.style.width = percentage + '%';
        progressBar.textContent = percentage + '%';
    }
}

// Show loading state for forms
function showLoading(button) {
    const originalText = button.innerHTML;
    button.innerHTML = '<span class="loading"></span>Memproses...';
    button.disabled = true;
    
    return function hideLoading() {
        button.innerHTML = originalText;
        button.disabled = false;
    };
}

// Utility functions
function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('id-ID', {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    });
}

function isOverdue(deadline, status) {
    if (status === 'selesai') return false;
    const deadlineDate = new Date(deadline);
    const today = new Date();
    today.setHours(0, 0, 0, 0);
    return deadlineDate < today;
}

// Search and filter functionality
function initializeSearch() {
    const searchInput = document.getElementById('search-tasks');
    if (searchInput) {
        searchInput.addEventListener('input', debounce(filterTasks, 300));
    }
    
    const filterSelect = document.getElementById('filter-status');
    if (filterSelect) {
        filterSelect.addEventListener('change', filterTasks);
    }
    
    const priorityFilter = document.getElementById('filter-priority');
    if (priorityFilter) {
        priorityFilter.addEventListener('change', filterTasks);
    }
}

function filterTasks() {
    const searchTerm = document.getElementById('search-tasks')?.value.toLowerCase() || '';
    const statusFilter = document.getElementById('filter-status')?.value || '';
    const priorityFilter = document.getElementById('filter-priority')?.value || '';
    
    const tasks = document.querySelectorAll('.task-item');
    
    tasks.forEach(task => {
        const title = task.querySelector('.task-title').textContent.toLowerCase();
        const status = task.classList.contains('completed') ? 'selesai' : 'belum selesai';
        const priority = task.querySelector('.priority').textContent.toLowerCase();
        
        let visible = true;
        
        // Search filter
        if (searchTerm && !title.includes(searchTerm)) {
            visible = false;
        }
        
        // Status filter
        if (statusFilter && statusFilter !== status) {
            visible = false;
        }
        
        // Priority filter
        if (priorityFilter && !priority.includes(priorityFilter)) {
            visible = false;
        }
        
        task.style.display = visible ? 'block' : 'none';
    });
    
    // Update "no tasks" message
    const visibleTasks = document.querySelectorAll('.task-item[style*="block"], .task-item:not([style*="none"])');
    const noTasksMsg = document.querySelector('.no-tasks');
    
    if (visibleTasks.length === 0 && !noTasksMsg) {
        const tasksSection = document.querySelector('.tasks-section');
        const msg = document.createElement('div');
        msg.className = 'no-tasks';
        msg.innerHTML = '🔍 Tidak ada tugas yang sesuai dengan filter';
        tasksSection.appendChild(msg);
    } else if (visibleTasks.length > 0 && noTasksMsg) {
        noTasksMsg.remove();
    }
}

// Debounce function for search optimization
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

// Keyboard shortcuts
function initializeKeyboardShortcuts() {
    document.addEventListener('keydown', function(e) {
        // Ctrl+N: Focus on new task input
        if (e.ctrlKey && e.key === 'n') {
            e.preventDefault();
            const firstInput = document.querySelector('input[name="nama_tugas"]');
            if (firstInput) {
                firstInput.focus();
                firstInput.scrollIntoView({ behavior: 'smooth' });
            }
        }
        
        // Escape: Close all edit forms
        if (e.key === 'Escape') {
            document.querySelectorAll('[id^="edit-form-"]').forEach(form => {
                form.style.display = 'none';
            });
        }
        
        // Ctrl+F: Focus on search
        if (e.ctrlKey && e.key === 'f') {
            e.preventDefault();
            const searchInput = document.getElementById('search-tasks');
            if (searchInput) {
                searchInput.focus();
            }
        }
    });
}

// Notification system
function showNotification(message, type = 'info') {
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        background: ${type === 'success' ? '#28a745' : type === 'error' ? '#dc3545' : '#17a2b8'};
        color: white;
        padding: 15px 20px;
        border-radius: 8px;
        box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        z-index: 10000;
        animation: slideInRight 0.3s ease;
        max-width: 300px;
        font-weight: 500;
    `;
    
    notification.innerHTML = `
        <div style="display: flex; align-items: center; gap: 10px;">
            <span>${type === 'success' ? '✅' : type === 'error' ? '❌' : 'ℹ️'}</span>
            <span>${message}</span>
            <button onclick="this.parentElement.parentElement.remove()" 
                    style="background: none; border: none; color: white; font-size: 18px; cursor: pointer; margin-left: auto;">✕</button>
        </div>
    `;
    
    document.body.appendChild(notification);
    
    // Auto-remove after 5 seconds
    setTimeout(() => {
        if (notification.parentNode) {
            notification.style.animation = 'slideOutRight 0.3s ease';
            setTimeout(() => notification.remove(), 300);
        }
    }, 5000);
    
    // Add animation styles if not exists
    if (!document.getElementById('notification-styles')) {
        const style = document.createElement('style');
        style.id = 'notification-styles';
        style.textContent = `
            @keyframes slideInRight {
                from { transform: translateX(100%); opacity: 0; }
                to { transform: translateX(0); opacity: 1; }
            }
            @keyframes slideOutRight {
                from { transform: translateX(0); opacity: 1; }
                to { transform: translateX(100%); opacity: 0; }
            }
        `;
        document.head.appendChild(style);
    }
}

// Export functions for global access
window.todoApp = {
    toggleEdit,
    showNotification,
    showConfirmDialog,
    formatDate,
    isOverdue,
    updateTaskCounters
};

// Initialize search and shortcuts when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    initializeSearch();
    initializeKeyboardShortcuts();
});