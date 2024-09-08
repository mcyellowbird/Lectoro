<?php
require dirname(dirname(__DIR__)) . '/vendor/autoload.php'; // Adjusted path

// Connect to MongoDB
$mongoClient = new MongoDB\Client("mongodb://localhost:27017");
$database = $mongoClient->selectDatabase("CSIT321Development");
$usersCollection = $database->selectCollection("users");

// Assuming user is logged in and we have their ID
$loggedInUserId = $_SESSION['_id'];
?>

<!DOCTYPE html>
<html lang="en">

<body>
    <div id="chatInterface" class="flex justify-center flex-row w-full h-full">
        <div id="conversationList" class="min-w-1/4 w-1/4 p-2.5 overflow-y-auto">
            <div class="searchBar">
                <i class="searchIcon bx bx-search"></i>
                <input type="text" id="searchBar" placeholder="Search for users..." class="searchInput">
            </div>
            <div id="searchResults" class="border-textAccent rounded-lg max-h-80 overflow-y-auto"></div>
            <div id="conversations" class="flex flex-col gap-2">
                <span class="mt-8 text-center block pt-4">Current Chats</span>
                <div id="loadingSpinner" class="flex w-full justify-center">
                    <svg aria-hidden="true" class="w-8 h-8 animate-spin text-textColour fill-accent" viewBox="0 0 100 101" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M100 50.5908C100 78.2051 77.6142 100.591 50 100.591C22.3858 100.591 0 78.2051 0 50.5908C0 22.9766 22.3858 0.59082 50 0.59082C77.6142 0.59082 100 22.9766 100 50.5908ZM9.08144 50.5908C9.08144 73.1895 27.4013 91.5094 50 91.5094C72.5987 91.5094 90.9186 73.1895 90.9186 50.5908C90.9186 27.9921 72.5987 9.67226 50 9.67226C27.4013 9.67226 9.08144 27.9921 9.08144 50.5908Z" fill="currentColor"></path>
                        <path d="M93.9676 39.0409C96.393 38.4038 97.8624 35.9116 97.0079 33.5539C95.2932 28.8227 92.871 24.3692 89.8167 20.348C85.8452 15.1192 80.8826 10.7238 75.2124 7.41289C69.5422 4.10194 63.2754 1.94025 56.7698 1.05124C51.7666 0.367541 46.6976 0.446843 41.7345 1.27873C39.2613 1.69328 37.813 4.19778 38.4501 6.62326C39.0873 9.04874 41.5694 10.4717 44.0505 10.1071C47.8511 9.54855 51.7191 9.52689 55.5402 10.0491C60.8642 10.7766 65.9928 12.5457 70.6331 15.2552C75.2735 17.9648 79.3347 21.5619 82.5849 25.841C84.9175 28.9121 86.7997 32.2913 88.1811 35.8758C89.083 38.2158 91.5421 39.6781 93.9676 39.0409Z" fill="currentFill"></path>
                    </svg>
                    <span class="sr-only">Loading...</span>
                </div>
            </div>
        </div>
        <div id="chatWindow" class="shadow-chatWindow rounded-lg border border-menu flex flex-1 p-8 flex-col max-w-[74%]">
            <div id="selectedUserName"></div>
            <div id="messages" class="messageContainer"></div>
            <div id="messageBox" class="searchBar">
                <i class="searchIcon bx bxs-message text-textAccent self-start pt-3"></i>
                <textarea type="text" id="messageInput" placeholder="Type a message..." class="searchInput"></textarea>
                <button id="sendMessageButton" class="sendButton"><i class="sendIcon bx bx-send"></i></button>
            </div>
        </div>
    </div>

    <script>
        $(document).ready(function () {
            const loggedInUserId = '<?php echo $loggedInUserId; ?>';
            var conversationIdentifier;
            // const socket = new WebSocket('ws://localhost:8080'); // Update if server runs on a different address
            // socket.onopen = function () {
            //     console.log('WebSocket connection established');
            // };
            // socket.onmessage = function (event) {
            //     const data = JSON.parse(event.data);

            //     // Ensure data contains necessary fields
            //     if (data.conversationId && data.sender_id && data.message) {
            //         const conversationId = data.conversationId;
            //         const senderId = data.sender_id;
            //         const message = data.message;

            //         // If the active conversation is the one where the message was sent, load messages
            //         const activeConversation = $('.activeConversation').data('conversationId');
            //         if (conversationId === activeConversation) {
            //             loadMessages(conversationId);
            //         } else {
            //             // Update the conversation list to mark the conversation as active if necessary
            //             refreshConversations();
            //         }
            //     }
            // };

            // socket.onerror = function (error) {
            //     console.error('WebSocket Error:', error);
            // };

            // socket.onclose = function () {
            //     console.log('WebSocket connection closed');
            // };

            function searchUsers() {
                const query = $('#searchBar').val();

                $.get('./src/events/chat/searchUsers.php', { query: query })
                    .done(function (data) {
                        const searchResultsDiv = $('#searchResults');
                        searchResultsDiv.empty();

                        if (query != "") {
                            data.forEach(user => {
                                const userDiv = $('<div></div>')
                                    .addClass('cursor-pointer p-1.5 hover:bg-buttonHover')
                                    .text(`${user.first_name} ${user.last_name}`)
                                    .data('userId', user.id)
                                    .click(() => openChat(user.id));
                                searchResultsDiv.append(userDiv);
                            });
                        }
                    })
                    .fail(function (error) {
                        console.error('Error:', error);
                    });
            }

            function openChat(userId) {
                $.get('./src/events/chat/getConversation.php', { id: userId })
                    .done(function (data) {
                        data = JSON.parse(data);
                        if (data.error) {
                            console.error('Error fetching conversation:', data.error);
                            return;
                        }
                        const conversationDiv = $('#conversations');

                        if (data.conversationId) {
                            conversationIdentifier = data.conversationId;
                            loadMessages(data.conversationId, function () {
                                const messagesDiv = $('#messages');
                                messagesDiv.scrollTop(messagesDiv[0].scrollHeight);
                            })
                            
                            const messagesDiv = $('#messages');
                            messagesDiv.scrollTop(messagesDiv[0].scrollHeight);

                            $('.activeConversation').removeClass('activeConversation');

                            const userDiv = conversationDiv.children().filter(function () {
                                return $(this).data('userId') === userId;
                            });
                            
                            if (userDiv.length) {
                                userDiv.addClass('activeConversation');
                            } else {
                                console.error('User not found in conversation list:', userId);
                            }

                            // Initialize WebSocket connection
                            // initializeWebSocket(data.conversationId);
                        } else {
                            createConversation(userId).then(conversationId => {
                                loadMessages(conversationId);
                                // initializeWebSocket(conversationId);
                            }).catch(error => console.error('Error creating conversation:', error));
                        }
                    })
                    .fail(function (error) {
                        console.error('Error:', error);
                    });
            }

            function createConversation(userId) {

                return $.post('./src/events/chat/createConversation.php', { userId: userId })
                    .done(function (data) {
                        data = JSON.parse(data);
                        if (data.success) {
                            return data.conversationId;
                        } else {
                            throw new Error(data.error || 'Failed to create conversation');
                        }
                    })
                    .fail(function (error) {
                        console.error('Error creating conversation:', error);
                        throw error;
                    });
            }

            function loadMessages(conversationId, callback) {
                $.get('./src/events/chat/getMessages.php', { conversationId: conversationId })
                    .done(function (data) {
                        data = JSON.parse(data);
                        const messagesDiv = $('#messages');
                        messagesDiv.empty();

                        let lastDate = null; // Variable to keep track of the last message date

                        data.forEach(message => {
                            // Convert UTC timestamp to local time
                            const utcDate = new Date(message.timestamp);
                            
                            // Local time conversion
                            const localDate = new Date(utcDate.getTime() - (utcDate.getTimezoneOffset() * 60000));

                            const optionsTime = { hour: '2-digit', minute: '2-digit', hour12: true };
                            const timeString = localDate.toLocaleTimeString('en-US', optionsTime);
                            
                            const optionsDate = { weekday: 'short', month: 'short', day: 'numeric' };
                            const dateString = localDate.toLocaleDateString('en-US', optionsDate);

                            // Check if the message date is different from the last one
                            const messageDate = localDate.toLocaleDateString();
                            if (lastDate !== messageDate) {
                                // Add day separator
                                $('<div></div>')
                                    .addClass('text-center block my-2.5 text-buttonHover')
                                    .text(dateString)
                                    .appendTo(messagesDiv);
                                lastDate = messageDate;
                            }

                            // Create and append message elements
                            const parentContainer = $('<div></div>')
                                .addClass('flex flex-row');

                            const messageContainer = $('<div></div>')
                                .addClass('flex flex-col !max-w-60p')
                                .addClass(message.senderId === loggedInUserId ? 'self-end' : 'self-start');

                            const messageInner = $('<div></div>')
                                .addClass('flex flex-row mt-0.5 mb-0.5');

                            const messageDiv = $('<div></div>')
                                .addClass(message.senderId === loggedInUserId ? 'message-from-user' : 'message-to-user');

                            messageDiv.append($('<span></span>').text(message.message));

                            const timeDiv = $('<div></div>')
                                .addClass('text-buttonHover text-xs min-w-fit pt-3')
                                .text(timeString);

                            const emptyContainer = $('<div></div>')
                                .addClass('shrink-0 flex-grow'); // This will take the remaining width

                            // Swap emptyContainer and messageContainer if the message is from the other user
                            if (message.senderId === loggedInUserId) {
                                timeDiv.addClass('mr-2')
                                messageInner.append(timeDiv);
                                messageInner.append(messageDiv);
                                messageContainer.append(messageInner);
                                
                                parentContainer.append(emptyContainer);
                                parentContainer.append(messageContainer);
                                parentContainer.append($('<i class="bx bxs-user pl-1.5 pr-1.5 text-xl"></i>'));
                            } else {
                                timeDiv.addClass('ml-2')
                                messageInner.append(messageDiv);
                                messageInner.append(timeDiv);
                                messageContainer.append(messageInner);

                                parentContainer.append($('<i class="bx bxs-user pr-1.5 text-xl"></i>'));
                                parentContainer.append(messageContainer);
                                parentContainer.append(emptyContainer);
                            }

                            messagesDiv.append(parentContainer);
                        });
                    })
                    .done(function() {
                        if (typeof callback === 'function') {
                            callback();
                        }
                    })
                    .fail(function (error) {
                        console.error('Error:', error);
                    });
            }

            // function initializeWebSocket(conversationId) {
            //     // Close any existing connection
            //     if (socket) {
            //         socket.close();
            //     }

            //     // Open new connection
            //     socket = new WebSocket('ws://localhost:8080');

            //     socket.onopen = () => {
            //         console.log('WebSocket connection opened');
            //         // Send initial message or subscribe to conversation if needed
            //         socket.send(JSON.stringify({ action: 'subscribe', conversationId: conversationId }));
            //     };

            //     socket.onmessage = (event) => {
            //         const data = JSON.parse(event.data);
            //         console.log('WebSocket message received:', data);

            //         if (data.action === 'message') {
            //             loadMessages(conversationId); // Reload messages when a new one arrives
            //         }
            //     };

            //     socket.onerror = (error) => {
            //         console.error('WebSocket Error:', error);
            //     };

            //     socket.onclose = () => {
            //         console.log('WebSocket connection closed');
            //     };
            // }

            // setInterval(() => {
            //     const activeConversation = $('.activeConversation').data('conversationId');
            //     if (activeConversation) {
            //         loadMessages(activeConversation);
            //     }
            // }, 5000);

            const sendMessageButton = $('#sendMessageButton');
            
            const messageInput = $('#messageInput');

            function sendMessage() {
                const message = messageInput.val().trim();
                const activeConversation = $('.activeConversation').data('conversationId');

                if (activeConversation && message.length > 0) {
                    $.post('./src/events/chat/sendMessage.php', {
                        conversationId: activeConversation,
                        message: message
                    })
                    .done(function (data) {
                        try {
                            if (data.success) {
                                // Send message over WebSocket
                                // if (socket) {
                                //     socket.send(JSON.stringify({
                                //         action: 'message',
                                //         conversationId: activeConversation,
                                //         message: message
                                //     }));
                                // }
                                $('#messageInput').val('');
                                loadMessages(conversationIdentifier, function () {
                                    const messagesDiv = $('#messages');
                                    messagesDiv.scrollTop(messagesDiv[0].scrollHeight);
                                });
                            } else {
                                console.error('Failed to send message:', data.error);
                            }
                        } catch (e) {
                            console.error('Failed to parse JSON response:', e);
                            console.error('Response:', data);
                        }
                    })
                    .fail(function (error) {
                        console.error('Error:', error);
                    });
                } else {
                    console.error('No conversation selected');
                }
            };
            
            sendMessageButton.click(function() {sendMessage();});

            messageInput.on('keypress', function(e) {
                if(e.which === 13 && !e.shiftKey) {
                    e.preventDefault(); // Prevent the default action (new line)
                    sendMessage();
                }
            });

            function refreshConversations() {
                $.get('./src/events/chat/getConversationList.php')
                    .done(function (data) {
                        data = JSON.parse(data);
                        const conversationsDiv = $('#conversations');
                        if (conversationsDiv) {
                            const searchBar = $('#searchBar');
                            data.forEach(conversation => {
                                const userDiv = $('<div></div>')
                                    .addClass('flex items-center rounded-md cursor-pointer p-1.5 hover:bg-buttonHover/60')
                                    .text(conversation.display_name)
                                    .data('userId', conversation.userId)
                                    .data('conversationId', conversation.conversationId)
                                    .click(() => openChat(conversation.userId));
                                
                                if (lastMessageTimestamps[conversation.conversationId] &&
                                    (!$('.activeConversation').data('conversationId') || 
                                    new Date(lastMessageTimestamps[conversation.conversationId]) > new Date(lastMessageTimestamps[$('.activeConversation').data('conversationId')]))) {
                                    $('.activeConversation').removeClass('activeConversation');
                                    userDiv.addClass('activeConversation');
                                }

                                conversationsDiv.append(userDiv);
                                userDiv.prepend($('<i class="bx bxs-user pr-1.5 text-xl"></i>'));
                            });

                            $('#searchBar').on('input', searchUsers);
                        }
                    })
                    .fail(function (error) {
                        console.error('Error:', error);
                    });
            }


            const loadingSpinner = $('#loadingSpinner');
            loadingSpinner.show();
            const conversationsDiv = $('#conversations');

            $.get('./src/events/chat/getConversationList.php')
                .done(function(data) {
                    data = JSON.parse(data);

                    if (conversationsDiv) {
                        const searchBar = $('#searchBar');
                        data.forEach(conversation => {
                            const userDiv = $('<div></div>')
                                .addClass('flex items-center rounded-md cursor-pointer p-1.5 hover:bg-buttonHover/60')
                                .text(conversation.display_name)
                                .data('userId', conversation.userId)
                                .data('conversationId', conversation.conversationId)
                                .click(() => openChat(conversation.userId));
                                
                            conversationsDiv.append(userDiv);
                            userDiv.prepend($('<i class="bx bxs-user pr-1.5 text-xl"></i>'));
                        });

                        $('#searchBar').on('input', searchUsers);
                    }
                })
                .fail(function(error) {
                    console.error('Error:', error);
                })
                .always(function() {
                    // Hide spinner
                    loadingSpinner.hide();
                });
        });
    </script>
</body>

</html>
