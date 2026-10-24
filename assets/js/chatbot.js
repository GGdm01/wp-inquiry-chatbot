/**
 * WP Inquiry Chatbot (ECV Style) - Vanilla JavaScript Client
 * Features:
 * - Multi-turn Probing Question Engine (发散式追问表单流)
 * - Strict Email Gate: Enforces valid email with '@'
 * - Buyer Dossier Tracking: Dwell time, footprint, referrer
 * - Instant Feishu Interactive Card Synthesis
 */
(function () {
    'use strict';

    if (typeof WPI_CONFIG === 'undefined') {
        return;
    }

    // Language and i18n dictionary resolution
    const configuredLang = WPI_CONFIG.language || 'en';
    const isBrowserZh = (typeof navigator !== 'undefined' && navigator.language && navigator.language.toLowerCase().startsWith('zh'));
    const activeLang = (configuredLang === 'zh_CN' || (configuredLang === 'auto' && isBrowserZh)) ? 'zh' : 'en';

    const i18nDict = {
        en: {
            bot_name: 'Sales Specialist',
            status_text: 'Online | Direct Factory Reply',
            context_tag: 'Currently Viewing:',
            input_placeholder: 'Type WhatsApp, Email or question...',
            footer_note: 'Secured & Direct to Manufacturer',
            close_title: 'Minimize',
            launcher_aria: 'Open Inquiry Chat',
            placeholder_default: 'Type your answer or click below...',
            placeholder_email: 'Enter Name & Email with @...',
            placeholder_notes: 'Type additional notes for our engineer...',
            note_added: 'Note added to your inquiry.',
            faq_follow_up: 'By the way, could you also help with our previous question:',
            email_required_error: 'Please provide a valid business email containing "@" (e.g. name@company.com) so our engineer can send the specifications.',
            email_test_error: 'Please provide a reachable business email address. Test accounts cannot receive official quotes and specifications.',
            email_fallback: 'May I have your name and business Email with "@" so our engineer can send the specifications?',
            lead_success_msg: 'Thank you! Our engineering and sales team will contact you shortly with the specifications and wholesale quotation.',
            quick_submit_btn: 'Submit Inquiry Directly',
            quick_submit_tag: "I'm done",
            quick_submit_chip: 'Submit Now',
            email_saved_notice: '<div class="wpi-notice-pill"><span class="wpi-check-icon">✓</span> <strong>Contact details saved!</strong> Answer a few quick options for an exact quote, or submit directly at any time.</div>',
            step_badge: 'Step {current}/{total}',
            wa_urgent_prompt: 'Need urgent response? Click below to chat directly with our engineer on WhatsApp:',
            wa_btn_label: 'Chat on WhatsApp',
        },
        zh: {
            bot_name: '业务与技术支持',
            status_text: '在线 | 工厂直达响应',
            context_tag: '当前咨询产品：',
            input_placeholder: '输入 WhatsApp、微信、邮箱或咨询需求...',
            footer_note: '隐私已加密保护 | 直通工厂工程部',
            close_title: '最小化',
            launcher_aria: '打开咨询窗口',
            placeholder_default: '输入您的回答或点击下方选项...',
            placeholder_email: '请输入联系人与带 @ 的业务邮箱...',
            placeholder_notes: '可继续输入补充需求或技术要求...',
            note_added: '补充说明已同步至您的询盘工单。',
            faq_follow_up: '顺便请问，关于刚才的问题：',
            email_required_error: '请提供包含“@”的真实有效业务邮箱（如 name@company.com），以便工程师为您发送图纸与出厂底价。',
            email_test_error: '不接受测试或临时邮箱，请提供真实可用的业务邮箱以便发送正式规格书与报价。',
            email_fallback: '请提供您的姓名和带“@”的业务邮箱，方便工程师发送出厂报价与规格书。',
            lead_success_msg: '感谢您的垂询！我们的工程与销售团队将在2小时内通过邮件联系您，发送出厂报价与完整规格书。',
            quick_submit_btn: '直接提交询盘',
            quick_submit_tag: '已填好',
            quick_submit_chip: '直接提交',
            email_saved_notice: '<div class="wpi-notice-pill"><span class="wpi-check-icon">✓</span> <strong>联系方式已保存！</strong> 您可继续选择下方规格以便精准核算，或随时点击直接提交。</div>',
            step_badge: '第 {current}/{total} 步',
            wa_urgent_prompt: '如需加急对接，亦可直接通过 WhatsApp 联系技术经理：',
            wa_btn_label: 'WhatsApp 即时沟通',
        }
    };

    const i18n = Object.assign({}, i18nDict[activeLang] || i18nDict.en, (WPI_CONFIG.i18n && WPI_CONFIG.i18n[activeLang]) ? WPI_CONFIG.i18n[activeLang] : {});

    // State Variables
    let isOpen = false;
    let lastAutoOpenTime = 0;
    const pageLoadTime = Date.now();
    let currentProduct = null;
    let leadCaptured = false;
    let flowCompleted = false;
    let currentQuestionIndex = 0;
    const inquiryData = {
        session_id: getOrCreateSessionId(),
        customer_name: '',
        email: '',
        phone: '',
        product_name: '',
        source_url: window.location.href,
        product_url: window.location.href,
        form_data: {},
        dwell_time: 0,
    };

    // DOM Elements
    let launcherBtn, chatBox, messagesContainer, actionsContainer, userInput, sendBtn, closeBtn, contextBanner, contextTitle;
    let quickSubmitBar, quickSubmitBtn, quickSubmitText, quickSubmitTag;

    // Initialize on DOM ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

    function init() {
        launcherBtn       = document.getElementById('wpi-launcher');
        chatBox           = document.getElementById('wpi-chatbox');
        messagesContainer = document.getElementById('wpi-messages');
        actionsContainer  = document.getElementById('wpi-actions');
        userInput         = document.getElementById('wpi-user-input');
        sendBtn           = document.getElementById('wpi-send-btn');
        closeBtn          = document.getElementById('wpi-close-btn');
        contextBanner     = document.getElementById('wpi-context-banner');
        contextTitle      = document.getElementById('wpi-context-title');

        if (!launcherBtn || !chatBox) {
            return;
        }
        const widgetContainer = document.getElementById('wpi-widget');
        const bottomOverlap = document.getElementById('zxtlcd-wa-float') || document.getElementById('zxtlcd-wa-float-ds') || document.querySelector('.joinchat--right') || document.getElementById('ht-ctc-chat') || document.querySelector('.ht-ctc-chat');
        if (bottomOverlap && widgetContainer) {
            widgetContainer.style.bottom = window.innerWidth <= 480 ? '76px' : '90px';
        }
        quickSubmitBar  = document.getElementById('wpi-quick-submit-bar');
        quickSubmitBtn  = document.getElementById('wpi-quick-submit-btn');
        quickSubmitText = document.getElementById('wpi-quick-submit-text');
        quickSubmitTag  = document.getElementById('wpi-quick-submit-tag');

        if (quickSubmitText && i18n.quick_submit_btn) quickSubmitText.innerText = i18n.quick_submit_btn;
        if (quickSubmitTag && i18n.quick_submit_tag) quickSubmitTag.innerText = i18n.quick_submit_tag;

        // Apply dynamic i18n strings to DOM
        const statusTextEl = document.getElementById('wpi-status-text');
        if (statusTextEl && i18n.status_text) statusTextEl.innerText = i18n.status_text;
        const contextTagEl = document.getElementById('wpi-context-tag');
        if (contextTagEl && i18n.context_tag) contextTagEl.innerText = i18n.context_tag;
        const footerNoteEl = document.getElementById('wpi-footer-note');
        if (footerNoteEl && i18n.footer_note) footerNoteEl.innerText = i18n.footer_note;
        if (userInput && i18n.input_placeholder) userInput.placeholder = i18n.input_placeholder;
        if (launcherBtn && i18n.launcher_aria) launcherBtn.setAttribute('aria-label', i18n.launcher_aria);
        if (closeBtn && i18n.close_title) closeBtn.title = i18n.close_title;
        // 1. Detect Product Context
        detectProductContext();

        // 2. Bind UI Events
        bindEvents();

        // 4. Initialize Flow
        renderInitialGreeting();

        // 5. Setup Proactive Behavior Triggers
        setupTriggers();
    }

    function getOrCreateSessionId() {
        let sid = localStorage.getItem('wpi_sid');
        if (!sid) {
            sid = 'sid_' + Math.random().toString(36).substring(2, 12) + '_' + Date.now();
            localStorage.setItem('wpi_sid', sid);
        }
        return sid;
    }

    function detectProductContext() {
        const wcTitle = document.querySelector('.product_title');
        const h1Title = document.querySelector('h1.entry-title, h1');
        let title = '';

        if (wcTitle && wcTitle.innerText.trim()) {
            title = wcTitle.innerText.trim();
        } else if (h1Title && h1Title.innerText.trim() && h1Title.innerText.length < 80) {
            title = h1Title.innerText.trim();
        } else {
            const cleanTitle = document.title.split(/[-–|]/)[0].trim();
            if (cleanTitle && cleanTitle.length > 2 && cleanTitle.length < 70) {
                title = cleanTitle;
            }
        }

        if (title && !isHomePage()) {
            currentProduct = {
                title: title,
                url: window.location.href,
            };
            inquiryData.product_name = title;
            inquiryData.product_url = window.location.href;

            if (contextBanner && contextTitle) {
                contextTitle.innerText = title;
                contextBanner.style.display = 'block';
            }
        }
    }

    function isHomePage() {
        return window.location.pathname === '/' || window.location.pathname === '';
    }


    function bindEvents() {
        launcherBtn.addEventListener('click', toggleChat);
        closeBtn.addEventListener('click', closeChat);

        sendBtn.addEventListener('click', handleUserSend);
        userInput.addEventListener('keydown', function (e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                handleUserSend();
            }
        });

        if (quickSubmitBtn) {
            quickSubmitBtn.addEventListener('click', handleQuickSubmit);
        }
    }

    function showQuickSubmitBar() {
        if (quickSubmitBar && !flowCompleted) {
            quickSubmitBar.style.display = 'flex';
        }
    }

    function hideQuickSubmitBar() {
        if (quickSubmitBar) {
            quickSubmitBar.style.display = 'none';
        }
    }

    function handleQuickSubmit() {
        if (flowCompleted) {
            return;
        }

        // Must have at least captured email or phone
        if (!leadCaptured && !inquiryData.email && !inquiryData.phone) {
            appendBotMessage(i18n.email_required_error);
            userInput.placeholder = i18n.placeholder_email;
            userInput.focus();
            return;
        }

        flowCompleted = true;
        hideQuickSubmitBar();
        actionsContainer.innerHTML = '';

        const successMsg = WPI_CONFIG.leadSuccessMsg || i18n.lead_success_msg;
        appendBotMessage(successMsg);

        // If WhatsApp number is configured, offer immediate 1-click wa.me chat
        const waNum = (WPI_CONFIG.whatsappNumber || '').replace(/[^\d]/g, '');
        if (waNum) {
            const pTitle = currentProduct ? currentProduct.title : document.title.split(/[-–|]/)[0].trim();
            const waText = 'Hello, I just submitted an inquiry for "' + pTitle + '" on your website (' + window.location.href + '). Please send quotation & specifications.';
            const waUrl = 'https://wa.me/' + waNum + '?text=' + encodeURIComponent(waText);
            const waPrompt = '<div class="wpi-wa-card"><p>' + (i18n.wa_urgent_prompt || 'Need urgent response?') + '</p><a href="' + waUrl + '" target="_blank" rel="noopener noreferrer" class="wpi-wa-link">💬 ' + (i18n.wa_btn_label || 'Chat on WhatsApp') + '</a></div>';
            appendBotMessage(waPrompt, true);
        }

        userInput.placeholder = i18n.placeholder_notes;
        submitLead({ isUpdate: true, isFinal: true, silent: false });
    }

    function toggleChat() {
        if (isOpen) {
            if (Date.now() - lastAutoOpenTime < 1000) {
                return;
            }
            closeChat();
        } else {
            openChat('manual');
        }
    }

    function openChat(triggerReason) {
        if (isOpen) return;
        isOpen = true;
        if (triggerReason !== 'manual') {
            lastAutoOpenTime = Date.now();
        }
        chatBox.style.display = 'flex';
        launcherBtn.querySelector('.wpi-icon-chat').style.display = 'none';
        launcherBtn.querySelector('.wpi-icon-close').style.display = 'block';
        launcherBtn.querySelector('.wpi-badge-dot').style.display = 'none';

        setTimeout(() => {
            if (userInput) userInput.focus();
        }, 150);

        sessionStorage.setItem('wpi_opened', '1');
    }

    function closeChat() {
        if (!isOpen) return;
        isOpen = false;
        chatBox.style.display = 'none';
        launcherBtn.querySelector('.wpi-icon-chat').style.display = 'block';
        launcherBtn.querySelector('.wpi-icon-close').style.display = 'none';
        sessionStorage.setItem('wpi_dismissed', '1');
    }

    function setupTriggers() {
        if (sessionStorage.getItem('wpi_opened') || sessionStorage.getItem('wpi_dismissed')) {
            return;
        }

        const seconds = parseInt(WPI_CONFIG.autoPopupSeconds, 10);
        if (seconds > 0) {
            setTimeout(() => {
                if (!sessionStorage.getItem('wpi_opened') && !sessionStorage.getItem('wpi_dismissed')) {
                    openChat('timer');
                }
            }, seconds * 1000);
        }

        if (WPI_CONFIG.exitIntent) {
            document.addEventListener('mouseleave', function (e) {
                if (e.clientY <= 0 && Date.now() - pageLoadTime > 3000 && !sessionStorage.getItem('wpi_opened') && !sessionStorage.getItem('wpi_dismissed')) {
                    openChat('exit_intent');
                }
            });
        }

        const scrollThreshold = parseInt(WPI_CONFIG.scrollTrigger, 10);
        if (scrollThreshold > 0) {
            const handleScroll = function () {
                const maxScroll = document.documentElement.scrollHeight - window.innerHeight;
                if (maxScroll > 300) {
                    const scrollPercent = (window.scrollY / maxScroll) * 100;
                    if (scrollPercent >= scrollThreshold && !sessionStorage.getItem('wpi_opened') && !sessionStorage.getItem('wpi_dismissed')) {
                        openChat('scroll');
                        window.removeEventListener('scroll', handleScroll);
                    }
                }
            };
            window.addEventListener('scroll', handleScroll, { passive: true });
        }
    }

    /**
     * Initial Greeting & Kick off Question 1
     */
    function renderInitialGreeting() {
        currentQuestionIndex = 0;
        const questions = getQuestions();
        const q1 = questions[0];

        // If Question 1 is already an upfront email/greeting hook, go straight to it
        if (q1 && (q1.type === 'email' || q1.type === 'name_email' || q1.require_at_email || /^(Hi|Hello|您好|你好)/i.test(q1.question))) {
            askCurrentQuestion();
            return;
        }

        let greeting = '';
        if (currentProduct) {
            greeting = (WPI_CONFIG.welcomeMsg || '').replace('{product_name}', currentProduct.title);
        } else {
            greeting = WPI_CONFIG.defaultWelcomeMsg || 'Hello! How can we assist your business today?';
        }
        appendBotMessage(greeting);
        askCurrentQuestion();
    }

    function getQuestions() {
        return WPI_CONFIG.formQuestions || [];
    }

    /**
     * Render the active probing question
     */
    function askCurrentQuestion() {
        const questions = getQuestions();
        if (currentQuestionIndex >= questions.length) {
            // All questions finished
            if (!flowCompleted && inquiryData.email) {
                flowCompleted = true;
                hideQuickSubmitBar();
                submitLead({ isUpdate: true, isFinal: true, silent: false });
            }
            return;
        }

        const q = questions[currentQuestionIndex];
        let qText = q.question || '';
        if (currentProduct) {
            qText = qText.replace(/\{product_name\}/g, currentProduct.title);
        } else {
            qText = qText.replace(/\s*(for|about)?\s*\{product_name\}/gi, ' for our products');
        }

        // Render question text with step progress badge if multi-question flow
        const totalSteps = questions.length;
        if (totalSteps > 1) {
            const currentStep = currentQuestionIndex + 1;
            const stepBadgeTemplate = i18n.step_badge || 'Step {current}/{total}';
            const stepBadgeText = stepBadgeTemplate.replace('{current}', currentStep).replace('{total}', totalSteps);
            appendBotMessage('<span class="wpi-step-badge">' + stepBadgeText + '</span> ' + qText, true);
        } else {
            appendBotMessage(qText);
        }

        // If contact info has been secured, ensure quick submit bar is displayed
        if (leadCaptured && !flowCompleted) {
            showQuickSubmitBar();
        }

        // Update placeholder
        if (q.placeholder) {
            userInput.placeholder = q.placeholder;
        } else if (q.require_at_email || q.type === 'email' || q.type === 'name_email') {
            userInput.placeholder = 'Enter your business Email (name@company.com)...';
        } else {
            userInput.placeholder = i18n.placeholder_default;
        }

        // Render Action Chips
        actionsContainer.innerHTML = '';
        if (q.options && q.options.length > 0) {
            q.options.forEach(optText => {
                const btn = document.createElement('button');
                btn.className = 'wpi-chip-btn';
                btn.type = 'button';
                btn.innerText = optText;
                btn.onclick = () => {
                    handleAnswer(optText);
                };
                actionsContainer.appendChild(btn);
            });

            // If contact is already secured, also offer direct submit chip alongside options
            if (leadCaptured && !flowCompleted) {
                const submitChip = document.createElement('button');
                submitChip.className = 'wpi-chip-btn wpi-chip-submit-now';
                submitChip.type = 'button';
                submitChip.innerHTML = '⚡ ' + (i18n.quick_submit_chip || 'Submit Now');
                submitChip.onclick = () => {
                    handleQuickSubmit();
                };
                actionsContainer.appendChild(submitChip);
            }
        }
    }

    /**
     * Handle user submission or chip selection
     */
    function handleUserSend() {
        const text = userInput.value.trim();
        if (!text) return;

        appendUserMessage(text);
        userInput.value = '';
        const questions = getQuestions();
        const isFlowDone = currentQuestionIndex >= questions.length;

        // If all structured questions are already completed, subsequent messages are extra project notes
        if (isFlowDone) {
            inquiryData.form_data['Additional Notes'] = (inquiryData.form_data['Additional Notes'] ? inquiryData.form_data['Additional Notes'] + ' | ' : '') + text;
            appendBotMessage(i18n.note_added);
            submitLead({ isUpdate: true, isFinal: false, silent: true });
            return;
        }

        // Check if user is asking a FAQ
        const faqAnswer = matchFaq(text);
        if (faqAnswer) {
            setTimeout(() => {
                appendBotMessage(faqAnswer);
                setTimeout(() => {
                    appendBotMessage(i18n.faq_follow_up);
                    askCurrentQuestion();
                }, 600);
            }, 300);
            return;
        }

        handleAnswer(text);
    }

    function handleAnswer(answerText) {
        const questions = getQuestions();
        if (currentQuestionIndex >= questions.length) {
            return;
        }

        const q = questions[currentQuestionIndex];

        // 1. DIRECT EMAIL STEP ("email", "name_email", or "require_at_email")
        if (q.require_at_email || q.type === 'email' || q.type === 'name_email') {
            const detectedEmail = extractEmail(answerText);

            if (detectedEmail) {
                if (isTestEmail(detectedEmail)) {
                    setTimeout(() => {
                        appendBotMessage(i18n.email_test_error || 'Please provide a reachable business email address. Test accounts cannot receive official quotes and specifications.');
                        userInput.placeholder = i18n.placeholder_email || 'Please enter genuine business email...';
                        userInput.focus();
                    }, 200);
                    return; // DO NOT ADVANCE!
                }

                // Valid email found!
                inquiryData.email = detectedEmail;

                // Extract Phone if present
                let remaining = answerText.replace(detectedEmail, '').trim();
                const phoneMatch = remaining.match(/(\+?\d[\d\s\-.()]{6,18}\d)/);
                if (phoneMatch && phoneMatch[0].replace(/\D/g, '').length >= 7) {
                    inquiryData.phone = phoneMatch[0].trim();
                    remaining = remaining.replace(phoneMatch[0], '').trim();
                }

                // If user happened to type a name along with email, or infer from email prefix
                const nameInInput = cleanNameString(remaining);
                if (nameInInput && nameInInput.length >= 2) {
                    inquiryData.customer_name = nameInInput;
                } else if (!inquiryData.customer_name) {
                    const userPart = detectedEmail.split('@')[0];
                    const parts = userPart.split(/[._-]/).filter(p => p.length > 1 && !/\d/.test(p));
                    if (parts.length > 0 && parts.length <= 3) {
                        inquiryData.customer_name = parts.map(p => p.charAt(0).toUpperCase() + p.slice(1).toLowerCase()).join(' ');
                    }
                }

                leadCaptured = true;
                // Advance to next question
                currentQuestionIndex++;
                actionsContainer.innerHTML = '';

                // SECURE THE LEAD IMMEDIATELY in background database
                submitLead({ isUpdate: false, isFinal: false, silent: true });

                if (currentQuestionIndex < questions.length) {
                    showQuickSubmitBar();
                    appendBotMessage(i18n.email_saved_notice, true);
                    setTimeout(askCurrentQuestion, 600);
                } else {
                    flowCompleted = true;
                    hideQuickSubmitBar();
                    const successMsg = WPI_CONFIG.leadSuccessMsg || i18n.lead_success_msg;
                    appendBotMessage(successMsg);
                    userInput.placeholder = i18n.placeholder_notes;
                    submitLead({ isUpdate: true, isFinal: true, silent: false });
                }
                return;
            }

            // NO EMAIL FOUND IN INPUT! Directly prompt for business email:
            setTimeout(() => {
                const errorMsg = q.prompt_error || 'Please provide a valid business email containing "@" (e.g. name@company.com) so our engineer can send the specifications.';
                appendBotMessage(errorMsg);
                userInput.placeholder = 'Enter your business Email (name@company.com)...';
                userInput.focus();
            }, 200);
            return;
        }

        // 2. Regular Question or Choice Question
        if (answerText.indexOf('Chat on WhatsApp') !== -1 || answerText.indexOf('WhatsApp') !== -1) {
            try {
                const waNum = (WPI_CONFIG.whatsappNumber || '').replace(/[^\d]/g, '');
                if (waNum) {
                    const pTitle = currentProduct ? currentProduct.title : document.title.split(/[-–|]/)[0].trim();
                    const waText = 'Hello, I am interested in "' + pTitle + '" from your website (' + window.location.href + '). Please send quotation & specifications.';
                    window.open('https://wa.me/' + waNum + '?text=' + encodeURIComponent(waText), '_blank');
                }
            } catch (e) {}
        }
        if (answerText.indexOf('微信') !== -1) {
            const wechatNum = WPI_CONFIG.whatsappNumber || '';
            if (wechatNum) {
                appendBotMessage('技术经理微信/电话：' + wechatNum + ' （长按可复制），欢迎随时添加微信获取即时报价与图纸！');
            }
        }
        if (answerText.indexOf('Skip') !== -1 || answerText.indexOf('跳过') !== -1) {
            currentQuestionIndex++;
            actionsContainer.innerHTML = '';
            if (currentQuestionIndex >= questions.length) {
                flowCompleted = true;
                hideQuickSubmitBar();
                if (inquiryData.email) {
                    const successMsg = WPI_CONFIG.leadSuccessMsg || i18n.lead_success_msg;
                    appendBotMessage(successMsg);
                    userInput.placeholder = i18n.placeholder_notes;
                    submitLead({ isUpdate: true, isFinal: true, silent: false });
                } else {
                    appendBotMessage(i18n.email_fallback);
                    userInput.placeholder = i18n.placeholder_email;
                }
            } else {
                if (leadCaptured) {
                    submitLead({ isUpdate: true, isFinal: false, silent: true });
                }
                setTimeout(askCurrentQuestion, 300);
            }
            return;
        }
        // Store answer with clean short label (prevent polluting specs with full prompt sentences)
        if (q.id === 'whatsapp_contact' || q.type === 'phone') {
            const phoneMatch = answerText.match(/(\+?\d[\d\s\-.()]{6,18}\d)/);
            if (phoneMatch && phoneMatch[0].replace(/\D/g, '').length >= 7) {
                inquiryData.phone = phoneMatch[0].trim();
            }
        } else {
            const labelKey = q.label || getShortLabel(q.id, q.question);
            inquiryData.form_data[labelKey] = answerText;
        }
        // Auto parse Customer Name if question asks for Name
        if (q.id === 'name' || q.id === 'customer_name' || /^(your\s+)?name$/i.test(q.id) || /what('s|\s+is)\s+your\s+name/i.test(q.question)) {
            const cleaned = cleanNameString(answerText);
            if (cleaned) {
                inquiryData.customer_name = cleaned;
            }
        }

        // Advance to next question
        currentQuestionIndex++;
        actionsContainer.innerHTML = '';

        if (currentQuestionIndex >= questions.length) {
            flowCompleted = true;
            hideQuickSubmitBar();
            if (inquiryData.email) {
                const successMsg = WPI_CONFIG.leadSuccessMsg || i18n.lead_success_msg;
                appendBotMessage(successMsg);
                userInput.placeholder = i18n.placeholder_notes;
                submitLead({ isUpdate: true, isFinal: true, silent: false });
            } else {
                appendBotMessage(i18n.email_fallback);
                userInput.placeholder = i18n.placeholder_email;
            }
        } else {
            // Intermediate question answered: silently sync to DB without notification spam
            if (leadCaptured) {
                submitLead({ isUpdate: true, isFinal: false, silent: true });
            }
            setTimeout(askCurrentQuestion, 400);
        }
    }
    function cleanNameString(text) {
        if (!text || typeof text !== 'string') return '';
        let cleaned = text.trim();
        // Remove conversational intros: "my name is", "i am", "i'm", "this is", "name is", "name:", "我是", "叫我"
        cleaned = cleaned.replace(/^(my\s+name\s+is|i\s+am|i'm|this\s+is|name\s*[:：]|我是|叫我)\s*/i, '');
        // Strip out email or phone if mixed in
        const emailMatch = extractEmail(cleaned);
        if (emailMatch) {
            cleaned = cleaned.replace(emailMatch, '');
        }
        cleaned = cleaned.replace(/(\+?\d[\d\s\-.()]{6,18}\d)/, '');
        // Remove punctuation and cleanup whitespace
        cleaned = cleaned.replace(/^[,\s;:\-_/|]+|[,\s;:\-_/|.!?:;，。！]+$/g, '').trim();
        return cleaned;
    }

    function extractEmail(text) {
        if (!text || text.indexOf('@') === -1) {
            return null;
        }
        const match = text.match(/[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}/);
        return match ? match[0].toLowerCase() : null;
    }
    function isTestEmail(email) {
        if (!email || typeof email !== 'string' || email.indexOf('@') === -1) {
            return false;
        }
        const parts = email.toLowerCase().trim().split('@');
        const user = parts[0];
        const domain = parts[1] || '';
        const testDomains = ['test.com', 'example.com', 'sample.com', 'tempmail.com', 'testmail.com', 'fakemail.com'];
        if (testDomains.indexOf(domain) !== -1) {
            return true;
        }
        const tokens = user.split(/[._+\-]/);
        return tokens.some(function (t) {
            return /^(test\d*|\d*test)$/i.test(t);
        });
    }
    function getShortLabel(id, question) {
        id = (id || '').toLowerCase();
        question = (question || '').toLowerCase();
        if (id === 'touchscreen' || question.includes('touch') || question.includes('触控') || question.includes('触摸')) return '触控方式';
        if (id === 'screen_size' || question.includes('screen') || question.includes('size') || question.includes('尺寸')) return '屏幕尺寸';
        if (id === 'os_preference' || question.includes('android') || question.includes('windows') || question.includes('系统') || question.includes(' os')) return '操作系统';
        if (id === 'hardware_specs' || question.includes('processor') || question.includes('ram') || question.includes('configuration') || question.includes('配置')) return '配置要求';
        if (id === 'quantity' || question.includes('quantity') || question.includes('volume') || question.includes('pcs') || question.includes('批量') || question.includes('数量')) return '预估批量';
        if (id === 'specs_req' || question.includes('drawing') || question.includes('spec') || question.includes('图纸') || question.includes('需求')) return '采购意向';
        return question.replace(/[:?？：]+$/, '').trim().substring(0, 14);
    }


    function matchFaq(text) {
        const faqs = WPI_CONFIG.faqList || [];
        const lower = text.toLowerCase();

        for (let i = 0; i < faqs.length; i++) {
            const item = faqs[i];
            const keywords = item.keywords || [];
            for (let j = 0; j < keywords.length; j++) {
                if (lower.includes(keywords[j].toLowerCase())) {
                    return item.answer;
                }
            }
        }
        return null;
    }

    function submitLead(opts) {
        opts = opts || {};
        const isUpdate = !!opts.isUpdate;
        const isFinal  = !!opts.isFinal;
        const silent   = !!opts.silent;

        inquiryData.dwell_time = Math.round((Date.now() - pageLoadTime) / 1000);

        const payload = Object.assign({}, inquiryData, {
            silent: silent,
            is_final: isFinal,
        });

        fetch(WPI_CONFIG.apiUrl + 'lead', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-WP-Nonce': WPI_CONFIG.nonce,
            },
            body: JSON.stringify(payload),
        })
            .then(res => res.json())
            .then(data => {
                if (isFinal) {
                    flowCompleted = true;
                }
            })
            .catch(err => {
                // Silent fallback on network error
            });
    }

    // Exit sync: If user leaves page mid-way after providing email, ensure lead dossier is dispatched once
    function handleExitSync() {
        if (leadCaptured && !flowCompleted && inquiryData.email) {
            inquiryData.dwell_time = Math.round((Date.now() - pageLoadTime) / 1000);
            const payload = Object.assign({}, inquiryData, {
                silent: false,
                is_final: true,
            });
            const bodyStr = JSON.stringify(payload);
            if (navigator.sendBeacon) {
                const blob = new Blob([bodyStr], { type: 'application/json' });
                navigator.sendBeacon(WPI_CONFIG.apiUrl + 'lead', blob);
            } else {
                fetch(WPI_CONFIG.apiUrl + 'lead', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-WP-Nonce': WPI_CONFIG.nonce,
                    },
                    body: bodyStr,
                    keepalive: true,
                });
            }
            flowCompleted = true;
        }
    }

    window.addEventListener('beforeunload', handleExitSync);
    document.addEventListener('visibilitychange', function () {
        if (document.visibilityState === 'hidden') {
            handleExitSync();
        }
    });

    function appendBotMessage(text, isHtml) {
        const div = document.createElement('div');
        div.className = 'wpi-msg wpi-msg-bot';
        if (isHtml || /<[a-z][\s\S]*>/i.test(text)) {
            div.innerHTML = text;
        } else {
            div.innerText = text;
        }
        messagesContainer.appendChild(div);
        messagesContainer.scrollTop = messagesContainer.scrollHeight;
    }

    function appendUserMessage(text) {

        const div = document.createElement('div');
        div.className = 'wpi-msg wpi-msg-user';
        div.innerText = text;
        messagesContainer.appendChild(div);
        messagesContainer.scrollTop = messagesContainer.scrollHeight;
    }
})();
