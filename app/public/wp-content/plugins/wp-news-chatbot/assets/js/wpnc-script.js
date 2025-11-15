// Simple, dependency-free widget client
(function () {
  // Elements
  function qs(id) {
    return document.getElementById(id);
  }
  var root,
    toggleBtn,
    windowEl,
    closeBtn,
    messagesEl,
    formEl,
    inputEl,
    sendBtn,
    overlay;

  function init() {
    root = qs("wpnc-chatbot");
    if (!root) return;
    toggleBtn = qs("wpnc-toggle");
    windowEl = qs("wpnc-window");
    closeBtn = qs("wpnc-close");
    messagesEl = qs("wpnc-messages");
    formEl = qs("wpnc-form");
    inputEl = qs("wpnc-input");
    sendBtn = qs("wpnc-send");
    overlay = qs("wpnc-overlay");

    // Show widget
    root.classList.remove("wpnc-hidden");

    toggleBtn.addEventListener("click", function (e) {
      e.stopPropagation();
      if (windowEl.classList.contains("wpnc-open")) {
        closeChat();
      } else {
        openChat();
      }
    });
    closeBtn.addEventListener("click", closeChat);

    // Close when clicking outside
    if (overlay) {
      overlay.addEventListener("click", closeChat);
    }

    // Prevent window clicks from closing
    windowEl.addEventListener("click", function (e) {
      e.stopPropagation();
    });

    formEl.addEventListener("submit", function (e) {
      e.preventDefault();
      sendQuestion();
    });

    // allow Enter in input to send
    inputEl.addEventListener("keydown", function (e) {
      if (e.key === "Enter") {
        e.preventDefault();
        sendQuestion();
      }
    });

    // greet
    postBotMessage(
      "Hi! I'm your site assistant. Ask me anything about this article or the site."
    );
  }

  function openChat() {
    windowEl.classList.add("wpnc-open");
    windowEl.setAttribute("aria-hidden", "false");
    if (overlay) {
      overlay.classList.add("wpnc-overlay-active");
    }
    inputEl.focus();
    // Reset scroll to top when opening
    messagesEl.scrollTop = messagesEl.scrollHeight;
  }

  function closeChat() {
    windowEl.classList.remove("wpnc-open");
    windowEl.setAttribute("aria-hidden", "true");
    if (overlay) {
      overlay.classList.remove("wpnc-overlay-active");
    }
    toggleBtn.focus();
  }

  function appendMessage(text, who) {
    var wrapper = document.createElement("div");
    wrapper.className =
      "wpnc-message " + (who === "user" ? "wpnc-user" : "wpnc-bot");

    if (who === "bot") {
      var avatar = document.createElement("img");
      avatar.className = "wpnc-avatar";
      avatar.alt = "bot";
      avatar.src = wpncData && wpncData.botAvatar ? wpncData.botAvatar : "";
      wrapper.appendChild(avatar);
    }

    var bubble = document.createElement("div");
    bubble.className = "wpnc-bubble-text";
    bubble.innerHTML = text;
    wrapper.appendChild(bubble);

    messagesEl.appendChild(wrapper);
    messagesEl.scrollTop = messagesEl.scrollHeight;
  }

  function postBotMessage(text) {
    appendMessage(text, "bot");
  }

  function postUserMessage(text) {
    appendMessage(text, "user");
  }

  function showTyping() {
    var typing = document.createElement("div");
    typing.className = "wpnc-typing";
    typing.innerHTML =
      '<span class="dot"></span><span class="dot"></span><span class="dot"></span>';
    messagesEl.appendChild(typing);
    messagesEl.scrollTop = messagesEl.scrollHeight;
    return typing;
  }

  function removeTyping(el) {
    if (el && el.parentNode === messagesEl) messagesEl.removeChild(el);
  }

  function sendQuestion() {
    var question = inputEl.value.trim();
    if (!question) return;

    // Disable input while sending
    inputEl.disabled = true;
    sendBtn.disabled = true;

    postUserMessage(question);
    inputEl.value = "";
    var typingEl = showTyping();

    var payload = { question: question };
    var apiUrl =
      wpncData && wpncData.rest_url
        ? wpncData.rest_url
        : "/wp-json/wpnewschatbot/v1/message";

    console.log("WPNC Sending request to:", apiUrl);
    console.log("WPNC Payload:", payload);

    fetch(apiUrl, {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
        "X-WP-Nonce": wpncData && wpncData.nonce ? wpncData.nonce : "",
      },
      body: JSON.stringify(payload),
    })
      .then(function (resp) {
        console.log("WPNC Response status:", resp.status);
        return resp.json();
      })
      .then(function (data) {
        console.log("WPNC Response data:", data);
        removeTyping(typingEl);
        if (data && data.answer) {
          postBotMessage(data.answer);
        } else {
          postBotMessage("Sorry, I couldn't get an answer. Try again later.");
        }
      })
      .catch(function (err) {
        removeTyping(typingEl);
        postBotMessage("Network error. Try again later.");
        console.error("WP News Chatbot error:", err);
      })
      .finally(function () {
        // Re-enable input and button
        inputEl.disabled = false;
        sendBtn.disabled = false;
        inputEl.focus();
      });
  }

  // DOM ready
  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", init);
  } else {
    init();
  }
})();
