// assets/js/login.js

class LoginSystem {
    constructor() {
        this.init();
    }
    
    init() {
        this.setupFormValidation();
        this.setupPasswordToggle();
        this.setupAutoFocus();
    }
    
    // Validación del formulario
    setupFormValidation() {
        const form = document.getElementById('loginForm') || document.querySelector('form[action*="/login"]');
        
        if (!form) return;
        
        form.addEventListener('submit', (e) => {
            const username = document.getElementById('username');
            const password = document.getElementById('password');
            
            // Validaciones básicas
            if (!username.value.trim()) {
                e.preventDefault();
                this.showError(username, 'Por favor ingresa tu usuario');
                return;
            }
            
            if (!password.value) {
                e.preventDefault();
                this.showError(password, 'Por favor ingresa tu contraseña');
                return;
            }
            
            // Mostrar loading
            const submitBtn = form.querySelector('button[type="submit"]');
            if (submitBtn) {
                const originalText = submitBtn.innerHTML;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Iniciando...';
                submitBtn.disabled = true;
                
                // Restaurar después de 3 segundos (por si hay error)
                setTimeout(() => {
                    submitBtn.innerHTML = originalText;
                    submitBtn.disabled = false;
                }, 3000);
            }
        });
    }
    
    // Mostrar/ocultar contraseña
    setupPasswordToggle() {
        const passwordInput = document.getElementById('password');
        
        if (!passwordInput) return;
        
        // Crear botón para mostrar/ocultar
        const toggleBtn = document.createElement('button');
        toggleBtn.type = 'button';
        toggleBtn.innerHTML = '<i class="fas fa-eye"></i>';
        toggleBtn.className = 'password-toggle';
        toggleBtn.style.cssText = `
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: #7f8c8d;
            cursor: pointer;
            font-size: 14px;
        `;
        
        // Insertar después del campo de contraseña
        const formGroup = passwordInput.closest('.formGroup');
        if (formGroup) {
            formGroup.style.position = 'relative';
            formGroup.appendChild(toggleBtn);
        }
        
        // Alternar visibilidad
        toggleBtn.addEventListener('click', () => {
            const type = passwordInput.type === 'password' ? 'text' : 'password';
            passwordInput.type = type;
            toggleBtn.innerHTML = type === 'password' ? 
                '<i class="fas fa-eye"></i>' : 
                '<i class="fas fa-eye-slash"></i>';
        });
    }
    
    // Autofocus en el primer campo
    setupAutoFocus() {
        const usernameInput = document.getElementById('username');
        if (usernameInput && !usernameInput.value) {
            setTimeout(() => usernameInput.focus(), 100);
        }
    }
    
    // Mostrar error en campo
    showError(inputElement, message) {
        // Remover errores anteriores
        this.clearErrors();
        
        // Crear elemento de error
        const errorDiv = document.createElement('div');
        errorDiv.className = 'field-error';
        errorDiv.textContent = message;
        errorDiv.style.cssText = `
            color: #e74c3c;
            font-size: 12px;
            margin-top: 5px;
            padding: 5px 10px;
            background: #f8d7da;
            border-radius: 4px;
            border: 1px solid #f5c6cb;
        `;
        
        // Insertar después del campo
        inputElement.parentNode.insertBefore(errorDiv, inputElement.nextSibling);
        
        // Resaltar campo
        inputElement.style.borderColor = '#e74c3c';
        inputElement.style.boxShadow = '0 0 0 2px rgba(231, 76, 60, 0.1)';
        
        // Auto-remover después de 5 segundos
        setTimeout(() => {
            this.clearErrors();
            inputElement.style.borderColor = '';
            inputElement.style.boxShadow = '';
        }, 5000);
    }
    
    // Limpiar errores
    clearErrors() {
        document.querySelectorAll('.field-error').forEach(el => el.remove());
    }
}

// Inicializar cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', function() {
    // Inicializar sistema de login
    window.loginSystem = new LoginSystem();
    
    // Inicializar sistema "Recuérdame" (si existe)
    if (typeof RememberMeSystem !== 'undefined') {
        window.rememberMe = new RememberMeSystem();
    }
});