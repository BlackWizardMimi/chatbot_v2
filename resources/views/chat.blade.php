<!DOCTYPE html>
<html>
<head>
    <title>Business Intelligence Chatbot</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f5f5f5;
        }
        #chatbox {
            width: 500px;
            height: 600px; /* increased height for more content */
            margin: 15px auto;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 6px rgba(0,0,0,0.2);
            display: flex;
            flex-direction: column;
        }
        #messages {
            flex: 1;
            overflow-y: auto;
            padding: 15px;
            margin-bottom: 5px;
            margin-top: 5px;
        }
        #chatbox_position {
            display: flex;
            padding: 10px;
            border-top: 1px solid #ddd;
        }
        input[type=text] {
            flex: 1;
            padding: 13px;
            border: 1px solid #ccc;
            border-radius: 4px;
        }
        button {
            margin-left: 5px;
            padding: 13px 17px;
            border: none;
            background: #4CAF50;
            color: white;
            border-radius: 4px;
            cursor: pointer;
        }
        button:hover {
            background: #45a049;
        }
        .message {
            margin: 8px 0;
            word-wrap: break-word;
        }
        .user { 
            text-align: right; 
            color: #2196F3; 
            background: #e3f2fd;
            padding: 8px;
            border-radius: 10px;
            margin-left: 50px;
        }
        .bot { 
            text-align: left; 
            color: #4CAF50;
            background: #f1f8e9;
            padding: 8px;
            border-radius: 10px;
            margin-right: 50px;
        }
        .div_select_chatbot{
            margin-top: 35px;
        }
        .select_chatbot{
            padding: 7px;
            border-radius: 4px;
            border: 1px solid #ccc;
        }
        .quick-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 5px;
            padding: 10px;
            border-top: 1px solid #eee;
            background: #f9f9f9;
        }
        .quick-btn {
            padding: 8px 12px;
            background: #2196F3;
            color: white;
            border: none;
            border-radius: 15px;
            cursor: pointer;
            font-size: 12px;
            transition: background 0.3s;
        }
        .quick-btn:hover {
            background: #1976D2;
        }
        .business-insight {
            background: #fff3e0;
            border-left: 4px solid #ff9800;
            padding: 10px;
            margin: 5px 0;
            border-radius: 4px;
        }
        .chart-icon::before { content: "📊 "; }
        .marketing-icon::before { content: "🎯 "; }
        .recommendation-icon::before { content: "💡 "; }
        .welcome-message {
            background: #e8f5e8;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 10px;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="div_select_chatbot">
        <center>
        <select class="select_chatbot" id="chatbot-mode">
            <option value="business">🤖 Business Intelligence Bot</option>
            <option value="general">💬 General Chat (LLaMA3)</option>
        </select>
        </center>
    </div>
    
    <div id="chatbox">
        <div id="messages">
            <div class="welcome-message">
                <h3>🤖 Business Intelligence Assistant</h3>
                <p>I can analyze your customer data and provide business insights!</p>
                <small>Try the quick actions below or ask me about your customers</small>
            </div>
        </div>
        
        <!-- Quick Action Buttons -->
        <div class="quick-actions">
            <button class="quick-btn chart-icon" onclick="quickAction('analyze customers')">Customer Analysis</button>
            <button class="quick-btn marketing-icon" onclick="quickAction('marketing insights')">Marketing Tips</button>
            <button class="quick-btn recommendation-icon" onclick="quickAction('business recommendations')">Recommendations</button>
            <button class="quick-btn" onclick="quickAction('quick stats')">📈 Quick Stats</button>
        </div>
        
        <div id="chatbox_position">
            <input type="text" id="message" name="message" placeholder="Ask about customers, marketing, or business insights..." />
            <button onclick="sendMessage()">Send</button>
        </div>
    </div>
    
    <script>
        var messageCounter = 1;
        
        // Handle enter key press
        document.getElementById("message").addEventListener("keypress", function(event) {
            if (event.key === "Enter") {
                sendMessage();
            }
        });
        
        // Quick action function
        function quickAction(message) {
            document.getElementById("message").value = message;
            sendMessage();
        }
        
        async function sendMessage() {
            messageCounter += 1;
            let message = document.getElementById("message").value;
            if (!message.trim()) return;
            
            let messagesDiv = document.getElementById("messages");
            let chatMode = document.getElementById("chatbot-mode").value;
            
            // Show user message with better styling
            let userDiv = document.createElement("div");
            userDiv.className = "message user";
            userDiv.innerHTML = `<b>You:</b> ${message}`;
            messagesDiv.appendChild(userDiv);
            
            document.getElementById("message").value = "";
            
            // Show typing indicator
            let typingDiv = document.createElement("div");
            typingDiv.setAttribute("id", "typing" + messageCounter);
            typingDiv.className = "message bot";
            typingDiv.innerHTML = "<i>🤔 Analyzing your business data...</i>";
            messagesDiv.appendChild(typingDiv);
            messagesDiv.scrollTop = messagesDiv.scrollHeight;
            
            await new Promise(resolve => setTimeout(resolve, 500)); // Show thinking time
            
            try {
                let endpoint = chatMode === 'business' ? '/api/business-chat' : '/api/chat';
                
                let response = await fetch(endpoint, {
                    method: "POST",
                    headers: { 
                        "Content-Type": "application/json",
                        "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify({ 
                        message: message,
                        mode: chatMode 
                    })
                });
                
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                
                let data = await response.json();
                
                // Replace typing indicator with bot response
                typingDiv.className = "message bot";
                typingDiv.innerHTML = `<b>🤖 Business Bot:</b> <span id='bot-reply${messageCounter}'></span>`;
                
                let replySpan = document.getElementById("bot-reply" + messageCounter);
                let botReply = data.reply || data.response || "Sorry, I couldn't process that.";
                
                // Format business insights with better styling
                if (chatMode === 'business' && (message.includes('analyz') || message.includes('insight') || message.includes('recommend'))) {
                    // Add business insight styling for analysis responses
                    typingDiv.classList.add('business-insight');
                    
                    // Type out response word by word with faster speed for business data
                    let words = botReply.split(" ");
                    let i = 0;
                    let typingInterval = setInterval(() => {
                        replySpan.innerHTML += words[i] + " ";
                        i++;
                        messagesDiv.scrollTop = messagesDiv.scrollHeight;
                        if (i >= words.length) {
                            clearInterval(typingInterval);
                            // Add follow-up suggestions after business analysis
                            if (message.includes('analyz')) {
                                setTimeout(() => {
                                    addFollowUpSuggestions(messagesDiv);
                                }, 1000);
                            }
                        }
                    }, 50); // Faster typing for business data
                } else {
                    // Regular typing animation for general chat
                    let words = botReply.split(" ");
                    let i = 0;
                    let typingInterval = setInterval(() => {
                        replySpan.innerHTML += words[i] + " ";
                        i++;
                        messagesDiv.scrollTop = messagesDiv.scrollHeight;
                        if (i >= words.length) clearInterval(typingInterval);
                    }, 100);
                }
                
            } catch (error) {
                console.error('Error:', error);
                typingDiv.innerHTML = "<i>❌ Error: Could not connect to business intelligence system. Make sure your Laravel server is running.</i>";
            }
        }
        
        function addFollowUpSuggestions(messagesDiv) {
            let suggestionsDiv = document.createElement("div");
            suggestionsDiv.className = "message";
            suggestionsDiv.innerHTML = `
                <div style="background: #f0f8ff; padding: 10px; border-radius: 8px; margin-top: 10px;">
                    <small><b>💡 What would you like to explore next?</b></small><br>
                    <div style="margin-top: 8px;">
                        <button class="quick-btn" onclick="quickAction('marketing insights')" style="margin: 2px;">Marketing Tips</button>
                        <button class="quick-btn" onclick="quickAction('business recommendations')" style="margin: 2px;">Get Recommendations</button>
                        <button class="quick-btn" onclick="quickAction('show top customers')" style="margin: 2px;">Top Customers</button>
                    </div>
                </div>
            `;
            messagesDiv.appendChild(suggestionsDiv);
            messagesDiv.scrollTop = messagesDiv.scrollHeight;
        }
        
        // Change placeholder text based on mode
        document.getElementById("chatbot-mode").addEventListener("change", function() {
            let messageInput = document.getElementById("message");
            let mode = this.value;
            
            if (mode === 'business') {
                messageInput.placeholder = "Ask about customers, marketing, or business insights...";
            } else {
                messageInput.placeholder = "Type your message...";
            }
        });
    </script>
</body>
</html>