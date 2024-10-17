let lastTimestamp = 0;

function sendMessage() {
    const input = document.getElementById('message-input');
    const message = input.value.trim();
    if (message) {
        input.value = '';

        fetch('chat_server.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'message=' + encodeURIComponent(message)
        })
        .then(response => response.json())
        .then(data => {
            if (data.error) {
                console.error('Error al enviar mensaje:', data.error);
            }
        })
        .catch(error => {
            console.error('Error:', error);
        });
    }
}

function pollMessages() {
    fetch(`chat_server.php?timestamp=${lastTimestamp}`)
        .then(response => response.json())
        .then(data => {
            const chatBox = document.getElementById('chat-box');
            chatBox.innerHTML = data.messages.replace(/\n/g, '<br>');
            chatBox.scrollTop = chatBox.scrollHeight;
            lastTimestamp = data.timestamp;
            setTimeout(pollMessages, 1000); // Esperar 1 segundo antes de la siguiente solicitud
        })
        .catch(error => {
            console.error('Error:', error);
            setTimeout(pollMessages, 5000); // Reintentar después de 5 segundos en caso de error
        });
}

// Iniciar el long polling
pollMessages();