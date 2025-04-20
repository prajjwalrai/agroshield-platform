// Mobile menu toggle
document.getElementById('menu-toggle')?.addEventListener('click', function() {
    const mobileMenu = document.getElementById('mobile-menu');
    mobileMenu.classList.toggle('hidden');
});

// Chatbot functionality
const chatbotToggle = document.getElementById('chatbot-toggle');
const chatbotBox = document.getElementById('chatbot-box');
const closeChatbot = document.getElementById('close-chatbot');
const chatbotMessages = document.getElementById('chatbot-messages');
const chatbotInput = document.getElementById('chatbot-input');
const sendMessageBtn = document.getElementById('send-message');

if (chatbotToggle) {
    chatbotToggle.addEventListener('click', function() {
        chatbotBox.style.display = 'flex';
        chatbotToggle.style.display = 'none';
    });
}

if (closeChatbot) {
    closeChatbot.addEventListener('click', function() {
        chatbotBox.style.display = 'none';
        chatbotToggle.style.display = 'flex';
    });
}

function addMessage(message, isUser) {
    const messageDiv = document.createElement('div');
    messageDiv.classList.add('message');
    messageDiv.classList.add(isUser ? 'user-message' : 'bot-message');
    messageDiv.textContent = message;
    chatbotMessages.appendChild(messageDiv);
    chatbotMessages.scrollTop = chatbotMessages.scrollHeight;
}

function handleUserMessage() {
    const message = chatbotInput.value.trim();
    if (message) {
        addMessage(message, true);
        chatbotInput.value = '';
        
        // Simulate bot response
        setTimeout(() => {
            const responses = [
                "I can help with pesticide identification. Could you describe the symptoms you're seeing on your crops?",
                "For pesticide recommendations, please specify the crop type and the pest problem you're experiencing.",
                "You might want to check our resources section for integrated pest management guides.",
                "I can connect you with one of our experts if you need more detailed assistance.",
                "For immediate help, you can call our support line at +1 (800) 123-4567."
            ];
            const randomResponse = responses[Math.floor(Math.random() * responses.length)];
            addMessage(randomResponse, false);
        }, 1000);
    }
}

if (sendMessageBtn) {
    sendMessageBtn.addEventListener('click', handleUserMessage);
}

if (chatbotInput) {
    chatbotInput.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            handleUserMessage();
        }
    });
}

// Form submission handling
document.getElementById('identification-form')?.addEventListener('submit', function(e) {
    e.preventDefault();
    const form = e.target;
    const formData = new FormData(form);
    const responseDiv = document.getElementById('form-response');
    
    // In a real application, this would be an actual fetch request
    setTimeout(() => {
        responseDiv.innerHTML = `
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative">
                <strong class="font-bold">Success!</strong>
                <span class="block sm:inline">Your identification request has been submitted successfully. We'll contact you within 24 hours.</span>
            </div>
        `;
        form.reset();
        responseDiv.classList.remove('hidden');
    }, 1000);
});

// Contact form submission
document.getElementById('contact-form')?.addEventListener('submit', function(e) {
    e.preventDefault();
    const form = e.target;
    const formData = new FormData(form);
    const responseDiv = document.getElementById('contact-response');
    
    // In a real application, this would be an actual fetch request
    setTimeout(() => {
        responseDiv.innerHTML = `
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative">
                <strong class="font-bold">Thank you!</strong>
                <span class="block sm:inline">Your message has been sent successfully. We'll respond to you shortly.</span>
            </div>
        `;
        form.reset();
        responseDiv.classList.remove('hidden');
    }, 1000);
});