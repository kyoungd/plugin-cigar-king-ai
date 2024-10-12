jQuery(document).ready(function($) {
    const messagesContainer = $('#cigar-ai-messages');
    const userInput = $('#cigar-ai-user-input');
    const recommendationsContainer = $('#cigar-ai-recommendations');
    let initialResponse = null;
    let initialCallMade = false; // Track if initial call has been made

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

    function makeInitialCall() {
        console.log('makeInitialCall called');
        $('#cigar-ai-loading').show();
        userInput.prop('disabled', true); // Disable the input field
        $.ajax({
            url: cigar_ai_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'cigar_ai_initial_call',
                nonce: cigar_ai_ajax.nonce,
                subscription_external_id: cigar_ai_ajax.api_key,
                timeZone: Intl.DateTimeFormat().resolvedOptions().timeZone,
                caller: {},
                caller_domain: window.location.hostname
            },
            success: function(response) {
                $('#cigar-ai-loading').hide();
                userInput.prop('disabled', false); // Enable the input field
                if (response.success) {
                    initialResponse = response.data;
                    console.log('Initial call successful:', initialResponse);
                    initialCallMade = true;
                } else {
                    console.error('Initial call failed:', response.data);
                    addMessage('Error initializing chat. Please try again later.', true);
                }
            },
            error: function(xhr, status, error) {
                $('#cigar-ai-loading').hide();
                console.error('Error making initial call:', error);
                addMessage('Error initializing chat. Please try again later.', true);
            }
        });
    }

    function sendMessage() {
        const message = userInput.val().trim();
        if (message === '') return;

        addMessage(message);
        userInput.val('');

        // Check if initialResponse is ready
        if (initialResponse === null) {
            addMessage('Please wait, initializing chat...', true);
            // Wait for initialResponse to be ready
            let checkInitialResponse = setInterval(function() {
                if (initialResponse !== null) {
                    clearInterval(checkInitialResponse);
                    proceedWithMessage(message);
                }
            }, 100);
        } else {
            proceedWithMessage(message);
        }
    }

    function proceedWithMessage(message) {
        $.ajax({
            url: cigar_ai_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'send_ai_message',
                nonce: cigar_ai_ajax.nonce,
                message: message,
                data: initialResponse // Include the initial response data
            },
            success: function(response) {
                if (response.success) {
                    addMessage(response.data.message, true);
                    initialResponse = response.data;
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

    userInput.on('keypress', function(e) {
        if (e.which === 13) {
            sendMessage();
        }
    });

    // Make the initial call when the user focuses on the input field for the first time
    userInput.one('focus', function() {
        if (!initialCallMade) {
            console.log('Input field focused. Making initial AI call.');
            makeInitialCall();
        }
    });
});
