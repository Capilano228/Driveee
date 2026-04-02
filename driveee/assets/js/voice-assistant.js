let recognition = null;
let isListening = false;
let wakeWordDetected = false;

function initSpeechRecognition() {
    if (!('webkitSpeechRecognition' in window) && !('SpeechRecognition' in window)) {
        console.warn('Speech recognition not supported');
        return false;
    }
    const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
    recognition = new SpeechRecognition();
    recognition.lang = 'ru-RU';
    recognition.continuous = true;
    recognition.interimResults = true;
    
    recognition.onresult = function(event) {
        let final = '';
        for (let i = event.resultIndex; i < event.results.length; i++) {
            if (event.results[i].isFinal) final += event.results[i][0].transcript.toLowerCase();
        }
        if (final) processVoiceCommand(final);
    };
    
    recognition.onerror = function() { 
        addAssistantMessage('Ошибка распознавания', 'assistant'); 
    };
    
    recognition.onend = function() { 
        if (wakeWordDetected) {
            setTimeout(() => { if (wakeWordDetected) recognition.start(); }, 100);
        }
    };
    return true;
}

function processVoiceCommand(command) {
    if (command.includes('драйвик')) {
        wakeWordDetected = true;
        addAssistantMessage('Драйвик слушает! Чем могу помочь?', 'assistant');
        speak('Драйвик слушает!');
        if (!isListening) startContinuousListening();
        return;
    }
    
    if (wakeWordDetected || command.includes('закажи') || command.includes('сколько') || command.includes('квест')) {
        fetch('/api/voice-command.php', {
            method: 'POST', 
            headers: { 'Content-Type': 'application/json' }, 
            body: JSON.stringify({ command: command })
        })
        .then(r => r.json())
        .then(data => {
            addAssistantMessage(data.response, 'assistant');
            speak(data.response);
            if (data.action === 'order' && data.address) {
                setTimeout(() => confirmOrder(data.address), 1000);
            }
        });
    }
}

function confirmOrder(address) {
    addAssistantMessage('Подтверждаю заказ...', 'assistant');
    speak('Подтверждаю заказ');
    const dropoffField = document.getElementById('dropoffAddress');
    if (dropoffField) {
        dropoffField.value = address;
        if (typeof ymaps !== 'undefined') {
            ymaps.geocode(address).then(res => {
                const coords = res.geoObjects.get(0).geometry.getCoordinates();
                if (document.getElementById('dropoffLat')) {
                    document.getElementById('dropoffLat').value = coords[0];
                    document.getElementById('dropoffLng').value = coords[1];
                    if (typeof calculatePrice === 'function') calculatePrice();
                }
                setTimeout(() => { 
                    if (confirm(`Заказать такси до ${address}?`) && typeof orderRide === 'function') {
                        orderRide();
                    }
                }, 500);
            });
        } else if (typeof orderRide === 'function') {
            setTimeout(() => orderRide(), 500);
        }
    }
}

function startVoiceRecognition() {
    if (!recognition && !initSpeechRecognition()) { 
        alert('❌ Ваш браузер не поддерживает голосовое управление. Используйте Chrome.'); 
        return; 
    }
    if (isListening) {
        recognition.stop();
        isListening = false;
        wakeWordDetected = false;
        addAssistantMessage('🔇 Голосовое управление отключено', 'assistant');
        speak('Голосовое управление отключено');
    } else {
        recognition.start();
        isListening = true;
        addAssistantMessage('🎤 Слушаю... Скажите "Драйвик"', 'assistant');
        speak('Слушаю, скажите Драйвик');
    }
}

function startContinuousListening() {
    if (recognition && !isListening) { 
        recognition.start(); 
        isListening = true; 
    }
}

function toggleVoiceAssistant() {
    const win = document.querySelector('.assistant-window');
    if (win) {
        if (win.style.display === 'none') { 
            win.style.display = 'flex'; 
            initSpeechRecognition(); 
        } else { 
            win.style.display = 'none'; 
            if (recognition) recognition.stop(); 
            isListening = false; 
            wakeWordDetected = false; 
        }
    }
}

function closeAssistant() {
    const win = document.querySelector('.assistant-window');
    if (win) win.style.display = 'none';
    if (recognition) recognition.stop();
    isListening = false;
    wakeWordDetected = false;
}

function addAssistantMessage(text, sender) {
    const container = document.getElementById('assistantMessages');
    if (!container) return;
    const msg = document.createElement('div');
    msg.className = `message ${sender}`;
    msg.textContent = text;
    container.appendChild(msg);
    container.scrollTop = container.scrollHeight;
    while (container.children.length > 20) container.removeChild(container.firstChild);
}

function handleTextCommand(e) {
    if (e.key === 'Enter') {
        const input = document.getElementById('voiceInput');
        if (input && input.value) {
            addAssistantMessage(input.value, 'user');
            processVoiceCommand(input.value);
            input.value = '';
        }
    }
}

function speak(text) {
    if ('speechSynthesis' in window) {
        window.speechSynthesis.cancel();
        const utterance = new SpeechSynthesisUtterance(text);
        utterance.lang = 'ru-RU';
        utterance.rate = 0.9;
        window.speechSynthesis.speak(utterance);
    }
}

document.addEventListener('DOMContentLoaded', () => { 
    initSpeechRecognition(); 
    if ('speechSynthesis' in window) window.speechSynthesis.getVoices();
});

window.startVoiceRecognition = startVoiceRecognition;
window.toggleVoiceAssistant = toggleVoiceAssistant;
window.closeAssistant = closeAssistant;
window.handleTextCommand = handleTextCommand;