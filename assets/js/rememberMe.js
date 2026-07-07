// assets/js/rememberMe.js

class RememberMeSystem {
    constructor() {
        this.storageKey = 'chefcontrol_remember';
        this.expiryKey = 'chefcontrol_expires';
        this.encryptionKey = 'chefcontrol_enc_2024'; // Cambiar en producción
        this.expiryDays = 30; // Días que dura "Recuérdame"
        
        this.init();
    }
    
    // Inicializar el sistema
    init() {
        // Limpiar credenciales expiradas
        this.cleanExpiredCredentials();
        
        // Cargar credenciales al cargar la página
        this.loadCredentials();
        
        // Configurar evento para guardar al enviar el formulario
        this.setupFormSubmit();
    }
    
    // 🔐 Cifrar texto (AES simple)
    encrypt(text) {
        try {
            // En producción, usar una librería como crypto-js
            // Esta es una implementación básica para desarrollo
            let result = '';
            for (let i = 0; i < text.length; i++) {
                const charCode = text.charCodeAt(i) ^ this.encryptionKey.charCodeAt(i % this.encryptionKey.length);
                result += String.fromCharCode(charCode);
            }
            return btoa(result);
        } catch (error) {
            console.error('Error encriptando:', error);
            return btoa(text); // Fallback a base64
        }
    }
    
    // 🔓 Descifrar texto
    decrypt(encryptedText) {
        try {
            const text = atob(encryptedText);
            let result = '';
            for (let i = 0; i < text.length; i++) {
                const charCode = text.charCodeAt(i) ^ this.encryptionKey.charCodeAt(i % this.encryptionKey.length);
                result += String.fromCharCode(charCode);
            }
            return result;
        } catch (error) {
            console.error('Error desencriptando:', error);
            return atob(encryptedText); // Fallback de base64
        }
    }
    
    // Cargar credenciales guardadas
    loadCredentials() {
        const saved = localStorage.getItem(this.storageKey);
        
        if (!saved) return;
        
        try {
            const credentials = JSON.parse(saved);
            
            // Verificar si no han expirado
            if (this.isExpired(credentials.timestamp)) {
                this.clearCredentials();
                return;
            }
            
            // Rellenar formulario
            const usernameInput = document.getElementById('username');
            const passwordInput = document.getElementById('password');
            const rememberCheckbox = document.getElementById('remember');
            
            if (usernameInput) {
                usernameInput.value = credentials.username || '';
            }
            
            if (passwordInput) {
                try {
                    passwordInput.value = this.decrypt(credentials.password) || '';
                } catch (e) {
                    console.warn('No se pudo descifrar la contraseña:', e);
                    passwordInput.value = '';
                }
            }
            
            if (rememberCheckbox) {
                rememberCheckbox.checked = true;
            }
            
            console.log('Credenciales cargadas desde Local Storage');
            
        } catch (error) {
            console.error('Error cargando credenciales:', error);
            this.clearCredentials();
        }
    }
    
    // Configurar evento de envío del formulario
    setupFormSubmit() {
        const form = document.getElementById('loginForm') || document.querySelector('form[action*="/login"]');
        
        if (!form) {
            console.warn('Formulario de login no encontrado');
            return;
        }
        
        form.addEventListener('submit', (e) => this.handleFormSubmit(e));
    }
    
    // Manejar envío del formulario
    handleFormSubmit(e) {
        const rememberCheckbox = document.getElementById('remember');
        const usernameInput = document.getElementById('username');
        const passwordInput = document.getElementById('password');
        
        if (!rememberCheckbox || !usernameInput || !passwordInput) return;
        
        if (rememberCheckbox.checked) {
            this.saveCredentials(usernameInput.value, passwordInput.value);
        } else {
            this.clearCredentials();
        }
    }
    
    // Guardar credenciales
    saveCredentials(username, password) {
        if (!username || !password) {
            console.warn('Usuario o contraseña vacíos, no se guardarán');
            return;
        }
        
        try {
            const credentials = {
                username: username,
                password: this.encrypt(password),
                timestamp: new Date().getTime()
            };
            
            // Guardar en Local Storage
            localStorage.setItem(this.storageKey, JSON.stringify(credentials));
            
            // Guardar fecha de expiración
            const expiryDate = new Date();
            expiryDate.setDate(expiryDate.getDate() + this.expiryDays);
            
            const expiryData = {
                setDate: new Date().getTime(),
                expires: expiryDate.getTime()
            };
            
            localStorage.setItem(this.expiryKey, JSON.stringify(expiryData));
            
            console.log('Credenciales guardadas en Local Storage');
            
        } catch (error) {
            console.error('Error guardando credenciales:', error);
        }
    }
    
    // Limpiar credenciales
    clearCredentials() {
        localStorage.removeItem(this.storageKey);
        localStorage.removeItem(this.expiryKey);
        console.log('Credenciales eliminadas de Local Storage');
    }
    
    // Verificar si las credenciales han expirado
    isExpired(timestamp) {
        if (!timestamp) return true;
        
        const now = new Date().getTime();
        const expiryTime = timestamp + (this.expiryDays * 24 * 60 * 60 * 1000);
        
        return now > expiryTime;
    }
    
    // Limpiar credenciales expiradas
    cleanExpiredCredentials() {
        const expiryData = localStorage.getItem(this.expiryKey);
        
        if (!expiryData) {
            this.clearCredentials();
            return;
        }
        
        try {
            const data = JSON.parse(expiryData);
            const now = new Date().getTime();
            
            if (now > data.expires) {
                this.clearCredentials();
                console.log('Credenciales expiradas eliminadas');
            }
        } catch (error) {
            console.error('Error verificando expiración:', error);
            this.clearCredentials();
        }
    }
    
    // Método para verificar si hay credenciales guardadas
    hasSavedCredentials() {
        return !!localStorage.getItem(this.storageKey);
    }
    
    // Método para obtener usuario guardado (solo lectura)
    getSavedUsername() {
        const saved = localStorage.getItem(this.storageKey);
        
        if (!saved) return null;
        
        try {
            const credentials = JSON.parse(saved);
            return credentials.username || null;
        } catch (error) {
            return null;
        }
    }
}

// Inicializar cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', function() {
    window.rememberMe = new RememberMeSystem();
    
    // Agregar botón opcional para limpiar credenciales (debug)
    if (window.location.href.includes('login') && window.rememberMe.hasSavedCredentials()) {
        const clearBtn = document.createElement('button');
        clearBtn.textContent = '🗑️ Limpiar credenciales';
        clearBtn.style.cssText = `
            position: fixed;
            bottom: 10px;
            right: 10px;
            background: #e74c3c;
            color: white;
            border: none;
            padding: 5px 10px;
            border-radius: 4px;
            font-size: 12px;
            cursor: pointer;
            z-index: 9999;
            opacity: 0.3;
        `;
        clearBtn.addEventListener('mouseenter', () => clearBtn.style.opacity = '1');
        clearBtn.addEventListener('mouseleave', () => clearBtn.style.opacity = '0.3');
        clearBtn.addEventListener('click', () => {
            window.rememberMe.clearCredentials();
            location.reload();
        });
        document.body.appendChild(clearBtn);
    }
});

// Exportar para uso global
window.RememberMeSystem = RememberMeSystem;