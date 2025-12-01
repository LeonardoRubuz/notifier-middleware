// Notifier FlexRoll - JavaScript functionality
class NotifierApp {
    constructor() {
        this.emails = [];
        this.reasonFailures = [];
        this.init();
    }

    init() {
        this.bindEvents();
        this.initDateField();
    }

    bindEvents() {
        // Email input handling
        const emailInput = document.getElementById('emails');
        if (emailInput) {
            emailInput.addEventListener('input', (e) => this.handleEmailInput(e));
        }

        // Add reason failure button
        const addReasonBtn = document.getElementById('add-reason-btn');
        if (addReasonBtn) {
            addReasonBtn.addEventListener('click', () => this.addReasonFailure());
        }

        // Form submission
        const form = document.getElementById('notifier-form');
        if (form) {
            form.addEventListener('submit', (e) => this.handleSubmit(e));
        }
    }

    initDateField() {
        const dateField = document.getElementById('date');
        if (dateField) {
            const today = new Date().toISOString().split('T')[0];
            dateField.value = today;
        }
    }

    handleEmailInput(event) {
        const inputEmails = event.target.value;
        const emailArray = inputEmails.split(',')
            .map(email => email.trim())
            .filter(email => email !== '');
        
        this.emails = emailArray;
        this.renderEmailTags();
    }

    renderEmailTags() {
        const container = document.getElementById('email-tags');
        if (!container) return;

        if (this.emails.length === 0) {
            container.classList.add('hidden');
            return;
        }

        container.classList.remove('hidden');
        container.innerHTML = '';

        this.emails.forEach((email, index) => {
            const tag = this.createEmailTag(email, index);
            container.appendChild(tag);
        });
    }

    createEmailTag(email, index) {
        const tag = document.createElement('div');
        tag.className = 'bg-gray-200 rounded-sm py-0.5 px-2.5 flex items-center gap-1 text-center hover:bg-gray-300';
        
        tag.innerHTML = `
            <span class="lowercase">${email}</span>
            <i data-feather="x" class="hover:cursor-pointer hover:scale-110 transition-transform w-4 h-4" onclick="notifierApp.removeEmail(${index})"></i>
        `;

        // Replace feather icons
        setTimeout(() => feather.replace(), 0);
        
        return tag;
    }

    removeEmail(index) {
        this.emails.splice(index, 1);
        const emailInput = document.getElementById('emails');
        if (emailInput) {
            emailInput.value = this.emails.join(', ');
        }
        this.renderEmailTags();
    }

    addReasonFailure() {
        const phoneInput = document.getElementById('new-phone');
        const reasonInput = document.getElementById('new-reason');
        
        if (!phoneInput || !reasonInput) return;
        
        const phoneNumber = phoneInput.value.trim();
        const reason = reasonInput.value.trim();
        
        if (phoneNumber === '' || reason === '') {
            this.showMessage('Veuillez remplir le numéro de téléphone et la raison d\'échec', 'error');
            return;
        }

        // Validate phone number format
        if (!this.validatePhoneNumber(phoneNumber)) {
            this.showMessage('Format de numéro de téléphone invalide (ex: 243810000000)', 'error');
            return;
        }

        const reasonFailure = { phoneNumber, reason };
        this.reasonFailures.push(reasonFailure);
        
        // Clear inputs
        phoneInput.value = '';
        reasonInput.value = '';
        
        // Add row to table
        this.addReasonFailureRow(reasonFailure, this.reasonFailures.length - 1);
    }

    validatePhoneNumber(phoneNumber) {
        // Basic validation for phone number (adjust according to your requirements)
        const phoneRegex = /^[0-9+\-\s()]{10,15}$/;
        return phoneRegex.test(phoneNumber);
    }

    addReasonFailureRow(reasonFailure, index) {
        const tbody = document.getElementById('reason-failures-tbody');
        if (!tbody) return;

        const newRowTemplate = document.getElementById('new-row-template');
        const row = document.createElement('tr');
        row.className = 'border-b border-gray-100';
        
        row.innerHTML = `
            <td class="p-4 w-1/3">
                <input 
                    type="text"
                    disabled
                    value="${reasonFailure.phoneNumber}"
                    class="border border-gray-400 bg-gray-100 p-2 rounded-md w-full"
                />
            </td>
            <td class="p-4 w-1/3">
                <input
                    type="text"
                    disabled
                    value="${reasonFailure.reason}"
                    class="border border-gray-400 bg-gray-100 p-2 rounded-md w-full"
                />
            </td>
            <td class="p-4 w-1/3">
                <div class="flex items-center justify-center">
                    <i data-feather="trash-2" 
                       class="text-red-500 hover:scale-110 hover:cursor-pointer transition-transform w-5 h-5"
                       onclick="notifierApp.removeReasonFailure(${index})"></i>
                </div>
            </td>
        `;

        // Insert before the template row
        tbody.insertBefore(row, newRowTemplate);
        
        // Replace feather icons
        setTimeout(() => feather.replace(), 0);
    }

    removeReasonFailure(index) {
        this.reasonFailures.splice(index, 1);
        this.refreshReasonFailuresTable();
    }

    refreshReasonFailuresTable() {
        const tbody = document.getElementById('reason-failures-tbody');
        if (!tbody) return;

        // Remove all rows except the template
        const rows = tbody.querySelectorAll('tr:not(#new-row-template)');
        rows.forEach(row => row.remove());

        // Re-add all reason failures
        this.reasonFailures.forEach((reasonFailure, index) => {
            this.addReasonFailureRow(reasonFailure, index);
        });
    }

    validateForm() {
        const shortcode = document.getElementById('shortcode')?.value.trim();
        const total = document.getElementById('total')?.value;
        const date = document.getElementById('date')?.value;

        if (!shortcode) {
            this.showMessage('Le code marchand est requis', 'error');
            return false;
        }

        if (this.emails.length === 0) {
            this.showMessage('Au moins une adresse email est requise', 'error');
            return false;
        }

        if (!total || parseInt(total) <= 0) {
            this.showMessage('Le total de participants doit être un nombre positif', 'error');
            return false;
        }

        if (!date) {
            this.showMessage('La date d\'exécution est requise', 'error');
            return false;
        }

        // Validate email format
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        for (const email of this.emails) {
            if (!emailRegex.test(email)) {
                this.showMessage(`Format d'email invalide: ${email}`, 'error');
                return false;
            }
        }

        return true;
    }

    async handleSubmit(event) {
        event.preventDefault();
        
        if (!this.validateForm()) {
            return;
        }

        const formData = {
            shortcode: document.getElementById('shortcode').value.trim(),
            emails: this.emails,
            total: parseInt(document.getElementById('total').value),
            date: document.getElementById('date').value,
            reasonFailures: this.reasonFailures
        };

        this.showLoading(true);

        try {
            // TODO: Remplacer cette URL par votre endpoint Symfony
            const response = await fetch('/your-symfony-route', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(formData)
            });

            const result = await response.json();

            if (result.success) {
                this.showMessage('Email envoyé avec succès!', 'success');
                this.resetForm();
            } else {
                this.showMessage(result.message || 'Erreur lors de l\'envoi', 'error');
            }
        } catch (error) {
            console.error('Error:', error);
            this.showMessage('Erreur de connexion', 'error');
        } finally {
            this.showLoading(false);
        }
    }

    resetForm() {
        document.getElementById('notifier-form').reset();
        this.emails = [];
        this.reasonFailures = [];
        this.renderEmailTags();
        this.refreshReasonFailuresTable();
        this.initDateField();
    }

    showLoading(show) {
        const loading = document.getElementById('loading');
        if (loading) {
            if (show) {
                loading.classList.remove('hidden');
            } else {
                loading.classList.add('hidden');
            }
        }
    }

    showMessage(message, type = 'info') {
        const messageContainer = document.getElementById('message-container');
        const messageElement = document.getElementById('message');
        
        if (!messageContainer || !messageElement) return;

        // Set message content and style
        messageElement.textContent = message;
        messageElement.className = `p-4 rounded-md shadow-lg ${this.getMessageClass(type)}`;
        
        // Show message
        messageContainer.classList.remove('hidden');
        
        // Hide after 5 seconds
        setTimeout(() => {
            messageContainer.classList.add('hidden');
        }, 5000);
    }

    getMessageClass(type) {
        const classes = {
            success: 'bg-green-100 border border-green-400 text-green-700',
            error: 'bg-red-100 border border-red-400 text-red-700',
            info: 'bg-blue-100 border border-blue-400 text-blue-700',
            warning: 'bg-yellow-100 border border-yellow-400 text-yellow-700'
        };
        return classes[type] || classes.info;
    }
}

// Initialize the app when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    window.notifierApp = new NotifierApp();
});