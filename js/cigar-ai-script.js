// File: js/cigar-ai-script.js
jQuery(document).ready(function($) {
    const messagesContainer = $('#cigar-ai-messages');
    const userInput = $('#cigar-ai-user-input');
    const sendButton = $('#cigar-ai-send');
    const recommendationsContainer = $('#cigar-ai-recommendations');

    function addMessage(message, isAI = false) {
        const messageElement = $('<div>').addClass('message').text(message);
        if (isAI) {
            messageElement.addClass('ai-message');
        } else {
            messageElement.addClass('user-message');
        }
        messagesContainer.append(messageElement);
        messagesContainer.scrollTop(messagesContainer[0].scrollHeight);
    }

    function displayRecommendations(recommendations) {
        recommendationsContainer.empty();
        recommendations.forEach(product => {
            const productElement = $('<div>').addClass('product-recommendation');
            const imageElement = $('<img>').attr('src', product.image).attr('alt', product.name);
            const nameElement = $('<h3>').text(product.name);
            const descriptionElement = $('<p>').text(product.description);
            const linkElement = $('<a>').attr('href', product.url).text('View Product');

            productElement.append(imageElement, nameElement, descriptionElement, linkElement);
            recommendationsContainer.append(productElement);
        });
    }

    function sendMessage() {
        const message = userInput.val().trim();
        if (message === '') return;

        addMessage(message);
        userInput.val('');

        $.ajax({
            url: cigar_ai_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'send_ai_message',
                nonce: cigar_ai_ajax.nonce,
                message: message
            },
            success: function(response) {
                if (response.success) {
                    addMessage(response.data.message, true);
                    if (response.data.recommendations) {
                        displayRecommendations(response.data.recommendations);
                    }
                } else {
                    addMessage('Error: ' + response.data, true);
                }
            },
            error: function() {
                addMessage('Error communicating with the server', true);
            }
        });
    }

    sendButton.on('click', sendMessage);
    userInput.on('keypress', function(e) {
        if (e.which === 13) {
            sendMessage();
        }
    });
});