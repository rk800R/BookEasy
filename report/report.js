// report.js - Frontend JavaScript for Report and Feedback Management

class ReportManager {
    constructor() {
        this.apiUrl = 'report.php';
        this.init();
    }

    init() {
        this.setupFormHandler();
    }

    setupFormHandler() {
        const form = document.getElementById('feedbackForm');
        if (form) {
            form.addEventListener('submit', (e) => {
                e.preventDefault();
                this.submitFeedback();
            });
        }
    }

    async submitFeedback() {
        const type = document.getElementById('feedback-type').value;
        const email = document.getElementById('feedback-email').value.trim();
        const details = document.getElementById('feedback-details').value.trim();
        const messageElement = document.getElementById('feedbackMessage');

        // Validation
        if (!details) {
            this.showMessage('Please provide detailed description', 'error');
            return;
        }

        // Show loading message
        messageElement.style.color = 'blue';
        messageElement.textContent = 'Submitting your feedback...';

        try {
            const response = await fetch(this.apiUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action: 'submitFeedback',
                    type: type,
                    email: email || null,
                    details: details
                })
            });

            const data = await response.json();

            if (data.success) {
                this.showMessage(`✅ Thank you for your ${this.getFriendlyType(type)}! Your report has been submitted successfully.`, 'success');
                document.getElementById('feedbackForm').reset();
            } else {
                this.showMessage(`Failed to submit feedback: ${data.message || 'Unknown error'}`, 'error');
            }
        } catch (error) {
            console.error('Error submitting feedback:', error);
            this.showMessage('Error submitting feedback. Please try again later.', 'error');
        }
    }

    getFriendlyType(type) {
        const types = {
            'bug': 'Bug Report',
            'feature': 'Feature Suggestion',
            'general': 'General Feedback',
            'complaint': 'Complaint'
        };
        return types[type] || type;
    }

    showMessage(message, type = 'info') {
        const messageElement = document.getElementById('feedbackMessage');
        messageElement.textContent = message;
        
        switch(type) {
            case 'success':
                messageElement.style.color = '#4CAF50';
                break;
            case 'error':
                messageElement.style.color = '#F96167';
                break;
            default:
                messageElement.style.color = '#2196F3';
        }
    }
}

// Initialize report manager when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    window.reportManager = new ReportManager();
});
